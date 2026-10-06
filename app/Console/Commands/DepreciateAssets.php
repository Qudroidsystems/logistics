<?php

namespace App\Console\Commands;

use App\Services\Finance\AssetService;
use Illuminate\Console\Command;

class DepreciateAssets extends Command
{
    protected $signature = 'assets:depreciate {--period= : Month to depreciate (Y-m), default last month}';
    protected $description = 'Charge monthly straight-line depreciation on fixed assets (safe to re-run).';

    public function handle(AssetService $svc): int
    {
        if (!AssetService::available()) { $this->warn('Asset tables not migrated yet.'); return self::SUCCESS; }
        $period = $this->option('period') ?: now()->subMonthNoOverflow()->format('Y-m');
        $r = $svc->depreciate($period);
        $this->info("Depreciation {$period}: ₦" . number_format($r['total'], 2) . " on {$r['count']} asset(s).");
        return self::SUCCESS;
    }
}
