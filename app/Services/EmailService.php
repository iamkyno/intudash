<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailLog;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private ?SesClient $client = null;

    private function client(): SesClient
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = new SesClient([
            'version' => 'latest',
            'region'  => AppSetting::get('aws_region', 'us-east-1'),
            'credentials' => [
                'key'    => AppSetting::get('aws_key', ''),
                'secret' => AppSetting::get('aws_secret', ''),
            ],
        ]);
    }

    private function credentialsConfigured(): bool
    {
        return !empty(AppSetting::get('aws_key', ''))
            && !empty(AppSetting::get('aws_secret', ''));
    }

    public function sendCampaign(Campaign $campaign): array
    {
        if (!$this->credentialsConfigured()) {
            Log::error('SES credentials not configured', ['campaign_id' => $campaign->id]);
            return ['success' => false, 'error' => 'Amazon SES credentials are not configured in Settings.', 'sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $recipients = $campaign->validRecipients()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($recipients->isEmpty()) {
            return ['success' => false, 'error' => 'No valid recipients with email addresses', 'sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $fromName    = $campaign->email_from_name ?: AppSetting::get('ses_from_name', 'IntuDash');
        $fromAddress = $campaign->email_from_address ?: AppSetting::get('ses_from_email', '');
        $replyTo     = $campaign->email_reply_to ?: $fromAddress;

        if (!$fromAddress) {
            return ['success' => false, 'error' => 'No from address configured', 'sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $client = $this->client();
        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $recipient) {
            $log = EmailLog::create([
                'campaign_id'           => $campaign->id,
                'client_id'             => $campaign->client_id,
                'campaign_recipient_id' => $recipient->id,
                'recipient_email'       => $recipient->email,
                'subject'               => $campaign->email_subject,
                'provider'              => 'ses',
                'status'                => 'pending',
            ]);

            try {
                $body = $this->personalise($campaign->email_body, $recipient);

                $result = $client->sendEmail([
                    'Source' => "\"{$fromName}\" <{$fromAddress}>",
                    'Destination' => ['ToAddresses' => [$recipient->email]],
                    'ReplyToAddresses' => [$replyTo],
                    'Message' => [
                        'Subject' => ['Data' => $campaign->email_subject, 'Charset' => 'UTF-8'],
                        'Body' => [
                            'Html' => ['Data' => $body, 'Charset' => 'UTF-8'],
                            'Text' => ['Data' => strip_tags($body), 'Charset' => 'UTF-8'],
                        ],
                    ],
                ]);

                $log->update([
                    'status'              => 'sent',
                    'provider_message_id' => $result['MessageId'] ?? null,
                    'sent_at'             => now(),
                    'raw_response'        => ['MessageId' => $result['MessageId'] ?? null],
                ]);
                $sent++;
            } catch (AwsException $e) {
                $failed++;
                $errors[] = $e->getAwsErrorMessage();
                $log->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getAwsErrorMessage(),
                ]);
                Log::error('SES send failed', [
                    'campaign_id' => $campaign->id,
                    'recipient'   => $recipient->email,
                    'error'       => $e->getAwsErrorMessage(),
                ]);
            }
        }

        // Persist actual email cost/charge/profit (additive — SMS may have already written its share)
        $emailCost   = round($sent * (float) $campaign->internal_cost_per_email, 2);
        $emailCharge = round($sent * (float) $campaign->client_rate_per_email, 2);

        $campaign->update([
            'actual_email_recipients' => $sent,
            'actual_cost'    => round((float) $campaign->actual_cost + $emailCost, 2),
            'actual_charge'  => round((float) $campaign->actual_charge + $emailCharge, 2),
            'actual_profit'  => round((float) $campaign->actual_profit + ($emailCharge - $emailCost), 2),
        ]);

        return [
            'success' => $sent > 0,
            'sent'    => $sent,
            'failed'  => $failed,
            'total'   => $recipients->count(),
            'errors'  => array_values(array_unique($errors)),
        ];
    }

    /**
     * Send a single transactional email (used by the reminder engine).
     */
    public function sendOne(
        string $toEmail,
        string $subject,
        string $htmlBody,
        ?string $fromName = null,
        ?string $fromAddress = null,
        ?string $replyTo = null
    ): array {
        if (!$this->credentialsConfigured()) {
            return ['success' => false, 'message_id' => null, 'error' => 'SES credentials not configured'];
        }

        $fromName    = $fromName ?: AppSetting::get('ses_from_name', 'IntuDash');
        $fromAddress = $fromAddress ?: AppSetting::get('ses_from_email', '');
        $replyTo     = $replyTo ?: $fromAddress;

        if (!$fromAddress) {
            return ['success' => false, 'message_id' => null, 'error' => 'No from address configured'];
        }

        try {
            $result = $this->client()->sendEmail([
                'Source' => "\"{$fromName}\" <{$fromAddress}>",
                'Destination' => ['ToAddresses' => [$toEmail]],
                'ReplyToAddresses' => [$replyTo],
                'Message' => [
                    'Subject' => ['Data' => $subject, 'Charset' => 'UTF-8'],
                    'Body' => [
                        'Html' => ['Data' => $htmlBody, 'Charset' => 'UTF-8'],
                        'Text' => ['Data' => strip_tags($htmlBody), 'Charset' => 'UTF-8'],
                    ],
                ],
            ]);

            return ['success' => true, 'message_id' => $result['MessageId'] ?? null, 'error' => null];
        } catch (AwsException $e) {
            Log::error('SES sendOne failed', ['recipient' => $toEmail, 'error' => $e->getAwsErrorMessage()]);
            return ['success' => false, 'message_id' => null, 'error' => $e->getAwsErrorMessage()];
        }
    }

    public function processNotification(array $payload): void
    {
        // SES publishes bounce/complaint/delivery notifications via SNS.
        $type = $payload['notificationType'] ?? $payload['eventType'] ?? null;
        $mail = $payload['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            Log::warning('SES notification missing messageId', $payload);
            return;
        }

        $logs = EmailLog::where('provider_message_id', $messageId)->get();
        if ($logs->isEmpty()) {
            Log::warning('SES notification: no email log for message', ['message_id' => $messageId]);
            return;
        }

        [$status, $reason, $delivered] = match (strtolower((string) $type)) {
            'delivery'   => ['delivered', null, true],
            'bounce'     => ['bounced', $payload['bounce']['bounceType'] ?? 'bounce', false],
            'complaint'  => ['complained', $payload['complaint']['complaintFeedbackType'] ?? 'complaint', false],
            'reject'     => ['rejected', $payload['reject']['reason'] ?? 'rejected', false],
            default      => [null, null, false],
        };

        if (!$status) {
            return;
        }

        foreach ($logs as $log) {
            $log->update([
                'status'         => $status,
                'failure_reason' => $reason,
                'delivered_at'   => $delivered ? now() : $log->delivered_at,
                'raw_response'   => array_merge($log->raw_response ?? [], ['notification' => $payload]),
            ]);

            // Sync the originating reminder (email-only) from the SES outcome.
            if ($log->reminder_id && $log->reminder && $log->reminder->channel === 'email') {
                if ($status === 'delivered') {
                    $log->reminder->update(['status' => 'sent']);
                } elseif (in_array($status, ['bounced', 'complained', 'rejected'])) {
                    $log->reminder->update(['status' => 'failed', 'failure_reason' => "Email {$status}"]);
                }
            }
        }
    }

    private function personalise(string $body, CampaignRecipient $recipient): string
    {
        return str_replace(
            ['{{name}}', '{{email}}', '{{ name }}', '{{ email }}'],
            [$recipient->name ?? '', $recipient->email ?? '', $recipient->name ?? '', $recipient->email ?? ''],
            $body
        );
    }

    public static function getCostPerEmail(): float
    {
        return (float) AppSetting::get('internal_cost_per_email', '0.000100');
    }

    public static function getClientRatePerEmail(): float
    {
        return (float) AppSetting::get('default_client_rate_per_email', '0.000300');
    }
}
