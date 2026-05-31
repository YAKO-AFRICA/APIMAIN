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
        Schema::connection('mysql2')->table('caisses', function (Blueprint $table) {
            $table->decimal('solde_theorique', 15, 2)->default(0);
            $table->decimal('solde_physique', 15, 2)->default(0);
            $table->timestamp('last_transaction_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::connection('mysql2')->table('caisses', function (Blueprint $table) {
            $table->dropColumn(['solde_theorique', 'solde_physique', 'last_transaction_at']);
        });
    }
};
