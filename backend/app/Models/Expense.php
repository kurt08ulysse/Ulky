<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Dépense de la commune (comptabilité régisseur).
 */
class Expense extends Model
{
    public const CATEGORIES = ['fournitures', 'salaires', 'maintenance', 'services', 'investissement', 'autre'];

    protected $fillable = [
        'reference',
        'category',
        'label',
        'amount',
        'spent_at',
        'note',
        'recorded_by',
        'commune_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'spent_at' => 'date',
        'commune_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $expense) {
            $expense->reference ??= 'DEP-'.now()->year.'-'.Str::upper(Str::random(6));
        });
    }

    public function getAmountFormattedAttribute(): string
    {
        return number_format($this->amount / 100, 0, ',', ' ').' FCFA';
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
