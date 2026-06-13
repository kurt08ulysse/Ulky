<?php

namespace Tests\Feature\Api;

use App\Models\AdministrativeRequest;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Demandes administratives — isolation par citoyen et machine à états.
 */
class AdministrativeRequestTest extends TestCase
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
            'sub' => $clerkId,
            'iat' => time(),
            'exp' => time() + 3600,
            'iss' => 'https://clerk.test',
        ], self::TESTING_SECRET, 'HS256');
    }

    private function citizen(string $clerkId): User
    {
        $user = User::factory()->create(['clerk_id' => $clerkId]);
        $user->assignRole('citizen');

        return $user;
    }

    private function requestFor(User $user): AdministrativeRequest
    {
        return AdministrativeRequest::create([
            'user_id' => $user->id,
            'type' => 'acte_naissance',
            'title' => 'Copie d\'acte de naissance',
            'status' => 'submitted',
            'commune_id' => $user->commune_id,
        ]);
    }

    public function test_un_citoyen_depose_une_demande_et_un_evenement_est_trace(): void
    {
        $citizen = $this->citizen('user_dem_create');

        $response = $this->withToken($this->makeJwt('user_dem_create'))
            ->postJson('/api/v1/requests', [
                'type' => 'certificat_residence',
                'title' => 'Certificat de résidence',
                'description' => 'Pour dossier scolaire',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.type', 'certificat_residence');

        $this->assertDatabaseHas('administrative_requests', [
            'user_id' => $citizen->id,
            'status' => 'submitted',
        ]);
        // L'état initial est tracé (null → submitted).
        $this->assertDatabaseHas('administrative_request_events', [
            'to_status' => 'submitted',
            'from_status' => null,
        ]);
    }

    public function test_un_citoyen_ne_voit_que_ses_demandes(): void
    {
        $a = $this->citizen('user_dem_a');
        $own = $this->requestFor($a);

        $b = $this->citizen('user_dem_b');
        $this->requestFor($b);

        $response = $this->withToken($this->makeJwt('user_dem_a'))
            ->getJson('/api/v1/requests');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_un_citoyen_ne_peut_pas_voir_la_demande_d_un_autre(): void
    {
        $a = $this->citizen('user_dem_a2');
        $b = $this->citizen('user_dem_b2');
        $requestOfB = $this->requestFor($b);

        $this->withToken($this->makeJwt('user_dem_a2'))
            ->getJson("/api/v1/requests/{$requestOfB->id}")
            ->assertStatus(403);
    }

    public function test_le_staff_fait_evoluer_le_statut_avec_trace(): void
    {
        $citizen = $this->citizen('user_dem_c');
        $req = $this->requestFor($citizen);

        $agent = User::factory()->create(['clerk_id' => 'user_dem_agent']);
        $agent->assignRole('municipal_agent');

        $response = $this->withToken($this->makeJwt('user_dem_agent'))
            ->postJson("/api/v1/requests/{$req->id}/transition", [
                'to_status' => 'in_review',
                'note' => 'Dossier en cours d\'instruction',
            ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'in_review');

        $this->assertDatabaseHas('administrative_request_events', [
            'administrative_request_id' => $req->id,
            'from_status' => 'submitted',
            'to_status' => 'in_review',
            'note' => 'Dossier en cours d\'instruction',
        ]);
    }

    public function test_une_transition_invalide_est_refusee(): void
    {
        $citizen = $this->citizen('user_dem_d');
        $req = $this->requestFor($citizen); // submitted

        $agent = User::factory()->create(['clerk_id' => 'user_dem_agent2']);
        $agent->assignRole('municipal_agent');

        // submitted → approved n'est pas autorisé.
        $this->withToken($this->makeJwt('user_dem_agent2'))
            ->postJson("/api/v1/requests/{$req->id}/transition", ['to_status' => 'approved'])
            ->assertStatus(422);
    }

    public function test_un_citoyen_ne_peut_pas_changer_le_statut(): void
    {
        $citizen = $this->citizen('user_dem_e');
        $req = $this->requestFor($citizen);

        $this->withToken($this->makeJwt('user_dem_e'))
            ->postJson("/api/v1/requests/{$req->id}/transition", ['to_status' => 'in_review'])
            ->assertStatus(403);
    }
}
