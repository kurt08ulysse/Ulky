<?php

namespace App\Policies;

use App\Models\Tax;
use App\Models\User;

class TaxPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'cashier', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tax $tax): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'cashier', 'super_admin']);
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
    public function update(User $user, Tax $tax): bool
    {
        return $user->hasAnyRole(['commune_admin', 'municipal_agent', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tax $tax): bool
    {
        return $user->hasAnyRole(['commune_admin', 'super_admin']);
    }
}
