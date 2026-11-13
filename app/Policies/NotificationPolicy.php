<?php

/**
 * Política de autorização para notificações. Define quem pode visualizar, criar, marcar como lida e eliminar notificações.
 * Authorization policy for notifications. Defines who can view, create, mark as read, and delete notifications.
 */
namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    /**
     * Determine whether the user can view any models.
     * Qualquer utilizador autenticado pode listar as suas notificações.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Apenas o proprietário ou um admin pode ver uma notificação específica.
     */
    public function view(User $user, Notification $notification): bool
    {
        return $user->isAdmin() || $notification->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     * Apenas o admin pode criar notificações via API.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can mark the notification as read.
     * Apenas o proprietário da notificação pode marcá-la como lida.
     */
    public function markAsRead(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     * Apenas o proprietário ou um admin pode eliminar uma notificação.
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $user->isAdmin() || $notification->user_id === $user->id;
    }
}
