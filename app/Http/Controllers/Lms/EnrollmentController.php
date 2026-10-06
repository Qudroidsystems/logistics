<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsCourse;
use App\Services\Lms\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manage a course's learners: auto-sync a class, manually add/remove students.
 */
class EnrollmentController extends Controller
{
    use InteractsWithLms;

    public function __construct(protected EnrollmentService $enroller)
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    public function index(Request $request, LmsCourse $course)
    {
        $this->authorizeGrade($course);

        $rows = DB::table('lms_enrollments as e')
            ->leftJoin('studentRegistration as s', 's.id', '=', 'e.student_id')
            ->where('e.course_id', $course->id)
            ->when($request->filled('q'), fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('s.firstname', 'like', "%{$request->q}%")
                  ->orWhere('s.lastname', 'like', "%{$request->q}%")
                  ->orWhere('s.admissionNo', 'like', "%{$request->q}%");
            }))
            ->orderBy('s.lastname')->orderBy('s.firstname')
            ->select('e.*', 's.firstname', 's.lastname', 's.admissionNo')
            ->paginate(30)->withQueryString();

        return view('lms.enrollments.index', [
            'pagetitle' => 'Learners — ' . $course->title,
            'course'    => $course,
            'rows'      => $rows,
            'eligible'  => count($this->enroller->eligibleStudentIds($course)),
            'classes'   => $this->classList(),
        ]);
    }

    public function syncAuto(LmsCourse $course)
    {
        $this->authorizeManage($course);
        $n = $this->enroller->syncAuto($course);
        return back()->with('success', $n > 0 ? "Enrolled {$n} student(s) from the class." : 'No new students to enrol (check the course has a class with students for its session).');
    }

    public function syncSubject(LmsCourse $course)
    {
        $this->authorizeManage($course);
        if (!$course->subject_id) {
            return back()->with('error', 'Set a subject on the course first to enrol by subject registration.');
        }
        $n = $this->enroller->syncBySubject($course);
        return back()->with('success', $n > 0 ? "Enrolled {$n} student(s) registered for this subject." : 'No new students registered for this subject to enrol.');
    }

    /**
     * AJAX: students available to enrol. Base is the whole student roster so an
     * admin can add any student; an optional class filter narrows it. Already-
     * enrolled students are excluded. When no class is chosen and no search is
     * given, defaults to the course's class (if any) to keep the list short.
     */
    public function candidates(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);

        if (!Schema::hasTable('studentRegistration')) {
            return response()->json(['data' => []]);
        }

        $enrolled = DB::table('lms_enrollments')->where('course_id', $course->id)
            ->pluck('student_id')->map(fn ($v) => (int) $v)->all();

        $class = $request->input('class_id'); // '' = default, 'all' = every class, or an id
        $q = trim((string) $request->input('q', ''));

        // Resolve which class to filter by (if any).
        $classId = null;
        if ($class === 'all') {
            $classId = null;                       // no class filter — whole roster
        } elseif (is_numeric($class)) {
            $classId = (int) $class;               // explicit class
        } elseif ($q === '' && $course->schoolclass_id) {
            $classId = (int) $course->schoolclass_id; // default view: the course's class
        }

        $query = DB::table('studentRegistration as s')
            ->when($classId, function ($x) use ($classId) {
                $x->whereIn('s.id', DB::table('studentclass')
                    ->where('schoolclassid', $classId)->distinct()->pluck('studentId'));
            })
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('s.firstname', 'like', "%{$q}%")
                      ->orWhere('s.lastname', 'like', "%{$q}%")
                      ->orWhereRaw("TRIM(CONCAT(s.firstname,' ',s.lastname)) like ?", ["%{$q}%"])
                      ->orWhere('s.admissionNo', 'like', "%{$q}%");
                });
            })
            ->when($enrolled, fn ($x) => $x->whereNotIn('s.id', $enrolled))
            ->orderBy('s.lastname')->orderBy('s.firstname')
            ->selectRaw("s.id, TRIM(CONCAT(s.firstname,' ',s.lastname)) as name, s.admissionNo")
            ->limit(500)->get();

        return response()->json(['data' => $query]);
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $ids = (array) $request->input('student_ids', []);
        $n = $this->enroller->enroll($course, $ids, $this->me()->id);
        return back()->with($n > 0 ? 'success' : 'error',
            $n > 0 ? "Enrolled {$n} student(s)." : 'No students were selected (or they are already enrolled).');
    }

    public function destroy(Request $request, LmsCourse $course, int $student)
    {
        $this->authorizeManage($course);
        $this->enroller->unenroll($course, [$student]);
        return back()->with('success', 'Student removed from the course.');
    }

    /** Classes with arm labels for the picker. */
    protected function classList()
    {
        if (!Schema::hasTable('schoolclass')) return collect();
        return DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, TRIM(CONCAT(schoolclass.schoolclass,' ',COALESCE(schoolarm.arm,''))) as name")
            ->orderBy('schoolclass.schoolclass')->get();
    }
}
