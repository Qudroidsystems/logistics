<?php

namespace App\Console\Commands;

use App\Modules\Cod\CodService;
use Illuminate\Console\Command;

class FlagOverdueCash extends Command
{
    protected $signature = 'cod:flag-overdue';

    protected $description = 'Tell providers about cash on delivery riders have held past the hand-in time';

    public function handle(CodService $cod): int
    {
        $this->info($cod->flagOverdue().' collections flagged.');

        return self::SUCCESS;
    }
}
