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
        Schema::create('e_suggestions', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('code', 64)->unique();
            
            $table->string('uuid_qrcode', 64)->nullable();
            $table->integer('note')->nullable();
            $table->string('uuid_category', 255)->nullable();
            $table->longText('comment')->nullable();
            $table->string('nom_client', 255)->nullable();
            $table->string('prenom_client', 255)->nullable();
            $table->string('tel_client', 255)->nullable();
            $table->string('email_client', 255)->nullable();
            $table->string('statut', 255)->nullable(); //  (new,analyzed, in_progress, traited, rejected, closed)

            $table->string('etat', 255)->nullable(); // (actif, inactif)
            $table->string('deleted_at', 255)->nullable();
            $table->string('deleted_by', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_suggestions');
    }
};
