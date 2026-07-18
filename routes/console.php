<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every minute to dispatch due campaigns
Schedule::command('campaigns:send-scheduled')->everyMinute()->withoutOverlapping();

// Run every minute to dispatch due booking reminders
Schedule::command('reminders:dispatch')->everyMinute()->withoutOverlapping();

// Fallback for missed/failed delivery-receipt webhooks — actively polls SMSPortal
// for anything still awaiting confirmation, with backoff, so campaigns can't get
// stuck at "sending" forever if the webhook never arrives.
Schedule::command('sms:poll-deliveries')->everyFiveMinutes()->withoutOverlapping();
