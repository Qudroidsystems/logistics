<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Schoolclass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Shared engine for the vetter-facing "Subjects to Vet" pages:
 *   - MySubjectVettingsController      (terminal results: broadsheets)
 *   - MyMockSubjectVettingsController  (mock exams:       broadsheetmock)
 *
 * Both work the same way; only the tables, the columns shown and the route
 * names differ -- supplied by config() in each subclass.
 *
 * Rules:
 *  - Vetting is READ-ONLY on scores. Nothing here recalculates totals, cums,
 *    grades or positions; those belong to score entry and the position
 *    service.
 *  - Who may vet a broadsheet = whoever it is ASSIGNED to (a row in the
 *    assignment table for this user + subject-class + term + session). The
 *    only permission involved is the page's "View ..." permission.
 *  - Assignment status: pending | completed | rejected (sent back to the
 *    subject teacher). It turns completed automatically once every student
 *    row is vetted, and drops back to pending if one is un-vetted; rejected
 *    is only ever set or cleared by the vetter.
 */
abstract class BaseSubjectVettingController extends Controller
{
    protected const STATUSES = ['pending', 'completed', 'rejected'];

    /**
     * [
     *   'permission'        => 'View my-subject-vettings',
     *   'assignment_table'  => 'subject_vettings',
     *   'sheet_table'       => 'broadsheets',
     *   'record_table'      => 'broadsheet_records',
     *   'record_fk'         => 'broadsheet_record_id',   // column on sheet_table
     *   'mode'              => 'terminal' | 'mock',
     *   'view_prefix'       => 'mysubjectvettings',
     *   'routes'            => ['index' => ..., 'broadsheet' => ..., 'toggle' => ..., 'bulk' => ..., 'status' => ...],
     *   'labels'            => ['title' => ..., 'short' => ...],
     * ]
     */
    abstract protected function config(): array;

