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
        Schema::connection('mysql2')->create('caisses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('code')->unique();
            $table->string('libelle');

            // physique ou virtuelle
            $table->enum('type', ['physique', 'virtuelle'])->nullable();

            $table->decimal('solde', 18, 2)->default(0);
            $table->decimal('solde_alert', 18, 2)->default(0);

            $table->longText('description')->nullable();
            $table->boolean('isActive')->default(true);

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes(); // created_at, updated_at, deleted_at
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisses');
    }
};
