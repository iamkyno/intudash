<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('campaign_type', ['sms', 'email', 'both'])->default('sms')->after('name');

            // Email campaign fields
            $table->string('email_subject')->nullable()->after('campaign_type');
            $table->string('email_from_name')->nullable()->after('email_subject');
            $table->string('email_from_address')->nullable()->after('email_from_name');
            $table->string('email_reply_to')->nullable()->after('email_from_address');
            $table->longText('email_body')->nullable()->after('email_reply_to');

            // Email costing
            $table->decimal('internal_cost_per_email', 10, 6)->default(0)->after('internal_cost_per_sms');
            $table->decimal('client_rate_per_email', 10, 6)->default(0)->after('client_rate_per_sms');
            $table->unsignedInteger('estimated_email_recipients')->default(0)->after('estimated_recipients');
            $table->unsignedInteger('actual_email_recipients')->default(0)->after('actual_recipients');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_type', 'email_subject', 'email_from_name', 'email_from_address',
                'email_reply_to', 'email_body', 'internal_cost_per_email', 'client_rate_per_email',
                'estimated_email_recipients', 'actual_email_recipients',
            ]);
        });
    }
};
