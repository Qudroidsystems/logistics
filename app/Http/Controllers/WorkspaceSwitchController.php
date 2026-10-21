<?php

namespace App\Http\Controllers;

use App\Support\Workspaces;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** POST /workspace: move to another place this login works in (back office, a provider, a shop, driver, own account). */
class WorkspaceSwitchController extends Controller
{
    public function __invoke(Request $request)
    {
        $d = $request->validate(['key' => 'required|string|max:40']);
        $user = $request->user();
        $target = collect(Workspaces::for($user))->firstWhere('key', $d['key']);
        abort_unless($target, 403, 'You do not have access to that workspace.');
        if ($target['operator_id']) {
            DB::table('users')->where('id', $user->id)->update(['current_operator_id' => $target['operator_id'], 'updated_at' => now()]);
        }

        return redirect($target['url'])->with('success', "You are now working in {$target['label']}.");
    }
}
