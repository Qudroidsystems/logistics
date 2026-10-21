<?php

namespace App\Http\Controllers;

use App\Modules\Payments\ETransferService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Public: the "how to pay by e-Transfer" page for one payment, reached from the pay screen (web or app) and the email.
 * The reference is long and random, so knowing it is what lets someone see the page; it shows nothing personal.
 */
class ETransferPageController extends Controller
{
    public function show(string $reference, ETransferService $etransfers)
    {
        $p = $etransfers->forReference($reference);
        abort_unless($p, 404);

        return view('payments.etransfer', ['p' => $p, 'pagetitle' => 'Pay by e-Transfer']);
    }

    public function sent(Request $request, string $reference, ETransferService $etransfers)
    {
        $d = $request->validate(['sender_name' => 'nullable|string|max:120']);
        try {
            $etransfers->markSent($reference, $d['sender_name'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Thanks. We will confirm as soon as it arrives, usually within a few hours, and email you.');
    }
}
