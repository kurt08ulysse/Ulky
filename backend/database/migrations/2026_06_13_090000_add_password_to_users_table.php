<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mot de passe local — UNIQUEMENT pour le back-office Filament (personnel mairie).
 *
 * L'authentification des citoyens reste portée par Clerk (aucun mot de passe).
 * Cette colonne est nullable : seuls les comptes du personnel (agents/admins)
 * qui se connectent au panneau d'administration web en possèdent un.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
