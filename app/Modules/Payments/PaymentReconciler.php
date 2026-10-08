<?php

namespace App\Modules\Payments;

use App\Jobs\ProcessGatewayEvent;
use Illuminate\Support\Facades\DB;

/**
 * Asks the gateway about a payment that has not been confirmed yet. Used when the customer returns from the checkout page,
 * by the app, and by payments:reconcile for webhooks that never arrived. The check itself runs in ProcessGatewayEvent.
 */
class PaymentReconciler
{
    private const GATEWAYS = ['paystack', 'stripe', 'monnify', 'opay'];

    public function check(string $reference): bool
    {
        $intent = DB::table('payment_intents')->where('reference', $reference)->whereIn('status', ['initiated', 'pending'])->first();
        if (! $intent || ! in_array($intent->gateway, self::GATEWAYS, true)) {
            return false;
        }

        return $this->queue($reference, $intent->gateway);
    }

    /** At most one check per payment every 30 seconds, however many pages and webhooks ask. */
    private function queue(string $reference, string $gateway): bool
    {
        $eventId = 'check:'.$reference.':'.intdiv(time(), 30);
        $inserted = DB::table('gateway_events')->insertOrIgnore([
            'gateway' => $gateway, 'event_id' => $eventId, 'type' => 'charge.check',
            'payload' => json_encode(['event' => 'charge.check', 'data' => ['reference' => $reference]]),
            'signature_valid' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($inserted) {
            ProcessGatewayEvent::dispatch((int) DB::table('gateway_events')->where('gateway', $gateway)->where('event_id', $eventId)->value('id'));
        }

        return (bool) $inserted;
    }

    /** Checks every unconfirmed online payment started in the last window (minutes), skipping the freshest two minutes. */
    public function sweep(): int
    {
        $n = 0;
        DB::table('payment_intents')->whereIn('status', ['initiated', 'pending'])->whereIn('gateway', self::GATEWAYS)
            ->where('created_at', '<', now()->subMinutes(2))->where('created_at', '>', now()->subMinutes((int) config('payments.reconcile_window_minutes', 2880)))
            ->orderBy('id')->limit(200)->get(['reference', 'gateway'])
            ->each(function ($i) use (&$n) {
                $n += (int) $this->queue($i->reference, $i->gateway);
            });

        return $n;
    }
}
