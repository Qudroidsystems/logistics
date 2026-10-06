<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsAssignment;
use App\Models\LmsAssignmentSubmission;
use App\Models\LmsCourse;
use App\Models\LmsDiscussion;
use App\Models\LmsLesson;
use App\Models\LmsLessonProgress;
use App\Models\LmsQuiz;
use App\Models\LmsQuizAttempt;
use App\Services\Lms\EnrollmentService;
use App\Services\Lms\ProgressService;
use App\Services\Messaging\PortalNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Student learning experience: my courses, lesson player, lesson completion,
 * assignment submission, quiz attempts and discussions.
 */
class LearnController extends Controller
{
    use InteractsWithLms;

    public function __construct(
        protected ProgressService $progress,
        protected EnrollmentService $enroller
    ) {
        $this->middleware('auth');
    }

    /** The student's enrolled courses. */
    public function myCourses()
    {
        $sid = $this->currentStudentId();
        if (!$sid) abort(403, 'This area is for students.');

        $rows = DB::table('lms_enrollments as e')
            ->join('lms_courses as c', 'c.id', '=', 'e.course_id')
            ->where('e.student_id', $sid)->where('c.is_published', true)
            ->orderByDesc('e.updated_at')
            ->select('c.*', 'e.progress_percent', 'e.status as enrol_status')
            ->get();

        return view('lms.learn.my-courses', [
            'pagetitle' => 'My Learning',
            'rows'      => $rows,
            'catalog'   => $this->catalogQuery($sid)->limit(12)->get(),
        ]);
    }

    /** Courses the student may self-enrol into. */
    public function catalog()
    {
        $sid = $this->currentStudentId();
        if (!$sid) abort(403, 'This area is for students.');
        return view('lms.learn.catalog', [
            'pagetitle' => 'Course Catalog',
            'rows'      => $this->catalogQuery($sid)->paginate(24),
        ]);
    }

    public function selfEnroll(LmsCourse $course)
    {
        $sid = $this->currentStudentId();
        if (!$sid) abort(403);
        if (!$course->is_published || !$course->allow_self_enroll) {
            return back()->with('error', 'This course is not open for self-enrolment.');
        }
        $this->enroller->selfEnroll($course, $sid);
        return redirect()->route('lms.learn.show', $course)->with('success', 'You are now enrolled.');
    }

    /** Course home for a learner. */
    public function show(LmsCourse $course)
    {
        $sid = $this->ensureEnrolledOrManage($course);
        if ($blocked = $this->feeBlock($course, $sid)) return $blocked;

        $course->load(['sections' => fn ($q) => $q->where('is_published', true)->orderBy('position')]);
        $lessons = $course->publishedLessons()->get();

        $doneIds = $sid ? LmsLessonProgress::where('course_id', $course->id)->where('student_id', $sid)
            ->where('completed', true)->pluck('lesson_id')->map(fn ($v) => (int) $v)->all() : [];

        $enrol = $sid ? $course->enrollments()->where('student_id', $sid)->first() : null;

        return view('lms.learn.show', [
            'pagetitle'     => $course->title,
            'course'        => $course,
            'lessons'       => $lessons,
            'doneIds'       => $doneIds,
            'progress'      => $enrol->progress_percent ?? 0,
            'assignments'   => $course->assignments()->where('is_published', true)->get(),
            'quizzes'       => $course->quizzes()->where('is_published', true)->get(),
            'liveClasses'   => $course->liveClasses()->orderBy('scheduled_at')->get(),
            'announcements' => $course->announcements()->limit(10)->get(),
            'studentId'     => $sid,
        ]);
    }

