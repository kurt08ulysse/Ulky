<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Démarches payantes : une demande administrative peut comporter des frais
 * (fee_amount, en centimes) réglés via le même rail SingPay → quittance.
 *
 * Le paiement est SÉPARÉ du workflow : `payment_status` (unpaid/paid) n'écrase
 * jamais `status` (submitted/in_review/…). Frais fixés par la mairie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->unsignedInteger('fee_amount')->default(0)->after('description');
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->dropColumn(['fee_amount', 'payment_status', 'paid_at']);
        });
    }
};
