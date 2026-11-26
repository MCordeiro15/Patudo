<?php

/**
 * Avaliação para a página Avaliações do admin: estrelas, comentário, autor, petsitter, data e estado.
 * Review for the admin Reviews page: stars, comment, author, petsitter, date and status.
 */
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // o autor é o dono da reserva avaliada
        $author = $this->booking?->user;
        $petsitterUser = $this->petsitter?->user;

        return [
            'id' => $this->id,
            'star_rating' => $this->star_rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'author' => $author ? ['id' => $author->id, 'name' => $author->name] : null,
            'petsitter' => $petsitterUser ? ['id' => $petsitterUser->id, 'name' => $petsitterUser->name] : null,
            'booking_id' => $this->booking_id,
            'created_at' => $this->created_at,
        ];
    }
}
