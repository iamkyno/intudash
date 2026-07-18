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
        Schema::table('sms_logs', function (Blueprint $table) {
            // Tracks the active status-polling fallback for when SMSPortal's
            // delivery-receipt webhook never arrives — see SmsService::pollPendingDeliveries().
            $table->unsignedTinyInteger('poll_attempts')->default(0)->after('failure_reason');
            $table->timestamp('last_polled_at')->nullable()->after('poll_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['poll_attempts', 'last_polled_at']);
        });
    }
};
