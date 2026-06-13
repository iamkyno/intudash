<?php

namespace App\Services;

use App\Models\Client;
use App\Models\EmailLog;
use App\Models\Reminder;
use App\Models\ReminderTemplate;
use App\Models\SmsLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReminderService
{
    public function __construct(
        private SmsService $smsService,
        private EmailService $emailService
    ) {}

    /**
     * Create a reminder for a client from a normalised data array.
     * Throws ValidationException with a readable message on bad input.
     *
     * @param array $row keys: template (slug), send_at, phone?, email?, name?, data?
     */
    public function create(Client $client, array $row, string $source = 'manual'): Reminder
    {
        $slug = trim((string) ($row['template'] ?? ''));
        if ($slug === '') {
            throw ValidationException::withMessages(['template' => 'A template slug is required.']);
        }

        $template = $client->reminderTemplates()->where('slug', $slug)->where('active', true)->first();
        if (!$template) {
            throw ValidationException::withMessages([
                'template' => "No active reminder template '{$slug}' for this client.",
            ]);
        }

        $sendAtRaw = $row['send_at'] ?? null;
        if (empty($sendAtRaw)) {
            throw ValidationException::withMessages(['send_at' => 'A send date/time is required.']);
        }
        try {
            $sendAt = Carbon::parse($sendAtRaw);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['send_at' => "Could not parse send date '{$sendAtRaw}'."]);
        }

        $channel    = $template->channel;
        $needsPhone = in_array($channel, ['sms', 'both']);
        $needsEmail = in_array($channel, ['email', 'both']);

        $phone = trim((string) ($row['phone'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $normalized = null;

        if ($needsPhone) {
            if ($phone === '') {
                throw ValidationException::withMessages(['phone' => "Template '{$slug}' is SMS — a phone number is required."]);
            }
            $res = PhoneNormalizer::validateAndNormalize($phone);
            if (!$res['valid']) {
                throw ValidationException::withMessages(['phone' => "Invalid phone number: {$res['reason']}"]);
            }
            $normalized = $res['normalized'];
        }

        if ($needsEmail) {
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['email' => "Template '{$slug}' is email — a valid email is required."]);
            }
        }

        return Reminder::create([
            'client_id'            => $client->id,
            'reminder_template_id' => $template->id,
            'template_slug'        => $slug,
            'recipient_name'       => $row['name'] ?? null,
            'phone'                => $phone ?: null,
            'phone_normalized'     => $normalized,
            'email'                => $email ?: null,
            'data'                 => $row['data'] ?? null,
            'channel'              => $channel,
            'send_at'              => $sendAt,
            'status'               => 'pending',
            'source'               => $source,
        ]);
    }

    /**
     * Dispatch all pending reminders whose send_at has arrived.
     */
    public function dispatchDue(): int
    {
        $due = Reminder::due()->with('template')->limit(500)->get();
        $sent = 0;

        foreach ($due as $reminder) {
            // Atomic claim — only one worker sends a given reminder.
            $claimed = Reminder::where('id', $reminder->id)
                ->where('status', 'pending')
                ->update(['status' => 'sent', 'sent_at' => now()]);

            if (!$claimed) {
                continue;
            }

            try {
                $this->send($reminder->fresh('template'));
                $sent++;
            } catch (\Throwable $e) {
                $reminder->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                Log::error('Reminder dispatch failed', ['reminder_id' => $reminder->id, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    private function send(Reminder $reminder): void
    {
        $template = $reminder->template;
        if (!$template) {
            $reminder->update(['status' => 'failed', 'failure_reason' => 'Template no longer exists.']);
            return;
        }

        $channel    = $reminder->channel;
        $smsOk      = true;
        $emailOk    = true;
        $reasons    = [];

        if (in_array($channel, ['sms', 'both'])) {
            $message = $this->render($template->sms_body ?? '', $reminder);
            $number  = $reminder->phone_normalized ?: $reminder->phone;
            $res = $this->smsService->sendOne($number, $message, $template->sender_name);
            $smsOk = $res['success'];
            if ($res['message_id']) {
                $reminder->sms_provider_message_id = $res['message_id'];
            }
            if (!$smsOk) {
                $reasons[] = 'SMS: ' . ($res['error'] ?? 'failed');
            }

            // Unified delivery log — SMSPortal receipts update this row by message id.
            SmsLog::create([
                'source'              => 'reminder',
                'reminder_id'         => $reminder->id,
                'client_id'           => $reminder->client_id,
                'recipient_number'    => $number,
                'message'             => $message,
                'sms_segments'        => 1,
                'provider'            => 'smsportal',
                'provider_message_id' => $res['message_id'] ?? null,
                'status'              => $smsOk ? 'submitted' : 'failed',
                'failure_reason'      => $smsOk ? null : ($res['error'] ?? 'failed'),
                'sent_at'             => now(),
            ]);
        }

        if (in_array($channel, ['email', 'both'])) {
            $subject = $this->render($template->email_subject ?? '', $reminder);
            $body    = $this->render($template->email_body ?? '', $reminder);
            $res = $this->emailService->sendOne(
                $reminder->email,
                $subject,
                $body,
                $template->email_from_name,
                $template->email_from_address,
                $template->email_reply_to
            );
            $emailOk = $res['success'];
            if ($res['message_id']) {
                $reminder->email_provider_message_id = $res['message_id'];
            }
            if (!$emailOk) {
                $reasons[] = 'Email: ' . ($res['error'] ?? 'failed');
            }

            // Unified delivery log — SES (SNS) notifications update this row by message id.
            EmailLog::create([
                'source'              => 'reminder',
                'reminder_id'         => $reminder->id,
                'client_id'           => $reminder->client_id,
                'recipient_email'     => $reminder->email,
                'subject'             => $subject,
                'provider'            => 'ses',
                'provider_message_id' => $res['message_id'] ?? null,
                'status'              => $emailOk ? 'sent' : 'failed',
                'failure_reason'      => $emailOk ? null : ($res['error'] ?? 'failed'),
                'sent_at'             => $emailOk ? now() : null,
            ]);
        }

        if ($smsOk && $emailOk) {
            $reminder->status = 'sent';
        } elseif ($smsOk || $emailOk) {
            $reminder->status = $channel === 'both' ? 'partially_sent' : 'failed';
            $reminder->failure_reason = implode('; ', $reasons);
        } else {
            $reminder->status = 'failed';
            $reminder->failure_reason = implode('; ', $reasons);
        }

        $reminder->save();
    }

    /**
     * Merge {{name}}, {{email}}, {{phone}} and any custom data fields into a body.
     */
    private function render(string $body, Reminder $reminder): string
    {
        $merge = [
            'name'  => $reminder->recipient_name ?? '',
            'email' => $reminder->email ?? '',
            'phone' => $reminder->phone ?? '',
        ];

        foreach ((array) $reminder->data as $key => $value) {
            if (is_scalar($value)) {
                $merge[$key] = (string) $value;
            }
        }

        $replacements = [];
        foreach ($merge as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
            $replacements['{{ ' . $key . ' }}'] = $value;
        }

        return strtr($body, $replacements);
    }
}
