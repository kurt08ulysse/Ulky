<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Clerk est la source de vérité pour l'identité.
            // clerk_id est la clé de liaison principale — jamais l'email.
            // Nullable : le filet find-or-create peut créer le miroir avant que le webhook passe.
            $table->string('clerk_id')->unique()->nullable();

            $table->string('name');

            // Email optionnel selon PLAN.MD : la pénétration email au Gabon
            // est faible, le téléphone est l'identité primaire future.
            $table->string('email')->nullable();

            // Identité primaire future : OTP SMS (phase 1 → Clerk phone, phase 5 → SMS local)
            $table->string('phone')->nullable();

            // Séparation rôle / type contribuable (cf. PLAN.MD phase 1)
            // Le RÔLE (citizen, cashier…) vit dans spatie/laravel-permission.
            // Le TYPE DE CONTRIBUABLE est un attribut métier du User.
            $table->enum('taxpayer_type', ['individual', 'business'])->default('individual');

            // Préparation multi-tenant phase 2 — null = non rattaché à une commune
            $table->unsignedBigInteger('commune_id')->nullable()->index();

            $table->timestamps();
        });

        // Gardée pour le driver de session Laravel (SESSION_DRIVER=database)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
