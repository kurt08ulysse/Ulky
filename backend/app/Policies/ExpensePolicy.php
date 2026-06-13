<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

/**
 * Séparation des tâches : seul le personnel financier (régisseur/admin) gère les
 * dépenses. Un agent municipal (qui paramètre les taxes) ne saisit pas de dépenses.
 */
class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['cashier', 'commune_admin', 'super_admin']);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasAnyRole(['cashier', 'commune_admin', 'super_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['cashier', 'commune_admin', 'super_admin']);
    }
}
