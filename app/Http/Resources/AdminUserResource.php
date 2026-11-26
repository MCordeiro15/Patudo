<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'about' => $this->about,
            'is_admin' => $this->is_admin,
            'is_petsitter' => $this->is_petsitter,
            'type' => $this->is_admin ? 'admin' : ($this->is_petsitter ? 'petsitter' : 'owner'),
            'account_status' => $this->account_status,
            'suspended_until' => $this->suspended_until,
            'suspension_reason' => $this->suspension_reason,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
