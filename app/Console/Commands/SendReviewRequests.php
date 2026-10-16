<?php

namespace App\Console\Commands;

use App\Modules\Marketing\ReviewRequestService;
use Illuminate\Console\Command;

class SendReviewRequests extends Command
{
    protected $signature = 'reviews:send';

    protected $description = 'Ask customers for a Google review once their delivery has been finished for a while';

    public function handle(ReviewRequestService $reviews): int
    {
        $this->info($reviews->run().' review requests sent.');

        return self::SUCCESS;
    }
}
