<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard');
    }

    /**
     * Admin Control Center. Phase 1 starts with platform/identity KPIs; the
     * operations tiles (active deliveries, drivers online, revenue, disputes ...)
     * are filled in as the Deliveries, Dispatch and Payments modules land.
     */
    public function index()
    {
        $stats = [
            'users'     => User::count(),
            'active'    => User::where('is_disabled', false)->count(),
            'online'    => User::where('last_seen_at', '>=', now()->subMinutes(10))->count(),
            'roles'     => DB::table('roles')->count(),
        ];

        $recent = Schema::hasTable('activity_logs')
            ? DB::table('activity_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->orderByDesc('a.id')->limit(10)->get(['a.created_at', 'u.name', 'a.event', 'a.description'])
            : collect();

        return view('dashboards.dashboard', [
            'pagetitle' => 'Control Center',
            'stats'     => $stats,
            'recent'    => $recent,
        ]);
    }
}
