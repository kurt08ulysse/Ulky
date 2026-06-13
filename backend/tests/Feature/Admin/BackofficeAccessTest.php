<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrôle d'accès au back-office Filament (/admin).
 *
 * - accès anonyme → redirection vers la page de login ;
 * - un citoyen (auth Clerk, sans mot de passe) → 403 (canAccessPanel) ;
 * - un super_admin disposant d'un mot de passe → accès autorisé.
 */
class BackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_acces_anonyme_redirige_vers_le_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_un_citoyen_est_refuse(): void
    {
        $citizen = User::factory()->create(['password' => null]);
        $citizen->assignRole('citizen');

        $this->actingAs($citizen)->get('/admin')->assertForbidden();
    }

    public function test_un_agent_sans_role_d_encadrement_est_refuse(): void
    {
        // Un agent municipal a un mot de passe mais pas le rôle d'encadrement requis.
        $agent = User::factory()->create(['password' => 'secret-agent-xyz']);
        $agent->assignRole('municipal_agent');

        $this->actingAs($agent)->get('/admin')->assertForbidden();
    }

    public function test_un_super_admin_avec_mot_de_passe_accede(): void
    {
        $admin = User::factory()->create(['password' => 'secret-admin-xyz']);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get('/admin')->assertSuccessful();
    }
}
