<?php

namespace Tests\Feature\Api;

use App\Models\Expense;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comptabilité — dépenses de la commune (régisseur) + solde net au dashboard.
 * Séparation des tâches : un agent municipal ne saisit pas de dépenses.
 */
class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private const TESTING_SECRET = 'super-secret-test-key-ulky-2026-abcdefgh';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.clerk.testing_secret' => self::TESTING_SECRET]);
        $this->seed(RolesSeeder::class);
    }

    private function makeJwt(string $clerkId): string
    {
        return JWT::encode([
            'sub' => $clerkId, 'iat' => time(), 'exp' => time() + 3600, 'iss' => 'https://clerk.test',
        ], self::TESTING_SECRET, 'HS256');
    }

    private function staff(string $clerkId, string $role): User
    {
        $u = User::factory()->create(['clerk_id' => $clerkId]);
        $u->assignRole($role);

        return $u;
    }

    public function test_le_regisseur_saisit_une_depense(): void
    {
        $this->staff('user_exp_cashier', 'cashier');

        $response = $this->withToken($this->makeJwt('user_exp_cashier'))
            ->postJson('/api/v1/admin/expenses', [
                'category' => 'maintenance',
                'label' => 'Réparation groupe électrogène',
                'amount' => 750000,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', 750000)
            ->assertJsonPath('data.category', 'maintenance');

        $this->assertDatabaseHas('expenses', ['label' => 'Réparation groupe électrogène', 'amount' => 750000]);
    }

    public function test_un_agent_municipal_ne_peut_pas_saisir_de_depense(): void
    {
        // Séparation des tâches : l'agent paramètre les taxes, pas les dépenses.
        $this->staff('user_exp_agent', 'municipal_agent');

        $this->withToken($this->makeJwt('user_exp_agent'))
            ->postJson('/api/v1/admin/expenses', ['category' => 'autre', 'label' => 'X', 'amount' => 1000])
            ->assertStatus(403);
    }

    public function test_un_citoyen_ne_peut_pas_acceder_aux_depenses(): void
    {
        $c = $this->staff('user_exp_citizen', 'citizen');

        $this->withToken($this->makeJwt('user_exp_citizen'))
            ->getJson('/api/v1/admin/expenses')
            ->assertStatus(403);
    }

    public function test_le_dashboard_affiche_recettes_depenses_et_net(): void
    {
        $citizen = User::factory()->create();
        $tax = Tax::create(['name' => 'Taxe', 'base_amount' => 500000, 'periodicity' => 'one_time']);
        $notice = TaxNotice::create([
            'tax_id' => $tax->id, 'user_id' => $citizen->id, 'base_amount' => 500000,
            'status' => 'paid', 'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $notice->payments()->create([
            'amount' => 500000, 'operator' => 'airtel_money', 'phone' => '+24166000000',
            'status' => 'successful', 'transaction_id' => 'tx_net',
        ]);

        $admin = $this->staff('user_exp_admin', 'super_admin');
        Expense::create([
            'category' => 'fournitures', 'label' => 'Papeterie', 'amount' => 200000,
            'spent_at' => now()->toDateString(), 'recorded_by' => $admin->id,
        ]);

        $response = $this->withToken($this->makeJwt('user_exp_admin'))
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('collected.total.amount', 500000)
            ->assertJsonPath('expenses.total.amount', 200000)
            ->assertJsonPath('net.amount', 300000);
    }
}
