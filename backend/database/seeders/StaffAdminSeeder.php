<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Compte du personnel pour le back-office Filament.
 *
 * Sécurité :
 * - Identifiants lus depuis l'environnement (BACKOFFICE_ADMIN_EMAIL / _PASSWORD).
 * - En production, si les variables ne sont pas définies, AUCUN compte n'est créé
 *   (jamais de mot de passe par défaut en prod).
 * - Hors production, un compte de développement connu est créé pour faciliter les tests.
 * - Idempotent (updateOrCreate sur l'email).
 */
class StaffAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('BACKOFFICE_ADMIN_EMAIL');
        $password = env('BACKOFFICE_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            if (app()->environment('production')) {
                $this->command?->warn(
                    'StaffAdminSeeder : BACKOFFICE_ADMIN_EMAIL / BACKOFFICE_ADMIN_PASSWORD non définis — aucun compte créé.'
                );

                return;
            }

            // Valeurs de développement UNIQUEMENT (hors production).
            $email ??= 'admin@ulky.local';
            $password ??= 'password';
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrateur ULKY',
                'password' => $password, // cast "hashed" → hachage automatique
            ]
        );

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }

        $this->command?->info("StaffAdminSeeder : compte back-office prêt ({$email}).");
    }
}
