<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElectedOfficialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'display_order' => $this->display_order,
            'commune_id' => $this->commune_id,
        ];
    }
}
