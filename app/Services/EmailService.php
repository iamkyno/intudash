<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private function client(): SesClient
    {
        return new SesClient([
            'version' => 'latest',
            'region'  => AppSetting::get('aws_region', 'us-east-1'),
            'credentials' => [
                'key'    => AppSetting::get('aws_key', ''),
                'secret' => AppSetting::get('aws_secret', ''),
            ],
        ]);
    }

    public function sendCampaign(Campaign $campaign): array
    {
        $recipients = $campaign->validRecipients()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($recipients->isEmpty()) {
            return ['success' => false, 'error' => 'No valid recipients with email addresses'];
        }

        $fromName    = $campaign->email_from_name ?: AppSetting::get('ses_from_name', 'IntuDash');
        $fromAddress = $campaign->email_from_address ?: AppSetting::get('ses_from_email', '');
        $replyTo     = $campaign->email_reply_to ?: $fromAddress;

        if (!$fromAddress) {
            return ['success' => false, 'error' => 'No from address configured'];
        }

        $client  = $this->client();
        $sent    = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($recipients as $recipient) {
            try {
                $body = $this->personalise($campaign->email_body, $recipient);

                $client->sendEmail([
                    'Source' => "\"{$fromName}\" <{$fromAddress}>",
                    'Destination' => [
                        'ToAddresses' => [$recipient->email],
                    ],
                    'ReplyToAddresses' => [$replyTo],
                    'Message' => [
                        'Subject' => [
                            'Data'    => $campaign->email_subject,
                            'Charset' => 'UTF-8',
                        ],
                        'Body' => [
                            'Html' => [
                                'Data'    => $body,
                                'Charset' => 'UTF-8',
                            ],
                            'Text' => [
                                'Data'    => strip_tags($body),
                                'Charset' => 'UTF-8',
                            ],
                        ],
                    ],
                ]);

                $sent++;
            } catch (AwsException $e) {
                $failed++;
                $errors[] = $e->getAwsErrorMessage();
                Log::error('SES send failed', [
                    'campaign_id' => $campaign->id,
                    'recipient'   => $recipient->email,
                    'error'       => $e->getAwsErrorMessage(),
                ]);
            }
        }

        $campaign->update(['actual_email_recipients' => $sent]);

        return [
            'success'    => $sent > 0,
            'sent'       => $sent,
            'failed'     => $failed,
            'total'      => $recipients->count(),
            'errors'     => array_unique($errors),
        ];
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
