<?php

namespace App\Policies;

use App\Models\CitizenReport;
use App\Models\User;

class CitizenReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Le signaleur voit le sien ; le staff voit tout (cloisonné au contrôleur). */
    public function view(User $user, CitizenReport $report): bool
    {
        if ($user->hasAnyRole(['commune_admin', 'municipal_agent', 'cashier', 'super_admin'])) {
            return true;
        }

        return $user->id === $report->user_id;
    }

    /** Tout citoyen connecté peut signaler. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Seul le staff fait évoluer / assigne un signalement. */
    public function transition(User $user, CitizenReport $report): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }
}
