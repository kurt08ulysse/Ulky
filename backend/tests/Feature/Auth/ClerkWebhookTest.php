<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature pour POST /webhooks/clerk.
 *
 * Stratégie : on génère une vraie signature Svix locale avec un secret de test.
 * Aucun appel réseau à Clerk.
 */
class ClerkWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_dGVzdHNlY3JldGtleXRoYXRpc3Zlcnlsb25n'; // base64 fictif — test uniquement

    protected function setUp(): void
    {
        parent::setUp();

        // Injecter le secret de test
        config(['services.clerk.webhook_secret' => $this->webhookSecret]);

        // Créer les rôles ULKY (RefreshDatabase recrée la base vide)
        $this->seed(RolesSeeder::class);
    }

    /** Génère les headers Svix valides pour un payload donné. */
    private function svixHeaders(string $payload): array
    {
        $msgId = 'msg_'.uniqid();
        $timestamp = (string) time();

        $toSign = "{$msgId}.{$timestamp}.{$payload}";
        $secret = base64_decode(substr($this->webhookSecret, strlen('whsec_')));
        $sig = base64_encode(hash_hmac('sha256', $toSign, $secret, true));

        return [
            'svix-id' => $msgId,
            'svix-timestamp' => $timestamp,
            'svix-signature' => "v1,{$sig}",
            'Content-Type' => 'application/json',
        ];
    }

    public function test_webhook_sans_signature_retourne_401(): void
    {
        $response = $this->postJson('/api/webhooks/clerk', [
            'type' => 'user.created',
            'data' => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_user_created_cree_le_miroir(): void
    {
        $payload = json_encode([
            'type' => 'user.created',
            'data' => [
                'id' => 'user_clerk_abc',
                'first_name' => 'Marie',
                'last_name' => 'Dupont',
                'email_addresses' => [['email_address' => 'marie@exemple.com']],
                'phone_numbers' => [['phone_number' => '+241060000001']],
            ],
        ]);

        $response = $this->call(
            'POST',
            '/api/webhooks/clerk',
            [],
            [],
            [],
            $this->transformHeaders($this->svixHeaders($payload)),
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'clerk_id' => 'user_clerk_abc',
            'name' => 'Marie Dupont',
            'email' => 'marie@exemple.com',
        ]);
    }

    public function test_user_created_rejoue_ne_cree_pas_de_doublon(): void
    {
        $payload = json_encode([
            'type' => 'user.created',
            'data' => [
                'id' => 'user_clerk_idempotent',
                'first_name' => 'Paul',
                'last_name' => 'Martin',
                'email_addresses' => [['email_address' => 'paul@exemple.com']],
                'phone_numbers' => [],
            ],
        ]);

        $headers = $this->transformHeaders($this->svixHeaders($payload));

        // Premier appel
        $this->call('POST', '/api/webhooks/clerk', [], [], [], $headers, $payload)->assertStatus(200);
        // Rejeu
        $this->call('POST', '/api/webhooks/clerk', [], [], [], $headers, $payload)->assertStatus(200);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_updated_met_a_jour_le_miroir(): void
    {
        User::factory()->create([
            'clerk_id' => 'user_clerk_update',
            'name' => 'Ancien Nom',
            'email' => 'ancien@exemple.com',
        ]);

        $payload = json_encode([
            'type' => 'user.updated',
            'data' => [
                'id' => 'user_clerk_update',
                'first_name' => 'Nouveau',
                'last_name' => 'Nom',
                'email_addresses' => [['email_address' => 'nouveau@exemple.com']],
                'phone_numbers' => [],
            ],
        ]);

        $this->call(
            'POST',
            '/api/webhooks/clerk',
            [],
            [],
            [],
            $this->transformHeaders($this->svixHeaders($payload)),
            $payload
        )->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'clerk_id' => 'user_clerk_update',
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@exemple.com',
        ]);
    }

    public function test_user_deleted_anonymise_sans_supprimer(): void
    {
        User::factory()->create([
            'clerk_id' => 'user_clerk_delete',
            'name' => 'À Supprimer',
            'email' => 'supprimer@exemple.com',
        ]);

        $payload = json_encode([
            'type' => 'user.deleted',
            'data' => ['id' => 'user_clerk_delete'],
        ]);

        $this->call(
            'POST',
            '/api/webhooks/clerk',
            [],
            [],
            [],
            $this->transformHeaders($this->svixHeaders($payload)),
            $payload
        )->assertStatus(200);

        // Le user existe toujours en base (données fiscales préservées)
        $this->assertDatabaseCount('users', 1);

        // Mais anonymisé
        $this->assertDatabaseHas('users', [
            'name' => '[compte supprimé]',
            'email' => null,
            'clerk_id' => null,
        ]);
    }

    /** Convertit un tableau de headers en format attendu par $this->call(). */
    private function transformHeaders(array $headers): array
    {
        $transformed = [];
        foreach ($headers as $key => $value) {
            $key = 'HTTP_'.strtoupper(str_replace('-', '_', $key));
            $transformed[$key] = $value;
        }

        return $transformed;
    }
}
