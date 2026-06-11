<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function smsportal(Request $request, SmsService $smsService)
    {
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
}
