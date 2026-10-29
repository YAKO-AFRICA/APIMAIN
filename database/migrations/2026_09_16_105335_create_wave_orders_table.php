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
        Schema::create('wave_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();          // YAKO-xxx
            $table->string('wave_session_id')->nullable(); // cos-xxx
            $table->integer('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status')->default('pending'); // pending, succeeded, processing, failed
            $table->json('wave_response')->nullable();    // réponse brute de Wave
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('wave_session_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wave_orders');
    }
};
