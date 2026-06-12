<?php

namespace Tests\Feature\Api;

use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxNoticeTest extends TestCase
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
        $payload = [
            'sub' => $clerkId,
            'iat' => time(),
            'exp' => time() + 3600,
            'iss' => 'https://clerk.test',
        ];

        return JWT::encode($payload, self::TESTING_SECRET, 'HS256');
    }

    public function test_un_agent_municipal_peut_creer_un_avis_de_taxe_a_un_citoyen(): void
    {
        $agent = User::factory()->create(['clerk_id' => 'user_agent_123']);
        $agent->assignRole('municipal_agent');

        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_123']);
        $citizen->assignRole('citizen');

        $tax = Tax::create([
            'name' => 'Certificat de résidence (Adulte)',
            'base_amount' => 50000, // 500 FCFA
            'stamp_amount' => 100000, // 1000 FCFA
            'periodicity' => 'one_time',
        ]);

        $token = $this->makeJwt('user_agent_123');

        $response = $this->withToken($token)->postJson('/api/v1/tax-notices', [
            'tax_id' => $tax->id,
            'user_id' => $citizen->id,
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.base_amount', 50000)
            ->assertJsonPath('data.stamp_amount', 100000)
            ->assertJsonPath('data.total_amount', 150000)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('tax_notices', [
            'tax_id' => $tax->id,
            'user_id' => $citizen->id,
            'base_amount' => 50000,
            'stamp_amount' => 100000,
            'status' => 'pending',
        ]);
    }

    public function test_un_citoyen_peut_voir_ses_propres_avis_de_taxe(): void
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_123']);
        $citizen->assignRole('citizen');

        $tax = Tax::create([
            'name' => 'Attestation de cession',
            'base_amount' => 500000,
            'stamp_amount' => 100000,
            'periodicity' => 'one_time',
        ]);

        $notice = TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $citizen->id,
            'base_amount' => 500000,
            'stamp_amount' => 100000,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
        ]);

        $token = $this->makeJwt('user_citizen_123');

        // Test index (filtré)
        $responseIndex = $this->withToken($token)->getJson('/api/v1/tax-notices');
        $responseIndex->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $notice->id);

        // Test show
        $responseShow = $this->withToken($token)->getJson('/api/v1/tax-notices/' . $notice->id);
        $responseShow->assertStatus(200)
            ->assertJsonPath('data.id', $notice->id);
    }

    public function test_un_citoyen_ne_peut_pas_voir_les_avis_de_taxe_d_un_autre(): void
    {
        $citizen1 = User::factory()->create(['clerk_id' => 'user_citizen_1']);
        $citizen1->assignRole('citizen');

        $citizen2 = User::factory()->create(['clerk_id' => 'user_citizen_2']);
        $citizen2->assignRole('citizen');

        $tax = Tax::create([
            'name' => 'Attestation de cession',
            'base_amount' => 500000,
            'periodicity' => 'one_time',
        ]);

        $noticeOfCitizen2 = TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $citizen2->id,
            'base_amount' => 500000,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
        ]);

        $token = $this->makeJwt('user_citizen_1');

        // Test index (ne devrait pas renvoyer l'avis de citizen2)
        $responseIndex = $this->withToken($token)->getJson('/api/v1/tax-notices');
        $responseIndex->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Test show (doit renvoyer un 403)
        $responseShow = $this->withToken($token)->getJson('/api/v1/tax-notices/' . $noticeOfCitizen2->id);
        $responseShow->assertStatus(403);
    }

    public function test_un_agent_municipal_peut_annuler_un_avis_de_taxe(): void
    {
        $agent = User::factory()->create(['clerk_id' => 'user_agent_123']);
        $agent->assignRole('municipal_agent');

        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_123']);
        $citizen->assignRole('citizen');

        $tax = Tax::create([
            'name' => 'Attestation de cession',
            'base_amount' => 500000,
            'periodicity' => 'one_time',
        ]);

        $notice = TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $citizen->id,
            'base_amount' => 500000,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
        ]);

        $token = $this->makeJwt('user_agent_123');

        $response = $this->withToken($token)->putJson("/api/v1/tax-notices/{$notice->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('tax_notices', [
            'id' => $notice->id,
            'status' => 'cancelled',
        ]);
    }
}
