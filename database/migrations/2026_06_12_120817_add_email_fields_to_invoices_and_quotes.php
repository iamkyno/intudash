<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('email_quantity')->default(0)->after('sms_quantity');
            $table->decimal('email_rate', 10, 6)->default(0)->after('sms_rate');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedInteger('email_quantity')->default(0)->after('sms_quantity');
            $table->decimal('email_rate', 10, 6)->default(0)->after('sms_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['email_quantity', 'email_rate']);
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['email_quantity', 'email_rate']);
        });
    }
};
