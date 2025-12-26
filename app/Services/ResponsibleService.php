<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ResponsibleService
{
    /**
     * Check if user can assign responsible persons.
     * Only Directors and Managers can assign.
     */
    public function canAssignResponsible(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isDirector() || $user->isManager();
    }

    /**
     * Get users that can be assigned as responsible by the given user.
     * Directors: all managers and curators + themselves
     * Managers: their assigned curators + themselves
     * Curators: empty collection
     */
    public function getAssignableUsers(?User $currentUser = null): Collection
    {
        $currentUser = $currentUser ?? Auth::user();

        if (!$currentUser) {
            return collect();
        }

        if ($currentUser->isDirector()) {
            return User::where('created_by', $currentUser->creatorId())
                ->where(function ($q) use ($currentUser) {
                    $q->whereHas('roles', fn($r) => $r->whereIn('name', ['manager', 'curator']))
                      ->orWhere('id', $currentUser->id);
                })
                ->get();
        }

        if ($currentUser->isManager()) {
            $curators = $currentUser->assignedCurators;
            return $curators->push($currentUser)->unique('id');
        }

        return collect();
    }

    /**
     * Assign a responsible user to an entity.
     * Validates that the assignee is in the list of assignable users.
     */
    public function assignResponsible(Model $entity, int $userId, ?User $assignedBy = null): bool
    {
        $assignedBy = $assignedBy ?? Auth::user();

        if (!$this->canAssignResponsible($assignedBy)) {
            throw new \Exception(__('You do not have permission to assign responsible persons.'));
        }

        $assignableIds = $this->getAssignableUsers($assignedBy)->pluck('id');

        if (!$assignableIds->contains($userId)) {
            throw new \Exception(__('You cannot assign this user as responsible.'));
        }

        $oldResponsibleId = $entity->responsible_id;
        $entity->responsible_id = $userId;
        $entity->save();

        // Log the change in audit
        if ($oldResponsibleId !== $userId) {
            AuditService::logResponsibleChanged($entity, $oldResponsibleId, $userId);
        }

        return true;
    }

    /**
     * Check if a user can be assigned as responsible by the current user.
     */
    public function canAssignUser(int $userId, ?User $assignedBy = null): bool
    {
        $assignedBy = $assignedBy ?? Auth::user();

        if (!$this->canAssignResponsible($assignedBy)) {
            return false;
        }

        return $this->getAssignableUsers($assignedBy)->pluck('id')->contains($userId);
    }
}
