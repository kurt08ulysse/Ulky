<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tax;
use App\Models\TaxNotice;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Réconciliation quotidienne des paiements (Phase 3).
 */
class ReconcilePaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function successfulPayment(): void
    {
        $citizen = User::factory()->create();
        $tax = Tax::create(['name' => 'Taxe', 'base_amount' => 500000, 'periodicity' => 'one_time']);
        $notice = TaxNotice::create([
            'tax_id' => $tax->id, 'user_id' => $citizen->id, 'base_amount' => 500000,
            'status' => 'paid', 'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $notice->payments()->create([
            'amount' => 500000, 'operator' => 'airtel_money', 'phone' => '+24166000000',
            'status' => 'successful', 'transaction_id' => 'sp_tx_recon',
        ]);
    }

    public function test_reconciliation_conforme_sort_en_succes(): void
    {
        config(['services.singpay.testing_status' => 'successful']);
        $this->successfulPayment();

        $this->artisan('payments:reconcile')->assertExitCode(0);

        $this->assertDatabaseHas('audit_logs', ['action' => 'reconciliation.run']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'reconciliation.discrepancy']);
    }

    public function test_reconciliation_detecte_un_ecart_et_sort_en_echec(): void
    {
        // Local "successful" mais SingPay répond "failed" → écart bloquant.
        config(['services.singpay.testing_status' => 'failed']);
        $this->successfulPayment();

        $this->artisan('payments:reconcile')->assertExitCode(1);

        $this->assertDatabaseHas('audit_logs', ['action' => 'reconciliation.discrepancy']);
        $this->assertSame(1, AuditLog::where('action', 'reconciliation.discrepancy')->count());
    }
}
