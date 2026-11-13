<?php

/**
 * Recurso API que transforma o modelo PetsitterAvailabilityResource em JSON para resposta da API. Define quais os atributos e relações são expostos ao front-end.
 * API resource that transforms the PetsitterAvailabilityResource model into JSON for API responses. Defines which attributes and relationships are exposed to the front-end.
 */
namespace App\Http\Resources;

use App\Models\PetsitterAvailability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetsitterAvailability
 */
class PetsitterAvailabilityResource extends JsonResource
{
    /**
     * Transforma o modelo em array JSON para resposta da API, incluindo relações carregadas.
     * Transforms the model into a JSON array for API response, including loaded relationships.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'petsitter_id' => $this->petsitter_id,
            'date' => $this->date?->format('Y-m-d') ?? (string) $this->date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
