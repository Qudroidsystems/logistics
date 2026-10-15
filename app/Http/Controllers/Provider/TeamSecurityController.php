<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Security\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** A provider or merchant owner (or admin) requiring two-step sign-in for everyone on their team except drivers. */
class TeamSecurityController extends Controller
{
    public function save(Request $request, TwoFactorService $tf)
    {
        $user = $request->user();
        $opId = (int) $user->current_operator_id;
        $role = $opId ? DB::table('operator_members')->where(['operator_id' => $opId, 'user_id' => $user->id, 'status' => 'active'])->value('role') : null;
        abort_unless(in_array($role, ['owner', 'admin'], true), 403, 'Only the owner or an admin can change this.');
        $on = $request->boolean('required');
        if ($on && ! $tf->enabled($user)) {
            return back()->with('error', 'Turn on two-step sign-in for yourself first (Sign-in security page), so you are not locked out.');
        }
        $tf->putSetting(TwoFactorService::KEY_TEAM, $on, $user->id, $opId);

        return back()->with('success', $on ? 'Your team must now use two-step sign-in. Drivers are not affected.' : 'Two-step sign-in is now each person\'s choice.');
    }
}
