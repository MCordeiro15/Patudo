<?php

namespace App\Policies;

use App\Models\Petsitter;
use App\Models\User;

class PetsitterPolicy
{
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
    public function view(User $user, Petsitter $petsitter): bool
    {
        return true;
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
    public function update(User $user, Petsitter $petsitter): bool
    {
        return $user->isAdmin() || $user->id === $petsitter->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Petsitter $petsitter): bool
    {
        return $user->isAdmin() || $user->id === $petsitter->user_id;
    }

    /**
     * Determine whether the user can approve or reject the petsitter.
     */
    public function approve(User $user, Petsitter $petsitter): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Petsitter $petsitter): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Petsitter $petsitter): bool
    {
        return $user->isAdmin();
    }
}
