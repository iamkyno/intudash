<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('message');
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft',
                'recipients_uploaded',
                'invoice_generated',
                'awaiting_payment',
                'ready_to_schedule',
                'scheduled',
                'sending',
                'completed',
                'partially_completed',
                'failed',
                'cancelled',
            ])->default('draft');
            $table->decimal('internal_cost_per_sms', 8, 4)->default(0.12);
            $table->decimal('client_rate_per_sms', 8, 4)->default(0.25);
            $table->integer('estimated_recipients')->default(0);
            $table->integer('actual_recipients')->default(0);
            $table->integer('sms_segments')->default(1);
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('estimated_charge', 10, 2)->default(0);
            $table->decimal('estimated_profit', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->decimal('actual_charge', 10, 2)->default(0);
            $table->decimal('actual_profit', 10, 2)->default(0);
            $table->string('sender_name')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('provider')->default('smsportal');
            $table->string('provider_campaign_id')->nullable();
            $table->boolean('admin_override_payment')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
