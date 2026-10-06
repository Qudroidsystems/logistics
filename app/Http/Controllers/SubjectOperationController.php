<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Subjectclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\Studentpicture;
use App\Models\SubjectTeacher;
use App\Models\BroadsheetsMock;
use App\Models\BroadsheetRecord;
use App\Models\SchoolInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BroadsheetRecordMock;
use App\Models\StudentSubjectRecord;
use App\Models\ArchiveScoreSnapshot;
use App\Models\SubjectRegistrationStatus;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetSubAssessmentScore;
use App\Models\SubjectUnregistrationArchive;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class SubjectOperationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View subject-operation|Create subject-operation|Update subject-operation|Delete subject-operation', ['only' => ['index', 'subjectinfo', 'getRegisteredClasses', 'getArchivedRegistrations', 'getSnapshotDetail']]);
        $this->middleware('permission:Create subject-operation', ['only' => ['store', 'restoreRegistration']]);
        $this->middleware('permission:Delete subject-operation', ['only' => ['destroy', 'permanentlyDeleteArchive', 'permanentlyDeleteArchiveBatch']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): \Illuminate\View\View|\Illuminate\Http\Response
    {
        $pagetitle = "Subject Operation Management";

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id as id', 'schoolarm.arm as schoolarm', 'schoolclass.schoolclass as schoolclass'])
            ->orderBy('schoolclass.schoolclass')
            ->get();
        $schoolterms    = Schoolterm::all();
        $schoolsessions = Schoolsession::all();

        $staffs = User::whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Student');
        })->get(['users.id as userid', 'users.name as name', 'users.avatar as avatar']);

        $students        = null;
        $subjectTeachers = null;

        if ($request->filled(['class_id', 'session_id']) &&
            $request->input('class_id') !== 'ALL' &&
            $request->input('session_id') !== 'ALL') {

            $subjectTeachers = SubjectTeacher::leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->where('subjectteacher.sessionid', $request->input('session_id'))
                ->where('subjectclass.schoolclassid', $request->input('class_id'))
                ->select([
                    'subjectteacher.id as id',
                    'subjectclass.id as subjectclassid',
                    'users.id as userid',
                    'users.name as staffname',
                    'users.avatar as avatar',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                    'subjectteacher.updated_at',
                ])
                ->orderBy('subject.subject')
                ->get();

            $query = Student::leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('studentclass', 'studentclass.studentid', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('studentRegistration.admissionno', 'like', "%{$search}%")
                        ->orWhere('studentRegistration.firstname', 'like', "%{$search}%")
                        ->orWhere('studentRegistration.lastname', 'like', "%{$search}%");
                });
            }

            if ($gender = $request->input('gender')) {
                if ($gender !== 'ALL') {
                    $query->where('studentRegistration.gender', $gender);
                }
            }

            if ($admissionNo = $request->input('admissionno')) {
                if ($admissionNo !== 'ALL') {
                    $query->where('studentRegistration.admissionno', $admissionNo);
                }
            }

            $query->where('studentclass.schoolclassid', $request->input('class_id'))
                ->where('studentclass.sessionid', $request->input('session_id'));

            $students = $query->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionno as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentRegistration.updated_at',
                'studentpicture.picture',
                'studentclass.studentid as studentid',
                'studentclass.schoolclassid as schoolclassid',
                'studentclass.sessionid',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
            ])->paginate(100)->appends($request->query());
        }

        return view('subjectoperation.index', compact(
            'students', 'subjectTeachers', 'pagetitle', 'schoolclass', 'schoolterms', 'schoolsessions'
        ));
    }

    // =========================================================================
    // SUBJECT INFO
    // =========================================================================

    public function subjectinfo(Request $request, $id, $schoolclassid, $termid, $sessionid): \Illuminate\View\View|\Illuminate\Http\JsonResponse
    {
        $current = "Current";

        try {
            $pagetitle   = "Subject Operation Management";
            $studentdata = Student::where('id', $id)->get();
            if ($studentdata->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Student not found'], 404);
            }

            $studentpic = Studentpicture::where('studentid', $id)->select(['studentid', 'picture as avatar'])->get();

            $subjectclass = Subjectclass::query()
                ->where('subjectclass.schoolclassid', $schoolclassid)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->where('schoolterm.id', $termid)
                ->where('schoolsession.id', $sessionid)
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('staffbioinfo', 'staffbioinfo.userid', '=', 'users.id')
                ->leftJoin('staffpicture', 'staffpicture.staffid', '=', 'users.id')
                ->groupBy([
                    'subject.id', 'users.id', 'staffbioinfo.title', 'users.name',
                    'staffpicture.picture', 'subject.subject', 'subject.subject_code',
                    'subjectclass.id', 'schoolterm.term', 'schoolterm.id',
                    'schoolsession.session', 'schoolsession.id',
                ])
                ->select([
                    'subject.id as subjectid', 'staffbioinfo.title', 'users.name',
                    'staffpicture.picture as picture', 'subject.subject',
                    'users.id as staffid', 'subject.subject_code as subjectcode',
                    'subjectclass.id as subjectclassid', 'schoolterm.term',
                    'schoolterm.id as termid', 'schoolsession.session',
                    'schoolsession.id as sessionid',
                ])
                ->orderBy('subject.subject')
                ->get();

            $subjectRegistrations = [];
            foreach ($subjectclass as $sc) {
                $subjectRegistrations[$sc->subjectid][$sc->staffid] = [
                    'subjectclassid' => $sc->subjectclassid,
                    'status' => StudentSubjectRecord::where([
                        'studentId'      => $id,
                        'subjectclassid' => $sc->subjectclassid,
                        'staffid'        => $sc->staffid,
                        'session'        => $sessionid,
                    ])->exists()
                        ? ['status' => 'Registered', 'broadsheetid' => SubjectRegistrationStatus::where([
                            'studentid'      => $id,
                            'subjectclassid' => $sc->subjectclassid,
                            'staffid'        => $sc->staffid,
                        ])->value('broadsheetid')]
                        : ['status' => 'Not Registered', 'broadsheetid' => null],
                ];
            }

            $totalreg = Subjectclass::where('subjectclass.schoolclassid', $schoolclassid)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->where('schoolterm.id', $termid)
                ->where('schoolsession.id', $sessionid)
                ->distinct('subjectteacher.subjectid')
                ->count('subjectteacher.subjectid');

            $regcount = StudentSubjectRecord::where('student_subject_register_record.studentId', $id)
                ->leftJoin('subjectclass', 'subjectclass.id', '=', 'student_subject_register_record.subjectclassid')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'student_subject_register_record.session')
                ->where('schoolterm.id', $termid)
                ->where('schoolsession.status', $current)
                ->count();

            $noregcount = $totalreg - $regcount;

            $classname = Schoolclass::where('schoolclass.id', $schoolclassid)
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(['schoolclass.id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
                ->get();

            $terms = Schoolterm::all();

            return view('subjectoperation.subjectinfo', compact(
                'studentpic', 'classname', 'subjectclass', 'subjectRegistrations',
                'studentdata', 'id', 'termid', 'sessionid', 'totalreg',
                'regcount', 'noregcount', 'pagetitle', 'terms'
            ));

        } catch (\Exception $error) {
            Log::error('Error fetching subject info', [
                'student_id'    => $id,
                'schoolclassid' => $schoolclassid,
                'error'         => $error->getMessage(),
                'trace'         => $error->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subject information: ' . $error->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // GET SUBJECT TEACHERS AJAX
    // =========================================================================

    public function getSubjectTeachers(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $classId   = $request->input('class_id');
        $termId    = $request->input('term_id');
        $sessionId = $request->input('session_id');

        if (!$classId || !$termId || !$sessionId ||
            $classId === 'ALL' || $termId === 'ALL' || $sessionId === 'ALL') {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        $subjectTeachers = SubjectTeacher::leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('subjectteacher.termid', $termId)
            ->where('subjectteacher.sessionid', $sessionId)
            ->where('subjectclass.schoolclassid', $classId)
            ->select([
                'subjectteacher.id as id',
                'subjectclass.id as subjectclassid',
                'users.id as userid',
                'users.name as staffname',
                'users.avatar as avatar',
                'subject.id as subjectid',
                'subject.subject as subjectname',
                'subject.subject_code as subjectcode',
                'schoolterm.id as termid',
                'schoolterm.term as termname',
                'schoolsession.id as sessionid',
                'schoolsession.session as sessionname',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
            ])
            ->orderBy('subject.subject')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $subjectTeachers,
            'count'   => $subjectTeachers->count(),
        ]);
    }

    // =========================================================================
    // GET SCHOOL INFORMATION (for print)
    // =========================================================================

    public function getSchoolInformation(): JsonResponse
    {
        try {
            $schoolInfo = SchoolInformation::getActiveSchool();
            if (!$schoolInfo) {
                return response()->json(['success' => false, 'message' => 'School information not found'], 404);
            }
            return response()->json(['success' => true, 'data' => $schoolInfo]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // REGISTERED CLASSES — fixed student counts (no broken broadsheet join)
    // =========================================================================

    public function registeredClasses(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_id'   => ['required', 'integer', 'exists:schoolclass,id'],
                'session_id' => ['required', 'integer', 'exists:schoolsession,id'],
                'term_id'    => ['nullable', 'integer', 'exists:schoolterm,id'],
            ]);

            $classInfo = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->where('schoolclass.id', $validated['class_id'])
                ->select([
                    'schoolclass.id as class_id',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                ])
                ->first();

            if (!$classInfo) {
                return response()->json(['success' => false, 'message' => 'Class not found.'], 404);
            }

            $sessionInfo = Schoolsession::find($validated['session_id']);

            $terms = !empty($validated['term_id'])
                ? Schoolterm::where('id', $validated['term_id'])->get()
                : Schoolterm::all();

            $processedData = [];

            foreach ($terms as $term) {
                $subjectRecords = Subjectclass::query()
                    ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                    ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                    ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                    ->where('subjectclass.schoolclassid', $validated['class_id'])
                    ->where('subjectteacher.sessionid', $validated['session_id'])
                    ->where('subjectteacher.termid', $term->id)
                    ->whereNotNull('subject.id')
                    ->select([
                        'subjectclass.id as subjectclass_id',
                        'subject.id as subject_id',
                        'subject.subject as subject_name',
                        'subject.subject_code as subject_code',
                        'users.id as teacher_id',
                        'users.name as teacher_name',
                    ])
                    ->orderBy('subject.subject', 'asc')
                    ->get();

                if ($subjectRecords->isEmpty()) {
                    continue;
                }

                $subjectsWithTeachers = [];
                foreach ($subjectRecords as $record) {
                    $subjectKey = $record->subject_id;

                    if (!isset($subjectsWithTeachers[$subjectKey])) {
                        $subjectsWithTeachers[$subjectKey] = [
                            'id'              => $record->subject_id,
                            'name'            => $record->subject_name,
                            'code'            => $record->subject_code,
                            'subjectclass_id' => $record->subjectclass_id,
                            'teachers'        => [],
                            'student_count'   => 0,
                        ];
                    }

                    if ($record->teacher_id && $record->teacher_name) {
                        $alreadyAdded = false;
                        foreach ($subjectsWithTeachers[$subjectKey]['teachers'] as $t) {
                            if ($t['id'] == $record->teacher_id) { $alreadyAdded = true; break; }
                        }
                        if (!$alreadyAdded) {
                            $subjectsWithTeachers[$subjectKey]['teachers'][] = [
                                'id'   => $record->teacher_id,
                                'name' => $record->teacher_name,
                            ];
                        }
                    }
                }

                $subjectclassIds = $subjectRecords->pluck('subjectclass_id')->unique()->toArray();

                $studentCountsRaw = SubjectRegistrationStatus::query()
                    ->whereIn('subjectclassid', $subjectclassIds)
                    ->where('sessionid', $validated['session_id'])
                    ->where('termid', $term->id)
                    ->select([
                        'subjectclassid',
                        DB::raw('COUNT(DISTINCT studentid) as student_count'),
                    ])
                    ->groupBy('subjectclassid')
                    ->get()
                    ->keyBy('subjectclassid');

                $subjectclassToSubject = [];
                foreach ($subjectRecords as $record) {
                    $subjectclassToSubject[$record->subjectclass_id] = $record->subject_id;
                }

                $subjectStudentCounts = [];
                foreach ($studentCountsRaw as $subjectclassId => $row) {
                    $subjectId = $subjectclassToSubject[$subjectclassId] ?? null;
                    if (!$subjectId) continue;
                    if (!isset($subjectStudentCounts[$subjectId])) {
                        $subjectStudentCounts[$subjectId] = 0;
                    }
                    $subjectStudentCounts[$subjectId] = max(
                        $subjectStudentCounts[$subjectId],
                        (int) $row->student_count
                    );
                }

                foreach ($subjectsWithTeachers as $key => $subject) {
                    $subjectsWithTeachers[$key]['student_count'] = $subjectStudentCounts[$subject['id']] ?? 0;
                }

                $subjectsWithTeachers = array_values($subjectsWithTeachers);

                $totalStudents = SubjectRegistrationStatus::query()
                    ->whereIn('subjectclassid', $subjectclassIds)
                    ->where('sessionid', $validated['session_id'])
                    ->where('termid', $term->id)
                    ->distinct('studentid')
                    ->count('studentid');

                $processedData[] = [
                    'class_id'          => $classInfo->class_id,
                    'class_name'        => $classInfo->class_name,
                    'arm_name'          => $classInfo->arm_name ?? '',
                    'session_name'      => $sessionInfo->session ?? 'N/A',
                    'term_name'         => $term->term,
                    'term_id'           => $term->id,
                    'student_count'     => $totalStudents,
                    'subject_count'     => count($subjectsWithTeachers),
                    'subjects_teachers' => $subjectsWithTeachers,
                ];
            }

            if (empty($processedData)) {
                return response()->json(['success' => false, 'message' => 'No subjects found for this class and session. Please ensure subjects and teachers have been assigned.'], 200);
            }

            return response()->json(['success' => true, 'data' => $processedData]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching registered classes', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PER-STUDENT SUBJECT COUNT — for accurate PDF breakdown
    // =========================================================================

    public function getStudentSubjectCounts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_id'   => ['required', 'integer', 'exists:schoolclass,id'],
                'session_id' => ['required', 'integer', 'exists:schoolsession,id'],
                'term_id'    => ['nullable', 'integer', 'exists:schoolterm,id'],
            ]);

            $subjectclassQuery = Subjectclass::query()
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->where('subjectclass.schoolclassid', $validated['class_id'])
                ->where('subjectteacher.sessionid', $validated['session_id']);

            if (!empty($validated['term_id'])) {
                $subjectclassQuery->where('subjectteacher.termid', $validated['term_id']);
            }

            $subjectclassIds = $subjectclassQuery->pluck('subjectclass.id')->unique()->toArray();

            if (empty($subjectclassIds)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $srsQuery = SubjectRegistrationStatus::query()
                ->whereIn('subjectclassid', $subjectclassIds)
                ->where('sessionid', $validated['session_id']);

            if (!empty($validated['term_id'])) {
                $srsQuery->where('termid', $validated['term_id']);
            }

            $perStudent = $srsQuery
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'subject_registration_status.studentid')
                ->select([
                    'subject_registration_status.studentid',
                    'studentRegistration.admissionno',
                    DB::raw("CONCAT(COALESCE(studentRegistration.lastname,''), ' ', COALESCE(studentRegistration.firstname,'')) as student_name"),
                    'studentRegistration.gender',
                    DB::raw('COUNT(DISTINCT subject_registration_status.subjectclassid) as subject_count'),
                ])
                ->groupBy([
                    'subject_registration_status.studentid',
                    'studentRegistration.admissionno',
                    'studentRegistration.lastname',
                    'studentRegistration.firstname',
                    'studentRegistration.gender',
                ])
                ->orderBy('studentRegistration.lastname')
                ->get();

            return response()->json(['success' => true, 'data' => $perStudent]);

        } catch (\Exception $e) {
            Log::error('Error fetching per-student subject counts', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GET REGISTERED CLASSES (Legacy)
    // =========================================================================

    public function getRegisteredClasses(Request $request): JsonResponse
    {
        try {
            $classId   = $request->input('class_id');
            $sessionId = $request->input('session_id');
            $termId    = $request->input('term_id');

            $query = Subjectclass::query()
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('student_subject_register_record', 'student_subject_register_record.subjectclassid', '=', 'subjectclass.id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->whereNotNull('student_subject_register_record.studentId');

            if ($classId && $classId !== 'ALL') {
                $query->where('subjectclass.schoolclassid', $classId);
            }
            if ($sessionId && $sessionId !== 'ALL') {
                $query->where('subjectteacher.sessionid', $sessionId);
            }
            if ($termId && $termId !== 'ALL') {
                $query->where('subjectteacher.termid', $termId);
            }

            $registeredClasses = $query->select([
                'schoolclass.id as class_id',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
                'schoolsession.session as session_name',
                'schoolterm.term as term_name',
                DB::raw('COUNT(DISTINCT student_subject_register_record.studentId) as student_count'),
                DB::raw('COUNT(DISTINCT subject.id) as subject_count'),
                DB::raw('GROUP_CONCAT(DISTINCT subject.subject ORDER BY subject.subject SEPARATOR ", ") as subjects'),
                DB::raw('GROUP_CONCAT(DISTINCT users.name ORDER BY users.name SEPARATOR ", ") as teachers'),
            ])
                ->groupBy([
                    'schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm',
                    'schoolsession.session', 'schoolterm.term',
                ])
                ->get();

            return response()->json(['success' => true, 'data' => $registeredClasses], 200);

        } catch (\Exception $e) {
            Log::error("Error fetching registered classes: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // STORE (REGISTER) — Individual
    // =========================================================================

    public function store(Request $request): array
    {
        $validated = $request->validate([
            'studentid'      => ['required', 'array'],
            'studentid.*'    => ['required', 'exists:studentRegistration,id'],
            'subjectclassid' => ['required', 'exists:subjectclass,id'],
            'staffid'        => ['required', 'exists:users,id'],
            'termid'         => ['required', 'exists:schoolterm,id'],
            'sessionid'      => ['required', 'exists:schoolsession,id'],
        ]);

        $studentCount          = count($validated['studentid']);
        $batchThreshold        = 50;
        $largeDatasetThreshold = 500;

        if ($studentCount <= $batchThreshold) {
            return $this->processIndividually($validated);
        } elseif ($studentCount <= $largeDatasetThreshold) {
            return $this->processBatch($validated);
        } else {
            return $this->processLargeDataset($validated);
        }
    }

    // =========================================================================
    // BATCH REGISTER
    // =========================================================================

    public function batchRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'studentids'                      => ['required', 'array'],
            'studentids.*'                    => ['required', 'exists:studentRegistration,id'],
            'subjectclasses'                  => ['required', 'array'],
            'subjectclasses.*.subjectclassid' => ['required', 'exists:subjectclass,id'],
            'subjectclasses.*.staffid'        => ['required', 'exists:users,id'],
            'subjectclasses.*.termid'         => ['required', 'exists:schoolterm,id'],
            'sessionid'                       => ['required', 'exists:schoolsession,id'],
        ]);

        $results      = [];
        $errors       = [];
        $successCount = 0;

        try {
            DB::beginTransaction();

            foreach ($validated['subjectclasses'] as $subject) {
                $response = $this->processIndividually([
                    'studentid'      => $validated['studentids'],
                    'subjectclassid' => $subject['subjectclassid'],
                    'staffid'        => $subject['staffid'],
                    'termid'         => $subject['termid'],
                    'sessionid'      => $validated['sessionid'],
                ]);

                if ($response['success']) {
                    $successCount += $response['success_count'];
                } else {
                    $errors[] = [
                        'subjectclassid' => $subject['subjectclassid'],
                        'termid'         => $subject['termid'],
                        'message'        => $response['message'] ?? 'Error',
                        'details'        => $response['errors'] ?? [],
                    ];
                }
                $results[] = $response;
            }

            DB::commit();

            return response()->json([
                'success'       => empty($errors),
                'message'       => 'Batch registration completed.',
                'results'       => $results,
                'error_details' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Batch registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — soft-archives with score snapshot
    // =========================================================================

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'studentids'                      => ['required', 'array'],
            'studentids.*'                    => ['required', 'exists:studentRegistration,id'],
            'subjectclasses'                  => ['required', 'array'],
            'subjectclasses.*.subjectclassid' => ['required', 'exists:subjectclass,id'],
            'subjectclasses.*.staffid'        => ['required', 'exists:users,id'],
            'subjectclasses.*.termid'         => ['required', 'exists:schoolterm,id'],
            'sessionid'                       => ['required', 'exists:schoolsession,id'],
            'snapshot_name'                   => ['nullable', 'string', 'max:191'],
            'snapshot_notes'                  => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['snapshot_name'])) {
            $validated['snapshot_name'] = 'Unregistration — ' . now()->format('d M Y H:i');
        }

        $results              = [];
        $errors               = [];
        $unregisteredStudents = [];
        $skippedCount         = 0;
        $unregisteredById     = Auth::id();

        try {
            DB::beginTransaction();

            foreach ($validated['subjectclasses'] as $subject) {
                $subjectclassid = $subject['subjectclassid'];
                $staffid        = $subject['staffid'];
                $termid         = $subject['termid'];
                $sessionid      = $validated['sessionid'];

                $subjectclass  = Subjectclass::findOrFail($subjectclassid);
                $subjectId     = $subjectclass->subjectid;
                $schoolclassId = $subjectclass->schoolclassid;

                $existingRegistrations = SubjectRegistrationStatus::where([
                    'subjectclassid' => $subjectclassid,
                    'termid'         => $termid,
                    'sessionid'      => $sessionid,
                    'staffid'        => $staffid,
                ])->whereIn('studentid', $validated['studentids'])
                    ->get()
                    ->keyBy('studentid');

                $studentsToProcess = array_values(array_intersect(
                    $validated['studentids'],
                    $existingRegistrations->keys()->toArray()
                ));
                $skippedCount += count(array_diff($validated['studentids'], $studentsToProcess));

                if (empty($studentsToProcess)) {
                    $errors[] = [
                        'subjectclassid' => $subjectclassid,
                        'termid'         => $termid,
                        'message'        => 'No students are registered for this subject.',
                    ];
                    continue;
                }

                $unregisteredStudents = array_unique(array_merge($unregisteredStudents, $studentsToProcess));

                $broadsheetRecordIds = $existingRegistrations->pluck('broadsheetid')->filter()->toArray();

                $now         = now();
                $archiveRows = [];
                foreach ($studentsToProcess as $studentId) {
                    $reg           = $existingRegistrations->get($studentId);
                    $archiveRows[] = [
                        'studentid'            => $studentId,
                        'subjectclassid'       => $subjectclassid,
                        'staffid'              => $staffid,
                        'termid'               => $termid,
                        'sessionid'            => $sessionid,
                        'subjectid'            => $subjectId,
                        'schoolclassid'        => $schoolclassId,
                        'broadsheet_record_id' => $reg?->broadsheetid,
                        'unregistered_by'      => $unregisteredById,
                        'snapshot_name'        => $validated['snapshot_name'],
                        'snapshot_notes'       => $validated['snapshot_notes'] ?? null,
                        'status'               => SubjectUnregistrationArchive::STATUS_ARCHIVED,
                        'unregistered_at'      => $now,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
                SubjectUnregistrationArchive::insertOrIgnore($archiveRows);

                $createdArchives = SubjectUnregistrationArchive::whereIn('studentid', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('termid', $termid)
                    ->where('sessionid', $sessionid)
                    ->where('staffid', $staffid)
                    ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                    ->get()
                    ->keyBy('studentid');

                $this->captureScoreSnapshots(
                    $createdArchives,
                    $studentsToProcess,
                    $broadsheetRecordIds,
                    $subjectclassid,
                    $subjectId,
                    $schoolclassId,
                    $sessionid,
                    $termid,
                    $staffid,
                    $now
                );

                $broadsheetSheetIds = Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                    ->where('term_id', $termid)
                    ->where('subjectclass_id', $subjectclassid)
                    ->pluck('id');

                if ($broadsheetSheetIds->isNotEmpty()) {
                    BroadsheetAssessmentScore::whereIn('broadsheet_id', $broadsheetSheetIds)->delete();
                    BroadsheetSubAssessmentScore::whereIn('broadsheet_id', $broadsheetSheetIds)->delete();
                }

                $mockRecordIds = BroadsheetRecordMock::whereIn('student_id', $studentsToProcess)
                    ->where('subject_id', $subjectId)
                    ->where('schoolclass_id', $schoolclassId)
                    ->where('session_id', $sessionid)
                    ->pluck('id');

                if ($mockRecordIds->isNotEmpty()) {
                    BroadsheetsMock::whereIn('broadsheet_records_mock_id', $mockRecordIds)
                        ->where('subjectclass_id', $subjectclassid)
                        ->where('term_id', $termid)
                        ->where('staff_id', $staffid)
                        ->delete();
                }

                Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                    ->where('term_id', $termid)
                    ->where('subjectclass_id', $subjectclassid)
                    ->delete();

                $orphanedRecordIds = collect($broadsheetRecordIds)->filter(function ($recordId) {
                    return Broadsheets::where('broadsheet_record_id', $recordId)->doesntExist();
                })->toArray();

                if (!empty($orphanedRecordIds)) {
                    BroadsheetRecord::whereIn('id', $orphanedRecordIds)->delete();
                }

                if ($mockRecordIds->isNotEmpty()) {
                    $orphanedMockIds = BroadsheetRecordMock::whereIn('id', $mockRecordIds)
                        ->get()
                        ->filter(function ($mock) {
                            return BroadsheetsMock::where('broadsheet_records_mock_id', $mock->id)->doesntExist();
                        })
                        ->pluck('id')
                        ->toArray();

                    if (!empty($orphanedMockIds)) {
                        BroadsheetRecordMock::whereIn('id', $orphanedMockIds)->delete();
                    }
                }

                StudentSubjectRecord::whereIn('studentId', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('staffid', $staffid)
                    ->where('session', $sessionid)
                    ->delete();

                SubjectRegistrationStatus::whereIn('studentid', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('termid', $termid)
                    ->where('sessionid', $sessionid)
                    ->where('staffid', $staffid)
                    ->delete();

                $results[] = [
                    'subjectclassid'        => $subjectclassid,
                    'termid'                => $termid,
                    'message'               => 'Successfully unregistered ' . count($studentsToProcess) . ' students',
                    'students_unregistered' => $studentsToProcess,
                ];
            }

            $successCount = count($unregisteredStudents);

            if ($successCount === 0 && !empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success'       => false,
                    'message'       => 'No students were unregistered.',
                    'error_details' => $errors,
                    'success_count' => 0,
                    'skipped_count' => $skippedCount,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success'       => empty($errors),
                'message'       => "Successfully unregistered {$successCount} student(s) from " . count($validated['subjectclasses']) . " subject(s).",
                'results'       => $results,
                'error_details' => $errors,
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch unregistration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Batch unregistration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // GET ARCHIVED REGISTRATIONS
    // =========================================================================

    public function getArchivedRegistrations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id'   => ['required', 'integer', 'exists:schoolclass,id'],
            'session_id' => ['required', 'integer', 'exists:schoolsession,id'],
            'term_id'    => ['nullable', 'integer', 'exists:schoolterm,id'],
            'per_page'   => ['nullable', 'integer', 'in:20,50,100,150'],
        ]);

        try {
            $perPage = $request->input('per_page', 50);

            $query = SubjectUnregistrationArchive::query()
                ->where('subject_unregistration_archive.status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->where('subject_unregistration_archive.sessionid', $validated['session_id'])
                ->where('subject_unregistration_archive.schoolclassid', $validated['class_id'])
                ->leftJoin('subject', 'subject.id', '=', 'subject_unregistration_archive.subjectid')
                ->leftJoin('users as staff', 'staff.id', '=', 'subject_unregistration_archive.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subject_unregistration_archive.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subject_unregistration_archive.sessionid')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subject_unregistration_archive.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('users as actor', 'actor.id', '=', 'subject_unregistration_archive.unregistered_by')
                ->select([
                    DB::raw('MIN(subject_unregistration_archive.id) as archive_id'),
                    'subject_unregistration_archive.snapshot_name',
                    'subject_unregistration_archive.snapshot_notes',
                    'subject_unregistration_archive.subjectclassid',
                    'subject_unregistration_archive.termid',
                    'subject_unregistration_archive.sessionid',
                    'subject_unregistration_archive.subjectid',
                    'subject_unregistration_archive.schoolclassid',
                    'subject_unregistration_archive.staffid',
                    DB::raw('COUNT(DISTINCT subject_unregistration_archive.studentid) as student_count'),
                    DB::raw('MIN(subject_unregistration_archive.unregistered_at) as unregistered_at'),
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'staff.name as staffname',
                    'schoolterm.term as termname',
                    'schoolsession.session as sessionname',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                    'actor.name as unregistered_by_name',
                ])
                ->groupBy([
                    'subject_unregistration_archive.snapshot_name',
                    'subject_unregistration_archive.snapshot_notes',
                    'subject_unregistration_archive.subjectclassid',
                    'subject_unregistration_archive.termid',
                    'subject_unregistration_archive.sessionid',
                    'subject_unregistration_archive.subjectid',
                    'subject_unregistration_archive.schoolclassid',
                    'subject_unregistration_archive.staffid',
                    'subject.subject',
                    'subject.subject_code',
                    'staff.name',
                    'schoolterm.term',
                    'schoolsession.session',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'actor.name',
                ]);

            if (!empty($validated['term_id'])) {
                $query->where('subject_unregistration_archive.termid', $validated['term_id']);
            }

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('subject_unregistration_archive.snapshot_name', 'like', "%{$search}%")
                      ->orWhere('subject.subject', 'like', "%{$search}%");
                });
            }

            $query->orderBy('unregistered_at', 'desc');

            $archived = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $archived->items(),
                'meta'    => [
                    'current_page' => $archived->currentPage(),
                    'last_page'    => $archived->lastPage(),
                    'total'        => $archived->total(),
                    'per_page'     => $archived->perPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching archived registrations', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GET SNAPSHOT DETAIL
    // =========================================================================

    public function getSnapshotDetail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'snapshot_name'  => ['required', 'string'],
            'subjectclassid' => ['required', 'integer', 'exists:subjectclass,id'],
            'termid'         => ['required', 'integer', 'exists:schoolterm,id'],
            'sessionid'      => ['required', 'integer', 'exists:schoolsession,id'],
            'staffid'        => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $archives = SubjectUnregistrationArchive::where([
                'snapshot_name'  => $validated['snapshot_name'],
                'subjectclassid' => $validated['subjectclassid'],
                'termid'         => $validated['termid'],
                'sessionid'      => $validated['sessionid'],
                'staffid'        => $validated['staffid'],
                'status'         => SubjectUnregistrationArchive::STATUS_ARCHIVED,
            ])
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'subject_unregistration_archive.studentid')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'subject_unregistration_archive.id as archive_id',
                'subject_unregistration_archive.studentid',
                'subject_unregistration_archive.snapshot_name',
                'subject_unregistration_archive.snapshot_notes',
                'subject_unregistration_archive.unregistered_at',
                'studentRegistration.admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentpicture.picture',
            ])
            ->orderBy('studentRegistration.lastname')
            ->get();

            if ($archives->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Snapshot not found or already actioned.'], 404);
            }

            $archiveIds = $archives->pluck('archive_id');

            $scoreSnapshots = ArchiveScoreSnapshot::whereIn('archive_id', $archiveIds)
                ->orderBy('student_id')
                ->orderBy('score_type')
                ->orderBy('assessment_id')
                ->orderBy('sub_assessment_id')
                ->get()
                ->groupBy('archive_id');

            $rows = $archives->map(function ($row) use ($scoreSnapshots) {
                $scores = $scoreSnapshots->get($row->archive_id, collect());

                return [
                    'archive_id'            => $row->archive_id,
                    'studentid'             => $row->studentid,
                    'admissionno'           => $row->admissionno,
                    'firstname'             => $row->firstname,
                    'lastname'              => $row->lastname,
                    'othername'             => $row->othername,
                    'gender'                => $row->gender,
                    'picture'               => $row->picture,
                    'snapshot_name'         => $row->snapshot_name,
                    'snapshot_notes'        => $row->snapshot_notes,
                    'unregistered_at'       => $row->unregistered_at,
                    'assessment_scores'     => $scores->where('score_type', ArchiveScoreSnapshot::TYPE_ASSESSMENT)->values()->toArray(),
                    'sub_assessment_scores' => $scores->where('score_type', ArchiveScoreSnapshot::TYPE_SUB_ASSESSMENT)->values()->toArray(),
                ];
            });

            $assessmentHeaders = collect();
            $firstScores = $scoreSnapshots->first()?->groupBy('assessment_id') ?? collect();
            foreach ($firstScores as $assessmentId => $group) {
                $first = $group->first();
                if ($first->score_type === ArchiveScoreSnapshot::TYPE_ASSESSMENT) {
                    $assessmentHeaders->push([
                        'assessment_id'   => $assessmentId,
                        'assessment_name' => $first->assessment_name,
                    ]);
                }
            }

            return response()->json([
                'success'            => true,
                'rows'               => $rows,
                'assessment_headers' => $assessmentHeaders->values(),
                'snapshot_name'      => $archives->first()->snapshot_name,
                'snapshot_notes'     => $archives->first()->snapshot_notes,
                'total_students'     => $archives->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching snapshot detail', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // RESTORE REGISTRATION (WITH POSITION RECALCULATION)
    // =========================================================================

    public function restoreRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archive_ids'   => ['required', 'array'],
            'archive_ids.*' => ['required', 'integer', 'exists:subject_unregistration_archive,id'],
        ]);

        try {
            DB::beginTransaction();

            $archives = SubjectUnregistrationArchive::whereIn('id', $validated['archive_ids'])
                ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->get();

            if ($archives->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid archived records found.',
                ], 422);
            }

            $groups = $archives->groupBy(function ($row) {
                return $row->subjectclassid . '_' . $row->termid . '_' . $row->sessionid . '_' . $row->staffid;
            });

            $totalRestored = 0;
            $errors        = [];
            $recalcParams = [];

            foreach ($groups as $groupArchives) {
                $first      = $groupArchives->first();
                $studentIds = $groupArchives->pluck('studentid')->unique()->toArray();

                // Store parameters for recalculation
                $recalcParams[] = [
                    'schoolclass_id' => $first->schoolclassid,
                    'session_id'     => $first->sessionid,
                    'term_id'        => $first->termid,
                ];

                $result = $this->processIndividually([
                    'studentid'      => $studentIds,
                    'subjectclassid' => $first->subjectclassid,
                    'staffid'        => $first->staffid,
                    'termid'         => $first->termid,
                    'sessionid'      => $first->sessionid,
                ]);

                if ($result['success'] || ($result['skipped_count'] ?? 0) > 0) {
                    $this->restoreScoresFromSnapshot($groupArchives, $first);
                    SubjectUnregistrationArchive::whereIn('id', $groupArchives->pluck('id')->toArray())
                        ->update([
                            'status'      => SubjectUnregistrationArchive::STATUS_RESTORED,
                            'actioned_at' => now(),
                            'updated_at'  => now(),
                        ]);
                    $totalRestored += $result['success_count'] ?? 0;
                } else {
                    $errors[] = [
                        'subjectclassid' => $first->subjectclassid,
                        'termid'         => $first->termid,
                        'message'        => $result['message'] ?? 'Unknown error',
                    ];
                }
            }

            DB::commit();

            // ── AFTER COMMIT, RECALCULATE POSITIONS ──
            // Use unique parameters to avoid duplicate recalculations
            $uniqueRecalcParams = [];
            foreach ($recalcParams as $param) {
                $key = $param['schoolclass_id'] . '_' . $param['session_id'] . '_' . $param['term_id'];
                $uniqueRecalcParams[$key] = $param;
            }

            foreach ($uniqueRecalcParams as $param) {
                $this->recalculatePositions(
                    $param['schoolclass_id'],
                    $param['session_id'],
                    $param['term_id']
                );
            }

            return response()->json([
                'success'        => empty($errors),
                'message'        => "Successfully restored {$totalRestored} registration(s).",
                'total_restored' => $totalRestored,
                'errors'         => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // PERMANENTLY DELETE SINGLE
    // =========================================================================

    public function permanentlyDeleteArchive(Request $request, int $archiveId): JsonResponse
    {
        try {
            $archive = SubjectUnregistrationArchive::where('id', $archiveId)
                ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->firstOrFail();

            $archive->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archive record permanently deleted.',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Record not found or already actioned.'], 404);
        } catch (\Exception $e) {
            Log::error('Permanent delete failed', ['archive_id' => $archiveId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PERMANENTLY DELETE BATCH
    // =========================================================================

    public function permanentlyDeleteArchiveBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archive_ids'   => ['required', 'array'],
            'archive_ids.*' => ['required', 'integer'],
        ]);

        try {
            $deleted = SubjectUnregistrationArchive::whereIn('id', $validated['archive_ids'])
                ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} archive record(s) permanently deleted.",
                'deleted' => $deleted,
            ]);

        } catch (\Exception $e) {
            Log::error('Batch permanent delete failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    private function captureScoreSnapshots($createdArchives, array $studentsToProcess, array $broadsheetRecordIds, int $subjectclassid, int $subjectId, int $schoolclassId, int $sessionid, int $termid, int $staffid, $now): void
    {
        try {
            if ($createdArchives->isEmpty() || empty($broadsheetRecordIds)) {
                return;
            }

            $recordToStudent = SubjectRegistrationStatus::whereIn('broadsheetid', $broadsheetRecordIds)
                ->where('subjectclassid', $subjectclassid)
                ->where('termid', $termid)
                ->where('sessionid', $sessionid)
                ->pluck('studentid', 'broadsheetid');

            $broadsheets = Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                ->where('term_id', $termid)
                ->where('subjectclass_id', $subjectclassid)
                ->get()
                ->keyBy('broadsheet_record_id');

            if ($broadsheets->isEmpty()) {
                return;
            }

            $broadsheetIds = $broadsheets->pluck('id');

            $assessmentScores = BroadsheetAssessmentScore::whereIn('broadsheet_id', $broadsheetIds)
                ->join('assessments', 'assessments.id', '=', 'broadsheet_assessment_scores.assessment_id')
                ->select([
                    'broadsheet_assessment_scores.broadsheet_id',
                    'broadsheet_assessment_scores.assessment_id',
                    'broadsheet_assessment_scores.score',
                    'assessments.name as assessment_name',
                ])
                ->get()
                ->groupBy('broadsheet_id');

            $subAssessmentScores = BroadsheetSubAssessmentScore::whereIn('broadsheet_id', $broadsheetIds)
                ->join('sub_assessments', 'sub_assessments.id', '=', 'broadsheet_sub_assessment_scores.sub_assessment_id')
                ->select([
                    'broadsheet_sub_assessment_scores.broadsheet_id',
                    'broadsheet_sub_assessment_scores.assessment_id',
                    'broadsheet_sub_assessment_scores.sub_assessment_id',
                    'broadsheet_sub_assessment_scores.score',
                    'sub_assessments.name as sub_assessment_name',
                ])
                ->get()
                ->groupBy('broadsheet_id');

            $snapshots = [];

            foreach ($broadsheetRecordIds as $broadsheetRecordId) {
                $broadsheet = $broadsheets->get($broadsheetRecordId);
                if (!$broadsheet) {
                    continue;
                }

                $broadsheetId = $broadsheet->id;
                $studentId    = $recordToStudent->get($broadsheetRecordId);

                if (!$studentId) {
                    continue;
                }

                $archive = $createdArchives->get($studentId);
                if (!$archive) {
                    continue;
                }

                foreach (($assessmentScores->get($broadsheetId) ?? []) as $score) {
                    $snapshots[] = [
                        'archive_id'          => $archive->id,
                        'broadsheet_id'       => $broadsheetId,
                        'student_id'          => $studentId,
                        'subject_id'          => $subjectId,
                        'schoolclass_id'      => $schoolclassId,
                        'session_id'          => $sessionid,
                        'term_id'             => $termid,
                        'subjectclass_id'     => $subjectclassid,
                        'staff_id'            => $staffid,
                        'assessment_id'       => $score->assessment_id,
                        'assessment_name'     => $score->assessment_name,
                        'sub_assessment_id'   => null,
                        'sub_assessment_name' => null,
                        'score'               => $score->score,
                        'score_type'          => ArchiveScoreSnapshot::TYPE_ASSESSMENT,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }

                foreach (($subAssessmentScores->get($broadsheetId) ?? []) as $score) {
                    $snapshots[] = [
                        'archive_id'          => $archive->id,
                        'broadsheet_id'       => $broadsheetId,
                        'student_id'          => $studentId,
                        'subject_id'          => $subjectId,
                        'schoolclass_id'      => $schoolclassId,
                        'session_id'          => $sessionid,
                        'term_id'             => $termid,
                        'subjectclass_id'     => $subjectclassid,
                        'staff_id'            => $staffid,
                        'assessment_id'       => $score->assessment_id,
                        'assessment_name'     => null,
                        'sub_assessment_id'   => $score->sub_assessment_id,
                        'sub_assessment_name' => $score->sub_assessment_name,
                        'score'               => $score->score,
                        'score_type'          => ArchiveScoreSnapshot::TYPE_SUB_ASSESSMENT,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }
            }

            foreach (array_chunk($snapshots, 500) as $chunk) {
                ArchiveScoreSnapshot::insertOrIgnore($chunk);
            }

            Log::info('Score snapshots captured', [
                'archive_ids'    => $createdArchives->pluck('id')->toArray(),
                'snapshot_count' => count($snapshots),
            ]);

        } catch (\Exception $e) {
            Log::error('captureScoreSnapshots failed', ['error' => $e->getMessage()]);
        }
    }

    private function restoreScoresFromSnapshot($groupArchives, $first): void
    {
        try {
            $archiveIds = $groupArchives->pluck('id')->toArray();

            $snapshots = ArchiveScoreSnapshot::whereIn('archive_id', $archiveIds)->get();
            if ($snapshots->isEmpty()) {
                return;
            }

            $studentIds = $groupArchives->pluck('studentid')->toArray();

            $registrations = SubjectRegistrationStatus::whereIn('studentid', $studentIds)
                ->where('subjectclassid', $first->subjectclassid)
                ->where('termid', $first->termid)
                ->where('sessionid', $first->sessionid)
                ->where('staffid', $first->staffid)
                ->pluck('broadsheetid', 'studentid');

            $broadsheetRecordIds = $registrations->values()->toArray();
            $broadsheets = Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                ->where('term_id', $first->termid)
                ->where('subjectclass_id', $first->subjectclassid)
                ->pluck('id', 'broadsheet_record_id');

            $archiveToBroadsheetId = [];
            foreach ($groupArchives as $archive) {
                $broadsheetRecordId = $registrations->get($archive->studentid);
                if (!$broadsheetRecordId) {
                    continue;
                }
                $broadsheetId = $broadsheets->get($broadsheetRecordId);
                if (!$broadsheetId) {
                    continue;
                }
                $archiveToBroadsheetId[$archive->id] = $broadsheetId;
            }

            $assessmentSnapshots = $snapshots->where('score_type', ArchiveScoreSnapshot::TYPE_ASSESSMENT);
            foreach ($assessmentSnapshots as $snap) {
                $broadsheetId = $archiveToBroadsheetId[$snap->archive_id] ?? null;
                if (!$broadsheetId) {
                    continue;
                }
                BroadsheetAssessmentScore::where([
                    'broadsheet_id' => $broadsheetId,
                    'assessment_id' => $snap->assessment_id,
                ])->update(['score' => $snap->score]);
            }

            $subSnapshots = $snapshots->where('score_type', ArchiveScoreSnapshot::TYPE_SUB_ASSESSMENT);
            foreach ($subSnapshots as $snap) {
                $broadsheetId = $archiveToBroadsheetId[$snap->archive_id] ?? null;
                if (!$broadsheetId) {
                    continue;
                }
                BroadsheetSubAssessmentScore::where([
                    'broadsheet_id'     => $broadsheetId,
                    'sub_assessment_id' => $snap->sub_assessment_id,
                    'assessment_id'     => $snap->assessment_id,
                ])->update(['score' => $snap->score]);
            }

            Log::info('Scores restored from snapshot', [
                'archive_ids'    => $archiveIds,
                'snapshot_count' => $snapshots->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('restoreScoresFromSnapshot failed', ['error' => $e->getMessage()]);
        }
    }

    private function processIndividually(array $validated): array
    {
        $results      = [];
        $successCount = 0;
        $errors       = [];
        $skippedCount = 0;

        try {
            DB::beginTransaction();

            $subjectclass  = Subjectclass::findOrFail($validated['subjectclassid']);
            $subjectId     = $subjectclass->subjectid;
            $schoolclassId = $subjectclass->schoolclassid;

            $existingRegistrations = SubjectRegistrationStatus::where([
                'subjectclassid' => $validated['subjectclassid'],
                'termid'         => $validated['termid'],
                'sessionid'      => $validated['sessionid'],
            ])->whereIn('studentid', $validated['studentid'])
                ->pluck('studentid')
                ->toArray();

            $studentsToProcess = array_diff($validated['studentid'], $existingRegistrations);
            $skippedCount      = count($existingRegistrations);

            foreach ($existingRegistrations as $existingStudentId) {
                $errors[] = "Student ID {$existingStudentId} is already registered";
            }

            if (empty($studentsToProcess)) {
                DB::rollBack();
                return [
                    'success'       => false,
                    'message'       => 'All students are already registered for this subject.',
                    'errors'        => $errors,
                    'skipped_count' => $skippedCount,
                    'success_count' => 0,
                ];
            }

            foreach ($studentsToProcess as $studentId) {
                try {
                    $record = BroadsheetRecord::firstOrCreate([
                        'student_id'     => $studentId,
                        'subject_id'     => $subjectId,
                        'schoolclass_id' => $schoolclassId,
                        'session_id'     => $validated['sessionid'],
                    ]);

                    $recordmock = BroadsheetRecordMock::firstOrCreate([
                        'student_id'     => $studentId,
                        'subject_id'     => $subjectId,
                        'schoolclass_id' => $schoolclassId,
                        'session_id'     => $validated['sessionid'],
                    ]);

                    $this->createDependentRecords($record->id, $recordmock->id, $studentId, $validated);
                    $successCount++;
                    $results[] = "Successfully registered student ID {$studentId}";

                } catch (\Exception $e) {
                    Log::error("Error processing student {$studentId}", ['error' => $e->getMessage()]);
                    $errors[] = "Failed to register student ID {$studentId}: " . $e->getMessage();
                }
            }

            if ($successCount > 0) {
                DB::commit();
                return [
                    'success'       => true,
                    'message'       => "{$successCount} students registered successfully",
                    'method'        => 'individual',
                    'results'       => $results,
                    'errors'        => $errors,
                    'success_count' => $successCount,
                    'skipped_count' => $skippedCount,
                ];
            }

            DB::rollBack();
            return [
                'success'       => false,
                'message'       => 'No students were registered.',
                'errors'        => $errors,
                'skipped_count' => $skippedCount,
                'success_count' => 0,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Individual processing error', ['error' => $e->getMessage()]);
            return [
                'success'       => false,
                'message'       => 'Processing failed: ' . $e->getMessage(),
                'errors'        => [$e->getMessage()],
                'success_count' => 0,
            ];
        }
    }

    private function processBatch(array $validated): array
    {
        try {
            DB::beginTransaction();

            $subjectclass  = Subjectclass::findOrFail($validated['subjectclassid']);
            $subjectId     = $subjectclass->subjectid;
            $schoolclassId = $subjectclass->schoolclassid;
            $now           = now();

            $existingRegistrations = SubjectRegistrationStatus::where([
                'subjectclassid' => $validated['subjectclassid'],
                'termid'         => $validated['termid'],
                'sessionid'      => $validated['sessionid'],
            ])->whereIn('studentid', $validated['studentid'])
                ->pluck('studentid')
                ->toArray();

            $studentsToProcess = array_diff($validated['studentid'], $existingRegistrations);
            $skippedCount      = count($existingRegistrations);

            if (empty($studentsToProcess)) {
                DB::rollBack();
                return [
                    'success'       => false,
                    'message'       => 'All students are already registered.',
                    'skipped_count' => $skippedCount,
                    'success_count' => 0,
                ];
            }

            $broadsheetRecords     = [];
            $broadsheetRecordsMock = [];

            foreach ($studentsToProcess as $studentId) {
                $broadsheetRecords[]     = ['student_id' => $studentId, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
                $broadsheetRecordsMock[] = ['student_id' => $studentId, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
            }

            BroadsheetRecord::insertOrIgnore($broadsheetRecords);
            BroadsheetRecordMock::insertOrIgnore($broadsheetRecordsMock);

            $createdRecords     = BroadsheetRecord::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $studentsToProcess)->get()->keyBy('student_id');
            $createdRecordsMock = BroadsheetRecordMock::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $studentsToProcess)->get()->keyBy('student_id');

            $this->bulkCreateDependentRecords($createdRecords, $createdRecordsMock, $studentsToProcess, $validated, $now);

            DB::commit();

            return [
                'success'       => true,
                'message'       => count($studentsToProcess) . ' students registered',
                'method'        => 'batch',
                'success_count' => count($studentsToProcess),
                'skipped_count' => $skippedCount,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success'       => false,
                'message'       => 'Batch processing failed: ' . $e->getMessage(),
                'errors'        => [$e->getMessage()],
                'success_count' => 0,
            ];
        }
    }

    private function processLargeDataset(array $validated): array
    {
        try {
            DB::beginTransaction();

            $subjectclass   = Subjectclass::findOrFail($validated['subjectclassid']);
            $subjectId      = $subjectclass->subjectid;
            $schoolclassId  = $subjectclass->schoolclassid;
            $chunkSize      = 200;
            $totalProcessed = 0;
            $totalSkipped   = 0;
            $chunks         = array_chunk($validated['studentid'], $chunkSize);

            foreach ($chunks as $studentChunk) {
                $existingInChunk = SubjectRegistrationStatus::where([
                    'subjectclassid' => $validated['subjectclassid'],
                    'termid'         => $validated['termid'],
                    'sessionid'      => $validated['sessionid'],
                ])->whereIn('studentid', $studentChunk)->pluck('studentid')->toArray();

                $studentsToProcess = array_diff($studentChunk, $existingInChunk);
                $totalSkipped     += count($existingInChunk);

                if (!empty($studentsToProcess)) {
                    $this->processChunk($studentsToProcess, $validated, $subjectId, $schoolclassId);
                    $totalProcessed += count($studentsToProcess);
                }
            }

            DB::commit();
            return [
                'success'       => true,
                'message'       => "{$totalProcessed} students registered",
                'method'        => 'large_dataset',
                'success_count' => $totalProcessed,
                'skipped_count' => $totalSkipped,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success'       => false,
                'message'       => 'Large dataset processing failed: ' . $e->getMessage(),
                'errors'        => [$e->getMessage()],
                'success_count' => 0,
            ];
        }
    }

    private function processChunk(array $students, array $validated, int $subjectId, int $schoolclassId): void
    {
        $now = now();

        $broadsheetRecords     = [];
        $broadsheetRecordsMock = [];
        foreach ($students as $studentId) {
            $broadsheetRecords[]     = ['student_id' => $studentId, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
            $broadsheetRecordsMock[] = ['student_id' => $studentId, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
        }

        BroadsheetRecord::insertOrIgnore($broadsheetRecords);
        BroadsheetRecordMock::insertOrIgnore($broadsheetRecordsMock);

        $createdRecords     = BroadsheetRecord::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $students)->get()->keyBy('student_id');
        $createdRecordsMock = BroadsheetRecordMock::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $students)->get()->keyBy('student_id');

        $this->bulkCreateDependentRecords($createdRecords, $createdRecordsMock, $students, $validated, $now);
    }

    private function createDependentRecords(int $recordId, int $recordMockId, int $studentId, array $validated): void
    {
        $broadsheet = Broadsheets::firstOrCreate([
            'broadsheet_record_id' => $recordId,
            'term_id'              => $validated['termid'],
            'subjectclass_id'      => $validated['subjectclassid'],
        ], ['staff_id' => $validated['staffid']]);

        BroadsheetsMock::firstOrCreate([
            'broadsheet_records_mock_id' => $recordMockId,
            'term_id'                    => $validated['termid'],
            'subjectclass_id'            => $validated['subjectclassid'],
        ], ['staff_id' => $validated['staffid']]);

        SubjectRegistrationStatus::firstOrCreate([
            'studentid'      => $studentId,
            'subjectclassid' => $validated['subjectclassid'],
            'termid'         => $validated['termid'],
            'sessionid'      => $validated['sessionid'],
            'staffid'        => $validated['staffid'],
        ], ['broadsheetid' => $recordId, 'Status' => 1]);

        StudentSubjectRecord::firstOrCreate([
            'studentId'      => $studentId,
            'subjectclassid' => $validated['subjectclassid'],
            'staffid'        => $validated['staffid'],
            'session'        => $validated['sessionid'],
        ]);

        $this->createAssessmentScores($broadsheet->id, $validated['subjectclassid']);
    }

    private function createAssessmentScores(int $broadsheetId, int $subjectclassId): void
    {
        try {
            $subjectclass = Subjectclass::with(['schoolClass.classcategories'])->find($subjectclassId);
            if (!$subjectclass || !$subjectclass->schoolClass) return;

            $categoryIds = $subjectclass->schoolClass->classcategories->pluck('id');
            if ($categoryIds->isEmpty()) return;

            $assessments = DB::table('assessments')->whereIn('classcategory_id', $categoryIds)->distinct()->get(['id', 'name', 'classcategory_id']);
            if ($assessments->isEmpty()) return;

            foreach ($assessments as $assessment) {
                BroadsheetAssessmentScore::firstOrCreate(
                    ['broadsheet_id' => $broadsheetId, 'assessment_id' => $assessment->id],
                    ['score' => 0.00]
                );

                $subAssessments = DB::table('sub_assessments')->where('assessment_id', $assessment->id)->pluck('id');
                foreach ($subAssessments as $subAssessmentId) {
                    BroadsheetSubAssessmentScore::firstOrCreate(
                        ['broadsheet_id' => $broadsheetId, 'sub_assessment_id' => $subAssessmentId, 'assessment_id' => $assessment->id],
                        ['score' => 0.00]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to create assessment scores', ['broadsheet_id' => $broadsheetId, 'error' => $e->getMessage()]);
        }
    }

    private function bulkCreateDependentRecords($createdRecords, $createdRecordsMock, array $students, array $validated, $now): void
    {
        $broadsheets           = [];
        $broadsheetsMock       = [];
        $subjectRegistrations  = [];
        $studentSubjectRecords = [];

        foreach ($students as $studentId) {
            $record     = $createdRecords->get($studentId);
            $recordMock = $createdRecordsMock->get($studentId);
            if (!$record || !$recordMock) continue;

            $broadsheets[]           = ['broadsheet_record_id' => $record->id, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid'], 'staff_id' => $validated['staffid'], 'created_at' => $now, 'updated_at' => $now];
            $broadsheetsMock[]       = ['broadsheet_records_mock_id' => $recordMock->id, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid'], 'staff_id' => $validated['staffid'], 'created_at' => $now, 'updated_at' => $now];
            $subjectRegistrations[]  = ['studentid' => $studentId, 'subjectclassid' => $validated['subjectclassid'], 'staffid' => $validated['staffid'], 'termid' => $validated['termid'], 'sessionid' => $validated['sessionid'], 'broadsheetid' => $record->id, 'Status' => 1, 'created_at' => $now, 'updated_at' => $now];
            $studentSubjectRecords[] = ['studentId' => $studentId, 'subjectclassid' => $validated['subjectclassid'], 'staffid' => $validated['staffid'], 'session' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
        }

        if (!empty($broadsheets))           Broadsheets::insertOrIgnore($broadsheets);
        if (!empty($broadsheetsMock))       BroadsheetsMock::insertOrIgnore($broadsheetsMock);
        if (!empty($subjectRegistrations))  SubjectRegistrationStatus::insertOrIgnore($subjectRegistrations);
        if (!empty($studentSubjectRecords)) StudentSubjectRecord::insertOrIgnore($studentSubjectRecords);

        $recordIds = collect($students)->map(fn($sid) => $createdRecords->get($sid)?->id)->filter()->toArray();
        if (empty($recordIds)) return;

        $createdBroadsheets = Broadsheets::whereIn('broadsheet_record_id', $recordIds)
            ->where('term_id', $validated['termid'])
            ->where('subjectclass_id', $validated['subjectclassid'])
            ->get();

        $this->bulkCreateAssessmentScoresForBroadsheets($createdBroadsheets, $validated['subjectclassid'], $now);
    }

    private function bulkCreateAssessmentScoresForBroadsheets($broadsheets, int $subjectclassId, $now): void
    {
        try {
            if ($broadsheets->isEmpty()) return;

            $subjectclass = Subjectclass::with(['schoolClass.classcategories'])->find($subjectclassId);
            if (!$subjectclass || !$subjectclass->schoolClass) return;

            $categoryIds = $subjectclass->schoolClass->classcategories->pluck('id');
            if ($categoryIds->isEmpty()) return;

            $assessments = DB::table('assessments')->whereIn('classcategory_id', $categoryIds)->distinct()->get(['id']);
            if ($assessments->isEmpty()) return;

            $assessmentScores    = [];
            $subAssessmentScores = [];

            foreach ($broadsheets as $broadsheet) {
                foreach ($assessments as $assessment) {
                    $assessmentScores[] = ['broadsheet_id' => $broadsheet->id, 'assessment_id' => $assessment->id, 'score' => 0.00, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if (!empty($assessmentScores)) BroadsheetAssessmentScore::insertOrIgnore($assessmentScores);

            $assessmentIds  = $assessments->pluck('id')->toArray();
            $subAssessments = DB::table('sub_assessments')->whereIn('assessment_id', $assessmentIds)->get(['id', 'assessment_id']);

            foreach ($broadsheets as $broadsheet) {
                foreach ($subAssessments as $subAssessment) {
                    $subAssessmentScores[] = ['broadsheet_id' => $broadsheet->id, 'sub_assessment_id' => $subAssessment->id, 'assessment_id' => $subAssessment->assessment_id, 'score' => 0.00, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if (!empty($subAssessmentScores)) BroadsheetSubAssessmentScore::insertOrIgnore($subAssessmentScores);

        } catch (\Exception $e) {
            Log::error('Failed to bulk create assessment scores', ['error' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // RECALCULATE POSITIONS AFTER RESTORE
    // =========================================================================

    /**
     * Recalculate positions after restoring registrations or scores
     */
    protected function recalculatePositions($schoolclassId, $sessionId, $termId)
    {
        try {
            $controller = app(\App\Http\Controllers\ViewStudentReportController::class);
            $method = new \ReflectionMethod($controller, 'calculateClassPositionsAndAverages');
            $method->setAccessible(true);

            // Get all arms for this class group
            $baseClass = \App\Models\Schoolclass::find($schoolclassId);
            if ($baseClass) {
                $allArmIds = \App\Models\Schoolclass::where('schoolclass', $baseClass->schoolclass)
                    ->pluck('id');

                foreach ($allArmIds as $armId) {
                    $method->invoke($controller, $armId, $sessionId, $termId);
                }
            }

            Log::info('Position recalculation completed after restore', [
                'schoolclass_id' => $schoolclassId,
                'session_id' => $sessionId,
                'term_id' => $termId
            ]);
        } catch (\Exception $e) {
            Log::error('Position recalculation after restore failed', [
                'error' => $e->getMessage(),
                'schoolclass_id' => $schoolclassId,
                'session_id' => $sessionId,
                'term_id' => $termId
            ]);
        }
    }
}
