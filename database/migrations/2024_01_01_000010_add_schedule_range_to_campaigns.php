<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('scheduled_end_at')->nullable()->after('scheduled_at');
            $table->boolean('is_recurring_schedule')->default(false)->after('scheduled_end_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['scheduled_end_at', 'is_recurring_schedule']);
        });
    }
};
