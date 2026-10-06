<?php

namespace App\Http\Controllers;

use App\Models\FeeInstalmentPlan;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Services\Billing\InstalmentPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bursary: fee instalment plans (split a term's fees into dated parts).
 */
class InstalmentPlanController extends Controller
{
    public function __construct(protected InstalmentPlanService $plans)
    {
        $this->middleware('permission:View instalment-plans')->only(['index', 'show', 'classStudents']);
        $this->middleware('permission:Manage instalment-plans')->except(['index', 'show', 'classStudents']);
    }

    public function index(Request $request)
    {
        $sessions  = Schoolsession::orderByDesc('id')->get(['id', 'session', 'status']);
        $sessionId = (int) ($request->get('session_id') ?: (Schoolsession::where('status', 'Current')->value('id') ?? $sessions->first()?->id));

        $plans = FeeInstalmentPlan::where('session_id', $sessionId)->withCount('assignments')
            ->orderBy('term_id')->orderByDesc('id')->get();

        return view('instalments.index', [
            'pagetitle' => 'Instalment Plans',
            'plans'     => $plans,
            'sessions'  => $sessions,
            'sessionId' => $sessionId,
            'terms'     => Schoolterm::pluck('term', 'id'),
            'classes'   => $this->classOptions()->pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return $this->form(new FeeInstalmentPlan([
            'session_id' => Schoolsession::where('status', 'Current')->value('id'),
            'term_id'    => Schoolterm::where('status', true)->value('id'),
            'applies_to' => 'selected', 'results_when_on_track' => true, 'is_active' => true,
            'schedule'   => [
                ['label' => 'First instalment', 'percent' => 50, 'due_date' => null],
                ['label' => 'Second instalment', 'percent' => 30, 'due_date' => null],
                ['label' => 'Final instalment', 'percent' => 20, 'due_date' => null],
            ],
        ]));
    }

    public function edit(FeeInstalmentPlan $plan)
    {
        return $this->form($plan);
    }

    protected function form(FeeInstalmentPlan $plan)
    {
        return view('instalments.form', [
            'pagetitle' => $plan->exists ? 'Edit Instalment Plan' : 'New Instalment Plan',
            'plan'      => $plan,
            'sessions'  => Schoolsession::orderByDesc('id')->get(['id', 'session']),
            'terms'     => Schoolterm::orderBy('id')->get(['id', 'term']),
            'classes'   => $this->classOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (is_string($data)) return back()->withErrors(['schedule' => $data])->withInput();

        $plan = FeeInstalmentPlan::create($data + ['created_by' => $request->user()->id]);
        return redirect()->route('instalment-plans.show', $plan)->with('success', 'Plan created.' . ($plan->applies_to === 'selected' ? ' Now add the students on this plan.' : ''));
    }

    public function update(Request $request, FeeInstalmentPlan $plan)
    {
        $data = $this->validated($request);
        if (is_string($data)) return back()->withErrors(['schedule' => $data])->withInput();

        $plan->update($data);
        return redirect()->route('instalment-plans.show', $plan)->with('success', 'Plan updated.');
    }

    /** @return array|string validated data, or an error message */
    protected function validated(Request $request): array|string
    {
        $v = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'session_id'  => 'required|integer|exists:schoolsession,id',
            'term_id'     => 'required|integer|exists:schoolterm,id',
            'applies_to'  => 'required|in:selected,classes,all',
            'class_ids'   => 'nullable|array',
            'class_ids.*' => 'integer',
            'label'       => 'required|array',
            'percent'     => 'required|array',
            'due_date'    => 'required|array',
            'due_date.*'  => 'nullable|date',
        ]);

        [$rows, $err] = InstalmentPlanService::normaliseSchedule($v['label'], $v['percent'], $v['due_date']);
        if ($err) return $err;
        if ($v['applies_to'] === 'classes' && empty($v['class_ids'])) return 'Choose at least one class, or change who the plan applies to.';

        return [
            'name'                  => $v['name'],
            'description'           => $v['description'] ?? null,
            'session_id'            => (int) $v['session_id'],
            'term_id'               => (int) $v['term_id'],
            'applies_to'            => $v['applies_to'],
            'class_ids'             => $v['applies_to'] === 'classes' ? array_values(array_map('intval', $v['class_ids'])) : null,
            'schedule'              => $rows,
            'results_when_on_track' => $request->boolean('results_when_on_track'),
            'is_active'             => $request->boolean('is_active'),
        ];
    }

    public function show(Request $request, FeeInstalmentPlan $plan)
    {
        $classes = $this->classOptions()->pluck('name', 'id');

        $q = DB::table('studentRegistration as s')
            ->leftJoin('studentclass as sc', function ($j) use ($plan) {
                $j->on('sc.studentId', '=', 's.id')->where('sc.sessionid', $plan->session_id);
            })
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'");

        match ($plan->applies_to) {
            'selected' => $q->whereIn('s.id', DB::table('fee_instalment_assignments')->where('plan_id', $plan->id)->select('student_id')),
            'classes'  => $q->where(fn ($w) => $w->whereIn('sc.schoolclassid', $plan->class_ids ?: [0])
                              ->orWhereIn('s.id', DB::table('fee_instalment_assignments')->where('plan_id', $plan->id)->select('student_id'))),
            default    => $q->where(fn ($w) => $w->whereNotNull('sc.id')
                              ->orWhereIn('s.id', DB::table('fee_instalment_assignments')->where('plan_id', $plan->id)->select('student_id'))),
        };
        if ($s = trim((string) $request->get('search'))) {
            $q->where(fn ($w) => $w->where('s.admissionNo', 'like', "%$s%")->orWhereRaw("CONCAT(s.firstname,' ',s.lastname) LIKE ?", ["%$s%"]));
        }

        $students = $q->groupBy('s.id', 's.firstname', 's.lastname', 's.admissionNo')
            ->orderBy('s.lastname')->orderBy('s.firstname')
            ->select('s.id', 's.firstname', 's.lastname', 's.admissionNo', DB::raw('MAX(sc.schoolclassid) as class_id'))
            ->paginate(25)->withQueryString();

        $assignedIds = DB::table('fee_instalment_assignments')->where('plan_id', $plan->id)->pluck('student_id')->map(fn ($v) => (int) $v)->all();

        $students->getCollection()->transform(function ($st) use ($plan, $assignedIds) {
            $effective = $this->plans->planFor((int) $st->id, (int) $plan->term_id, (int) $plan->session_id, $st->class_id ? (int) $st->class_id : null);
            $st->other_plan = $effective && $effective->id !== $plan->id ? $effective->name : null;
            $st->assigned   = in_array((int) $st->id, $assignedIds, true);
            $st->sched      = null;
            if (!$st->other_plan) {
                try { $st->sched = $this->plans->schedule((int) $st->id, (int) $plan->term_id, (int) $plan->session_id, null, null, $st->class_id ? (int) $st->class_id : null); }
                catch (\Throwable $e) { $st->sched = null; }
            }
            return $st;
        });

        return view('instalments.show', [
            'pagetitle' => $plan->name,
            'plan'      => $plan,
            'students'  => $students,
            'classes'   => $classes,
            'term'      => Schoolterm::where('id', $plan->term_id)->value('term'),
            'session'   => Schoolsession::where('id', $plan->session_id)->value('session'),
            'preview'   => $this->plans->compute($plan, 100000, 0),
        ]);
    }

    /** AJAX: students in a class for the plan's session, with their current plan. */
    public function classStudents(Request $request, FeeInstalmentPlan $plan)
    {
        $classId = (int) $request->get('class_id');
        $rows = DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $plan->session_id)->where('sc.schoolclassid', $classId)
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->groupBy('s.id', 's.firstname', 's.lastname', 's.admissionNo')
            ->orderBy('s.lastname')->get(['s.id', 's.firstname', 's.lastname', 's.admissionNo']);

        $onThis = DB::table('fee_instalment_assignments')->where('plan_id', $plan->id)->pluck('student_id')->map(fn ($v) => (int) $v)->all();

        return response()->json($rows->map(fn ($r) => [
            'id'   => (int) $r->id,
            'name' => trim($r->lastname . ' ' . $r->firstname),
            'adm'  => $r->admissionNo,
            'on'   => in_array((int) $r->id, $onThis, true),
        ])->values());
    }

    public function assign(Request $request, FeeInstalmentPlan $plan)
    {
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('student_ids', []))));
        if (!$ids) return back()->with('error', 'Tick at least one student.');

        DB::transaction(function () use ($plan, $ids, $request) {
            // One plan per student per term: drop their other assignments for this term.
            $sameTerm = FeeInstalmentPlan::where('term_id', $plan->term_id)->where('session_id', $plan->session_id)
                ->where('id', '!=', $plan->id)->pluck('id');
            DB::table('fee_instalment_assignments')->whereIn('plan_id', $sameTerm)->whereIn('student_id', $ids)->delete();

            foreach ($ids as $id) {
                DB::table('fee_instalment_assignments')->insertOrIgnore([
                    'plan_id' => $plan->id, 'student_id' => $id, 'assigned_by' => $request->user()->id,
                    'note' => $request->input('note'), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', count($ids) . ' student(s) added to this plan.');
    }

    public function unassign(FeeInstalmentPlan $plan, int $student)
    {
        DB::table('fee_instalment_assignments')->where('plan_id', $plan->id)->where('student_id', $student)->delete();
        return back()->with('success', 'Student removed from the plan.');
    }

    public function toggle(FeeInstalmentPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        return back()->with('success', $plan->is_active ? 'Plan switched on.' : 'Plan switched off — students on it now owe the full term fees as normal.');
    }

    public function destroy(FeeInstalmentPlan $plan)
    {
        DB::transaction(function () use ($plan) {
            $plan->assignments()->delete();
            $plan->delete();
        });
        return redirect()->route('instalment-plans.index')->with('success', 'Plan deleted. No payments were changed.');
    }

    protected function classOptions()
    {
        return DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->orderBy('c.schoolclass')->orderBy('a.arm')
            ->get(['c.id', DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as name")]);
    }
}
