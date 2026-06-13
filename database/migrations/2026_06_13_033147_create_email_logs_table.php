<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_recipient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('provider')->default('ses');
            $table->string('provider_message_id')->nullable();
            $table->enum('status', [
                'pending',
                'sent',
                'delivered',
                'bounced',
                'complained',
                'rejected',
                'failed',
            ])->default('pending');
            $table->string('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
