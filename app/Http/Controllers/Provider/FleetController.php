<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Tracking\FleetService;
use Illuminate\Http\Request;

/**
 * The provider's live fleet map: every driver on the team, where they are and what they are doing.
 * Open to the people who dispatch work (owner, admin, dispatcher) and to the driver manager.
 */
class FleetController extends WorkspaceController
{
    public const ROLES = ['owner', 'admin', 'dispatcher', 'driver_manager'];

    /** @return object the operator, after checking the role may see the fleet */
    private function operator(Request $request): object
    {
        [$op, $role] = $this->ctx($request);
        abort_unless(in_array($role, self::ROLES, true), 403, 'Your role does not include the fleet map.');

        return $op;
    }

    public function index(Request $request)
    {
        $this->operator($request);

        return $this->view('provider.fleet', $request, [
            'liveUrl' => route('provider.fleet.live'),
            'trailUrl' => route('provider.fleet.trail', ['driver' => '__ID__']),
            'jobUrl' => route('provider.job', ['shipment' => '__ID__']),
            'jobKey' => 'id',
            'showCompany' => false,
            'pagetitle' => 'Live fleet',
        ]);
    }

    public function live(Request $request, FleetService $fleet)
    {
        return response()->json($fleet->snapshot($this->operator($request)->id, $request->query('q')));
    }

    public function trail(Request $request, string $driver, FleetService $fleet)
    {
        $points = $fleet->trail($driver, $this->operator($request)->id, (int) $request->query('minutes', 60));
        abort_if($points === null, 404);

        return response()->json(['points' => $points]);
    }
}
