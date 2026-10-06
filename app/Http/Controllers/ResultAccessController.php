<?php

namespace App\Http\Controllers;

use App\Models\ResultAccessException;
use App\Models\ResultAccessSetting;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Services\ResultAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin screen: control whether students who owe fees can see their results.
 *
 *  - Settings: on/off, which debt counts, threshold, mock reports, message.
 *  - Students (per class/term): who is blocked, who has an exception,
 *    grant / revoke exceptions (single or bulk), manual "block regardless".
 *  - History: every exception granted or revoked, by whom.
 */
class ResultAccessController extends Controller
{
    public function __construct(protected ResultAccessService $access)
    {
        $this->middleware('permission:View result-access');
        $this->middleware('permission:Update result-access')
            ->only(['saveSettings', 'grant', 'revoke', 'toggleManual']);
    }

    // =========================================================================
    // PAGE
    // =========================================================================

    public function index(): View
    {
        $currentSession = Schoolsession::where('status', 'Current')->first();
        $currentTerm    = Schoolterm::where('status', true)->first() ?? Schoolterm::orderBy('id')->first();

        return view('result-access.index', [
            'pagetitle'      => 'Result Access Control',
            'settings'       => ResultAccessSetting::current(),
            'sessions'       => Schoolsession::orderByDesc('id')->get(['id', 'session', 'status']),
            'terms'          => Schoolterm::orderBy('id')->get(['id', 'term']),
            'classes'        => DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                                    ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
                                    ->get(['schoolclass.id', DB::raw("TRIM(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) as name")]),
            'currentSession' => $currentSession,
            'currentTerm'    => $currentTerm,
            'stats'          => [
                'active_exceptions' => ResultAccessException::active()->count(),
                'manual_blocks'     => DB::table('studentRegistration')->where('can_view_assessments', false)->count(),
            ],
        ]);
    }

    // =========================================================================
    // SETTINGS
    // =========================================================================

