<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signalements citoyens (Phase 6).
 *
 * Un citoyen signale un incident (voirie, éclairage, déchets, eau, sécurité…)
 * avec une localisation optionnelle, et en suit le traitement par la mairie.
 * Historique des transitions append-only.
 *
 * Hors socle (différé) : photos (stockage), notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();            // ex. SIG-2026-AB12CD
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category');                        // voirie | eclairage | dechets | eau | securite | autre
            $table->string('title');
            $table->text('description')->nullable();
            // new | acknowledged | in_progress | resolved | rejected | closed
            $table->string('status')->default('new')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('commune_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('citizen_report_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_report_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();       // append-only
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_report_events');
        Schema::dropIfExists('citizen_reports');
    }
};