    /** Lesson player. */
    public function lesson(LmsCourse $course, LmsLesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        $sid = $this->currentStudentId();
        $enrolled = $sid && $course->isEnrolled($sid);

        if (!$enrolled && !$lesson->is_preview && !$this->canGrade($course)) {
            throw new HttpException(403, 'You are not enrolled in this course.');
        }
        if (!$lesson->is_published && !$this->canGrade($course)) abort(404);
        if ($enrolled && ($blocked = $this->feeBlock($course, $sid))) return $blocked;

        $lessons = $course->publishedLessons()->get();
        $idx = $lessons->search(fn ($l) => $l->id === $lesson->id);
        $prev = $idx !== false && $idx > 0 ? $lessons[$idx - 1] : null;
        $next = $idx !== false && $idx < $lessons->count() - 1 ? $lessons[$idx + 1] : null;

        $doneIds = $sid ? LmsLessonProgress::where('course_id', $course->id)->where('student_id', $sid)
            ->where('completed', true)->pluck('lesson_id')->map(fn ($v) => (int) $v)->all() : [];

        // linked CBT exam (graded test)
        $exam = null;
        if ($lesson->type === 'cbt' && $lesson->exam_id) {
            $exam = DB::table('exams')->where('id', $lesson->exam_id)->first();
        }

        $quizzes = $lesson->quizzes()->where('is_published', true)->get();

        $discussions = LmsDiscussion::with('author', 'replies.author')
            ->where('course_id', $course->id)->where('lesson_id', $lesson->id)
            ->whereNull('parent_id')->orderByDesc('is_pinned')->latest()->get();

        return view('lms.learn.lesson', [
            'pagetitle'   => $lesson->title,
            'course'      => $course,
            'lesson'      => $lesson,
            'lessons'     => $lessons,
            'prev'        => $prev,
            'next'        => $next,
            'doneIds'     => $doneIds,
            'completed'   => in_array((int) $lesson->id, $doneIds, true),
            'exam'        => $exam,
            'quizzes'     => $quizzes,
            'discussions' => $discussions,
            'studentId'   => $sid,
            'canModerate' => $this->canGrade($course),
        ]);
    }

