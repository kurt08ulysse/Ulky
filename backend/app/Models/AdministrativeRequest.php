<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Demande administrative déposée par un citoyen.
 *
 * Isolation : un citoyen ne voit que SES demandes (filtré au contrôleur + Policy).
 *
 * Machine à états (transitions autorisées dans ALLOWED_TRANSITIONS) :
 *   submitted → in_review | rejected
 *   in_review → additional_info | approved | rejected
 *   additional_info → in_review
 *   approved → closed
 *   rejected → closed
 *
 * Chaque transition (et la création) est tracée automatiquement dans
 * administrative_request_events (append-only), pour l'API comme pour le back-office.
 */
class AdministrativeRequest extends Model
{
    public const STATUSES = ['submitted', 'in_review', 'additional_info', 'approved', 'rejected', 'closed'];

    public const ALLOWED_TRANSITIONS = [
        'submitted' => ['in_review', 'rejected'],
        'in_review' => ['additional_info', 'approved', 'rejected'],
        'additional_info' => ['in_review'],
        'approved' => ['closed'],
        'rejected' => ['closed'],
        'closed' => [],
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'type',
        'title',
        'description',
        'status',
        'metadata',
        'commune_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'commune_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Note rattachée à la prochaine transition de statut (non persistée comme
     * attribut) : lue par le hook updating pour renseigner l'événement append-only.
     */
    public ?string $transitionNote = null;

    protected static function booted(): void
    {
        // Référence publique unique générée à la création.
        static::creating(function (self $request) {
            $request->reference ??= 'DEM-'.now()->year.'-'.Str::upper(Str::random(6));
            $request->status ??= 'submitted';
        });

        // Trace l'état initial.
        static::created(function (self $request) {
            $request->events()->create([
                'from_status' => null,
                'to_status' => $request->status,
                'actor_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        // Trace chaque changement de statut (API ou back-office).
        static::updating(function (self $request) {
            if ($request->isDirty('status')) {
                $request->events()->create([
                    'from_status' => $request->getOriginal('status'),
                    'to_status' => $request->status,
                    'actor_id' => auth()->id(),
                    'note' => $request->transitionNote,
                    'created_at' => now(),
                ]);
            }
        });
    }

    /** Indique si une transition de statut est autorisée par la machine à états. */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AdministrativeRequestEvent::class)->orderBy('created_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
