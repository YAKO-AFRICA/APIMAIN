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
        Schema::connection('mysql2')->create('rapports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->uuid('uuid')->unique();
            $table->date('date_rapport');
            $table->decimal('total_entrees', 15, 2)->default(0);
            $table->decimal('total_sorties', 15, 2)->default(0);
            $table->decimal('solde', 15, 2)->default(0);
            $table->text('observations')->nullable();
            $table->boolean('isActive')->default(true);
            $table->string('user_id')->nullable();

            $table->string('delected_at')->nullable();
            $table->string('delected_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql2')->dropIfExists('rapports');
    }
};

