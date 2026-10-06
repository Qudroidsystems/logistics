<?php

namespace App\Modules\Marketplace;

use App\Modules\Payments\Escrow\EscrowService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DisputeService
{
    public function __construct(private EscrowService $escrow)
    {
    }

    /**
     * Staff decision. full_release pays the provider as agreed; full_refund returns everything;
     * partial pays the provider $providerShare of the service price and refunds the rest.
     */
    public function decide(int $disputeId, string $decision, int $staffId, ?int $providerShare = null): array
    {
        if (! in_array($decision, ['full_release', 'partial', 'full_refund'], true)) {
            throw new InvalidArgumentException('Unknown decision.');
        }
        if ($decision === 'partial' && $providerShare === null) {
            throw new InvalidArgumentException('A partial decision needs the provider share.');
        }

        return DB::transaction(function () use ($disputeId, $decision, $staffId, $providerShare) {
            $d = DB::table('disputes')->where('id', $disputeId)->lockForUpdate()->first();
            if (! $d || ! in_array($d->status, ['open', 'evidence'], true)) {
                throw new RuntimeException('Dispute is not open.');
            }
            $agreementId = (int) $d->agreement_id;
            $goods = app(ShoppingService::class)->spend($agreementId);

            $this->escrow->unfreeze($agreementId);
            $result = match ($decision) {
                'full_release' => $this->escrow->release($agreementId, $goods, $staffId),
                'full_refund' => $this->escrow->releasePartial($agreementId, 0, 0, $staffId),
                'partial' => $this->escrow->releasePartial($agreementId, (int) $providerShare, $goods, $staffId),
            };

            app(\App\Modules\Partner\ShipmentEvents::class)->record((int) $d->shipment_id, 'dispute_resolved', 'delivered', 'delivered', 'staff', $staffId, ['decision' => $decision]);
            DB::table('disputes')->where('id', $disputeId)->update([
                'status' => 'decided', 'decision' => $decision, 'decided_by' => $staffId, 'decided_at' => now(),
                'amount' => $providerShare, 'outcome_ledger_tx_id' => $result['transaction_id'], 'updated_at' => now(),
            ]);

            return $result;
        });
    }
}
