<?php

namespace Tests\Feature\Api;

use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sécurité — export CSV admin.
 *
 * Vérifie que les valeurs contrôlées par le contribuable (nom via Clerk) sont
 * neutralisées avant écriture dans le CSV (protection CSV/Formula Injection).
 */
class AdminExportCsvTest extends TestCase
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

    public function test_export_csv_neutralise_l_injection_de_formule(): void
    {
        // Contribuable dont le nom est une charge utile de formule.
        $citizen = User::factory()->create([
            'clerk_id' => 'user_citizen_evil',
            'name' => '=cmd|\'/c calc\'!A1',
            'phone' => '+24166000000',
        ]);

        $tax = Tax::create([
            'name' => 'Taxe de test',
            'base_amount' => 500000,
            'stamp_amount' => 0,
            'periodicity' => 'one_time',
        ]);

        TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $citizen->id,
            'base_amount' => 500000,
            'stamp_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $agent = User::factory()->create(['clerk_id' => 'user_agent_csv']);
        $agent->assignRole('municipal_agent');

        $response = $this->withToken($this->makeJwt('user_agent_csv'))
            ->get('/api/v1/admin/export/csv');

        $response->assertStatus(200);

        $body = $response->streamedContent();

        // La charge utile doit être préfixée d'une apostrophe (mode texte forcé)…
        $this->assertStringContainsString("'=cmd|'/c calc'!A1", $body);
        // …et ne doit jamais apparaître comme formule active en début de cellule.
        $this->assertStringNotContainsString(';=cmd', $body);
    }

    public function test_la_liste_admin_des_avis_charge_la_relation_paiement(): void
    {
        // Régression : TaxNotice doit exposer la relation payment (sinon 500).
        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_list']);
        $tax = Tax::create([
            'name' => 'Taxe liste',
            'base_amount' => 100000,
            'stamp_amount' => 0,
            'periodicity' => 'one_time',
        ]);
        TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $citizen->id,
            'base_amount' => 100000,
            'stamp_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(15)->toDateString(),
        ]);

        $agent = User::factory()->create(['clerk_id' => 'user_agent_list']);
        $agent->assignRole('municipal_agent');

        $response = $this->withToken($this->makeJwt('user_agent_list'))
            ->getJson('/api/v1/admin/tax-notices');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.tax.name', 'Taxe liste')
            ->assertJsonPath('data.0.payment', null);
    }
}
