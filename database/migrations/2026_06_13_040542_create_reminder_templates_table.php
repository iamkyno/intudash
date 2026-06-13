<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('channel', ['sms', 'email', 'both'])->default('sms');

            // SMS
            $table->text('sms_body')->nullable();
            $table->string('sender_name', 11)->nullable();

            // Email
            $table->string('email_subject')->nullable();
            $table->string('email_from_name')->nullable();
            $table->string('email_from_address')->nullable();
            $table->string('email_reply_to')->nullable();
            $table->longText('email_body')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['client_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_templates');
    }
};
