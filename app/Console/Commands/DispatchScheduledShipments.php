<?php

namespace App\Console\Commands;

use App\Modules\Dispatch\DispatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Bookings for later: start looking for a driver shortly before the pickup time the customer chose. */
class DispatchScheduledShipments extends Command
{
    protected $signature = 'dispatch:scheduled';
    protected $description = 'Offer scheduled shipments to drivers when their pickup time is near';

    public function handle(DispatchService $dispatch): int
    {
        $due = DB::table('shipments as s')->where('s.status', 'awaiting_dispatch')->whereNotNull('s.pickup_window_start')
            ->where('s.pickup_window_start', '<=', now()->addMinutes(DispatchService::SCHEDULED_LEAD_MINUTES))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('dispatch_runs as r')->whereColumn('r.shipment_id', 's.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('assignments as a')->whereColumn('a.shipment_id', 's.id')->whereIn('a.status', ['accepted', 'en_route', 'active']))
            ->limit(200)->pluck('s.id');

        $started = 0;
        foreach ($due as $id) {
            try {
                $dispatch->start((int) $id, 'scored', true);
                $started++;
            } catch (Throwable $e) {
                Log::warning("Scheduled dispatch for shipment {$id} failed: {$e->getMessage()}");
            }
        }
        $this->info("Started dispatch for {$started} scheduled shipment(s).");

        return self::SUCCESS;
    }
}
