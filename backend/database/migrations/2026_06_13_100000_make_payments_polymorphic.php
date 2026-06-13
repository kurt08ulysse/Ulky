<?php

use App\Models\TaxNotice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rend `payments` polymorphe : un paiement peut régler un avis de taxe (TaxNotice)
 * OU un loyer d'emplacement (StallRent), via le même rail SingPay → quittance.
 *
 * Remplace la FK rigide `tax_notice_id` par `payable_type` / `payable_id`.
 * Les paiements existants (toujours des taxes) sont migrés vers payable = TaxNotice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->nullableMorphs('payable');
        });

        // Backfill : tout paiement existant réfère un avis de taxe.
        DB::table('payments')
            ->whereNotNull('tax_notice_id')
            ->update([
                'payable_type' => TaxNotice::class,
                'payable_id' => DB::raw('tax_notice_id'),
            ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['tax_notice_id']);
            $table->dropColumn('tax_notice_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('tax_notice_id')->nullable()->constrained('tax_notices')->onDelete('cascade');
        });

        DB::table('payments')
            ->where('payable_type', TaxNotice::class)
            ->update(['tax_notice_id' => DB::raw('payable_id')]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropMorphs('payable');
        });
    }
};
