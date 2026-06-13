<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Élus de la commune (maire et conseil) — contenu présentationnel.
 *
 * Géré par l'administration (back-office) : photo + description (rédigée en
 * anglais). Affiché en lecture seule aux citoyens et commerçants dans l'appli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elected_officials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');                           // ex. Mayor, Deputy Mayor
            $table->string('photo_path')->nullable();          // disque public
            $table->text('description')->nullable();           // rédigée en anglais
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('commune_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elected_officials');
    }
};
