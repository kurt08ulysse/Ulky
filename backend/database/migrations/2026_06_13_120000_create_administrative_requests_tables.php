<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socle "Demandes administratives" (Phase 5).
 *
 * Un citoyen dépose une demande (acte de naissance, certificat, autorisation…)
 * et en suit le traitement par états. L'historique des transitions est
 * append-only (jamais modifié/supprimé), comme le journal d'audit financier.
 *
 * Hors périmètre de ce socle : pièces jointes (stockage), notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();           // ex. DEM-2026-AB12CD
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');                           // catégorie de démarche
            $table->string('title');
            $table->text('description')->nullable();
            // submitted | in_review | additional_info | approved | rejected | closed
            $table->string('status')->default('submitted')->index();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('administrative_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrative_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();      // append-only : pas d'updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_request_events');
        Schema::dropIfExists('administrative_requests');
    }
};
