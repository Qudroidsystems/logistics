<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Tracking\FleetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** The staff console's live fleet map: drivers of every provider, or of one provider picked from the list. */
class FleetController extends Controller
{
    public function index(Request $request)
    {
        return view('ops.fleet', [
            'liveUrl' => route('ops.fleet.live'),
            'trailUrl' => route('ops.fleet.trail', ['driver' => '__ID__']),
            'jobUrl' => route('ops.order', ['order' => '__ID__']),
            'jobKey' => 'order_id',
            'showCompany' => true,
            'providers' => DB::table('operators')->whereIn('id', DB::table('driver_profiles')->whereNull('deleted_at')->select('operator_id'))
                ->orderBy('display_name')->get(['public_id', 'display_name']),
            'pagetitle' => 'Live fleet',
        ]);
    }

    public function live(Request $request, FleetService $fleet)
    {
        return response()->json($fleet->snapshot($this->provider($request), $request->query('q')));
    }

    public function trail(Request $request, string $driver, FleetService $fleet)
    {
        $points = $fleet->trail($driver, null, (int) $request->query('minutes', 60));
        abort_if($points === null, 404);

        return response()->json(['points' => $points]);
    }

    private function provider(Request $request): ?int
    {
        $p = (string) $request->query('provider', '');

        return $p === '' ? null : ((int) DB::table('operators')->where('public_id', $p)->value('id') ?: -1);
    }
}
