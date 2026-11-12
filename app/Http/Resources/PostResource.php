<?php

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
     * Transform the resource into an array.
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
                ->map(fn ($path) => Storage::disk('public')->url($path))
                ->all(),
            'created_at' => $this->created_at,
        ];
    }
}
