<?php

namespace App\Policies;

use App\Models\MassageClient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MassageClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MassageClient $client): bool
    {
        return $user->id === $client->created_by 
            || $user->id === $client->responsible_id
            || in_array($user->type, ['operator', 'company']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MassageClient $client): bool
    {
        return $user->id === $client->created_by 
            || $user->id === $client->responsible_id
            || in_array($user->type, ['operator', 'company']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MassageClient $client): bool
    {
        return $user->id === $client->created_by 
            || $user->id === $client->responsible_id
            || in_array($user->type, ['operator', 'company']);
    }
}
