<?php

namespace App\Http\Controllers;

use App\Modules\Eta\ReceiverReplies;
use App\Modules\Tracking\TrackingService;
use Illuminate\Http\Request;

/**
 * The public tracking page a customer or receiver opens from a link. No sign-in: the long random token is the key,
 * and the page only ever shows what TrackingService::snapshot allows (no phone numbers, price or delivery code).
 */
class TrackPageController extends Controller
{
    public function show(string $token, TrackingService $tracking)
    {
        $snap = $tracking->snapshot($token);
        abort_unless($snap, 404);

        return response()->view('track.show', ['token' => $token, 'snap' => $snap])
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** The tracking page's "tell the rider" buttons. */
    public function reply(Request $request, string $token, TrackingService $tracking, ReceiverReplies $replies)
    {
        $d = $request->validate(['reply' => 'required|string|in:'.implode(',', array_keys(ReceiverReplies::OPTIONS)), 'note' => 'nullable|string|max:140']);
        $id = $tracking->linkShipment($token);
        abort_unless($id, 404);
        try {
            $replies->reply($id, $d['reply'], $d['note'] ?? null);
        } catch (\RuntimeException $e) {
            return redirect()->route('track', $token)->with('reply_error', $e->getMessage());
        }

        return redirect()->route('track', $token)->with('reply_ok', 'The rider has been told.');
    }
}
