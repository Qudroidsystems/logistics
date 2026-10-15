<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair for times saved before the database session was set to Lagos time (config/database.php).
 *
 * Until then Laravel wrote Lagos wall-clock times ("2026-10-15 14:00:00") into a session running on UTC, so Postgres
 * stored them one hour late. This moves those values back by the difference. It only touches values saved before
 * --before (when the fix went live), and skips columns that were always written with their offset, so they were
 * right all along. Values the database filled in itself (a column default) were also right; a few of those get
 * moved an hour early, which is the smaller error.
 *
 * Without --force it only reports what it would change.
 */
class RepairLagosTimes extends Command
{
    protected $signature = 'db:repair-lagos-times {--before= : when the timezone fix went live, e.g. "2026-10-16 09:00" (Lagos time)} {--force : make the changes}';

    protected $description = 'Move times saved before the timezone fix back to the right moment';

    /** Written with an explicit offset (or as UTC), so already correct. */
    private const ALWAYS_RIGHT = [
        'delivery_codes' => '*', 'custody_events' => '*', 'stop_etas' => '*',
        'shipments' => ['hold_until'], 'migrations' => '*',
    ];

    /** Times the phones send with their own offset. */
    private const DEVICE_COLUMNS = ['device_time'];

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('Only for Postgres.');

            return self::FAILURE;
        }
        if (! $this->option('before')) {
            $this->error('Give --before: the time the timezone fix went live, in Lagos time.');

            return self::FAILURE;
        }
        $before = CarbonImmutable::parse($this->option('before'), 'Africa/Lagos');
        // What a "wrong" value of that moment looks like: an hour later than the real instant.
        $offset = CarbonImmutable::now('Africa/Lagos')->getOffset();
        $cut = $before->addSeconds($offset)->toIso8601String();
        $shift = "{$offset} seconds";

        $cols = DB::select("SELECT c.table_name, c.column_name FROM information_schema.columns c
            JOIN information_schema.tables t ON t.table_schema = c.table_schema AND t.table_name = c.table_name AND t.table_type = 'BASE TABLE'
            WHERE c.table_schema = current_schema() AND c.data_type = 'timestamp with time zone'
              AND NOT EXISTS (SELECT 1 FROM pg_inherits i JOIN pg_class k ON k.oid = i.inhrelid WHERE k.relname = c.table_name)
            ORDER BY c.table_name, c.column_name");

        $total = 0;
        $rows = [];
        foreach ($cols as $c) {
            $skip = self::ALWAYS_RIGHT[$c->table_name] ?? null;
            if ($skip === '*' || (is_array($skip) && in_array($c->column_name, $skip, true)) || in_array($c->column_name, self::DEVICE_COLUMNS, true)) {
                continue;
            }
            $n = DB::table($c->table_name)->where($c->column_name, '<', $cut)->count();
            if ($n === 0) {
                continue;
            }
            $rows[] = [$c->table_name, $c->column_name, $n];
            $total += $n;
            if ($this->option('force')) {
                DB::statement(sprintf('UPDATE %s SET %s = %s - interval \'%s\' WHERE %s < ?',
                    $this->quote($c->table_name), $this->quote($c->column_name), $this->quote($c->column_name), $shift, $this->quote($c->column_name)), [$cut]);
            }
        }

        $this->table(['Table', 'Column', 'Values'], $rows);
        $this->info($this->option('force') ? "Moved {$total} values back by {$shift}." : "{$total} values would move back by {$shift}. Run again with --force to change them.");

        return self::SUCCESS;
    }

    private function quote(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }
}
