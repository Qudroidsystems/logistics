<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Team\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class TeamController extends Controller
{
    use ResolvesOperator;

    private const MANAGERS = ['owner', 'admin', 'driver_manager'];

    public function __construct(private TeamService $team)
    {
    }

    public function index(Request $request)
    {
        $op = $this->operatorId($request, self::MANAGERS);

        return response()->json(['members' => $this->team->members($op), 'invitations' => $this->team->invitations($op), 'my_role' => $this->role($request, $op)]);
    }

    public function invite(Request $request)
    {
        $op = $this->operatorId($request, self::MANAGERS);
        $d = $request->validate(['email' => 'required|email:rfc|max:190', 'role' => ['required', Rule::in(TeamService::ROLES)]]);

        return $this->run(fn () => ['invitation' => $this->team->invite($op, $request->user()->id, $this->role($request, $op), $d['email'], $d['role'])], 201);
    }

    public function revoke(Request $request, string $invitation)
    {
        $op = $this->operatorId($request, self::MANAGERS);

        return $this->run(function () use ($request, $op, $invitation) {
            $this->team->revoke($op, $this->role($request, $op), $invitation);

            return ['ok' => true];
        });
    }

    public function changeRole(Request $request, int $user)
    {
        $op = $this->operatorId($request, self::MANAGERS);
        $d = $request->validate(['role' => ['required', Rule::in(TeamService::ROLES)]]);

        return $this->run(function () use ($request, $op, $user, $d) {
            $this->team->changeRole($op, $this->role($request, $op), $user, $d['role']);

            return ['ok' => true];
        });
    }

    public function remove(Request $request, int $user)
    {
        $op = $this->operatorId($request, self::MANAGERS);
        abort_if($user === $request->user()->id, 422, 'You cannot remove yourself.');

        return $this->run(function () use ($request, $op, $user) {
            $this->team->remove($op, $this->role($request, $op), $user);

            return ['ok' => true];
        });
    }

    public function driverStatus(Request $request, int $user)
    {
        $op = $this->operatorId($request, self::MANAGERS);
        $d = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])]]);

        return $this->run(function () use ($op, $user, $d) {
            $this->team->setDriverStatus($op, $user, $d['status']);

            return ['ok' => true];
        });
    }

    public function driverPay(Request $request, int $user)
    {
        $op = $this->operatorId($request, self::MANAGERS);
        $d = $request->validate(['percent' => 'required|numeric|between:0,100']);

        return $this->run(function () use ($op, $user, $d) {
            $this->team->setDriverPay($op, $user, (float) $d['percent']);

            return ['ok' => true];
        });
    }

    public function assignVehicle(Request $request, int $user)
    {
        $op = $this->operatorId($request, self::MANAGERS);
        $d = $request->validate(['vehicle_id' => 'required|string|size:26']);

        return $this->run(function () use ($op, $user, $d) {
            $this->team->assignVehicle($op, $user, $d['vehicle_id']);

            return ['ok' => true];
        });
    }

    // ---- for the person being invited (any signed-in user)

    public function myInvitations(Request $request)
    {
        return response()->json($this->team->mine($request->user()->id));
    }

    public function accept(Request $request)
    {
        $d = $request->validate(['token' => 'required_without:invitation|nullable|string|max:60', 'invitation' => 'required_without:token|nullable|string|size:26']);

        return $this->run(function () use ($request, $d) {
            $op = $this->team->accept($request->user()->id, $d['token'] ?? null, $d['invitation'] ?? null);

            return ['operator' => DB::table('operators')->where('id', $op)->first(['public_id', 'type', 'display_name'])];
        });
    }

    // ----------------------------------------------------------------

    private function role(Request $request, int $op): string
    {
        return (string) DB::table('operator_members')->where(['operator_id' => $op, 'user_id' => $request->user()->id, 'status' => 'active'])->value('role');
    }

    private function run(callable $fn, int $code = 200)
    {
        try {
            return response()->json($fn(), $code);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_complete', 'message' => $e->getMessage()], 422);
        }
    }
}
