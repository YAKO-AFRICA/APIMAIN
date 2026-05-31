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
        Schema::connection('mysql2')->create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->enum('type', ['DEPOT', 'RETRAIT', 'ENVOI_MTO', 'RETRAIT_MTO']);
            $table->enum('sens', ['ENTREE', 'SORTIE']);
            $table->decimal('montant', 15, 2);
            $table->decimal('frais', 15, 2)->default(0);
            $table->decimal('montant_total', 15, 2);

            // Relations
            $table->uuid('caisse_uuid');
            $table->uuid('operator_uuid')->nullable();
            $table->uuid('user_uuid')->nullable(); // Utilisateur qui a fait la transaction
            $table->uuid('client_uuid')->nullable(); // Client concerné

            // Informations transaction
            $table->string('numero_telephone')->nullable();
            $table->string('numero_carte')->nullable();
            $table->string('reference_transaction')->nullable(); // Référence externe
            $table->string('beneficiaire_nom')->nullable();
            $table->string('beneficiaire_telephone')->nullable();
            $table->string('beneficiaire_pays')->nullable();

            // Statut
            $table->enum('statut', ['EN_ATTENTE', 'VALIDEE', 'ANNULEE', 'ECHOUE'])->default('EN_ATTENTE');

            // Justification pour annulation
            $table->text('justification_annulation')->nullable();

            // Traçabilité
            $table->uuid('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('caisse_uuid');
            $table->index('operator_uuid');
            $table->index('reference');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};



