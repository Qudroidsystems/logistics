<?php

namespace App\Http\Controllers;

use App\Models\BroadsheetsMock;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\Studentclass;
use App\Models\Studentpersonalityprofile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ViewStudentMockReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View student-mock-report', ['only' => [
            'index', 'registeredClasses', 'classBroadsheet', 'studentmockresult',
            'exportStudentMockResultPdf', 'exportClassMockResultsPdf', 'calculateGradePreview',
            'getColumnOptions',
        ]]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function formatOrdinal($number)
    {
        if (!is_numeric($number) || $number <= 0) return '-';
        $lastDigit     = $number % 10;
        $lastTwoDigits = $number % 100;
        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) return $number . 'th';
        return $number . match ($lastDigit) {
            1       => 'st',
            2       => 'nd',
            3       => 'rd',
            default => 'th',
        };
    }

    protected function calculateGrade($score)
    {
        if ($score === null || $score == 0) return 'F9';
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
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    protected function getDefaultGrade($score)
    {
        return $this->calculateJuniorGrade($score);
    }

    protected function getRemark($grade)
    {
        return match ($grade) {
            'A', 'A1'             => 'Excellent',
            'B', 'B2', 'B3'       => 'Very Good',
            'C', 'C4', 'C5', 'C6' => 'Good',
            'D', 'D7', 'E8'       => 'Pass',
            default               => 'Fail',
        };
    }

    // =========================================================================
    // CLASS POSITIONS & AVERAGES
    // =========================================================================

    protected function calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid)
    {
        $cacheKey = "mock_class_metrics_{$schoolclassid}_{$sessionid}_{$termid}";
        Cache::forget($cacheKey);

        $schoolclass = Schoolclass::with('classcategories')
            ->where('id', $schoolclassid)
            ->first(['id', 'schoolclass', 'classcategoryid']);

        if (!$schoolclass) {
            Log::warning('Schoolclass not found for mock metrics', compact('schoolclassid', 'sessionid', 'termid'));
            return false;
        }

        $className = $schoolclass->schoolclass;
        $isSenior  = $schoolclass->classcategories->isNotEmpty()
            ? ($schoolclass->classcategories->first()->is_senior ?? false)
            : false;

        $classIds = Schoolclass::where('schoolclass', $className)->pluck('id')->toArray();
        if (empty($classIds)) return false;

        $students = Studentclass::whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionid)
            ->pluck('studentId')
            ->toArray();

        if (empty($students)) return false;

        $broadsheets = BroadsheetsMock::whereIn('broadsheet_records_mock.student_id', $students)
            ->where('broadsheetmock.term_id', $termid)
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->whereIn('broadsheet_records_mock.schoolclass_id', $classIds)
            ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->select([
                'broadsheetmock.id',
                'broadsheet_records_mock.student_id',
                'broadsheet_records_mock.subject_id',
                'subject.subject as subject_name',
                'studentRegistration.admissionNo as admission_no',
                'broadsheetmock.total',
                'broadsheetmock.subject_position_class',
                'broadsheetmock.avg',
                'broadsheetmock.grade',
                'broadsheetmock.remark',
            ])
            ->get();

        if ($broadsheets->isEmpty()) return false;

        $subjectGroups = $broadsheets->groupBy('subject_id');

        foreach ($subjectGroups as $subjectId => $subjectRecords) {
            $validRecords = $subjectRecords->filter(fn ($r) => $r->total != 0 && $r->total !== null);
            $classAvg     = $validRecords->count() > 0
                ? round($validRecords->sum('total') / $validRecords->count(), 1)
                : 0;

            $sortedRecords = $validRecords->sortByDesc('total')->values();
            $rank          = 0;
            $lastTotal     = null;
            $lastPosition  = 0;
            $positionMap   = [];

            foreach ($sortedRecords as $record) {
                $rank++;
                if ($lastTotal !== null && $record->total == $lastTotal) {
                    $positionMap[$record->id] = $lastPosition;
                } else {
                    $lastPosition             = $rank;
                    $lastTotal                = $record->total;
                    $positionMap[$record->id] = $lastPosition;
                }
            }

            foreach ($subjectRecords as $record) {
                $newPosition = $record->total == 0 ? '-' : $this->formatOrdinal($positionMap[$record->id] ?? 0);

                $grade = $record->total == 0 ? '-' : (
                    $isSenior && $schoolclass->classcategories->isNotEmpty()
                        ? $schoolclass->classcategories->first()->calculateGrade($record->total)
                        : $this->calculateJuniorGrade($record->total)
                );
                $remark = $this->getRemark($grade);

                if (
                    $record->avg != $classAvg ||
                    $record->subject_position_class != $newPosition ||
                    $record->grade != $grade ||
                    $record->remark != $remark
                ) {
                    BroadsheetsMock::where('id', $record->id)->update([
                        'avg'                    => $classAvg,
                        'subject_position_class' => $newPosition,
                        'grade'                  => $grade,
                        'remark'                 => $remark,
                    ]);
                }
            }
        }

        Cache::put($cacheKey, true, now()->addHours(1));
        return true;
    }

    // =========================================================================
    // STUDENT MOCK RESULT DATA
    // =========================================================================

    private function getStudentMockResultData($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            if (!is_numeric($id) || !is_numeric($schoolclassid) || !is_numeric($sessionid) || !is_numeric($termid)) {
                return [];
            }

            $students = Student::where('studentRegistration.id', $id)
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->select([
                    'studentRegistration.id as id',
                    'studentRegistration.admissionNo as admissionNo',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lastname',
                    'studentRegistration.othername as othername',
                    'studentRegistration.dateofbirth as dateofbirth',
                    'studentRegistration.gender as gender',
                    'studentRegistration.home_address2 as present_address',
                    'studentRegistration.updated_at as updated_at',
                    'studentpicture.picture as picture',
                ])
                ->get();

            if ($students->isEmpty()) $students = collect([]);

            $mockScores = BroadsheetsMock::where('broadsheet_records_mock.student_id', $id)
                ->where('broadsheetmock.term_id', $termid)
                ->where('broadsheet_records_mock.session_id', $sessionid)
                ->where('broadsheet_records_mock.schoolclass_id', $schoolclassid)
                ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'subject.id as subject_id',
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
                ])
                ->get();

            // Totals summary
            $totalObtained   = $mockScores->sum(fn ($s) => (float) ($s->total ?? 0));
            $totalObtainable = $mockScores->count() * 100;
            $totalPercentage = $totalObtainable > 0
                ? round(($totalObtained / $totalObtainable) * 100, 1)
                : 0;

            $totalsSummary = [
                'obtained'   => round($totalObtained, 1),
                'obtainable' => $totalObtainable,
                'percentage' => $totalPercentage,
            ];

            $schoolclass = Schoolclass::with(['arms', 'classcategories'])->find($schoolclassid);
            $schoolterm  = Schoolterm::find($termid);
            $schoolsession = Schoolsession::find($sessionid);

            $numberOfStudents = Studentclass::whereIn(
                'schoolclassid',
                Schoolclass::where('schoolclass', $schoolclass->schoolclass ?? '')->pluck('id')
            )->where('sessionid', $sessionid)->count();

            $schoolInfo = SchoolInformation::first();
            if (!$schoolInfo) {
                $schoolInfo                        = new \stdClass();
                $schoolInfo->school_name           = 'School Name Not Found';
                $schoolInfo->school_logo           = null;
                $schoolInfo->school_stamp          = null; // ADD THIS LINE
                $schoolInfo->school_motto          = 'Motto Not Found';
                $schoolInfo->school_address        = 'Address Not Found';
                $schoolInfo->school_phone          = 'Phone Not Found';
                $schoolInfo->date_next_term_begins = null;
            }else {
                // Ensure school_stamp is included
                $schoolInfo->school_stamp = $schoolInfo->school_stamp ?? null;
            }

            $studentpp = Studentpersonalityprofile::where('studentid', $id)
                ->where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->where('termid', $termid)
                ->get();

            return [
                'students'         => $students,
                'studentpp'        => $studentpp,
                'mockScores'       => $mockScores,
                'studentid'        => $id,
                'schoolclassid'    => $schoolclassid,
                'sessionid'        => $sessionid,
                'termid'           => $termid,
                'schoolclass'      => $schoolclass,
                'schoolterm'       => $schoolterm,
                'schoolsession'    => $schoolsession,
                'numberOfStudents' => $numberOfStudents,
                'schoolInfo'       => $schoolInfo,
                'totals_summary'   => $totalsSummary,
            ];
        } catch (\Exception $e) {
            Log::error('getStudentMockResultData error', [
                'student_id'    => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
                'error'         => $e->getMessage(),
            ]);
            return [];
        }
    }

    // =========================================================================
    // COLUMN OPTIONS (for PDF modal)
    // =========================================================================

    public function getColumnOptions(Request $request)
    {
        $schoolclassid = $request->input('schoolclassid');
        $sessionid     = $request->input('sessionid');
        $termid        = $request->input('termid');

        if (!$schoolclassid || !$sessionid || !$termid) {
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        $columns = [
            'student_info' => [
                'sn'           => ['label' => 'SN',           'default' => true],
                'name'         => ['label' => 'Subject Name', 'default' => true],
                'picture'      => ['label' => 'Picture',      'default' => true],
                'gender'       => ['label' => 'Gender',       'default' => false],
                'dob'          => ['label' => 'Date of Birth','default' => false],
            ],
            'scores' => [
                'exam'          => ['label' => 'Exam Score',   'default' => true],
                'total'         => ['label' => 'Total',        'default' => true],
                'grade'         => ['label' => 'Grade',        'default' => true],
                'position'      => ['label' => 'Position',     'default' => true],
                'class_average' => ['label' => 'Class Avg',    'default' => true],
                'cmin'          => ['label' => 'Class Min',    'default' => false],
                'cmax'          => ['label' => 'Class Max',    'default' => false],
            ],
            'other' => [
                'vetted_status' => ['label' => 'Vetted Status', 'default' => false],
            ],
        ];

        return response()->json([
            'success' => true,
            'columns' => $columns,
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): View|JsonResponse
    {
        $pagetitle   = "Student Mock Report Management";
        $allstudents = new LengthAwarePaginator([], 0, 10);

        if (
            $request->filled('schoolclassid') && $request->filled('sessionid') &&
            $request->input('schoolclassid') !== 'ALL' && $request->input('sessionid') !== 'ALL'
        ) {
            $query = Studentclass::query()
                ->where('schoolclassid', $request->input('schoolclassid'))
                ->where('sessionid', $request->input('sessionid'))
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->where('schoolsession.status', 'Current');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.firstname', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.lastname', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.othername', 'like', "%{$search}%");
                });
            }

            $allstudents = $query->select([
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.othername as othername',
                'studentRegistration.gender as gender',
                'studentRegistration.id as stid',
                'studentpicture.picture as picture',
                'studentclass.schoolclassid as schoolclassID',
                'studentclass.sessionid as sessionid',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as schoolarm',
                'schoolsession.session as session',
            ])->latest('studentclass.created_at')->paginate(100);
        }

        $schoolsessions = Schoolsession::where('status', 'Current')->get();
        $schoolclasses  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);

        if ($request->ajax()) {
            return response()->json([
                'tableBody'    => view('studentmockreports.partials.student_rows', compact('allstudents'))->render(),
                'pagination'   => $allstudents->links('pagination::bootstrap-5')->render(),
                'studentCount' => $allstudents->total(),
            ]);
        }

        return view('studentmockreports.index', compact('allstudents', 'schoolsessions', 'schoolclasses', 'pagetitle'));
    }

    // =========================================================================
    // STUDENT MOCK RESULT VIEW
    // =========================================================================

    public function studentmockresult($id, $schoolclassid, $sessionid, $termid): View
    {
        $pagetitle         = "Student Mock Result";
        $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);

        if (!$metricsCalculated) {
            Log::warning('Mock metrics calculation returned false', compact('schoolclassid', 'sessionid', 'termid'));
        }

        $data = $this->getStudentMockResultData($id, $schoolclassid, $sessionid, $termid);

        return view('studentmockreports.studentmockresult')->with($data)->with('pagetitle', $pagetitle);
    }

    // =========================================================================
    // REGISTERED CLASSES
    // =========================================================================

    public function registeredClasses(Request $request): JsonResponse
    {
        $classId   = $request->query('class_id');
        $sessionId = $request->query('session_id');

        if (!$classId || !$sessionId || $classId === 'ALL' || $sessionId === 'ALL') {
            return response()->json(['success' => false, 'message' => 'Please select a valid class and session.'], 400);
        }

        $classes = Studentclass::query()
            ->join('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolclass.id', $classId)
            ->where('schoolsession.id', $sessionId)
            ->where('schoolsession.status', 'Current')
            ->groupBy('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm', 'schoolsession.session')
            ->selectRaw('
                schoolclass.schoolclass as class_name,
                schoolarm.arm as name_arm,
                schoolsession.session as session_name,
                COUNT(DISTINCT studentclass.studentId) as student_count
            ')
            ->get();

        return response()->json(['success' => true, 'data' => $classes]);
    }

    // =========================================================================
    // SINGLE STUDENT PDF
    // =========================================================================

    public function exportStudentMockResultPdf($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
            $data = $this->getStudentMockResultData($id, $schoolclassid, $sessionid, $termid);

            if (empty($data) || empty($data['students']) || $data['students']->isEmpty()) {
                return back()->with('error', 'No student data found for the provided parameters.');
            }

            // NOTE: fixImagePaths() takes its argument by reference, so we can't pass
            // a literal array expression like [$data] directly (PHP can't bind a
            // reference to a temporary value). We wrap $data into a real variable
            // first, pass that, then unwrap the (now-mutated) result back into $data.
            $dataWrapper = [$data];
            $this->fixImagePaths($dataWrapper);
            $data = $dataWrapper[0];

            $student     = $data['students']->first();
            $studentName = $student ? $student->fname . '_' . $student->lastname : 'Student';
            $session     = $data['schoolsession']->session ?? 'session';
            $filename    = 'Mock_Report_' . $studentName . '_' . $session . '_Term_' . $termid . '.pdf';

            $pdf = Pdf::loadView('studentmockreports.studentmockresult_pdf', ['data' => $data])
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi'                     => 150,
                    'defaultFont'             => 'DejaVu Sans',
                    'isRemoteEnabled'         => true,
                    'isHtml5ParserEnabled'    => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled'            => false,
                    'chroot'                  => [public_path(), storage_path()],
                    'fontCache'               => storage_path('fonts/'),
                    'logOutputFile'           => storage_path('logs/dompdf.log'),
                ]);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('exportStudentMockResultPdf error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // CLASS PDF
    // =========================================================================

    public function exportClassMockResultsPdf(Request $request)
    {
        try {
            ini_set('max_execution_time', 1200);
            ini_set('memory_limit', '2048M');

            $schoolclassid   = $request->input('schoolclassid');
            $sessionid       = $request->input('sessionid');
            $termid          = $request->input('termid');
            $studentIds      = $request->input('studentIds', []);
            $selectedColumns = $request->input('selectedColumns', []);

            if (!$schoolclassid || !$sessionid || !$termid) {
                return response()->json(['success' => false, 'message' => 'Missing required parameters.'], 400);
            }

            $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);

            $allStudentData = [];
            $processedCount = 0;
            $failedCount    = 0;

            foreach ($studentIds as $studentId) {
                $studentData = $this->getStudentMockResultData($studentId, $schoolclassid, $sessionid, $termid);

                if (!empty($studentData) && !empty($studentData['students']) && $studentData['students']->isNotEmpty()) {
                    $studentData['selected_columns'] = $selectedColumns;
                    $allStudentData[]                = $studentData;
                    $processedCount++;
                } else {
                    $failedCount++;
                    Log::warning('Skipped student - empty mock data', ['student_id' => $studentId]);
                }
            }

            if (empty($allStudentData)) {
                return response()->json(['success' => false, 'message' => 'No valid student data found.'], 500);
            }

            $this->fixImagePaths($allStudentData);

            $schoolclass   = Schoolclass::where('id', $schoolclassid)->with(['arms', 'classcategories'])->first();
            $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';
            $term          = Schoolterm::where('id', $termid)->value('term') ?? 'Term';
            $className     = $schoolclass
                ? ($schoolclass->schoolclass . ($schoolclass->arms ? $schoolclass->arms->arm : ''))
                : 'Class';
            $filename      = 'Class_Mock_Results_'
                . preg_replace('/[^A-Za-z0-9_-]/', '_', $className) . '_'
                . preg_replace('/[^A-Za-z0-9_-]/', '_', $schoolsession) . '_'
                . $term . '.pdf';

            $viewData = [
                'allStudentData' => $allStudentData,
                'metadata'       => [
                    'class_name'       => $className,
                    'session'          => $schoolsession,
                    'term'             => $term,
                    'generation_date'  => now()->format('Y-m-d H:i:s'),
                    'student_count'    => count($allStudentData),
                    'selected_columns' => $selectedColumns,
                ],
            ];

            $this->ensureDirectoriesExist();

            $pdf = Pdf::loadView('studentmockreports.class_mock_results_pdf', $viewData)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi'                     => 96,
                    'defaultFont'             => 'DejaVu Sans',
                    'isRemoteEnabled'         => true,
                    'isHtml5ParserEnabled'    => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled'            => false,
                    'chroot'                  => [public_path(), storage_path()],
                    'tempDir'                 => storage_path('app/temp/'),
                    'fontCache'               => storage_path('fonts/'),
                    'logOutputFile'           => storage_path('logs/dompdf.log'),
                    'isJavascriptEnabled'     => false,
                ]);

            $pdfContent = $pdf->output();

            if (empty($pdfContent)) {
                return response()->json(['success' => false, 'message' => 'Generated PDF content is empty.'], 500);
            }

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            Log::error('exportClassMockResultsPdf error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GRADE PREVIEW
    // =========================================================================

    public function calculateGradePreview(Request $request): JsonResponse
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'total'          => 'required|numeric|min:0|max:100',
        ]);

        $schoolclass = Schoolclass::with('classcategories')->findOrFail($request->schoolclass_id);
        $grade       = $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()->calculateGrade($request->total)
            : $this->getDefaultGrade($request->total);

        return response()->json(['grade' => $grade, 'remark' => $this->getRemark($grade)]);
    }

    // =========================================================================
    // IMAGE HELPERS (mirrors terminal controller)
    // =========================================================================

    private function getAbsoluteImagePath($path, $isStudent = false)
    {
        if (empty($path)) return null;

        if (str_starts_with($path, public_path()) || str_starts_with($path, storage_path())) {
            return file_exists($path) ? $path : null;
        }

        if (str_starts_with($path, 'data:image')) return null;

        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('/^(http:\/\/|https:\/\/|\/\/)[^\/]+/', '', $path);
        $path = ltrim($path, DIRECTORY_SEPARATOR);

        $possiblePaths = $isStudent
            ? [
                public_path('storage/student_avatars/' . $path),
                storage_path('app/public/student_avatars/' . $path),
                public_path('storage/' . $path),
                storage_path('app/public/' . $path),
                public_path($path),
            ]
            : [
                storage_path('app/public/' . $path),
                public_path('storage/' . $path),
                storage_path('app/public/school_logos/' . basename($path)),
                public_path('storage/school_logos/' . basename($path)),
                public_path($path),
            ];

        foreach (array_unique($possiblePaths) as $fullPath) {
            if (file_exists($fullPath)) return $fullPath;
        }

        return null;
    }

    private function imageToBase64($imagePath)
    {
        if (str_starts_with((string) $imagePath, 'data:image')) return $imagePath;

        if (!$imagePath || !file_exists($imagePath)) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                        <rect width="100" height="100" fill="#f0f0f0"/>
                        <circle cx="50" cy="40" r="15" fill="#ddd"/>
                        <rect x="35" y="60" width="30" height="25" fill="#ddd" rx="2"/>
                    </svg>';
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        try {
            $imageData = file_get_contents($imagePath);
            $mimeType  = mime_content_type($imagePath) ?: 'image/jpeg';
            return "data:{$mimeType};base64," . base64_encode($imageData);
        } catch (\Exception $e) {
            Log::error('imageToBase64 failed', ['path' => $imagePath, 'error' => $e->getMessage()]);
            return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f8f9fa"/></svg>'
            );
        }
    }

    private function fixImagePaths(&$studentData)
    {
        $defaultStudentImage = public_path('storage/student_avatars/unnamed.jpg');
        $defaultSchoolLogo   = public_path('storage/school_logos/default.jpg');

        foreach ($studentData as &$student) {
            // Student image
            $picturePath = $student['students']->isNotEmpty()
                ? ($student['students']->first()->picture ?? null)
                : null;

            if ($picturePath) {
                $abs = $this->getAbsoluteImagePath($picturePath, true);
                $student['student_image_base64'] = $this->imageToBase64($abs ?: $defaultStudentImage);
            } else {
                $student['student_image_base64'] = $this->imageToBase64($defaultStudentImage);
            }

            // School logo
            $logoPath = $student['schoolInfo']->school_logo ?? null;
            if ($logoPath) {
                $abs = $this->getAbsoluteImagePath($logoPath, false);
                $student['school_logo_base64'] = $this->imageToBase64($abs ?: $defaultSchoolLogo);
            } else {
                $student['school_logo_base64'] = $this->imageToBase64($defaultSchoolLogo);
            }
        }
    }

    private function ensureDirectoriesExist()
    {
        foreach ([storage_path('app/temp'), storage_path('fonts'), storage_path('logs'), public_path('temp_pdfs')] as $dir) {
            if (!file_exists($dir)) mkdir($dir, 0755, true);
        }
    }

    private function validateStudentData($studentData): bool
    {
        return !empty($studentData) && !empty($studentData['students']) && isset($studentData['mockScores']);
    }
}
