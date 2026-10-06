<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayItem;
use App\Models\PayrollPeriod;
use App\Models\SalaryGrade;
use App\Models\SalaryReview;
use App\Services\Payroll\SalaryReviewService;
use App\Services\Payroll\SalaryScaleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Salary scales (grades/steps), the pay items library, staff placements and
 * pay items, and salary reviews.
 */
class PayrollStructureController extends Controller
{
    public function __construct(protected SalaryScaleService $scale)
    {
        $this->middleware('permission:View payroll|Manage salary structures')->only(['scales', 'items', 'reviews', 'reviewShow']);
        $this->middleware('permission:Manage salary structures')->only(['storeGrade', 'updateGrade', 'saveSteps', 'storeItem', 'updateItem', 'assignItem',
            'removeStaffItem', 'savePlacement', 'reviewStore']);
        $this->middleware('permission:Approve payroll')->only(['reviewApprove', 'reviewApply']);
    }

    // ── Salary scales ───────────────────────────────────────────────────

    public function scales(Request $request)
    {
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();
        $grades = SalaryGrade::orderBy('sort')->orderBy('code')->get();
        $counts = DB::table('staff_grade_history as h')
            ->whereIn('h.id', DB::table('staff_grade_history')->where('effective_from', '<=', $date->toDateString())->selectRaw('MAX(id)')->groupBy('staff_id'))
            ->groupBy('h.grade_id')->selectRaw('h.grade_id, COUNT(*) as n')->pluck('n', 'grade_id');

        $edit = $request->get('grade') ? $grades->firstWhere('id', (int) $request->get('grade')) : null;
        return view('finance.payroll.scales', [
            'pagetitle' => 'Salary Scales', 'grades' => $grades, 'scale' => $this->scale->scaleOn($date), 'date' => $date,
            'counts' => $counts, 'edit' => $edit, 'cols' => SalaryScaleService::COLUMNS,
            'versions' => DB::table('salary_grade_steps')->distinct()->orderByDesc('effective_from')->pluck('effective_from'),
        ]);
    }

    public function storeGrade(Request $request)
    {
        $d = $request->validate(['code' => 'required|string|max:20|unique:salary_grades,code', 'name' => 'required|string|max:100',
                                 'max_step' => 'required|integer|min:1|max:30', 'sort' => 'nullable|integer']);
        $g = SalaryGrade::create($d + ['is_active' => true]);
        return redirect()->route('payroll.scales', ['grade' => $g->id])->with('success', 'Grade added. Now enter the amounts for each step.');
    }

