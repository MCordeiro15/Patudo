<?php

/**
 * Recurso API que transforma o modelo BookingResource em JSON para resposta da API. Define quais os atributos e relações são expostos ao front-end.
 * API resource that transforms the BookingResource model into JSON for API responses. Defines which attributes and relationships are exposed to the front-end.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookingResource extends JsonResource
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
            'petsitter_id' => $this->petsitter_id,
            'service_id' => $this->service_id,
            // null em reservas antigas, criadas antes de existirem serviços
            'service' => $this->whenLoaded('service', fn () => $this->service ? [
                'id' => $this->service->id,
                'name' => $this->service->type,
                'price' => (float) $this->service->price,
            ] : null),
            // nome e localidade de quem está na reserva, para as listas de Pedidos/Reservas
            'owner' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'location' => $this->user->location,
                // foto do user, ou avatar com as iniciais (como no UserResource)
                'avatar_url' => $this->user->avatar
                    ?: $this->initialsAvatar($this->user->name),
            ] : null),
            'petsitter' => $this->whenLoaded('petsitter', fn () => $this->petsitter ? [
                'id' => $this->petsitter->user_id,
                'name' => $this->petsitter->user?->name,
                'location' => $this->petsitter->service_location ?? $this->petsitter->city,
                // foto do perfil de petsitter, ou avatar com as iniciais (como no PetsitterResource)
                'avatar_url' => $this->petsitter->profile_photo
                    ? (str_starts_with($this->petsitter->profile_photo, 'data:')
                        ? $this->petsitter->profile_photo
                        : Storage::disk('public')->url($this->petsitter->profile_photo))
                    : $this->initialsAvatar($this->petsitter->user?->name),
            ] : null),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'total_price' => $this->total_price,
            // whenLoaded: só aparece se o controller fez load/with, evita queries a mais
            'animals' => AnimalResource::collection($this->whenLoaded('animals')),
            'review' => new ReviewResource($this->whenLoaded('review')),
            'created_at' => $this->created_at,
            'message' => $this->message,
            'zone' => $this->zone,
            'entrance_hour' => $this->entrance_hour,
            'exit_hour' => $this->exit_hour,
        ];
    }

    /**
     * Avatar gerado com as iniciais do nome (o mesmo dos outros resources).
     * Avatar generated from the name's initials (same as the other resources).
     */
    private function initialsAvatar(?string $name): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($name ?? 'U') . '&background=random';
    }
}
