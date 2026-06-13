<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Élu de la commune (maire, adjoints, conseillers).
 * Contenu présentationnel géré par l'administration.
 */
class ElectedOfficial extends Model
{
    protected $fillable = [
        'name',
        'title',
        'photo_path',
        'description',
        'display_order',
        'is_published',
        'commune_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'display_order' => 'integer',
        'commune_id' => 'integer',
    ];

    /** URL publique de la photo (disque public + storage:link). */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
