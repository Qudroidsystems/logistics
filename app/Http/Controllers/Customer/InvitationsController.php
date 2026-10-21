<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Team\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Team invitations on the web: a company invites someone by email (as a driver, dispatcher, ...), and the invited
 * person accepts here, either from the list of invitations sent to their email or by typing the emailed code.
 */
class InvitationsController extends Controller
{
    public function __construct(private TeamService $team)
    {
    }

    public function index(Request $request)
    {
        return view('account.invitations', [
            'invitations' => $this->team->mine($request->user()->id),
            'email' => $request->user()->email,
            'pagetitle' => 'Team invitations',
        ]);
    }

    public function accept(Request $request)
    {
        $d = $request->validate([
            'token' => 'required_without:invitation|nullable|string|max:60',
            'invitation' => 'required_without:token|nullable|string|size:26',
        ]);

        try {
            $opId = $this->team->accept($request->user()->id, isset($d['token']) ? trim($d['token']) : null, $d['invitation'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $op = DB::table('operators')->where('id', $opId)->first(['display_name']);
        $role = (string) DB::table('operator_members')->where(['operator_id' => $opId, 'user_id' => $request->user()->id])->value('role');
        $name = $op->display_name ?? 'the company';

        // A driver has no driver pages until the company approves them, so they stay here with what happens next.
        if ($role === 'driver') {
            return redirect()->route('account.invitations')->with('success', "You joined {$name} as a driver. Download the driver app and sign in with this account; you can take jobs as soon as {$name} approves you.");
        }

        return redirect()->route('provider.dashboard')->with('success', "You joined {$name}.");
    }
}
