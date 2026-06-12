<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avis de loyer mensuel pour un emplacement de marché.
 *
 * Équivalent de TaxNotice mais pour les commerçants du marché.
 * Le paiement passe par SingPay (même chemin : Payment → Receipt).
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
        'payment_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function stall(): BelongsTo
    {
        return $this->belongsTo(MarketStall::class, 'market_stall_id');
    }

    public function occupant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'occupant_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
