<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsCourse;
use App\Models\LmsLesson;
use App\Models\LmsQuestionBank;
use App\Models\LmsQuiz;
use App\Models\LmsQuizAttempt;
use App\Models\LmsQuizQuestion;
use App\Services\Lms\ProgressService;
use App\Services\Messaging\PortalNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher-side lesson quiz builder and results overview. Student take/submit
 * lives in LearnController (enrolment-gated, not permission-gated).
 */
class QuizController extends Controller
{
    use InteractsWithLms;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $data = $this->quizRules($request);
        $data['course_id'] = $course->id;
        $quiz = LmsQuiz::create($data);
        return redirect()->route('lms.quizzes.edit', [$course, $quiz])->with('success', 'Quiz created. Add questions below.');
    }

    public function edit(LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id, 404);
        $quiz->load('questions');
        $subjects = \Illuminate\Support\Facades\Schema::hasTable('subject')
            ? DB::table('subject')->orderBy('subject')->get(['id', 'subject']) : collect();
        return view('lms.quizzes.edit', [
            'pagetitle' => 'Quiz — ' . $quiz->title,
            'course'    => $course,
            'quiz'      => $quiz,
            'subjects'  => $subjects,
        ]);
    }

    public function update(Request $request, LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id, 404);
        $quiz->update($this->quizRules($request));
        return back()->with('success', 'Quiz updated.');
    }

    public function destroy(LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id, 404);
        DB::table('lms_quiz_attempts')->where('quiz_id', $quiz->id)->delete();
        $quiz->questions()->delete();
        $quiz->delete();
        return redirect()->route('lms.courses.show', $course)->with('success', 'Quiz deleted.');
    }

    public function storeQuestion(Request $request, LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id, 404);
        $data = $this->questionRules($request);
        $data['quiz_id']  = $quiz->id;
        $data['position'] = (int) $quiz->questions()->max('position') + 1;
        LmsQuizQuestion::create($data);
        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, LmsCourse $course, LmsQuiz $quiz, LmsQuizQuestion $question)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id && $question->quiz_id === $quiz->id, 404);
        $question->update($this->questionRules($request));
        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(LmsCourse $course, LmsQuiz $quiz, LmsQuizQuestion $question)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id && $question->quiz_id === $quiz->id, 404);
        $question->delete();
        return back()->with('success', 'Question removed.');
    }

    /** Attempts overview for a quiz (best attempt per student). */
    public function results(LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeGrade($course);
        abort_unless($quiz->course_id === $course->id, 404);

        $rows = DB::table('lms_quiz_attempts as a')
            ->leftJoin('studentRegistration as s', 's.id', '=', 'a.student_id')
            ->where('a.quiz_id', $quiz->id)
            ->orderByDesc('a.percent')->orderBy('a.attempt_no')
            ->select('a.*', 's.firstname', 's.lastname', 's.admissionNo')
            ->paginate(40);

        return view('lms.quizzes.results', [
            'pagetitle' => 'Quiz results — ' . $quiz->title,
            'course'    => $course,
            'quiz'      => $quiz,
            'rows'      => $rows,
        ]);
    }

    /** Attempts awaiting manual grading (essay / short-answer overrides). */
    public function review(LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeGrade($course);
        abort_unless($quiz->course_id === $course->id, 404);

        $quiz->load('questions');
        $attempts = LmsQuizAttempt::where('quiz_id', $quiz->id)
            ->where('needs_review', true)->orderBy('submitted_at')->get();

        $students = DB::table('studentRegistration')
            ->whereIn('id', $attempts->pluck('student_id')->all())
            ->get(['id', 'firstname', 'lastname', 'admissionNo'])->keyBy('id');

        return view('lms.quizzes.review', [
            'pagetitle' => 'Grade — ' . $quiz->title,
            'course'    => $course,
            'quiz'      => $quiz,
            'attempts'  => $attempts,
            'students'  => $students,
        ]);
    }

    /** Save teacher-awarded marks for an attempt and finalise its score. */
    public function gradeAttempt(Request $request, LmsCourse $course, LmsQuiz $quiz, LmsQuizAttempt $attempt)
    {
        $this->authorizeGrade($course);
        abort_unless($quiz->course_id === $course->id && $attempt->quiz_id === $quiz->id, 404);

        $questions = $quiz->questions()->get()->keyBy('id');
        $marks = is_array($attempt->marks) ? $attempt->marks : [];
        foreach ((array) $request->input('marks', []) as $qid => $pts) {
            if (!isset($questions[$qid])) continue;
            $max = (float) $questions[$qid]->points;
            $marks[$qid] = max(0, min($max, (float) $pts));
        }

        $score = array_sum(array_map('floatval', $marks));
        $maxScore = (float) ($attempt->max_score ?: $questions->sum('points'));
        $percent = $maxScore > 0 ? (int) round($score / $maxScore * 100) : 0;
        $passed = $percent >= (int) $quiz->pass_mark;

        $attempt->update([
            'marks'        => $marks,
            'score'        => $score,
            'percent'      => $percent,
            'passed'       => $passed,
            'needs_review' => false,
            'graded_by'    => $this->me()->id,
            'graded_at'    => now(),
        ]);

        // notify student + auto-complete the linked lesson on a pass
        try {
            $userId = DB::table('users')->where('student_id', $attempt->student_id)->value('id');
            if ($userId) {
                PortalNotifier::toUsers([(int) $userId], 'Quiz graded',
                    "Your attempt at \"{$quiz->title}\" was graded: {$percent}%.",
                    route('lms.learn.show', $course), 'result');
            }
        } catch (\Throwable $e) {}

        if ($passed && $quiz->lesson_id) {
            $lesson = LmsLesson::find($quiz->lesson_id);
            if ($lesson && $lesson->course_id === $course->id) {
                app(ProgressService::class)->markLesson($course, $lesson, (int) $attempt->student_id, true);
            }
        }

        return back()->with('success', 'Attempt graded.');
    }

    /** Import questions from the shared bank into this quiz (copies rows). */
    public function importBank(Request $request, LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id, 404);

        $pos = (int) $quiz->questions()->max('position');
        $imported = 0;

        if ($request->input('mode') === 'random') {
            $count = max(1, min(100, (int) $request->input('count', 5)));
            $pool = LmsQuestionBank::query()
                ->when($request->filled('subject'), fn ($q) => $q->where('subject_id', $request->subject))
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
                ->when($request->filled('tag'), fn ($q) => $q->where('tag', 'like', "%{$request->tag}%"))
                ->inRandomOrder()->limit($count)->get();
        } else {
            $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
            $pool = $ids ? LmsQuestionBank::whereIn('id', $ids)->get() : collect();
        }

        foreach ($pool as $bq) {
            LmsQuizQuestion::create($bq->toQuizFields() + ['quiz_id' => $quiz->id, 'position' => ++$pos]);
            $imported++;
        }

        return back()->with($imported ? 'success' : 'error',
            $imported ? "Imported {$imported} question(s) from the bank." : 'No questions imported.');
    }

    /** Copy an existing quiz question into the shared bank. */
    public function saveToBank(Request $request, LmsCourse $course, LmsQuiz $quiz, LmsQuizQuestion $question)
    {
        $this->authorizeManage($course);
        abort_unless($quiz->course_id === $course->id && $question->quiz_id === $quiz->id, 404);

        LmsQuestionBank::create([
            'subject_id'       => $course->subject_id,
            'question'         => $question->question,
            'type'             => $question->type,
            'options'          => $question->options,
            'correct'          => $question->correct,
            'accepted_answers' => $question->accepted_answers,
            'explanation'      => $question->explanation,
            'image_path'       => $question->image_path,
            'points'           => $question->points,
            'created_by'       => $this->me()->id,
        ]);

        return back()->with('success', 'Question saved to the bank.');
    }

    /** Per-question difficulty report for a quiz. */
    public function itemAnalysis(LmsCourse $course, LmsQuiz $quiz)
    {
        $this->authorizeGrade($course);
        abort_unless($quiz->course_id === $course->id, 404);

        $questions = $quiz->questions()->get();
        $attempts = LmsQuizAttempt::where('quiz_id', $quiz->id)->get(['answers', 'marks']);

        $stats = [];
        foreach ($questions as $qn) {
            $answered = 0; $correct = 0; $optionTally = [];
            foreach ($attempts as $at) {
                $marks = $at->marks ?? [];
                $answers = $at->answers ?? [];
                if (!array_key_exists($qn->id, $answers)) continue;
                $answered++;
                $awarded = (float) ($marks[$qn->id] ?? 0);
                if ($qn->points > 0 && $awarded >= (float) $qn->points) $correct++;
                if ($qn->isChoice()) {
                    foreach ((array) $answers[$qn->id] as $oi) {
                        $oi = (int) $oi;
                        $optionTally[$oi] = ($optionTally[$oi] ?? 0) + 1;
                    }
                }
            }
            $stats[] = [
                'question' => $qn,
                'answered' => $answered,
                'correct'  => $correct,
                'pct'      => $answered > 0 ? (int) round($correct / $answered * 100) : null,
                'tally'    => $optionTally,
            ];
        }

        return view('lms.quizzes.analysis', [
            'pagetitle' => 'Item analysis — ' . $quiz->title,
            'course'    => $course,
            'quiz'      => $quiz,
            'stats'     => $stats,
            'attempts'  => $attempts->count(),
        ]);
    }

    // ── validation ────────────────────────────────────────────────────────
    protected function quizRules(Request $request): array
    {
        $v = $request->validate([
            'title'              => 'required|string|max:200',
            'description'        => 'nullable|string',
            'lesson_id'          => 'nullable|integer',
            'pass_mark'          => 'required|integer|min:0|max:100',
            'max_attempts'       => 'required|integer|min:0|max:100',
            'time_limit_minutes' => 'nullable|integer|min:0|max:100000',
            'available_from'     => 'nullable|date',
            'available_until'    => 'nullable|date|after_or_equal:available_from',
            'shuffle'            => 'nullable|boolean',
            'allow_partial'      => 'nullable|boolean',
            'is_published'       => 'nullable|boolean',
        ]);
        $v['shuffle']       = $request->boolean('shuffle');
        $v['allow_partial'] = $request->boolean('allow_partial');
        $v['is_published']  = $request->boolean('is_published', true);
        return $v;
    }

    protected function questionRules(Request $request): array
    {
        $data = $request->validate([
            'question'          => 'required|string',
            'type'              => 'required|in:single,multiple,boolean,short_answer,fill_blank,essay',
            'points'            => 'required|integer|min:1|max:100',
            'options'           => 'nullable|array',
            'options.*'         => 'nullable|string|max:500',
            'correct'           => 'nullable|array',
            'correct.*'         => 'integer|min:0',
            'accepted'          => 'nullable|array',
            'accepted.*'        => 'nullable|string|max:500',
            'explanation'       => 'nullable|string|max:5000',
            'image'             => 'nullable|image|max:4096',
            'remove_image'      => 'nullable|boolean',
        ]);

        $type = $data['type'];
        $out = [
            'question'    => $data['question'],
            'type'        => $type,
            'points'      => $data['points'],
            'explanation' => $data['explanation'] ?? null,
            'options'     => null,
            'correct'     => null,
            'accepted_answers' => null,
        ];

        if (in_array($type, ['single', 'multiple', 'boolean'], true)) {
            if ($type === 'boolean') {
                $out['options'] = ['True', 'False'];
            } else {
                $out['options'] = array_values(array_filter(
                    array_map(fn ($o) => trim((string) $o), $data['options'] ?? []),
                    fn ($o) => $o !== ''
                ));
            }
            $max = count($out['options']);
            if ($max < 2) abort(422, 'Add at least two options.');
            $correct = array_values(array_filter(
                array_unique(array_map('intval', $data['correct'] ?? [])),
                fn ($i) => $i >= 0 && $i < $max
            ));
            if ($type !== 'multiple') $correct = array_slice($correct, 0, 1);
            if (empty($correct)) abort(422, 'Select at least one valid correct answer.');
            $out['correct'] = $correct;
        } elseif (in_array($type, ['short_answer', 'fill_blank'], true)) {
            $accepted = array_values(array_filter(
                array_map(fn ($a) => trim((string) $a), $data['accepted'] ?? []),
                fn ($a) => $a !== ''
            ));
            if (empty($accepted)) abort(422, 'Add at least one accepted answer.');
            $out['accepted_answers'] = $accepted;
        }
        // essay: no options/correct/accepted — graded manually.

        // image handling
        if ($request->hasFile('image')) {
            $out['image_path'] = $request->file('image')->store('lms/questions', 'public');
        } elseif ($request->boolean('remove_image')) {
            $out['image_path'] = null;
        }

        return $out;
    }
}
