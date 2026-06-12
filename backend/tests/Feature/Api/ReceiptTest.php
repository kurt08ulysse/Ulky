<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private const TESTING_SECRET = 'super-secret-test-key-ulky-2026-abcdefgh';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.clerk.testing_secret' => self::TESTING_SECRET]);
        $this->seed(RolesSeeder::class);
        Storage::fake('public');
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

    private function createReceiptForUser(User $user): Receipt
    {
        $tax = Tax::create([
            'name' => 'Attestation de cession',
            'base_amount' => 500000,
            'periodicity' => 'one_time',
        ]);

        $notice = TaxNotice::create([
            'tax_id' => $tax->id,
            'user_id' => $user->id,
            'base_amount' => 500000,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $payment = Payment::create([
            'tax_notice_id' => $notice->id,
            'amount' => $notice->total_amount,
            'operator' => 'airtel_money',
            'phone' => '+24166000000',
            'status' => 'successful',
            'transaction_id' => 'sp_tx_123',
        ]);

        return Receipt::create([
            'payment_id' => $payment->id,
            'receipt_number' => 'QTY-SYS-2026-00001',
            'qr_code_token' => 'test_qr_token_123',
            'pdf_path' => 'receipts/QTY-SYS-2026-00001.pdf',
        ]);
    }

    public function test_un_citoyen_peut_telecharger_sa_propre_quittance(): void
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_123']);
        $citizen->assignRole('citizen');

        $receipt = $this->createReceiptForUser($citizen);

        // Crée le faux fichier PDF
        Storage::disk('public')->put($receipt->pdf_path, 'fake pdf content');

        $token = $this->makeJwt('user_citizen_123');

        $response = $this->withToken($token)->get("/api/v1/receipts/{$receipt->id}");

        $response->assertStatus(200)
            ->assertHeader('content-disposition', 'attachment; filename=quittance-QTY-SYS-2026-00001.pdf');
    }

    public function test_un_citoyen_ne_peut_pas_telecharger_la_quittance_d_un_autre(): void
    {
        $citizen1 = User::factory()->create(['clerk_id' => 'user_citizen_1']);
        $citizen1->assignRole('citizen');

        $citizen2 = User::factory()->create(['clerk_id' => 'user_citizen_2']);
        $citizen2->assignRole('citizen');

        $receiptOfCitizen2 = $this->createReceiptForUser($citizen2);

        Storage::disk('public')->put($receiptOfCitizen2->pdf_path, 'fake pdf content');

        $token = $this->makeJwt('user_citizen_1');

        $response = $this->withToken($token)->get("/api/v1/receipts/{$receiptOfCitizen2->id}");

        $response->assertStatus(403);
    }

    public function test_verification_publique_de_quittance_via_qr_code_token(): void
    {
        $citizen = User::factory()->create(['clerk_id' => 'user_citizen_123', 'name' => 'Jean EBO']);
        $citizen->assignRole('citizen');

        $receipt = $this->createReceiptForUser($citizen);

        // Route publique, aucun JWT nécessaire
        $response = $this->get("/verify/receipt/{$receipt->qr_code_token}");

        $response->assertStatus(200)
            ->assertSee('Jean EBO')
            ->assertSee('QTY-SYS-2026-00001')
            ->assertSee('Attestation de cession');
    }

    public function test_verification_publique_echoue_avec_token_invalide(): void
    {
        $response = $this->get('/verify/receipt/invalid_token_xyz');

        $response->assertStatus(404);
    }
}
