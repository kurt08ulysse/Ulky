<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : marchés municipaux et emplacements.
 *
 * Un Market est un marché appartenant à la mairie (ex. "Marché du Plateau", "Marché de Libreville").
 * Un MarketStall est un emplacement (kiosque, boutique, étal) loué à un commerçant.
 *
 * Modèle économique :
 *   - Chaque emplacement a un loyer mensuel (rent_amount_cents).
 *   - Un commerçant (User avec rôle 'merchant') peut occuper un ou plusieurs emplacements.
 *   - Les loyers génèrent des StallRent (équivalents de TaxNotice pour les marchés).
 *   - Le paiement passe par SingPay (même chemin que les taxes : Payment → Receipt).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Marchés ──────────────────────────────────────────────────────────
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // ex. "Marché du Plateau"
            $table->string('address')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Emplacements (stalles) ────────────────────────────────────────────
        Schema::create('market_stalls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->string('stall_number');                  // ex. "A12", "B04"
            $table->string('stall_type')->default('standard'); // standard | boutique | kiosque | entrepot
            $table->unsignedInteger('rent_amount_cents');    // loyer mensuel en centimes
            $table->string('status')->default('vacant');     // vacant | occupied | maintenance
            // Occupant actuel (null si vacant)
            $table->foreignId('occupant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('occupancy_start_date')->nullable(); // début du bail
            $table->date('occupancy_end_date')->nullable();   // fin du bail (null = indéterminée)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['market_id', 'stall_number']);
        });

        // ── Avis de loyer (équivalent TaxNotice pour les marchés) ─────────────
        Schema::create('stall_rents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_stall_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupant_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');         // montant gelé à l'émission
            $table->string('period');                        // ex. "2026-06" (YYYY-MM)
            $table->string('status')->default('pending');    // pending | paid | cancelled | late
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            // Lien optionnel vers Payment si payé via SingPay
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // Un seul avis par emplacement par période
            $table->unique(['market_stall_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stall_rents');
        Schema::dropIfExists('market_stalls');
        Schema::dropIfExists('markets');
    }
};
