<?php

/**
 * Recurso API que transforma o modelo SupportTicketsResource em JSON para resposta da API. Define quais os atributos e relações são expostos ao front-end.
 * API resource that transforms the SupportTicketsResource model into JSON for API responses. Defines which attributes and relationships are exposed to the front-end.
 */
namespace App\Http\Resources;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SupportTicket
 */
class SupportTicketsResource extends JsonResource
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
            'claimed_by' => $this->claimed_by,
            'reported_user_id' => $this->reported_user_id,
            'subject' => $this->subject,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'claimant' => new UserResource($this->whenLoaded('claimant')),
            'reportedUser' => new UserResource($this->whenLoaded('reportedUser')),
        ];
    }
}
