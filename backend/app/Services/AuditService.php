<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Enregistre une entrée d'audit append-only.
     *
     * Toute opération sensible (paiement, webhook, génération de quittance,
     * annulation) DOIT produire au moins une ligne. Append-only par design :
     * jamais d'update, jamais de delete.
     */
    public function record(
        string $action,
        ?Model $subject = null,
        ?array $payload = null,
        ?Model $actor = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_type' => $actor ? $actor->getMorphClass() : null,
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'payload' => $payload,
            'ip' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
