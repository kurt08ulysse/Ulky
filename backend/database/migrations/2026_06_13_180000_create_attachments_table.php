<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pièces jointes polymorphes (photos / documents).
 *
 * Rattachables à un signalement (CitizenReport) ou une démarche
 * (AdministrativeRequest). Stockées sur le disque public (storage:link).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');                 // attachable_type + attachable_id (indexés)
            $table->string('path');                        // chemin sur le disque public
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->default(0);   // octets
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
