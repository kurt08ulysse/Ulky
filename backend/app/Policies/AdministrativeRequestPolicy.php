<?php

namespace App\Policies;

use App\Models\AdministrativeRequest;
use App\Models\User;

class AdministrativeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Le demandeur voit la sienne ; le staff municipal voit tout (cloisonné au contrôleur). */
    public function view(User $user, AdministrativeRequest $request): bool
    {
        if ($user->hasAnyRole(['commune_admin', 'municipal_agent', 'cashier', 'super_admin'])) {
            return true;
        }

        return $user->id === $request->user_id;
    }

    /** Tout citoyen connecté peut déposer une demande. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Seul le staff fait évoluer le statut d'une demande. */
    public function transition(User $user, AdministrativeRequest $request): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }
}
