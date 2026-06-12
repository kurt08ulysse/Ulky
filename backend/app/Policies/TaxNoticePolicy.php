<?php

namespace App\Policies;

use App\Models\TaxNotice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaxNoticePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les rôles connectés peuvent lister (les citoyens seront filtrés au niveau du contrôleur)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TaxNotice $taxNotice): bool
    {
        if ($user->hasAnyRole(['commune_admin', 'municipal_agent', 'cashier', 'super_admin'])) {
            return true;
        }

        // Un citoyen peut uniquement voir son propre avis de taxe
        return $user->id === $taxNotice->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TaxNotice $taxNotice): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }

    /**
     * Determine whether the user can cancel the tax notice.
     */
    public function cancel(User $user, TaxNotice $taxNotice): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TaxNotice $taxNotice): bool
    {
        return $user->hasAnyRole(['commune_admin', 'super_admin']);
    }
}
