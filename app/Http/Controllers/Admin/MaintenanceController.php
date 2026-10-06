<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Admin-controlled maintenance mode. Turn it on or off, set the message and
 * which roles keep access, and (optionally) plan a switch-on time. Everything
 * here is reversible from this page while logged in.
 */
class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage maintenance mode');
    }

    public function index()
    {
        $m = MaintenanceSetting::current();
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();

        return view('admin.maintenance.index', [
            'pagetitle' => 'Maintenance Mode',
            'm' => $m,
            'roles' => $roles,
            'activator' => $m->activated_by ? DB::table('users')->where('id', $m->activated_by)->value('name') : null,
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:150',
            'message' => 'nullable|string|max:2000',
            'contact_info' => 'nullable|string|max:255',
            'allow_role_ids' => 'nullable|array',
            'allow_role_ids.*' => 'integer|exists:roles,id',
            'retry_after' => 'nullable|integer|min:0|max:10080',
            'scheduled_at' => 'nullable|date|after:now',
            'scheduled_note' => 'nullable|string|max:255',
            'action' => 'required|in:save,turn_on,turn_off,schedule,clear_schedule',
        ]);

        $m = MaintenanceSetting::current();
        $m->fill([
            'title' => $data['title'] ?? $m->title,
            'message' => $data['message'] ?? $m->message,
            'contact_info' => $data['contact_info'] ?? null,
            'allow_role_ids' => array_values(array_map('intval', $data['allow_role_ids'] ?? [])),
            'retry_after' => $data['retry_after'] ?? null,
            'scheduled_note' => $data['scheduled_note'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        $msg = 'Settings saved.';

        switch ($data['action']) {
            case 'turn_on':
                $m->is_active = true;
                $m->activated_at = now();
                $m->activated_by = $request->user()->id;
                $m->scheduled_at = null;
                $msg = 'Maintenance mode is now ON. Only you and the roles you allowed can use the portal.';
                break;

            case 'turn_off':
                $m->is_active = false;
                $m->scheduled_at = null;
                $msg = 'Maintenance mode is OFF. The portal is open to everyone again.';
                break;

            case 'schedule':
                if (empty($data['scheduled_at'])) {
                    return back()->withInput()->with('error', 'Pick a date and time in the future to schedule the switch-on.');
                }
                $m->is_active = false;
                $m->scheduled_at = $data['scheduled_at'];
                $msg = 'Scheduled. The portal will switch to maintenance mode at the time you set — you can change or cancel this any time.';
                break;

            case 'clear_schedule':
                $m->scheduled_at = null;
                $msg = 'Scheduled switch-on cancelled.';
                break;
        }

        $m->save();
        MaintenanceSetting::forget();

        return back()->with('success', $msg);
    }
}
