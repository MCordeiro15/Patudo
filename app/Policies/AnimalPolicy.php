<?php

/**
 * Política de autorização para animais. Define quem pode visualizar, criar, atualizar e eliminar animais (apenas o proprietário ou admin).
 * Authorization policy for animals. Defines who can view, create, update, and delete animals (only the owner or admin).
 */
namespace App\Policies;

use App\Models\Animal;
use App\Models\User;

class AnimalPolicy
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
    public function view(User $user, Animal $animal): bool
    {
        return $user->isAdmin() || $user->id === $animal->user_id;
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
    public function update(User $user, Animal $animal): bool
    {
        return $user->isAdmin() || $user->id === $animal->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Animal $animal): bool
    {
        return $user->isAdmin() || $user->id === $animal->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Animal $animal): bool
    {
        return $user->isAdmin() || $user->id === $animal->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Animal $animal): bool
    {
        return $user->isAdmin() || $user->id === $animal->user_id;
    }
}
