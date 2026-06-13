<?php

namespace App\Policies;

use App\Models\StallRent;
use App\Models\User;

class StallRentPolicy
{
    /** Tous les connectés peuvent lister (filtré au contrôleur). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Un commerçant ne voit que SES loyers ; le staff municipal voit tout
     * (cloisonnement commune appliqué au contrôleur).
     */
    public function view(User $user, StallRent $stallRent): bool
    {
        if ($user->hasAnyRole(['commune_admin', 'municipal_agent', 'cashier', 'super_admin'])) {
            return true;
        }

        return $user->id === $stallRent->occupant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }

    public function update(User $user, StallRent $stallRent): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }

    public function cancel(User $user, StallRent $stallRent): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }
}
