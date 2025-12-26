<?php

namespace App\Services;

use App\Models\ManagerCurator;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ManagerCuratorService
{
    /**
     * Assign a curator to a manager.
     * Only Directors can do this.
     */
    public function assignCuratorToManager(int $curatorId, int $managerId): ManagerCurator
    {
        $currentUser = Auth::user();

        if (!$currentUser->isDirector()) {
            throw new \Exception(__('Only directors can assign curators to managers.'));
        }

        $curator = User::where('id', $curatorId)
            ->where('created_by', $currentUser->creatorId())
            ->whereHas('roles', fn($q) => $q->where('name', 'curator'))
            ->firstOrFail();

        $manager = User::where('id', $managerId)
            ->where('created_by', $currentUser->creatorId())
            ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
            ->firstOrFail();

        // Check if already assigned
        $existing = ManagerCurator::where('manager_id', $managerId)
            ->where('curator_id', $curatorId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $assignment = ManagerCurator::create([
            'manager_id' => $managerId,
            'curator_id' => $curatorId,
            'created_by' => $currentUser->id,
        ]);

        AuditService::logCuratorAssigned($manager, $curator);

        return $assignment;
    }

    /**
     * Remove a curator from a manager.
     * Only Directors can do this.
     */
    public function removeCuratorFromManager(int $curatorId, int $managerId): bool
    {
        $currentUser = Auth::user();

        if (!$currentUser->isDirector()) {
            throw new \Exception(__('Only directors can remove curators from managers.'));
        }

        $curator = User::findOrFail($curatorId);
        $manager = User::findOrFail($managerId);

        $deleted = ManagerCurator::where('manager_id', $managerId)
            ->where('curator_id', $curatorId)
            ->delete();

        if ($deleted) {
            AuditService::logCuratorRemoved($manager, $curator);
        }

        return $deleted > 0;
    }

    /**
     * Get all curators assigned to a manager.
     */
    public function getCuratorsForManager(int $managerId): Collection
    {
        $manager = User::findOrFail($managerId);
        return $manager->assignedCurators;
    }

    /**
     * Get all managers a curator is assigned to.
     */
    public function getManagersForCurator(int $curatorId): Collection
    {
        $curator = User::findOrFail($curatorId);
        return $curator->assignedManagers;
    }

    /**
     * Get all curators in the company (for director's view).
     */
    public function getAllCurators(): Collection
    {
        $currentUser = Auth::user();

        return User::where('created_by', $currentUser->creatorId())
            ->whereHas('roles', fn($q) => $q->where('name', 'curator'))
            ->get();
    }

    /**
     * Get all managers in the company (for director's view).
     */
    public function getAllManagers(): Collection
    {
        $currentUser = Auth::user();

        return User::where('created_by', $currentUser->creatorId())
            ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
            ->get();
    }
}
