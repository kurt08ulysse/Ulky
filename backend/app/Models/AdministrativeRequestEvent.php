<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transition d'état d'une demande administrative — append-only.
 * Jamais d'update ni de delete : c'est la trace d'audit du traitement.
 */
class AdministrativeRequestEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'administrative_request_id',
        'from_status',
        'to_status',
        'actor_id',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRequest::class, 'administrative_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
