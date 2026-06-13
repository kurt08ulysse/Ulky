<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligne les loyers d'emplacement (StallRent) sur le socle de paiement polymorphe.
 *
 * - Ajoute `commune_id` (gelé à l'émission) pour le cloisonnement multi-commune
 *   et la numérotation de quittance (ReceiptGeneratorService lit payable->commune_id).
 * - Supprime `payment_id` : le lien loyer ↔ paiement passe désormais par la
 *   relation polymorphe (Payment.payable), comme pour les avis de taxe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stall_rents', function (Blueprint $table) {
            $table->unsignedBigInteger('commune_id')->nullable()->index()->after('occupant_id');
        });

        Schema::table('stall_rents', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('stall_rents', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->dropColumn('commune_id');
        });
    }
};
