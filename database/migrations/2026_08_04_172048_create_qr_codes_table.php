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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('code', 64)->unique();
            $table->string('agency_code', 20)->nullable()->index();
            $table->string('link', 2048)->nullable(); // URL longue
            $table->string('etat', 20)->default('actif');
            $table->integer('scan_count')->default(0);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
