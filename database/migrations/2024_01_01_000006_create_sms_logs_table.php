<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_recipient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_number');
            $table->text('message');
            $table->integer('sms_segments')->default(1);
            $table->string('provider')->default('smsportal');
            $table->string('provider_message_id')->nullable();
            $table->string('provider_event_id')->nullable();
            $table->enum('status', [
                'pending',
                'submitted',
                'staged',
                'delivered',
                'undelivered',
                'expired',
                'blacklisted',
                'no_route',
                'failed',
                'cancelled',
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
        Schema::dropIfExists('sms_logs');
    }
};
