<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Ratings\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RatingController extends Controller
{
    use ResolvesOperator;

    /** Provider rates the customer after a delivery. Private: only used for risk and dispatch decisions. */
    public function rateCustomer(Request $request, string $shipment, RatingService $ratings)
    {
        $op = $this->operatorId($request, ['owner', 'admin', 'dispatcher']);
        $d = $request->validate(['rating' => 'required|integer|min:1|max:5', 'tags' => 'array|max:5', 'tags.*' => 'string|max:24', 'comment' => 'nullable|string|max:500']);
        $id = DB::table('shipments')->where('public_id', $shipment)->where('operator_id', $op)->value('id');
        abort_unless($id, 404);

        try {
            $ratings->rate((int) $id, $request->user()->id, 'provider', (int) $d['rating'], $d['tags'] ?? [], $d['comment'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_rate', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['rated' => true], 201);
    }

    /** Public provider page: numbers and recent reviews. */
    public function providerPage(string $slug, RatingService $ratings)
    {
        $p = DB::table('provider_profiles as p')->join('operators as o', 'o.id', '=', 'p.operator_id')->where('p.public_slug', $slug)->where('p.listed', true)->where('o.status', 'active')
            ->first(['p.operator_id', 'o.display_name', 'p.headline', 'p.about', 'p.tier', 'p.rating_avg', 'p.rating_count', 'p.jobs_completed', 'p.completion_rate', 'p.years_operating', 'p.languages', 'p.verified_badges']);
        abort_unless($p, 404);

        return response()->json([
            'provider' => collect((array) $p)->except('operator_id')->all(),
            'reviews' => $ratings->publicReviews((int) $p->operator_id),
        ]);
    }
}
