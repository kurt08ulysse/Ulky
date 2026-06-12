<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Crée les 5 rôles métier ULKY.
 *
 * Séparation rôle / type contribuable (cf. PLAN.MD phase 1) :
 * - Le RÔLE porte les permissions d'accès (qui peut voir quoi, qui encaisse).
 * - Le TYPE DE CONTRIBUABLE (individual/business) est un attribut du User,
 *   pas un rôle d'autorisation.
 *
 * Rôles :
 * - citizen         : citoyen, accès à ses propres taxes et démarches
 * - municipal_agent : agent municipal, gestion des contribuables et taxes
 * - cashier         : régisseur — valide et encaisse les recettes
 *                     (séparation des tâches phase 3 : ne configure pas les taxes)
 * - commune_admin   : administrateur de commune, paramétrage complet
 * - super_admin     : multi-tenant, accès à toutes les communes (phase 2)
 *
 * Ce seeder est idempotent : firstOrCreate ne crée pas de doublon.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'citizen',
            'municipal_agent',
            'cashier',
            'commune_admin',
            'super_admin',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->command->info('Rôles ULKY créés : '.implode(', ', $roles));
    }
}
