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
        Schema::create('evisites', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('code', 64)->unique();
            $table->string('nom', 64);
            $table->string('prenoms', 64)->nullable();
            $table->string('mobile', 64)->nullable();
            $table->string('email', 64)->nullable();
            $table->string('motif_uuid')->nullable();
            $table->string('personne_visite')->nullable();
            $table->string('date_de_visite')->nullable();
            $table->string('nature_piece')->nullable();
            $table->string('num_piece')->nullable();
            $table->string('agence')->nullable();
            $table->LongText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evisites');
    }
};
