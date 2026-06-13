<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketStallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stall_number' => $this->stall_number,
            'stall_type' => $this->stall_type,
            'rent_amount_cents' => $this->rent_amount_cents,
            'rent_amount_formatted' => $this->rent_amount_formatted,
            'status' => $this->status,
            'occupancy_start_date' => $this->occupancy_start_date?->toDateString(),
            'occupancy_end_date' => $this->occupancy_end_date?->toDateString(),
            'market' => $this->whenLoaded('market', fn () => [
                'id' => $this->market->id,
                'name' => $this->market->name,
                'address' => $this->market->address,
                'commune_id' => $this->market->commune_id,
            ]),
        ];
    }
}
