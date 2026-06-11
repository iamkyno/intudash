<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(public Campaign $campaign) {}

    public function handle(SmsService $smsService): void
    {
        Log::info('SendCampaignJob started', ['campaign_id' => $this->campaign->id]);

        if (!$this->campaign->canBeScheduled()) {
            Log::warning('Campaign not ready to send', ['campaign_id' => $this->campaign->id]);
            return;
        }

        $result = $smsService->sendCampaign($this->campaign);

        Log::info('SendCampaignJob completed', [
            'campaign_id' => $this->campaign->id,
            'success' => $result['success'],
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendCampaignJob failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $e->getMessage(),
        ]);

        $this->campaign->update(['status' => 'failed']);
    }
}
