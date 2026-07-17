<?php

namespace App\Services;

class RecipientValidationService
{
    /**
     * Validate + normalise a single raw contact row against a channel requirement.
     * 'any' means either a phone or an email is enough — used for the persistent
     * client-level audience, which isn't tied to one campaign's channel.
     */
    public static function validateRow(?string $name, ?string $phone, ?string $email, string $channelType = 'any'): array
    {
        $phone = trim((string) $phone);
        $email = strtolower(trim((string) $email));

        $hasUsablePhone = false;
        $normalized = null;
        $invalidReason = null;

        if ($phone !== '') {
            $result = PhoneNormalizer::validateAndNormalize($phone);
            $hasUsablePhone = $result['valid'];
            $normalized = $result['valid'] ? $result['normalized'] : null;
            if (!$result['valid']) {
                $invalidReason = $result['reason'];
            }
        }

        $hasUsableEmail = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);

        $usable = match ($channelType) {
            'sms'   => $hasUsablePhone,
            'email' => $hasUsableEmail,
            'both'  => $hasUsablePhone || $hasUsableEmail,
            default => $hasUsablePhone || $hasUsableEmail, // 'any' — persistent list, channel-agnostic
        };

        if (!$usable && !$invalidReason) {
            $invalidReason = $channelType === 'email'
                ? 'Missing or invalid email address'
                : 'Missing or invalid phone number';
        }

        return [
            'name'             => $name ?: null,
            'phone'            => $phone ?: null,
            'phone_normalized' => $normalized,
            'email'            => $email ?: null,
            'usable'           => $usable,
            'invalid_reason'   => $usable ? null : $invalidReason,
            'dedupe_key'       => $normalized ?: ($email ?: null),
        ];
    }

    /**
     * Pull name/phone/email out of a League\Csv record, tolerating a few common header casings.
     */
    public static function extractCsvFields(array $record): array
    {
        return [
            'name'  => $record['name'] ?? $record['Name'] ?? null,
            'phone' => trim($record['phone'] ?? $record['Phone'] ?? $record['mobile'] ?? $record['Mobile'] ?? ''),
            'email' => trim($record['email'] ?? $record['Email'] ?? ''),
        ];
    }
}
