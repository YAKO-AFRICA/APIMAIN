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
        Schema::create('suggestion_treatments', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('code', 64)->unique();
            $table->uuid('uuid_suggestion')->nullable();
            $table->string('code_responsable')->nullable();
            $table->string('action')->nullable();
            $table->string('assigned_by')->nullable();
            $table->string('etat')->default('actif'); // inactif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_treatments');
    }
};
