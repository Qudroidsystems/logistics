<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGatewayEvent;
use App\Modules\Payments\Gateways\MonnifyGateway;
use App\Modules\Payments\Gateways\PaystackGateway;
use App\Modules\Payments\Gateways\StripeGateway;
use App\Modules\Payments\PaymentReconciler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The webhook only authenticates and records. All money logic runs in ProcessGatewayEvent so a slow
 * ledger write can never make the gateway time out and retry.
 */
class PaymentWebhookController extends Controller
{
    public function paystack(Request $request, PaystackGateway $paystack)
    {
        $raw = $request->getContent();
        if (! $paystack->validSignature($raw, $request->header('x-paystack-signature'))) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $body = json_decode($raw, true) ?: [];
        $type = (string) ($body['event'] ?? 'unknown');
        $data = $body['data'] ?? [];
        // Paystack has no top-level event id; transaction id + event name is stable across retries.
        $eventId = $type.':'.($data['id'] ?? $data['reference'] ?? sha1($raw));

        $inserted = DB::table('gateway_events')->insertOrIgnore([
            'gateway' => 'paystack', 'event_id' => $eventId, 'type' => $type, 'payload' => $raw,
            'signature_valid' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        if ($inserted) {
            $id = DB::table('gateway_events')->where('gateway', 'paystack')->where('event_id', $eventId)->value('id');
            ProcessGatewayEvent::dispatch($id);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Stripe: signed with the webhook secret. A payment event only names the payment; ProcessGatewayEvent asks Stripe what happened.
     * refund.updated is mapped to the refund events RefundService already understands.
     */
    public function stripe(Request $request, StripeGateway $stripe)
    {
        $raw = $request->getContent();
        if (! $stripe->validSignature($raw, $request->header('Stripe-Signature'))) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }
        $event = json_decode($raw, true) ?: [];
        $object = $event['data']['object'] ?? [];
        $type = (string) ($event['type'] ?? '');

        if (str_starts_with($type, 'checkout.session.') && ! empty($object['client_reference_id'])) {
            $this->record('stripe', (string) ($event['id'] ?? sha1($raw)), 'charge.check', ['event' => 'charge.check', 'data' => ['reference' => $object['client_reference_id']]]);
        } elseif ($type === 'refund.updated' && in_array($object['status'] ?? '', ['succeeded', 'failed'], true)) {
            $this->record('stripe', (string) ($event['id'] ?? sha1($raw)), $object['status'] === 'succeeded' ? 'refund.processed' : 'refund.failed', ['event' => 'refund', 'data' => ['id' => $object['id'] ?? '']]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Monnify (Moniepoint). The signature is checked when Monnify sends one (production), but it is never what decides:
     * the reference is re-verified against Monnify's API before any money is credited.
     */
    public function monnify(Request $request, MonnifyGateway $monnify)
    {
        $raw = $request->getContent();
        $sig = $request->header('monnify-signature');
        if ($sig && ! $monnify->validSignature($raw, $sig)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }
        $body = json_decode($raw, true) ?: [];
        $d = $body['eventData'] ?? [];
        $ref = (string) ($d['paymentReference'] ?? $d['product']['reference'] ?? '');
        if ($ref !== '') {
            $this->record('monnify', ($body['eventType'] ?? 'event').':'.($d['transactionReference'] ?? $ref), 'charge.check', ['event' => 'charge.check', 'data' => ['reference' => $ref]]);
        }

        return response()->json(['ok' => true]);
    }

    /** OPay callback. Only names the payment; the status is re-queried from OPay. */
    public function opay(Request $request)
    {
        $p = (array) ($request->input('payload') ?? []);
        $ref = (string) ($p['reference'] ?? '');
        if ($ref !== '') {
            $this->record('opay', $ref.':'.($p['status'] ?? ''), 'charge.check', ['event' => 'charge.check', 'data' => ['reference' => $ref]]);
        }

        return response()->json(['ok' => true]);
    }

    /** Stores a normalised event once (gateways retry) and queues it. */
    private function record(string $gateway, string $eventId, string $type, array $payload): void
    {
        $eventId = mb_substr($eventId, 0, 120);
        $inserted = DB::table('gateway_events')->insertOrIgnore([
            'gateway' => $gateway, 'event_id' => $eventId, 'type' => $type, 'payload' => json_encode($payload),
            'signature_valid' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($inserted) {
            ProcessGatewayEvent::dispatch((int) DB::table('gateway_events')->where('gateway', $gateway)->where('event_id', $eventId)->value('id'));
        }
    }

    /** Browser return URL. Never trusted for payment status; the webhook is the source of truth. */
    public function callback(Request $request, PaymentReconciler $reconciler)
    {
        // The customer is back from the checkout page: ask the gateway now instead of waiting for its webhook.
        $ref = (string) ($request->query('reference') ?: $request->query('trxref') ?: $request->query('paymentReference'));
        if ($ref !== '') {
            $reconciler->check($ref);
        }

        $to = auth()->check() && ! auth()->user()->can('dashboard') ? route('account.orders') : route('home');

        return redirect($to)->with('success', 'Payment received. We are confirming it now.');
    }
}
