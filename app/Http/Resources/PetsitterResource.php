<?php

/**
 * Recurso API que transforma o modelo PetsitterResource em JSON para resposta da API. Define quais os atributos e relações são expostos ao front-end.
 * API resource that transforms the PetsitterResource model into JSON for API responses. Defines which attributes and relationships are exposed to the front-end.
 */

namespace App\Http\Resources;

use App\Models\Petsitter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Petsitter
 */
class PetsitterResource extends JsonResource
{
    /**
     * Transforma o modelo em array JSON para resposta da API, incluindo relações carregadas.
     * Transforms the model into a JSON array for API response, including loaded relationships.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $viewer = $request->user();
        $canSeePrivate = $viewer && ($viewer->isAdmin() || $viewer->id === $this->user_id);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'avatar_url' => $this->profile_photo ? Storage::disk('public')->url($this->profile_photo) : null,
            'profile_photo' => $this->profile_photo,
            'short_bio' => $this->short_bio,
            'experience' => $this->experience,
            'service_location' => $this->service_location,
            'city' => $this->city,
            'age' => $this->age,
            'price_per_day' => $this->price_per_day,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews()->where('visible', true)->count(),
            'accepted_sizes' => $this->accepted_sizes,
            'accepted_animal_types' => $this->accepted_animal_types,
            'sex' => $this->sex,
            'approval_status' => $this->approval_status,
            'identification_documents' => $this->when($canSeePrivate, $this->identification_documents),
            'availability_days' => $this->availability_days,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', fn () => $canSeePrivate
                ? new UserResource($this->user)
                : ['id' => $this->user->id, 'name' => $this->user->name]),
        ];
    }
}
