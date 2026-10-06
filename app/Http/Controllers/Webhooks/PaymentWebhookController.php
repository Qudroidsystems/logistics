<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGatewayEvent;
use App\Modules\Payments\Gateways\PaystackGateway;
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

    public function opay(Request $request)
    {
        return response()->json(['message' => 'OPay gateway not enabled yet.'], 501);
    }

    /** Browser return URL. Never trusted for payment status; the webhook is the source of truth. */
    public function callback(Request $request)
    {
        return redirect()->route('home')->with('success', 'Payment received. We are confirming it now.');
    }
}
