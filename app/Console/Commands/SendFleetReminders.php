<?php

namespace App\Console\Commands;

use App\Modules\Fleet\VehicleCareService;
use Illuminate\Console\Command;

/** Tells providers (and drivers, for their own papers) about insurance, roadworthiness, licences and services coming due. Safe to run again. */
class SendFleetReminders extends Command
{
    protected $signature = 'fleet:reminders';

    protected $description = 'Send reminders for vehicle papers, driver documents and services that are due';

    public function handle(VehicleCareService $care): int
    {
        $n = $care->sendReminders();
        $this->info("{$n} reminder(s) sent.");

        return self::SUCCESS;
    }
}
