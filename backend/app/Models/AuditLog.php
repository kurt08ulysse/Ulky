<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal d'audit append-only.
 *
 * Ne JAMAIS appeler ->update() ou ->save() sur une instance existante.
 * Toute lecture passe par les scopes ; toute écriture passe par AuditService.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'payload',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
