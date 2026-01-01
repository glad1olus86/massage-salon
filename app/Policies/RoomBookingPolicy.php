<?php

namespace App\Policies;

use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomBookingPolicy
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
    public function view(User $user, RoomBooking $booking): bool
    {
        return $user->id === $booking->user_id 
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
    public function update(User $user, RoomBooking $booking): bool
    {
        return $user->id === $booking->user_id 
            || in_array($user->type, ['operator', 'company']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RoomBooking $booking): bool
    {
        return $user->id === $booking->user_id 
            || in_array($user->type, ['operator', 'company']);
    }
}
