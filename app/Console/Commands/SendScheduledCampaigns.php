<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:send-scheduled';
    protected $description = 'Dispatch sending jobs for campaigns that are due.';

    public function handle(): void
    {
        $campaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            Log::info('Dispatching scheduled campaign', ['id' => $campaign->id]);
            SendCampaignJob::dispatch($campaign);
            $campaign->update(['status' => 'sending']);
        }

        $this->info("Dispatched {$campaigns->count()} campaign(s).");
    }
}
