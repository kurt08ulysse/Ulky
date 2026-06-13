<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TaxNotice extends Model
{
    protected $fillable = [
        'tax_id',
        'user_id',
        'base_amount',
        'stamp_amount',
        'status',
        'due_date',
        'paid_at',
        'commune_id',
    ];

    protected $casts = [
        'tax_id' => 'integer',
        'user_id' => 'integer',
        'base_amount' => 'integer',
        'stamp_amount' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'commune_id' => 'integer',
    ];

    /**
     * Relation vers la Taxe parente.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Relation vers le Contribuable (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tous les paiements (polymorphes) rattachés à cet avis.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Paiement le plus récent.
     *
     * Un avis peut comporter plusieurs tentatives (échec puis succès) ;
     * on expose la plus récente, utilisée par le tableau de bord admin
     * et l'export CSV (avec sa quittance éventuelle via payment.receipt).
     */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }

    /**
     * Calcule le montant total dû en centimes (Base + Timbre).
     */
    public function getTotalAmountAttribute(): int
    {
        return $this->base_amount + $this->stamp_amount;
    }

    /**
     * Scope pour filtrer les avis en attente.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour filtrer les avis payés.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope pour filtrer les avis annulés.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }
}
