<?php

namespace App\Http\Controllers;

use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Services\Houses\HouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * House dashboard: members, captains, balanced auto-assignment, house points.
 * Houses themselves are still created on the existing School House page.
 */
class HouseController extends Controller
{
    public function __construct(protected HouseService $svc)
    {
        $this->middleware('permission:View schoolhouse|Update schoolhouse')->only(['index', 'show', 'classStudents']);
        $this->middleware('permission:Update schoolhouse')->only(['assign', 'role', 'autoPreview', 'autoApply', 'details']);
        $this->middleware('permission:Award house points|Update schoolhouse')->only(['award', 'deletePoint']);
    }

    protected function period(Request $request): array
    {
        $sessionId = (int) ($request->get('session_id') ?: (Schoolsession::where('status', 'Current')->value('id') ?? Schoolsession::max('id')));
        $termId = (int) (Schoolterm::where('status', true)->value('id') ?? 0);
        return [$sessionId, $termId];
    }

    public function index(Request $request)
    {
        [$sessionId] = $this->period($request);
        $summary = $this->svc->summary($sessionId);

        $log = Schema::hasTable('house_points')
            ? DB::table('house_points as p')->join('schoolhouses as h', 'h.id', '=', 'p.house_id')
                ->leftJoin('users as u', 'u.id', '=', 'p.awarded_by')->leftJoin('studentRegistration as s', 's.id', '=', 'p.student_id')
                ->where('p.session_id', $sessionId)->orderByDesc('p.event_date')->orderByDesc('p.id')->limit(15)
                ->get(['p.*', 'h.house', 'h.housecolour', 'u.name as by_name', DB::raw("CONCAT(s.firstname,' ',s.lastname) as student_name")])
            : collect();

        $byCategory = Schema::hasTable('house_points')
            ? DB::table('house_points')->where('session_id', $sessionId)->groupBy('house_id', 'category')->selectRaw('house_id, category, SUM(points) as pts')->get()
                ->groupBy('house_id')->map(fn ($g) => $g->pluck('pts', 'category'))
            : collect();

        return view('houses.index', [
            'pagetitle' => 'School Houses', 'summary' => $summary, 'log' => $log, 'byCategory' => $byCategory,
            'unassigned' => $this->svc->unassignedCount($sessionId),
            'sessions' => Schoolsession::orderByDesc('id')->get(['id', 'session']), 'sessionId' => $sessionId,
            'plan' => session('house_plan'),
        ]);
    }

