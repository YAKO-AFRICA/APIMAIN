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
        Schema::connection('mysql2')->create('rapport_operations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->uuid('uuid')->unique();
            $table->string('rapport_uuid')->nullable();
            $table->string('type_operation_uuid')->nullable();
            $table->integer('quantite')->default(1);
            $table->decimal('montant_unitaire', 15, 2);
            $table->decimal('montant_total', 15, 2);
            $table->string('nature')->nullable(); //    'entree' ou 'sortie'
            $table->string('produit_assurance')->nullable();
            $table->decimal('prime_souhaitee', 15, 2)->nullable();
            $table->string('code_contrat')->nullable();
            $table->boolean('client_a_paye')->default(false);
            $table->text('description')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::connection('mysql2')->dropIfExists('rapport_operations');
    }
};
