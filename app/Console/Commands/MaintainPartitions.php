<?php

namespace App\Console\Commands;

use App\Support\Database\PartitionManager;
use Illuminate\Console\Command;

class MaintainPartitions extends Command
{
    protected $signature = 'partitions:maintain {--no-prune : Keep old GPS partitions}';
    protected $description = 'Create upcoming time partitions and drop expired raw GPS partitions';

    public function handle(PartitionManager $partitions): int
    {
        $created = $partitions->ensure();
        $this->info('Created ' . count($created) . ' partition(s).');

        if (! $this->option('no-prune')) {
            $dropped = $partitions->pruneLocations();
            $this->info('Dropped ' . count($dropped) . ' expired GPS partition(s).');
        }

        return self::SUCCESS;
    }
}
