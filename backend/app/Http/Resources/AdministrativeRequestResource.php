<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdministrativeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'fee_amount' => $this->fee_amount,
            'fee_amount_formatted' => number_format($this->fee_amount / 100, 0, ',', ' ').' FCFA',
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'commune_id' => $this->commune_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'receipt' => $this->whenLoaded('payment', function () {
                $receipt = $this->payment?->receipt;

                return $receipt ? [
                    'id' => $receipt->id,
                    'number' => $receipt->receipt_number,
                    'verification_url' => $receipt->verification_url,
                ] : null;
            }),

            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($event) => [
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'note' => $event->note,
                'actor_id' => $event->actor_id,
                'created_at' => $event->created_at?->toIso8601String(),
            ])),
        ];
    }
}
