<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            
            // Montants bruts en centimes
            'base_amount' => $this->base_amount,
            'stamp_amount' => $this->stamp_amount,
            'total_amount' => $this->total_amount,
            
            // Montants formatés pour l'affichage (FCFA)
            'base_amount_formatted' => number_format($this->base_amount / 100, 0, ',', ' ') . ' FCFA',
            'stamp_amount_formatted' => number_format($this->stamp_amount / 100, 0, ',', ' ') . ' FCFA',
            'total_amount_formatted' => number_format($this->total_amount / 100, 0, ',', ' ') . ' FCFA',
            
            'periodicity' => $this->periodicity,
            'commune_id' => $this->commune_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
