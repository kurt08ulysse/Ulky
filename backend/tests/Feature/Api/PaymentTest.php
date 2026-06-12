<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private const TESTING_SECRET = 'super-secret-test-key-ulky-2026-abcdefgh';

    private const WEBHOOK_SECRET = 'test-webhook-secret-ulky-2026';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.clerk.testing_secret' => self::TESTING_SECRET,
            'services.singpay.webhook_secret' => self::WEBHOOK_SECRET,
            'services.singpay.testing_status' => 'successful',
        ]);
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

    private function signWebhook(array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $body, self::WEBHOOK_SECRET);

        return [
            'body' => $body,
            'headers' => [
                'X-SingPay-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
    }

    public function test_un_citoyen_peut_initier_le_paiement_de_son_propre_avis_de_taxe(): void
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_123']);
        $citizen->assignRole('citizen');

        $tax = Tax::create([
            'name' => 'Certificat de célibat',
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

        $response = $this->withToken($token)->postJson("/api/v1/tax-notices/{$notice->id}/pay", [
            'operator' => 'airtel_money',
            'phone' => '+24166000000',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'payment' => ['id', 'transaction_id', 'status']]);

        $this->assertDatabaseHas('payments', [
            'tax_notice_id' => $notice->id,
            'operator' => 'airtel_money',
            'phone' => '+24166000000',
            'status' => 'pending',
        ]);
    }

    public function test_un_citoyen_ne_peut_pas_payer_un_avis_de_taxe_d_un_autre(): void
    {
        $citizen1 = User::factory()->create(['clerk_id' => 'user_citizen_1']);
        $citizen1->assignRole('citizen');

        $citizen2 = User::factory()->create(['clerk_id' => 'user_citizen_2']);
        $citizen2->assignRole('citizen');

        $tax = Tax::create([
            'name' => 'Certificat de célibat',
            'base_amount' => 500000,
            'periodicity' => 'one_time',
        ]);

        $notice = TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $citizen2->id,
            'base_amount' => 500000,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
        ]);

        $token = $this->makeJwt('user_citizen_1');

        $response = $this->withToken($token)->postJson("/api/v1/tax-notices/{$notice->id}/pay", [
            'operator' => 'moov_money',
            'phone' => '062123456',
        ]);

        $response->assertStatus(403);
    }

    public function test_le_webhook_singpay_valide_le_paiement_et_genere_la_quittance(): void
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_123', 'name' => 'Jean EBO']);
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

        $payment = Payment::create([
            'tax_notice_id' => $notice->id,
            'amount' => $notice->total_amount,
            'operator' => 'airtel_money',
            'phone' => '+24166000000',
            'status' => 'pending',
        ]);

        $payload = [
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'status' => 'successful',
            'transaction_id' => 'sp_tx_998877',
            'amount' => 5000,
        ];

        $signed = $this->signWebhook($payload);

        $response = $this->call(
            'POST',
            '/api/webhooks/singpay',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($signed['headers']),
            $signed['body']
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'successful',
            'transaction_id' => 'sp_tx_998877',
        ]);

        $this->assertDatabaseHas('tax_notices', [
            'id' => $notice->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('receipts', [
            'payment_id' => $payment->id,
        ]);
    }
}
