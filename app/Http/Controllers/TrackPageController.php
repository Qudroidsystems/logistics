<?php

namespace App\Http\Controllers;

use App\Modules\Tracking\TrackingService;

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
}
