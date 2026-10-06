<?php

namespace App\Support\Database;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creates and retires time partitions for the high-volume tables.
 *
 *  - ledger_entries, shipment_events: monthly, kept forever
 *  - driver_locations: daily, raw points kept for $locationRetentionDays then dropped
 *
 * Every partitioned table also has a DEFAULT partition so an insert never fails if this job is late.
 * Run it from the scheduler (daily) and from the first migration, before any rows exist.
 */
class PartitionManager
{
    public function __construct(
        private int $monthsAhead = 3,
        private int $daysAhead = 7,
        private int $locationRetentionDays = 60,
    ) {
    }

    public function ensure(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $created = [];

        foreach (['ledger_entries', 'shipment_events'] as $table) {
            for ($i = 0; $i <= $this->monthsAhead; $i++) {
                $start = $now->startOfMonth()->addMonthsNoOverflow($i);
                $name = sprintf('%s_%s', $table, $start->format('Y_m'));
                if ($this->createPartition($table, $name, $start, $start->addMonthNoOverflow())) {
                    $created[] = $name;
                }
            }
        }

        for ($i = -1; $i <= $this->daysAhead; $i++) {
            $start = $now->startOfDay()->addDays($i);
            $name = 'driver_locations_' . $start->format('Ymd');
            if ($this->createPartition('driver_locations', $name, $start, $start->addDay())) {
                $created[] = $name;
            }
        }

        return $created;
    }

    /** Drop raw GPS partitions older than the retention window. Returns the names dropped. */
    public function pruneLocations(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $cutoff = $now->startOfDay()->subDays($this->locationRetentionDays);
        $dropped = [];

        $rows = DB::select(
            "SELECT c.relname FROM pg_inherits i
             JOIN pg_class c ON c.oid = i.inhrelid
             JOIN pg_class p ON p.oid = i.inhparent
             WHERE p.relname = 'driver_locations' AND c.relname ~ '^driver_locations_[0-9]{8}$'"
        );

        foreach ($rows as $row) {
            $day = CarbonImmutable::createFromFormat('Ymd', substr($row->relname, -8), 'UTC')->startOfDay();
            if ($day->lt($cutoff)) {
                DB::statement("DROP TABLE IF EXISTS {$row->relname}");
                $dropped[] = $row->relname;
            }
        }

        return $dropped;
    }

    private function createPartition(string $parent, string $name, CarbonImmutable $from, CarbonImmutable $to): bool
    {
        $exists = DB::selectOne('SELECT to_regclass(?) AS t', ['public.' . $name]);
        if ($exists && $exists->t !== null) {
            return false;
        }

        DB::statement(sprintf(
            "CREATE TABLE %s PARTITION OF %s FOR VALUES FROM ('%s') TO ('%s')",
            $name,
            $parent,
            $from->format('Y-m-d H:i:sP'),
            $to->format('Y-m-d H:i:sP'),
        ));

        return true;
    }
}
