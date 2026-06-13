<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Cloisonnement multi-commune pour les ressources Filament dont la table
 * porte une colonne `commune_id`.
 *
 * - super_admin : accès global.
 * - tout autre rôle d'encadrement : strictement sa propre commune.
 *
 * Les ressources sans colonne `commune_id` (ex. emplacements via leur marché)
 * surchargent directement getEloquentQuery().
 */
trait ScopesToCommune
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->hasRole('super_admin')) {
            $query->where(static::$communeColumn ?? 'commune_id', $user->commune_id);
        }

        return $query;
    }
}
