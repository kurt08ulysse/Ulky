<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Signalement citoyen (Phase 6).
 *
 * Isolation : un citoyen ne voit que SES signalements (Policy + contrôleur).
 *
 * Machine à états :
 *   new → acknowledged | rejected
 *   acknowledged → in_progress | rejected
 *   in_progress → resolved | rejected
 *   resolved → closed
 *   rejected → closed
 *
 * Chaque transition (et la création) est tracée automatiquement (append-only).
 */
class CitizenReport extends Model
{
    public const STATUSES = ['new', 'acknowledged', 'in_progress', 'resolved', 'rejected', 'closed'];

    public const CATEGORIES = ['voirie', 'eclairage', 'dechets', 'eau', 'securite', 'autre'];

    public const ALLOWED_TRANSITIONS = [
        'new' => ['acknowledged', 'rejected'],
        'acknowledged' => ['in_progress', 'rejected'],
        'in_progress' => ['resolved', 'rejected'],
        'resolved' => ['closed'],
        'rejected' => ['closed'],
        'closed' => [],
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'category',
        'title',
        'description',
        'status',
        'latitude',
        'longitude',
        'address',
        'assigned_to',
        'commune_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'commune_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public ?string $transitionNote = null;

    protected static function booted(): void
    {
        static::creating(function (self $report) {
            $report->reference ??= 'SIG-'.now()->year.'-'.Str::upper(Str::random(6));
            $report->status ??= 'new';
        });

        static::created(function (self $report) {
            $report->events()->create([
                'from_status' => null,
                'to_status' => $report->status,
                'actor_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        static::updating(function (self $report) {
            if ($report->isDirty('status')) {
                $report->events()->create([
                    'from_status' => $report->getOriginal('status'),
                    'to_status' => $report->status,
                    'actor_id' => auth()->id(),
                    'note' => $report->transitionNote,
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CitizenReportEvent::class)->orderBy('created_at');
    }
}
