<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\WebhookEvent;
use App\Services\EmailService;
use App\Services\OptOutService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Verify a shared secret if one is configured. Accepts either an
     * X-Webhook-Secret header or a ?secret= query param (SMSPortal/SNS can be
     * configured with the secret embedded in the callback URL).
     */
    private function secretValid(Request $request): bool
    {
        $configured = AppSetting::get('webhook_secret', '');

        // No secret configured → allow (but warn) for backwards compatibility.
        if (empty($configured)) {
            Log::warning('Webhook received with no webhook_secret configured — endpoint is unauthenticated.');
            return true;
        }

        $provided = $request->header('X-Webhook-Secret') ?: $request->query('secret', '');

        return is_string($provided) && hash_equals($configured, $provided);
    }

    public function smsportal(Request $request, SmsService $smsService)
    {
        if (!$this->secretValid($request)) {
            Log::warning('SMSPortal webhook rejected: invalid secret', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->all();

        $event = WebhookEvent::create([
            'provider' => 'smsportal',
            'event_type' => 'delivery_receipt',
            'payload' => $payload,
            'status' => 'received',
        ]);

        try {
            $receipts = $payload['receipts'] ?? $payload['Receipts'] ?? [$payload];

            foreach ($receipts as $receipt) {
                // processDeliveryReceipt updates by message id and is idempotent.
                $smsService->processDeliveryReceipt($receipt);
            }

            $event->update(['status' => 'processed', 'processed_at' => now()]);

            Log::info('SMSPortal webhook processed', ['event_id' => $event->id, 'count' => count($receipts)]);
        } catch (\Exception $e) {
            $event->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::error('SMSPortal webhook error', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Inbound SMS replies from customers (STOP, etc). Records every reply and
     * opts the number out everywhere when it's a stop-style keyword.
     */
    public function smsReply(Request $request, OptOutService $optOutService)
    {
        if (!$this->secretValid($request)) {
            Log::warning('SMS reply webhook rejected: invalid secret', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->all();

        $event = WebhookEvent::create([
            'provider' => 'sms',
            'event_type' => 'inbound_reply',
            'payload' => $payload,
            'status' => 'received',
        ]);

        try {
            // Tolerate a batch of replies or a single one, across common field names.
            $replies = $payload['replies'] ?? $payload['Replies'] ?? [$payload];

            foreach ($replies as $reply) {
                $from = $reply['from'] ?? $reply['From'] ?? $reply['source'] ?? $reply['msisdn'] ?? '';
                $body = $reply['content'] ?? $reply['message'] ?? $reply['body'] ?? $reply['text'] ?? null;

                if ($from !== '') {
                    $optOutService->handleInboundReply((string) $from, $body, $reply);
                }
            }

            $event->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Exception $e) {
            $event->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::error('SMS reply webhook error', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Amazon SES bounce/complaint/delivery notifications delivered via SNS.
     */
    public function ses(Request $request, EmailService $emailService)
    {
        if (!$this->secretValid($request)) {
            Log::warning('SES webhook rejected: invalid secret', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $body = json_decode($request->getContent(), true) ?: $request->all();
        $messageType = $request->header('x-amz-sns-message-type') ?? ($body['Type'] ?? null);

        // Auto-confirm SNS subscription.
        if ($messageType === 'SubscriptionConfirmation' && !empty($body['SubscribeURL'])) {
            try {
                Http::get($body['SubscribeURL']);
                Log::info('SES SNS subscription confirmed');
            } catch (\Throwable $e) {
                Log::error('SES SNS subscription confirm failed', ['error' => $e->getMessage()]);
            }
            return response()->json(['status' => 'confirmed']);
        }

        $event = WebhookEvent::create([
            'provider' => 'ses',
            'event_type' => 'ses_notification',
            'payload' => $body,
            'status' => 'received',
        ]);

        try {
            // SNS Notification wraps the SES payload as a JSON string in Message.
            $message = $body['Message'] ?? $body;
            if (is_string($message)) {
                $message = json_decode($message, true) ?: [];
            }

            $emailService->processNotification($message);

            $event->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Exception $e) {
            $event->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::error('SES webhook error', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }
}
