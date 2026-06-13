<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\MarketStall;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Numéro de commerçant — la mairie promeut, jamais l'utilisateur lui-même.
 * Le commerçant reste le MÊME compte (aucune collision d'email).
 */
class MerchantPromotionTest extends TestCase
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

    public function test_la_mairie_promeut_un_citoyen_en_commercant(): void
    {
        $citizen = User::factory()->create(['phone' => '+24177001122']);
        $citizen->assignRole('citizen');

        $agent = User::factory()->create(['clerk_id' => 'user_agent_promote']);
        $agent->assignRole('municipal_agent');

        $response = $this->withToken($this->makeJwt('user_agent_promote'))
            ->postJson('/api/v1/admin/merchants', ['phone' => '+24177001122']);

        $response->assertStatus(200)
            ->assertJsonPath('data.roles', fn ($roles) => in_array('merchant', $roles, true));

        $citizen->refresh();
        $this->assertTrue($citizen->hasRole('merchant'));
        $this->assertNotEmpty($citizen->merchant_number);
        $this->assertStringStartsWith('COM-', $citizen->merchant_number);
        // Reste citoyen : c'est le même compte, on a juste ajouté un rôle.
        $this->assertTrue($citizen->hasRole('citizen'));
    }

    public function test_allouer_un_emplacement_promeut_le_commercant(): void
    {
        $person = User::factory()->create();
        $this->assertFalse($person->hasRole('merchant'));

        $market = Market::create(['name' => 'Marché', 'is_active' => true]);
        MarketStall::create([
            'market_id' => $market->id, 'stall_number' => 'A1',
            'rent_amount_cents' => 1000000, 'status' => 'occupied', 'occupant_id' => $person->id,
        ]);

        $person->refresh();
        $this->assertTrue($person->hasRole('merchant'));
        $this->assertNotEmpty($person->merchant_number);
    }

    public function test_un_citoyen_ne_peut_pas_se_promouvoir(): void
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_self_promote', 'phone' => '+24177003344']);
        $citizen->assignRole('citizen');

        $this->withToken($this->makeJwt('user_self_promote'))
            ->postJson('/api/v1/admin/merchants', ['phone' => '+24177003344'])
            ->assertStatus(403);
    }
}
