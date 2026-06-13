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
 * Isolation des données par utilisateur sur GET /api/v1/tax-notices.
 *
 * Vérifie le principe « chacun ne voit que ses propres avis » :
 * - un commerçant (rôle merchant) ne voit pas les avis d'un citoyen ;
 * - un compte sans rôle ne voit que les siens ;
 * - un agent municipal voit l'ensemble.
 */
class TaxNoticeIsolationTest extends TestCase
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

    private function noticeFor(User $user): TaxNotice
    {
        $tax = Tax::create([
            'name' => 'Taxe '.$user->id,
            'base_amount' => 100000,
            'stamp_amount' => 0,
            'periodicity' => 'one_time',
        ]);

        return TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $user->id,
            'base_amount' => 100000,
            'stamp_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(20)->toDateString(),
        ]);
    }

    public function test_un_commercant_ne_voit_pas_les_avis_d_un_citoyen(): void
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_iso']);
        $citizen->assignRole('citizen');
        $citizenNotice = $this->noticeFor($citizen);

        $merchant = User::factory()->create(['clerk_id' => 'user_merchant_iso']);
        $merchant->assignRole('merchant');

        $response = $this->withToken($this->makeJwt('user_merchant_iso'))
            ->getJson('/api/v1/tax-notices');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonMissing(['id' => $citizenNotice->id]);
    }

    public function test_un_compte_sans_role_ne_voit_que_ses_propres_avis(): void
    {
        $other = User::factory()->create(['clerk_id' => 'user_other_iso']);
        $other->assignRole('citizen');
        $this->noticeFor($other);

        $roleless = User::factory()->create(['clerk_id' => 'user_noroles_iso']);
        $ownNotice = $this->noticeFor($roleless);

        $response = $this->withToken($this->makeJwt('user_noroles_iso'))
            ->getJson('/api/v1/tax-notices');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownNotice->id);
    }

    public function test_un_agent_municipal_voit_tous_les_avis(): void
    {
        $citizenA = User::factory()->create(['clerk_id' => 'user_a_iso']);
        $this->noticeFor($citizenA);
        $citizenB = User::factory()->create(['clerk_id' => 'user_b_iso']);
        $this->noticeFor($citizenB);

        $agent = User::factory()->create(['clerk_id' => 'user_agent_iso']);
        $agent->assignRole('municipal_agent');

        $response = $this->withToken($this->makeJwt('user_agent_iso'))
            ->getJson('/api/v1/tax-notices');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }
}
