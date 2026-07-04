<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Scheduler runs every minute: WHERE status='scheduled' AND scheduled_at <= now
            $table->index(['status', 'scheduled_at'], 'campaigns_status_scheduled_at_index');
            // Dashboard + list filter by status alone
            $table->index('status', 'campaigns_status_index');
            // notArchived() scope on the campaign index list
            $table->index('archived_at', 'campaigns_archived_at_index');
            // Sibling-run navigation on the show page
            $table->index('campaign_group_id', 'campaigns_campaign_group_id_index');
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            // Dedup lookups during CSV / DB import: WHERE campaign_id=? AND phone_normalized=?
            $table->index(['campaign_id', 'phone_normalized'], 'recipients_campaign_phone_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Dashboard revenue/outstanding sums group by status
            $table->index('status', 'invoices_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_status_scheduled_at_index');
            $table->dropIndex('campaigns_status_index');
            $table->dropIndex('campaigns_archived_at_index');
            $table->dropIndex('campaigns_campaign_group_id_index');
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropIndex('recipients_campaign_phone_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_status_index');
        });
    }
};
