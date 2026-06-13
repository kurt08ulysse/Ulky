<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dépenses de la commune (comptabilité régisseur).
 *
 * Complète les recettes (encaissements) pour donner au régisseur une vue
 * recettes / dépenses / solde net. Saisies par le personnel financier
 * (régisseur/admin), jamais par un citoyen. Cloisonnées par commune.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();            // ex. DEP-2026-AB12CD
            $table->string('category');                        // fournitures | salaires | maintenance | ...
            $table->string('label');
            $table->unsignedInteger('amount');                 // en centimes
            $table->date('spent_at');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('commune_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
