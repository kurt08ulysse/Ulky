<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'tax_notice_id',
        'transaction_id',
        'amount',
        'operator',
        'phone',
        'status',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    /**
     * Relation avec l'avis de taxe.
     */
    public function taxNotice(): BelongsTo
    {
        return $this->belongsTo(TaxNotice::class);
    }

    /**
     * Relation avec la quittance.
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * Scope pour les paiements réussis.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'successful');
    }

    /**
     * Scope pour les paiements en attente.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
