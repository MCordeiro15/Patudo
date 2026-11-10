<?php

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
     * Transform the resource into an array.
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
