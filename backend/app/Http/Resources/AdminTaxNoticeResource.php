<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource enrichie pour la vue admin d'un avis de taxe.
 * Inclut les données contribuable + quittance + paiement.
 */
class AdminTaxNoticeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payment = $this->whenLoaded('payment', fn () => $this->payment);
        $receipt = $this->whenLoaded('receipt', fn () => $this->receipt);

        return [
            'id'         => $this->id,
            'tax_id'     => $this->tax_id,
            'user_id'    => $this->user_id,

            // Taxe parente
            'tax' => new TaxResource($this->whenLoaded('tax')),

            // Contribuable (données admin — inclut le téléphone)
            'citizen' => $this->whenLoaded('user', fn () => [
                'id'             => $this->user->id,
                'name'           => $this->user->name,
                'phone'          => $this->user->phone,
                'email'          => $this->user->email,
                'taxpayer_type'  => $this->user->taxpayer_type,
            ]),

            // Montants en centimes
            'base_amount'  => $this->base_amount,
            'stamp_amount' => $this->stamp_amount,
            'total_amount' => $this->total_amount,

            // Montants formatés
            'base_amount_formatted'  => number_format($this->base_amount / 100, 0, ',', ' ').' FCFA',
            'stamp_amount_formatted' => number_format($this->stamp_amount / 100, 0, ',', ' ').' FCFA',
            'total_amount_formatted' => number_format($this->total_amount / 100, 0, ',', ' ').' FCFA',

            'status'  => $this->status,
            'due_date'=> $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),

            // Données paiement (si existant)
            'payment' => $this->whenLoaded('payment', fn () => $payment ? [
                'id'             => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'operator'       => $payment->operator,
                'phone'          => $payment->phone,
                'status'         => $payment->status,
                'created_at'     => $payment->created_at?->toIso8601String(),
            ] : null),

            // Quittance (si générée)
            'receipt' => $this->whenLoaded('receipt', fn () => $receipt ? [
                'id'               => $receipt->id,
                'number'           => $receipt->number ?? null,
                'verification_url' => $receipt->verification_url ?? null,
            ] : null),

            'commune_id' => $this->commune_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
