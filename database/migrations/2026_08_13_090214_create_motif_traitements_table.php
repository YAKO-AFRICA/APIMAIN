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
        Schema::create('motif_traitements', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('code', 64)->unique();
            $table->string('libelle', 255);
            $table->string('systeme_used')->nullable();
            $table->text('description')->nullable();
            $table->string('etat', 255)->default('actif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motif_traitements');
    }
};
