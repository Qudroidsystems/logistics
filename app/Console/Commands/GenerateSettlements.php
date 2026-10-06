<?php

namespace App\Console\Commands;

use App\Modules\Settlements\SettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSettlements extends Command
{
    protected $signature = 'settlements:generate {--from=} {--to=}';
    protected $description = 'Build last week\'s provider settlement statements';

    public function handle(SettlementService $svc): int
    {
        [$start, $end] = $this->option('from') ? [$this->option('from'), $this->option('to') ?: $this->option('from')] : $svc->lastWeek();
        $n = 0;
        foreach (DB::table('commissions')->where('status', 'pending')->distinct()->pluck('operator_id') as $operatorId) {
            $svc->generate((int) $operatorId, $start, $end);
            $n++;
        }
        $this->info("Generated {$n} statement(s) for {$start} to {$end}.");

        return self::SUCCESS;
    }
}
