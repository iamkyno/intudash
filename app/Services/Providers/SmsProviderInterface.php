<?php

namespace App\Services\Providers;

interface SmsProviderInterface
{
    public function sendBulk(array $recipients, string $message, string $sender = null): array;
    public function getDeliveryStatus(string $messageId): array;
    public function authenticate(): bool;
}
