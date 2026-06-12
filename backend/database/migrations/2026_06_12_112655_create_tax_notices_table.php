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
        Schema::create('tax_notices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tax_id')->constrained('taxes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // On fige les montants lors de la génération de l'avis
            $table->unsignedInteger('base_amount');
            $table->unsignedInteger('stamp_amount')->default(0);

            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();

            $table->unsignedBigInteger('commune_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_notices');
    }
};
