<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clerk_id' => $this->clerk_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'taxpayer_type' => $this->taxpayer_type,
            'roles' => $this->getRoleNames(),
            'commune_id' => $this->commune_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
