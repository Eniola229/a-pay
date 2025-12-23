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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); 
            $table->string('beneficiary');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('cash_back', 15, 2)->default(0);
            $table->decimal('charges', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->enum('status', ['SUCCESS','PENDING', 'ERROR']);
            $table->enum('status', ['CREDIT', 'DEBIT'])->nullable('nullable');
            $table->decimal('balance_before', 15, 2)->after('amount');
            $table->decimal('balance_after', 15, 2)->after('balance_before');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
