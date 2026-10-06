<?php

namespace App\Http\Controllers;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceSummary;
use App\Models\AttendanceTermSetting;
use App\Models\ClassTeacher;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\StudentAttendance;
use App\Models\Studentclass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View attendance-register',   ['only' => ['myClasses', 'register']]);
        $this->middleware('permission:Create attendance-register', ['only' => ['save', 'saveSingle', 'markAllPresent']]);
        $this->middleware('permission:View attendance-class-summary',  ['only' => ['classSummary']]);
        $this->middleware('permission:View attendance-student-report', ['only' => ['studentReport']]);
    }

    /** An attendance admin (can manage settings) is never restricted on dates. */
    protected function isAttendanceAdmin(): bool
    {
        $u = Auth::user();
        return $u && ($u->can('Create attendance-settings') || $u->hasRole(['admin', 'super-admin']));
    }

    /** Returns an error message if the current user may not mark this date, else null. */
    protected function backdateError(int $termId, int $sessionId, string $date): ?string
    {
        try { $d = Carbon::parse($date)->startOfDay(); } catch (\Throwable $e) { return 'Invalid date.'; }
        $today = today();
        if ($d->gt($today)) return 'You cannot mark attendance for a future date.';
        if ($this->isAttendanceAdmin()) return null;
        $setting = AttendanceTermSetting::where('term_id', $termId)->where('session_id', $sessionId)->first();
        $allow = $setting ? (bool) ($setting->allow_backdating ?? true) : true;
        $days  = (int) ($setting->backdate_days ?? 7);
        if (!$allow) {
            return $d->isSameDay($today) ? null : "Backdating is turned off — you can only mark today's attendance. Ask an admin to open older dates.";
        }
        $min = $today->copy()->subDays(max(0, $days));
        if ($d->lt($min)) return "You can only mark attendance from the last {$days} day(s). Ask an admin to open older dates.";
        return null;
    }

    // =========================================================================
    // MY CLASSES  –  class teacher landing page
    // =========================================================================

    public function myClasses(Request $request)
    {
        $user = Auth::user();

        // Base query: every class assigned to this teacher, across ALL sessions/terms.
        $base = fn () => ClassTeacher::where('classteacher.staffid', $user->id)
            ->join('schoolclass',   'schoolclass.id',   '=', 'classteacher.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id',     '=', 'schoolclass.arm')
            ->join('schoolterm',    'schoolterm.id',    '=', 'classteacher.termid')
            ->join('schoolsession', 'schoolsession.id', '=', 'classteacher.sessionid');

        // Sessions & terms the teacher actually has assignments in (for the pickers).
        $mySessions = $base()->select('schoolsession.id', 'schoolsession.session', 'schoolsession.status')
            ->distinct()->orderByDesc('schoolsession.id')->get();
        $myTerms = $base()->select('schoolterm.id', 'schoolterm.term')
            ->distinct()->orderBy('schoolterm.id')->get();

        // Default to the current session unless the teacher chose one (or "all").
        $currentSessionId = (int) (DB::table('schoolsession')->where('status', 'Current')->value('id') ?? 0);
        $sessionParam = $request->query('session', (string) $currentSessionId); // '' = all
        $termParam    = $request->query('term', '');                             // '' = all
        $selectedSession = $sessionParam === 'all' ? null : (int) $sessionParam;
        $selectedTerm    = ($termParam === '' || $termParam === 'all') ? null : (int) $termParam;

        $classes = $base()
            ->when($selectedSession, fn ($q) => $q->where('classteacher.sessionid', $selectedSession))
            ->when($selectedTerm, fn ($q) => $q->where('classteacher.termid', $selectedTerm))
            ->select([
                'classteacher.id as id',
                'classteacher.schoolclassid',
                'classteacher.termid',
                'classteacher.sessionid',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolterm.term',
                'schoolsession.session',
                'schoolsession.status as session_status',
            ])
            ->orderByDesc('schoolsession.id')->orderBy('schoolterm.id')
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get();

        $pagetitle = 'My Classes – Attendance';
        return view('attendance.teacher.my-classes', compact(
            'classes', 'pagetitle', 'mySessions', 'myTerms', 'selectedSession', 'selectedTerm', 'currentSessionId'
        ));
    }

    // =========================================================================
    // REGISTER PAGE  –  mark attendance for a class on a specific date
    // =========================================================================

