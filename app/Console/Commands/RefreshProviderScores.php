<?php

namespace App\Console\Commands;

use App\Modules\Ratings\ProviderScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshProviderScores extends Command
{
    protected $signature = 'providers:refresh-scores';

    protected $description = 'Recompute every active provider\'s rating, completion and dispute rates, score and tier';

    public function handle(ProviderScoreService $scores): int
    {
        $n = 0;
        DB::table('operators')->whereIn('type', ['company', 'independent_driver', 'market_shopper'])->where('status', 'active')->orderBy('id')->chunkById(100, function ($ops) use ($scores, &$n) {
            foreach ($ops as $op) {
                $scores->refresh((int) $op->id);
                $n++;
            }
        });
        $this->info("Refreshed {$n} providers.");

        return self::SUCCESS;
    }
}
