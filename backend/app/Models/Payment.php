<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
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
     * Objet réglé par ce paiement : un avis de taxe (TaxNotice) ou un
     * loyer d'emplacement (StallRent). Toute « facture » payable expose
     * au minimum : status, paid_at, amount, commune_id, et son bénéficiaire.
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
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
