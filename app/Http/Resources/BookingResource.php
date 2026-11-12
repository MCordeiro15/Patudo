<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'petsitter_id' => $this->petsitter_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'total_price' => $this->total_price,
            // whenLoaded: só aparece se o controller fez load/with, evita queries a mais
            'animals' => AnimalResource::collection($this->whenLoaded('animals')),
            'review' => new ReviewResource($this->whenLoaded('review')),
            'created_at' => $this->created_at,
        ];
    }
}
