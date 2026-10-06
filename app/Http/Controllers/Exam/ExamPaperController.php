<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Models\ExamQuestion;
use App\Models\ExamVetComment;
use App\Models\SubjectTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Teacher side of exam vetting: build an exam paper, tag each question to the
 * week's syllabus topics, set marks/difficulty, submit for HOD vetting, and
 * print the stamped paper once approved.
 */
class ExamPaperController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Write exam papers|Vet exam papers');
    }

    /** The teacher's own papers (plus any they may vet, if they also hold Vet). */
    public function index(Request $request)
    {
        $uid = Auth::id();
        $q = ExamPaper::query();

        // Teachers see only their own; vetters can optionally see all via ?scope=all
        if (!Auth::user()->can('Vet exam papers') || $request->get('scope') !== 'all') {
            $q->where('teacher_id', $uid);
        }
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }

        $papers = $q->orderByDesc('updated_at')->paginate(20)->withQueryString();

        $labels = $this->labelsFor($papers->pluck('subjectclass_id')->all());

        return view('exam.papers.index', [
            'pagetitle' => 'Exam Papers',
            'papers'    => $papers,
            'labels'    => $labels,
            'statuses'  => ExamPaper::STATUS,
            'canVetAll' => Auth::user()->can('Vet exam papers'),
            'scope'     => $request->get('scope'),
            'status'    => $status,
        ]);
    }

    public function create()
    {
        return $this->form(new ExamPaper(['exam_type' => 'exam']));
    }

    public function edit(ExamPaper $paper)
    {
        $this->authorizeOwner($paper);
        abort_unless($paper->isEditable(), 403, 'This paper is '.$paper->label()[0].' and can no longer be edited.');
        return $this->form($paper);
    }

    public function store(Request $request)
    {
        $data = $this->validatePaper($request);
        $sc = $this->ownedSubjectclass($data['subjectclass_id']);

        $paper = ExamPaper::create([
            'subjectclass_id'  => $sc->id,
            'subject_id'       => $sc->subject_id,
            'class_level'      => $sc->class_level,
            'term_id'          => $sc->term_id,
            'session_id'       => $sc->session_id,
            'teacher_id'       => Auth::id(),
            'title'            => $data['title'],
            'exam_type'        => $data['exam_type'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'instructions'     => $data['instructions'] ?? null,
            'status'           => 'draft',
        ]);

        $this->syncQuestions($paper, $request->input('questions'));
        $paper->recomputeTotal();

        return $this->afterSave($request, $paper, 'Exam paper created.');
    }

    public function update(Request $request, ExamPaper $paper)
    {
        $this->authorizeOwner($paper);
        abort_unless($paper->isEditable(), 403, 'This paper can no longer be edited.');

        $data = $this->validatePaper($request);
        $sc = $this->ownedSubjectclass($data['subjectclass_id']);

        $paper->update([
            'subjectclass_id'  => $sc->id,
            'subject_id'       => $sc->subject_id,
            'class_level'      => $sc->class_level,
            'term_id'          => $sc->term_id,
            'session_id'       => $sc->session_id,
            'title'            => $data['title'],
            'exam_type'        => $data['exam_type'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'instructions'     => $data['instructions'] ?? null,
        ]);

        $this->syncQuestions($paper, $request->input('questions'));
        $paper->recomputeTotal();

        return $this->afterSave($request, $paper, 'Exam paper saved.');
    }

    /** Teacher submits the paper for vetting. */
    public function submit(ExamPaper $paper)
    {
        $this->authorizeOwner($paper);
        abort_unless($paper->isEditable(), 403, 'Already submitted.');
        abort_if($paper->questions()->count() === 0, 422, 'Add at least one question before submitting.');

        $paper->recomputeTotal();
        $paper->update(['status' => 'submitted']);
        ExamVetComment::create([
            'exam_paper_id' => $paper->id, 'user_id' => Auth::id(),
            'action' => 'submit', 'comment' => 'Submitted for vetting.',
        ]);

        return redirect()->route('exam.papers.index')->with('success', 'Submitted for vetting.');
    }

    public function show(ExamPaper $paper)
    {
        $this->authorizeView($paper);
        $paper->load(['questions.topics']);
        return view('exam.papers.show', [
            'pagetitle' => $paper->title,
            'paper'     => $paper,
            'label'     => $this->labelsFor([$paper->subjectclass_id])[$paper->subjectclass_id] ?? '',
            'canVet'    => Auth::user()->can('Vet exam papers'),
        ]);
    }

    /** Stamped printable paper. */
    public function print(ExamPaper $paper)
    {
        $this->authorizeView($paper);
        $paper->load(['questions']);

        $school = DB::table('school_information')->where('is_active', 1)->first()
            ?? DB::table('school_information')->first();

        return view('exam.papers.print', [
            'paper'  => $paper,
            'label'  => $this->labelsFor([$paper->subjectclass_id])[$paper->subjectclass_id] ?? '',
            'school' => $school,
            'vetter' => $paper->vetted_by ? DB::table('users')->where('id', $paper->vetted_by)->value('name') : null,
        ]);
    }

    /** Download the vetted paper as a professionally formatted Word (.docx) file. */
    public function word(ExamPaper $paper)
    {
        $this->authorizeView($paper);
        abort_unless(in_array($paper->status, ['approved', 'locked'], true), 403,
            'Only a vetted (approved/locked) paper can be exported to Word.');

        $school = DB::table('school_information')->where('is_active', 1)->first()
            ?? DB::table('school_information')->first();

        $meta = [
            'school' => $school,
            'label'  => $this->labelsFor([$paper->subjectclass_id])[$paper->subjectclass_id] ?? '',
            'vetter' => $paper->vetted_by ? DB::table('users')->where('id', $paper->vetted_by)->value('name') : null,
        ];

        $path  = app(\App\Services\ExamWordExport::class)->build($paper, $meta);
        $fname = \Illuminate\Support\Str::slug($paper->title ?: 'exam-paper').'.docx';

        return response()->download($path, $fname)->deleteFileAfterSend(true);
    }

    public function destroy(ExamPaper $paper)
    {
        $this->authorizeOwner($paper);
        abort_unless(in_array($paper->status, ['draft', 'changes_requested'], true), 403, 'Only drafts can be deleted.');
        $ids = $paper->questions()->pluck('id');
        DB::table('exam_question_topic')->whereIn('exam_question_id', $ids)->delete();
        DB::table('exam_question_scores')->whereIn('exam_question_id', $ids)->delete();
        $paper->questions()->delete();
        $paper->comments()->delete();
        $paper->delete();

        return redirect()->route('exam.papers.index')->with('success', 'Paper deleted.');
    }

    // ── form helper ──────────────────────────────────────────────────────────
    protected function form(ExamPaper $paper)
    {
        $assignments = $this->myAssignments();

        // syllabus topics per subjectclass (for topic tagging in the builder)
        $topicsByClass = [];
        foreach ($assignments as $a) {
            $topicsByClass[$a->subjectclass_id] = SubjectTopic::where('subject_id', $a->subject_id)
                ->where('class_level', $a->class_level)
                ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $a->term_id))
                ->orderBy('week_no')->orderBy('position')->orderBy('id')
                ->get(['id', 'title', 'week_no'])
                ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'week_no' => $t->week_no])
                ->values();
        }

        // existing questions, with their tagged topic ids
        $questions = [];
        if ($paper->exists) {
            foreach ($paper->questions()->with('topics:id')->get() as $qq) {
                $questions[] = [
                    'id'         => $qq->id,
                    'section'    => $qq->section,
                    'number'     => $qq->number,
                    'type'       => $qq->type,
                    'question'   => $qq->question,
                    'options'    => $qq->options ?: (object) [],
                    'answer'     => $qq->answer,
                    'marks'      => (float) $qq->marks,
                    'difficulty' => $qq->difficulty,
                    'topics'     => $qq->topics->pluck('id')->all(),
                ];
            }
        }

        return view('exam.papers.form', [
            'pagetitle'     => $paper->exists ? 'Edit Exam Paper' : 'New Exam Paper',
            'paper'         => $paper,
            'assignments'   => $assignments,
            'topicsByClass' => $topicsByClass,
            'questions'     => $questions,
        ]);
    }

    // ── validation / question sync ─────────────────────────────────────────────
    protected function validatePaper(Request $request): array
    {
        return $request->validate([
            'subjectclass_id'  => 'required|integer',
            'title'            => 'required|string|max:200',
            'exam_type'        => 'required|in:'.implode(',', array_keys(ExamPaper::TYPES)),
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'instructions'     => 'nullable|string|max:5000',
        ]);
    }

    /** Persist the posted question set (JSON), upserting and pruning. */
    protected function syncQuestions(ExamPaper $paper, $raw): void
    {
        $items = is_string($raw) ? json_decode($raw, true) : (array) $raw;
        if (!is_array($items)) $items = [];

        $validTopicIds = SubjectTopic::where('subject_id', $paper->subject_id)
            ->where('class_level', $paper->class_level)
            ->pluck('id')->map(fn ($v) => (int) $v)->all();

        $keepIds = [];
        $pos = 0;
        foreach ($items as $it) {
            $pos++;
            $text = trim((string) ($it['question'] ?? ''));
            if ($text === '') continue;

            $type = in_array(($it['type'] ?? 'theory'), ['objective', 'theory', 'practical'], true) ? $it['type'] : 'theory';
            $options = null;
            if ($type === 'objective' && !empty($it['options']) && is_array($it['options'])) {
                $options = array_filter(
                    array_map(fn ($v) => (string) $v, $it['options']),
                    fn ($v) => trim($v) !== ''
                );
                if (empty($options)) $options = null;
            }

            $attrs = [
                'exam_paper_id' => $paper->id,
                'section'       => $this->str($it['section'] ?? null, 40),
                'number'        => $this->str($it['number'] ?? null, 20),
                'type'          => $type,
                'question'      => mb_substr($text, 0, 8000),
                'options'       => $options,
                'answer'        => $this->str($it['answer'] ?? null, 8000),
                'marks'         => max(0, (float) ($it['marks'] ?? 1)),
                'difficulty'    => in_array(($it['difficulty'] ?? null), ['easy', 'medium', 'hard'], true) ? $it['difficulty'] : null,
                'position'      => $pos,
            ];

            $id = (int) ($it['id'] ?? 0);
            if ($id && ($q = ExamQuestion::where('exam_paper_id', $paper->id)->find($id))) {
                $q->update($attrs);
            } else {
                $q = ExamQuestion::create($attrs);
            }
            $keepIds[] = $q->id;

            // topic tags, intersected with valid topics for this subject+level
            $tags = array_values(array_intersect(
                array_map('intval', (array) ($it['topics'] ?? [])),
                $validTopicIds
            ));
            $q->topics()->sync($tags);
        }

        // prune removed questions (and their tags / scores)
        $removed = ExamQuestion::where('exam_paper_id', $paper->id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->pluck('id');
        if ($removed->isNotEmpty()) {
            DB::table('exam_question_topic')->whereIn('exam_question_id', $removed)->delete();
            DB::table('exam_question_scores')->whereIn('exam_question_id', $removed)->delete();
            ExamQuestion::whereIn('id', $removed)->delete();
        }
    }

    protected function str($v, int $max): ?string
    {
        $v = is_scalar($v) ? trim((string) $v) : '';
        return $v === '' ? null : mb_substr($v, 0, $max);
    }

    protected function afterSave(Request $request, ExamPaper $paper, string $msg)
    {
        if ($request->input('action') === 'submit') {
            return $this->submit($paper);
        }
        return redirect()->route('exam.papers.edit', $paper)->with('success', $msg);
    }

    // ── access + resolution helpers (mirrors Curriculum controllers) ───────────
    protected function myAssignments()
    {
        $uid = Auth::id();
        $currentSession = DB::table('schoolsession')->where('status', 'Current')->value('id');

        return DB::table('subjectteacher as st')
            ->join('subject as s', 's.id', '=', 'st.subjectid')
            ->join('subjectclass as sjc', 'sjc.subjectteacherid', '=', 'st.id')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->where('st.staffid', $uid)
            ->when($currentSession, fn ($q) => $q->where('st.sessionid', $currentSession))
            ->selectRaw("sjc.id as subjectclass_id, sjc.subjectid as subject_id, sjc.termid as term_id,
                sjc.sessionid as session_id, c.schoolclass as class_level,
                TRIM(CONCAT(s.subject,' — ',c.schoolclass,' ',COALESCE(arm.arm,''))) as label")
            ->orderBy('label')->get();
    }

    protected function ownedSubjectclass(int $id)
    {
        $sc = DB::table('subjectclass as sjc')
            ->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->where('sjc.id', $id)
            ->selectRaw("sjc.id, sjc.subjectid as subject_id, sjc.termid as term_id,
                sjc.sessionid as session_id, st.staffid as staff_id, c.schoolclass as class_level")
            ->first();
        abort_unless($sc, 404);
        abort_unless((int) $sc->staff_id === (int) Auth::id() || Auth::user()->can('Vet exam papers'),
            403, 'Not your class.');
        return $sc;
    }

    protected function authorizeOwner(ExamPaper $paper): void
    {
        abort_unless((int) $paper->teacher_id === (int) Auth::id() || Auth::user()->can('Vet exam papers'),
            403, 'Not your paper.');
    }

    protected function authorizeView(ExamPaper $paper): void
    {
        abort_unless(
            (int) $paper->teacher_id === (int) Auth::id()
            || Auth::user()->can('Vet exam papers'),
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
