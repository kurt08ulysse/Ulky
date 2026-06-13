<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\MarketStall;
use App\Models\StallRent;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Métier commerçant — loyers d'emplacement.
 *
 * Vérifie l'isolation par occupant (« chacun ne voit/paie que ses loyers »),
 * le paiement via le socle SingPay, et la génération de quittance de loyer.
 */
class MarketRentTest extends TestCase
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
            'sub' => $clerkId,
            'iat' => time(),
            'exp' => time() + 3600,
            'iss' => 'https://clerk.test',
        ], self::TESTING_SECRET, 'HS256');
    }

    private function signWebhook(array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        return [
            'body' => $body,
            'headers' => [
                'X-SingPay-Signature' => hash_hmac('sha256', $body, self::WEBHOOK_SECRET),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
    }

    private function makeRent(User $occupant, ?int $communeId = null): StallRent
    {
        $market = Market::create(['name' => 'Marché du Plateau', 'commune_id' => $communeId, 'is_active' => true]);
        $stall = MarketStall::create([
            'market_id' => $market->id,
            'stall_number' => 'A'.fake()->unique()->numberBetween(1, 9999),
            'rent_amount_cents' => 1500000,
            'status' => 'occupied',
            'occupant_id' => $occupant->id,
        ]);

        return StallRent::create([
            'market_stall_id' => $stall->id,
            'occupant_id' => $occupant->id,
            'amount_cents' => 1500000,
            'period' => '2026-06',
            'status' => 'pending',
            'due_date' => now()->addDays(10)->toDateString(),
            'commune_id' => $communeId,
        ]);
    }

    public function test_un_commercant_ne_voit_que_ses_propres_loyers(): void
    {
        $merchant = User::factory()->create(['clerk_id' => 'user_merchant_a']);
        $merchant->assignRole('merchant');
        $own = $this->makeRent($merchant);

        $other = User::factory()->create(['clerk_id' => 'user_merchant_b']);
        $other->assignRole('merchant');
        $this->makeRent($other);

        $response = $this->withToken($this->makeJwt('user_merchant_a'))
            ->getJson('/api/v1/my/rents');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_un_commercant_voit_ses_emplacements(): void
    {
        $merchant = User::factory()->create(['clerk_id' => 'user_merchant_stalls']);
        $merchant->assignRole('merchant');
        $this->makeRent($merchant);

        $response = $this->withToken($this->makeJwt('user_merchant_stalls'))
            ->getJson('/api/v1/my/stalls');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_un_commercant_paie_son_loyer(): void
    {
        $merchant = User::factory()->create(['clerk_id' => 'user_merchant_pay']);
        $merchant->assignRole('merchant');
        $rent = $this->makeRent($merchant);

        $response = $this->withToken($this->makeJwt('user_merchant_pay'))
            ->postJson("/api/v1/rents/{$rent->id}/pay", [
                'operator' => 'airtel_money',
                'phone' => '+24166000000',
            ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('payments', [
            'payable_type' => StallRent::class,
            'payable_id' => $rent->id,
            'status' => 'pending',
        ]);
    }

    public function test_un_commercant_ne_peut_pas_payer_le_loyer_d_un_autre(): void
    {
        $merchant = User::factory()->create(['clerk_id' => 'user_merchant_x']);
        $merchant->assignRole('merchant');

        $other = User::factory()->create(['clerk_id' => 'user_merchant_y']);
        $other->assignRole('merchant');
        $rentOfOther = $this->makeRent($other);

        $response = $this->withToken($this->makeJwt('user_merchant_x'))
            ->postJson("/api/v1/rents/{$rentOfOther->id}/pay", [
                'operator' => 'moov_money',
                'phone' => '062123456',
            ]);

        $response->assertStatus(403);
    }

    public function test_le_webhook_confirme_le_loyer_et_genere_une_quittance(): void
    {
        $merchant = User::factory()->create(['clerk_id' => 'user_merchant_wh', 'name' => 'Awa NDONG']);
        $merchant->assignRole('merchant');
        $rent = $this->makeRent($merchant);

        $payment = $rent->payments()->create([
            'amount' => $rent->total_amount,
            'operator' => 'airtel_money',
            'phone' => '+24166000000',
            'status' => 'pending',
        ]);

        $signed = $this->signWebhook([
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'status' => 'successful',
            'transaction_id' => 'sp_rent_4455',
        ]);

        $response = $this->call(
            'POST',
            '/api/webhooks/singpay',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($signed['headers']),
            $signed['body']
        );

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('stall_rents', ['id' => $rent->id, 'status' => 'paid']);
        $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id]);

        // La page publique de vérification affiche bien les infos du loyer.
        $receipt = $payment->fresh()->receipt;
        $this->get("/verify/receipt/{$receipt->qr_code_token}")
            ->assertStatus(200)
            ->assertSee('Awa NDONG')
            ->assertSee('Loyer');
    }
}
