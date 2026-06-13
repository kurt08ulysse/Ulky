<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numéro de commerçant — attribué par la mairie quand une personne devient
 * commerçant (rôle 'merchant'), typiquement à l'allocation d'un emplacement.
 *
 * Le commerçant n'est PAS un compte séparé : c'est le même utilisateur (un seul
 * clerk_id / email) avec le rôle 'merchant' et ce numéro. Aucune collision d'email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('merchant_number')->nullable()->unique()->after('taxpayer_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('merchant_number');
        });
    }
};
