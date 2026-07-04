<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\EmailService;
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

    /** Space out retries (seconds) so a provider hiccup isn't hammered. */
    public array $backoff = [30, 120, 300];

    public function __construct(public Campaign $campaign) {}

    public function handle(SmsService $smsService, EmailService $emailService): void
    {
        Log::info('SendCampaignJob started', [
            'campaign_id' => $this->campaign->id,
            'type'        => $this->campaign->campaign_type,
        ]);

        if (!$this->campaign->canBeScheduled()) {
            Log::warning('Campaign not ready to send', ['campaign_id' => $this->campaign->id]);
            return;
        }

        $type = $this->campaign->campaign_type ?? 'sms';
        $smsResult   = null;
        $emailResult = null;

        if (in_array($type, ['sms', 'both'])) {
            $smsResult = $smsService->sendCampaign($this->campaign);
        }

        if (in_array($type, ['email', 'both'])) {
            $emailResult = $emailService->sendCampaign($this->campaign);
        }

        // SmsService manages its own status transition via delivery receipts.
        // For email-only campaigns nothing else updates status, so finalise here.
        if ($type === 'email') {
            $this->finaliseEmailOnly($emailResult);
        }

        Log::info('SendCampaignJob completed', [
            'campaign_id' => $this->campaign->id,
            'sms'         => $smsResult['success'] ?? null,
            'email'       => $emailResult['success'] ?? null,
        ]);
    }

    private function finaliseEmailOnly(?array $emailResult): void
    {
        if (!$emailResult) {
            return;
        }

        // Interim status from the send result; SES bounce/complaint/delivery
        // notifications refine per-recipient EmailLog state afterwards.
        if (($emailResult['sent'] ?? 0) > 0 && ($emailResult['failed'] ?? 0) === 0) {
            $this->campaign->update(['status' => 'completed', 'completed_at' => now()]);
        } elseif (($emailResult['sent'] ?? 0) > 0) {
            $this->campaign->update(['status' => 'partially_completed', 'completed_at' => now()]);
        } else {
            $this->campaign->update(['status' => 'failed']);
        }
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
