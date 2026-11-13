<?php

/**
 * Recurso API que transforma o modelo AnimalResource em JSON para resposta da API. Define quais os atributos e relações são expostos ao front-end.
 * API resource that transforms the AnimalResource model into JSON for API responses. Defines which attributes and relationships are exposed to the front-end.
 */

namespace App\Http\Resources;

use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Animal
 */
class AnimalResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'species' => $this->species,
            'breed' => $this->breed,
            'size' => $this->size,
            'special_needs' => $this->special_needs,
            'aggression' => $this->aggression,
            'medication' => $this->medication,
            'sex' => $this->sex,
            'photo' => $this->photo,
            'weight' => $this->weight,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
