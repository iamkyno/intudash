<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class DispatchReminders extends Command
{
    protected $signature = 'reminders:dispatch';
    protected $description = 'Send any booking reminders whose send time has arrived.';

    public function handle(ReminderService $reminderService): int
    {
        $sent = $reminderService->dispatchDue();
        $this->info("Dispatched {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
