<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reminder_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_slug');

            $table->string('recipient_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_normalized')->nullable();
            $table->string('email')->nullable();
            $table->json('data')->nullable(); // arbitrary merge fields for templating

            $table->enum('channel', ['sms', 'email', 'both'])->default('sms');
            $table->timestamp('send_at');
            $table->enum('status', ['pending', 'sent', 'partially_sent', 'failed', 'cancelled'])->default('pending');
            $table->enum('source', ['api', 'csv', 'manual'])->default('manual');

            $table->string('sms_provider_message_id')->nullable();
            $table->string('email_provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('failure_reason')->nullable();

            $table->timestamps();

            $table->index(['status', 'send_at']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
