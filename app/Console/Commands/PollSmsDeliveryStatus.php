<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class PollSmsDeliveryStatus extends Command
{
    protected $signature = 'sms:poll-deliveries';
    protected $description = 'Actively check the SMS gateway for delivery status on messages whose webhook receipt never arrived.';

    public function handle(SmsService $smsService): void
    {
        $stats = $smsService->pollPendingDeliveries();

        $this->info("Checked {$stats['checked']}, resolved {$stats['resolved']}, gave up {$stats['gave_up']}, errors {$stats['errors']}.");
    }
}
