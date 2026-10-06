<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Placeholder endpoints so the Payment Gateways admin page can show the URLs to
 * register with Paystack / OPay. The real handlers (signature verification,
 * idempotent wallet credit / order payment) arrive with the Payments module.
 */
class PaymentWebhookController extends Controller
{
    public function paystack(Request $request)
    {
        return response()->json(['message' => 'Payments module not installed yet.'], 501);
    }

    public function opay(Request $request)
    {
        return response()->json(['message' => 'Payments module not installed yet.'], 501);
    }

    public function callback(Request $request)
    {
        return redirect()->route('home')->with('warning', 'Payments module not installed yet.');
    }
}
