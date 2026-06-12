<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxNoticeResource extends JsonResource
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
            'tax_id' => $this->tax_id,
            'user_id' => $this->user_id,

            // Relations chargées à la demande
            'tax' => new TaxResource($this->whenLoaded('tax')),
            'user' => new UserResource($this->whenLoaded('user')),

            // Montants bruts en centimes
            'base_amount' => $this->base_amount,
            'stamp_amount' => $this->stamp_amount,
            'total_amount' => $this->total_amount,

            // Montants formatés pour l'affichage (FCFA)
            'base_amount_formatted' => number_format($this->base_amount / 100, 0, ',', ' ').' FCFA',
            'stamp_amount_formatted' => number_format($this->stamp_amount / 100, 0, ',', ' ').' FCFA',
            'total_amount_formatted' => number_format($this->total_amount / 100, 0, ',', ' ').' FCFA',

            'status' => $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),

            'commune_id' => $this->commune_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
