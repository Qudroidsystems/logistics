<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CoopMember;
use App\Models\CoopTransaction;
use App\Services\Loans\CoopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Staff cooperative: members, monthly savings, withdrawals, dividends. */
class CoopController extends Controller
{
    public function __construct(protected CoopService $svc)
    {
        $this->middleware('permission:Manage cooperative|View payroll')->only(['index', 'show']);
        $this->middleware('permission:Manage cooperative')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $balances = CoopTransaction::groupBy('staff_id')->selectRaw("staff_id, SUM(amount) as bal, SUM(CASE WHEN type='contribution' THEN amount ELSE 0 END) as saved")->get()->keyBy('staff_id');
        $members = CoopMember::query()->leftJoin('staffbioinfo as s', 's.id', '=', 'coop_members.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->select('coop_members.*', 'u.name as staff_name')
            ->when($request->filled('status'), fn ($q) => $q->where('coop_members.status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where('u.name', 'like', '%' . $request->q . '%'))
            ->orderBy('u.name')->get();
        $nonMembers = DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->whereNotIn('s.id', CoopMember::pluck('staff_id'))->orderBy('u.name')->get(['s.id', 'u.name']);

        return view('finance.cooperative.index', [
            'pagetitle' => 'Staff Cooperative', 'members' => $members, 'balances' => $balances, 'nonMembers' => $nonMembers,
            'stats' => [
                'fund' => CoopTransaction::sum('amount'), 'members' => CoopMember::where('status', 'active')->count(),
                'monthly' => CoopMember::where('status', 'active')->sum('monthly_contribution'),
                'coop_loans' => \App\Models\LoanAdvance::where('type', 'cooperative')->where('status', 'active')->sum('balance'),
            ],
            'recent' => CoopTransaction::latest('id')->limit(10)->get(),
            'names' => DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->pluck('u.name', 's.id'),
        ]);
    }

    public function show(int $staff)
    {
        $name = DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.id', $staff)->value('u.name');
        abort_unless($name, 404);
        return view('finance.cooperative.show', ['pagetitle' => 'Cooperative · ' . $name, 'staffId' => $staff, 'name' => $name] + $this->svc->statement($staff));
    }

    public function member(Request $request)
    {
        $d = $request->validate(['staff_id' => 'required|integer|exists:staffbioinfo,id', 'monthly_contribution' => 'required|numeric|min:0', 'note' => 'nullable|string|max:255', 'opening_balance' => 'nullable|numeric|min:0']);
        $this->svc->join((int) $d['staff_id'], (float) $d['monthly_contribution'], $d['note'] ?? null);
        if (!empty($d['opening_balance'])) $this->svc->record((int) $d['staff_id'], 'adjustment', (float) $d['opening_balance'], (int) auth()->id(), null, 'OPENING', 'Opening balance');
        return back()->with('success', 'Member saved. Their savings will be deducted from the next payroll.');
    }

    public function status(Request $request, CoopMember $member)
    {
        $d = $request->validate(['status' => 'required|in:active,suspended,left']);
        $this->svc->setStatus($member, $d['status']);
        return back()->with('success', 'Status updated.');
    }

    public function transaction(Request $request)
    {
        $d = $request->validate(['staff_id' => 'required|integer', 'type' => 'required|in:withdrawal,adjustment,contribution', 'amount' => 'required|numeric|not_in:0',
                                 'txn_date' => 'nullable|date|before_or_equal:today', 'reference' => 'nullable|string|max:80', 'note' => 'required|string|max:255']);
        try {
            $this->svc->record((int) $d['staff_id'], $d['type'], (float) $d['amount'], (int) auth()->id(), $d['txn_date'] ?? null, $d['reference'] ?? null, $d['note']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Transaction recorded.');
    }

    public function dividend(Request $request)
    {
        $d = $request->validate(['pool' => 'required|numeric|min:1', 'note' => 'nullable|string|max:255']);
        try { $n = $this->svc->dividend((float) $d['pool'], (int) auth()->id(), $d['note'] ?? null); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', "Dividend shared among {$n} member(s).");
    }
}
