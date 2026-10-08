<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\RatingController;
use App\Modules\Ratings\RatingService;
use Illuminate\Support\Facades\DB;

/**
 * The public page for a listed provider: standing, numbers and recent reviews. Anyone can read it.
 * It shows the same data as the public JSON endpoint, so what is public is decided in one place.
 */
class ProviderPageController extends Controller
{
    public function show(string $slug, RatingController $api, RatingService $ratings)
    {
        $data = $api->providerPage($slug, $ratings)->getData(true);
        $p = $data['provider'];
        $listJson = fn ($v) => is_string($v) ? (json_decode($v, true) ?: []) : ((array) $v);

        return view('providers.show', [
            'p' => $p,
            'badges' => $listJson($p['verified_badges'] ?? null),
            'languages' => $listJson($p['languages'] ?? null),
            'reviews' => $data['reviews'],
            'operatorId' => (int) DB::table('provider_profiles')->where('public_slug', $slug)->value('operator_id'),
            'slug' => $slug,
        ]);
    }
}
