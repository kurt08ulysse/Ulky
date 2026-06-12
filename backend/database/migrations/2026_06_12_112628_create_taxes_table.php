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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Montants en centimes pour éviter les approximations de float (ex: 500000 = 5000 FCFA)
            $table->unsignedInteger('base_amount');
            $table->unsignedInteger('stamp_amount')->default(0);

            $table->enum('periodicity', ['monthly', 'quarterly', 'yearly', 'one_time'])->default('one_time');

            // Rattachement optionnel à une commune (préparation multi-communes phase 2)
            $table->unsignedBigInteger('commune_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
