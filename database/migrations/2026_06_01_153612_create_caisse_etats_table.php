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
        Schema::connection('mysql2')->create('caisse_etats', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('caisse_uuid');
            $table->date('date_journee');
            
            // Statuts
            $table->enum('statut', ['OUVERTE', 'EN_COURS', 'FERMEE'])->default('OUVERTE');
            
            // Soldes
            $table->decimal('solde_initial', 15, 2)->default(0);
            $table->decimal('solde_theorique', 15, 2)->default(0);
            $table->decimal('solde_physique', 15, 2)->nullable();
            $table->decimal('ecart', 15, 2)->nullable();
            
            // Rapprochement
            $table->text('justification_ecart')->nullable();
            $table->timestamp('date_ouverture')->nullable();
            $table->timestamp('date_fermeture')->nullable();
            
            // Utilisateurs
            $table->uuid('ouverte_par')->nullable();
            $table->uuid('fermee_par')->nullable();
            $table->uuid('verrouille_par')->nullable();
            
            // Verrouillage
            $table->boolean('est_verrouille')->default(false);
            $table->timestamp('date_verrouillage')->nullable();
            $table->text('motif_verrouillage')->nullable();
            
            // Métadonnées
            $table->json('metadatas')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('caisse_uuid');
            $table->index('date_journee');
            $table->index('statut');
            $table->unique(['caisse_uuid', 'date_journee']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql2')->dropIfExists('caisse_etats');
    }
};
