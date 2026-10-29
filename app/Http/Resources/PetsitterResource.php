<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetsitterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'biography' => $this->biography,
            'experience' => $this->experience,
            'service_location' => $this->service_location,
            'price_per_day' => $this->price_per_day,
            'accepted_sizes' => $this->accepted_sizes,
            'accepted_animal_types' => $this->accepted_animal_types,
            'approval_status' => $this->approval_status,
            'identification_documents' => $this->identification_documents,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