    public function completeLesson(Request $request, LmsCourse $course, LmsLesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);
        $sid = $this->ensureEnrolled($course);
        $completed = $request->boolean('completed', true);
        $this->progress->markLesson($course, $lesson, $sid, $completed);
        return back()->with('success', $completed ? 'Lesson marked complete.' : 'Lesson marked incomplete.');
    }

    // ── Assignments ─────────────────────────────────────────────────────────
    public function submitAssignment(Request $request, LmsCourse $course, LmsAssignment $assignment)
    {
        abort_unless($assignment->course_id === $course->id, 404);
        $sid = $this->ensureEnrolled($course);
        abort_unless($assignment->is_published, 404);

        $rules = [];
        if ($assignment->allow_text) $rules['body'] = 'nullable|string|max:20000';
        if ($assignment->allow_file) $rules['file'] = 'nullable|file|max:51200'; // 50 MB
        $request->validate($rules);

        if (!$request->filled('body') && !$request->hasFile('file')) {
            return back()->with('error', 'Add your answer text or attach a file.');
        }

        $sub = LmsAssignmentSubmission::firstOrNew([
            'assignment_id' => $assignment->id, 'student_id' => $sid,
        ]);
        if ($assignment->allow_text) $sub->body = $request->input('body');
        if ($assignment->allow_file && $request->hasFile('file')) {
            if ($sub->file_path) Storage::disk('public')->delete($sub->file_path);
            $f = $request->file('file');
            $sub->file_path = $f->store('lms/submissions', 'public');
            $sub->file_name = $f->getClientOriginalName();
        }
        $sub->status = 'submitted';
        $sub->score = null;
        $sub->graded_at = null;
        $sub->submitted_at = now();
        $sub->save();

        return back()->with('success', 'Assignment submitted.');
    }

    // ── Quizzes ───────────────────────────────────────────────────────────
    public function takeQuiz(LmsCourse $course, LmsQuiz $quiz)
    {
        abort_unless($quiz->course_id === $course->id, 404);
        $sid = $this->ensureEnrolled($course);
        abort_unless($quiz->is_published, 404);

        if (!$quiz->isOpen()) {
            return redirect()->route('lms.learn.show', $course)->with('error', 'This quiz is not open right now.' . ($quiz->availabilityNote() ? ' ' . $quiz->availabilityNote() . '.' : ''));
        }

        $left = $quiz->attemptsLeft($sid);
        if ($left !== null && $left <= 0) {
            return redirect()->route('lms.learn.show', $course)->with('error', 'You have used all attempts for this quiz.');
        }

        $questions = $quiz->questions()->get();
        if ($quiz->shuffle) $questions = $questions->shuffle();

        return view('lms.learn.quiz', [
            'pagetitle'  => $quiz->title,
            'course'     => $course,
            'quiz'       => $quiz,
            'questions'  => $questions,
            'attemptsLeft' => $left,
        ]);
    }

    public function submitQuiz(Request $request, LmsCourse $course, LmsQuiz $quiz)
    {
        abort_unless($quiz->course_id === $course->id, 404);
        $sid = $this->ensureEnrolled($course);
        abort_unless($quiz->is_published, 404);

        if (!$quiz->isOpen()) {
            return redirect()->route('lms.learn.show', $course)->with('error', 'This quiz is not open right now.');
        }

        $left = $quiz->attemptsLeft($sid);
        if ($left !== null && $left <= 0) {
            return redirect()->route('lms.learn.show', $course)->with('error', 'No attempts remaining.');
        }

        $answers = (array) $request->input('answers', []); // [question_id => idx|[idx,..]|text]
        $questions = $quiz->questions()->get();
        $partial = (bool) $quiz->allow_partial;

        $score = 0.0; $max = 0.0; $stored = []; $marks = []; $needsReview = false;
        foreach ($questions as $qn) {
            $max += $qn->points;
            $ans = $answers[$qn->id] ?? null;

            if ($qn->isChoice()) {
                $sel = is_array($ans) ? $ans : ($ans === null || $ans === '' ? [] : [$ans]);
                $sel = array_values(array_filter(array_map('intval', $sel), fn ($v) => $v >= 0));
                $stored[$qn->id] = $sel;
                $awarded = $qn->award($sel, $partial);
            } else {
                // text (short_answer / fill_blank / essay)
                $text = is_array($ans) ? implode(' ', $ans) : (string) $ans;
                $stored[$qn->id] = $text;
                $awarded = $qn->award($text, $partial);
                if ($qn->isManual()) $needsReview = true; // essay graded later
            }
            $marks[$qn->id] = $awarded;
            $score += $awarded;
        }

        $percent = $max > 0 ? (int) round($score / $max * 100) : 0;
        // Only decide pass/fail once no manual grading is pending.
        $passed = !$needsReview && $percent >= (int) $quiz->pass_mark;

        $attempt = LmsQuizAttempt::create([
            'quiz_id'      => $quiz->id,
            'student_id'   => $sid,
            'answers'      => $stored,
            'marks'        => $marks,
            'score'        => $score,
            'max_score'    => $max,
            'percent'      => $percent,
            'passed'       => $passed,
            'needs_review' => $needsReview,
            'attempt_no'   => $quiz->attemptsUsed($sid) + 1,
            'started_at'   => $request->input('started_at') ? now()->parse($request->input('started_at')) : now(),
            'submitted_at' => now(),
        ]);

        // auto-complete the lesson only for a clean pass (no pending manual grade)
        if ($passed && $quiz->lesson_id) {
            $lesson = LmsLesson::find($quiz->lesson_id);
            if ($lesson && $lesson->course_id === $course->id) {
                $this->progress->markLesson($course, $lesson, $sid, true);
            }
        }

        $msg = $needsReview
            ? 'Submitted. Some answers need to be graded by your teacher — your final score will appear once reviewed.'
            : "You scored {$percent}%.";

        return redirect()->route('lms.learn.quiz.result', [$course, $quiz, $attempt])->with('success', $msg);
    }

    public function quizResult(LmsCourse $course, LmsQuiz $quiz, LmsQuizAttempt $attempt)
    {
        abort_unless($quiz->course_id === $course->id && $attempt->quiz_id === $quiz->id, 404);
        $sid = $this->currentStudentId();
        abort_unless(($sid && $attempt->student_id === $sid) || $this->canGrade($course), 403);

        return view('lms.learn.quiz-result', [
            'pagetitle' => 'Quiz result — ' . $quiz->title,
            'course'    => $course,
            'quiz'      => $quiz,
            'attempt'   => $attempt,
            'questions' => $quiz->questions()->get(),
        ]);
    }

    // ── Discussions ─────────────────────────────────────────────────────────
    public function postDiscussion(Request $request, LmsCourse $course)
    {
        $this->ensureEnrolledOrManage($course);
        $data = $request->validate([
            'body'      => 'required|string|max:5000',
            'lesson_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
        ]);

        // validate lesson / parent belong to this course
        if (!empty($data['lesson_id']) && !$course->lessons()->where('id', $data['lesson_id'])->exists()) {
            $data['lesson_id'] = null;
        }
        $parent = null;
        if (!empty($data['parent_id'])) {
            $parent = LmsDiscussion::where('course_id', $course->id)->find($data['parent_id']);
            if (!$parent) $data['parent_id'] = null;
        }

        $post = LmsDiscussion::create([
            'course_id' => $course->id,
            'lesson_id' => $data['lesson_id'] ?? ($parent->lesson_id ?? null),
            'user_id'   => Auth::id(),
            'parent_id' => $data['parent_id'] ?? null,
            'body'      => $data['body'],
        ]);

        // notify the parent author on a reply
        if ($parent && $parent->user_id !== Auth::id()) {
            try {
                PortalNotifier::toUsers([(int) $parent->user_id], 'New reply in ' . $course->title,
                    mb_substr(strip_tags($post->body), 0, 200), route('lms.learn.show', $course), 'notice');
            } catch (\Throwable $e) {}
        }

        return back()->with('success', 'Posted.');
    }

    public function destroyDiscussion(LmsCourse $course, LmsDiscussion $discussion)
    {
        abort_unless($discussion->course_id === $course->id, 404);
        // author can delete own; teachers moderate via EngagementController
        abort_unless($discussion->user_id === Auth::id() || $this->canGrade($course), 403);
        LmsDiscussion::where('parent_id', $discussion->id)->delete();
        $discussion->delete();
        return back()->with('success', 'Removed.');
    }

    // ── helpers ───────────────────────────────────────────────────────────
    protected function catalogQuery(int $studentId)
    {
        $enrolled = DB::table('lms_enrollments')->where('student_id', $studentId)->pluck('course_id')->all();
        return LmsCourse::where('is_published', true)->where('allow_self_enroll', true)
            ->when($enrolled, fn ($q) => $q->whereNotIn('id', $enrolled))
            ->orderByDesc('id');
    }

    /**
     * When the course's fee gate is on and the student owes fees, return a
     * redirect that blocks access; otherwise null. Managers (sid null) pass.
     */
    protected function feeBlock(LmsCourse $course, ?int $sid)
    {
        if (!$sid || !$course->feesGateOn()) return null;
        if (EnrollmentService::owesFees($sid, $course->session_id, $course->term_id)) {
            return redirect()->route('lms.learn.index')
                ->with('error', 'Access to “' . $course->title . '” is on hold until your school fees are cleared. Please contact the bursary.');
        }
        return null;
    }

    /** Enrolled student id, or null when a course manager is previewing. */
    protected function ensureEnrolledOrManage(LmsCourse $course): ?int
    {
        $sid = $this->currentStudentId();
        if ($sid && $course->isEnrolled($sid)) return $sid;
        if ($this->canGrade($course)) return null;
        throw new HttpException(403, 'You are not enrolled in this course.');
    }
}
