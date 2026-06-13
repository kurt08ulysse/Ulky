<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'category' => $this->category,
            'label' => $this->label,
            'amount' => $this->amount,
            'amount_formatted' => $this->amount_formatted,
            'spent_at' => $this->spent_at?->toDateString(),
            'note' => $this->note,
            'recorded_by' => $this->recorded_by,
            'commune_id' => $this->commune_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
