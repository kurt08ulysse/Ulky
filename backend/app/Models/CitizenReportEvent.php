<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transition d'état d'un signalement — append-only (trace de traitement).
 */
class CitizenReportEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'citizen_report_id',
        'from_status',
        'to_status',
        'actor_id',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(CitizenReport::class, 'citizen_report_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
