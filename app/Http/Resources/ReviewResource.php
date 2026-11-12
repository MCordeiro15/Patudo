<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'petsitter_id' => $this->petsitter_id,
            'star_rating' => $this->star_rating,
            'comment' => $this->comment,
            'visible' => $this->visible,
            'created_at' => $this->created_at,
        ];
    }
}
