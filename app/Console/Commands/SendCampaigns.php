<?php

namespace App\Console\Commands;

use App\Modules\Marketing\CampaignService;
use Illuminate\Console\Command;

class SendCampaigns extends Command
{
    protected $signature = 'campaigns:send';

    protected $description = 'Start campaigns that are due and send the next batch of each';

    public function handle(CampaignService $campaigns): int
    {
        $this->info($campaigns->run().' campaign messages sent.');

        return self::SUCCESS;
    }
}
