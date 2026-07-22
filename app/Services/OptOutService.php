<?php

namespace App\Services;

use App\Models\ClientRecipient;
use App\Models\OptOut;
use App\Models\Reminder;
use App\Models\SmsReply;
use Illuminate\Support\Facades\Log;

class OptOutService
{
    /** Reply keywords (case-insensitive, whole message after trimming) that mean "stop messaging me". */
    public const KEYWORDS = ['STOP', 'END', 'CANCEL', 'UNSUBSCRIBE', 'QUIT', 'OPT OUT', 'OPTOUT', 'STOP ALL'];

    public static function isOptOutMessage(?string $message): bool
    {
        $clean = strtoupper(trim((string) $message));

        return $clean !== '' && in_array($clean, self::KEYWORDS, true);
    }

    /**
     * Record an inbound reply; if it's a STOP-style keyword, opt the number out
     * everywhere: global do-not-contact list, every client's saved recipient
     * list, and any still-pending reminders to that number.
     */
    public function handleInboundReply(string $fromNumber, ?string $message, array $rawPayload = []): SmsReply
    {
        $normalized = PhoneNormalizer::normalize($fromNumber);
        $isOptOut = self::isOptOutMessage($message);

        $reply = SmsReply::create([
            'from_number'      => $fromNumber,
            'phone_normalized' => $normalized,
            'message'          => $message,
            'is_opt_out'       => $isOptOut,
            'raw_payload'      => $rawPayload,
        ]);

        if ($isOptOut && $normalized) {
            $this->optOut($normalized, 'sms_reply', strtoupper(trim($message)));
        }

        return $reply;
    }

    public function optOut(string $phoneNormalized, string $source = 'manual', ?string $keyword = null): void
    {
        OptOut::firstOrCreate(
            ['phone_normalized' => $phoneNormalized],
            ['channel' => 'sms', 'source' => $source, 'keyword' => $keyword, 'opted_out_at' => now()]
        );

        // Every client's saved audience — the number said stop, so stop for everyone.
        $unsubscribed = ClientRecipient::where('phone_normalized', $phoneNormalized)
            ->where('status', '!=', 'unsubscribed')
            ->update(['status' => 'unsubscribed', 'invalid_reason' => 'Opted out via SMS reply']);

        // Anything still queued to go to this number.
        $cancelled = Reminder::where('phone_normalized', $phoneNormalized)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled', 'failure_reason' => 'Recipient opted out']);

        Log::info('Number opted out', [
            'number'               => $phoneNormalized,
            'source'               => $source,
            'recipients_unsubbed'  => $unsubscribed,
            'reminders_cancelled'  => $cancelled,
        ]);
    }
}
