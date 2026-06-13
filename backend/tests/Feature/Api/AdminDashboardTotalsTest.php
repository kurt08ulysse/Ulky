<?php

namespace Tests\Feature\Api;

use App\Models\Market;
use App\Models\MarketStall;
use App\Models\StallRent;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critère de sortie Phase 4 : les totaux affichés par le tableau de bord
 * ÉGALENT la somme des paiements confirmés (taxes + loyers) — vérifié par un
 * test, pas à l'œil. Sert aussi de garde-fou pour la relation polymorphe payable.
 */
class AdminDashboardTotalsTest extends TestCase
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

    private function paidTaxPayment(User $citizen, int $amount, string $status): void
    {
        $tax = Tax::create(['name' => 'Taxe '.uniqid(), 'base_amount' => $amount, 'periodicity' => 'one_time']);
        $notice = TaxNotice::create([
            'tax_id' => $tax->id, 'user_id' => $citizen->id,
            'base_amount' => $amount, 'status' => 'paid',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $notice->payments()->create([
            'amount' => $amount, 'operator' => 'airtel_money',
            'phone' => '+24166000000', 'status' => $status,
            'transaction_id' => 'tx_'.uniqid(),
        ]);
    }

    private function paidRentPayment(User $merchant, int $amount, string $status): void
    {
        $market = Market::create(['name' => 'Marché', 'is_active' => true]);
        $stall = MarketStall::create([
            'market_id' => $market->id, 'stall_number' => 'A'.fake()->unique()->numberBetween(1, 9999),
            'rent_amount_cents' => $amount, 'status' => 'occupied', 'occupant_id' => $merchant->id,
        ]);
        $rent = StallRent::create([
            'market_stall_id' => $stall->id, 'occupant_id' => $merchant->id,
            'amount_cents' => $amount, 'period' => '2026-06', 'status' => 'paid',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $rent->payments()->create([
            'amount' => $amount, 'operator' => 'airtel_money',
            'phone' => '+24166000000', 'status' => $status,
            'transaction_id' => 'tx_'.uniqid(),
        ]);
    }

    public function test_les_totaux_du_dashboard_egalent_la_somme_des_paiements_confirmes(): void
    {
        $citizen = User::factory()->create();
        $merchant = User::factory()->create();

        // Confirmés : 500 000 + 300 000 (taxes) + 150 000 (loyer) = 950 000
        $this->paidTaxPayment($citizen, 500000, 'successful');
        $this->paidTaxPayment($citizen, 300000, 'successful');
        $this->paidRentPayment($merchant, 150000, 'successful');
        // Non confirmé : ne doit PAS compter
        $this->paidTaxPayment($citizen, 999999, 'failed');

        $admin = User::factory()->create(['clerk_id' => 'user_dash_admin']);
        $admin->assignRole('super_admin');

        $response = $this->withToken($this->makeJwt('user_dash_admin'))
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('collected.total.amount', 950000)
            ->assertJsonPath('collected.total.formatted', '9 500 FCFA');
    }
}
