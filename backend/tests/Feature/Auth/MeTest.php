<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature pour GET /api/v1/auth/me.
 *
 * Stratégie : aucun appel réseau à Clerk, aucune dépendance OpenSSL.
 * On utilise HS256 (HMAC-SHA256) avec un secret symétrique local.
 * Le middleware ClerkAuthenticate accepte CLERK_TESTING_SECRET pour ce mode.
 */
class MeTest extends TestCase
{
    use RefreshDatabase;

    private const TESTING_SECRET = 'super-secret-test-key-ulky-2026-abcdefgh';

    protected function setUp(): void
    {
        parent::setUp();

        // Injecter le secret symétrique de test
        // Le middleware l'utilise au lieu d'appeler clerk.com pour les JWKS
        config(['services.clerk.testing_secret' => self::TESTING_SECRET]);
    }

    /** Créer un JWT HS256 signé avec le secret de test. */
    private function makeJwt(string $clerkId, int $expiresIn = 3600): string
    {
        $payload = [
            'sub' => $clerkId,
            'iat' => time(),
            'exp' => time() + $expiresIn,
            'iss' => 'https://clerk.test',
        ];

        return JWT::encode($payload, self::TESTING_SECRET, 'HS256');
    }

    public function test_me_sans_token_retourne_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_avec_jwt_valide_retourne_le_profil(): void
    {
        $user = User::factory()->create([
            'clerk_id' => 'user_test_123',
            'name' => 'Jean Test',
            'email' => 'jean@test.com',
        ]);

        $token = $this->makeJwt('user_test_123');

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'clerk_id', 'name', 'email', 'phone', 'taxpayer_type', 'roles'],
                'meta' => [],
            ])
            ->assertJsonPath('data.clerk_id', 'user_test_123')
            ->assertJsonPath('data.name', 'Jean Test');
    }

    public function test_me_avec_jwt_expire_retourne_401(): void
    {
        User::factory()->create(['clerk_id' => 'user_expired_456']);

        // JWT expiré depuis 1 heure
        $token = $this->makeJwt('user_expired_456', expiresIn: -3600);

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_avec_jwt_invalide_retourne_401(): void
    {
        $response = $this->withToken('jwt.invalide.totalement')->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
