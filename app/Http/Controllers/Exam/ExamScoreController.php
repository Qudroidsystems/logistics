<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Models\ExamQuestionScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Per-question score entry: a students × questions grid for one paper. Scores
 * roll up (via the topic tags) into per-topic performance used by the coverage
 * report and, later, the parent AI report. This does NOT touch the broadsheet —
 * terminal scores stay in the existing results engine.
 */
class ExamScoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Write exam papers|Vet exam papers');
    }

    public function grid(ExamPaper $paper)
    {
        $this->authorizeOwner($paper);
        $paper->load('questions');

        $students = $this->roster($paper);
        $scores = ExamQuestionScore::whereIn('exam_question_id', $paper->questions->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->keyBy('exam_question_id')->map(fn ($r) => $r->score));

        return view('exam.scores.grid', [
            'pagetitle' => 'Enter Scores — '.$paper->title,
            'paper'     => $paper,
            'label'     => $this->labelsFor([$paper->subjectclass_id])[$paper->subjectclass_id] ?? '',
            'students'  => $students,
            'scores'    => $scores,
        ]);
    }

    public function save(Request $request, ExamPaper $paper)
    {
        $this->authorizeOwner($paper);
        $paper->load('questions');

        $maxByQ = $paper->questions->pluck('marks', 'id');
        $input  = (array) $request->input('score', []);   // score[studentId][questionId] = value

        $rosterIds = $this->roster($paper)->pluck('id')->map(fn ($v) => (int) $v)->all();
        $qIds      = $paper->questions->pluck('id')->map(fn ($v) => (int) $v)->all();

        $saved = 0;
        DB::transaction(function () use ($input, $maxByQ, $rosterIds, $qIds, &$saved) {
            foreach ($input as $sid => $row) {
                $sid = (int) $sid;
                if (!in_array($sid, $rosterIds, true)) continue;
                foreach ((array) $row as $qid => $val) {
                    $qid = (int) $qid;
                    if (!in_array($qid, $qIds, true)) continue;

                    $raw = trim((string) $val);
                    if ($raw === '') {
                        ExamQuestionScore::where('exam_question_id', $qid)->where('student_id', $sid)->delete();
                        continue;
                    }
                    $score = (float) $raw;
                    $max = (float) ($maxByQ[$qid] ?? 0);
                    if ($max > 0) $score = min($score, $max);
                    $score = max($score, 0);

                    ExamQuestionScore::updateOrCreate(
                        ['exam_question_id' => $qid, 'student_id' => $sid],
                        ['score' => $score]
                    );
                    $saved++;
                }
            }
        });

        return back()->with('success', "Saved {$saved} score(s).");
    }

    // ── roster ────────────────────────────────────────────────────────────────
    /** Students registered for this subject-class; falls back to the arm roster. */
    protected function roster(ExamPaper $paper)
    {
        $ids = DB::table('subjectRegistrationStatus')
            ->where('subjectclassid', $paper->subjectclass_id)
            ->whereIn('Status', ['active', 'Active', 'ACTIVE', '1', 1])
            ->pluck('studentid')->map(fn ($v) => (int) $v)->unique();

        if ($ids->isEmpty()) {
            $armId = DB::table('subjectclass')->where('id', $paper->subjectclass_id)->value('schoolclassid');
            if ($armId) {
                $q = DB::table('student_current_term')->where('schoolclassId', $armId);
                if ($paper->session_id) $q->where('sessionId', $paper->session_id);
                $ids = $q->pluck('studentId')->map(fn ($v) => (int) $v)->unique();
            }
        }
        if ($ids->isEmpty()) return collect();

        return DB::table('studentRegistration')
            ->whereIn('id', $ids->all())
            ->selectRaw("id, admissionNo,
                TRIM(CONCAT(COALESCE(lastname,''),' ',COALESCE(firstname,''),' ',COALESCE(othername,''))) as name")
            ->orderBy('lastname')->orderBy('firstname')->get();
    }

    protected function authorizeOwner(ExamPaper $paper): void
    {
        abort_unless((int) $paper->teacher_id === (int) Auth::id() || Auth::user()->can('Vet exam papers'),
            403, 'Not your paper.');
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
