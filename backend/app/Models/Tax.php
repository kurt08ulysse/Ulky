<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tax extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_amount',
        'stamp_amount',
        'periodicity',
        'commune_id',
    ];

    protected $casts = [
        'base_amount' => 'integer',
        'stamp_amount' => 'integer',
        'commune_id' => 'integer',
    ];

    /**
     * Relation vers les avis de taxes (rattachements de contribuables).
     */
    public function notices(): HasMany
    {
        return $this->hasMany(TaxNotice::class);
    }

    /**
     * Calcule le montant total en centimes (Base + Timbre).
     */
    public function getTotalAmountAttribute(): int
    {
        return $this->base_amount + $this->stamp_amount;
    }
}
