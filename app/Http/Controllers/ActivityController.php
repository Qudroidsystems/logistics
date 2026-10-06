<?php

namespace App\Http\Controllers;

use App\Models\ActivityMembership;
use App\Models\Schoolsession;
use App\Services\Activities\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Club & sport membership (a student can be in many of each).
 * Clubs/sports themselves are still created on the existing Club/Sport pages.
 */
class ActivityController extends Controller
{
    public function __construct(protected ActivityService $svc) {}

    protected function authorizeType(Request $request, string $type, bool $manage = false): array
    {
        $c = ActivityService::cfg($type);
        $u = $request->user();
        abort_unless($manage ? $u->can($c['manage']) : ($u->can($c['view']) || $u->can($c['manage'])), 403);
        return $c;
    }

    protected function sessionId(Request $request): int
    {
        return (int) ($request->get('session_id') ?: (Schoolsession::where('status', 'Current')->value('id') ?? Schoolsession::max('id')));
    }

    public function index(Request $request, string $type)
    {
        $c = $this->authorizeType($request, $type);
        $sessionId = $this->sessionId($request);
        $items = $this->svc->list($type, $sessionId);
        if ($s = trim((string) $request->get('search'))) $items = $items->filter(fn ($i) => stripos($i->name, $s) !== false || stripos((string) $i->lead_name, $s) !== false);

        return view('activities.index', [
            'pagetitle' => $c['plural'], 'type' => $type, 'c' => $c, 'items' => $items->values(),
            'sessions' => Schoolsession::orderByDesc('id')->get(['id', 'session']), 'sessionId' => $sessionId,
            'totalMembers' => DB::table('activity_memberships')->where('type', $type)->where('session_id', $sessionId)->count(),
            'uniqueStudents' => DB::table('activity_memberships')->where('type', $type)->where('session_id', $sessionId)->distinct()->count('student_id'),
        ]);
    }

    public function show(Request $request, string $type, int $id)
    {
        $c = $this->authorizeType($request, $type);
        $sessionId = $this->sessionId($request);
        $act = $this->svc->find($type, $id);
        $members = $this->svc->members($type, $id, $sessionId);

        return view('activities.show', [
            'pagetitle' => $act->name, 'type' => $type, 'c' => $c, 'act' => $act, 'members' => $members,
            'sessions' => Schoolsession::orderByDesc('id')->get(['id', 'session']), 'sessionId' => $sessionId,
            'classes' => DB::table('schoolclass as cl')->leftJoin('schoolarm as a', 'a.id', '=', 'cl.arm')->orderBy('cl.schoolclass')->orderBy('a.arm')
                ->get(['cl.id', DB::raw("TRIM(CONCAT(COALESCE(cl.schoolclass,''), ' ', COALESCE(a.arm,''))) as name")])->pluck('name', 'id'),
            'teams' => $type === 'sport' && Schema::hasTable('sport_teams') ? DB::table('sport_teams')->where('sportid', $id)->orderBy('team_name')->pluck('team_name', 'id') : collect(),
            'staff' => DB::table('users')->whereNull('student_id')->orderBy('name')->pluck('name', 'id'),
            'canManage' => $request->user()->can($c['manage']),
        ]);
    }

