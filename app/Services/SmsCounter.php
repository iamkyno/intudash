<?php

namespace App\Services;

class SmsCounter
{
    // GSM 03.38 characters that count as 2 characters in extended set
    private const EXTENDED_CHARS = ['^', '{', '}', '\\', '[', '~', ']', '|', '€'];

    public static function count(string $message): array
    {
        $length = 0;
        $hasExtended = false;

        for ($i = 0; $i < mb_strlen($message); $i++) {
            $char = mb_substr($message, $i, 1);
            if (in_array($char, self::EXTENDED_CHARS, true)) {
                $length += 2;
                $hasExtended = true;
            } else {
                $length++;
            }
        }

        $singleSmsLimit = 160;
        $multiSmsLimit = 153;

        if ($length <= $singleSmsLimit) {
            $segments = 1;
        } else {
            $segments = (int) ceil($length / $multiSmsLimit);
        }

        return [
            'length' => $length,
            'segments' => $segments,
            'has_extended' => $hasExtended,
            'single_limit' => $singleSmsLimit,
            'remaining' => $length <= $singleSmsLimit
                ? $singleSmsLimit - $length
                : $multiSmsLimit - ($length % $multiSmsLimit),
        ];
    }
}
