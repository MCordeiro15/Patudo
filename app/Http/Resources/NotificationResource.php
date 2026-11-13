<?php

/**
 * Recurso API que transforma o modelo Notification em JSON para resposta da API. Define quais os atributos e estado de leitura são expostos ao front-end.
 * API resource that transforms the Notification model into JSON for API responses. Defines which attributes and read status are exposed to the front-end.
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transforma o modelo em array JSON para resposta da API, incluindo estado de leitura.
     * Transforms the model into a JSON array for API response, including read status.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'message' => $this->message,
            'read' => $this->isRead(),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
