<?php


/**
 * Política de autorização para reservas. Define quem pode visualizar, criar, atualizar e alterar o estado das reservas.
 * Authorization policy for bookings. Defines who can view, create, update, and change the status of bookings.
 */
namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    // Qualquer utilizador autenticado pode listar; o controller só devolve as dele.
    /**
     * Permite visualizar se o utilizador pode listar todos os registos.
     * Determines if the user can list all records.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Permite visualizar se o utilizador pode ver este registo (proprietário ou admin).
     * Determines if the user can view this record (owner or admin).
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $user->id === $booking->user_id
            || $user->id === $booking->petsitter_id;
    }

    /**
     * Permite verificar se o utilizador pode criar novos registos.
     * Determines if the user can create new records.
     */
    public function create(User $user): bool
    {
        return true;
    }

    // Só o dono ou o petsitter da reserva. Que mudanças cada um pode fazer
    // fica no controller (TRANSITIONS). Não há delete: o histórico mantém-se.
    /**
     * Permite verificar se o utilizador pode alterar o estado (donou ou petsitter da reserva).
     * Determines if the user can change the status (booking owner or petsitter).
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id || $user->id === $booking->petsitter_id;
    }
}
