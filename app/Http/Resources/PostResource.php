<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'petsitter_id' => $this->petsitter_id,
            'description' => $this->description,
            'photos' => collect($this->photos)
                ->map(fn ($path) => Storage::disk('public')->url($path))
                ->all(),
            'created_at' => $this->created_at,
        ];
    }
}
