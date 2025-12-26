<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasResponsible
{
    /**
     * Boot the trait.
     */
    public static function bootHasResponsible(): void
    {
        static::creating(function ($model) {
            if (empty($model->responsible_id) && Auth::check()) {
                $model->responsible_id = Auth::id();
            }
        });
    }

    /**
     * Get the responsible user.
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * Scope to filter entities visible to a specific user.
     * Directors and Managers see everything, Curators see only their assigned entities.
     */
    public function scopeVisibleToUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // No user = no access
        }

        // Directors (company owners) and Managers see everything
        if ($user->isDirector() || $user->isManager()) {
            return $query;
        }

        // Curators see only entities they're responsible for
        if ($user->isCurator()) {
            return $query->where('responsible_id', $user->id);
        }

        return $query;
    }

    /**
     * Check if the given user can view this entity.
     */
    public function isVisibleTo(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isDirector() || $user->isManager()) {
            return true;
        }

        if ($user->isCurator()) {
            return $this->responsible_id === $user->id;
        }

        return false;
    }
}
