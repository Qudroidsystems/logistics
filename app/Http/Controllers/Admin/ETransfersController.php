<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Payments\ETransferService;
use App\Support\Money;
use Illuminate\Http\Request;
use RuntimeException;

/** Operations > e-Transfers: match the e-Transfers that arrived in the business's inbox to waiting payments. */
class ETransfersController extends Controller
{
    private function check(Request $request): void
    {
        abort_unless($request->user()->canAny(['Confirm e-transfer', 'Manage payment gateways']), 403, 'You cannot confirm e-Transfers.');
    }

    public function index(Request $request, ETransferService $etransfers)
    {
        abort_unless($request->user()->canAny(['Confirm e-transfer', 'Manage payment gateways', 'View payment']), 403);

        return view('ops.etransfers', [
            'rows' => $etransfers->queue(), 'recent' => $etransfers->recent(), 'to' => $etransfers->gateway()->instructions(),
            'ready' => $etransfers->gateway()->isReady(), 'canConfirm' => $request->user()->canAny(['Confirm e-transfer', 'Manage payment gateways']),
            'pagetitle' => 'e-Transfers',
        ]);
    }

    public function confirm(Request $request, int $intent, ETransferService $etransfers)
    {
        $this->check($request);
        $d = $request->validate(['amount' => 'required|string|max:20', 'interac_ref' => 'nullable|string|max:60']);
        $amount = Money::toMinor($d['amount']);
        if ($amount === null) {
            return back()->with('error', 'Enter the amount you received.');
        }
        try {
            $how = $etransfers->confirm($intent, $request->user()->id, $amount, $d['interac_ref'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $how === 'wallet'
            ? 'Received. The booking had already closed, so the money is in the customer\'s wallet and they have been told.'
            : 'Received. The delivery is going ahead.');
    }

    public function reject(Request $request, int $intent, ETransferService $etransfers)
    {
        $this->check($request);
        $d = $request->validate(['reason' => 'required|string|max:300']);
        try {
            $etransfers->reject($intent, $request->user()->id, $d['reason']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Rejected. The customer has been told why.');
    }
}
