<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // No schema changes needed — vat_registered stored in app_settings
        // campaign_group_id added to campaigns for repeat linking
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_group_id')->nullable()->after('id');
            $table->unsignedTinyInteger('campaign_group_run')->nullable()->after('campaign_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['campaign_group_id', 'campaign_group_run']);
        });
    }
};
