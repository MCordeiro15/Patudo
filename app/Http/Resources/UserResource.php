<?php

/**
 * Recurso API que transforma o modelo UserResource em JSON para resposta da API. Define quais os atributos e relações são expostos ao front-end.
 * API resource that transforms the UserResource model into JSON for API responses. Defines which attributes and relationships are exposed to the front-end.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transforma o modelo em array JSON para resposta da API, incluindo relações carregadas.
     * Transforms the model into a JSON array for API response, including loaded relationships.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarUrl = null;

        // Se o usuário é petsitter, usa a foto do perfil do petsitter
        if ($this->isPetsitter() && $this->relationLoaded('petsitter')) {
            $petsitter = $this->petsitter;
            if ($petsitter && $petsitter->profile_photo) {
                $avatarUrl = Storage::disk('public')->url($petsitter->profile_photo);
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $avatarUrl,
            'email_verified_at' => $this->email_verified_at,
            'is_petsitter' => $this->isPetsitter(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
