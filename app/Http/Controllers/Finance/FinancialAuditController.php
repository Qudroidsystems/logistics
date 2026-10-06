<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialAuditLog;
use App\Services\Audit\FinancialAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialAuditController extends Controller
{
    public function __construct(protected FinancialAuditService $svc)
    {
        $this->middleware('auth');
        $this->middleware('permission:View financial audit')->only(['dashboard', 'trail', 'exceptions', 'users', 'exportTrail']);
        $this->middleware('permission:Clear audit exceptions')->only(['review']);
    }

    protected function range(Request $request): array
    {
        $from = $request->filled('from') ? $request->from : Carbon::now()->startOfYear()->toDateString();
        $to   = $request->filled('to') ? $request->to : Carbon::now()->toDateString();
        return [$from, $to];
    }

    public function dashboard(Request $request)
    {
        [$from, $to] = $this->range($request);
        $summary = $this->svc->summary($from, $to);
        $exceptions = $this->svc->exceptions();

        return view('finance.audit.dashboard', [
            'pagetitle'  => 'Financial Audit',
            'from' => $from, 'to' => $to,
            'summary'    => $summary,
            'openExceptions' => $exceptions->where('status', 'open')->take(12)->values(),
            'kinds'      => FinancialAuditService::KINDS,
        ]);
    }

    public function trail(Request $request)
    {
        [$from, $to] = $this->range($request);
        $q = FinancialAuditLog::query()
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($request->filled('module'), fn ($x) => $x->where('auditable_type', $request->module))
            ->when($request->filled('event'), fn ($x) => $x->where('event', $request->event))
            ->when($request->filled('user'), fn ($x) => $x->where('user_id', $request->user))
            ->when($request->filled('q'), fn ($x) => $x->where(fn ($w) =>
                $w->where('summary', 'like', "%{$request->q}%")->orWhere('ref', 'like', "%{$request->q}%")->orWhere('user_name', 'like', "%{$request->q}%")))
            ->orderByDesc('created_at');

        return view('finance.audit.trail', [
            'pagetitle' => 'Audit Trail',
            'from' => $from, 'to' => $to,
            'rows'    => $q->paginate(40)->withQueryString(),
            'modules' => FinancialAuditLog::select('auditable_type', 'model_label')->distinct()->orderBy('model_label')->get()->pluck('model_label', 'auditable_type'),
            'users'   => FinancialAuditLog::whereNotNull('user_id')->select('user_id', 'user_name')->distinct()->orderBy('user_name')->get()->pluck('user_name', 'user_id'),
            'events'  => FinancialAuditLog::EVENTS,
        ]);
    }

    public function exceptions(Request $request)
    {
        $items = $this->svc->exceptions([
            'kind' => $request->query('kind'),
            'include_cleared' => $request->boolean('include_cleared'),
        ]);
        return view('finance.audit.exceptions', [
            'pagetitle' => 'Audit Exceptions',
            'groups'    => $items->groupBy('kind'),
            'kinds'     => FinancialAuditService::KINDS,
            'activeKind'=> $request->query('kind'),
            'includeCleared' => $request->boolean('include_cleared'),
            'total'     => $items->count(),
        ]);
    }

    public function review(Request $request)
    {
        $d = $request->validate([
            'kind' => 'required|string|max:40',
            'key'  => 'required|string|max:191',
            'status' => 'required|in:open,cleared,flagged',
            'note' => 'nullable|string|max:500',
        ]);
        $this->svc->review($d['kind'], $d['key'], $d['status'], $d['note'] ?? null, (int) $request->user()->id);
        return back()->with('success', 'Exception marked as ' . $d['status'] . '.');
    }

    public function users(Request $request)
    {
        [$from, $to] = $this->range($request);

        $activity = FinancialAuditLog::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw("user_id, user_name,
                SUM(CASE WHEN event='created' THEN 1 ELSE 0 END) created,
                SUM(CASE WHEN event='updated' THEN 1 ELSE 0 END) updated,
                SUM(CASE WHEN event='deleted' THEN 1 ELSE 0 END) deleted,
                COUNT(*) total, SUM(COALESCE(amount,0)) amount")
            ->groupBy('user_id', 'user_name')->orderByDesc('total')->get();

        // Segregation of duties on expense vouchers: who requested vs approved vs paid.
        $sod = [];
        if (Schema::hasTable('expense_vouchers')) {
            $sod = [
                'requested' => DB::table('expense_vouchers as v')->join('users as u', 'u.id', '=', 'v.requested_by')
                    ->whereBetween('v.expense_date', [$from, $to])->groupBy('u.id', 'u.name')
                    ->selectRaw('u.name, COUNT(*) n, SUM(v.amount) total')->orderByDesc('n')->limit(10)->get(),
                'approved' => DB::table('expense_vouchers as v')->join('users as u', 'u.id', '=', 'v.approved_by')
                    ->whereBetween('v.expense_date', [$from, $to])->whereNotNull('v.approved_by')->groupBy('u.id', 'u.name')
                    ->selectRaw('u.name, COUNT(*) n, SUM(v.amount) total')->orderByDesc('n')->limit(10)->get(),
            ];
        }

        return view('finance.audit.users', [
            'pagetitle' => 'Who did what (financial)',
            'from' => $from, 'to' => $to, 'activity' => $activity, 'sod' => $sod,
        ]);
    }

    public function exportTrail(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = FinancialAuditLog::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('created_at')->limit(20000)->get();
        return response()->streamDownload(function () use ($rows) {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['When', 'User', 'Action', 'Module', 'Reference', 'Amount', 'Summary', 'Changes', 'IP']);
            foreach ($rows as $r) {
                fputcsv($o, [
                    optional($r->created_at)->format('Y-m-d H:i'), $r->user_name, $r->event, $r->model_label,
                    $r->ref, $r->amount, $r->summary,
                    $r->changes ? collect($r->changes)->map(fn ($v, $k) => "$k: " . ($v[0] ?? '') . '→' . ($v[1] ?? ''))->implode('; ') : '',
                    $r->ip,
                ]);
            }
            fclose($o);
        }, 'financial-audit-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
