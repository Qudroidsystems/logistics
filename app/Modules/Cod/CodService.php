<?php

namespace App\Modules\Cod;

use App\Modules\Notifications\NotificationService;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cash on delivery: goods money the receiver pays the rider at the door, which belongs to whoever sent the parcel.
 *
 *   collect   the rider takes the cash at the drop-off (the stop cannot finish without the exact amount)
 *   hand in   staff count it in at the office
 *   pay out   staff pay the sender (bank transfer or cash) and record the reference
 *
 * Each step posts a balanced ledger entry, so the provider can always see who holds what:
 * cod_with_rider (per rider) -> cod_in_office (per provider) -> cod_payable to the sender, which falls to zero when paid.
 * The delivery fee is separate and settles through escrow as before.
 */
class CodService
{
    /** Riders should hand cash in within this many hours of collecting it. */
    public const HAND_IN_HOURS = 24;

    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts, private NotificationService $notify) {}

    public static function naira(int $kobo): string
    {
        return '₦'.number_format($kobo / 100, $kobo % 100 ? 2 : 0);
    }

    /** At the final drop-off. Safe to call twice for the same shipment. */
    public function collect(int $shipmentId, int $driverProfileId, int $amount): void
    {
        if ($amount <= 0 || DB::table('cod_collections')->where('shipment_id', $shipmentId)->exists()) {
            return;
        }
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->join('operators as p', 'p.id', '=', 's.operator_id')
            ->where('s.id', $shipmentId)->first(['s.id', 's.operator_id', 's.is_test', 'o.customer_id', 'o.merchant_id', 'o.order_number', 'p.display_name']);
        [$type, $sender] = $s->merchant_id ? ['merchant', (int) $s->merchant_id] : ['customer', (int) $s->customer_id];
        $op = (int) $s->operator_id;

        $tx = $this->ledger->post("cod-collect-{$shipmentId}", [
            'operator_id' => $op, 'kind' => 'cod_collect', 'reference_type' => 'shipment', 'reference_id' => $shipmentId,
            'description' => "Cash collected for order {$s->order_number}", 'is_test' => (bool) $s->is_test,
        ], [
            ['account_id' => $this->accounts->for('driver_profile', $driverProfileId, 'cod_with_rider', $op), 'direction' => 'debit', 'amount' => $amount],
            ['account_id' => $this->accounts->for($type, $sender, 'cod_payable', $op), 'direction' => 'credit', 'amount' => $amount],
        ]);
        DB::table('cod_collections')->insert([
            'shipment_id' => $shipmentId, 'driver_profile_id' => $driverProfileId, 'operator_id' => $op, 'amount' => $amount,
            'sender_type' => $type, 'sender_id' => $sender, 'status' => 'with_rider',
            'collected_at' => now(), 'remit_due_at' => now()->addHours(self::HAND_IN_HOURS), 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($type === 'customer') {
            $this->notify->notify($sender, 'cod.collected', ['amount' => self::naira($amount), 'order' => $s->order_number, 'provider' => $s->display_name], '/orders/'.DB::table('shipments')->where('id', $shipmentId)->value('public_id'));
        }
    }

    /**
     * Staff count in cash a rider brought to the office.
     *
     * @param  list<int>  $ids  cod_collections ids
     */
    public function handIn(int $operatorId, array $ids, int $staffId): int
    {
        return DB::transaction(function () use ($operatorId, $ids, $staffId) {
            $rows = DB::table('cod_collections')->where('operator_id', $operatorId)->whereIn('id', $ids)->where('status', 'with_rider')->lockForUpdate()->get();
            if ($rows->isEmpty()) {
                throw new RuntimeException('Choose cash that a rider is still holding.');
            }
            foreach ($rows as $r) {
                $this->ledger->post("cod-handin-{$r->id}", [
                    'operator_id' => $operatorId, 'kind' => 'cod_hand_in', 'reference_type' => 'cod_collection', 'reference_id' => $r->id,
                    'description' => 'Cash on delivery handed in', 'posted_by' => $staffId,
                ], [
                    ['account_id' => $this->accounts->for('operator', $operatorId, 'cod_in_office', $operatorId), 'direction' => 'debit', 'amount' => (int) $r->amount],
                    ['account_id' => $this->accounts->for('driver_profile', (int) $r->driver_profile_id, 'cod_with_rider', $operatorId), 'direction' => 'credit', 'amount' => (int) $r->amount],
                ]);
            }
            DB::table('cod_collections')->whereIn('id', $rows->pluck('id'))->update([
                'status' => 'in_office', 'remitted_at' => now(), 'remit_method' => 'hub_deposit', 'received_by' => $staffId, 'updated_at' => now(),
            ]);

            return (int) $rows->sum('amount');
        });
    }

    /**
     * Staff paid a sender the cash held for them. All rows must belong to one sender.
     *
     * @param  list<int>  $ids
     */
    public function payOut(int $operatorId, array $ids, string $reference, int $staffId): int
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new RuntimeException('Give the transfer reference, or write "cash" if you paid in cash.');
        }

        return DB::transaction(function () use ($operatorId, $ids, $reference, $staffId) {
            $rows = DB::table('cod_collections')->where('operator_id', $operatorId)->whereIn('id', $ids)->where('status', 'in_office')->lockForUpdate()->get();
            if ($rows->isEmpty()) {
                throw new RuntimeException('Choose cash that has been handed in at the office. Cash still with a rider must be handed in first.');
            }
            if ($rows->map(fn ($r) => $r->sender_type.':'.$r->sender_id)->unique()->count() > 1) {
                throw new RuntimeException('Pay one sender at a time.');
            }
            $first = $rows->first();
            $total = (int) $rows->sum('amount');
            $this->ledger->post('cod-pay-'.$rows->pluck('id')->sort()->implode('-'), [
                'operator_id' => $operatorId, 'kind' => 'cod_payout', 'reference_type' => 'cod_collection', 'reference_id' => (int) $first->id,
                'description' => "Cash on delivery paid to sender ({$reference})", 'posted_by' => $staffId,
            ], [
                ['account_id' => $this->accounts->for($first->sender_type, (int) $first->sender_id, 'cod_payable', $operatorId), 'direction' => 'debit', 'amount' => $total],
                ['account_id' => $this->accounts->for('operator', $operatorId, 'cod_in_office', $operatorId), 'direction' => 'credit', 'amount' => $total],
            ]);
            DB::table('cod_collections')->whereIn('id', $rows->pluck('id'))->update([
                'status' => 'paid', 'paid_at' => now(), 'paid_by' => $staffId, 'paid_reference' => mb_substr($reference, 0, 80), 'updated_at' => now(),
            ]);
            $provider = (string) DB::table('operators')->where('id', $operatorId)->value('display_name');
            $vars = ['provider' => $provider, 'amount' => self::naira($total), 'count' => $rows->count(), 'reference' => $reference];
            if ($first->sender_type === 'customer') {
                $this->notify->notify((int) $first->sender_id, 'cod.paid', $vars, '/wallet');
            } else {
                // A business on the partner API: tell its own team.
                $team = (int) DB::table('merchants')->where('id', $first->sender_id)->value('operator_id');
                if ($team) {
                    $this->notify->notifyOperator($team, 'cod.paid', $vars, '/merchant');
                }
            }

            return $total;
        });
    }

    /** Text riders whose cash is past the hand-in time, once per collection. Run hourly. */
    public function flagOverdue(): int
    {
        $rows = DB::table('cod_collections as c')->join('driver_profiles as d', 'd.id', '=', 'c.driver_profile_id')->join('users as u', 'u.id', '=', 'd.user_id')
            ->where('c.status', 'with_rider')->where('c.remit_due_at', '<', now())->whereNull('c.note')
            ->get(['c.id', 'c.operator_id', 'c.amount', 'u.name']);
        foreach ($rows->groupBy(fn ($r) => $r->operator_id.':'.$r->name) as $group) {
            $r = $group->first();
            $this->notify->notifyOperator((int) $r->operator_id, 'cod.overdue', ['rider' => $r->name, 'amount' => self::naira((int) $group->sum('amount'))], '/provider/cash');
        }
        DB::table('cod_collections')->whereIn('id', $rows->pluck('id'))->update(['note' => 'Hand-in overdue', 'updated_at' => now()]);

        return $rows->count();
    }

    /** The provider's Cash on delivery page. */
    public function forProvider(int $operatorId): array
    {
        $rows = DB::table('cod_collections as c')->join('shipments as s', 's.id', '=', 'c.shipment_id')->join('orders as o', 'o.id', '=', 's.order_id')
            ->join('driver_profiles as d', 'd.id', '=', 'c.driver_profile_id')->join('users as r', 'r.id', '=', 'd.user_id')
            ->leftJoin('users as cu', fn ($j) => $j->on('cu.id', '=', 'c.sender_id')->where('c.sender_type', 'customer'))
            ->leftJoin('merchants as m', fn ($j) => $j->on('m.id', '=', 'c.sender_id')->where('c.sender_type', 'merchant'))
            ->where('c.operator_id', $operatorId)
            ->where(fn ($q) => $q->where('c.status', '<>', 'paid')->orWhere('c.paid_at', '>', now()->subDays(30)))
            ->orderBy('c.collected_at')
            ->get(['c.id', 'c.amount', 'c.status', 'c.collected_at', 'c.remit_due_at', 'c.paid_at', 'c.paid_reference', 'c.sender_type', 'c.sender_id',
                'o.order_number', 's.public_id as shipment', 'r.name as rider', 'c.driver_profile_id', DB::raw('COALESCE(m.display_name, cu.name) AS sender')]);
        $rows->each(fn ($r) => $r->overdue = $r->status === 'with_rider' && $r->remit_due_at < now());

        return [
            'riders' => $rows->where('status', 'with_rider')->groupBy('driver_profile_id')->map(fn ($g) => [
                'rider' => $g->first()->rider, 'total' => (int) $g->sum('amount'), 'overdue' => $g->contains('overdue', true), 'rows' => $g->values(),
            ])->values(),
            'senders' => $rows->where('status', 'in_office')->groupBy(fn ($r) => $r->sender_type.':'.$r->sender_id)->map(fn ($g) => [
                'sender' => $g->first()->sender, 'kind' => $g->first()->sender_type, 'total' => (int) $g->sum('amount'), 'rows' => $g->values(),
            ])->values(),
            'paid' => $rows->where('status', 'paid')->sortByDesc('paid_at')->values(),
            'totals' => [
                'with_riders' => (int) $rows->where('status', 'with_rider')->sum('amount'),
                'in_office' => (int) $rows->where('status', 'in_office')->sum('amount'),
            ],
        ];
    }

    /** For the order screen and the partner API. Null when the delivery has no cash to collect. */
    public function forShipment(int $shipmentId): ?array
    {
        $due = (int) DB::table('shipments')->where('id', $shipmentId)->value('cod_amount');
        if ($due <= 0) {
            return null;
        }
        $c = DB::table('cod_collections')->where('shipment_id', $shipmentId)->first();
        $status = $c ? ($c->status === 'paid' ? 'paid' : 'collected') : 'to_collect';

        return [
            'amount' => $due, 'status' => $status,
            'label' => match ($status) {
                'paid' => self::naira($due).' paid to you'.($c->paid_reference ? " (ref {$c->paid_reference})" : ''),
                'collected' => self::naira($due).' collected. It will be paid to you.',
                default => 'The rider collects '.self::naira($due).' from the receiver.',
            },
            'collected_at' => $c->collected_at ?? null, 'paid_at' => $c->paid_at ?? null, 'paid_reference' => $c->paid_reference ?? null,
        ];
    }

    /** Cash the rider is carrying now. */
    public function heldBy(int $driverProfileId): int
    {
        return (int) DB::table('cod_collections')->where('driver_profile_id', $driverProfileId)->where('status', 'with_rider')->sum('amount');
    }

    /** Turns a naira amount typed on a form or in a spreadsheet into kobo; null when blank. */
    public static function kobo(mixed $naira): ?int
    {
        if ($naira === null || $naira === '') {
            return null;
        }
        $v = (float) str_replace([',', '₦', ' '], '', (string) $naira);

        return $v > 0 ? (int) round($v * 100) : null;
    }
}
