<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Student;
use App\Models\Assessment;
use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use App\Models\Classcategory;
use App\Models\Schoolsession;
use App\Models\BroadsheetRecord;
use App\Models\AttendanceSummary;
use App\Models\Studentpersonalityprofile;
use App\Models\CompulsorySubjectClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SchoolInformation;
use App\Services\PromotionEvaluator;
use App\Services\ClassPositionService;
use App\Services\ResultAccessService;
use App\Models\BroadsheetsMock;

class StudentAssessmentController extends Controller
{
    protected ClassPositionService $positionService;

    protected ResultAccessService $resultAccess;

    public function __construct(ClassPositionService $positionService, ResultAccessService $resultAccess)
    {
        $this->positionService = $positionService;
        $this->resultAccess    = $resultAccess;

        $this->middleware('permission:View student assessments', ['only' => ['index', 'printResult', 'printMockResult']]);
    }

    // =========================================================================
    // FORMAT ORDINAL
    // =========================================================================

    protected function formatOrdinal($number)
    {
        if (!is_numeric($number) || $number <= 0) {
            return '-';
        }

        $lastDigit     = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) {
            return $number . 'th';
        }

        return $number . match ($lastDigit) {
            1       => 'st',
            2       => 'nd',
            3       => 'rd',
            default => 'th',
        };
    }

    // =========================================================================
    // GRADE HELPERS
    // =========================================================================

    protected function calculateSeniorGrade($score)
    {
        if ($score === null || $score < 0) return 'F9';
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

    protected function calculateJuniorGrade($score)
    {
        if ($score === null || $score < 40) return 'F';
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    protected function getGradePoint($score, $isSenior = false): float
    {
        if (!$isSenior) {
            if ($score >= 70) return 5.0;
            if ($score >= 60) return 4.0;
            if ($score >= 50) return 3.0;
            if ($score >= 40) return 2.0;
            return 0.0;
        } else {
            if ($score >= 75) return 5.0;
            if ($score >= 65) return 4.0;
            if ($score >= 50) return 3.0;
            if ($score >= 45) return 2.0;
            if ($score >= 40) return 1.0;
            return 0.0;
        }
    }

    protected function getGpaGrade(float $gpa): string
    {
        if ($gpa >= 4.5) return 'A1';
        if ($gpa >= 4.0) return 'B2';
        if ($gpa >= 3.5) return 'B3';
        if ($gpa >= 3.0) return 'C4';
        if ($gpa >= 2.5) return 'C5';
        if ($gpa >= 2.0) return 'C6';
        if ($gpa >= 1.5) return 'D7';
        if ($gpa >= 1.0) return 'E8';
        return 'F9';
    }

    protected function getRemark($grade)
    {
        return match ($grade) {
            'A1', 'A' => 'Excellent',
            'B2', 'B3', 'B' => 'Very Good',
            'C4', 'C5', 'C6', 'C' => 'Good',
            'D7', 'D' => 'Pass',
            'E8' => 'Pass',
            'F9', 'F' => 'Fail',
            default => 'Unknown',
        };
    }

    // =========================================================================
    // MOCK DATA HELPER
    // =========================================================================

    private function getMockData(int $studentId, int $schoolclassId, int $sessionId, ?int $termId): \Illuminate\Support\Collection
    {
        if (!$termId) return collect();

        try {
            $rows = BroadsheetsMock::where('broadsheet_records_mock.student_id', $studentId)
                ->where('broadsheet_records_mock.session_id', $sessionId)
                ->where('broadsheet_records_mock.schoolclass_id', $schoolclassId)
                ->where('broadsheetmock.term_id', $termId)
                ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'subject.subject as subject_name',
                    'subject.subject_code',
                    'broadsheetmock.exam',
                    'broadsheetmock.total',
                    'broadsheetmock.grade',
                    'broadsheetmock.remark',
                    'broadsheetmock.subject_position_class as position',
                    'broadsheetmock.avg as class_average',
                    'broadsheetmock.cmin',
                    'broadsheetmock.cmax',
                    'broadsheet_records_mock.id as record_id',
                    'broadsheet_records_mock.subject_id',
                ])
                ->get();

            // Positions not stored yet: rank each subject within the class
            // (same class/session/term), ties sharing a place. The old
            // fallback used a relation that doesn't exist on BroadsheetsMock,
            // threw, and the catch below then hid the whole mock section.
            if ($rows->isNotEmpty() && $rows->every(fn ($row) => empty($row->position) || $row->position == 0)) {
                $classTotals = DB::table('broadsheetmock as bm')
                    ->join('broadsheet_records_mock as brm', 'brm.id', '=', 'bm.broadsheet_records_mock_id')
                    ->where('brm.schoolclass_id', $schoolclassId)
                    ->where('brm.session_id', $sessionId)
                    ->where('bm.term_id', $termId)
                    ->get(['brm.subject_id', 'bm.total'])
                    ->groupBy('subject_id');

                foreach ($rows as $row) {
                    $totals = $classTotals->get($row->subject_id, collect())->pluck('total')->map(fn ($t) => (float) $t);
                    $row->position = $totals->isEmpty() ? null : $totals->filter(fn ($t) => $t > (float) $row->total)->count() + 1;
                }
            }

            return $rows;
        } catch (\Exception $e) {
            Log::error('getMockData error', [
                'student_id'    => $studentId,
                'schoolclassid' => $schoolclassId,
                'sessionid'     => $sessionId,
                'termid'        => $termId,
                'error'         => $e->getMessage(),
            ]);
            return collect();
        }
    }

    // =========================================================================
    // STUDENT PERIOD / CLASS RESOLUTION
    // studentclass only holds a student's CURRENT placement, so on its own it
    // made every past session show "No class registration". The class for a
    // session is taken from the student's results (broadsheet_records) first,
    // then studentclass.
    // =========================================================================

    private function resolveStudentClass(int $studentId, ?int $sessionId, ?int $termId): ?object
    {
        if (!$sessionId) {
            $sessionId = (int) (Schoolsession::where('status', 'Current')->value('id')
                ?: DB::table('studentclass')->where('studentId', $studentId)->orderByDesc('sessionid')->value('sessionid'));
        }
        if (!$sessionId) return null;

        $classId = DB::table('broadsheet_records as br')
            ->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->where('br.student_id', $studentId)
            ->where('br.session_id', $sessionId)
            ->when($termId, fn ($q) => $q->where('b.term_id', $termId))
            ->groupBy('br.schoolclass_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('br.schoolclass_id')
            ?: DB::table('studentclass')->where('studentId', $studentId)->where('sessionid', $sessionId)
                ->orderByDesc('id')->value('schoolclassid');

        if (!$classId) return null;

        $termId = $termId ?: $this->latestTermWithResults($studentId, $sessionId)
            ?: (int) DB::table('studentclass')->where('studentId', $studentId)->where('sessionid', $sessionId)->value('termid');

        $cls = DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('schoolclass.id', $classId)->first(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);

        return (object) [
            'class_id'     => (int) $classId,
            'class_name'   => $cls->schoolclass ?? '',
            'arm_name'     => $cls->arm ?? '',
            'term_id'      => $termId ? (int) $termId : null,
            'term_name'    => $termId ? Schoolterm::where('id', $termId)->value('term') : null,
            'session_id'   => (int) $sessionId,
            'session_name' => Schoolsession::where('id', $sessionId)->value('session'),
        ];
    }

    private function latestTermWithResults(int $studentId, int $sessionId): ?int
    {
        $id = DB::table('broadsheets as b')
            ->join('broadsheet_records as br', 'br.id', '=', 'b.broadsheet_record_id')
            ->where('br.student_id', $studentId)
            ->where('br.session_id', $sessionId)
            ->max('b.term_id');
        return $id ? (int) $id : null;
    }

    /** Sessions the student has results or enrolment in, plus the current one (archived sessions hidden). */
    private function studentSessions(int $studentId)
    {
        $ids = collect()
            ->merge(DB::table('broadsheet_records')->where('student_id', $studentId)->pluck('session_id'))
            ->merge(DB::table('studentclass')->where('studentId', $studentId)->pluck('sessionid'))
            ->merge(Schoolsession::where('status', 'Current')->pluck('id'))
            ->map(fn ($v) => (int) $v)->filter()->unique();

        return Schoolsession::whereIn('id', $ids)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'Archived'))
            ->orderByDesc('id')
            ->get(['id', 'session', 'status']);
    }

    /** Students with results in this class for the term (not the current-only studentclass table). */
    private function classSize(int $schoolclassId, int $sessionId, int $termId): int
    {
        return (int) DB::table('broadsheet_records as br')
            ->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->where('br.schoolclass_id', $schoolclassId)
            ->where('br.session_id', $sessionId)
            ->where('b.term_id', $termId)
            ->distinct()
            ->count('br.student_id');
    }

    private function gpaFromGradePoints($points): array
    {
        $points = collect($points)->map(fn ($p) => (float) $p);
        $gpa    = $points->isNotEmpty() ? $points->avg() : 0.0;
        return [
            'gpa'                => $gpa,
            'cgpa'               => 0.0,
            'gpa_grade'          => $this->getGpaGrade($gpa),
            'num_subjects'       => $points->count(),
            'total_grade_points' => $points->sum(),
        ];
    }

    // =========================================================================
    // COMPUTE GPA/CGPA
    // =========================================================================

    private function computeOverallForStudent($studentId, $schoolclass, $termId, $sessionId, $isSenior): array
    {
        $currentTermBroadsheets = Broadsheets::where('term_id', $termId)
            ->whereHas('broadsheetRecord', function ($q) use ($studentId, $sessionId) {
                $q->where('student_id', $studentId)->where('session_id', $sessionId);
            })
            ->get(['cum_ave']);

        $termGradePoints    = $currentTermBroadsheets->map(fn ($b) => $this->getGradePoint(round($b->cum_ave ?? 0), $isSenior));
        $gpa                = $termGradePoints->avg() ?? 0.0;
        $num_subjects       = $currentTermBroadsheets->count();
        $total_grade_points = $termGradePoints->sum();
        $gpaGrade           = $this->getGpaGrade($gpa);

        return [
            'gpa'                => $gpa,
            'cgpa'               => 0.0,
            'gpa_grade'          => $gpaGrade,
            'num_subjects'       => $num_subjects,
            'total_grade_points' => $total_grade_points,
        ];
    }

    // =========================================================================
    // BUILD GPA TREND
    // =========================================================================

    private function buildGpaTrend(int $studentId, ?int $sessionId, bool $isSenior): array
    {
        $trend = [];
        $terms = Schoolterm::orderBy('id')->get();

        foreach ($terms as $t) {
            $broadsheets = Broadsheets::where('term_id', $t->id)
                ->whereHas('broadsheetRecord', function ($q) use ($studentId, $sessionId) {
                    $q->where('student_id', $studentId);
                    if ($sessionId) $q->where('session_id', $sessionId);
                })
                ->get(['cum_ave']);

            if ($broadsheets->isEmpty()) continue;

            $gp  = $broadsheets->map(fn ($b) => $this->getGradePoint(round($b->cum_ave ?? 0), $isSenior));
            $gpa = $gp->avg() ?? 0.0;
            if ($gpa > 0) {
                $trend[$t->term] = round($gpa, 2);
            }
        }
        return $trend;
    }

    // =========================================================================
    // IMAGE HELPERS
    // =========================================================================

    private function logoToBase64($schoolInfo): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                <rect width="80" height="80" rx="40" fill="#1e3a5f"/>
                <text x="40" y="46" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">SCH</text>
            </svg>'
        );

        if (!$schoolInfo || empty($schoolInfo->school_logo)) return $placeholder;

        $paths = [
            storage_path('app/public/' . $schoolInfo->school_logo),
            public_path('storage/' . $schoolInfo->school_logo),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && filesize($path) > 100) {
                $mime = mime_content_type($path) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
        return $placeholder;
    }

    private function imageToBase64ForPdf(?string $path): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="95" viewBox="0 0 80 95">
                <rect width="80" height="95" fill="#e2e8f0"/>
                <circle cx="40" cy="32" r="18" fill="#94a3b8"/>
                <rect x="20" y="56" width="40" height="28" rx="4" fill="#94a3b8"/>
                <text x="40" y="90" text-anchor="middle" fill="#475569" font-family="Arial" font-size="8">PHOTO</text>
            </svg>'
        );

        if (!$path) return $placeholder;

        $possiblePaths = [
            public_path('storage/student_avatars/' . $path),
            storage_path('app/public/student_avatars/' . $path),
        ];

        foreach ($possiblePaths as $fullPath) {
            if (file_exists($fullPath) && filesize($fullPath) > 100) {
                $mime = mime_content_type($fullPath) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }
        return $placeholder;
    }

    private function getSchoolStampBase64($schoolInfo)
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="#f1f5f9" stroke="#3b82f6" stroke-width="2"/>
                <text x="50" y="55" text-anchor="middle" fill="#1e293b" font-size="12" font-family="Arial">STAMP</text>
            </svg>'
        );

        if (!$schoolInfo || empty($schoolInfo->school_stamp)) {
            return $placeholder;
        }

        $paths = [
            storage_path('app/public/' . $schoolInfo->school_stamp),
            public_path('storage/' . $schoolInfo->school_stamp),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && filesize($path) > 100) {
                $mime = mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        return $placeholder;
    }

    // =========================================================================
    // GET STUDENT PROFILE DATA
    // =========================================================================

    private function getStudentProfileData($studentId, $termId, $sessionId, $schoolclassId)
    {
        try {
            $profile = Studentpersonalityprofile::where('studentid', $studentId)
                ->where('termid', $termId)
                ->where('sessionid', $sessionId)
                ->where('schoolclassid', $schoolclassId)
                ->first();

            return $profile ? collect([$profile]) : collect();
        } catch (\Exception $e) {
            Log::error('Error fetching student personality profile', [
                'student_id' => $studentId,
                'error'      => $e->getMessage(),
            ]);
            return collect();
        }
    }

    // =========================================================================
    // INDEX - Web View (UPDATED with compulsory subjects and term display fix)
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = 'My Assessments';
        $studentId = auth()->user()->student_id;

        if (!$studentId) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        $student = Student::where('id', $studentId)
            ->select('id', 'firstname', 'lastname', 'othername', 'admissionNo', 'gender', 'can_view_assessments')
            ->first();

        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        $terms    = Schoolterm::orderBy('id', 'desc')->get(['id', 'term']);
        $sessions = $this->studentSessions($studentId);

        $userSelectedTermId = $request->get('term_id');
        $selectedSessionId  = $request->get('session_id');
        if (!$selectedSessionId || !$sessions->contains('id', (int) $selectedSessionId)) {
            $selectedSessionId = $sessions->firstWhere('status', 'Current')->id ?? $sessions->first()?->id;
        }
        $selectedTermId     = $userSelectedTermId ?: null;
        $isAllTerms         = empty($userSelectedTermId);

        if ($isAllTerms && $selectedSessionId) {
            // Latest term the student actually has results for in this session
            // (studentclass only keeps the current placement).
            $latestTermId = $this->latestTermWithResults($studentId, (int) $selectedSessionId);
            if ($latestTermId) {
                $selectedTermId = $latestTermId;
            }
        }

        $studentClassData = $this->resolveStudentClass($studentId, $selectedSessionId ? (int) $selectedSessionId : null, $selectedTermId ? (int) $selectedTermId : null);

        if (!$studentClassData) {
            return view('student.assessments.index', compact(
                'pagetitle', 'student', 'terms', 'sessions', 'userSelectedTermId', 'selectedSessionId'
            ))->with('error', 'No class registration found for the selected term and session.');
        }

        $class = (object) [
            'id'          => $studentClassData->class_id,
            'schoolclass' => $studentClassData->class_name,
            'arm_name'    => $studentClassData->arm_name ?? '',
        ];

        $term    = (object) ['id' => $studentClassData->term_id,    'term'    => $studentClassData->term_name];
        $session = (object) ['id' => $studentClassData->session_id, 'session' => $studentClassData->session_name];

        // Result access gate: manual block, or fees owed per the admin's rules.
        $access = $this->resultAccess->check(
            $studentId,
            (int) ($selectedTermId ?: $studentClassData->term_id),
            (int) ($selectedSessionId ?: $studentClassData->session_id)
        );
        if (!$access['allowed']) {
            return view('student.assessments.blocked', compact(
                'pagetitle', 'student', 'class', 'term', 'session', 'terms', 'sessions',
                'userSelectedTermId', 'selectedSessionId', 'access'
            ));
        }

        // FIX: Get the selected term/session names for display
        $selectedTermModel = $userSelectedTermId ? Schoolterm::find($userSelectedTermId) : null;
        $selectedTermName = $selectedTermModel ? $selectedTermModel->term : ($term->term ?? null);
        $selectedSessionName = $session->session ?? null;

        $schoolclass = Schoolclass::with('classcategories')->find($studentClassData->class_id);

        if (!$schoolclass || $schoolclass->classcategories->isEmpty()) {
            return view('student.assessments.index', compact(
                'pagetitle', 'student', 'class', 'term', 'session', 'terms', 'sessions', 
                'userSelectedTermId', 'selectedSessionId', 'selectedTermName', 'selectedSessionName'
            ))->with('error', 'Class category not found.');
        }

        $isSenior    = $schoolclass->classcategories->first()->is_senior ?? false;
        $categoryIds = $schoolclass->classcategories->pluck('id');
        $gradeCategory = $schoolclass->classcategories->first();

        // Ensure positions/averages are current for this class/session/term
        if ($selectedTermId) {
            $this->positionService->recalculate(
                $studentClassData->class_id,
                $selectedSessionId ?? $studentClassData->session_id,
                $selectedTermId
            );
        }

        // Get compulsory subjects for this class
        $compulsorySubjectIds = CompulsorySubjectClass::where('schoolclassid', $studentClassData->class_id)
            ->pluck('subjectId')
            ->toArray();

        $attendanceSummary = AttendanceSummary::where('student_id', $studentId)
            ->where('term_id', $selectedTermId)
            ->where('session_id', $selectedSessionId ?? $studentClassData->session_id)
            ->first();

        $registeredSubjects = DB::table('student_subject_register_record as ssrr')
            ->where('ssrr.studentId', $studentId)
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'ssrr.subjectclassid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'ssrr.session')
            ->when($selectedSessionId, fn ($q) => $q->where('schoolsession.id', $selectedSessionId))
            ->when(!$isAllTerms && $selectedTermId, fn ($q) => $q->where('subjectteacher.termid', $selectedTermId))
            ->where('schoolsession.status', '!=', 'Archived')
            ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->select('subject.id as subject_id', 'subject.subject as subject_name', 'subject.subject_code')
            ->distinct()->get();

        $subjectsWithAssessments = collect();
        $allAssessments          = Assessment::whereIn('classcategory_id', $categoryIds)
            ->with('subAssessments')
            ->orderBy('id')
            ->get();

        $overallProgress = [
            'total_subjects'     => 0,
            'completed_subjects' => 0,
            'total_score'        => 0,
            'average_cum'        => 0,
            'gpa'                => '-',
            'cgpa'               => '-',
            'gpa_grade'          => '-',
            'num_subjects'       => 0,
            'total_grade_points' => 0.0,
        ];

        foreach ($registeredSubjects as $regSubject) {
            $broadsheetRecord = BroadsheetRecord::where('student_id', $studentId)
                ->where('subject_id', $regSubject->subject_id)
                ->where('schoolclass_id', $studentClassData->class_id)
                ->where('session_id', $selectedSessionId ?? $studentClassData->session_id)
                ->first();

            if (!$broadsheetRecord) continue;

            $broadsheet = Broadsheets::where('broadSheet_record_id', $broadsheetRecord->id)
                ->where('term_id', $selectedTermId)
                ->first();

            if (!$broadsheet) continue;

            $broadsheet->load(['assessmentScores', 'subAssessmentScores']);

            $assessmentData = $allAssessments->map(function ($assessment) use ($broadsheet) {
                $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                $score    = $scoreObj ? $scoreObj->score : 0;

                $subScores = collect();
                if ($assessment->subAssessments->isNotEmpty()) {
                    $subScores = $assessment->subAssessments->map(function ($sub) use ($broadsheet) {
                        $subScoreObj = $broadsheet->subAssessmentScores->where('sub_assessment_id', $sub->id)->first();
                        return [
                            'id'         => $sub->id,
                            'name'       => $sub->name,
                            'max_score'  => $sub->max_score,
                            'score'      => $subScoreObj ? $subScoreObj->score : 0,
                            'percentage' => $sub->max_score > 0
                                ? round(($subScoreObj ? $subScoreObj->score : 0) / $sub->max_score * 100, 2)
                                : 0,
                        ];
                    });
                }

                return [
                    'id'              => $assessment->id,
                    'name'            => $assessment->name,
                    'max_score'       => $assessment->max_score,
                    'score'           => $score,
                    'percentage'      => $assessment->max_score > 0 ? round($score / $assessment->max_score * 100, 2) : 0,
                    'sub_assessments' => $subScores,
                ];
            });

            $cumRaw = $broadsheet->cum ?? 0;
            $cumAve = $broadsheet->cum_ave ?? 0;

            $gradeSource  = round($cumAve);
            $subjectGPA   = $this->getGradePoint($gradeSource, $isSenior);
            $subjectGrade = $gradeCategory ? $gradeCategory->calculateGrade($gradeSource) : ($broadsheet->grade ?? '-');

            // Check if this subject is compulsory
            $isCompulsory = in_array($regSubject->subject_id, $compulsorySubjectIds);

            $subjectsWithAssessments->push([
                'grade_point'      => $subjectGPA,
                'subject_id'       => $regSubject->subject_id,
                'subject_name'     => $regSubject->subject_name,
                'subject_code'     => $regSubject->subject_code,
                'assessments'      => $assessmentData,
                'total'            => $broadsheet->total ?? 0,
                'bf'               => $broadsheet->bf ?? 0,
                'cum'              => $cumRaw,
                'cum_ave'          => $cumAve,
                'grade'            => $subjectGrade,
                'subject_gpa'      => round($subjectGPA, 1),
                'remark'           => $broadsheet->remark ?? '-',
                'position'         => $broadsheet->subject_position_class ? $this->formatOrdinal($broadsheet->subject_position_class) : '-',
                'position_total'   => $broadsheet->subject_position_class_total ? $this->formatOrdinal($broadsheet->subject_position_class_total) : '-',
                'arm_position'     => $broadsheet->arm_position ? $this->formatOrdinal($broadsheet->arm_position) : '-',
                'arm_position_cum' => $broadsheet->arm_position_cum ? $this->formatOrdinal($broadsheet->arm_position_cum) : '-',
                'is_compulsory'    => $isCompulsory,
            ]);

            $overallProgress['total_subjects']++;
            if ($cumAve > 0) {
                $overallProgress['completed_subjects']++;
                $overallProgress['total_score'] += $cumAve;
            }
        }

        if ($overallProgress['completed_subjects'] > 0) {
            $overallProgress['average_cum'] = round(
                $overallProgress['total_score'] / $overallProgress['completed_subjects'], 2
            );
        }

        if ($subjectsWithAssessments->isNotEmpty() && $schoolclass) {
            // GPA from exactly the subjects listed on this page (registered
            // subjects with a broadsheet), not every broadsheet row.
            $gpaCgpaData = $this->gpaFromGradePoints($subjectsWithAssessments->pluck('grade_point'));
            $overallProgress['gpa']                = round($gpaCgpaData['gpa'], 2);
            $overallProgress['cgpa']               = round($gpaCgpaData['cgpa'], 2);
            $overallProgress['gpa_grade']          = $gpaCgpaData['gpa_grade'] ?? 'F';
            $overallProgress['num_subjects']       = $gpaCgpaData['num_subjects'];
            $overallProgress['total_grade_points'] = $gpaCgpaData['total_grade_points'];
        }

        $mockResults = $this->getMockData(
            $studentId,
            $studentClassData->class_id,
            $selectedSessionId ?? $studentClassData->session_id,
            $selectedTermId
        );

        $mockTotalObtained   = $mockResults->sum(fn ($r) => (float) ($r->total ?? 0));
        $mockTotalObtainable = $mockResults->count() * 100;
        $mockPercentage      = $mockTotalObtainable > 0
            ? round(($mockTotalObtained / $mockTotalObtainable) * 100, 1)
            : 0;
        $mockSummary = [
            'obtained'   => round($mockTotalObtained, 1),
            'obtainable' => $mockTotalObtainable,
            'percentage' => $mockPercentage,
            'count'      => $mockResults->count(),
        ];

        $gpaTrend       = $this->buildGpaTrend($studentId, $selectedSessionId, $isSenior);
        $studentPicture = DB::table('studentpicture')->where('studentid', $studentId)->value('picture');
        $schoolInfo     = SchoolInformation::first();

        return view('student.assessments.index', compact(
            'pagetitle', 'student', 'class', 'term', 'session',
            'subjectsWithAssessments', 'terms', 'sessions',
            'userSelectedTermId', 'selectedSessionId', 'overallProgress',
            'gpaTrend', 'studentPicture', 'schoolInfo', 'selectedTermId',
            'isSenior', 'allAssessments', 'attendanceSummary',
            'mockResults', 'mockSummary',
            'selectedTermName', 'selectedSessionName'
        ));
    }

    // =========================================================================
    // PRINT TERMINAL RESULT (PDF)
    // =========================================================================

  public function printResult(Request $request)
{
    ini_set('max_execution_time', 120);
    ini_set('memory_limit', '512M');

    $studentId         = auth()->user()->student_id;
    $selectedSessionId = $request->get('session_id');
    $selectedTermId    = $request->get('term_id');
    $selectedColumns   = $request->get('selected_columns', []);

    if (!$studentId) {
        return back()->with('error', 'Student profile not found.');
    }

    $student = Student::where('id', $studentId)
        ->select('id', 'firstname', 'lastname', 'othername', 'admissionNo', 'gender', 'can_view_assessments')
        ->first();

    if (!$student) {
        return back()->with('error', 'Student profile not found.');
    }

    $studentClassData = $this->resolveStudentClass($studentId, $selectedSessionId ? (int) $selectedSessionId : null, $selectedTermId ? (int) $selectedTermId : null);

    if (!$studentClassData) {
        return back()->with('error', 'No class data found.');
    }

    if (!$selectedTermId) {
        $selectedTermId = $studentClassData->term_id;
    }

    $access = $this->resultAccess->check($studentId, (int) $selectedTermId, (int) ($selectedSessionId ?: $studentClassData->session_id));
    if (!$access['allowed']) {
        return redirect()->route('assessments', ['session_id' => $selectedSessionId, 'term_id' => $selectedTermId])
            ->with('error', $access['message']);
    }

    $sessionIdForQuery = $selectedSessionId ?? $studentClassData->session_id;
    $schoolclassId     = $studentClassData->class_id;

    // Ensure positions/averages are current
    $this->positionService->recalculate($schoolclassId, $sessionIdForQuery, $selectedTermId);

    $schoolclass = Schoolclass::with('classcategories')->find($schoolclassId);
    $isSenior    = $schoolclass?->classcategories->first()?->is_senior ?? false;
    $categoryIds = $schoolclass?->classcategories->pluck('id') ?? collect();
    $gradeCategory = $schoolclass?->classcategories->first();

    $termModel    = Schoolterm::find($selectedTermId);
    $sessionModel = Schoolsession::find($sessionIdForQuery);

    $previousTermId = Schoolterm::where('id', '<', $selectedTermId)->orderBy('id', 'desc')->first()?->id;

    // Get compulsory subjects for this class - FIX: Make sure we get the subject IDs
    $compulsorySubjectIds = CompulsorySubjectClass::where('schoolclassid', $schoolclassId)
        ->pluck('subjectId')
        ->toArray();


    $registeredSubjects = DB::table('student_subject_register_record as ssrr')
        ->where('ssrr.studentId', $studentId)
        ->leftJoin('subjectclass', 'subjectclass.id', '=', 'ssrr.subjectclassid')
        ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
        ->leftJoin('schoolsession', 'schoolsession.id', '=', 'ssrr.session')
        ->when($selectedSessionId, fn ($q) => $q->where('schoolsession.id', $selectedSessionId))
        ->when($selectedTermId, fn ($q) => $q->where('subjectteacher.termid', $selectedTermId))
        ->where('schoolsession.status', '!=', 'Archived')
        ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
        ->select('subject.id as subject_id', 'subject.subject as subject_name', 'subject.subject_code')
        ->distinct()->get();

    $allAssessments = Assessment::whereIn('classcategory_id', $categoryIds)->orderBy('id')->get();

    $scores          = collect();
    $totalObtained   = 0;
    $totalObtainable = 0;

    foreach ($registeredSubjects as $regSubject) {
        $broadsheetRecord = BroadsheetRecord::where('student_id', $studentId)
            ->where('subject_id', $regSubject->subject_id)
            ->where('schoolclass_id', $schoolclassId)
            ->where('session_id', $sessionIdForQuery)
            ->first();

        if (!$broadsheetRecord) continue;

        $broadsheet = Broadsheets::where('broadSheet_record_id', $broadsheetRecord->id)
            ->where('term_id', $selectedTermId)
            ->first();

        if (!$broadsheet) continue;

        $broadsheet->load(['assessmentScores', 'subAssessmentScores']);

        $scoreData               = new \stdClass();
        $scoreData->subject_id   = $regSubject->subject_id;
        $scoreData->subject_name = $regSubject->subject_name;
        $scoreData->subject_code = $regSubject->subject_code;
        $scoreData->total        = $broadsheet->total ?? 0;

        $bfValue = 0;
        if ($previousTermId) {
            $previousBroadsheet = Broadsheets::where('broadSheet_record_id', $broadsheetRecord->id)
                ->where('term_id', $previousTermId)
                ->first();
            $bfValue = $previousBroadsheet ? ($previousBroadsheet->cum ?? 0) : 0;
        }
        $scoreData->bf = $bfValue;

        $cumRaw = $broadsheet->cum ?? 0;
        $cumAve = $broadsheet->cum_ave ?? 0;

        $gradeSource = round($cumAve);

        $scoreData->cum           = $cumRaw;
        $scoreData->cum_ave       = $cumAve;
        $scoreData->grade         = $gradeCategory ? $gradeCategory->calculateGrade($gradeSource) : ($broadsheet->grade ?? '-');
        $scoreData->class_average = $broadsheet->avg ?? 0;

        $scoreData->position                     = $broadsheet->subject_position_class ?? null;
        $scoreData->position_total               = $broadsheet->subject_position_class_total ?? null;
        $scoreData->arm_position                 = $broadsheet->arm_position ?? null;
        $scoreData->arm_position_cum             = $broadsheet->arm_position_cum ?? null;
        $scoreData->subject_position_class       = $broadsheet->subject_position_class ?? null;
        $scoreData->subject_position_class_total = $broadsheet->subject_position_class_total ?? null;

        $scoreData->position_formatted         = $broadsheet->subject_position_class ? $this->formatOrdinal($broadsheet->subject_position_class) : '-';
        $scoreData->position_total_formatted   = $broadsheet->subject_position_class_total ? $this->formatOrdinal($broadsheet->subject_position_class_total) : '-';
        $scoreData->arm_position_formatted     = $broadsheet->arm_position ? $this->formatOrdinal($broadsheet->arm_position) : '-';
        $scoreData->arm_position_cum_formatted = $broadsheet->arm_position_cum ? $this->formatOrdinal($broadsheet->arm_position_cum) : '-';

        // Check if compulsory - FIX: Use in_array with proper subject_id
        $scoreData->is_compulsory = in_array($regSubject->subject_id, $compulsorySubjectIds);


        $scoreData->assessment_scores = collect();
        foreach ($allAssessments as $assessment) {
            $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
            $scoreData->assessment_scores->push((object) [
                'assessment_id' => $assessment->id,
                'score'         => $scoreObj ? $scoreObj->score : 0,
                'max_score'     => $assessment->max_score,
                'name'          => $assessment->name,
            ]);
        }

        $scores->push($scoreData);
        $totalObtained   += (float) $scoreData->total;
        $totalObtainable += 100;
    }

    $percentage = $totalObtainable > 0 ? round(($totalObtained / $totalObtainable) * 100, 1) : 0;

    $gpaData = $this->gpaFromGradePoints(
        $scores->map(fn ($sc) => $this->getGradePoint(round((float) $sc->cum_ave), $isSenior))
    );

    // Promotion Evaluation
    $evaluator = new PromotionEvaluator();
    $promotionResult = $evaluator->evaluate(
        studentId:      $studentId,
        schoolclassid:  $schoolclassId,
        termid:         $selectedTermId,
        sessionid:      $sessionIdForQuery,
        scores:         $scores,
        overallAverage: $percentage
    );

    $schoolInfo    = SchoolInformation::first();
    $logoBase64    = $this->logoToBase64($schoolInfo);
    $pictureBase64 = $this->imageToBase64ForPdf(
        DB::table('studentpicture')->where('studentid', $studentId)->value('picture')
    );
    $stampBase64 = $this->getSchoolStampBase64($schoolInfo);

    $numberOfStudents = $this->classSize((int) $schoolclassId, (int) $sessionIdForQuery, (int) $selectedTermId);

    $studentProfileData = $this->getStudentProfileData($studentId, $selectedTermId, $sessionIdForQuery, $schoolclassId);

    $attendanceSummary = AttendanceSummary::where('student_id', $studentId)
        ->where('term_id', $selectedTermId)
        ->where('session_id', $sessionIdForQuery)
        ->first();

    $attendanceData = [];
    if ($attendanceSummary) {
        $attendanceData = [
            'found'                 => true,
            'total_school_days'     => $attendanceSummary->total_school_days ?? 0,
            'days_present'          => $attendanceSummary->days_present ?? 0,
            'days_absent'           => $attendanceSummary->days_absent ?? 0,
            'days_late'             => $attendanceSummary->days_late ?? 0,
            'days_sick_leave'       => $attendanceSummary->days_sick_leave ?? 0,
            'days_excused'          => $attendanceSummary->days_excused ?? 0,
            'attendance_percentage' => $attendanceSummary->attendance_percentage ?? 0,
        ];
    } else {
        $attendanceData = ['found' => false];
    }

    $mockRows            = $this->getMockData($studentId, $schoolclassId, $sessionIdForQuery, $selectedTermId);
    $mockTotalObtained   = $mockRows->sum(fn ($r) => (float) ($r->total ?? 0));
    $mockTotalObtainable = $mockRows->count() * 100;
    $mockPercentage      = $mockTotalObtainable > 0
        ? round(($mockTotalObtained / $mockTotalObtainable) * 100, 1)
        : 0;
    $mockSummaryForPdf = [
        'obtained'   => round($mockTotalObtained, 1),
        'obtainable' => $mockTotalObtainable,
        'percentage' => $mockPercentage,
    ];

    // Get selected term/session names for display
    $selectedTermName = $termModel ? $termModel->term : ($studentClassData->term_name ?? 'Term');
    $selectedSessionName = $sessionModel ? $sessionModel->session : ($studentClassData->session_name ?? 'Session');

    $metadata = [
        'term'             => $selectedTermName,
        'session'          => $selectedSessionName,
        'selected_columns' => $selectedColumns,
        'grade_basis'      => 'cum_ave',
    ];

    $schoolclassWithArms              = new \stdClass();
    $schoolclassWithArms->schoolclass = $studentClassData->class_name ?? '';
    $schoolclassWithArms->arms        = new \stdClass();
    $schoolclassWithArms->arms->arm   = $studentClassData->arm_name ?? '';

    $allStudentData = [[
        'students'             => collect([$student]),
        'schoolclass'          => $schoolclassWithArms,
        'scores'               => $scores,
        'assessments'          => $allAssessments,
        'gpa_data'             => $gpaData,
        'totals_summary'       => [
            'obtained'   => $totalObtained,
            'obtainable' => $totalObtainable,
            'percentage' => $percentage,
        ],
        'schoolInfo'           => $schoolInfo,
        'school_logo_base64'   => $logoBase64,
        'school_stamp_base64'  => $stampBase64,
        'student_image_base64' => $pictureBase64,
        'numberOfStudents'     => $numberOfStudents,
        'studentpp'            => $studentProfileData,
        'attendance_summary'   => $attendanceData,
        'promotion_result'     => $promotionResult,
        'mock_results'         => $mockRows,
        'mock_summary'         => $mockSummaryForPdf,
        'compulsory_subject_ids' => $compulsorySubjectIds, // Add this explicitly
    ]];

    $safeAdmissionNo = preg_replace('/[^A-Za-z0-9\-]/', '_', $student->admissionNo ?? 'student');
    $safeTerm        = preg_replace('/[^A-Za-z0-9\-]/', '_', $selectedTermName);
    $filename        = 'Terminal_Report_' . $safeAdmissionNo . '_' . $safeTerm . '.pdf';

    $pdf = Pdf::loadView('student.assessments.print-pdf', [
        'allStudentData' => $allStudentData,
        'metadata'       => $metadata,
    ])
    ->setPaper('A4', 'portrait')
    ->setOptions([
        'dpi'                  => 150,
        'defaultFont'          => 'DejaVu Sans',
        'isRemoteEnabled'      => true,
        'isHtml5ParserEnabled' => true,
    ]);

    return $pdf->stream($filename);
}
    // =========================================================================
    // PRINT MOCK RESULT (PDF)
    // =========================================================================

    public function printMockResult(Request $request)
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '512M');

        $studentId         = auth()->user()->student_id;
        $selectedSessionId = $request->get('session_id');
        $selectedTermId    = $request->get('term_id');

        if (!$studentId) {
            return back()->with('error', 'Student profile not found.');
        }

        $student = Student::where('id', $studentId)
            ->select('id', 'firstname', 'lastname', 'othername', 'admissionNo', 'gender', 'can_view_assessments')
            ->first();

        if (!$student) {
            return back()->with('error', 'Student profile not found.');
        }

        $studentClassData = $this->resolveStudentClass($studentId, $selectedSessionId ? (int) $selectedSessionId : null, $selectedTermId ? (int) $selectedTermId : null);

        if (!$studentClassData) {
            return back()->with('error', 'No class data found.');
        }

        if (!$selectedTermId) {
            $selectedTermId = $studentClassData->term_id;
        }

        $access = $this->resultAccess->check($studentId, (int) $selectedTermId, (int) ($selectedSessionId ?: $studentClassData->session_id), true);
        if (!$access['allowed']) {
            return redirect()->route('assessments', ['session_id' => $selectedSessionId, 'term_id' => $selectedTermId])
                ->with('error', $access['message']);
        }

        $sessionIdForQuery = $selectedSessionId ?? $studentClassData->session_id;
        $schoolclassId     = $studentClassData->class_id;

        $termModel    = Schoolterm::find($selectedTermId);
        $sessionModel = Schoolsession::find($sessionIdForQuery);
        $schoolInfo   = SchoolInformation::first();

        $mockRows = $this->getMockData($studentId, $schoolclassId, $sessionIdForQuery, $selectedTermId);

        $mockTotalObtained   = $mockRows->sum(fn ($r) => (float) ($r->total ?? 0));
        $mockTotalObtainable = $mockRows->count() * 100;
        $mockPercentage      = $mockTotalObtainable > 0
            ? round(($mockTotalObtained / $mockTotalObtainable) * 100, 1)
            : 0;

        $logoBase64    = $this->logoToBase64($schoolInfo);
        $pictureBase64 = $this->imageToBase64ForPdf(
            DB::table('studentpicture')->where('studentid', $studentId)->value('picture')
        );
        $stampBase64 = $this->getSchoolStampBase64($schoolInfo);

        $numberOfStudents = $this->classSize((int) $schoolclassId, (int) $sessionIdForQuery, (int) $selectedTermId);

        $schoolclassWithArms              = new \stdClass();
        $schoolclassWithArms->schoolclass = $studentClassData->class_name ?? '';
        $schoolclassWithArms->arms        = new \stdClass();
        $schoolclassWithArms->arms->arm   = $studentClassData->arm_name ?? '';

        $selectedTermName = $termModel ? $termModel->term : ($studentClassData->term_name ?? 'Term');
        $selectedSessionName = $sessionModel ? $sessionModel->session : ($studentClassData->session_name ?? 'Session');

        $safeAdmissionNo = preg_replace('/[^A-Za-z0-9\-]/', '_', $student->admissionNo ?? 'student');
        $safeTerm        = preg_replace('/[^A-Za-z0-9\-]/', '_', $selectedTermName);
        $filename        = 'Mock_Report_' . $safeAdmissionNo . '_' . $safeTerm . '.pdf';

        $pdf = Pdf::loadView('student.assessments.print-mock-pdf', [
            'student'          => $student,
            'schoolclass'      => $schoolclassWithArms,
            'mockRows'         => $mockRows,
            'mockSummary'      => [
                'obtained'   => round($mockTotalObtained, 1),
                'obtainable' => $mockTotalObtainable,
                'percentage' => $mockPercentage,
            ],
            'schoolInfo'       => $schoolInfo,
            'logoBase64'       => $logoBase64,
            'stampBase64'      => $stampBase64,
            'pictureBase64'    => $pictureBase64,
            'numberOfStudents' => $numberOfStudents,
            'term'             => $selectedTermName,
            'session'          => $selectedSessionName,
        ])
        ->setPaper('A4', 'portrait')
        ->setOptions([
            'dpi'                  => 150,
            'defaultFont'          => 'DejaVu Sans',
            'isRemoteEnabled'      => true,
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->stream($filename);
    }

    // =========================================================================
    // GET COLUMN OPTIONS (for PDF column selection)
    // =========================================================================

    public function getColumnOptions(Request $request)
    {
        $schoolclassid = $request->input('schoolclassid');
        $sessionid     = $request->input('sessionid');
        $termid        = $request->input('termid');

        if (!$schoolclassid || !$sessionid || !$termid) {
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
        $assessments = collect();

        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            try {
                if (class_exists(\App\Models\Assessment::class)) {
                    $assessments = \App\Models\Assessment::whereIn('classcategory_id', $categoryIds)
                        ->with('subAssessments')
                        ->orderBy('id')
                        ->get();
                }
            } catch (\Exception $e) {
                Log::error('Error loading assessments for column options', ['error' => $e->getMessage()]);
            }
        }

        $columns = [
            'student_info' => [
                'sn'           => ['label' => 'SN',            'default' => true],
                'admission_no' => ['label' => 'Admission No',  'default' => true],
                'name'         => ['label' => 'Name',          'default' => true],
                'picture'      => ['label' => 'Picture',       'default' => true],
                'gender'       => ['label' => 'Gender',        'default' => false],
                'dob'          => ['label' => 'Date of Birth', 'default' => false],
            ],
            'assessments' => [],
            'scores' => [
                'total'            => ['label' => 'Total',                        'default' => true],
                'bf'               => ['label' => 'BF',                           'default' => true],
                'cum'              => ['label' => 'Cum (raw sum)',                'default' => true],
                'cum_ave'          => ['label' => 'Cum Ave',                      'default' => true],
                'grade'            => ['label' => 'Grade',                        'default' => true],
                'arm_position'     => ['label' => 'Arm Pos (Total) — This Arm',   'default' => true],
                'arm_position_cum' => ['label' => 'Arm Pos (Cum) — This Arm',     'default' => true],
                'position_total'   => ['label' => 'Class Pos (Total) — All Arms', 'default' => true],
                'position'         => ['label' => 'Class Pos (Cum) — All Arms',   'default' => true],
                'class_average'    => ['label' => 'Class Avg',                    'default' => true],
            ],
            'gpa_metrics' => [
                'num_subjects'       => ['label' => 'Num Subjects', 'default' => true],
                'total_grade_points' => ['label' => 'Total GP',     'default' => true],
                'gpa'                => ['label' => 'GPA',          'default' => true],
                'calculated_gpa'     => ['label' => 'Calc GPA',     'default' => true],
                'gpa_grade'          => ['label' => 'GPA Grade',    'default' => true],
                'cgpa'               => ['label' => 'CGPA',         'default' => true],
            ],
            'attendance' => [
                'attendance_days_present' => ['label' => 'Days Present',      'default' => true],
                'attendance_days_absent'  => ['label' => 'Days Absent',       'default' => true],
                'attendance_days_late'    => ['label' => 'Days Late',         'default' => false],
                'attendance_sick_leave'   => ['label' => 'Sick Leave',        'default' => false],
                'attendance_excused'      => ['label' => 'Excused',           'default' => false],
                'attendance_total_days'   => ['label' => 'Total School Days', 'default' => true],
                'attendance_percentage'   => ['label' => 'Attendance %',      'default' => true],
            ],
            'other' => [
                'compulsory_flag'   => ['label' => 'Compulsory',    'default' => false],
                'vetted_status'     => ['label' => 'Vetted Status', 'default' => true],
                'promotion_status'  => ['label' => 'Promotion',     'default' => true],
            ],
        ];

        foreach ($assessments as $assessment) {
            $columns['assessments'][$assessment->id] = [
                'label'               => $assessment->name . ' (' . $assessment->max_score . ')',
                'default'             => true,
                'is_assessment'       => true,
                'max_score'           => $assessment->max_score,
                'has_sub_assessments' => $assessment->subAssessments->isNotEmpty(),
            ];
        }

        return response()->json([
            'success'           => true,
            'columns'           => $columns,
            'assessments_count' => $assessments->count(),
            'is_senior'         => $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? ($schoolclass->classcategories->first()->is_senior ?? false)
                : false,
        ]);
    }
}