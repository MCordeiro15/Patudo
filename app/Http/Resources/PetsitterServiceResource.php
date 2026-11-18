<?php

/**
 * Recurso API que transforma o modelo PetsitterService em JSON para resposta da API.
 * API resource that transforms the PetsitterService model into JSON for API responses.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetsitterServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'petsitter_id' => $this->petsitter_id,
            'type' => $this->type,
            'price' => (float) $this->price,
        ];
    }
}
