<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Tracking\FleetService;
use Illuminate\Http\Request;

/** The live fleet for provider apps and integrations: GET /v1/provider/fleet and a driver's recent path. */
class FleetController extends Controller
{
    use ResolvesOperator;

    private const ROLES = ['owner', 'admin', 'dispatcher', 'driver_manager'];

    public function index(Request $request, FleetService $fleet)
    {
        return response()->json($fleet->snapshot($this->operatorId($request, self::ROLES), $request->query('q')));
    }

    public function trail(Request $request, string $driver, FleetService $fleet)
    {
        $points = $fleet->trail($driver, $this->operatorId($request, self::ROLES), (int) $request->query('minutes', 60));
        abort_if($points === null, 404, 'No such driver on your team.');

        return response()->json(['points' => $points]);
    }
}
