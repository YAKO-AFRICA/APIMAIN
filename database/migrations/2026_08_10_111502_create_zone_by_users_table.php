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
        Schema::create('zone_by_users', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('code')->unique();
            $table->string('libelle')->nullable();
            $table->string('responsable_uuid'); // Changé en uuid pour correspondre à users.idmembre
            $table->json('agence_codes')->nullable(); // Renommé pour plus de clarté
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_by_users');
    }
};
