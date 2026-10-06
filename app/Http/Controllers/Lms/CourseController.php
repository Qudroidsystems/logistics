<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\CertificateTemplate;
use App\Models\LmsAssignment;
use App\Models\LmsCourse;
use App\Models\LmsLesson;
use App\Models\LmsQuiz;
use App\Models\LmsQuizQuestion;
use App\Models\LmsSection;
use App\Models\User;
use App\Services\Lms\CalendarSync;
use App\Services\Lms\EnrollmentService;
use App\Services\Lms\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    use InteractsWithLms;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    public function index(Request $request)
    {
        $u = Auth::user();
        $mine = !$u->can('Manage courses'); // graders see their own courses only

        $q = LmsCourse::query()
            ->withCount(['lessons', 'enrollments'])
            ->when($mine, fn ($x) => $x->where('teacher_id', $u->id))
            ->when($request->filled('q'), fn ($x) => $x->where('title', 'like', "%{$request->q}%"))
            ->when($request->filled('class'), fn ($x) => $x->where('schoolclass_id', $request->class))
            ->when($request->status === 'published', fn ($x) => $x->where('is_published', true))
            ->when($request->status === 'draft', fn ($x) => $x->where('is_published', false))
            ->orderByDesc('id');

        return view('lms.courses.index', [
            'pagetitle' => 'Courses',
            'rows'      => $q->paginate(20)->withQueryString(),
            'classes'   => $this->classList(),
            'stats'     => [
                'total'     => LmsCourse::when($mine, fn ($x) => $x->where('teacher_id', $u->id))->count(),
                'published' => LmsCourse::when($mine, fn ($x) => $x->where('teacher_id', $u->id))->where('is_published', true)->count(),
                'learners'  => (int) DB::table('lms_enrollments')->distinct('student_id')->count('student_id'),
            ],
        ]);
    }

    public function create()
    {
        return view('lms.courses.form', $this->formData(new LmsCourse(['enrollment_mode' => 'both'])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = Auth::id();
        if (!Auth::user()->can('Manage courses')) {
            $data['teacher_id'] = Auth::id(); // graders own their courses
        }
        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('lms/covers', 'public');
        }
        $course = LmsCourse::create($data);

        return redirect()->route('lms.courses.show', $course)->with('success', 'Course created. Add sections and lessons below.');
    }

    /** Course management dashboard (curriculum, learners, coursework, engagement). */
    public function show(LmsCourse $course)
    {
        $this->authorizeGrade($course);

        $course->load(['sections.lessons', 'lessons' => fn ($q) => $q->orderBy('position')]);

        return view('lms.courses.show', [
            'pagetitle'   => $course->title,
            'course'      => $course,
            'assignments' => $course->assignments()->withCount('submissions')->latest()->get(),
            'quizzes'     => $course->quizzes()->withCount('questions')->latest()->get(),
            'liveClasses' => $course->liveClasses()->orderByDesc('scheduled_at')->get(),
            'announcements' => $course->announcements()->limit(10)->get(),
            'enrollCount' => $course->enrollments()->count(),
            'completedCount' => $course->enrollments()->where('status', 'completed')->count(),
            'exams'       => $this->examOptions($course),
            'certTemplates' => $this->certTemplates(),
        ]);
    }

    public function edit(LmsCourse $course)
    {
        $this->authorizeManage($course);
        return view('lms.courses.form', $this->formData($course));
    }

    public function update(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $data = $this->validated($request);
        if (!Auth::user()->can('Manage courses')) {
            unset($data['teacher_id']); // graders can't reassign ownership
        }
        if ($request->hasFile('cover')) {
            if ($course->cover_path) Storage::disk('public')->delete($course->cover_path);
            $data['cover_path'] = $request->file('cover')->store('lms/covers', 'public');
        }
        $course->update($data);

        return redirect()->route('lms.courses.show', $course)->with('success', 'Course updated.');
    }

    public function destroy(LmsCourse $course)
    {
        $this->authorizeManage($course);
        DB::transaction(function () use ($course) {
            $ids = ['course_id' => $course->id];
            DB::table('lms_lesson_progress')->where($ids)->delete();
            DB::table('lms_assignment_submissions')->whereIn('assignment_id', $course->assignments()->pluck('id'))->delete();
            DB::table('lms_quiz_attempts')->whereIn('quiz_id', $course->quizzes()->pluck('id'))->delete();
            DB::table('lms_quiz_questions')->whereIn('quiz_id', $course->quizzes()->pluck('id'))->delete();
            $course->assignments()->delete();
            $course->quizzes()->delete();
            $course->discussions()->delete();
            $course->liveClasses()->delete();
            $course->announcements()->delete();
            $course->lessons()->delete();
            $course->sections()->delete();
            $course->enrollments()->delete();
            if ($course->cover_path) Storage::disk('public')->delete($course->cover_path);
            $course->delete();
        });

        return redirect()->route('lms.courses.index')->with('success', 'Course deleted.');
    }

    public function togglePublish(LmsCourse $course)
    {
        $this->authorizeManage($course);
        $course->update(['is_published' => !$course->is_published]);
        return back()->with('success', $course->is_published ? 'Course published.' : 'Course unpublished.');
    }

    /** Mirror this course's assignment due-dates & live classes into the school calendar. */
    public function syncCalendar(LmsCourse $course)
    {
        $this->authorizeManage($course);
        if (!CalendarSync::available()) return back()->with('error', 'The school calendar module is not available.');
        $n = CalendarSync::syncCourse($course);
        return back()->with('success', "Synced {$n} item(s) to the school calendar.");
    }

    /** Bulk publish/unpublish from the course list. */
    public function bulkPublish(Request $request)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
        $publish = $request->input('action') === 'publish';
        if (!$ids) return back()->with('error', 'No courses selected.');

        $q = LmsCourse::whereIn('id', $ids);
        if (!Auth::user()->can('Manage courses')) {
            $q->where('teacher_id', Auth::id()); // graders only their own
        }
        $n = $q->update(['is_published' => $publish]);

        return back()->with('success', "{$n} course(s) " . ($publish ? 'published.' : 'unpublished.'));
    }

    /** Deep-duplicate a course: sections, lessons, quizzes+questions, assignments. */
    public function duplicate(LmsCourse $course)
    {
        $this->authorizeManage($course);

        $new = DB::transaction(function () use ($course) {
            $copy = $course->replicate(['slug']);
            $copy->title = $course->title . ' (Copy)';
            $copy->slug = null;             // regenerated on create
            $copy->is_published = false;
            $copy->created_by = Auth::id();
            $copy->save();

            // sections (old id => new id)
            $sectionMap = [];
            foreach ($course->sections()->get() as $s) {
                $ns = $s->replicate();
                $ns->course_id = $copy->id;
                $ns->save();
                $sectionMap[$s->id] = $ns->id;
            }

            // lessons (old id => new id)
            $lessonMap = [];
            foreach ($course->lessons()->get() as $l) {
                $nl = $l->replicate();
                $nl->course_id = $copy->id;
                $nl->section_id = $l->section_id ? ($sectionMap[$l->section_id] ?? null) : null;
                $nl->save();
                $lessonMap[$l->id] = $nl->id;
            }

            // assignments
            foreach ($course->assignments()->get() as $a) {
                $na = $a->replicate();
                $na->course_id = $copy->id;
                $na->lesson_id = $a->lesson_id ? ($lessonMap[$a->lesson_id] ?? null) : null;
                $na->created_by = Auth::id();
                $na->save();
            }

            // quizzes + questions
            foreach ($course->quizzes()->with('questions')->get() as $qz) {
                $nq = $qz->replicate();
                $nq->course_id = $copy->id;
                $nq->lesson_id = $qz->lesson_id ? ($lessonMap[$qz->lesson_id] ?? null) : null;
                $nq->save();
                foreach ($qz->questions as $qn) {
                    $nqn = $qn->replicate();
                    $nqn->quiz_id = $nq->id;
                    $nqn->save();
                }
            }

            return $copy;
        });

        return redirect()->route('lms.courses.show', $new)->with('success', 'Course duplicated. This copy is a draft — review and publish when ready.');
    }

    // ── helpers ───────────────────────────────────────────────────────────
    protected function validated(Request $request): array
    {
        $v = $request->validate([
            'title'                       => 'required|string|max:200',
            'code'                        => 'nullable|string|max:60',
            'description'                 => 'nullable|string',
            'subject_id'                  => 'nullable|integer',
            'schoolclass_id'              => 'nullable|integer',
            'session_id'                  => 'nullable|integer',
            'term_id'                     => 'nullable|integer',
            'teacher_id'                  => 'nullable|integer',
            'enrollment_mode'             => 'required|in:auto,manual,both',
            'allow_self_enroll'           => 'nullable|boolean',
            'completion_cert_template_id' => 'nullable|integer',
            'is_published'                => 'nullable|boolean',
            'cover'                       => 'nullable|image|max:4096',
            'quiz_weight'                 => 'nullable|integer|min:0|max:100',
            'assignment_weight'           => 'nullable|integer|min:0|max:100',
            'fees_gate'                   => 'nullable|boolean',
        ]);
        $v['allow_self_enroll'] = $request->boolean('allow_self_enroll');
        $v['is_published']      = $request->boolean('is_published');
        // grade weighting + fee gate live in settings (JSON)
        $v['settings'] = [
            'quiz_weight'       => (int) $request->input('quiz_weight', 50),
            'assignment_weight' => (int) $request->input('assignment_weight', 50),
            'fees_gate'         => $request->boolean('fees_gate'),
        ];
        unset($v['cover'], $v['quiz_weight'], $v['assignment_weight'], $v['fees_gate']);
        return $v;
    }

    protected function formData(LmsCourse $course): array
    {
        return [
            'pagetitle'     => $course->exists ? 'Edit Course' : 'New Course',
            'course'        => $course,
            'subjects'      => Schema::hasTable('subject') ? DB::table('subject')->orderBy('subject')->get(['id', 'subject']) : collect(),
            'classes'       => $this->classList(),
            'sessions'      => Schema::hasTable('schoolsession') ? DB::table('schoolsession')->orderByDesc('id')->get(['id', 'session', 'status']) : collect(),
            'terms'         => Schema::hasTable('schoolterm') ? DB::table('schoolterm')->get(['id', 'term', 'status']) : collect(),
            'teachers'      => $this->teacherList(),
            'certTemplates' => $this->certTemplates(),
        ];
    }

    protected function classList()
    {
        if (!Schema::hasTable('schoolclass')) return collect();
        return DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, TRIM(CONCAT(schoolclass.schoolclass,' ',COALESCE(schoolarm.arm,''))) as name")
            ->orderBy('schoolclass.schoolclass')->get();
    }

    protected function teacherList()
    {
        try {
            return User::role('Staff')->orderBy('name')->get(['id', 'name']);
        } catch (\Throwable $e) {
            return User::has('staff')->orderBy('name')->get(['id', 'name']);
        }
    }

    protected function certTemplates()
    {
        if (!Schema::hasTable('certificate_templates')) return collect();
        return CertificateTemplate::orderBy('name')->get(['id', 'name']);
    }

    /** Existing CBT exams that a lesson can point to. */
    protected function examOptions(LmsCourse $course)
    {
        if (!Schema::hasTable('exams')) return collect();
        return DB::table('exams')
            ->when($course->schoolclass_id, fn ($q) => $q->where('schoolclass_id', $course->schoolclass_id))
            ->orderByDesc('id')->limit(200)->get(['id', 'title']);
    }
}