    /** JSON: active students in a class (for the session) with membership flag. */
    public function classStudents(Request $request, string $type, int $id)
    {
        $this->authorizeType($request, $type, true);
        $sessionId = $this->sessionId($request);
        $on = DB::table('activity_memberships')->where('type', $type)->where('activity_id', $id)->where('session_id', $sessionId)->pluck('student_id')->map(fn ($v) => (int) $v)->all();

        return response()->json(DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $sessionId)->where('sc.schoolclassid', (int) $request->get('class_id'))
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->select('s.id', 's.firstname', 's.lastname', 's.admissionNo')->distinct()->orderBy('s.lastname')->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'name' => trim($r->lastname . ' ' . $r->firstname), 'adm' => $r->admissionNo, 'on' => in_array((int) $r->id, $on, true)]));
    }

    public function add(Request $request, string $type, int $id)
    {
        $c = $this->authorizeType($request, $type, true);
        $data = $request->validate(['student_ids' => 'required|array|min:1', 'student_ids.*' => 'integer', 'role' => 'nullable|string|max:40']);
        $role = array_key_exists($data['role'] ?? 'member', $c['roles']) ? ($data['role'] ?? 'member') : 'member';

        $r = $this->svc->add($type, $id, $data['student_ids'], $this->sessionId($request), $request->user()->id, $role);
        $msg = $r['added'] . ' student(s) added.';
        if ($r['full']) $msg .= ' ' . $r['skipped'] . ' not added — the ' . strtolower($c['label']) . ' is full.';
        return back()->with($r['full'] ? 'error' : 'success', $msg);
    }

    public function updateMember(Request $request, string $type, ActivityMembership $member)
    {
        $c = $this->authorizeType($request, $type, true);
        abort_unless($member->type === $type, 404);
        $data = $request->validate(['role' => 'required|string|max:40', 'team_id' => 'nullable|integer']);
        abort_unless(array_key_exists($data['role'], $c['roles']), 422);

        $member->update(['role' => $data['role'], 'team_id' => $type === 'sport' ? ($data['team_id'] ?? null) : null]);
        return back()->with('success', 'Updated.');
    }

    public function removeMember(Request $request, string $type, ActivityMembership $member)
    {
        $this->authorizeType($request, $type, true);
        abort_unless($member->type === $type, 404);
        $this->svc->remove($member);
        return back()->with('success', 'Removed.');
    }

    public function updateDetails(Request $request, string $type, int $id)
    {
        $c = $this->authorizeType($request, $type, true);
        $data = $request->validate([
            'lead_id' => 'nullable|integer|exists:users,id', 'meeting_day' => 'nullable|string|max:20', 'meeting_time' => 'nullable|string|max:20',
            'venue' => 'nullable|string|max:120', 'capacity' => 'nullable|integer|min:1|max:5000', 'description' => 'nullable|string|max:2000',
        ]);
        $upd = ['meeting_day' => $data['meeting_day'] ?? null, 'meeting_time' => $data['meeting_time'] ?? null, 'venue' => $data['venue'] ?? null,
                'capacity' => $data['capacity'] ?? null, 'description' => $data['description'] ?? null,
                'is_active' => $request->boolean('is_active'), 'updated_at' => now()];
        if (!empty($data['lead_id'])) $upd[$c['lead']] = (int) $data['lead_id'];
        DB::table($c['table'])->where('id', $id)->update($upd);
        return back()->with('success', 'Details saved.');
    }

    public function addTeam(Request $request, int $id)
    {
        $this->authorizeType($request, 'sport', true);
        $data = $request->validate(['team_name' => 'required|string|max:100']);
        DB::table('sport_teams')->insert(['sportid' => $id, 'team_name' => $data['team_name'], 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Team added.');
    }

    public function export(Request $request, string $type, int $id)
    {
        $c = $this->authorizeType($request, $type);
        $act = $this->svc->find($type, $id);
        $rows = $this->svc->members($type, $id, $this->sessionId($request));
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $act->name) . '_members.csv';

        return response()->streamDownload(function () use ($rows, $c) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Surname', 'First name', 'Class', 'Gender', 'Role', 'Team', 'Joined']);
            foreach ($rows as $r) fputcsv($out, [$r->admissionNo, $r->lastname, $r->firstname, $r->class_name, $r->gender, $c['roles'][$r->role] ?? $r->role, $r->team_name ?? '', $r->joined_on]);
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    // ── By student: tick all clubs & sports for one student ─────────────

    public function student(Request $request)
    {
        $u = $request->user();
        abort_unless($u->can('Update club') || $u->can('Update sport') || $u->can('View club') || $u->can('View sport'), 403);
        $sessionId = $this->sessionId($request);
        $student = $request->get('student_id') ? DB::table('studentRegistration')->where('id', (int) $request->get('student_id'))->first(['id', 'firstname', 'lastname', 'admissionNo']) : null;

        return view('activities.student', [
            'pagetitle' => 'Clubs & Sports by Student', 'student' => $student, 'sessionId' => $sessionId,
            'sessions' => Schoolsession::orderByDesc('id')->get(['id', 'session']),
            'clubs' => $this->svc->list('club', $sessionId)->where('active', true), 'sports' => $this->svc->list('sport', $sessionId)->where('active', true),
            'mine' => $student ? $this->svc->forStudent((int) $student->id, $sessionId) : null,
        ]);
    }

    public function saveStudent(Request $request)
    {
        $data = $request->validate(['student_id' => 'required|integer|exists:studentRegistration,id', 'clubs' => 'nullable|array', 'sports' => 'nullable|array']);
        $sessionId = $this->sessionId($request);
        $notes = [];
        foreach (['club' => 'clubs', 'sport' => 'sports'] as $type => $field) {
            if (!$request->user()->can(ActivityService::cfg($type)['manage'])) continue;
            $r = $this->svc->syncStudent($type, (int) $data['student_id'], $sessionId, $data[$field] ?? [], $request->user()->id);
            if ($r['full']) $notes[] = implode(', ', $r['full']) . ' is full';
        }
        return redirect()->route('activities.student', ['student_id' => $data['student_id'], 'session_id' => $sessionId])
            ->with($notes ? 'error' : 'success', $notes ? 'Saved, but ' . implode('; ', $notes) . '.' : 'Clubs and sports saved.');
    }

    public function searchStudents(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) return response()->json([]);
        return response()->json(DB::table('studentRegistration')
            ->where(fn ($w) => $w->where('admissionNo', 'like', "%$q%")->orWhereRaw("CONCAT(firstname,' ',lastname) LIKE ?", ["%$q%"])->orWhereRaw("CONCAT(lastname,' ',firstname) LIKE ?", ["%$q%"]))
            ->whereRaw("LOWER(COALESCE(student_status, 'active')) = 'active'")
            ->orderBy('lastname')->limit(15)->get(['id', 'firstname', 'lastname', 'admissionNo'])
            ->map(fn ($r) => ['id' => $r->id, 'text' => trim($r->lastname . ' ' . $r->firstname) . ' (' . $r->admissionNo . ')']));
    }
}
