<?php

namespace App\Services;

class PhoneNormalizer
{
    public static function normalize(string $phone): ?string
    {
        $clean = preg_replace('/[\s\-\(\)\+]/', '', $phone);

        // Already in international format
        if (preg_match('/^27[6-8][0-9]{8}$/', $clean)) {
            return '+' . $clean;
        }

        // Local 0xx format
        if (preg_match('/^0([6-8][0-9]{8})$/', $clean, $m)) {
            return '+27' . $m[1];
        }

        // International +27 format with +
        if (preg_match('/^\+27([6-8][0-9]{8})$/', $phone)) {
            return preg_replace('/[\s\-\(\)]/', '', $phone);
        }

        return null;
    }

    public static function isValid(string $phone): bool
    {
        return static::normalize($phone) !== null;
    }

    public static function validateAndNormalize(string $phone): array
    {
        $normalized = static::normalize($phone);

        if ($normalized) {
            return ['valid' => true, 'normalized' => $normalized, 'reason' => null];
        }

        return [
            'valid' => false,
            'normalized' => null,
            'reason' => 'Invalid South African mobile number',
        ];
    }
}