public function register(Request $request, int $classId, int $termId, int $sessionId)
{
    $user = Auth::user();

    // Authorise – must be class teacher or admin
    if (!$user->hasRole(['admin', 'super-admin'])) {
        ClassTeacher::where('staffid', $user->id)
            ->where('schoolclassid', $classId)
            ->where('termid', $termId)
            ->where('sessionid', $sessionId)
            ->firstOrFail();
    }

    // Resolve date
    $date = $request->input('date', today()->toDateString());
    try {
        $date = Carbon::parse($date)->toDateString();
    } catch (\Exception $e) {
        $date = today()->toDateString();
    }

    $period = $request->input('period', 'morning');

    // Term setting
    $setting = AttendanceTermSetting::where('term_id', $termId)
        ->where('session_id', $sessionId)
        ->first();

    if (!$setting) {
        return redirect()->back()->with('error', 'Attendance has not been configured for this term. Please ask admin to set it up.');
    }

    // Validate period choice
    if ($period === 'afternoon' && !$setting->track_afternoon) {
        $period = 'morning';
    }

    // Check if date is a holiday
    $isHoliday = $this->isHolidayDate($date, $termId, $sessionId);

    // Fetch students
    $students = Studentclass::where('studentclass.schoolclassid', $classId)
        ->where('studentclass.sessionid', $sessionId)
        ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
        ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
        ->orderBy('studentRegistration.lastname')
        ->orderBy('studentRegistration.firstname')
        ->get([
            'studentRegistration.id',
            'studentRegistration.admissionNo as admissionno',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'studentRegistration.gender',
            'studentpicture.picture',
        ]);

    // Fetch existing attendance for this date+period
    $existing = StudentAttendance::where('schoolclass_id', $classId)
        ->where('term_id', $termId)
        ->where('session_id', $sessionId)
        ->where('attendance_date', $date)
        ->where('period', $period)
        ->pluck('status', 'student_id');

    // Calendar – generate school days for date picker
    $calendarDays = $this->buildCalendarDays($setting, $termId, $sessionId);

    // Summaries keyed by student_id
    $summaries = AttendanceSummary::where('schoolclass_id', $classId)
        ->where('term_id', $termId)
        ->where('session_id', $sessionId)
        ->get()
        ->keyBy('student_id');

    $schoolclass = Schoolclass::with('arms')->find($classId);
    $term        = Schoolterm::find($termId);
    $session     = Schoolsession::find($sessionId);

    // ── NEW: school-hours labels for the blade ─────────────────
    $resumptionLabel = $setting->resumption_time
        ? Carbon::parse($setting->resumption_time)->format('g:i A')
        : '8:00 AM';
    $closingLabel = $setting->closing_time
        ? Carbon::parse($setting->closing_time)->format('g:i A')
        : '2:00 PM';
    $morningEndLabel = $setting->morning_end_time
        ? Carbon::parse($setting->morning_end_time)->format('g:i A')
        : '12:00 PM';

    $backdateNote = $this->backdateError($termId, $sessionId, $date);
    $dateEditable = $backdateNote === null;
    $isAttendanceAdmin = $this->isAttendanceAdmin();

    $pagetitle = "Attendance – {$schoolclass->schoolclass} {$schoolclass->arms?->arm}";

    return view('attendance.teacher.register', compact(
        'students', 'existing', 'setting', 'date', 'period',
        'classId', 'termId', 'sessionId', 'isHoliday',
        'calendarDays', 'summaries', 'schoolclass', 'term', 'session',
        'pagetitle', 'resumptionLabel', 'closingLabel', 'morningEndLabel',
        'dateEditable', 'backdateNote', 'isAttendanceAdmin'
    ));
}
    // =========================================================================
    // SAVE ATTENDANCE  –  handles both full-class save and single-student toggle
    // =========================================================================

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schoolclass_id'       => 'required|exists:schoolclass,id',
            'term_id'              => 'required|exists:schoolterm,id',
            'session_id'           => 'required|exists:schoolsession,id',
            'attendance_date'      => 'required|date',
            'period'               => 'required|in:morning,afternoon',
            'records'              => 'required|array',
            'records.*.student_id' => 'required|exists:studentRegistration,id',
            'records.*.status'     => 'required|in:present,absent,sick_leave,excused,late',
            'records.*.notes'      => 'nullable|string|max:300',
        ]);

        $classId   = $validated['schoolclass_id'];
        $termId    = $validated['term_id'];
        $sessionId = $validated['session_id'];
        $date      = $validated['attendance_date'];
        $period    = $validated['period'];

        if ($err = $this->backdateError($termId, $sessionId, $date)) {
            return response()->json(['success' => false, 'message' => $err], 422);
        }

        try {
            DB::transaction(function () use ($validated, $classId, $termId, $sessionId, $date, $period) {
                foreach ($validated['records'] as $rec) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id'      => $rec['student_id'],
                            'schoolclass_id'  => $classId,
                            'term_id'         => $termId,
                            'session_id'      => $sessionId,
                            'attendance_date' => $date,
                            'period'          => $period,
                        ],
                        [
                            'status'    => $rec['status'],
                            'notes'     => $rec['notes'] ?? null,
                            'marked_by' => Auth::id(),
                        ]
                    );

                    AttendanceSettingController::rebuildSummary($rec['student_id'], $classId, $termId, $sessionId);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Attendance saved for ' . count($validated['records']) . ' student(s).',
            ]);
        } catch (\Exception $e) {
            Log::error('Attendance save error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // QUICK SINGLE STUDENT UPDATE (icon toggle)
    // =========================================================================

    public function saveSingle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id'      => 'required|exists:studentRegistration,id',
            'schoolclass_id'  => 'required|exists:schoolclass,id',
            'term_id'         => 'required|exists:schoolterm,id',
            'session_id'      => 'required|exists:schoolsession,id',
            'attendance_date' => 'required|date',
            'period'          => 'required|in:morning,afternoon',
            'status'          => 'required|in:present,absent,sick_leave,excused,late',
            'notes'           => 'nullable|string|max:300',
        ]);

        if ($err = $this->backdateError((int) $validated['term_id'], (int) $validated['session_id'], $validated['attendance_date'])) {
            return response()->json(['success' => false, 'message' => $err], 422);
        }

        try {
            StudentAttendance::updateOrCreate(
                [
                    'student_id'      => $validated['student_id'],
                    'schoolclass_id'  => $validated['schoolclass_id'],
                    'term_id'         => $validated['term_id'],
                    'session_id'      => $validated['session_id'],
                    'attendance_date' => $validated['attendance_date'],
                    'period'          => $validated['period'],
                ],
                [
                    'status'    => $validated['status'],
                    'notes'     => $validated['notes'] ?? null,
                    'marked_by' => Auth::id(),
                ]
            );

            AttendanceSettingController::rebuildSummary(
                $validated['student_id'],
                $validated['schoolclass_id'],
                $validated['term_id'],
                $validated['session_id']
            );

            $summary = AttendanceSummary::where([
                'student_id'     => $validated['student_id'],
                'schoolclass_id' => $validated['schoolclass_id'],
                'term_id'        => $validated['term_id'],
                'session_id'     => $validated['session_id'],
            ])->first();

            return response()->json([
                'success' => true,
                'message' => 'Updated.',
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('saveSingle error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MARK ALL PRESENT  (quick action)
    // =========================================================================

    public function markAllPresent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schoolclass_id'  => 'required|exists:schoolclass,id',
            'term_id'         => 'required|exists:schoolterm,id',
            'session_id'      => 'required|exists:schoolsession,id',
            'attendance_date' => 'required|date',
            'period'          => 'required|in:morning,afternoon',
            'student_ids'     => 'required|array',
            'student_ids.*'   => 'exists:studentRegistration,id',
        ]);

        if ($err = $this->backdateError((int) $validated['term_id'], (int) $validated['session_id'], $validated['attendance_date'])) {
            return response()->json(['success' => false, 'message' => $err], 422);
        }

        try {
            DB::transaction(function () use ($validated) {
                foreach ($validated['student_ids'] as $sid) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id'      => $sid,
                            'schoolclass_id'  => $validated['schoolclass_id'],
                            'term_id'         => $validated['term_id'],
                            'session_id'      => $validated['session_id'],
                            'attendance_date' => $validated['attendance_date'],
                            'period'          => $validated['period'],
                        ],
                        ['status' => 'present', 'notes' => null, 'marked_by' => Auth::id()]
                    );
                    AttendanceSettingController::rebuildSummary(
                        $sid,
                        $validated['schoolclass_id'],
                        $validated['term_id'],
                        $validated['session_id']
                    );
                }
            });

            return response()->json(['success' => true, 'message' => 'All students marked present.']);
        } catch (\Exception $e) {
            Log::error('markAllPresent error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // STUDENT REPORT  –  per-student attendance history
    // =========================================================================

    // =========================================================================
    // CLASS HISTORY  –  day-by-day record of what this class has marked
    // =========================================================================

    public function classHistory(Request $request, int $classId, int $termId, int $sessionId)
    {
        $user = Auth::user();
        if (!$this->isAttendanceAdmin()) {
            ClassTeacher::where('staffid', $user->id)->where('schoolclassid', $classId)
                ->where('termid', $termId)->where('sessionid', $sessionId)->firstOrFail();
        }

        $days = StudentAttendance::where('schoolclass_id', $classId)
            ->where('term_id', $termId)->where('session_id', $sessionId)
            ->selectRaw("attendance_date, period,
                SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) present,
                SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) late,
                SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) absent,
                SUM(CASE WHEN status IN ('sick_leave','excused') THEN 1 ELSE 0 END) excused,
                COUNT(*) total, MAX(updated_at) last_marked")
            ->groupBy('attendance_date', 'period')
            ->orderByDesc('attendance_date')->orderBy('period')
            ->paginate(31)->withQueryString();

        $schoolclass = Schoolclass::with('arms')->find($classId);
        $term        = Schoolterm::find($termId);
        $session     = Schoolsession::find($sessionId);
        $pagetitle   = 'Attendance History';

        return view('attendance.teacher.class-history', compact(
            'days', 'schoolclass', 'term', 'session', 'classId', 'termId', 'sessionId', 'pagetitle'
        ));
    }

    public function studentReport(Request $request, int $studentId, int $classId, int $termId, int $sessionId)
    {
        $records = StudentAttendance::where('student_id', $studentId)
            ->where('schoolclass_id', $classId)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->orderBy('attendance_date')
            ->get();

        $summary = AttendanceSummary::where([
            'student_id'     => $studentId,
            'schoolclass_id' => $classId,
            'term_id'        => $termId,
            'session_id'     => $sessionId,
        ])->first();

        // CORRECT - table name specified to remove ambiguity
        $student = Studentclass::where('studentclass.studentId', $studentId)
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->first([
                'studentRegistration.id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentRegistration.othername as mname',
                'studentpicture.picture',
            ]);

        $setting = AttendanceTermSetting::where('term_id', $termId)->where('session_id', $sessionId)->first();
        $term    = Schoolterm::find($termId);
        $session = Schoolsession::find($sessionId);
        $class   = Schoolclass::with('arms')->find($classId);

        $pagetitle = "Attendance Report – {$student?->lname} {$student?->fname}";

        return view('attendance.teacher.student-report', compact(
            'records', 'summary', 'student', 'term', 'session', 'class', 'setting',
            'classId', 'termId', 'sessionId', 'pagetitle'
        ));
    }

    // =========================================================================
    // CLASS SUMMARY TABLE
    // =========================================================================

    public function classSummary(int $classId, int $termId, int $sessionId)
    {
        $summaries = AttendanceSummary::where('schoolclass_id', $classId)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->join('studentRegistration', 'studentRegistration.id', '=', 'attendance_summaries.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->orderBy('studentRegistration.lastname')
            ->get([
                'attendance_summaries.*',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentRegistration.othername as mname',
                'studentpicture.picture',
            ]);

        $class   = Schoolclass::with('arms')->find($classId);
        $term    = Schoolterm::find($termId);
        $session = Schoolsession::find($sessionId);
        $setting = AttendanceTermSetting::where('term_id', $termId)->where('session_id', $sessionId)->first();

        $pagetitle = "Class Summary – {$class?->schoolclass} {$class?->arms?->arm}";

        return view('attendance.teacher.class-summary', compact(
            'summaries', 'class', 'term', 'session', 'setting',
            'classId', 'termId', 'sessionId', 'pagetitle'
        ));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function isHolidayDate(string $date, int $termId, int $sessionId): bool
    {
        return AttendanceHoliday::where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->where(function ($q) use ($date) {
                $q->where(function ($qq) use ($date) {
                    $qq->whereNull('holiday_end_date')->where('holiday_date', $date);
                })->orWhere(function ($qq) use ($date) {
                    $qq->where('holiday_date', '<=', $date)
                       ->where('holiday_end_date', '>=', $date);
                });
            })->exists();
    }

    private function buildCalendarDays(AttendanceTermSetting $setting, int $termId, int $sessionId): array
    {
        $holidays = AttendanceHoliday::where('term_id', $termId)->where('session_id', $sessionId)->get();

        $holidayDates = collect();
        foreach ($holidays as $h) {
            $s = $h->holiday_date->copy();
            $e = $h->holiday_end_date ?? $h->holiday_date;
            while ($s->lte($e)) {
                $holidayDates->push($s->toDateString());
                $s->addDay();
            }
        }

        $days    = [];
        $current = $setting->resumption_date->copy();
        while ($current->lte($setting->vacation_date)) {
            if (!$current->isWeekend()) {
                $days[] = [
                    'date'       => $current->toDateString(),
                    'label'      => $current->format('D, d M'),
                    'is_today'   => $current->isToday(),
                    'is_holiday' => $holidayDates->contains($current->toDateString()),
                ];
            }
            $current->addDay();
        }

        return $days;
    }
}
