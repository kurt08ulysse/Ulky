<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Emplacement (stalle) dans un marché municipal.
 * Un commerçant occupe un emplacement et paie un loyer mensuel.
 */
class MarketStall extends Model
{
    protected $fillable = [
        'market_id',
        'stall_number',
        'stall_type',
        'rent_amount_cents',
        'status',
        'occupant_id',
        'occupancy_start_date',
        'occupancy_end_date',
        'notes',
    ];

    protected $casts = [
        'rent_amount_cents' => 'integer',
        'occupancy_start_date' => 'date',
        'occupancy_end_date' => 'date',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function occupant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'occupant_id');
    }

    public function rents(): HasMany
    {
        return $this->hasMany(StallRent::class);
    }

    public function getRentAmountFormattedAttribute(): string
    {
        return number_format($this->rent_amount_cents / 100, 0, ',', ' ').' FCFA';
    }

    public function scopeOccupied(Builder $query): Builder
    {
        return $query->where('status', 'occupied');
    }

    public function scopeVacant(Builder $query): Builder
    {
        return $query->where('status', 'vacant');
    }
}
