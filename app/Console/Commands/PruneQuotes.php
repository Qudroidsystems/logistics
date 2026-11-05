<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Every price check saves a quote; ones never booked are removed a week after they expire, so the table doesn't grow without end. */
class PruneQuotes extends Command
{
    protected $signature = 'quotes:prune {--days=7 : Keep unbooked quotes this many days after they expire}';

    protected $description = 'Remove old price quotes that were never booked';

    public function handle(): int
    {
        $before = now()->subDays(max(1, (int) $this->option('days')));
        $total = 0;
        do {
            $ids = DB::table('quotes as q')->whereNull('q.accepted_order_id')->where('q.expires_at', '<', $before)
                ->whereNotExists(fn ($w) => $w->select(DB::raw(1))->from('orders as o')->whereColumn('o.quote_id', 'q.id'))
                ->whereNotExists(fn ($w) => $w->select(DB::raw(1))->from('agreements as a')->whereRaw("(a.terms->>'quote_id') = q.id::text"))
                ->limit(5000)->pluck('q.id');
            $total += $ids->isEmpty() ? 0 : DB::table('quotes')->whereIn('id', $ids)->delete();
        } while ($ids->count() === 5000);
        $this->info("{$total} old quote(s) removed.");

        return self::SUCCESS;
    }
}