    public function show(Request $request, int $house)
    {
        [$sessionId] = $this->period($request);
        $h = $this->svc->houses()->firstWhere('id', $house);
        abort_unless($h, 404);

        $members = $this->svc->members($house, $sessionId, $request->get('class_id') ? (int) $request->get('class_id') : null, trim((string) $request->get('search')) ?: null);
        $classes = DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')->orderBy('c.schoolclass')->orderBy('a.arm')
            ->get(['c.id', DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as name")])->pluck('name', 'id');

        return view('houses.show', [
            'pagetitle' => $h->house, 'h' => $h, 'members' => $members, 'classes' => $classes,
            'houses' => $this->svc->houses()->where('active', true),
            'sessions' => Schoolsession::orderByDesc('id')->get(['id', 'session']), 'sessionId' => $sessionId,
            'points' => Schema::hasTable('house_points') ? (int) DB::table('house_points')->where('house_id', $house)->where('session_id', $sessionId)->sum('points') : 0,
            'staff' => DB::table('users')->whereNull('student_id')->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /** JSON: students in a class with their current house. */
    public function classStudents(Request $request)
    {
        [$sessionId] = $this->period($request);
        $houses = DB::table('schoolhouses')->pluck('house', 'id');
        $current = $this->svc->currentQuery()->pluck('sh.schoolhouse', 'sh.studentid');

        return response()->json(DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $sessionId)->where('sc.schoolclassid', (int) $request->get('class_id'))
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->select('s.id', 's.firstname', 's.lastname', 's.admissionNo')->distinct()->orderBy('s.lastname')->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'name' => trim($r->lastname . ' ' . $r->firstname), 'adm' => $r->admissionNo,
                              'house_id' => isset($current[$r->id]) ? (int) $current[$r->id] : null, 'house' => $houses[$current[$r->id] ?? 0] ?? null]));
    }

    public function assign(Request $request, int $house)
    {
        $data = $request->validate(['student_ids' => 'required|array|min:1', 'student_ids.*' => 'integer']);
        [$sessionId, $termId] = $this->period($request);
        $n = $this->svc->assign($data['student_ids'], $house, $termId ?: null, $sessionId);
        return back()->with('success', "$n student(s) placed in this house.");
    }

    public function role(Request $request, int $student)
    {
        $data = $request->validate(['role' => 'required|in:' . implode(',', array_keys(HouseService::ROLES))]);
        $this->svc->setRole($student, $data['role']);
        return back()->with('success', 'Role updated.');
    }

    public function move(Request $request, int $student)
    {
        abort_unless($request->user()->can('Update schoolhouse'), 403);
        $data = $request->validate(['house_id' => 'required|integer|exists:schoolhouses,id']);
        [$sessionId, $termId] = $this->period($request);
        $this->svc->assign([$student], (int) $data['house_id'], $termId ?: null, $sessionId);
        return back()->with('success', 'Student moved.');
    }

    public function details(Request $request, int $house)
    {
        $data = $request->validate([
            'housemasterid' => 'nullable|integer|exists:users,id', 'housecolour' => 'nullable|string|max:30',
            'motto' => 'nullable|string|max:150', 'description' => 'nullable|string|max:2000',
            'patron_id' => 'nullable|integer|exists:users,id', 'assistant_master_id' => 'nullable|integer|exists:users,id',
            'mascot' => 'nullable|string|max:80', 'founded_year' => 'nullable|integer|min:1900|max:2100', 'meeting_place' => 'nullable|string|max:120',
        ]);
        $upd = ['updated_at' => now()];
        foreach (['housemasterid', 'housecolour'] as $k) if (!empty($data[$k])) $upd[$k] = $data[$k];
        if (Schema::hasColumn('schoolhouses', 'patron_id')) {
            foreach (['patron_id', 'assistant_master_id', 'mascot', 'founded_year', 'meeting_place'] as $k) $upd[$k] = $data[$k] ?? null;
        }
        if (Schema::hasColumn('schoolhouses', 'motto')) { $upd['motto'] = $data['motto'] ?? null; $upd['description'] = $data['description'] ?? null; $upd['is_active'] = $request->boolean('is_active'); }
        DB::table('schoolhouses')->where('id', $house)->update($upd);
        return back()->with('success', 'House details saved.');
    }

    public function autoPreview(Request $request)
    {
        [$sessionId] = $this->period($request);
        $p = $this->svc->autoPlan($sessionId);
        if (!$p['plan']) return back()->with('success', 'Every student already has a house.');
        session(['house_plan' => ['session_id' => $sessionId, 'plan' => $p['plan'], 'per_house' => $p['per_house'], 'at' => now()->toDateTimeString()]]);
        return redirect()->route('houses.index', ['session_id' => $sessionId]);
    }

    public function autoApply(Request $request)
    {
        $plan = session('house_plan');
        if (!$plan) return back()->with('error', 'Preview the assignment first.');
        [, $termId] = $this->period($request);

        $byHouse = [];
        foreach ($plan['plan'] as $sid => $h) $byHouse[$h][] = (int) $sid;
        $n = 0;
        DB::transaction(function () use ($byHouse, $termId, $plan, &$n) {
            foreach ($byHouse as $h => $ids) {
                // Only students who still have no house (someone may have been placed since the preview).
                $ids = array_values(array_filter($ids, fn ($id) => !DB::table('studenthouses')->where('studentid', $id)->whereIn('schoolhouse', DB::table('schoolhouses')->select('id'))->exists()));
                $n += $this->svc->assign($ids, (int) $h, $termId ?: null, (int) $plan['session_id']);
            }
        });
        session()->forget('house_plan');
        return back()->with('success', "$n student(s) placed in houses.");
    }

    public function award(Request $request)
    {
        $data = $request->validate([
            'house_id' => 'required|integer|exists:schoolhouses,id', 'points' => 'required|integer|between:-1000,1000|not_in:0',
            'category' => 'required|in:' . implode(',', array_keys(HouseService::CATEGORIES)), 'reason' => 'required|string|max:255',
            'event_date' => 'nullable|date', 'student_id' => 'nullable|integer',
        ]);
        [$sessionId, $termId] = $this->period($request);
        $this->svc->awardPoints($data + ['session_id' => $sessionId, 'term_id' => $termId ?: null], $request->user()->id);
        return back()->with('success', ($data['points'] > 0 ? 'Awarded ' : 'Deducted ') . abs($data['points']) . ' point(s).');
    }

    public function deletePoint(int $point)
    {
        DB::table('house_points')->where('id', $point)->delete();
        return back()->with('success', 'Entry removed.');
    }
}
