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
        Schema::connection('mysql2')->table('rapport_operations', function (Blueprint $table) {
            $table->string('type_category')->after('type_operation_uuid')->nullable();
            $table->string('type_mouvement')->after('type_category')->nullable();

            $table->integer('nb_agents_terrain')->after('client_a_paye')->default(0);
            $table->integer('nb_agents_commerciaux')->after('nb_agents_terrain')->default(0);
            $table->integer('nb_souscriptions_hors_agence')->after('nb_agents_commerciaux')->default(0);
            $table->integer('nb_souscriptions_en_agence')->after('nb_souscriptions_hors_agence')->default(0);
            $table->integer('nb_souscriptions')->after('nb_souscriptions_en_agence')->default(0);
            //trafiq
            $table->integer('nb_personnes')->after('nb_souscriptions')->default(0);
            $table->integer('taux_satisfaction')->after('nb_personnes')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql2')->table('rapport_operations', function (Blueprint $table) {
            //
        });
    }
};
