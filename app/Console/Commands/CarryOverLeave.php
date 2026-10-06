<?php

namespace App\Console\Commands;

use App\Services\Leave\LeaveService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Carry unused annual leave into a new year.
 *
 * For every active staff member and every leave type that allows carry-over
 * (carry_over_max > 0), the unused portion of the previous year's entitlement
 * is written as a leave_adjustment in the new year, capped at carry_over_max.
 *
 * Idempotent: each carry-over adjustment is tagged with a marker note, so
 * re-running the command never doubles it — it updates the existing row.
 */
class CarryOverLeave extends Command
{
    protected $signature = 'leave:carry-over
        {--year= : The NEW year to carry balances INTO (defaults to the current year)}
        {--dry-run : Show what would happen without writing anything}';

    protected $description = 'Carry unused annual leave from the previous year into the new year (capped per leave type)';

    public function handle(LeaveService $svc): int
    {
        if (!Schema::hasTable('leave_requests') || !Schema::hasTable('leave_adjustments')) {
            $this->error('Leave tables are not installed. Run the leave migrations first.');
            return self::FAILURE;
        }

        $newYear = (int) ($this->option('year') ?: Carbon::now()->year);
        $prevYear = $newYear - 1;
        $dry = (bool) $this->option('dry-run');

        $carryTypes = DB::table('leave_types')
            ->where('is_active', true)->where('carry_over_max', '>', 0)->where('days_per_year', '>', 0)
            ->get(['id', 'name', 'carry_over_max', 'gender']);

        if ($carryTypes->isEmpty()) {
            $this->warn('No leave types allow carry-over (carry_over_max = 0 for all). Nothing to do.');
            return self::SUCCESS;
        }

        $staff = DB::table('staffbioinfo')->get(['id', 'gender']);
        $this->info(sprintf('Carrying unused %d leave into %d for %d staff across %d type(s)%s…',
            $prevYear, $newYear, $staff->count(), $carryTypes->count(), $dry ? ' [DRY RUN]' : ''));

        $marker = "Carried over from {$prevYear}";
        $written = 0; $skippedZero = 0; $totalDays = 0.0;

        foreach ($staff as $st) {
            $balances = $svc->balances((int) $st->id, $prevYear, $st->gender ?? null)->keyBy('id');
            foreach ($carryTypes as $t) {
                $bal = $balances->get($t->id);
                if (!$bal) continue; // type not applicable (e.g. gender)

                // Unused = entitlement minus what was actually approved last year.
                $unused = max(0.0, (float) $bal->entitled - (float) $bal->approved);
                $carry = round(min($unused, (float) $t->carry_over_max), 1);
                if ($carry <= 0) { $skippedZero++; continue; }

                $existing = DB::table('leave_adjustments')
                    ->where('staff_id', $st->id)->where('leave_type_id', $t->id)
                    ->where('year', $newYear)->where('note', $marker)->first();

                if ($existing && (float) $existing->days == $carry) { continue; } // already correct

                $totalDays += $carry; $written++;
                if ($dry) continue;

                if ($existing) {
                    DB::table('leave_adjustments')->where('id', $existing->id)->update(['days' => $carry, 'updated_at' => now()]);
                } else {
                    DB::table('leave_adjustments')->insert([
                        'staff_id' => $st->id, 'leave_type_id' => $t->id, 'year' => $newYear,
                        'days' => $carry, 'note' => $marker, 'created_by' => null,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->info(sprintf('%s %d carry-over adjustment(s), %.1f day(s) total. (%d had nothing to carry.)',
            $dry ? 'Would write' : 'Wrote', $written, $totalDays, $skippedZero));
        return self::SUCCESS;
    }
}
