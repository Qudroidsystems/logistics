<?php

namespace App\Console\Commands;

use App\Modules\Billing\InvoiceService;
use Illuminate\Console\Command;

/** Bills last month's finished deliveries for every business account. Safe to run again. */
class IssueMonthlyInvoices extends Command
{
    protected $signature = 'invoices:monthly';

    protected $description = 'Issue last month\'s invoices for business accounts';

    public function handle(InvoiceService $invoices): int
    {
        $n = $invoices->monthly();
        $this->info("{$n} invoice(s) issued.");

        return self::SUCCESS;
    }
}
