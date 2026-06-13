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
 * Cloisonnement multi-commune (multi-tenant) sur les routes admin.
 *
 * - un commune_admin ne voit que les avis de SA commune ;
 * - il reçoit 404 sur un avis d'une autre commune ;
 * - un super_admin voit toutes les communes.
 */
class AdminCommuneScopeTest extends TestCase
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

    private function noticeInCommune(int $communeId): TaxNotice
    {
        $citizen = User::factory()->create(['commune_id' => $communeId]);
        $tax = Tax::create([
            'name' => 'Taxe commune '.$communeId.'-'.uniqid(),
            'base_amount' => 100000,
            'stamp_amount' => 0,
            'periodicity' => 'one_time',
        ]);

        return TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $citizen->id,
            'base_amount' => 100000,
            'stamp_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(20)->toDateString(),
            'commune_id' => $communeId,
        ]);
    }

    public function test_un_commune_admin_ne_voit_que_sa_commune(): void
    {
        $this->noticeInCommune(1);
        $noticeCommune2 = $this->noticeInCommune(2);

        $admin = User::factory()->create(['clerk_id' => 'user_cadmin', 'commune_id' => 1]);
        $admin->assignRole('commune_admin');

        $token = $this->makeJwt('user_cadmin');

        // La liste ne contient que la commune 1
        $this->withToken($token)->getJson('/api/v1/admin/tax-notices')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // L'avis de la commune 2 est invisible (404)
        $this->withToken($token)->getJson("/api/v1/admin/tax-notices/{$noticeCommune2->id}")
            ->assertStatus(404);
    }

    public function test_un_super_admin_voit_toutes_les_communes(): void
    {
        $this->noticeInCommune(1);
        $this->noticeInCommune(2);

        $admin = User::factory()->create(['clerk_id' => 'user_sadmin', 'commune_id' => 1]);
        $admin->assignRole('super_admin');

        $this->withToken($this->makeJwt('user_sadmin'))
            ->getJson('/api/v1/admin/tax-notices')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