    public function updateGrade(Request $request, SalaryGrade $grade)
    {
        $d = $request->validate(['code' => ['required', 'string', 'max:20', Rule::unique('salary_grades', 'code')->ignore($grade->id)],
                                 'name' => 'required|string|max:100', 'max_step' => 'required|integer|min:1|max:30', 'sort' => 'nullable|integer']);
        $grade->update($d + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Grade saved.');
    }

    /** Save amounts for every step of a grade, from a date (a new version if the date is new). */
    public function saveSteps(Request $request, SalaryGrade $grade)
    {
        $request->validate(['effective_from' => 'required|date', 'steps' => 'required|array']);
        $from = Carbon::parse($request->effective_from)->toDateString();
        $locked = PayrollPeriod::whereIn('status', ['approved', 'paid', 'locked'])->where('end_date', '>=', $from)->exists();
        if ($locked && !$request->boolean('confirm_past')) {
            return back()->withInput()->with('error', 'Payroll months from that date are already approved. To pay a back-dated raise with arrears, use Salary Reviews; or tick "I understand" to only change future months.');
        }

        DB::transaction(function () use ($request, $grade, $from) {
            foreach ((array) $request->steps as $step => $vals) {
                $step = (int) $step;
                if ($step < 1 || $step > $grade->max_step) continue;
                $row = ['grade_id' => $grade->id, 'step' => $step, 'effective_from' => $from, 'updated_at' => now()];
                $any = false;
                foreach (array_keys(SalaryScaleService::COLUMNS) as $c) {
                    $row[$c] = round(max(0, (float) str_replace(',', '', $vals[$c] ?? 0)), 2);
                    $any = $any || $row[$c] > 0;
                }
                if (!$any) continue;
                DB::table('salary_grade_steps')->updateOrInsert(['grade_id' => $grade->id, 'step' => $step, 'effective_from' => $from], $row + ['created_at' => now()]);
            }
        });
        return redirect()->route('payroll.scales', ['grade' => $grade->id, 'date' => $from])->with('success', "Amounts for {$grade->code} saved from {$from}.");
    }

    // ── Pay items library ───────────────────────────────────────────────

    public function items()
    {
        $items = PayItem::orderBy('type')->orderBy('name')->get();
        $usage = DB::table('staff_pay_items')->where(fn ($q) => $q->whereNull('to_month')->orWhere('to_month', '>=', now()->startOfMonth()->toDateString()))
            ->groupBy('pay_item_id')->selectRaw('pay_item_id, COUNT(DISTINCT staff_id) as n')->pluck('n', 'pay_item_id');
        return view('finance.payroll.items', [
            'pagetitle' => 'Allowances & Deductions', 'items' => $items, 'usage' => $usage,
            'staff' => DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->orderBy('u.name')->get(['s.id', 'u.name', 's.employmentid']),
            'periods' => PayrollPeriod::whereIn('status', ['draft', 'processing'])->orderBy('start_date')->get(['id', 'period_name', 'start_date']),
        ]);
    }

    protected function itemData(Request $request, ?PayItem $item = null): array
    {
        $d = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/', Rule::unique('pay_items', 'code')->ignore($item?->id)],
            'name' => 'required|string|max:100', 'type' => 'required|in:earning,deduction', 'calc' => 'required|in:fixed,percent_basic,percent_gross',
            'default_amount' => 'nullable|numeric|min:0', 'default_rate' => 'nullable|numeric|min:0|max:100', 'description' => 'nullable|string|max:255',
        ]);
        return $d + [
            'default_amount' => $d['default_amount'] ?? 0, 'default_rate' => $d['default_rate'] ?? 0,
            'taxable' => $d['type'] === 'earning' && $request->boolean('taxable'), 'pensionable' => $d['type'] === 'earning' && $request->boolean('pensionable'),
            'one_off' => $request->boolean('one_off'), 'is_active' => $request->boolean('is_active', true),
        ];
    }

    public function storeItem(Request $request)
    {
        $request->merge(['code' => strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', (string) $request->code))]);
        PayItem::create($this->itemData($request));
        return back()->with('success', 'Item added.');
    }

    public function updateItem(Request $request, PayItem $item)
    {
        $request->merge(['code' => $item->is_system ? $item->code : strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', (string) $request->code))]);
        $item->update($this->itemData($request, $item));
        return back()->with('success', 'Item saved.');
    }

    /** Give an item to one or many staff, for one month or from a month onward. */
    public function assignItem(Request $request)
    {
        $d = $request->validate([
            'pay_item_id' => 'required|exists:pay_items,id', 'staff_ids' => 'required|array|min:1', 'staff_ids.*' => 'integer',
            'amount' => 'nullable|numeric|min:0', 'rate' => 'nullable|numeric|min:0|max:100',
            'from_month' => 'required|date_format:Y-m', 'to_month' => 'nullable|date_format:Y-m', 'mode' => 'required|in:once,ongoing,range', 'note' => 'nullable|string|max:255',
        ]);
        $from = Carbon::parse($d['from_month'] . '-01')->toDateString();
        $to = match ($d['mode']) { 'once' => $from, 'range' => $d['to_month'] ? Carbon::parse($d['to_month'] . '-01')->toDateString() : $from, default => null };
        if ($to && $to < $from) return back()->with('error', 'The end month is before the start month.');

        $rows = [];
        foreach (array_unique(array_map('intval', $d['staff_ids'])) as $sid) {
            $rows[] = ['staff_id' => $sid, 'pay_item_id' => $d['pay_item_id'], 'amount' => $d['amount'] ?? null, 'rate' => $d['rate'] ?? null,
                       'from_month' => $from, 'to_month' => $to, 'note' => $d['note'] ?? null, 'created_by' => $request->user()->id,
                       'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('staff_pay_items')->insert($rows);
        return back()->with('success', count($rows) . ' staff updated. Recalculate any draft payroll month to include it.');
    }

    public function removeStaffItem(int $id)
    {
        $row = DB::table('staff_pay_items')->where('id', $id)->first();
        abort_unless($row, 404);
        $m = now()->startOfMonth()->toDateString();
        // Keep history: an ongoing item that has already been paid is ended last month instead of deleted.
        $paid = DB::table('payroll_run_lines as l')->join('payroll_runs as r', 'r.id', '=', 'l.payroll_run_id')
            ->join('payroll_periods as p', 'p.id', '=', 'r.payroll_period_id')->whereIn('p.status', ['approved', 'paid', 'locked'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(l.meta, '$.staff_pay_item_id')) = ?", [(string) $id])->exists();
        if ($paid) {
            DB::table('staff_pay_items')->where('id', $id)->update(['to_month' => Carbon::parse($m)->subMonth()->toDateString(), 'updated_at' => now()]);
            return back()->with('success', 'Ended from this month (earlier payments are kept).');
        }
        DB::table('staff_pay_items')->where('id', $id)->delete();
        return back()->with('success', 'Removed.');
    }

    /** Pay-profile section: salary source + grade/step placement. */
    public function savePlacement(Request $request, int $staff)
    {
        $d = $request->validate(['salary_source' => 'required|in:structure,grade', 'grade_id' => 'nullable|required_if:salary_source,grade|exists:salary_grades,id',
                                 'step' => 'nullable|required_if:salary_source,grade|integer|min:1', 'effective_from' => 'nullable|required_if:salary_source,grade|date',
                                 'reason' => 'nullable|in:placement,promotion,increment,correction']);
        DB::table('staff_pay_profiles')->updateOrInsert(['staff_id' => $staff], ['salary_source' => $d['salary_source'], 'updated_at' => now()]);
        if ($d['salary_source'] === 'grade') {
            $g = SalaryGrade::find($d['grade_id']);
            if ($d['step'] > $g->max_step) return back()->with('error', "{$g->code} only goes up to step {$g->max_step}.");
            $cur = $this->scale->placement($staff, $d['effective_from']);
            if (!$cur || $cur->grade_id != $g->id || $cur->step != $d['step']) {
                $this->scale->place($staff, $g->id, (int) $d['step'], $d['effective_from'], $d['reason'] ?? 'placement', $request->user()->id);
            }
        }
        return back()->with('success', 'Salary placement saved.');
    }

    // ── Salary reviews ──────────────────────────────────────────────────

    public function reviews()
    {
        return view('finance.payroll.reviews', [
            'pagetitle' => 'Salary Reviews', 'reviews' => SalaryReview::orderByDesc('id')->get(),
            'grades' => SalaryGrade::where('is_active', true)->orderBy('sort')->orderBy('code')->get(), 'cols' => SalaryScaleService::COLUMNS,
        ]);
    }

    public function reviewStore(Request $request)
    {
        $d = $request->validate(['name' => 'required|string|max:150', 'type' => 'required|in:step_increment,percent_raise', 'effective_from' => 'required|date',
                                 'percent' => 'nullable|required_if:type,percent_raise|numeric|min:0.01|max:200', 'grade_ids' => 'nullable|array', 'components' => 'nullable|array']);
        $r = SalaryReview::create($d + ['status' => 'draft', 'created_by' => $request->user()->id,
            'grade_ids' => array_values(array_map('intval', $d['grade_ids'] ?? [])) ?: null, 'components' => $d['type'] === 'percent_raise' ? ($d['components'] ?? ['basic']) : null]);
        return redirect()->route('payroll.reviews.show', $r);
    }

    public function reviewShow(SalaryReview $review, SalaryReviewService $svc)
    {
        return view('finance.payroll.review-show', [
            'pagetitle' => $review->name, 'r' => $review, 'p' => $svc->preview($review),
            'periods' => PayrollPeriod::whereIn('status', ['draft', 'processing'])->orderBy('start_date')->get(['id', 'period_name', 'start_date']),
            'grades' => SalaryGrade::pluck('code', 'id'),
        ]);
    }

    public function reviewApprove(Request $request, SalaryReview $review)
    {
        abort_unless($review->status === 'draft', 422);
        if ((int) $review->created_by === (int) $request->user()->id && !empty(\App\Services\Payroll\StatutoryRates::on(now())['limits']['require_different_approver'])) {
            return back()->with('error', 'Someone other than the person who created this review must approve it.');
        }
        $review->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        return back()->with('success', 'Approved. Apply it when ready.');
    }

    public function reviewApply(Request $request, SalaryReview $review, SalaryReviewService $svc)
    {
        $review->update(['arrears_period_id' => $request->input('arrears_period_id') ?: null]);
        try {
            $s = $svc->apply($review->fresh(), $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', "Applied to {$s['staff']} staff. Monthly pay rises by ₦" . number_format($s['monthly_increase'], 2)
            . ($s['arrears_staff'] ? ". Arrears of ₦" . number_format($s['arrears_total'], 2) . " for {$s['arrears_staff']} staff added to the chosen month — recalculate it." : '.'));
    }
}
