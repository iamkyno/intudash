<?php

namespace App\Services\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsPortalProvider implements SmsProviderInterface
{
    private string $clientId;
    private string $apiSecret;
    private string $baseUrl;
    private bool $testMode;
    private ?string $token = null;

    public function __construct()
    {
        $this->clientId = config('services.smsportal.client_id', '');
        $this->apiSecret = config('services.smsportal.api_secret', '');
        $this->baseUrl = config('services.smsportal.base_url', 'https://rest.smsportal.com/v1');
        $this->testMode = config('services.smsportal.test_mode', true);
    }

    public function authenticate(): bool
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->apiSecret)
                ->post("{$this->baseUrl}/Authentication");

            if ($response->successful()) {
                $this->token = $response->json('token');
                Log::info('SMSPortal authentication successful');
                return true;
            }

            Log::error('SMSPortal authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('SMSPortal authentication exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function getAuthHeaders(): array
    {
        if (!$this->token) {
            $this->authenticate();
        }

        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function sendBulk(array $recipients, string $message, string $sender = null): array
    {
        if (!$this->token && !$this->authenticate()) {
            return ['success' => false, 'error' => 'Authentication failed', 'results' => []];
        }

        $messages = array_map(fn($r) => [
            'content' => $message,
            'destination' => $r['phone'],
        ], $recipients);

        $payload = [
            'messages' => $messages,
            'sendOptions' => [
                'testMode' => $this->testMode,
            ],
        ];

        if ($sender) {
            $payload['sendOptions']['senderId'] = $sender;
        }

        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->post("{$this->baseUrl}/BulkMessages", $payload);

            $responseData = $response->json();

            Log::info('SMSPortal bulk send', [
                'test_mode' => $this->testMode,
                'recipient_count' => count($recipients),
                'status' => $response->status(),
                'event_id' => $responseData['eventId'] ?? null,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'event_id' => $responseData['eventId'] ?? null,
                    'results' => $responseData['messages'] ?? [],
                    'raw' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $responseData['message'] ?? 'Unknown error',
                'results' => [],
                'raw' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('SMSPortal bulk send exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'results' => []];
        }
    }

    public function getDeliveryStatus(string $messageId): array
    {
        if (!$this->token && !$this->authenticate()) {
            return ['success' => false, 'error' => 'Authentication failed'];
        }

        try {
            $response = Http::withHeaders($this->getAuthHeaders())
                ->get("{$this->baseUrl}/BulkMessages/{$messageId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => 'Failed to get status'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }
}
