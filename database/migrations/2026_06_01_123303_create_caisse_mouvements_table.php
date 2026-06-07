<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql2')->create('caisse_mouvements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->enum('type', ['APPROVISIONNEMENT', 'RAPATRIEMENT']);
            $table->enum('statut', ['EN_ATTENTE', 'EN_TRANSIT', 'RECU', 'ANNULE'])->default('EN_ATTENTE');
            
            // Caisses source et destination
            $table->uuid('caisse_source_uuid');
            $table->uuid('caisse_destination_uuid');
            
            // Montants
            $table->decimal('montant_envoye', 15, 2);
            $table->decimal('frais', 15, 2)->default(0);
            $table->decimal('montant_recu', 15, 2)->nullable();
            
            // Dates
            $table->timestamp('date_envoi')->nullable();
            $table->timestamp('date_reception')->nullable();
            
            // Utilisateurs
            $table->uuid('envoye_par')->nullable();
            $table->uuid('recu_par')->nullable();
            
            // Confirmation
            $table->boolean('confirmation_recu')->default(false);
            $table->text('justification_annulation')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('caisse_source_uuid');
            $table->index('caisse_destination_uuid');
            $table->index('statut');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql2')->dropIfExists('caisse_mouvements');
    }
};