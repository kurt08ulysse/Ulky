<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Avis de loyer mensuel pour un emplacement de marché.
 *
 * Équivalent de TaxNotice mais pour les commerçants du marché.
 * Le paiement passe par SingPay via le socle polymorphe (Payment.payable → Receipt).
 */
class StallRent extends Model
{
    protected $fillable = [
        'market_stall_id',
        'occupant_id',
        'amount_cents',
        'period',
        'status',
        'due_date',
        'paid_at',
        'commune_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'commune_id' => 'integer',
    ];

    public function stall(): BelongsTo
    {
        return $this->belongsTo(MarketStall::class, 'market_stall_id');
    }

    public function occupant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'occupant_id');
    }

    /**
     * Paiements (polymorphes) rattachés à ce loyer.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Paiement le plus récent (tentative la plus récente).
     */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }

    /**
     * Montant total dû (alias homogène avec TaxNotice::total_amount).
     */
    public function getTotalAmountAttribute(): int
    {
        return $this->amount_cents;
    }

    public function getAmountFormattedAttribute(): string
    {
        return number_format($this->amount_cents / 100, 0, ',', ' ').' FCFA';
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeLate(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString());
    }
}
