<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\SmsLog;
use App\Services\Providers\SmsProviderInterface;
use App\Services\Providers\SmsPortalProvider;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private SmsProviderInterface $provider;

    /** Give up actively polling a message after this many checks — it stays 'submitted' but stops blocking the campaign. */
    private const MAX_POLL_ATTEMPTS = 5;

    /** Minutes to wait before each successive poll attempt (indexed by current attempt count). */
    private const POLL_BACKOFF_MINUTES = [2, 5, 10, 20, 30];

    public function __construct(?SmsProviderInterface $provider = null)
    {
        $this->provider = $provider ?? new SmsPortalProvider();
    }

    private function credentialsConfigured(): bool
    {
        return !empty(AppSetting::get('smsportal_client_id', ''))
            && !empty(AppSetting::get('smsportal_api_secret', ''));
    }

    /**
     * Send a single transactional SMS (used by the reminder engine).
     */
    public function sendOne(string $phone, string $message, ?string $sender = null): array
    {
        if (!$this->credentialsConfigured()) {
            return ['success' => false, 'message_id' => null, 'error' => 'SMSPortal credentials are not configured in Settings.'];
        }

        $result = $this->provider->sendBulk(
            [['phone' => $phone]],
            $message,
            $sender
        );

        $messageId = null;
        if (!empty($result['results'][0]['messageId'])) {
            $messageId = $result['results'][0]['messageId'];
        }

        return [
            'success'    => $result['success'] ?? false,
            'message_id' => $messageId,
            'error'      => $result['error'] ?? null,
        ];
    }

    public function sendCampaign(Campaign $campaign): array
    {
        if (!$this->credentialsConfigured()) {
            Log::error('SMSPortal credentials not configured', ['campaign_id' => $campaign->id]);
            $this->markFailed($campaign, 'SMSPortal credentials are not configured in Settings.');
            return ['success' => false, 'error' => 'SMSPortal credentials are not configured in Settings.'];
        }

        $recipients = $campaign->validRecipients()->get();

        if ($recipients->isEmpty()) {
            $this->markFailed($campaign, 'No valid recipients');
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

    /**
     * Give up on a campaign that never reached the provider (missing credentials,
     * no recipients). Callers always flip status to 'sending' before dispatching
     * the send job, so without this the campaign would hang there forever.
     */
    private function markFailed(Campaign $campaign, string $reason): void
    {
        if (!in_array($campaign->status, ['sending', 'paused'])) {
            return;
        }

        $campaign->update([
            'status'            => 'failed',
            'provider_response' => ['error' => $reason],
        ]);
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

        if ($log->campaign_id && $log->campaign) {
            $this->updateCampaignStatus($log->campaign);
        } elseif ($log->reminder_id && $log->reminder) {
            $this->updateReminderStatus($log->reminder, $status);
        }
    }

    private function updateReminderStatus(\App\Models\Reminder $reminder, string $status): void
    {
        if (in_array($status, ['delivered'])) {
            $reminder->update(['status' => 'sent']);
        } elseif (in_array($status, ['undelivered', 'expired', 'blacklisted', 'no_route', 'failed', 'cancelled'])) {
            // For a 'both' reminder, don't override a successful email leg.
            if ($reminder->channel === 'sms') {
                $reminder->update(['status' => 'failed', 'failure_reason' => "SMS {$status}"]);
            }
        }
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

    /**
     * Actively check SMSPortal's status API for any message still awaiting a
     * delivery-receipt webhook — a fallback for when the webhook never arrives
     * (unreachable URL, secret mismatch, provider outage, etc). Runs the whole
     * queue of due messages; one failure never blocks the rest. Backs off
     * between attempts per message and gives up after MAX_POLL_ATTEMPTS so a
     * permanently-unresolvable message can't keep its campaign stuck forever.
     */
    public function pollPendingDeliveries(): array
    {
        $stats = ['checked' => 0, 'resolved' => 0, 'gave_up' => 0, 'errors' => 0];

        $candidates = SmsLog::whereIn('status', ['pending', 'submitted', 'staged'])
            ->whereNotNull('provider_message_id')
            ->where('poll_attempts', '<', self::MAX_POLL_ATTEMPTS)
            ->where(function ($q) {
                $q->whereNull('last_polled_at')->orWhere('last_polled_at', '<=', now()->subMinutes(2));
            })
            ->get()
            ->filter(fn ($log) => $this->isDueForPoll($log));

        $affectedCampaignIds = [];

        foreach ($candidates as $log) {
            $stats['checked']++;

            try {
                $result = $this->provider->getDeliveryStatus($log->provider_message_id);
                $resolvedStatus = ($result['success'] ?? false) ? $this->extractStatusFromPoll($result['data'] ?? []) : null;

                if ($resolvedStatus) {
                    $log->update([
                        'status'         => $resolvedStatus,
                        'delivered_at'   => $resolvedStatus === 'delivered' ? now() : null,
                        'failure_reason' => in_array($resolvedStatus, ['undelivered', 'expired', 'blacklisted', 'no_route', 'failed'])
                            ? 'Resolved via status poll — no webhook receipt arrived.'
                            : null,
                        'poll_attempts'  => $log->poll_attempts + 1,
                        'last_polled_at' => now(),
                        'raw_response'   => array_merge($log->raw_response ?? [], ['status_poll' => $result['data'] ?? null]),
                    ]);
                    $stats['resolved']++;
                } else {
                    $newAttempts = $log->poll_attempts + 1;
                    $log->update(['poll_attempts' => $newAttempts, 'last_polled_at' => now()]);
                    if ($newAttempts >= self::MAX_POLL_ATTEMPTS) {
                        $stats['gave_up']++;
                        Log::warning('Gave up polling SMS delivery status — no webhook or poll ever confirmed it', [
                            'sms_log_id' => $log->id,
                            'message_id' => $log->provider_message_id,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // One message failing to check never blocks the rest of the queue.
                $stats['errors']++;
                Log::warning('SMS delivery status poll failed, will retry', ['sms_log_id' => $log->id, 'error' => $e->getMessage()]);
                $log->update(['poll_attempts' => $log->poll_attempts + 1, 'last_polled_at' => now()]);
            }

            if ($log->campaign_id) {
                $affectedCampaignIds[$log->campaign_id] = true;
            }
        }

        foreach (array_keys($affectedCampaignIds) as $campaignId) {
            $campaign = Campaign::find($campaignId);
            if ($campaign) {
                $this->updateCampaignStatus($campaign);
            }
        }

        return $stats;
    }

    private function isDueForPoll(SmsLog $log): bool
    {
        if (!$log->last_polled_at) {
            // First check — give the webhook a couple minutes' head start before polling.
            return $log->sent_at && $log->sent_at->lte(now()->subMinutes(2));
        }

        $schedule = self::POLL_BACKOFF_MINUTES;
        $backoff = $schedule[$log->poll_attempts] ?? end($schedule);

        return $log->last_polled_at->lte(now()->subMinutes($backoff));
    }

    /**
     * Only treat a poll result as resolving the message if it's a terminal
     * outcome — a re-confirmed 'submitted'/'staged' just means still in
     * flight, so keep polling rather than "resolving" to a non-answer.
     */
    private function extractStatusFromPoll(array $data): ?string
    {
        $code = $data['statusCode'] ?? $data['status'] ?? $data['deliveryStatus'] ?? null;
        if (!$code) {
            return null;
        }

        $mapped = $this->mapDeliveryStatus((string) $code);

        return in_array($mapped, ['pending', 'submitted', 'staged']) ? null : $mapped;
    }

    private function updateCampaignStatus(?Campaign $campaign): void
    {
        // Only transition if campaign is in an active sending state
        if (!$campaign || !in_array($campaign->status, ['sending', 'paused'])) {
            return;
        }

        // Note: $campaign->smsLogs() returns a mutable relation query builder —
        // calling ->where()->count() on the SAME instance repeatedly accumulates
        // every prior where() clause (Eloquent builders mutate and return $this),
        // silently ANDing unrelated conditions together. Always start a fresh
        // query per count (a single grouped query here, both correct and cheaper).
        $counts = $campaign->smsLogs()
            ->selectRaw("
                sum(case when status = 'delivered' then 1 else 0 end) as delivered,
                sum(case when status in ('undelivered','expired','failed','no_route','blacklisted','cancelled') then 1 else 0 end) as failed,
                sum(case when status in ('pending','submitted','staged') and poll_attempts < ? then 1 else 0 end) as still_waiting,
                sum(case when status in ('pending','submitted','staged') and poll_attempts >= ? then 1 else 0 end) as unconfirmed
            ", [self::MAX_POLL_ATTEMPTS, self::MAX_POLL_ATTEMPTS])
            ->first();

        $delivered    = (int) $counts->delivered;
        $failed       = (int) $counts->failed;
        $stillWaiting = (int) $counts->still_waiting;
        $unconfirmed  = (int) $counts->unconfirmed;

        // Still within their retry budget — give them more time before deciding anything.
        if ($stillWaiting > 0) {
            return;
        }

        $campaign->update(['completed_at' => now()]);

        if ($delivered > 0 && $failed === 0 && $unconfirmed === 0) {
            $campaign->update(['status' => 'completed']);
        } elseif ($delivered > 0 || $unconfirmed > 0) {
            $campaign->update(['status' => 'partially_completed']);
        } else {
            $campaign->update(['status' => 'failed']);
        }
    }
}
