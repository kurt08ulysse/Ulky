<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SingPayWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'test-webhook-secret-ulky-2026';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.singpay.webhook_secret' => self::WEBHOOK_SECRET,
            'services.singpay.testing_status' => 'successful',
        ]);
        $this->seed(RolesSeeder::class);
    }

    private function makePayment(): Payment
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_webhook_test']);
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

        return Payment::create([
            'tax_notice_id' => $notice->id,
            'amount' => $notice->total_amount,
            'operator' => 'airtel_money',
            'phone' => '+24166000000',
            'status' => 'pending',
        ]);
    }

    private function postSigned(array $payload, ?string $secretOverride = null): TestResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $body, $secretOverride ?? self::WEBHOOK_SECRET);

        return $this->call(
            'POST',
            '/api/webhooks/singpay',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'X-SingPay-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
            $body
        );
    }

    public function test_webhook_non_signe_est_rejete_avec_401_et_audit(): void
    {
        $payment = $this->makePayment();

        $response = $this->postJson('/api/webhooks/singpay', [
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'status' => 'successful',
            'transaction_id' => 'sp_tx_001',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('audit_logs', ['action' => 'webhook.signature_failed']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('receipts', ['payment_id' => $payment->id]);
    }

    public function test_webhook_avec_mauvaise_signature_est_rejete(): void
    {
        $payment = $this->makePayment();

        $response = $this->postSigned([
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'status' => 'successful',
            'transaction_id' => 'sp_tx_002',
        ], 'wrong-secret');

        $response->assertStatus(401);

        $this->assertDatabaseHas('audit_logs', ['action' => 'webhook.signature_failed']);
    }

    public function test_webhook_signe_avec_statut_divergent_est_rejete_avec_409(): void
    {
        $payment = $this->makePayment();
        config(['services.singpay.testing_status' => 'failed']);

        $response = $this->postSigned([
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'status' => 'successful',
            'transaction_id' => 'sp_tx_003',
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('audit_logs', ['action' => 'webhook.status_mismatch']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('receipts', ['payment_id' => $payment->id]);
    }

    public function test_webhook_signe_et_confirme_genere_une_quittance_avec_trace_audit(): void
    {
        $payment = $this->makePayment();

        $response = $this->postSigned([
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'status' => 'successful',
            'transaction_id' => 'sp_tx_004',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'successful']);
        $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id]);

        foreach (['webhook.received', 'payment.confirmed', 'receipt.generated'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action]);
        }
    }

    public function test_rejeu_du_meme_webhook_est_idempotent(): void
    {
        $payment = $this->makePayment();
        $reference = 'PAY-'.$payment->id.'-'.time();

        $this->postSigned(['reference' => $reference, 'status' => 'successful', 'transaction_id' => 'sp_tx_005'])
            ->assertStatus(200);

        $this->postSigned(['reference' => $reference, 'status' => 'successful', 'transaction_id' => 'sp_tx_005'])
            ->assertStatus(200);

        $this->assertSame(1, Receipt::where('payment_id', $payment->id)->count());
        $this->assertSame(2, AuditLog::where('action', 'webhook.received')->count());
    }

    public function test_webhook_signe_avec_reference_inconnue_renvoie_404_et_audit(): void
    {
        $response = $this->postSigned([
            'reference' => 'PAY-999999-12345',
            'status' => 'successful',
            'transaction_id' => 'sp_tx_006',
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('audit_logs', ['action' => 'webhook.unknown_payment']);
    }
}
