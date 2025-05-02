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
        Schema::create('leaners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('age');
            $table->enum('sex', ['male', 'female', 'other']);
            $table->string('country');
            $table->string('state');
            $table->string('course_of_study');
            $table->boolean('is_student');
            $table->integer('amount_paid')->default(0);
            $table->string('email');
            $table->string('whatsapp');
            $table->string('payment_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaners');
    }
};
