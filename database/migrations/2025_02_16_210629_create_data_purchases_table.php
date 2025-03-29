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
        Schema::create('data_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('phone_number'); // Phone number for the data purchase
            $table->string('data_plan_id'); // ID of the data plan (from the API)
            $table->string('network_id'); // Network ID (mtn, glo, airtel, etisalat)
            $table->decimal('amount', 10, 2); // Amount paid for the data plan
            $table->string('status')->default('PENDING'); // Purchase status (PENDING, SUCCESS, FAILED)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_purchases');
    }
};
