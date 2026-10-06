<?php

namespace App\Http\Controllers;

use App\Services\Activity\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Staff activity log and who's online. */
class ActivityLogController extends Controller
{
    public const ONLINE_MINUTES = 5;

    public function __construct()
    {
        $this->middleware('permission:View activity log')->only(['index', 'export']);
        $this->middleware('permission:View online staff|View activity log')->only(['online', 'onlineCount']);
    }

    protected function query(Request $request)
    {
        return DB::table('activity_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->when($request->filled('user'), fn ($q) => $q->where('a.user_id', (int) $request->user))
            ->when($request->filled('event'), fn ($q) => $q->where('a.event', $request->event))
            ->when($request->filled('from'), fn ($q) => $q->where('a.created_at', '>=', Carbon::parse($request->from)->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('a.created_at', '<=', Carbon::parse($request->to)->endOfDay()))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('a.description', 'like', '%' . $request->search . '%')
                ->orWhere('u.name', 'like', '%' . $request->search . '%')->orWhere('a.ip', 'like', '%' . $request->search . '%')))
            ->orderByDesc('a.id');
    }

    public function index(Request $request)
    {
        $logs = $this->query($request)->select('a.*', 'u.name', 'u.email')->paginate(40)->withQueryString();
        $today = now()->startOfDay();
        return view('activity.index', [
            'pagetitle' => 'Staff Activity Log', 'logs' => $logs,
            'users' => DB::table('users')->whereNull('student_id')->orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'logins' => DB::table('activity_logs')->where('event', 'login')->where('created_at', '>=', $today)->count(),
                'people' => DB::table('activity_logs')->where('event', 'login')->where('created_at', '>=', $today)->distinct()->count('user_id'),
                'changes' => DB::table('activity_logs')->whereIn('event', ['create', 'update', 'delete', 'action'])->where('created_at', '>=', $today)->count(),
                'failed' => DB::table('activity_logs')->where('event', 'login_failed')->where('created_at', '>=', $today)->count(),
            ],
            'person' => $request->filled('user') ? DB::table('users')->where('id', (int) $request->user)->first(['id', 'name', 'email', 'last_seen_at', 'last_login_at', 'last_login_ip']) : null,
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->limit(20000)->get(['a.created_at', 'u.name', 'a.event', 'a.description', 'a.method', 'a.url', 'a.status', 'a.ip', 'a.device']);
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Time', 'User', 'Event', 'What', 'Method', 'Address', 'Result', 'IP', 'Device']);
            foreach ($rows as $r) fputcsv($out, [$r->created_at, $r->name, $r->event, $r->description, $r->method, $r->url, $r->status, $r->ip, $r->device]);
            fclose($out);
        }, 'activity_log_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function online()
    {
        $since = now()->subMinutes(self::ONLINE_MINUTES);
        $staffOnly = fn ($q) => $q->whereNull('u.student_id')
            ->whereNotExists(fn ($x) => $x->from('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->whereColumn('mr.model_id', 'u.id')->where('mr.model_type', \App\Models\User::class)->whereIn('r.name', ['Student', 'Parent']));

        $online = DB::table('users as u')->where('u.last_seen_at', '>=', $since)->tap($staffOnly)->orderByDesc('u.last_seen_at')
            ->get(['u.id', 'u.name', 'u.email', 'u.last_seen_at', 'u.last_seen_url', 'u.last_login_at', 'u.last_login_ip']);
        $others = DB::table('users as u')->where('u.last_seen_at', '>=', $since)->count() - $online->count();

        $roles = DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')->where('mr.model_type', \App\Models\User::class)
            ->whereIn('mr.model_id', $online->pluck('id'))->get(['mr.model_id', 'r.name'])->groupBy('model_id')->map(fn ($g) => $g->pluck('name')->implode(', '));
        $devices = DB::table('activity_logs')->where('event', 'login')->whereIn('user_id', $online->pluck('id'))->orderByDesc('id')->get(['user_id', 'device'])->unique('user_id')->pluck('device', 'user_id');

        $todayLogins = DB::table('activity_logs as a')->join('users as u', 'u.id', '=', 'a.user_id')->where('a.event', 'login')->where('a.created_at', '>=', now()->startOfDay())
            ->tap($staffOnly)->groupBy('a.user_id', 'u.name')->selectRaw('a.user_id, u.name, MIN(a.created_at) first_in, MAX(a.created_at) last_in, COUNT(*) times')->orderBy('first_in')->get();

        return view('activity.online', ['pagetitle' => "Who's Online", 'online' => $online, 'roles' => $roles, 'devices' => $devices,
            'others' => max(0, $others), 'todayLogins' => $todayLogins, 'minutes' => self::ONLINE_MINUTES]);
    }

    /** JSON count for the top-bar badge. */
    public function onlineCount()
    {
        $n = DB::table('users')->whereNull('student_id')->where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_MINUTES))->count();
        return response()->json(['count' => $n]);
    }
}
