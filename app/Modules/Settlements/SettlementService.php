<?php

namespace App\Modules\Settlements;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A settlement is the statement for one provider and one period: every completed job's gross, the platform's
 * commission and the provider's net. Providers are paid into their wallet at the moment of release; the
 * statement is the record they reconcile against and the basis for their invoice.
 */
class SettlementService
{
    /** Builds (or returns) the draft statement for [start, end] inclusive. Re-running never double counts. */
    public function generate(int $operatorId, string $start, string $end): int
    {
        return DB::transaction(function () use ($operatorId, $start, $end) {
            $existing = DB::table('settlements')->where(['operator_id' => $operatorId, 'period_start' => $start, 'period_end' => $end])->lockForUpdate()->first();
            if ($existing && $existing->status !== 'draft') {
                return (int) $existing->id;
            }
            $id = $existing->id ?? DB::table('settlements')->insertGetId([
                'public_id' => (string) \Illuminate\Support\Str::ulid(), 'operator_id' => $operatorId, 'period_start' => $start, 'period_end' => $end,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Only commissions not yet on any statement, created inside the period.
            $rows = DB::table('commissions')->where('operator_id', $operatorId)->where('status', 'pending')
                ->where('created_at', '>=', $start.' 00:00:00')->where('created_at', '<', date('Y-m-d', strtotime($end.' +1 day')).' 00:00:00')
                ->lockForUpdate()->get();

            foreach ($rows as $c) {
                DB::table('settlement_items')->insert(['settlement_id' => $id, 'commission_id' => $c->id, 'shipment_id' => $c->shipment_id, 'amount' => $c->operator_net]);
                DB::table('commissions')->where('id', $c->id)->update(['status' => 'settled', 'updated_at' => now()]);
            }

            $t = DB::table('settlement_items')->join('commissions', 'commissions.id', '=', 'settlement_items.commission_id')
                ->where('settlement_items.settlement_id', $id)
                ->selectRaw('COALESCE(SUM(commissions.gross_amount),0) g, COALESCE(SUM(commissions.platform_fee),0) c, COALESCE(SUM(commissions.operator_net),0) n, COALESCE(SUM(commissions.tax),0) t')->first();
            DB::table('settlements')->where('id', $id)->update([
                'gross' => $t->g, 'commission' => $t->c, 'tax' => $t->t, 'net' => $t->n, 'updated_at' => now(),
            ]);

            return (int) $id;
        });
    }

    /** Freezes the statement. After this, late commissions roll into the next period. */
    public function approve(int $settlementId): void
    {
        $n = DB::table('settlements')->where('id', $settlementId)->where('status', 'draft')->update(['status' => 'approved', 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('Only a draft statement can be approved.');
        }
    }

    /** Previous Monday to Sunday relative to $today. */
    public function lastWeek(?string $today = null): array
    {
        $t = strtotime($today ?? 'today');
        $monday = strtotime('monday this week', $t);

        return [date('Y-m-d', strtotime('-7 days', $monday)), date('Y-m-d', strtotime('-1 day', $monday))];
    }
}
