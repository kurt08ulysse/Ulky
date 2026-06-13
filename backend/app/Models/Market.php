<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Marché municipal appartenant à la mairie.
 */
class Market extends Model
{
    protected $fillable = ['name', 'address', 'commune_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'commune_id' => 'integer',
    ];

    public function stalls(): HasMany
    {
        return $this->hasMany(MarketStall::class);
    }
}
