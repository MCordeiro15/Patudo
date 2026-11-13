<?php

/**
 * Política de autorização para posts. Define quem pode visualizar, criar, atualizar e eliminar posts.
 * Authorization policy for posts. Defines who can view, create, update, and delete posts.
 */
namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Permite visualizar se o utilizador pode ver este registo (proprietário ou admin).
     * Determines if the user can view this record (owner or admin).
     */
    public function view(User $user, Post $post): bool
    {
        return true;
    }

    /**
     * Permite verificar se o utilizador pode criar novos registos.
     * Determines if the user can create new records.
     */
    public function create(User $user): bool
    {
        return $user->petsitter?->approval_status === 'approved';
    }

    /**
     * Permite verificar se o utilizador pode atualizar este registo (apenas proprietário ou admin).
     * Determines if the user can update this record (only owner or admin).
     */
    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->petsitter_id;
    }

    /**
     * Permite verificar se o utilizador pode eliminar este registo.
     * Determines if the user can delete this record.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->petsitter_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return false;
    }
}
