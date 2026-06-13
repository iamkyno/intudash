<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->change();
            $table->string('source')->default('campaign')->after('id');
            $table->foreignId('reminder_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->change();
            $table->string('source')->default('campaign')->after('id');
            $table->foreignId('reminder_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reminder_id');
            $table->dropColumn('source');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reminder_id');
            $table->dropColumn('source');
        });
    }
};
