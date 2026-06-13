<?php

namespace Tests\Feature\Api;

use App\Models\AdministrativeRequest;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Démarches payantes : frais fixés par la mairie, réglés par le citoyen via SingPay.
 * Le paiement ne touche QUE payment_status (le workflow status reste indépendant).
 */
class DemarchePaymentTest extends TestCase
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
        Storage::fake('public');
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

    private function requestWithFee(User $user, int $fee): AdministrativeRequest
    {
        return AdministrativeRequest::create([
            'user_id' => $user->id,
            'type' => 'acte_naissance',
            'title' => 'Copie intégrale d\'acte de naissance',
            'fee_amount' => $fee,
            'status' => 'in_review',
        ]);
    }

    public function test_la_mairie_fixe_des_frais_via_la_transition(): void
    {
        $citizen = $this->citizen('user_dpm_c1');
        $req = AdministrativeRequest::create([
            'user_id' => $citizen->id, 'type' => 'certificat', 'title' => 'Certificat', 'status' => 'submitted',
        ]);

        $agent = User::factory()->create(['clerk_id' => 'user_dpm_agent']);
        $agent->assignRole('municipal_agent');

        $this->withToken($this->makeJwt('user_dpm_agent'))
            ->postJson("/api/v1/requests/{$req->id}/transition", ['to_status' => 'in_review', 'fee_amount' => 500000])
            ->assertStatus(200)
            ->assertJsonPath('data.fee_amount', 500000);
    }

    public function test_un_citoyen_paie_les_frais_de_sa_demarche(): void
    {
        $citizen = $this->citizen('user_dpm_pay');
        $req = $this->requestWithFee($citizen, 500000);

        $response = $this->withToken($this->makeJwt('user_dpm_pay'))
            ->postJson("/api/v1/requests/{$req->id}/pay", ['operator' => 'airtel_money', 'phone' => '+24166000000']);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('payments', [
            'payable_type' => AdministrativeRequest::class,
            'payable_id' => $req->id,
            'status' => 'pending',
        ]);
    }

    public function test_payer_une_demarche_sans_frais_est_refuse(): void
    {
        $citizen = $this->citizen('user_dpm_nofee');
        $req = $this->requestWithFee($citizen, 0);

        $this->withToken($this->makeJwt('user_dpm_nofee'))
            ->postJson("/api/v1/requests/{$req->id}/pay", ['operator' => 'airtel_money', 'phone' => '+24166000000'])
            ->assertStatus(422);
    }

    public function test_le_webhook_confirme_les_frais_sans_toucher_le_workflow(): void
    {
        $citizen = $this->citizen('user_dpm_wh');
        $req = $this->requestWithFee($citizen, 500000); // status workflow = in_review

        $payment = $req->payments()->create([
            'amount' => $req->total_amount, 'operator' => 'airtel_money',
            'phone' => '+24166000000', 'status' => 'pending',
        ]);

        $body = json_encode([
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'status' => 'successful',
            'transaction_id' => 'sp_dem_777',
        ], JSON_UNESCAPED_UNICODE);

        $headers = [
            'X-SingPay-Signature' => hash_hmac('sha256', $body, self::WEBHOOK_SECRET),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $this->call('POST', '/api/webhooks/singpay', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertStatus(200);

        // Frais payés, MAIS le statut de workflow reste inchangé (in_review).
        $this->assertDatabaseHas('administrative_requests', [
            'id' => $req->id,
            'payment_status' => 'paid',
            'status' => 'in_review',
        ]);
        $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id]);
    }
}
