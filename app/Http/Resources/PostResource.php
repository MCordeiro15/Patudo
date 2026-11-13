<?php

/**
 * Recurso API que transforma o modelo PostResource em JSON para resposta da API. Define quais os atributos e relações são expostos ao front-end.
 * API resource that transforms the PostResource model into JSON for API responses. Defines which attributes and relationships are exposed to the front-end.
 */

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'photos' => collect($this->photos)
                ->map(fn ($path) => str_starts_with($path, 'data:')
                    ? $path
                    : Storage::disk('public')->url($path))
                ->all(),
            'created_at' => $this->created_at,
        ];
    }
}
