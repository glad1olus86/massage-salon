<?php

namespace App\Services;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Collection;

class CashHierarchyService
{
    /**
     * Role hierarchy constants
     * Defines who can distribute to whom
     */
    const ROLE_BOSS = 'boss';
    const ROLE_MANAGER = 'manager';
    const ROLE_CURATOR = 'curator';
    const ROLE_WORKER = 'worker';

    /**
     * Hierarchy map: role => [allowed recipients]
     * 'self' means can distribute to self
     * 'self_once' means can distribute to self once per period
     */
    const HIERARCHY = [
        self::ROLE_BOSS => [self::ROLE_MANAGER, self::ROLE_CURATOR, self::ROLE_WORKER, 'self'],
        self::ROLE_MANAGER => [self::ROLE_CURATOR, self::ROLE_WORKER, 'self_once'],
        self::ROLE_CURATOR => [self::ROLE_WORKER],
    ];

    /**
     * Check if sender can distribute money to recipient
     *
     * @param User $sender
     * @param User|Worker $recipient
     * @return bool
     */
    public function canDistributeTo(User $sender, $recipient): bool
    {
        $senderRole = $this->getUserCashboxRole($sender);
        
        if (!$senderRole) {
            return false;
        }

        // Boss can distribute to anyone
        if ($senderRole === self::ROLE_BOSS) {
            return true;
        }

        // Get allowed recipient roles for sender
        $allowedRoles = self::HIERARCHY[$senderRole] ?? [];

        // If recipient is a Worker model
        if ($recipient instanceof Worker) {
            return in_array(self::ROLE_WORKER, $allowedRoles);
        }

        // If recipient is a User
        if ($recipient instanceof User) {
            // Check if distributing to self
            if ($sender->id === $recipient->id) {
                return in_array('self', $allowedRoles) || in_array('self_once', $allowedRoles);
            }

            $recipientRole = $this->getUserCashboxRole($recipient);
            
            if (!$recipientRole) {
                return false;
            }

            // Manager cannot distribute to other managers (Requirement 1.5)
            if ($senderRole === self::ROLE_MANAGER && $recipientRole === self::ROLE_MANAGER) {
                return false;
            }

            // Curator cannot distribute to other curators (Requirement 1.6)
            if ($senderRole === self::ROLE_CURATOR && $recipientRole === self::ROLE_CURATOR) {
                return false;
            }

            return in_array($recipientRole, $allowedRoles);
        }

        return false;
    }

    /**
     * Get list of available recipients for a sender within a company
     *
     * @param User $sender
     * @param int $companyId
     * @return Collection
     */
    public function getAvailableRecipients(User $sender, int $companyId): Collection
    {
        $senderRole = $this->getUserCashboxRole($sender);
        $recipients = collect();

        if (!$senderRole) {
            return $recipients;
        }

        $allowedRoles = self::HIERARCHY[$senderRole] ?? [];

        // Get users with allowed roles from the same company
        $users = User::where('created_by', $companyId)
            ->where('is_active', 1)
            ->get();

        foreach ($users as $user) {
            $userRole = $this->getUserCashboxRole($user);
            
            if ($userRole && in_array($userRole, $allowedRoles)) {
                // Skip same role restrictions
                if ($senderRole === self::ROLE_MANAGER && $userRole === self::ROLE_MANAGER) {
                    continue;
                }
                if ($senderRole === self::ROLE_CURATOR && $userRole === self::ROLE_CURATOR) {
                    continue;
                }
                
                $recipients->push([
                    'id' => $user->id,
                    'type' => User::class,
                    'name' => $user->name,
                    'role' => $userRole,
                ]);
            }
        }

        // Add workers if allowed
        if (in_array(self::ROLE_WORKER, $allowedRoles)) {
            $workers = Worker::where('created_by', $companyId)->get();
            
            foreach ($workers as $worker) {
                $recipients->push([
                    'id' => $worker->id,
                    'type' => Worker::class,
                    'name' => $worker->first_name . ' ' . $worker->last_name,
                    'role' => self::ROLE_WORKER,
                ]);
            }
        }

        // Add self if allowed (for self-salary)
        if (in_array('self', $allowedRoles) || in_array('self_once', $allowedRoles)) {
            // Check if sender is not already in the list
            $senderInList = $recipients->contains(function ($item) use ($sender) {
                return $item['type'] === User::class && $item['id'] === $sender->id;
            });

            if (!$senderInList) {
                $recipients->push([
                    'id' => $sender->id,
                    'type' => User::class,
                    'name' => $sender->name . ' (себе)',
                    'role' => $senderRole,
                    'is_self' => true,
                ]);
            }
        }

        return $recipients;
    }

