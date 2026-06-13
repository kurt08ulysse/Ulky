<?php

namespace Tests\Feature\Api;

use App\Models\CitizenReport;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Signalements citoyens — isolation par signaleur et machine à états.
 */
class CitizenReportTest extends TestCase
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

    private function citizen(string $clerkId): User
    {
        $u = User::factory()->create(['clerk_id' => $clerkId]);
        $u->assignRole('citizen');

        return $u;
    }

    private function reportFor(User $user): CitizenReport
    {
        return CitizenReport::create([
            'user_id' => $user->id,
            'category' => 'voirie',
            'title' => 'Nid-de-poule dangereux',
            'status' => 'new',
            'commune_id' => $user->commune_id,
        ]);
    }

    public function test_un_citoyen_signale_un_probleme_avec_localisation(): void
    {
        $citizen = $this->citizen('user_rep_create');

        $response = $this->withToken($this->makeJwt('user_rep_create'))
            ->postJson('/api/v1/reports', [
                'category' => 'eclairage',
                'title' => 'Lampadaire éteint',
                'description' => 'Rue sombre la nuit',
                'latitude' => -0.3976,
                'longitude' => 9.4673,
                'address' => 'Quartier Louis',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.category', 'eclairage');

        $this->assertDatabaseHas('citizen_reports', [
            'user_id' => $citizen->id,
            'category' => 'eclairage',
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('citizen_report_events', ['to_status' => 'new', 'from_status' => null]);
    }

    public function test_un_citoyen_ne_voit_que_ses_signalements(): void
    {
        $a = $this->citizen('user_rep_a');
        $own = $this->reportFor($a);
        $b = $this->citizen('user_rep_b');
        $this->reportFor($b);

        $this->withToken($this->makeJwt('user_rep_a'))
            ->getJson('/api/v1/reports')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_un_citoyen_ne_peut_pas_voir_le_signalement_d_un_autre(): void
    {
        $a = $this->citizen('user_rep_a2');
        $b = $this->citizen('user_rep_b2');
        $reportOfB = $this->reportFor($b);

        $this->withToken($this->makeJwt('user_rep_a2'))
            ->getJson("/api/v1/reports/{$reportOfB->id}")
            ->assertStatus(403);
    }

    public function test_le_staff_traite_un_signalement_avec_trace(): void
    {
        $citizen = $this->citizen('user_rep_c');
        $report = $this->reportFor($citizen);

        $agent = User::factory()->create(['clerk_id' => 'user_rep_agent']);
        $agent->assignRole('municipal_agent');

        $this->withToken($this->makeJwt('user_rep_agent'))
            ->postJson("/api/v1/reports/{$report->id}/transition", [
                'to_status' => 'acknowledged',
                'note' => 'Équipe voirie prévenue',
                'assigned_to' => $agent->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'acknowledged')
            ->assertJsonPath('data.assigned_to', $agent->id);

        $this->assertDatabaseHas('citizen_report_events', [
            'citizen_report_id' => $report->id,
            'from_status' => 'new',
            'to_status' => 'acknowledged',
            'note' => 'Équipe voirie prévenue',
        ]);
    }

    public function test_une_transition_invalide_est_refusee(): void
    {
        $citizen = $this->citizen('user_rep_d');
        $report = $this->reportFor($citizen); // new

        $agent = User::factory()->create(['clerk_id' => 'user_rep_agent2']);
        $agent->assignRole('municipal_agent');

        // new → resolved n'est pas autorisé.
        $this->withToken($this->makeJwt('user_rep_agent2'))
            ->postJson("/api/v1/reports/{$report->id}/transition", ['to_status' => 'resolved'])
            ->assertStatus(422);
    }

    public function test_un_citoyen_ne_peut_pas_traiter_un_signalement(): void
    {
        $citizen = $this->citizen('user_rep_e');
        $report = $this->reportFor($citizen);

        $this->withToken($this->makeJwt('user_rep_e'))
            ->postJson("/api/v1/reports/{$report->id}/transition", ['to_status' => 'acknowledged'])
            ->assertStatus(403);
    }
}
