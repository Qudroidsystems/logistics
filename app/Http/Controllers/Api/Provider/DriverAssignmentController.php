<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Dispatch\ManualAssignmentService;
use Illuminate\Http\Request;
use RuntimeException;

class DriverAssignmentController extends Controller
{
    use ResolvesOperator;

    private const DISPATCHERS = ['owner', 'admin', 'dispatcher'];

    public function __construct(private ManualAssignmentService $svc)
    {
    }

    /** The drivers a job can be given to. */
    public function drivers(Request $request)
    {
        return response()->json($this->svc->drivers($this->operatorId($request, self::DISPATCHERS)));
    }

    /** A team driver, or (for a solo rider or shopper) yourself. */
    public function assign(Request $request, string $shipment)
    {
        $op = $this->operatorId($request, self::DISPATCHERS);
        $d = $request->validate(['driver_user_id' => 'required|integer', 'reason' => 'nullable|string|max:120']);
        try {
            $id = $this->svc->assign($op, $shipment, (int) $d['driver_user_id'], $request->user()->id, $d['reason'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_assign', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'assignment_id' => $id]);
    }
}
