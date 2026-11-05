<?php

namespace App\Console\Commands;

use App\Modules\DropOff\StoreStatements;
use Illuminate\Console\Command;

/** On the 1st: email each partner store last month's statement (parcels handed over, earned, paid, still owed). */
class SendStoreStatements extends Command
{
    protected $signature = 'dropoff:statements {--month= : YYYY-MM, default last month} {--again : Send even if already sent}';

    protected $description = 'Send partner stores their monthly statement';

    public function handle(StoreStatements $statements): int
    {
        try {
            $n = $statements->sendAll($this->option('month') ?: null, (bool) $this->option('again'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("{$n} statement(s) sent.");

        return self::SUCCESS;
    }
}
