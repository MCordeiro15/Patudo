<?php

/**
 * Controlador API para gestão de notificações de utilizadores. Fornece endpoints para listar, ver, criar, marcar como lida e eliminar notificações.
 * API controller for user notification management. Provides endpoints to list, view, create, mark as read, and delete notifications.
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    /**
     * Lista as notificações do utilizador autenticado, da mais recente para a mais antiga.
     * Lists the authenticated user's notifications, from newest to oldest.
     */
    public function index()
    {
        $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();

        return NotificationResource::collection(
            auth()->user()->notifications()->with('booking:id,start_date')->latest()->paginate(15)
        )->additional(['unread_count' => $unreadCount]);
    }

    /**
     * Retorna os detalhes de uma notificação específica. Verifica permissão antes de exibir.
     * Returns details of a specific notification. Checks permission before displaying.
     */
    public function show(Notification $notification)
    {
        Gate::authorize('view', $notification);

        return new NotificationResource($notification);
    }

    /**
     * Cria uma nova notificação para um utilizador. Apenas administradores podem criar notificações.
     * Creates a new notification for a user. Only administrators can create notifications.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Notification::class);

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'type' => 'required|string|max:255',
            'message' => 'required|string',
            'booking_id' => 'nullable|integer|exists:bookings,id',
        ]);

        $notification = Notification::create($data);

        return (new NotificationResource($notification))->response()->setStatusCode(201);
    }

    /**
     * Marca uma notificação como lida. Apenas o proprietário pode marcar a sua notificação.
     * Marks a notification as read. Only the owner can mark their notification.
     */
    public function markAsRead(Notification $notification)
    {
        Gate::authorize('markAsRead', $notification);

        $notification->markAsRead();

        return new NotificationResource($notification);
    }

    /**
     * Marca uma notificação como não lida. Apenas o proprietário pode alterar a sua notificação.
     * Marks a notification as unread. Only the owner can change their notification.
     */
    public function markAsUnread(Notification $notification)
    {
        Gate::authorize('markAsUnread', $notification);

        $notification->markAsUnread();

        return new NotificationResource($notification);
    }

    /**
     * Elimina uma notificação. Apenas o proprietário ou um admin pode eliminar.
     * Deletes a notification. Only the owner or an admin can delete.
     */
    public function destroy(Notification $notification)
    {
        Gate::authorize('delete', $notification);

        $notification->delete();

        return response()->noContent();
    }
}
