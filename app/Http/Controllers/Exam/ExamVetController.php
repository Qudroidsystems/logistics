<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Models\ExamVetComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * HOD / Exam Officer vetting: review a submitted paper, leave per-question or
 * paper-level comments, then approve, request changes, or lock for printing.
 * Every action is written to exam_vet_comments as an audit trail.
 */
class ExamVetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Vet exam papers');
    }

    /** Papers awaiting attention. */
    public function queue(Request $request)
    {
        $status = $request->get('status', 'submitted');
        $papers = ExamPaper::query()
            ->when(in_array($status, array_keys(ExamPaper::STATUS), true),
                fn ($q) => $q->where('status', $status),
                fn ($q) => $q->whereIn('status', ['submitted', 'changes_requested']))
            ->orderBy('updated_at')
            ->paginate(20)->withQueryString();

        return view('exam.vet.queue', [
            'pagetitle' => 'Exam Vetting',
            'papers'    => $papers,
            'labels'    => $this->labelsFor($papers->pluck('subjectclass_id')->all()),
            'teachers'  => $this->teacherNames($papers->pluck('teacher_id')->all()),
            'statuses'  => ExamPaper::STATUS,
            'status'    => $status,
        ]);
    }

    public function review(ExamPaper $paper)
    {
        $paper->load(['questions.topics', 'comments.user']);

        // group comments by question id for inline display
        $byQuestion = $paper->comments->whereNotNull('exam_question_id')->groupBy('exam_question_id');
        $paperLevel = $paper->comments->whereNull('exam_question_id');

        // coverage snapshot (how much of the taught syllabus this paper tests)
        $coverage = app(ExamCoverageController::class)->snapshot($paper);

        return view('exam.vet.review', [
            'pagetitle'   => 'Vetting — '.$paper->title,
            'paper'       => $paper,
            'label'       => $this->labelsFor([$paper->subjectclass_id])[$paper->subjectclass_id] ?? '',
            'teacher'     => $this->teacherNames([$paper->teacher_id])[$paper->teacher_id] ?? 'Teacher',
            'byQuestion'  => $byQuestion,
            'paperLevel'  => $paperLevel,
            'coverage'    => $coverage,
        ]);
    }

    /** Add a comment (per-question when exam_question_id is present, else paper-level). */
    public function comment(Request $request, ExamPaper $paper)
    {
        $data = $request->validate([
            'exam_question_id' => 'nullable|integer',
            'comment'          => 'required|string|max:3000',
        ]);

        ExamVetComment::create([
            'exam_paper_id'    => $paper->id,
            'exam_question_id' => $data['exam_question_id'] ?: null,
            'user_id'          => Auth::id(),
            'comment'          => $data['comment'],
            'action'           => 'comment',
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function approve(Request $request, ExamPaper $paper)
    {
        abort_unless(in_array($paper->status, ['submitted', 'changes_requested'], true), 422, 'Paper is not awaiting vetting.');

        $paper->update([
            'status'      => 'approved',
            'vetted_by'   => Auth::id(),
            'vetted_at'   => now(),
            'vet_summary' => $request->input('comment') ?: $paper->vet_summary,
        ]);
        $this->log($paper, 'approve', $request->input('comment') ?: 'Approved.');
        $this->notifyTeacher($paper, 'Your exam paper "'.$paper->title.'" has been approved.');

        return redirect()->route('exam.vet.queue')->with('success', 'Paper approved.');
    }

    public function requestChanges(Request $request, ExamPaper $paper)
    {
        $data = $request->validate(['comment' => 'required|string|max:3000']);
        abort_unless(in_array($paper->status, ['submitted', 'changes_requested'], true), 422, 'Paper is not awaiting vetting.');

        $paper->update(['status' => 'changes_requested', 'vet_summary' => $data['comment']]);
        $this->log($paper, 'return', $data['comment']);
        $this->notifyTeacher($paper, 'Changes were requested on your exam paper "'.$paper->title.'".');

        return redirect()->route('exam.vet.queue')->with('success', 'Returned to the teacher.');
    }

    public function lock(ExamPaper $paper)
    {
        abort_unless($paper->status === 'approved', 422, 'Only an approved paper can be locked.');
        $paper->update(['status' => 'locked', 'locked_by' => Auth::id(), 'locked_at' => now()]);
        $this->log($paper, 'lock', 'Locked for printing.');

        return back()->with('success', 'Paper locked.');
    }

    public function unlock(ExamPaper $paper)
    {
        abort_unless($paper->status === 'locked', 422, 'Paper is not locked.');
        $paper->update(['status' => 'approved', 'locked_by' => null, 'locked_at' => null]);
        $this->log($paper, 'unlock', 'Unlocked.');

        return back()->with('success', 'Paper unlocked.');
    }

    // ── helpers ────────────────────────────────────────────────────────────
    protected function log(ExamPaper $paper, string $action, string $comment): void
    {
        ExamVetComment::create([
            'exam_paper_id' => $paper->id, 'user_id' => Auth::id(),
            'action' => $action, 'comment' => $comment,
        ]);
    }

    /** Best-effort in-app notification to the paper's teacher. */
    protected function notifyTeacher(ExamPaper $paper, string $message): void
    {
        if (!$paper->teacher_id) return;
        try {
            if (class_exists(\App\Services\Messaging\PortalNotifier::class)) {
                \App\Services\Messaging\PortalNotifier::toUsers(
                    [(int) $paper->teacher_id], 'Exam vetting', $message,
                    route('exam.papers.show', $paper), 'notice'
                );
            }
        } catch (\Throwable $e) {
            // notification is non-critical
        }
    }

    protected function teacherNames(array $ids): array
    {
        $ids = array_values(array_filter(array_unique($ids)));
        if (!$ids) return [];
        return DB::table('users')->whereIn('id', $ids)->pluck('name', 'id')->toArray();
    }

    protected function labelsFor(array $scIds): array
    {
        $scIds = array_values(array_filter(array_unique($scIds)));
        if (!$scIds) return [];
        return DB::table('subjectclass as sjc')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->whereIn('sjc.id', $scIds)
            ->selectRaw("sjc.id, TRIM(CONCAT(s.subject,' — ',c.schoolclass,' ',COALESCE(arm.arm,''))) as label")
            ->pluck('label', 'id')->toArray();
    }
}
