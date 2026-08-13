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
        Schema::table('evisites', function (Blueprint $table) {
            $table->renameColumn('agence', 'code_agence');
        });
    }

    /**
     * Reverse the migrations.
     */
     public function down(): void
    {
        Schema::table('evisites', function (Blueprint $table) {
            $table->renameColumn('code_agence', 'agence');
        });
    }
};