    public function __construct()
    {
        $this->middleware('permission:' . $this->config()['permission']);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): View|JsonResponse
    {
        $cfg         = $this->config();
        $assignments = $this->attachProgress($this->assignmentsQuery((int) Auth::id())->get());

        $stats = [
            'total'     => $assignments->count(),
            'pending'   => $assignments->where('status', 'pending')->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'rejected'  => $assignments->where('status', 'rejected')->count(),
            'rows'      => (int) $assignments->sum('students'),
            'vetted'    => (int) $assignments->sum('vetted'),
        ];
        $stats['percent'] = $stats['rows'] ? (int) round($stats['vetted'] / $stats['rows'] * 100) : 0;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'assignments' => $assignments->values(), 'stats' => $stats]);
        }

        return view($cfg['view_prefix'] . '.index', [
            'pagetitle'        => $cfg['labels']['title'],
            'cfg'              => $cfg,
            'assignments'      => $assignments,
            'stats'            => $stats,
            'termOptions'      => $assignments->filter(fn ($a) => $a->termid)->unique('termid')->sortBy('termid')
                                    ->mapWithKeys(fn ($a) => [$a->termid => $a->termname])->all(),
            'sessionOptions'   => $assignments->filter(fn ($a) => $a->sessionid)->unique('sessionid')->sortByDesc('sessionid')
                                    ->mapWithKeys(fn ($a) => [$a->sessionid => $a->sessionname])->all(),
            'currentSessionId' => DB::table('schoolsession')->where('status', 'Current')->value('id'),
        ]);
    }

    // =========================================================================
    // CLASS BROADSHEET
    // =========================================================================

    public function classBroadsheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid): View
    {
        $cfg        = $this->config();
        $assignment = $this->findAssignment((int) $subjectclassid, (int) $termid, (int) $sessionid);
        abort_unless($assignment || $this->canViewAnyBroadsheet(), 403, 'This broadsheet is not in your vetting assignments.');

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
        $armName     = $schoolclass ? DB::table('schoolarm')->where('id', $schoolclass->arm)->value('arm') : null;

        $assessments = collect();
        if ($cfg['mode'] === 'terminal' && $schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $assessments = Assessment::whereIn('classcategory_id', $schoolclass->classcategories->pluck('id'))
                ->orderBy('id')->get();
        }

        $rows = $this->getSheetRows((int) $subjectclassid, (int) $termid, (int) $sessionid);

        $meta = DB::table('subjectclass as sjc')
            ->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
            ->join('subject as s', 's.id', '=', 'st.subjectid')
            ->leftJoin('users as u', 'u.id', '=', 'st.staffid')
            ->where('sjc.id', $subjectclassid)
            ->first(['s.subject', 's.subject_code', 'u.name as teacher']);

        $summary = [
            'students' => $rows->count(),
            'vetted'   => $rows->filter(fn ($b) => (int) $b->vettedstatus === 1)->count(),
            'entered'  => $rows->filter(fn ($b) => (float) $b->total > 0)->count(),
            'average'  => $rows->count() ? round($rows->avg(fn ($b) => (float) $b->total), 1) : 0,
        ];

        $className = trim(($schoolclass->schoolclass ?? '') . ' ' . ($armName ?? ''));

        return view($cfg['view_prefix'] . '.classbroadsheet', [
            'pagetitle'      => $cfg['labels']['short'] . ': ' . ($meta->subject ?? 'Subject') . ' — ' . $className,
            'cfg'            => $cfg,
            'broadsheets'    => $rows,
            'assessments'    => $assessments,
            'className'      => $className ?: '—',
            'subjectName'    => $meta->subject ?? '—',
            'subjectCode'    => $meta->subject_code ?? '',
            'teacherName'    => $meta->teacher ?? '—',
            'schoolterm'     => DB::table('schoolterm')->where('id', $termid)->value('term') ?? '—',
            'schoolsession'  => DB::table('schoolsession')->where('id', $sessionid)->value('session') ?? '—',
            'assignment'     => $assignment,
            'summary'        => $summary,
            'subjectclassid' => (int) $subjectclassid,
            'termid'         => (int) $termid,
            'sessionid'      => (int) $sessionid,
            'canUpdate'      => (bool) $assignment,
        ]);
    }

    // =========================================================================
    // VET / UN-VET ONE ROW
    // =========================================================================

    public function updateVettedStatus(Request $request): JsonResponse
    {
        $cfg = $this->config();
        $request->validate([
            'broadsheet_id' => 'required|integer|exists:' . $cfg['sheet_table'] . ',id',
            'vettedstatus'  => 'required|in:0,1',
        ]);

        $row = DB::table($cfg['sheet_table'] . ' as b')
            ->join($cfg['record_table'] . ' as br', 'br.id', '=', 'b.' . $cfg['record_fk'])
            ->where('b.id', $request->broadsheet_id)
            ->first(['b.id', 'b.subjectclass_id', 'b.term_id', 'br.session_id']);

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Score row not found.'], 404);
        }

        $assignment = $this->findAssignment((int) $row->subjectclass_id, (int) $row->term_id, (int) $row->session_id);
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'This score row is not in your vetting assignments.'], 403);
        }

        try {
            DB::table($cfg['sheet_table'])->where('id', $row->id)->update([
                'vettedstatus' => (int) $request->vettedstatus,
                'vettedby'     => Auth::id(),
                'updated_at'   => now(),
            ]);
            return response()->json(['success' => true] + $this->syncAssignmentStatus($assignment));
        } catch (\Throwable $e) {
            Log::error('Failed to update vetted status', ['table' => $cfg['sheet_table'], 'id' => $row->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update vetted status.'], 500);
        }
    }

    // =========================================================================
    // BULK VET
    // =========================================================================

    public function bulkVet(Request $request): JsonResponse
    {
        $cfg = $this->config();
        $request->validate([
            'subjectclass_id'  => 'required|integer',
            'term_id'          => 'required|integer',
            'session_id'       => 'required|integer',
            'vettedstatus'     => 'required|in:0,1',
            'broadsheet_ids'   => 'nullable|array',
            'broadsheet_ids.*' => 'integer',
        ]);

        $assignment = $this->findAssignment((int) $request->subjectclass_id, (int) $request->term_id, (int) $request->session_id);
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Not in your vetting assignments.'], 403);
        }

        // Only rows that genuinely belong to this assignment can be touched.
        $ids = $this->rowsQuery((int) $request->subjectclass_id, (int) $request->term_id, (int) $request->session_id)
            ->when($request->filled('broadsheet_ids'), fn ($q) => $q->whereIn('b.id', $request->broadsheet_ids))
            ->pluck('b.id');

        try {
            DB::table($cfg['sheet_table'])->whereIn('id', $ids)->update([
                'vettedstatus' => (int) $request->vettedstatus,
                'vettedby'     => Auth::id(),
                'updated_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Bulk vet failed', ['table' => $cfg['sheet_table'], 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update vetted status.'], 500);
        }

        return response()->json([
            'success' => true,
            'updated' => $ids->count(),
            'ids'     => $ids->values(),
        ] + $this->syncAssignmentStatus($assignment));
    }

    // =========================================================================
    // ASSIGNMENT STATUS
    // =========================================================================

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $cfg = $this->config();
        $request->validate(['status' => 'required|in:' . implode(',', self::STATUSES)]);

        $assignment = DB::table($cfg['assignment_table'])->where('id', $id)->where('userid', Auth::id())->first();
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        if ($request->status === 'completed') {
            $p = $this->progressFor($assignment);
            if ($p['students'] > 0 && $p['vetted'] < $p['students']) {
                return response()->json([
                    'success' => false,
                    'message' => "Only {$p['vetted']} of {$p['students']} students are vetted. Vet every student before marking this complete.",
                ], 422);
            }
        }

        $this->saveStatus($assignment, $request->status);

        return response()->json([
            'success' => true,
            'status'  => $request->status,
            'message' => match ($request->status) {
                'completed' => 'Marked as completed.',
                'rejected'  => 'Sent back to the subject teacher for corrections.',
                default     => 'Reopened for vetting.',
            },
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function assignmentsQuery(int $userId)
    {
        $t = $this->config()['assignment_table'];

        return DB::table($t . ' as sv')
            ->leftJoin('subjectclass as sjc',  'sjc.id', '=', 'sv.subjectclassid')
            ->leftJoin('schoolclass as sc',    'sc.id',  '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm',     'arm.id', '=', 'sc.arm')
            ->leftJoin('subjectteacher as st', 'st.id',  '=', 'sjc.subjectteacherid')
            ->leftJoin('subject as s',         's.id',   '=', 'st.subjectid')
            ->leftJoin('users as teacher',     'teacher.id', '=', 'st.staffid')
            ->leftJoin('schoolterm as t',      't.id',   '=', 'sv.termid')
            ->leftJoin('schoolsession as ss',  'ss.id',  '=', 'sv.sessionid')
            ->where('sv.userid', $userId)
            ->orderByDesc('sv.sessionid')->orderBy('sv.termid')->orderBy('s.subject')->orderBy('sc.schoolclass')
            ->select([
                'sv.id          as svid',
                'sjc.id         as subjectclassid',
                'sc.id          as schoolclassid',
                'sc.schoolclass as sclass',
                'arm.arm        as schoolarm',
                'st.staffid     as staffid',
                's.id           as subjectid',
                's.subject      as subjectname',
                's.subject_code as subjectcode',
                'teacher.name   as teachername',
                't.id           as termid',
                't.term         as termname',
                'ss.id          as sessionid',
                'ss.session     as sessionname',
                'sv.status      as status',
                'sv.updated_at  as updated_at',
            ]);
    }

    /** Score rows for one subject-class / term / session. */
    protected function rowsQuery(int $subjectclassId, int $termId, int $sessionId)
    {
        $cfg = $this->config();

        return DB::table($cfg['sheet_table'] . ' as b')
            ->join($cfg['record_table'] . ' as br', 'br.id', '=', 'b.' . $cfg['record_fk'])
            ->where('b.subjectclass_id', $subjectclassId)
            ->where('b.term_id', $termId)
            ->where('br.session_id', $sessionId);
    }

    /** Adds students / vetted / entered / percent to each assignment in one grouped query. */
    protected function attachProgress(Collection $rows): Collection
    {
        $cfg = $this->config();
        $ids = $rows->pluck('subjectclassid')->filter()->unique()->values();

        $progress = $ids->isEmpty() ? collect() : DB::table($cfg['sheet_table'] . ' as b')
            ->join($cfg['record_table'] . ' as br', 'br.id', '=', 'b.' . $cfg['record_fk'])
            ->whereIn('b.subjectclass_id', $ids)
            ->groupBy('b.subjectclass_id', 'b.term_id', 'br.session_id')
            ->select([
                'b.subjectclass_id', 'b.term_id', 'br.session_id',
                DB::raw('COUNT(*) as students'),
                DB::raw('SUM(CASE WHEN b.vettedstatus = 1 THEN 1 ELSE 0 END) as vetted'),
                DB::raw('SUM(CASE WHEN b.total > 0 THEN 1 ELSE 0 END) as entered'),
            ])
            ->get()
            ->keyBy(fn ($p) => "{$p->subjectclass_id}_{$p->term_id}_{$p->session_id}");

        return $rows->map(function ($r) use ($progress) {
            $p = $progress["{$r->subjectclassid}_{$r->termid}_{$r->sessionid}"] ?? null;
            $r->students = (int) ($p->students ?? 0);
            $r->vetted   = (int) ($p->vetted ?? 0);
            $r->entered  = (int) ($p->entered ?? 0);
            $r->percent  = $r->students ? (int) round($r->vetted / $r->students * 100) : 0;
            $r->status   = in_array($r->status, self::STATUSES, true) ? $r->status : 'pending';
            return $r;
        });
    }

    /** Column names are read case-insensitively (the migration spells it subjectclassId). */
    protected function subjectclassIdOf(object $a): int
    {
        $attrs = array_change_key_case((array) $a, CASE_LOWER);
        return (int) ($attrs['subjectclassid'] ?? 0);
    }

    protected function progressFor(object $a): array
    {
        $q = $this->rowsQuery($this->subjectclassIdOf($a), (int) $a->termid, (int) $a->sessionid);
        return [
            'students' => (clone $q)->count(),
            'vetted'   => (clone $q)->where('b.vettedstatus', 1)->count(),
        ];
    }

    protected function saveStatus(object $a, string $status): void
    {
        DB::table($this->config()['assignment_table'])->where('id', $a->id)
            ->update(['status' => $status, 'updated_at' => now()]);
        $a->status = $status;
    }

    protected function syncAssignmentStatus(object $a): array
    {
        $p = $this->progressFor($a);
        $allVetted = $p['students'] > 0 && $p['vetted'] === $p['students'];

        if ($allVetted && !in_array($a->status, ['completed', 'rejected'], true)) {
            $this->saveStatus($a, 'completed');
        } elseif (!$allVetted && $a->status === 'completed') {
            $this->saveStatus($a, 'pending');
        }

        return [
            'status'   => $a->status,
            'students' => $p['students'],
            'vetted'   => $p['vetted'],
            'percent'  => $p['students'] ? (int) round($p['vetted'] / $p['students'] * 100) : 0,
        ];
    }

    protected function findAssignment(int $subjectclassId, int $termId, int $sessionId): ?object
    {
        return DB::table($this->config()['assignment_table'])
            ->where('userid', Auth::id())
            ->where('subjectclassid', $subjectclassId)
            ->where('termid', $termId)
            ->where('sessionid', $sessionId)
            ->first();
    }

    /**
     * Admins who manage vetting assignments may open any broadsheet
     * (read-only). Checked by permission NAME via the DB so an unseeded
     * permission never throws.
     */
    protected function canViewAnyBroadsheet(): bool
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'getAllPermissions')) return false;
        $names = $this->config()['admin_permissions'] ?? [];
        return $user->getAllPermissions()->pluck('name')->intersect($names)->isNotEmpty();
    }

    /** Rows for the broadsheet page, exactly as stored by score entry. */
    protected function getSheetRows(int $subjectclassId, int $termId, int $sessionId): Collection
    {
        $cfg = $this->config();

        $cols = [
            'b.id',
            'br.student_id',
            'sr.admissionNo as admissionno',
            'sr.firstname   as fname',
            'sr.lastname    as lname',
            'sr.othername   as mname',
            DB::raw('(SELECT sp.picture FROM studentpicture sp WHERE sp.studentid = sr.id ORDER BY sp.id DESC LIMIT 1) as picture'),
            'b.total',
            'b.grade',
            'b.remark',
            'b.subject_position_class as position',
            'b.vettedstatus',
        ];
        if ($cfg['mode'] === 'terminal') {
            array_push($cols, 'b.bf', 'b.cum', 'b.cum_ave');
        } else {
            $cols[] = 'b.exam';
        }

        $rows = $this->rowsQuery($subjectclassId, $termId, $sessionId)
            ->leftJoin('studentRegistration as sr', 'sr.id', '=', 'br.student_id')
            ->orderBy('sr.lastname')->orderBy('sr.firstname')
            ->get($cols);

        // Per-assessment scores (terminal only), attached as $row->scores[assessment_id].
        if ($cfg['mode'] === 'terminal' && $rows->isNotEmpty()) {
            $scores = DB::table('broadsheet_assessment_scores')
                ->whereIn('broadsheet_id', $rows->pluck('id'))
                ->get(['broadsheet_id', 'assessment_id', 'score'])
                ->groupBy('broadsheet_id');
            $rows->each(function ($r) use ($scores) {
                $r->scores = ($scores[$r->id] ?? collect())->pluck('score', 'assessment_id')->all();
            });
        }

        return $rows;
    }
}
