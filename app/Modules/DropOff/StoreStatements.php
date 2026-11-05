<?php

namespace App\Modules\DropOff;

use App\Modules\Notifications\NotificationService;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * A partner store's monthly statement: the parcels it handed to drivers in the month (local time), what it earned
 * for them, what we paid it during the month, and what is still owed today. Emailed on the 1st and viewable any time
 * from the counter screen and Ops > Drop-off centres.
 */
class StoreStatements
{
    public function __construct(private NotificationService $notifications) {}

    /** [start, end) of a 'YYYY-MM' month in the app's time zone, as UTC instants. */
    public static function bounds(string $month): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new InvalidArgumentException('Month must look like 2026-11.');
        }
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $month.'-01 00:00:00', config('app.timezone'));

        return [$start->copy()->utc(), $start->copy()->addMonth()->utc()];
    }

    public static function lastMonth(): string
    {
        return now(config('app.timezone'))->startOfMonth()->subMonth()->format('Y-m');
    }

    /** @return array{month:string, label:string, parcels:array, count:int, earned:int, paid:int, owed:int, payouts:array} */
    public function build(int $centreId, string $month): array
    {
        [$from, $to] = self::bounds($month);
        $parcels = DB::table('dropoff_parcels as p')->join('shipments as s', 's.id', '=', 'p.shipment_id')
            ->where('p.dropoff_centre_id', $centreId)->whereIn('p.commission_status', ['earned', 'paid'])
            ->where('p.collected_at', '>=', $from)->where('p.collected_at', '<', $to)
            ->orderBy('p.collected_at')->get(['s.tracking_code', 'p.received_at', 'p.collected_at', 'p.commission', 'p.commission_status', 'p.payout_ref']);
        $payouts = DB::table('dropoff_parcels')->where('dropoff_centre_id', $centreId)->where('commission_status', 'paid')
            ->where('paid_at', '>=', $from)->where('paid_at', '<', $to)
            ->groupBy('payout_ref')->selectRaw('payout_ref, min(paid_at) as paid_at, count(*) as parcels, sum(commission) as amount')->orderBy('paid_at')->get();

        return [
            'month' => $month,
            'label' => $from->copy()->setTimezone(config('app.timezone'))->format('F Y'),
            'parcels' => $parcels->all(),
            'count' => $parcels->count(),
            'earned' => (int) $parcels->sum('commission'),
            'paid' => (int) $payouts->sum('amount'),
            'owed' => (int) DB::table('dropoff_parcels')->where('dropoff_centre_id', $centreId)->where('commission_status', 'earned')->sum('commission'),
            'payouts' => $payouts->all(),
        ];
    }

    /** Sends last month's (or the given month's) statement to every store that had parcels, payouts or money owed. Returns how many were sent. */
    public function sendAll(?string $month = null, bool $again = false): int
    {
        $month ??= self::lastMonth();
        $sent = 0;
        foreach (DB::table('dropoff_centres')->get(['id', 'name']) as $c) {
            if (! $again && DB::table('dropoff_statements')->where(['dropoff_centre_id' => $c->id, 'month' => $month])->whereNotNull('sent_at')->exists()) {
                continue;
            }
            $s = $this->build((int) $c->id, $month);
            if ($s['count'] === 0 && $s['paid'] === 0 && $s['owed'] === 0) {
                continue;
            }
            DB::table('dropoff_statements')->updateOrInsert(['dropoff_centre_id' => $c->id, 'month' => $month],
                ['parcels' => $s['count'], 'earned' => $s['earned'], 'paid' => $s['paid'], 'owed' => $s['owed'], 'sent_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
            foreach (DB::table('dropoff_centre_users')->where('dropoff_centre_id', $c->id)->pluck('user_id') as $uid) {
                $this->notifications->notify((int) $uid, 'dropoff.statement', [
                    'store' => $c->name, 'month' => $s['label'], 'parcels' => $s['count'], 'earned' => Money::format($s['earned'], true),
                    'paid' => Money::format($s['paid'], true), 'owed' => Money::format($s['owed'], true),
                ], '/counter/statement/'.$month);
            }
            $sent++;
        }

        return $sent;
    }
}
