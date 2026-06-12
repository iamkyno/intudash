<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\SmsLog;
use App\Services\Providers\SmsProviderInterface;
use App\Services\Providers\SmsPortalProvider;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private SmsProviderInterface $provider;

    public function __construct(?SmsProviderInterface $provider = null)
    {
        $this->provider = $provider ?? new SmsPortalProvider();
    }

    public function sendCampaign(Campaign $campaign): array
    {
        $recipients = $campaign->validRecipients()->get();

        if ($recipients->isEmpty()) {
            return ['success' => false, 'error' => 'No valid recipients'];
        }

        $campaign->update(['status' => 'sending', 'sent_at' => now()]);

        $recipientData = $recipients->map(fn($r) => [
            'id' => $r->id,
            'phone' => $r->phone_normalized,
            'name' => $r->name,
        ])->toArray();

        $result = $this->provider->sendBulk(
            $recipientData,
            $campaign->message,
            $campaign->sender_name
        );

        if ($result['success']) {
            $this->processSuccessfulSend($campaign, $recipients, $result);
        } else {
            $campaign->update(['status' => 'failed']);
            Log::error('Campaign send failed', [
                'campaign_id' => $campaign->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }

        return $result;
    }

    private function processSuccessfulSend(Campaign $campaign, $recipients, array $result): void
    {
        $messageResults = collect($result['results'] ?? []);
        $eventId = $result['event_id'] ?? null;

        foreach ($recipients as $recipient) {
            $messageResult = $messageResults->firstWhere('destination', $recipient->phone_normalized);

            SmsLog::create([
                'campaign_id' => $campaign->id,
                'client_id' => $campaign->client_id,
                'campaign_recipient_id' => $recipient->id,
                'recipient_number' => $recipient->phone_normalized,
                'message' => $campaign->message,
                'sms_segments' => $campaign->sms_segments,
                'provider' => 'smsportal',
                'provider_message_id' => $messageResult['messageId'] ?? null,
                'provider_event_id' => $eventId,
                'status' => 'submitted',
                'sent_at' => now(),
                'raw_response' => $messageResult,
            ]);
        }

        $actualRecipients = $recipients->count();
        $campaign->update([
            'status' => 'sending',
            'actual_recipients' => $actualRecipients,
            'actual_cost' => round($actualRecipients * $campaign->sms_segments * $campaign->internal_cost_per_sms, 2),
            'actual_charge' => round($actualRecipients * $campaign->sms_segments * $campaign->client_rate_per_sms, 2),
            'actual_profit' => round(
                ($actualRecipients * $campaign->sms_segments * $campaign->client_rate_per_sms) -
                ($actualRecipients * $campaign->sms_segments * $campaign->internal_cost_per_sms), 2
            ),
            'provider_campaign_id' => $eventId,
            'provider_response' => $result['raw'] ?? null,
        ]);
    }

    public function processDeliveryReceipt(array $payload): void
    {
        $messageId = $payload['messageId'] ?? $payload['MessageId'] ?? null;
        $statusCode = $payload['statusCode'] ?? $payload['StatusCode'] ?? null;

        if (!$messageId) {
            Log::warning('SMSPortal webhook missing messageId', $payload);
            return;
        }

        $log = SmsLog::where('provider_message_id', $messageId)->first();

        if (!$log) {
            Log::warning('SMSPortal webhook: no log found for message', ['message_id' => $messageId]);
            return;
        }

        $status = $this->mapDeliveryStatus($statusCode);

        $log->update([
            'status' => $status,
            'delivered_at' => in_array($status, ['delivered']) ? now() : null,
            'failure_reason' => in_array($status, ['undelivered', 'expired', 'blacklisted', 'no_route', 'failed'])
                ? ($payload['failureReason'] ?? $statusCode)
                : null,
            'raw_response' => array_merge($log->raw_response ?? [], ['delivery_receipt' => $payload]),
        ]);

        $this->updateCampaignStatus($log->campaign);
    }

    private function mapDeliveryStatus(string $code): string
    {
        return match (strtoupper($code)) {
            'DELIVRD' => 'delivered',
            'UNDELIV' => 'undelivered',
            'EXPIRED' => 'expired',
            'BLIST' => 'blacklisted',
            'SUBMITD' => 'submitted',
            'STAGED' => 'staged',
            'CANCELLED' => 'cancelled',
            'NOROUTE' => 'no_route',
            default => 'failed',
        };
    }

    private function updateCampaignStatus(Campaign $campaign): void
    {
        // Only transition if campaign is in an active sending state
        if (!in_array($campaign->status, ['sending', 'paused'])) {
            return;
        }

        $logs = $campaign->smsLogs();
        $delivered  = $logs->where('status', 'delivered')->count();
        $failed     = $logs->whereIn('status', ['undelivered', 'expired', 'failed', 'no_route', 'blacklisted', 'cancelled'])->count();
        $pending    = $logs->whereIn('status', ['pending', 'submitted', 'staged'])->count();

        // Wait until every log has a final status before marking complete
        if ($pending > 0) {
            return;
        }

        $campaign->update(['completed_at' => now()]);

        if ($delivered > 0 && $failed === 0) {
            $campaign->update(['status' => 'completed']);
        } elseif ($delivered > 0) {
            $campaign->update(['status' => 'partially_completed']);
        } else {
            $campaign->update(['status' => 'failed']);
        }
    }
}
