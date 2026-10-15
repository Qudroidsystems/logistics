<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Security\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Staff page for sign-in rules: who must use two-step sign-in, who has it on, and resetting it for someone who lost their phone. */
class SecurityController extends Controller
{
    public function __construct(private TwoFactorService $tf)
    {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->canAny(['Manage security settings', 'Reset two-factor']), 403);

        $staff = DB::table('users as u')->whereExists(fn ($q) => $q->from('model_has_roles as r')->whereColumn('r.model_id', 'u.id')->where('r.model_type', User::class))
            ->orderBy('u.name')->get(['u.id', 'u.name', 'u.email', 'u.two_factor_confirmed_at', 'u.is_disabled']);
        $partners = DB::table('operator_members as m')->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.status', 'active')->where('m.role', '!=', 'driver')
            ->selectRaw('count(distinct u.id) as people, count(distinct u.id) filter (where u.two_factor_confirmed_at is not null) as with_two_factor')->first();

        return view('admin.security', [
            'pagetitle' => 'Sign-in security',
            'staffRequired' => $this->tf->setting(TwoFactorService::KEY_STAFF),
            'partnersRequired' => $this->tf->setting(TwoFactorService::KEY_PARTNERS),
            'staff' => $staff, 'partners' => $partners,
            'canManage' => $request->user()->can('Manage security settings'),
            'canReset' => $request->user()->can('Reset two-factor'),
        ]);
    }

    public function save(Request $request)
    {
        abort_unless($request->user()->can('Manage security settings'), 403);
        $request->validate(['staff' => 'nullable|in:1', 'partners' => 'nullable|in:1']);

        $this->tf->putSetting(TwoFactorService::KEY_STAFF, $request->boolean('staff'), $request->user()->id);
        $this->tf->putSetting(TwoFactorService::KEY_PARTNERS, $request->boolean('partners'), $request->user()->id);

        return back()->with('success', 'Sign-in rules saved. People who must now use two-step sign-in are asked to set it up the next time they open a page.');
    }

    /** Lost phone: turn it off so they can sign in with their password and set it up again. */
    public function reset(Request $request, int $user)
    {
        abort_unless($request->user()->can('Reset two-factor'), 403);
        $target = User::findOrFail($user);
        abort_if($target->id === $request->user()->id, 422, 'Use your own Sign-in security page.');
        if ($target->hasRole('Super Admin') && ! $request->user()->hasRole('Super Admin')) {
            abort(403, 'Only a Super Admin can reset a Super Admin.');
        }
        $this->tf->disable($target, 'two_factor.reset_by_staff');

        return back()->with('success', 'Two-step sign-in reset for '.$target->name.'. They sign in with their password and set it up again.');
    }
}
