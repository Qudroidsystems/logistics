<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One-time backfill: creates a minimal staffbioinfo row for every User that
 * has the 'Staff' role but has no linked Staff record yet. Needed because
 * device-mapping search, StaffAttendance, and DeviceAttendanceProcessor all
 * key off staffbioinfo.id, not users.id — so every staff user needs one of
 * these rows to exist before they can be mapped to a device PIN.
 *
 * Usage: php artisan staff:backfill-bioinfo
 */
class BackfillStaffBioInfo extends Command
{
    protected $signature = 'staff:backfill-bioinfo';
    protected $description = 'Create a staffbioinfo row for every Staff-role user missing one';

    public function handle(): int
    {
        $users = User::role('Staff')
            ->whereDoesntHave('staffemploymentDetails')
            ->get();

        if ($users->isEmpty()) {
            $this->info('Nothing to backfill — every staff user already has a staffbioinfo row.');
            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} staff user(s) missing a staffbioinfo row.");

        foreach ($users as $user) {
            Staff::create([
                'userid' => $user->id,
                'status' => 'active',
                'email'  => $user->email,
            ]);
            $this->line(" - created for: {$user->name} (user #{$user->id})");
        }

        $this->info('Done. Re-run this any time a new staff account is created without one.');
        return self::SUCCESS;
    }
}