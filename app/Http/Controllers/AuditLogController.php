<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Data changes: one entry per record created, changed or deleted on the audited tables (written by a database
 * trigger), plus security events such as two-step sign-in being turned on or reset. Shows who, what changed (old and
 * new values) and from where. The activity log answers "what did this person do"; this answers "who changed this".
 */
class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View audit log');
    }

    protected function query(Request $request)
    {
        return DB::table('audit_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')->leftJoin('operators as o', 'o.id', '=', 'a.operator_id')
            ->when($request->filled('user'), fn ($q) => $q->where('a.user_id', (int) $request->user))
            ->when($request->filled('table'), fn ($q) => $q->where('a.subject_type', (string) $request->table))
            ->when($request->filled('record'), fn ($q) => $q->where('a.subject_id', (int) $request->record))
            ->when($request->filled('action'), fn ($q) => $q->where('a.action', (string) $request->action))
            ->when($request->filled('request'), fn ($q) => $q->where('a.request_id', (string) $request->request))
            ->when($request->filled('operator'), fn ($q) => $q->where('a.operator_id', (int) $request->operator))
            ->when($request->filled('from'), fn ($q) => $q->where('a.created_at', '>=', Carbon::parse($request->from)->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('a.created_at', '<=', Carbon::parse($request->to)->endOfDay()))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('u.name', 'ilike', '%'.$request->search.'%')
                ->orWhere('a.ip', 'like', '%'.$request->search.'%')->orWhereRaw('a.after::text ilike ?', ['%'.$request->search.'%'])->orWhereRaw('a.before::text ilike ?', ['%'.$request->search.'%'])))
            ->orderByDesc('a.id');
    }

    public function index(Request $request)
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date', 'record' => 'nullable|integer', 'user' => 'nullable|integer', 'operator' => 'nullable|integer']);
        $logs = $this->query($request)->select('a.*', 'u.name', 'u.email', 'o.display_name as operator_name')->paginate(40)->withQueryString();
        $today = now()->startOfDay();

        return view('audit.index', [
            'pagetitle' => 'Data changes', 'logs' => $logs,
            'tables' => DB::table('audit_logs')->where('created_at', '>=', now()->subDays(90))->distinct()->orderBy('subject_type')->pluck('subject_type')->filter()->values(),
            'users' => DB::table('users')->whereIn('id', DB::table('audit_logs')->whereNotNull('user_id')->where('created_at', '>=', now()->subDays(90))->distinct()->select('user_id'))->orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'today' => DB::table('audit_logs')->where('created_at', '>=', $today)->count(),
                'people' => DB::table('audit_logs')->where('created_at', '>=', $today)->whereNotNull('user_id')->distinct()->count('user_id'),
                'deleted' => DB::table('audit_logs')->where('created_at', '>=', $today)->where('action', 'deleted')->count(),
                'security' => DB::table('audit_logs')->where('created_at', '>=', $today)->where('action', 'like', 'two_factor.%')->count(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->limit(20000)->get(['a.created_at', 'u.name', 'o.display_name as operator_name', 'a.action', 'a.subject_type', 'a.subject_id', 'a.before', 'a.after', 'a.ip', 'a.request_id']);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Time', 'User', 'Company', 'Action', 'Table', 'Record', 'Before', 'After', 'IP', 'Request']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->created_at, $r->name ?? 'System', $r->operator_name, $r->action, $r->subject_type, $r->subject_id, $r->before, $r->after, $r->ip, $r->request_id]);
            }
            fclose($out);
        }, 'data_changes_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
