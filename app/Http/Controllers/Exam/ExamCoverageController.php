<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Models\SubjectTopic;
use App\Models\TopicProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Coverage & alignment (Phase 4, item 14). Pure logic now; AI narration later.
 *
 *  - Coverage  : how much of the (taught) syllabus the paper actually examines.
 *  - Alignment : how many marks sit on topics the class has been taught.
 *  - Flags     : marks on untaught topics, taught-but-not-examined, untagged Qs.
 *  - Per-topic performance (term-end): average score % per topic from the
 *    per-question scores, to surface weak topics.
 */
class ExamCoverageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Write exam papers|Vet exam papers|Manage topics');
    }

    /** Compact figures used by the vetting screen and the paper card. */
    public function snapshot(ExamPaper $paper): array
    {
        $paper->loadMissing('questions.topics');

        $syllabus = SubjectTopic::where('subject_id', $paper->subject_id)
            ->where('class_level', $paper->class_level)
            ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $paper->term_id))
            ->get(['id', 'title']);
        $syllabusIds = $syllabus->pluck('id');

        $taughtIds = TopicProgress::where('subjectclass_id', $paper->subjectclass_id)
            ->whereIn('status', ['taught', 'confirmed'])
            ->pluck('subject_topic_id')->map(fn ($v) => (int) $v)->unique();

        $examinedIds = collect();
        $untaggedCount = 0;
        $untaughtMarks = 0.0;
        $totalMarks = 0.0;
        $taughtMarks = 0.0;

        foreach ($paper->questions as $q) {
            $totalMarks += (float) $q->marks;
            $tids = $q->topics->pluck('id');
            if ($tids->isEmpty()) { $untaggedCount++; continue; }
            $examinedIds = $examinedIds->merge($tids);
            $isTaught = $tids->contains(fn ($id) => $taughtIds->contains((int) $id));
            if ($isTaught) $taughtMarks += (float) $q->marks;
            else           $untaughtMarks += (float) $q->marks;
        }
        $examinedIds = $examinedIds->unique();

        $examinedInSyllabus = $examinedIds->intersect($syllabusIds)->count();
        $taughtNotExamined = $syllabus->filter(fn ($t) =>
            $taughtIds->contains((int) $t->id) && !$examinedIds->contains((int) $t->id)
        )->pluck('title')->values()->all();

        return [
            'syllabus_total'      => $syllabusIds->count(),
            'taught_count'        => $taughtIds->intersect($syllabusIds)->count(),
            'examined_count'      => $examinedInSyllabus,
            'examined_pct'        => $syllabusIds->count() ? (int) round($examinedInSyllabus / $syllabusIds->count() * 100) : 0,
            'alignment_pct'       => $totalMarks > 0 ? (int) round($taughtMarks / $totalMarks * 100) : 0,
            'untagged_count'      => $untaggedCount,
            'untaught_marks'      => round($untaughtMarks, 2),
            'total_marks'         => round($totalMarks, 2),
            'taught_not_examined' => $taughtNotExamined,
        ];
    }

    /** Full per-paper coverage report with per-topic breakdown. */
    public function report(ExamPaper $paper)
    {
        $this->authorizeView($paper);
        $paper->load('questions.topics');
        $snap = $this->snapshot($paper);

        $syllabus = SubjectTopic::where('subject_id', $paper->subject_id)
            ->where('class_level', $paper->class_level)
            ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $paper->term_id))
            ->orderBy('week_no')->orderBy('position')->orderBy('id')->get();

        $taughtIds = TopicProgress::where('subjectclass_id', $paper->subjectclass_id)
            ->whereIn('status', ['taught', 'confirmed'])
            ->pluck('subject_topic_id')->map(fn ($v) => (int) $v)->unique();

        // map topic_id => [questionIds, marks]
        $byTopic = [];
        foreach ($paper->questions as $q) {
            foreach ($q->topics as $t) {
                $byTopic[$t->id]['qids'][] = $q->id;
                $byTopic[$t->id]['marks'] = ($byTopic[$t->id]['marks'] ?? 0) + (float) $q->marks;
                $byTopic[$t->id]['qmax'][$q->id] = (float) $q->marks;
            }
        }

        // per-topic average performance from entered scores
        $perf = $this->topicPerformance($byTopic);

        $rows = $syllabus->map(function ($t) use ($byTopic, $taughtIds, $perf) {
            $examined = isset($byTopic[$t->id]);
            return (object) [
                'title'     => $t->title,
                'week_no'   => $t->week_no,
                'taught'    => $taughtIds->contains((int) $t->id),
                'examined'  => $examined,
                'marks'     => $examined ? round($byTopic[$t->id]['marks'], 2) : 0,
                'questions' => $examined ? count(array_unique($byTopic[$t->id]['qids'])) : 0,
                'avg_pct'   => $perf[$t->id] ?? null,
            ];
        });

        // questions tagged to topics outside the syllabus (orphan tags) + untagged
        $untagged = $paper->questions->filter(fn ($q) => $q->topics->isEmpty())->values();

        return view('exam.coverage.report', [
            'pagetitle' => 'Coverage — '.$paper->title,
            'paper'     => $paper,
            'label'     => $this->labelsFor([$paper->subjectclass_id])[$paper->subjectclass_id] ?? '',
            'snap'      => $snap,
            'rows'      => $rows,
            'untagged'  => $untagged,
        ]);
    }

    /** Term-end per-topic analysis across all approved/locked papers of a class. */
    public function index(Request $request)
    {
        $assignments = $this->assignmentOptions();
        $scId = (int) $request->get('subjectclass_id', optional($assignments->first())->subjectclass_id);

        $data = null;
        if ($scId) {
            $meta = $assignments->firstWhere('subjectclass_id', $scId) ?? $this->resolveMeta($scId);
            $papers = ExamPaper::where('subjectclass_id', $scId)
                ->whereIn('status', ['approved', 'locked'])->with('questions.topics')->get();

            $syllabus = SubjectTopic::where('subject_id', $meta->subject_id ?? 0)
                ->where('class_level', $meta->class_level ?? '')
                ->orderBy('week_no')->orderBy('position')->get();

            $taughtIds = TopicProgress::where('subjectclass_id', $scId)
                ->whereIn('status', ['taught', 'confirmed'])
                ->pluck('subject_topic_id')->map(fn ($v) => (int) $v)->unique();

            // aggregate questions per topic across all papers
            $byTopic = [];
            foreach ($papers as $p) {
                foreach ($p->questions as $q) {
                    foreach ($q->topics as $t) {
                        $byTopic[$t->id]['qids'][] = $q->id;
                        $byTopic[$t->id]['qmax'][$q->id] = (float) $q->marks;
                    }
                }
            }
            $perf = $this->topicPerformance($byTopic);

            $rows = $syllabus->map(fn ($t) => (object) [
                'title'    => $t->title,
                'week_no'  => $t->week_no,
                'taught'   => $taughtIds->contains((int) $t->id),
                'examined' => isset($byTopic[$t->id]),
                'questions'=> isset($byTopic[$t->id]) ? count(array_unique($byTopic[$t->id]['qids'])) : 0,
                'avg_pct'  => $perf[$t->id] ?? null,
            ]);

            $data = (object) [
                'meta'       => $meta,
                'papers'     => $papers,
                'rows'       => $rows,
                'taught'     => $taughtIds->intersect($syllabus->pluck('id'))->count(),
                'examined'   => collect(array_keys($byTopic))->intersect($syllabus->pluck('id'))->count(),
                'syllabus'   => $syllabus->count(),
            ];
        }

        return view('exam.coverage.index', [
            'pagetitle'   => 'Term-end Topic Analysis',
            'assignments' => $assignments,
            'scId'        => $scId,
            'data'        => $data,
        ]);
    }

    // ── helpers ────────────────────────────────────────────────────────────────
    /** For a map topic_id => ['qids'=>[], 'qmax'=>[qid=>marks]], returns topic_id => avg %. */
    protected function topicPerformance(array $byTopic): array
    {
        $allQids = [];
        foreach ($byTopic as $d) $allQids = array_merge($allQids, $d['qids'] ?? []);
        $allQids = array_values(array_unique($allQids));
        if (!$allQids) return [];

        $scores = DB::table('exam_question_scores')
            ->whereIn('exam_question_id', $allQids)
            ->whereNotNull('score')
            ->get(['exam_question_id', 'score']);
        if ($scores->isEmpty()) return [];

        $scoresByQ = $scores->groupBy('exam_question_id');

        $out = [];
        foreach ($byTopic as $tid => $d) {
            $ratios = [];
            foreach (array_unique($d['qids'] ?? []) as $qid) {
                $max = (float) ($d['qmax'][$qid] ?? 0);
                if ($max <= 0) continue;
                foreach (($scoresByQ[$qid] ?? collect()) as $s) {
                    $ratios[] = min((float) $s->score / $max, 1);
                }
            }
            if ($ratios) $out[$tid] = (int) round(array_sum($ratios) / count($ratios) * 100);
        }
        return $out;
    }

    protected function assignmentOptions()
    {
        $uid = Auth::id();
        $currentSession = DB::table('schoolsession')->where('status', 'Current')->value('id');
        $canAll = Auth::user()->can('Vet exam papers') || Auth::user()->can('Manage topics');

        $q = DB::table('subjectclass as sjc')
            ->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->when($currentSession, fn ($qq) => $qq->where('sjc.sessionid', $currentSession))
            ->when(!$canAll, fn ($qq) => $qq->where('st.staffid', $uid))
            ->selectRaw("sjc.id as subjectclass_id, sjc.subjectid as subject_id, c.schoolclass as class_level,
                TRIM(CONCAT(s.subject,' — ',c.schoolclass,' ',COALESCE(arm.arm,''))) as label")
            ->orderBy('label');

        return $q->get();
    }

    protected function resolveMeta(int $scId)
    {
        return DB::table('subjectclass as sjc')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->where('sjc.id', $scId)
            ->selectRaw('sjc.subjectid as subject_id, c.schoolclass as class_level')->first()
            ?? (object) ['subject_id' => 0, 'class_level' => ''];
    }

    protected function authorizeView(ExamPaper $paper): void
    {
        abort_unless(
            (int) $paper->teacher_id === (int) Auth::id()
            || Auth::user()->can('Vet exam papers')
            || Auth::user()->can('Manage topics'),
            403, 'Not allowed.'
        );
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
