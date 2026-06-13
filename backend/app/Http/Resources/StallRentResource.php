<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StallRentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'market_stall_id' => $this->market_stall_id,
            'occupant_id' => $this->occupant_id,
            'amount_cents' => $this->amount_cents,
            'amount_formatted' => $this->amount_formatted,
            'period' => $this->period,
            'status' => $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'commune_id' => $this->commune_id,

            'stall' => $this->whenLoaded('stall', fn () => [
                'id' => $this->stall->id,
                'stall_number' => $this->stall->stall_number,
                'stall_type' => $this->stall->stall_type,
                'market' => $this->stall->relationLoaded('market') && $this->stall->market ? [
                    'id' => $this->stall->market->id,
                    'name' => $this->stall->market->name,
                ] : null,
            ]),

            'occupant' => $this->whenLoaded('occupant', fn () => [
                'id' => $this->occupant->id,
                'name' => $this->occupant->name,
                'phone' => $this->occupant->phone,
            ]),

            'receipt' => $this->whenLoaded('payment', function () {
                $receipt = $this->payment?->receipt;

                return $receipt ? [
                    'id' => $receipt->id,
                    'number' => $receipt->receipt_number,
                    'verification_url' => $receipt->verification_url,
                ] : null;
            }),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