    public function saveSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled'          => 'required|boolean',
            'debt_scope'       => 'required|in:' . implode(',', array_keys(ResultAccessSetting::SCOPES)),
            'threshold_type'   => 'required|in:' . implode(',', array_keys(ResultAccessSetting::THRESHOLDS)),
            'threshold_value'  => 'nullable|numeric|min:0',
            'apply_to_mock'    => 'required|boolean',
            'show_amount_owed' => 'required|boolean',
            'blocked_message'  => 'nullable|string|max:1000',
        ]);

        if ($data['threshold_type'] === 'percent' && (float) ($data['threshold_value'] ?? 0) >= 100) {
            return response()->json(['success' => false, 'message' => 'A percentage threshold must be below 100.'], 422);
        }

        $settings = ResultAccessSetting::current();
        $settings->fill($data + ['threshold_value' => 0]);
        $settings->threshold_value = (float) ($data['threshold_value'] ?? 0);
        $settings->updated_by = Auth::id();
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => $settings->enabled
                ? 'Saved. Students who owe fees are now blocked from their results (unless you grant an exception).'
                : 'Saved. Fee blocking is OFF — every student can see their results.',
        ]);
    }

    // =========================================================================
    // STUDENTS FOR A CLASS / TERM
    // =========================================================================

    public function students(Request $request): JsonResponse
    {
        $request->validate([
            'class_id'   => 'required|integer|exists:schoolclass,id',
            'session_id' => 'required|integer|exists:schoolsession,id',
            'term_id'    => 'required|integer|exists:schoolterm,id',
        ]);

        $classId   = (int) $request->class_id;
        $sessionId = (int) $request->session_id;
        $termId    = (int) $request->term_id;
        $settings  = ResultAccessSetting::current();

        // Class cohort: current placement + anyone with results or fees for it.
        $ids = collect()
            ->merge(DB::table('studentclass')->where('schoolclassid', $classId)->where('sessionid', $sessionId)->pluck('studentId'))
            ->merge(DB::table('broadsheet_records')->where('schoolclass_id', $classId)->where('session_id', $sessionId)->pluck('student_id'))
            ->merge(DB::table('student_bill_payment_book')->where('class_id', $classId)->where('session_id', $sessionId)->where('term_id', $termId)->pluck('student_id'))
            ->map(fn ($v) => (int) $v)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return response()->json(['success' => true, 'students' => [], 'summary' => $this->summary(collect())]);
        }

        $students = Student::whereIn('id', $ids)
            ->orderBy('lastname')->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname', 'othername', 'admissionNo', 'statusId', 'can_view_assessments']);

        $exceptions = ResultAccessException::whereIn('student_id', $ids)
            ->active()->covering($termId, $sessionId)
            ->orderByDesc('id')->get()->groupBy('student_id');

        $grantedByNames = DB::table('users')
            ->whereIn('id', $exceptions->flatten()->pluck('granted_by')->filter()->unique())
            ->pluck('name', 'id');

        $rows = $students->map(function ($s) use ($termId, $sessionId, $settings, $exceptions, $grantedByNames) {
            try {
                $debt = $this->access->debtFor($s, $termId, $sessionId, $settings);
            } catch (\Throwable $e) {
                Log::warning('Result access: debt calc failed', ['student' => $s->id, 'error' => $e->getMessage()]);
                $debt = ['owed' => 0.0, 'payable' => 0.0, 'term_owed' => 0.0, 'arrears' => 0.0];
            }

            $over      = $this->access->overThreshold($debt['owed'], $debt['payable'], $settings);
            $exception = optional($exceptions->get($s->id))->first();
            $manual    = !$s->can_view_assessments;

            $status = $manual ? 'manual_block'
                : (!$over ? ($debt['owed'] > 0.009 ? 'under_threshold' : 'clear')
                : ($exception ? 'exception' : ($settings->enabled ? 'blocked' : 'would_block')));

            return [
                'id'          => $s->id,
                'name'        => trim($s->lastname . ', ' . $s->firstname . ' ' . ($s->othername ?? ''), ', '),
                'admissionno' => $s->admissionNo,
                'owed'        => $debt['owed'],
                'term_owed'   => $debt['term_owed'],
                'arrears'     => $debt['arrears'],
                'payable'     => $debt['payable'],
                'percent_owed'=> $debt['payable'] > 0 ? round($debt['owed'] / $debt['payable'] * 100) : 0,
                'status'      => $status,
                'manual'      => $manual,
                'exception'   => $exception ? [
                    'id'         => $exception->id,
                    'all_terms'  => $exception->isAllTerms(),
                    'expires_on' => $exception->expires_on?->format('d M Y'),
                    'reason'     => $exception->reason,
                    'granted_by' => $grantedByNames[$exception->granted_by] ?? '—',
                    'granted_at' => $exception->created_at?->format('d M Y'),
                ] : null,
            ];
        });

        return response()->json(['success' => true, 'students' => $rows->values(), 'summary' => $this->summary($rows)]);
    }

    private function summary($rows): array
    {
        return [
            'total'     => $rows->count(),
            'owing'     => $rows->where('owed', '>', 0.009)->count(),
            'blocked'   => $rows->whereIn('status', ['blocked', 'would_block'])->count(),
            'exception' => $rows->where('status', 'exception')->count(),
            'manual'    => $rows->where('status', 'manual_block')->count(),
            'owed'      => round((float) $rows->sum('owed'), 2),
        ];
    }

    // =========================================================================
    // EXCEPTIONS
    // =========================================================================

    public function grant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:studentRegistration,id',
            'scope'         => 'required|in:term,all',
            'term_id'       => 'required_if:scope,term|nullable|integer|exists:schoolterm,id',
            'session_id'    => 'required_if:scope,term|nullable|integer|exists:schoolsession,id',
            'expires_on'    => 'nullable|date|after_or_equal:today',
            'reason'        => 'nullable|string|max:500',
        ]);

        $termId    = $data['scope'] === 'term' ? (int) $data['term_id'] : null;
        $sessionId = $data['scope'] === 'term' ? (int) $data['session_id'] : null;

        $created = 0;
        DB::transaction(function () use ($data, $termId, $sessionId, &$created) {
            foreach (array_unique($data['student_ids']) as $sid) {
                // Replace any active exception with the same scope, so the
                // newest expiry/reason is the one that applies.
                ResultAccessException::where('student_id', $sid)->active()
                    ->where('term_id', $termId)->where('session_id', $sessionId)
                    ->update(['revoked_at' => now(), 'revoked_by' => Auth::id()]);

                ResultAccessException::create([
                    'student_id' => $sid,
                    'term_id'    => $termId,
                    'session_id' => $sessionId,
                    'expires_on' => $data['expires_on'] ?? null,
                    'reason'     => $data['reason'] ?? null,
                    'granted_by' => Auth::id(),
                ]);
                $created++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Result access granted to {$created} student(s)"
                . ($termId ? ' for this term' : ' for all terms')
                . (!empty($data['expires_on']) ? ' until ' . \Carbon\Carbon::parse($data['expires_on'])->format('d M Y') : '') . '.',
        ]);
    }

    /** Revoke every active exception that covers this term for the given students (or one exception by id). */
    public function revoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exception_id'  => 'nullable|integer',
            'student_ids'   => 'required_without:exception_id|array',
            'student_ids.*' => 'integer',
            'term_id'       => 'required_without:exception_id|nullable|integer',
            'session_id'    => 'required_without:exception_id|nullable|integer',
        ]);

        $q = ResultAccessException::active();
        if (!empty($data['exception_id'])) {
            $q->where('id', $data['exception_id']);
        } else {
            $q->whereIn('student_id', $data['student_ids'])->covering((int) $data['term_id'], (int) $data['session_id']);
        }

        $n = $q->update(['revoked_at' => now(), 'revoked_by' => Auth::id()]);

        return response()->json([
            'success' => $n > 0,
            'message' => $n ? "Revoked {$n} exception(s). Those students are blocked again if they still owe." : 'No active exception to revoke.',
        ], $n ? 200 : 404);
    }

    /** Manual "block regardless of fees" switch (studentRegistration.can_view_assessments). */
    public function toggleManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:studentRegistration,id',
            'can_view'      => 'required|boolean',
        ]);

        $n = DB::table('studentRegistration')->whereIn('id', $data['student_ids'])
            ->update(['can_view_assessments' => (bool) $data['can_view']]);

        return response()->json([
            'success' => true,
            'message' => $data['can_view']
                ? "Manual block removed for {$n} student(s)."
                : "{$n} student(s) blocked from results regardless of fees.",
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $rows = ResultAccessException::query()
            ->leftJoin('studentRegistration as s', 's.id', '=', 'result_access_exceptions.student_id')
            ->leftJoin('schoolterm as t', 't.id', '=', 'result_access_exceptions.term_id')
            ->leftJoin('schoolsession as ss', 'ss.id', '=', 'result_access_exceptions.session_id')
            ->leftJoin('users as g', 'g.id', '=', 'result_access_exceptions.granted_by')
            ->leftJoin('users as r', 'r.id', '=', 'result_access_exceptions.revoked_by')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('s.firstname', 'like', $term)->orWhere('s.lastname', 'like', $term)->orWhere('s.admissionNo', 'like', $term));
            })
            ->orderByDesc('result_access_exceptions.id')
            ->limit(200)
            ->get([
                'result_access_exceptions.*',
                DB::raw("TRIM(CONCAT(COALESCE(s.lastname,''), ', ', COALESCE(s.firstname,''))) as student_name"),
                's.admissionNo as admissionno',
                't.term', 'ss.session',
                'g.name as granted_by_name', 'r.name as revoked_by_name',
            ])
            ->map(function ($e) {
                $expired = $e->expires_on && $e->expires_on->lt(now()->startOfDay());
                return [
                    'id'          => $e->id,
                    'student'     => $e->student_name,
                    'admissionno' => $e->admissionno,
                    'covers'      => $e->term_id ? ($e->term . ' · ' . $e->session) : 'All terms',
                    'expires_on'  => $e->expires_on?->format('d M Y'),
                    'reason'      => $e->reason,
                    'granted_by'  => $e->granted_by_name ?? '—',
                    'granted_at'  => $e->created_at?->format('d M Y, H:i'),
                    'revoked_by'  => $e->revoked_by_name,
                    'revoked_at'  => $e->revoked_at?->format('d M Y, H:i'),
                    'state'       => $e->revoked_at ? 'revoked' : ($expired ? 'expired' : 'active'),
                ];
            });

        return response()->json(['success' => true, 'history' => $rows]);
    }
}
