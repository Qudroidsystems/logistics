<?php

namespace App\Http\Controllers;

use App\Models\ReportApproval;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Services\Reporting\ReportApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Report card approval board: class teachers submit, the principal approves
 * (releases) or returns with a note.
 */
class ReportApprovalController extends Controller
{
    public function __construct(protected ReportApprovalService $svc)
    {
        $this->middleware('permission:View report-approvals|Submit report cards|Approve report cards')->only(['index', 'open', 'show', 'preview', 'submit']);
        $this->middleware('permission:Approve report cards')->only(['approve', 'returnBack', 'reopen', 'approveAll', 'settings']);
    }

    public function index(Request $request)
    {
        [$sessions, $terms, $sessionId, $termId] = $this->period($request);
        $user = $request->user();

        // Class teachers without approval rights only see their own classes.
        $only = null;
        if (!$user->can('Approve report cards') && !$user->can('View report-approvals')) {
            $only = DB::table('classteacher')->where('staffid', $user->id)->where('sessionid', $sessionId)->pluck('schoolclassid')->map(fn ($v) => (int) $v)->all();
        }
        $board = $this->svc->board($termId, $sessionId, $only);
        $counts = $board->countBy('status');

        return view('report-approvals.index', [
            'pagetitle' => 'Report Card Approval',
            'board' => $board, 'counts' => $counts,
            'sessions' => $sessions, 'terms' => $terms, 'sessionId' => $sessionId, 'termId' => $termId,
            'settings' => ReportApprovalService::settings(),
            'statusFilter' => $request->get('status'),
        ]);
    }

    public function open(Request $request, int $class, int $term, int $session)
    {
        $a = $this->svc->record($class, $term, $session);
        return redirect()->route('report-approvals.show', $a);
    }

    public function show(Request $request, ReportApproval $approval)
    {
        $user = $request->user();
        abort_unless($user->can('Approve report cards') || $user->can('View report-approvals')
            || $this->svc->isClassTeacher($user, $approval->schoolclass_id, $approval->session_id), 403);

        $r = $this->svc->readiness($approval->schoolclass_id, $approval->term_id, $approval->session_id);

        $students = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->join('studentRegistration as s', 's.id', '=', 'br.student_id')
            ->where('br.schoolclass_id', $approval->schoolclass_id)->where('br.session_id', $approval->session_id)->where('b.term_id', $approval->term_id)
            ->groupBy('s.id', 's.firstname', 's.lastname', 's.admissionNo')
            ->orderBy('s.lastname')->orderBy('s.firstname')
            ->get(['s.id', 's.firstname', 's.lastname', 's.admissionNo',
                   DB::raw('COUNT(*) as entries'), DB::raw('SUM(b.vettedstatus = 1) as vetted'), DB::raw('ROUND(AVG(b.total), 1) as average')]);

        $comments = DB::table('studentpersonalityprofiles')->where('schoolclassid', $approval->schoolclass_id)
            ->where('termid', $approval->term_id)->where('sessionid', $approval->session_id)
            ->get(['studentid', 'classteachercomment', 'principalscomment'])->keyBy('studentid');

        $userNames = DB::table('users')->whereIn('id', collect($approval->history ?? [])->pluck('by')->filter()->unique())->pluck('name', 'id');

        return view('report-approvals.show', [
            'pagetitle' => 'Report Cards — ' . $this->svc->label($approval),
            'a' => $approval, 'r' => $r, 'students' => $students, 'comments' => $comments,
            'label' => $this->svc->label($approval), 'userNames' => $userNames,
            'canSubmit' => $this->svc->canSubmit($user, $approval->schoolclass_id, $approval->session_id),
            'canApprove' => $user->can('Approve report cards'),
        ]);
    }

