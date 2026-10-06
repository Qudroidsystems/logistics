<?php

namespace App\Jobs;

use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketplace\OrderFromAgreement;
use App\Modules\Payments\Escrow\EscrowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessGatewayEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public int $eventId)
    {
    }

    public function handle(EscrowService $escrow, OrderFromAgreement $orders, DispatchService $dispatch): void
    {
        $shipmentId = null;

        DB::transaction(function () use ($escrow, $orders, &$shipmentId) {
            $event = DB::table('gateway_events')->where('id', $this->eventId)->lockForUpdate()->first();
            if (! $event || $event->processed_at) {
                return; // already handled: webhook retries are harmless
            }
            $payload = json_decode($event->payload, true);

            if ($event->type === 'charge.success') {
                $shipmentId = $this->chargeSucceeded($payload['data'], $escrow, $orders);
            }

            $ref = $payload['data']['reference'] ?? '';
            if ($event->type === 'refund.processed') {
                app(\App\Modules\Payments\RefundService::class)->complete((string) ($payload['data']['id'] ?? ''));
            } elseif ($event->type === 'refund.failed') {
                app(\App\Modules\Payments\RefundService::class)->fail((string) ($payload['data']['id'] ?? ''), 'refund.failed');
            }
            if ($event->type === 'transfer.success') {
                app(\App\Modules\Settlements\PayoutService::class)->complete($ref);
            } elseif (in_array($event->type, ['transfer.failed', 'transfer.reversed'], true)) {
                app(\App\Modules\Settlements\PayoutService::class)->fail($ref, (string) ($payload['data']['reason'] ?? $event->type));
            }

            DB::table('gateway_events')->where('id', $event->id)->update(['processed_at' => now(), 'error' => null, 'updated_at' => now()]);
        });

        // Dispatch only after the payment transaction has committed; a driver must never be offered unpaid work.
        if ($shipmentId) {
            $dispatch->start($shipmentId);
        }
    }

    private function chargeSucceeded(array $data, EscrowService $escrow, OrderFromAgreement $orders): ?int
    {
        $intent = DB::table('payment_intents')->where('reference', $data['reference'] ?? '')->lockForUpdate()->first();
        if (! $intent || $intent->status === 'succeeded') {
            return null;
        }
        // Never trust the event alone: amount and currency must match what we asked the customer to pay.
        if ((int) $data['amount'] !== (int) $intent->amount || ($data['currency'] ?? 'NGN') !== $intent->currency) {
            DB::table('payment_intents')->where('id', $intent->id)->update(['status' => 'failed', 'raw' => json_encode($data), 'updated_at' => now()]);
            DB::table('risk_events')->insert(['type' => 'payment_amount_mismatch', 'subject_type' => 'payment_intent', 'subject_id' => $intent->id, 'severity' => 'high', 'evidence' => json_encode($data), 'created_at' => now(), 'updated_at' => now()]);

            return null;
        }

        DB::table('payment_intents')->where('id', $intent->id)->update([
            'status' => 'succeeded', 'paid_at' => now(), 'channel' => $data['channel'] ?? null,
            'authorization_code' => $data['authorization']['authorization_code'] ?? null, 'raw' => json_encode($data), 'updated_at' => now(),
        ]);

        if (! $intent->agreement_id) {
            app(\App\Modules\Payments\WalletService::class)->creditTopUp((int) $intent->id); // wallet top-up

            return null;
        }

        $escrow->hold((int) $intent->agreement_id, 'gateway');
        $made = $orders->create((int) $intent->agreement_id);
        DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);

        return $made['shipment_id'];
    }

    public function failed(Throwable $e): void
    {
        DB::table('gateway_events')->where('id', $this->eventId)->update(['error' => mb_substr($e->getMessage(), 0, 1000), 'updated_at' => now()]);
    }
}
