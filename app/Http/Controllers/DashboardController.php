<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\Schoolclass;
use App\Models\Subject;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\Studentclass;
use App\Models\Exam;
use App\Models\AttendanceSummary;
use App\Models\AttendanceTermSetting;
use App\Models\SchoolBill;
use App\Models\SchoolPayment;
use App\Models\Broadsheets;
use App\Models\Subjectclass;
use App\Models\ClassTeacher;
use App\Models\StudentAttendance;
use App\Models\BroadsheetRankingSetting;
use App\Services\BestStudentsService;
use App\Services\ClassResultsLoader;
use App\Services\BestStudentsExplorer;
use App\Models\SchoolInformation;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            if ($u && $u->hasRole('Parent') && !$u->can('dashboard')) {
                return redirect()->route('parent.dashboard');
            }
            return $next($request);
        })->only(['index']);
        $this->middleware('permission:dashboard', ['only' => ['index', 'bestStudentsPrint']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "School Analytics Dashboard";

        // ============================================================
        // TERM / SESSION SELECTOR
        // ============================================================
        $allTerms    = Schoolterm::orderBy('id')->get();
        $allSessions = Schoolsession::orderByDesc('id')->get();

        $currentTerm    = Schoolterm::where('status', true)->first()
                       ?? $allTerms->last();
        $currentSession = Schoolsession::where('status', 'Current')->first()
                       ?? $allSessions->first();

        $selectedTermId    = (int) $request->input('term_id',    $currentTerm?->id    ?? 0);
        $selectedSessionId = (int) $request->input('session_id', $currentSession?->id ?? 0);

        $selectedTerm    = Schoolterm::find($selectedTermId)    ?? $currentTerm;
        $selectedSession = Schoolsession::find($selectedSessionId) ?? $currentSession;

        // ============================================================
        // POPULATION STATS (session-wide, not term-filtered)
        // ============================================================
        $currentMonth   = Carbon::now()->startOfMonth();
        $previousMonth  = Carbon::now()->subMonth()->startOfMonth();

        $total_population = Student::count();
        $previous_population = Student::where('created_at', '<', $currentMonth)
            ->where('created_at', '>=', $previousMonth)->count();
        $population_percentage = $previous_population > 0
            ? number_format((($total_population - $previous_population) / $previous_population) * 100, 2)
            : ($total_population > 0 ? 100.00 : 0.00);

        $gender_counts = Student::groupBy('gender')
            ->selectRaw('gender, COUNT(*) as gender_count')
            ->pluck('gender_count', 'gender')
            ->toArray();
        $gender_counts = [
            'Male'   => $gender_counts['Male']   ?? 0,
            'Female' => $gender_counts['Female'] ?? 0,
        ];

        $status_counts = Student::groupBy('statusId')
            ->selectRaw("CASE WHEN statusId=1 THEN 'Old Student' WHEN statusId=2 THEN 'New Student' ELSE 'Other' END as student_status, COUNT(*) as student_count")
            ->pluck('student_count', 'student_status')->toArray();
        $status_counts = [
            'Old Student' => $status_counts['Old Student'] ?? 0,
            'New Student' => $status_counts['New Student'] ?? 0,
        ];

        // ============================================================
        // STUDENTS ENROLLED IN SELECTED SESSION/TERM
        // ============================================================
        $students_in_selected_term = 0;
        $students_by_class         = [];

        if ($selectedSession && $selectedTerm) {
            $students_in_selected_term = Studentclass::where('sessionid', $selectedSession->id)
                ->where('termid', $selectedTerm->id)
                ->distinct('studentId')->count('studentId');

            $students_by_class = Studentclass::with(['schoolclass', 'schoolclass.armRelation'])
                ->where('sessionid', $selectedSession->id)
                ->where('termid', $selectedTerm->id)
                ->select('schoolclassid', DB::raw('count(*) as total'))
                ->groupBy('schoolclassid')
                ->get()
                ->map(fn($item) => [
                    'class_name' => $item->schoolclass?->schoolclass ?? 'N/A',
                    'arm'        => $item->schoolclass?->armRelation?->arm ?? '',
                    'total'      => $item->total,
                ])
                ->sortByDesc('total')
                ->take(10)
                ->values()
                ->toArray();
        }

        // ============================================================
        // STAFF STATS
        // ============================================================
        $staff_count = User::whereHas('roles', fn($q) =>
            $q->whereIn('name', ['staff', 'teacher', 'admin'])
        )->count();

        $staff_by_role = User::with('roles')
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['staff', 'teacher', 'admin']))
            ->get()
            ->groupBy(fn($u) => $u->roles->first()->name ?? 'Unknown')
            ->map(fn($g) => $g->count())
            ->toArray();

        // ============================================================
        // CLASS / SUBJECT STATS
        // ============================================================
        $total_classes  = Schoolclass::count();
        $total_subjects = Subject::count();

        $classes_with_arms = Schoolclass::with(['armRelation'])
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_label'])
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'class_name' => $c->schoolclass,
                'arm'        => $c->arm_label ?? '',
            ])
            ->toArray();

        $class_capacity_data = Schoolclass::withCount([
                'studentCurrentTerms as student_count' => fn($q) =>
                    $selectedSession ? $q->where('sessionId', $selectedSession->id) : $q
            ])
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_label'])
            ->get()
            ->map(function ($class) {
                $capacity    = $class->capacity ?? 40;
                $utilization = $capacity > 0
                    ? round(($class->student_count / $capacity) * 100, 1) : 0;
                return [
                    'class_name'  => $class->schoolclass,
                    'arm'         => $class->arm_label ?? '',
                    'students'    => $class->student_count,
                    'capacity'    => $capacity,
                    'utilization' => $utilization,
                ];
            })
            ->sortByDesc('students')
            ->values()
            ->toArray();

        // ============================================================
        // ACADEMIC PERFORMANCE (selected term + session)
        // ============================================================
        $academic_performance  = [];
        $top_students          = [];
        $subject_performance   = [];
        $grade_distribution    = [];
        $best_students         = $this->emptyBestStudents();

        if (class_exists(Broadsheets::class) && $selectedTerm && $selectedSession) {

            $academic_performance = Broadsheets::where('broadsheets.term_id', $selectedTerm->id)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->where('broadsheet_records.session_id', $selectedSession->id)
                ->join('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(
                    'broadsheet_records.schoolclass_id',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                    DB::raw('AVG(broadsheets.total) as avg_score'),
                    DB::raw('MAX(broadsheets.total) as max_score'),
                    DB::raw('MIN(broadsheets.total) as min_score'),
                    DB::raw('COUNT(DISTINCT broadsheet_records.student_id) as student_count')
                )
                ->groupBy('broadsheet_records.schoolclass_id', 'schoolclass.schoolclass', 'schoolarm.arm')
                ->get()
                ->map(fn($item) => [
                    'class_name'    => $item->class_name ?? 'N/A',
                    'arm'           => $item->arm_name ?? '',
                    'avg_score'     => round($item->avg_score, 1),
                    'max_score'     => round($item->max_score, 1),
                    'min_score'     => round($item->min_score, 1),
                    'student_count' => $item->student_count,
                    'label'         => trim(($item->class_name ?? '') . ' ' . ($item->arm_name ?? '')),
                ])
                ->sortBy('class_name')
                ->values()
                ->toArray();

            // Best students — same maths and rules as the broadsheet
            $best_students = $this->getBestStudents($selectedTerm, $selectedSession);
            $top_students  = $best_students['overall'];

            $subject_performance = Broadsheets::where('broadsheets.term_id', $selectedTerm->id)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->where('broadsheet_records.session_id', $selectedSession->id)
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->select(
                    'broadsheet_records.subject_id',
                    'subject.subject as subject_name',
                    DB::raw('AVG(broadsheets.total) as avg_score'),
                    DB::raw('MAX(broadsheets.total) as max_score'),
                    DB::raw('MIN(broadsheets.total) as min_score'),
                    DB::raw('COUNT(*) as total_entries'),
                    DB::raw('SUM(CASE WHEN broadsheets.total >= 40 THEN 1 ELSE 0 END) as passed')
                )
                ->groupBy('broadsheet_records.subject_id', 'subject.subject')
                ->get()
                ->map(function ($item) use ($selectedSession, $selectedTerm) {
                    $teachers = DB::table('subjectteacher as st')
                        ->join('subjectclass as sc', 'sc.subjectteacherid', '=', 'st.id')
                        ->join('users', 'users.id', '=', 'st.staffid')
                        ->where('st.subjectid', $item->subject_id)
                        ->select('users.id', 'users.name', 'users.avatar')
                        ->distinct()
                        ->limit(3)
                        ->get()
                        ->map(fn($teacher) => [
                            'id'     => $teacher->id,
                            'name'   => $teacher->name,
                            'avatar' => $teacher->avatar,
                        ])
                        ->toArray();

                    $passRate = $item->total_entries > 0
                        ? round(($item->passed / $item->total_entries) * 100, 1) : 0;

                    return [
                        'subject_id'   => $item->subject_id,
                        'subject_name' => $item->subject_name,
                        'avg_score'    => round($item->avg_score, 1),
                        'max_score'    => round($item->max_score, 1),
                        'min_score'    => round($item->min_score, 1),
                        'pass_rate'    => $passRate,
                        'total_entries'=> $item->total_entries,
                        'teachers'     => $teachers,
                    ];
                })
                ->sortByDesc('avg_score')
                ->values()
                ->toArray();

            $grade_distribution = $this->getGradeDistribution($selectedTerm, $selectedSession);
        }

        // ============================================================
        // BEST PERFORMING TEACHERS
        // ============================================================
        $top_teachers = $this->getTopTeachers($selectedTerm, $selectedSession);

        // ============================================================
        // ATTENDANCE STATS — per class, arm
        // ============================================================
        $overall_attendance_rate = 0;
        $attendance_by_class     = [];
        $attendance_trend        = [];

        if (class_exists(AttendanceSummary::class) && $selectedTerm && $selectedSession) {
            $att = AttendanceSummary::where('term_id', $selectedTerm->id)
                ->where('session_id', $selectedSession->id)
                ->selectRaw('SUM(days_present) as total_present, SUM(total_school_days) as total_days')
                ->first();

            if ($att && $att->total_days > 0) {
                $overall_attendance_rate = round(($att->total_present / $att->total_days) * 100, 1);
            }

            $attendance_by_class = AttendanceSummary::where('attendance_summaries.term_id', $selectedTerm->id)
                ->where('attendance_summaries.session_id', $selectedSession->id)
                ->join('schoolclass', 'schoolclass.id', '=', 'attendance_summaries.schoolclass_id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(
                    'attendance_summaries.schoolclass_id',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                    DB::raw('SUM(attendance_summaries.days_present) as total_present'),
                    DB::raw('SUM(attendance_summaries.total_school_days) as total_days'),
                    DB::raw('COUNT(DISTINCT attendance_summaries.student_id) as student_count'),
                    DB::raw('SUM(attendance_summaries.days_absent) as total_absent'),
                    DB::raw('SUM(attendance_summaries.days_late) as total_late')
                )
                ->groupBy(
                    'attendance_summaries.schoolclass_id',
                    'schoolclass.schoolclass',
                    'schoolarm.arm'
                )
                ->get()
                ->map(fn($row) => [
                    'class_name'    => $row->class_name ?? 'N/A',
                    'arm'           => $row->arm_name ?? '',
                    'label'         => trim(($row->class_name ?? '') . ' ' . ($row->arm_name ?? '')),
                    'student_count' => $row->student_count,
                    'rate'          => $row->total_days > 0
                        ? round(($row->total_present / $row->total_days) * 100, 1) : 0,
                    'total_present' => $row->total_present,
                    'total_absent'  => $row->total_absent,
                    'total_late'    => $row->total_late ?? 0,
                    'total_days'    => $row->total_days,
                ])
                ->sortByDesc('rate')
                ->values()
                ->toArray();

            if (class_exists(StudentAttendance::class)) {
                $attendance_trend = StudentAttendance::where('term_id', $selectedTerm->id)
                    ->where('session_id', $selectedSession->id)
                    ->select(
                        'attendance_date',
                        DB::raw('SUM(CASE WHEN status="present" OR status="late" THEN 1 ELSE 0 END) as present_count'),
                        DB::raw('SUM(CASE WHEN status="absent" THEN 1 ELSE 0 END) as absent_count'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('attendance_date')
                    ->orderBy('attendance_date')
                    ->limit(30)
                    ->get()
                    ->map(fn($r) => [
                        'date'          => Carbon::parse($r->attendance_date)->format('d M'),
                        'rate'          => $r->total > 0
                            ? round(($r->present_count / $r->total) * 100, 1) : 0,
                        'present_count' => $r->present_count,
                        'absent_count'  => $r->absent_count,
                    ])
                    ->toArray();
            }
        }

        // ============================================================
        // PAYMENT / FINANCE
        // ============================================================
        $total_payments     = 0;
        $payment_percentage = 0;
        $pending_payments   = 0;
        $completed_payments = 0;
        $total_bills        = 0;
        $collection_rate    = 0;

        if (class_exists(SchoolPayment::class)) {
            $total_payments     = SchoolPayment::sum('amount_paid') ?? 0;
            $pending_payments   = SchoolPayment::where('status', 'pending')->count();
            $completed_payments = SchoolPayment::where('status', 'completed')->count();
        }
        if (class_exists(SchoolBill::class)) {
            $total_bills = SchoolBill::sum('amount') ?? 0;
        }
        $collection_rate = $total_bills > 0
            ? round(($total_payments / $total_bills) * 100, 1) : 0;

        // ============================================================
        // EXAM STATS
        // ============================================================
        $total_exams     = 0;
        $upcoming_exams  = 0;
        $completed_exams = 0;
        if (class_exists(Exam::class)) {
            $total_exams     = Exam::count();
            $upcoming_exams  = Exam::where('start_time', '>', Carbon::now())->count();
            $completed_exams = Exam::where('end_time', '<', Carbon::now())->count();
        }

        // ============================================================
        // ALL-TERM COMPARISON
        // ============================================================
        $all_terms_performance = [];
        if (class_exists(Broadsheets::class) && $selectedSession) {
            foreach ($allTerms as $term) {
                $avgScore = Broadsheets::where('broadsheets.term_id', $term->id)
                    ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                    ->where('broadsheet_records.session_id', $selectedSession->id)
                    ->avg('broadsheets.total');

                $all_terms_performance[] = [
                    'term'      => $term->term,
                    'term_id'   => $term->id,
                    'avg_score' => round($avgScore ?? 0, 1),
                ];
            }
        }

        // ============================================================
        // ENROLLMENT TREND (monthly, last 12 months)
        // ============================================================
        $yearly_trends = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $yearly_trends[] = [
                'month'    => $month->format('M Y'),
                'students' => Student::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)->count(),
            ];
        }

        // ============================================================
        // RECENT ACTIVITIES
        // ============================================================
        $recent_activities = $this->getRecentActivities();

        // ============================================================
        // CLASS TEACHER MAP
        // ============================================================
        $class_teachers = [];
        if (class_exists(ClassTeacher::class) && $selectedTerm && $selectedSession) {
            $class_teachers = ClassTeacher::where('termid', $selectedTerm->id)
                ->where('sessionid', $selectedSession->id)
                ->join('schoolclass', 'schoolclass.id', '=', 'classteacher.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->join('users', 'users.id', '=', 'classteacher.staffid')
                ->select(
                    'classteacher.schoolclassid',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                    'users.id as teacher_id',
                    'users.name as teacher_name',
                    'users.avatar as teacher_avatar'
                )
                ->get()
                ->map(fn($r) => [
                    'class_id'       => $r->schoolclassid,
                    'class_name'     => $r->class_name,
                    'arm'            => $r->arm_name ?? '',
                    'teacher_id'     => $r->teacher_id,
                    'teacher_name'   => $r->teacher_name,
                    'teacher_avatar' => $r->teacher_avatar,
                ])
                ->toArray();
        }

        // ============================================================
        // BEST STUDENTS EXPLORER (admin-selected classes/arms + criteria)
        // ============================================================
        $best_explorer = $this->bestStudentsExplorer($request, $selectedTerm, $selectedSession);

        return view('dashboards.dashboard', compact(
            'pagetitle', 'best_explorer',
            'allTerms', 'allSessions', 'selectedTerm', 'selectedSession',
            'currentTerm', 'currentSession',
            'total_population', 'population_percentage',
            'gender_counts', 'status_counts',
            'students_in_selected_term', 'students_by_class',
            'staff_count', 'staff_by_role',
            'total_classes', 'total_subjects',
            'classes_with_arms', 'class_capacity_data',
            'academic_performance', 'top_students', 'best_students',
            'subject_performance', 'grade_distribution',
            'all_terms_performance',
            'top_teachers',
            'overall_attendance_rate', 'attendance_by_class', 'attendance_trend',
            'total_payments', 'payment_percentage',
            'pending_payments', 'completed_payments',
            'total_bills', 'collection_rate',
            'total_exams', 'upcoming_exams', 'completed_exams',
            'recent_activities', 'yearly_trends',
            'class_teachers'
        ));
    }

    // =========================================================================
    // BEST STUDENTS — whole school, each class (all arms), each arm
    //
    // Uses ClassResultsLoader (same BF / Cum / Cum Ave maths as the broadsheet)
    // and BestStudentsService (same ranking + eligibility rules as the
    // broadsheet's toppers panel), driven by the saved junior / senior
    // Broadsheet Ranking settings. Best-effort: failures are logged and the
    // dashboard still renders.
    // =========================================================================

    private function emptyBestStudents(): array
    {
        return [
            'overall'       => [],
            'by_class'      => [],
            'by_arm'        => [],
            'measure_label' => 'Cumulative average',
            'ranked'        => 0,
            'students'      => 0,
            'notice'        => null,   // why nothing could be loaded (shown on the dashboard)
            'excluded_summary' => [],  // [reason => count] when nobody met the ranking rules
        ];
    }

    /** Group exclusion reasons so the dashboard can say WHY a list is empty. */
    private function summariseExclusions(array $excluded): array
    {
        $out = [];
        foreach ($excluded as $reason) {
            $key = preg_match('/^Only \d+ subject\(s\) scored \(minimum (\d+)\)/', $reason, $m)
                ? "Fewer than {$m[1]} subjects scored (Ranking settings → minimum subjects)"
                : (preg_match('/^Average .* is below (.+)$/', $reason, $m2)
                    ? "Average below {$m2[1]} (Ranking settings → minimum average)"
                    : $reason);
            $out[$key] = ($out[$key] ?? 0) + 1;
        }
        arsort($out);
        return $out;
    }

    private function getBestStudents(Schoolterm $term, Schoolsession $session): array
    {
        $result = $this->emptyBestStudents();

        try {
            $data = app(ClassResultsLoader::class)->load((int) $term->id, (int) $session->id);
            $rows = $data['rows'];
            if (empty($rows)) {
                $result['notice'] = 'No student results were found for this term and session.';
                return $result;
            }

            $service  = app(BestStudentsService::class);
            $settings = [
                'junior' => $this->rankingSettings('junior'),
                'senior' => $this->rankingSettings('senior'),
            ];

            // ── Per class (all arms together) + per arm inside it ──
            $groups = [];
            foreach ($rows as $row) {
                $groups[$row['class_name']][] = $row;
            }
            uksort($groups, 'strnatcasecmp');

            foreach ($groups as $className => $groupRows) {
                $section    = !empty($groupRows[0]['is_senior']) ? 'senior' : 'junior';
                $s          = $settings[$section];
                $classIds   = array_values(array_unique(array_column($groupRows, 'schoolclassid')));
                $compulsory = $s->require_all_compulsory
                    ? $this->compulsorySubjectIds($classIds, (int) $session->id)
                    : [];

                $r = $service->rank($groupRows, $this->optionsFrom($s, $compulsory), $data['subjects']);

                $lookup = [];
                foreach ($groupRows as $gr) $lookup[(int) $gr['id']] = $gr;

                $result['by_class'][$className] = [
                    'arms'     => count($classIds),
                    'students' => count($groupRows),
                    'ranked'   => $r['eligible_count'],
                    'top'      => $this->decorate($r['overall'], $lookup),
                ];

                foreach ($r['by_arm'] as $armLabel => $entries) {
                    $result['by_arm'][$armLabel] = [
                        'class_name' => $className,
                        'top'        => $this->decorate($entries, $lookup),
                    ];
                }
            }
            uksort($result['by_arm'], 'strnatcasecmp');

            // ── Whole school ──
            // A student enrolled in two arms in the same session keeps the
            // record with more scored subjects, so they appear once.
            $unique = [];
            foreach ($rows as $row) {
                $sid = (int) $row['id'];
                if (!isset($unique[$sid]) || $row['num_subjects'] > $unique[$sid]['num_subjects']) {
                    $unique[$sid] = $row;
                }
            }

            $schoolOpts = $this->optionsFrom($settings['junior'], []);
            $schoolOpts['measure']      = $settings['junior']->primary_measure === $settings['senior']->primary_measure
                ? $settings['junior']->primary_measure : 'cum_ave';
            $schoolOpts['min_subjects'] = min((int) ($settings['junior']->min_subjects ?? 0), (int) ($settings['senior']->min_subjects ?? 0));
            $schoolOpts['min_average']  = null;
            $schoolOpts['exclude_failed'] = false;
            $schoolOpts['top_n']        = 10;

            $overall = $service->rank(array_values($unique), $schoolOpts, $data['subjects']);

            $result['overall']       = $this->decorate($overall['overall'], $unique);
            $result['measure_label'] = $overall['measure_label'];
            $result['ranked']        = $overall['eligible_count'];
            $result['students']      = count($unique);
            if (empty($result['overall'])) {
                $result['excluded_summary'] = $this->summariseExclusions($overall['excluded']);
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard best students failed: ' . $e->getMessage());
            $result['notice'] = 'Best students could not be loaded: ' . $e->getMessage();
        }

        return $result;
    }

    private function rankingSettings(string $section)
    {
        return BroadsheetRankingSetting::where('section', $section)->first()
            ?? BroadsheetRankingSetting::defaultFor($section);
    }

    private function optionsFrom($s, array $compulsory): array
    {
        return [
            'measure'          => $s->primary_measure,
            'tiebreakers'      => (array) ($s->tiebreakers ?? []),
            'top_n'            => (int) ($s->top_n ?? 3),
            'subject_top_n'    => (int) ($s->subject_top_n ?? 3),
            'min_subjects'     => (int) ($s->min_subjects ?? 0),
            'min_average'      => $s->min_average,
            'exclude_failed'   => (bool) $s->exclude_failed,
            'compulsory'       => $compulsory,
            'core_subject_ids' => (array) ($s->core_subject_ids ?? []),
            'basis'            => 'cum_ave',
        ];
    }

    private function compulsorySubjectIds(array $classIds, int $sessionId): array
    {
        if (empty($classIds) || !Schema::hasTable('compulsory_subject_classes')) return [];

        return DB::table('compulsory_subject_classes')
            ->whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionId)
            ->pluck('subjectId')->map(fn ($v) => (int) $v)->unique()->values()->all();
    }

    /** Ranked entries → the shape the dashboard view uses. */
    private function decorate(array $entries, array $rowsById): array
    {
        return array_map(function ($e) use ($rowsById) {
            $row = $rowsById[$e['id']] ?? [];
            $avg = (float) ($row['cum_ave'] ?? 0);
            return [
                'student_id'    => $e['id'],
                'rank'          => $e['rank'],
                'name'          => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
                'admission_no'  => $e['admissionno'],
                'class'         => $row['class_name'] ?? '',
                'arm'           => $row['arm'] ?? '',
                'class_label'   => $row['class_label'] ?? '',
                'picture'       => $row['picture'] ?? null,
                'value'         => $e['value'],
                'average'       => $avg,
                'total_cum'     => (float) ($row['total_cum'] ?? 0),
                'subject_count' => (int) ($row['num_subjects'] ?? 0),
                'grade'         => $this->calculateGrade($avg),
            ];
        }, $entries);
    }

    // =========================================================================
    // BEST STUDENTS EXPLORER — admin picks classes/arms + criteria; shows best
    // overall, per class and per arm, and the best in EVERY subject at class
    // and arm level. Same figures as the broadsheet (ClassResultsLoader).
    // Query params: bx_groups[] (class names = all arms), bx_ids[] (arms),
    // bx_basis, bx_measure, bx_top, bx_subject_top, bx_min, bx_fail.
    // =========================================================================

    private function bestStudentsExplorer(Request $request, ?Schoolterm $term, ?Schoolsession $session): array
    {
        $explorer = app(BestStudentsExplorer::class);

        $groups = array_values(array_filter((array) $request->input('bx_groups', [])));
        $ids    = array_values(array_filter(array_map('intval', (array) $request->input('bx_ids', []))));
        $opts   = $explorer->normaliseOptions([
            'basis'          => $request->input('bx_basis'),
            'measure'        => $request->input('bx_measure'),
            'top_n'          => $request->input('bx_top', 5),
            'subject_top_n'  => $request->input('bx_subject_top', 3),
            'min_subjects'   => $request->input('bx_min', 0),
            'exclude_failed' => $request->input('bx_fail', false),
        ]);

        $result = [
            'classes'  => Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
                ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
                ->get()->groupBy('schoolclass'),
            'bases'    => BestStudentsExplorer::BASES,
            'measures' => BestStudentsExplorer::MEASURES,
            'opts'     => $opts,
            'groups'   => $groups,
            'ids'      => $ids,
            'report'   => null,
            'error'    => null,
        ];

        if (!$request->boolean('bx') || (!$groups && !$ids)) {
            if ($request->boolean('bx')) $result['error'] = 'Select at least one class or arm.';
            return $result;
        }
        if (!$term || !$session) {
            $result['error'] = 'Select a term and session first.';
            return $result;
        }

        try {
            $classIds = $explorer->resolveClassIds($groups, $ids);
            $result['report'] = $explorer->build((int) $term->id, (int) $session->id, $classIds, $opts);
        } catch (\Throwable $e) {
            Log::warning('Best students explorer failed: ' . $e->getMessage());
            $result['error'] = 'Could not build the report: ' . $e->getMessage();
        }

        return $result;
    }

    /** Printable, comprehensive Best Students report (same params as the dashboard explorer). */
    public function bestStudentsPrint(Request $request)
    {
        $term = Schoolterm::find((int) $request->input('term_id'))
            ?? Schoolterm::where('status', true)->first();
        $session = Schoolsession::find((int) $request->input('session_id'))
            ?? Schoolsession::where('status', 'Current')->first();

        $request->merge(['bx' => 1]);
        $bx = $this->bestStudentsExplorer($request, $term, $session);

        if (!$bx['report']) {
            return redirect()->back()->with('error', $bx['error'] ?? 'Select at least one class or arm.');
        }

        $school = SchoolInformation::getActiveSchool();

        return view('dashboards.best-students-print', [
            'bx'          => $bx,
            'report'      => $bx['report'],
            'term'        => $term,
            'session'     => $session,
            'school'      => $school,
            'logo'        => $this->schoolLogoBase64($school),
            'generatedAt' => now()->format('d M Y, g:i A'),
            'generatedBy' => optional($request->user())->name,
        ]);
    }

    private function schoolLogoBase64($school): ?string
    {
        if (!$school || empty($school->school_logo)) return null;

        foreach ([
            storage_path('app/public/' . $school->school_logo),
            public_path('storage/' . $school->school_logo),
            public_path($school->school_logo),
        ] as $path) {
            if (is_file($path) && filesize($path) > 100) {
                return 'data:' . (mime_content_type($path) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
        return null;
    }

    // =========================================================================
    // TOP TEACHERS — ranked by average student score in their subjects
    // =========================================================================
    private function getTopTeachers(?Schoolterm $term, ?Schoolsession $session): array
    {
        if (!$term || !$session || !class_exists(Broadsheets::class)) {
            return [];
        }

        $rows = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheets.term_id', $term->id)
            ->where('broadsheet_records.session_id', $session->id)
            ->join('subjectteacher', 'subjectteacher.subjectid', '=', 'broadsheet_records.subject_id')
            ->join('users', 'users.id', '=', 'subjectteacher.staffid')
            ->select(
                'users.id as teacher_id',
                'users.name as teacher_name',
                'users.avatar as teacher_avatar',
                DB::raw('AVG(broadsheets.total) as avg_score'),
                DB::raw('COUNT(DISTINCT broadsheet_records.subject_id) as subject_count'),
                DB::raw('COUNT(DISTINCT broadsheet_records.student_id) as student_count'),
                DB::raw('SUM(CASE WHEN broadsheets.total >= 40 THEN 1 ELSE 0 END) as passed'),
                DB::raw('COUNT(*) as total_entries'),
                DB::raw('MAX(broadsheets.total) as max_score')
            )
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderByDesc('avg_score')
            ->limit(8)
            ->get();

        return $rows->map(function ($row) use ($term, $session) {
            $subjects = DB::table('subjectteacher')
                ->where('staffid', $row->teacher_id)
                ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->select('subject.subject')
                ->distinct()
                ->limit(4)
                ->pluck('subject')
                ->toArray();

            $termSubjects = DB::table('broadsheet_records')
                ->where('broadsheet_records.session_id', $session->id)
                ->join('subjectteacher', 'subjectteacher.subjectid', '=', 'broadsheet_records.subject_id')
                ->where('subjectteacher.staffid', $row->teacher_id)
                ->join('broadsheets', 'broadsheets.broadsheet_record_id', '=', 'broadsheet_records.id')
                ->where('broadsheets.term_id', $term->id)
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->select('subject.subject')
                ->distinct()
                ->limit(4)
                ->pluck('subject')
                ->toArray();

            $passRate = $row->total_entries > 0
                ? round(($row->passed / $row->total_entries) * 100, 1) : 0;

            return [
                'teacher_id'     => $row->teacher_id,
                'name'           => $row->teacher_name,
                'avatar'         => $row->teacher_avatar,
                'avg_score'      => round($row->avg_score, 1),
                'subject_count'  => $row->subject_count,
                'student_count'  => $row->student_count,
                'pass_rate'      => $passRate,
                'max_score'      => round($row->max_score, 1),
                'subjects'       => array_unique(array_merge($termSubjects, $subjects)),
            ];
        })->toArray();
    }

    // =========================================================================
    // GRADE DISTRIBUTION
    // =========================================================================
    private function getGradeDistribution(?Schoolterm $term, ?Schoolsession $session): array
    {
        if (!$term || !$session || !class_exists(Broadsheets::class)) return [];

        $grades = Broadsheets::where('broadsheets.term_id', $term->id)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.session_id', $session->id)
            ->select('broadsheets.grade', DB::raw('count(*) as count'))
            ->groupBy('broadsheets.grade')
            ->pluck('count', 'grade')
            ->toArray();

        $gradeOrder = ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'];
        $result     = [];
        foreach ($gradeOrder as $grade) {
            $result[$grade] = $grades[$grade] ?? 0;
        }
        return $result;
    }

    // =========================================================================
    // GRADE HELPER
    // =========================================================================
    private function calculateGrade(float $score): string
    {
        if ($score >= 75) return 'A1';
        if ($score >= 70) return 'B2';
        if ($score >= 65) return 'B3';
        if ($score >= 60) return 'C4';
        if ($score >= 55) return 'C5';
        if ($score >= 50) return 'C6';
        if ($score >= 45) return 'D7';
        if ($score >= 40) return 'E8';
        return 'F9';
    }

    // =========================================================================
    // RECENT ACTIVITIES
    // =========================================================================
    private function getRecentActivities(): array
    {
        $activities = [];

        $recent_students = Student::orderBy('created_at', 'desc')->take(3)->get();
        foreach ($recent_students as $student) {
            $activities[] = [
                'type'        => 'student',
                'title'       => 'New Student Enrolled',
                'description' => $student->firstname . ' ' . $student->lastname . ' joined the school',
                'time'        => $student->created_at->diffForHumans(),
                'icon'        => 'ph-user-plus',
                'color'       => 'primary',
                'raw_time'    => $student->created_at->timestamp,
            ];
        }

        $recent_staff = User::whereHas('roles', fn($q) =>
            $q->whereIn('name', ['staff', 'teacher'])
        )->orderBy('created_at', 'desc')->take(2)->get();

        foreach ($recent_staff as $staff) {
            $activities[] = [
                'type'        => 'staff',
                'title'       => 'New Staff Member',
                'description' => $staff->name . ' joined as ' . ($staff->roles->first()->name ?? 'staff'),
                'time'        => $staff->created_at->diffForHumans(),
                'icon'        => 'ph-chalkboard-teacher',
                'color'       => 'success',
                'raw_time'    => $staff->created_at->timestamp,
            ];
        }

        usort($activities, fn($a, $b) => $b['raw_time'] - $a['raw_time']);
        return array_slice($activities, 0, 6);
    }
}
