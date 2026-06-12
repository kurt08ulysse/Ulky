<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Database\Seeders\TaxesTableSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    private const TESTING_SECRET = 'super-secret-test-key-ulky-2026-abcdefgh';

    protected function setUp(): void
    {
        parent::setUp();

        // Injecter le secret symétrique de test pour le guard Clerk
        config(['services.clerk.testing_secret' => self::TESTING_SECRET]);

        // Initialiser les rôles
        $this->seed(RolesSeeder::class);
    }

    /** Créer un JWT HS256 de test. */
    private function makeJwt(string $clerkId): string
    {
        $payload = [
            'sub' => $clerkId,
            'iat' => time(),
            'exp' => time() + 3600,
            'iss' => 'https://clerk.test',
        ];

        return JWT::encode($payload, self::TESTING_SECRET, 'HS256');
    }

    public function test_un_agent_municipal_peut_creer_une_taxe(): void
    {
        $agent = User::factory()->create([
            'clerk_id' => 'user_agent_123',
            'name' => 'Agent Municipal',
        ]);
        $agent->assignRole('municipal_agent');

        $token = $this->makeJwt('user_agent_123');

        $response = $this->withToken($token)->postJson('/api/v1/taxes', [
            'name' => 'Attestation de cession',
            'description' => 'Cession de terrain',
            'base_amount' => 500000, // 5000 FCFA
            'stamp_amount' => 100000, // 1000 FCFA
            'periodicity' => 'one_time',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Attestation de cession')
            ->assertJsonPath('data.total_amount', 600000)
            ->assertJsonPath('data.total_amount_formatted', '6 000 FCFA');

        $this->assertDatabaseHas('taxes', [
            'name' => 'Attestation de cession',
            'base_amount' => 500000,
            'stamp_amount' => 100000,
        ]);
    }

    public function test_un_citoyen_ne_peut_pas_creer_une_taxe(): void
    {
        $citizen = User::factory()->create([
            'clerk_id' => 'user_citizen_123',
        ]);
        $citizen->assignRole('citizen');

        $token = $this->makeJwt('user_citizen_123');

        $response = $this->withToken($token)->postJson('/api/v1/taxes', [
            'name' => 'Taxe frauduleuse',
            'base_amount' => 1000,
            'periodicity' => 'one_time',
        ]);

        $response->assertStatus(403);
    }

    public function test_un_agent_municipal_peut_lister_les_taxes(): void
    {
        $agent = User::factory()->create([
            'clerk_id' => 'user_agent_123',
        ]);
        $agent->assignRole('municipal_agent');

        // Pré-remplir la table
        $this->seed(TaxesTableSeeder::class);

        $token = $this->makeJwt('user_agent_123');

        $response = $this->withToken($token)->getJson('/api/v1/taxes');

        $response->assertStatus(200)
            ->assertJsonCount(39, 'data'); // 39 actes réels
    }

    public function test_un_citoyen_ne_peut_pas_lister_les_taxes(): void
    {
        $citizen = User::factory()->create([
            'clerk_id' => 'user_citizen_123',
        ]);
        $citizen->assignRole('citizen');

        $token = $this->makeJwt('user_citizen_123');

        $response = $this->withToken($token)->getJson('/api/v1/taxes');

        $response->assertStatus(403);
    }
}
