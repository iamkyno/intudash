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

        $dispatched = 0;

        foreach ($campaigns as $campaign) {
            // Atomically claim the campaign: only the process that flips
            // 'scheduled' → 'sending' gets to dispatch it. Prevents double-send
            // if a run overlaps the next minute.
            $claimed = Campaign::where('id', $campaign->id)
                ->where('status', 'scheduled')
                ->update(['status' => 'sending']);

            if (!$claimed) {
                continue;
            }

            Log::info('Dispatching scheduled campaign', ['id' => $campaign->id]);
            SendCampaignJob::dispatch($campaign->fresh());
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} campaign(s).");
    }
}
