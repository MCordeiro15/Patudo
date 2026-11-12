<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    // Qualquer utilizador autenticado pode listar; o controller só devolve as dele.
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $user->id === $booking->user_id
            || $user->id === $booking->petsitter_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    // Só o dono ou o petsitter da reserva. Que mudanças cada um pode fazer
    // fica no controller (TRANSITIONS). Não há delete: o histórico mantém-se.
    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id || $user->id === $booking->petsitter_id;
    }
}
