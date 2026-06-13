<?php

namespace Tests\Feature\Api;

use App\Models\ElectedOfficial;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Élus de la commune : contenu géré par l'admin, affiché en lecture seule aux
 * citoyens/commerçants. Seuls les élus publiés sont exposés, ordonnés.
 */
class ElectedOfficialTest extends TestCase
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

    public function test_un_citoyen_voit_les_elus_publies_ordonnes(): void
    {
        ElectedOfficial::create(['name' => 'Jean MBA', 'title' => 'Mayor', 'display_order' => 1, 'is_published' => true]);
        ElectedOfficial::create(['name' => 'Marie OBAME', 'title' => 'Deputy Mayor', 'display_order' => 2, 'is_published' => true]);
        ElectedOfficial::create(['name' => 'Brouillon', 'title' => 'Councillor', 'display_order' => 3, 'is_published' => false]);

        $citizen = User::factory()->create(['clerk_id' => 'user_officials']);
        $citizen->assignRole('citizen');

        $response = $this->withToken($this->makeJwt('user_officials'))
            ->getJson('/api/v1/officials');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')                       // l'élu non publié est masqué
            ->assertJsonPath('data.0.name', 'Jean MBA')        // ordonné par display_order
            ->assertJsonPath('data.1.title', 'Deputy Mayor');
    }
}
