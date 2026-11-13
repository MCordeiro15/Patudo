<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    // Só o dono da reserva, e só se a reserva estiver concluída.
    // Recebe a reserva (não a review), porque a review ainda não existe.
    public function create(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id && $booking->status === 'completed';
    }

    // Reviews escondidas só o autor e o admin as veem.
    public function view(User $user, Review $review): bool
    {
        return $review->visible
            || $user->isAdmin()
            || $review->booking->user_id === $user->id;
    }

    public function update(User $user, Review $review): bool
    {
        return $review->booking->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->booking->user_id === $user->id;
    }

    // Esconder uma review: só o admin.
    public function hide(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