    /**
     * Check if sender can refund money to recipient
     * Refund is only allowed to the person who gave the money (Requirement 7.4)
     *
     * @param User $sender The one returning money
     * @param User $recipient The one receiving the refund (original sender)
     * @return bool
     */
    public function canRefundTo(User $sender, User $recipient): bool
    {
        $senderRole = $this->getUserCashboxRole($sender);
        $recipientRole = $this->getUserCashboxRole($recipient);

        if (!$senderRole || !$recipientRole) {
            return false;
        }

        // Cannot refund to self
        if ($sender->id === $recipient->id) {
            return false;
        }

        // Refund hierarchy (reverse of distribution)
        // Manager can refund to Boss
        // Curator can refund to Manager or Boss
        $refundHierarchy = [
            self::ROLE_MANAGER => [self::ROLE_BOSS],
            self::ROLE_CURATOR => [self::ROLE_MANAGER, self::ROLE_BOSS],
        ];

        $allowedRefundTo = $refundHierarchy[$senderRole] ?? [];

        return in_array($recipientRole, $allowedRefundTo);
    }

    /**
     * Get the cashbox role for a user
     *
     * @param User $user
     * @return string|null
     */
    public function getUserCashboxRole(User $user): ?string
    {
        // Company type users are always boss
        if ($user->type === 'company') {
            return self::ROLE_BOSS;
        }

        // Check for specific cashbox view permissions (highest priority)
        if ($user->can('cashbox_view_boss')) {
            return self::ROLE_BOSS;
        }

        if ($user->can('cashbox_view_manager')) {
            return self::ROLE_MANAGER;
        }

        if ($user->can('cashbox_view_curator')) {
            return self::ROLE_CURATOR;
        }

        // Fallback: Check for specific cashbox roles using Spatie roles
        if ($user->hasRole('boss')) {
            return self::ROLE_BOSS;
        }

        if ($user->hasRole('manager')) {
            return self::ROLE_MANAGER;
        }

        if ($user->hasRole('curator')) {
            return self::ROLE_CURATOR;
        }

        return null;
    }

    /**
     * Check if user can distribute to self (for self-salary)
     *
     * @param User $user
     * @return bool
     */
    public function canDistributeToSelf(User $user): bool
    {
        $role = $this->getUserCashboxRole($user);
        
        if (!$role) {
            return false;
        }

        $allowedRoles = self::HIERARCHY[$role] ?? [];
        
        return in_array('self', $allowedRoles) || in_array('self_once', $allowedRoles);
    }

    /**
     * Check if user has self-salary limit (once per period)
     *
     * @param User $user
     * @return bool
     */
    public function hasSelfSalaryLimit(User $user): bool
    {
        $role = $this->getUserCashboxRole($user);
        
        if (!$role) {
            return true;
        }

        $allowedRoles = self::HIERARCHY[$role] ?? [];
        
        // Boss has no limit, Manager has limit
        return in_array('self_once', $allowedRoles);
    }

    /**
     * Get the view permission level for cashbox diagram
     * Determines what level of detail user can see in the diagram
     * Priority: boss > manager > curator
     *
     * @param User $user
     * @return string|null 'boss', 'manager', 'curator' or null if no access
     */
    public function getViewPermissionLevel(User $user): ?string
    {
        // Check permissions in priority order (highest first)
        if ($user->can('cashbox_view_boss')) {
            return self::ROLE_BOSS;
        }
        
        if ($user->can('cashbox_view_manager')) {
            return self::ROLE_MANAGER;
        }
        
        if ($user->can('cashbox_view_curator')) {
            return self::ROLE_CURATOR;
        }
        
        return null;
    }
}
