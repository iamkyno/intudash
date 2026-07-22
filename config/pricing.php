<?php

/*
|--------------------------------------------------------------------------
| Default pricing (ZAR)
|--------------------------------------------------------------------------
|
| Baseline rates used when nothing is set in Settings. These reflect
| realistic South African bulk-messaging market pricing rather than
| token placeholder values. Override the live values under Settings;
| set your true cost-to-company under "internal cost" so profit figures
| are accurate.
|
| All values are in Rand per single message (per SMS segment / per email).
|
*/

return [
    'sms' => [
        // Your true cost to send one SMS segment. Set this to your gateway's
        // actual per-message charge — the placeholder is a mid-volume estimate.
        'internal_cost' => env('DEFAULT_SMS_INTERNAL_COST', '0.2000'),
        // What you charge the client per SMS segment.
        'client_rate'   => env('DEFAULT_SMS_CLIENT_RATE', '0.4500'),
    ],

    'email' => [
        // True cost to send one email (gateway per-email fee in ZAR).
        'internal_cost' => env('DEFAULT_EMAIL_INTERNAL_COST', '0.0020'),
        // What you charge the client per email.
        'client_rate'   => env('DEFAULT_EMAIL_CLIENT_RATE', '0.0100'),
    ],
];
