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
        Schema::table('e_suggestions', function (Blueprint $table) {
             $table->renameColumn('agency_code', 'uuid_qrcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_suggestions', function (Blueprint $table) {
            $table->renameColumn('uuid_qrcode', 'agency_code');
        });
    }
};
