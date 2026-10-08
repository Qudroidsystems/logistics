<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pays a company's driver for a finished job, out of the company's wallet into the driver's own wallet (the same wallet
 * the driver sees at /account/wallet and can withdraw from). The company chooses the driver's share (driver_profiles.pay_share_bp);
 * with no share set nothing moves and the company pays its driver outside the platform, as before.
 *
 * Runs inside the escrow release, right after the company's wallet is credited. It is posted once per assignment
 * (idempotent) and never breaks the release: a problem here is logged and the job can be paid later by hand.
 */
class DriverPayService
{
    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts)
    {
    }

    /** Pure: the driver's cut of the company's net, rounded down to whole kobo. */
    public static function share(int $companyNet, ?int $shareBp): int
    {
        if ($companyNet <= 0 || ! $shareBp || $shareBp < 0) {
            return 0;
        }

        return intdiv($companyNet * min($shareBp, 10000), 10000);
    }

    /** @return int kobo paid to the driver (0 when nothing was due) */
    public function payForShipment(int $shipmentId, int $companyNet, int $operatorId, bool $isTest = false): int
    {
        try {
            return (int) DB::transaction(function () use ($shipmentId, $companyNet, $operatorId, $isTest) {
                $a = DB::table('assignments as a')->join('driver_profiles as d', 'd.id', '=', 'a.driver_profile_id')
                    ->where('a.shipment_id', $shipmentId)->where('a.status', 'completed')->whereNull('a.pay_posted_at')
                    ->orderByDesc('a.id')->lockForUpdate()
                    ->first(['a.id', 'd.user_id', 'd.pay_share_bp', 'd.operator_id as driver_operator']);
                if (! $a || (int) $a->driver_operator !== $operatorId) {
                    return 0; // nobody to pay, or a driver who is not on this company's team
                }
                if (! $this->paidOnFailure($shipmentId)) {
                    return 0;
                }
                $pay = self::share($companyNet, $a->pay_share_bp ? (int) $a->pay_share_bp : null);
                if ($pay <= 0) {
                    return 0;
                }
                $this->ledger->post("driver-pay-{$a->id}", [
                    'operator_id' => $operatorId, 'kind' => 'driver_pay', 'reference_type' => 'assignment', 'reference_id' => (int) $a->id,
                    'description' => 'Driver pay for a finished delivery', 'is_test' => $isTest,
                ], [
                    ['account_id' => $this->accounts->wallet('operator', $operatorId, $operatorId), 'direction' => 'debit', 'amount' => $pay],
                    ['account_id' => $this->accounts->wallet('customer', (int) $a->user_id, 1), 'direction' => 'credit', 'amount' => $pay],
                ]);
                DB::table('assignments')->where('id', $a->id)->update(['payout_amount' => $pay, 'pay_posted_at' => now(), 'updated_at' => now()]);

                return $pay;
            });
        } catch (Throwable $e) {
            Log::warning("Driver pay for shipment {$shipmentId} not posted: {$e->getMessage()}");

            return 0;
        }
    }

    /** Pays a job whose money was released earlier but whose driver only just finished (the return trip of a failed delivery). */
    public function payFromCommission(int $shipmentId): int
    {
        $c = DB::table('commissions')->where('shipment_id', $shipmentId)->first(['operator_id', 'operator_net', 'agreement_id']);
        if (! $c) {
            return 0;
        }
        $test = (bool) DB::table('agreements')->where('id', $c->agreement_id)->value('is_test');

        return $this->payForShipment($shipmentId, (int) $c->operator_net, (int) $c->operator_id, $test);
    }

    /** False when the shipment ended as a failed delivery and the agreement says the driver is not paid for that. */
    private function paidOnFailure(int $shipmentId): bool
    {
        $row = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->join('agreements as a', 'a.id', '=', 'o.agreement_id')
            ->where('s.id', $shipmentId)->first(['s.status', 'a.failed_delivery_policy']);
        if (! $row || ! in_array($row->status, ['failed_attempt', 'returning', 'returned'], true)) {
            return true;
        }
        $policy = \App\Modules\Marketplace\FailedDeliveryPolicy::normalize($row->failed_delivery_policy ? json_decode($row->failed_delivery_policy, true) : null);

        return $policy['driver_paid'];
    }
}
