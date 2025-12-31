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
    Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number'); // The person's number
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->text('message_body');
            $table->string('message_sid')->nullable(); // Twilio message ID
            $table->string('status')->default('sent'); // sent, delivered, failed, received
            $table->json('metadata')->nullable(); // Any additional data
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('phone_number');
            $table->index('direction');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