    public function preview(Request $request, ReportApproval $approval, int $student)
    {
        $user = $request->user();
        abort_unless($user->can('Approve report cards') || $user->can('View report-approvals')
            || $this->svc->isClassTeacher($user, $approval->schoolclass_id, $approval->session_id), 403);

        $pdf = app(ViewStudentReportController::class)->renderReportCardPdf($student, $approval->schoolclass_id, $approval->session_id, $approval->term_id);
        abort_unless($pdf, 404, 'The report card could not be generated.');
        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Report_Card_Preview.pdf"']);
    }

    public function submit(Request $request, ReportApproval $approval)
    {
        abort_unless($this->svc->canSubmit($request->user(), $approval->schoolclass_id, $approval->session_id), 403, 'Only the class teacher can submit this class.');
        if ($approval->status === 'approved') return back()->with('error', 'Already approved.');

        $r = $this->svc->readiness($approval->schoolclass_id, $approval->term_id, $approval->session_id);
        if (!$r) return back()->with('error', 'There are no results for this class and term yet.');
        if (!$r->ready && !$request->boolean('force')) {
            return back()->with('error', ($r->entries - $r->vetted) . ' subject score(s) are not vetted yet. Get them vetted first, or tick "submit anyway".');
        }
        $cfg = ReportApprovalService::settings()->config ?? [];
        if (!empty($cfg['require_comments']) && $r->t_comments < $r->students) {
            return back()->with('error', 'Class teacher comments are missing for ' . ($r->students - $r->t_comments) . ' student(s).');
        }

        $this->svc->submit($approval, $request->user(), $request->input('note'));
        return back()->with('success', 'Submitted for approval. The principal has been notified.');
    }

    public function approve(Request $request, ReportApproval $approval)
    {
        $this->svc->approve($approval, $request->user(), $request->input('note'));
        return back()->with('success', 'Approved. ' . (ReportApprovalService::enforced() ? 'Students and parents can now see these results.' : ''));
    }

    public function returnBack(Request $request, ReportApproval $approval)
    {
        $request->validate(['note' => 'required|string|max:500']);
        $this->svc->returnToTeacher($approval, $request->user(), $request->note);
        return back()->with('success', 'Returned to the class teacher with your note.');
    }

    public function reopen(Request $request, ReportApproval $approval)
    {
        $this->svc->reopen($approval, $request->user(), $request->input('note'));
        return back()->with('success', 'Approval withdrawn.' . (ReportApprovalService::enforced() ? ' These results are hidden from students and parents again.' : ''));
    }

    /** Approve every submitted class for the term. */
    public function approveAll(Request $request)
    {
        $data = $request->validate(['term_id' => 'required|integer', 'session_id' => 'required|integer']);
        $list = ReportApproval::where('term_id', $data['term_id'])->where('session_id', $data['session_id'])->where('status', 'submitted')->get();
        foreach ($list as $a) $this->svc->approve($a, $request->user(), 'Approved in bulk');
        return back()->with('success', $list->count() . ' class(es) approved.');
    }

    public function settings(Request $request)
    {
        $s = ReportApprovalService::settings();
        $s->is_active = $request->boolean('enforce');
        $s->config = array_merge($s->config ?? [], [
            'notify' => $request->boolean('notify'),
            'require_comments' => $request->boolean('require_comments'),
        ]);
        $s->updated_by = $request->user()->id;
        $s->save();

        return back()->with('success', $s->is_active
            ? 'Approval is now required. Students and parents only see results for approved classes.'
            : 'Approval is not required. Results show as soon as they are vetted (fee rules still apply).');
    }

    protected function period(Request $request): array
    {
        $sessions = Schoolsession::orderByDesc('id')->get(['id', 'session', 'status']);
        $terms    = Schoolterm::orderBy('id')->get(['id', 'term']);
        $sessionId = (int) ($request->get('session_id') ?: (Schoolsession::where('status', 'Current')->value('id') ?? $sessions->first()?->id));
        $termId    = (int) ($request->get('term_id') ?: (Schoolterm::where('status', true)->value('id') ?? $terms->first()?->id));
        return [$sessions, $terms, $sessionId, $termId];
    }
}
