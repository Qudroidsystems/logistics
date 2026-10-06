<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\SubjectTeacher;
use App\Models\Schoolclass;
use App\Models\Subjectclass;
use App\Models\Broadsheets;
use App\Models\BroadsheetRecord;
use App\Models\Assessment;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetSubAssessmentScore;
use App\Models\SubAssessment;
use App\Models\PromotionStatus;
use App\Models\User;
use App\Models\Classcategory;
use App\Models\SchoolInformation;
use App\Models\ScoresheetLock;
use App\Models\Student;
use App\Models\SubjectRegistrationStatus;
use App\Exports\AdminRecordsheetExport;
use App\Imports\AdminScoresheetImport;
use App\Jobs\AutoUnlockScoresheet;
use App\Jobs\AutoUnlockGlobalLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminScoreEntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View admin-score-entry|Create admin-score-entry|Update admin-score-entry|Delete admin-score-entry')->only(['index']);
        $this->middleware('permission:Create admin-score-entry')->only(['create', 'store']);
        $this->middleware('permission:Update admin-score-entry')->only(['edit', 'update', 'bulkUpdate', 'singleUpdate', 'lockManagement', 'getScoresheetsList', 'bulkLockManagement']);
        $this->middleware('permission:Delete admin-score-entry')->only(['destroy']);
        $this->middleware('permission:Manage admin-student-results')->only(['studentResultManager', 'getStudentResults', 'updateStudentSubjectScore', 'bulkUpdateStudentScores']);
    }

    // =========================================================================
    // INDEX — list teacher subjects with enhanced stats
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Admin Score Entry - Teacher Subjects";
        $terms     = Schoolterm::orderBy('id')->get();
        $sessions  = Schoolsession::orderBy('id', 'desc')->get();

        $teacherSubjects   = collect();
        $selectedTermId    = $request->get('termid');
        $selectedSessionId = $request->get('sessionid');

        // Dashboard statistics
        $dashboardStats = [
            'total_teachers' => 0,
            'total_subjects' => 0,
            'total_classes' => 0,
            'completed_scoresheets' => 0,
            'pending_scoresheets' => 0,
            'completion_rate' => 0,
            'mock_completed' => 0,
            'mock_pending' => 0,
            'teacher_stats' => [],
            'class_stats' => [],
            'total_expected_entries' => 0,
            'total_actual_entries' => 0,
            'entry_completion_rate' => 0,
        ];

        if ($selectedTermId && $selectedSessionId) {
            $teacherSubjects = $this->getTeacherSubjects($selectedTermId, $selectedSessionId);

            // Calculate dashboard statistics
            $dashboardStats = $this->calculateDashboardStats($teacherSubjects, $selectedTermId, $selectedSessionId);
        }

        return view('admin.score-entry.index', compact(
            'pagetitle', 'terms', 'sessions', 'teacherSubjects',
            'selectedTermId', 'selectedSessionId', 'dashboardStats'
        ));
    }

    /**
     * Calculate comprehensive dashboard statistics
     */
    protected function calculateDashboardStats($teacherSubjects, $termId, $sessionId)
    {
        $stats = [
            'total_teachers' => $teacherSubjects->groupBy('teacher_id')->count(),
            'total_subjects' => $teacherSubjects->count(),
            'total_classes' => $teacherSubjects->groupBy('schoolclass_id')->count(),
            'completed_scoresheets' => 0,
            'pending_scoresheets' => 0,
            'completion_rate' => 0,
            'mock_completed' => 0,
            'mock_pending' => 0,
            'teacher_stats' => [],
            'class_stats' => [],
            'total_expected_entries' => 0,
            'total_actual_entries' => 0,
            'entry_completion_rate' => 0,
        ];

        // Get all registered students for this term/session to calculate expected entries
        $totalStudentsInTerm = DB::table('studentclass')
            ->where('sessionid', $sessionId)
            ->where('termid', $termId)
            ->distinct('studentId')
            ->count('studentId');

        // Calculate per-teacher stats
        $teacherGroups = $teacherSubjects->groupBy('teacher_id');

        foreach ($teacherGroups as $teacherId => $subjects) {
            $teacherName = $subjects->first()->teacher_name;
            $teacherStats = [
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherName,
                'subjects_count' => $subjects->count(),
                'completed_terminal' => 0,
                'pending_terminal' => 0,
                'completed_mock' => 0,
                'pending_mock' => 0,
                'completion_rate' => 0,
                'expected_entries' => 0,
                'actual_entries' => 0,
                'subjects_details' => [],
                'classes' => [],
            ];

            foreach ($subjects as $subject) {
                $studentCount = $subject->student_count;
                $expectedEntries = $studentCount;
                $actualTerminalEntries = $subject->terminal_entries_count;
                $actualMockEntries = $subject->mock_entries_count;

                if ($subject->has_terminal_scores) {
                    $teacherStats['completed_terminal']++;
                } else {
                    $teacherStats['pending_terminal']++;
                }

                if ($subject->has_mock_scores) {
                    $teacherStats['completed_mock']++;
                } else {
                    $teacherStats['pending_mock']++;
                }

                $teacherStats['expected_entries'] += $expectedEntries;
                $teacherStats['actual_entries'] += $actualTerminalEntries;

                $teacherStats['subjects_details'][] = [
                    'subject_name' => $subject->subject_name,
                    'class_name' => $subject->class_name,
                    'student_count' => $studentCount,
                    'has_terminal' => $subject->has_terminal_scores,
                    'has_mock' => $subject->has_mock_scores,
                    'terminal_entries' => $actualTerminalEntries,
                    'terminal_partial' => $subject->terminal_partial_count,
                    'mock_entries' => $actualMockEntries,
                    'expected_entries' => $expectedEntries,
                    'completion_percentage' => $subject->entry_percentage,
                    'entry_status' => $subject->entry_status,
                    'required_assessment_count' => $subject->required_assessment_count,
                ];

                $teacherStats['classes'][] = $subject->class_name;
                $stats['total_expected_entries'] += $expectedEntries;
                $stats['total_actual_entries'] += $actualTerminalEntries;
            }

            $teacherStats['classes'] = array_unique($teacherStats['classes']);
            $teacherStats['completion_rate'] = $teacherStats['subjects_count'] > 0
                ? round(($teacherStats['completed_terminal'] / $teacherStats['subjects_count']) * 100, 1)
                : 0;

            $stats['completed_scoresheets'] += $teacherStats['completed_terminal'];
            $stats['pending_scoresheets'] += $teacherStats['pending_terminal'];
            $stats['mock_completed'] += $teacherStats['completed_mock'];
            $stats['mock_pending'] += $teacherStats['pending_mock'];
            $stats['teacher_stats'][] = $teacherStats;
        }

        // Calculate class-level stats
        $classGroups = $teacherSubjects->groupBy('schoolclass_id');
        foreach ($classGroups as $classId => $subjects) {
            $className = $subjects->first()->class_name;
            $studentCount = $subjects->first()->student_count;
            $totalSubjects = $subjects->count();
            $completedSubjects = $subjects->where('entry_status', 'complete')->count();
            $classExpectedEntries = $totalSubjects * $studentCount;
            $classActualEntries = $subjects->sum('terminal_entries_count');

            $stats['class_stats'][] = [
                'class_name' => $className,
                'class_id' => $classId,
                'student_count' => $studentCount,
                'total_subjects' => $totalSubjects,
                'completed_subjects' => $completedSubjects,
                'pending_subjects' => $totalSubjects - $completedSubjects,
                'completion_rate' => $totalSubjects > 0 ? round(($completedSubjects / $totalSubjects) * 100, 1) : 0,
                'entry_completion_rate' => $classExpectedEntries > 0 ? round(($classActualEntries / $classExpectedEntries) * 100, 1) : 0,
                'subjects' => $subjects->pluck('subject_name')->toArray(),
            ];
        }

        // Sort stats by completion rate
        usort($stats['teacher_stats'], function($a, $b) {
            return $b['completion_rate'] <=> $a['completion_rate'];
        });

        usort($stats['class_stats'], function($a, $b) {
            return $b['completion_rate'] <=> $a['completion_rate'];
        });

        $stats['completion_rate'] = $stats['total_subjects'] > 0
            ? round(($stats['completed_scoresheets'] / $stats['total_subjects']) * 100, 1)
            : 0;

        $stats['entry_completion_rate'] = $stats['total_expected_entries'] > 0
            ? round(($stats['total_actual_entries'] / $stats['total_expected_entries']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get teacher subjects with enhanced assessment-level progress tracking
     */
    protected function getTeacherSubjects($termId, $sessionId)
    {
        $subjects = SubjectTeacher::query()
            ->join('users', 'users.id', '=', 'subjectteacher.staffid')
            ->join('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
            ->join('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->join('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolclass_classcategory', 'schoolclass_classcategory.schoolclass_id', '=', 'schoolclass.id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
            ->where('subjectteacher.termid', $termId)
            ->where('subjectteacher.sessionid', $sessionId)
            ->whereNotNull('subjectclass.id')
            ->select(
                'subjectteacher.id as subjectteacher_id',
                'users.id as teacher_id',
                'users.name as teacher_name',
                'subject.subject as subject_name',
                'subject.subject_code',
                'subjectclass.id as subjectclass_id',
                'schoolclass.id as schoolclass_id',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as class_name"),
                DB::raw("GROUP_CONCAT(DISTINCT classcategories.category ORDER BY classcategories.category SEPARATOR ', ') as class_categories"),
                'schoolclass.classcategoryid',
                'subjectteacher.termid',
                'subjectteacher.sessionid',
                'schoolterm.term as term_name',
                'schoolsession.session as session_name',
                'subjectclass.teacher_editing_enabled'
            )
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->groupBy(
                'subjectteacher.id', 'users.id', 'users.name', 'subject.subject',
                'subject.subject_code', 'subjectclass.id', 'schoolclass.id', 'class_name',
                'schoolclass.classcategoryid', 'subjectteacher.termid', 'subjectteacher.sessionid',
                'schoolterm.term', 'schoolsession.session', 'subjectclass.teacher_editing_enabled'
            )
            ->orderBy('users.name')
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        // Pre-compute assessment data per class category
        $categoryIds = $subjects->pluck('classcategoryid')->filter()->unique()->values();

        $assessmentsByCategory = Assessment::whereIn('classcategory_id', $categoryIds)
            ->get(['id', 'classcategory_id', 'name', 'max_score'])
            ->groupBy('classcategory_id');

        $assessmentCountsByCategory = $assessmentsByCategory->map(fn ($rows) => $rows->count());
        $assessmentIdsByCategory = $assessmentsByCategory->map(fn ($rows) => $rows->pluck('id')->toArray());
        $assessmentDetailsByCategory = $assessmentsByCategory->map(fn ($rows) => $rows->toArray());

        return $subjects->map(function ($item) use ($termId, $sessionId, $assessmentCountsByCategory, $assessmentIdsByCategory, $assessmentDetailsByCategory) {
            // Student count for this class/subject
            $studentCount = DB::table('studentclass')
                ->where('sessionid', $sessionId)
                ->where('termid', $termId)
                ->where('schoolclassid', $item->schoolclass_id)
                ->count();

            $item->student_count = $studentCount;

            $requiredAssessments = (int) ($assessmentCountsByCategory[$item->classcategoryid] ?? 0);
            $item->required_assessment_count = $requiredAssessments;
            $item->assessment_ids = $assessmentIdsByCategory[$item->classcategoryid] ?? [];
            $item->assessment_details = $assessmentDetailsByCategory[$item->classcategoryid] ?? [];

            // Get detailed assessment progress for each assessment
            $assessmentProgress = $this->getAssessmentProgress(
                $item->subjectclass_id,
                $termId,
                $sessionId,
                $item->assessment_ids
            );
            $item->assessment_progress = $assessmentProgress;

            // Terminal completion stats - now properly checking ALL assessments
            $terminalStats = $this->computeTerminalCompletionStats(
                $item->subjectclass_id, $termId, $sessionId, $requiredAssessments
            );

            $item->terminal_entries_count = $terminalStats['fully_entered'];
            $item->terminal_partial_count = $terminalStats['partially_entered'];
            $item->terminal_started_count = $terminalStats['fully_entered'] + $terminalStats['partially_entered'];
            $item->has_terminal_scores = $terminalStats['fully_entered'] > 0;

            $item->entry_percentage = $studentCount > 0
                ? round(($terminalStats['fully_entered'] / $studentCount) * 100, 1)
                : 0;

            $item->entry_status = $this->resolveEntryStatus(
                $terminalStats['fully_entered'], $item->terminal_started_count, $studentCount
            );

            // Mock completion
            $mockEnteredCount = \App\Models\BroadsheetsMock::where('subjectclass_id', $item->subjectclass_id)
                ->where('staff_id', $item->teacher_id)
                ->where('term_id', $item->termid)
                ->where('exam', '>', 0)
                ->count();

            $mockStartedCount = \App\Models\BroadsheetsMock::where('subjectclass_id', $item->subjectclass_id)
                ->where('staff_id', $item->teacher_id)
                ->where('term_id', $item->termid)
                ->count();

            $item->mock_entries_count = $mockEnteredCount;
            $item->has_mock_scores = $mockEnteredCount > 0;
            $item->mock_percentage = $studentCount > 0
                ? round(($mockEnteredCount / $studentCount) * 100, 1)
                : 0;
            $item->mock_status = $this->resolveEntryStatus($mockEnteredCount, $mockStartedCount, $studentCount);

            return $item;
        });
    }

    /**
     * Get detailed progress for each assessment
     */
    protected function getAssessmentProgress($subjectclassId, $termId, $sessionId, $assessmentIds)
    {
        if (empty($assessmentIds)) {
            return [];
        }

        $students = DB::table('studentclass')
            ->where('sessionid', $sessionId)
            ->where('termid', $termId)
            ->where('schoolclassid', function($query) use ($subjectclassId) {
                $query->select('schoolclassid')
                    ->from('subjectclass')
                    ->where('id', $subjectclassId);
            })
            ->pluck('studentId')
            ->toArray();

        $totalStudents = count($students);

        $progress = [];
        foreach ($assessmentIds as $assessmentId) {
            $assessment = Assessment::find($assessmentId);
            if (!$assessment) continue;

            // Count students who have a score > 0 for this assessment
            $scoredCount = DB::table('broadsheets')
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->join('broadsheet_assessment_scores', 'broadsheet_assessment_scores.broadsheet_id', '=', 'broadsheets.id')
                ->where('broadsheets.subjectclass_id', $subjectclassId)
                ->where('broadsheets.term_id', $termId)
                ->where('broadsheet_records.session_id', $sessionId)
                ->where('broadsheet_assessment_scores.assessment_id', $assessmentId)
                ->where('broadsheet_assessment_scores.score', '>', 0)
                ->distinct('broadsheet_records.student_id')
                ->count('broadsheet_records.student_id');

            $progress[] = [
                'assessment_id' => $assessmentId,
                'assessment_name' => $assessment->name,
                'max_score' => $assessment->max_score,
                'scored_count' => $scoredCount,
                'total_students' => $totalStudents,
                'percentage' => $totalStudents > 0 ? round(($scoredCount / $totalStudents) * 100, 1) : 0,
                'status' => $scoredCount >= $totalStudents ? 'complete' : ($scoredCount > 0 ? 'partial' : 'not_started')
            ];
        }

        return $progress;
    }

    /**
     * For a given subjectclass/term/session, count students who are:
     *  - "fully entered"     -> score > 0 against EVERY required assessment
     *  - "partially entered" -> score > 0 against at least one, but not all
     */
    protected function computeTerminalCompletionStats($subjectclassId, $termId, $sessionId, $requiredAssessments)
    {
        // Get all assessment IDs for this class category
        $assessmentIds = DB::table('assessments')
            ->join('schoolclass_classcategory', 'schoolclass_classcategory.classcategory_id', '=', 'assessments.classcategory_id')
            ->join('schoolclass', 'schoolclass.id', '=', 'schoolclass_classcategory.schoolclass_id')
            ->join('subjectclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
            ->where('subjectclass.id', $subjectclassId)
            ->pluck('assessments.id')
            ->toArray();

        if (empty($assessmentIds)) {
            // Fall back to total > 0 for legacy configurations
            $fullyEntered = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassId)
                ->where('broadsheets.term_id', $termId)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->where('broadsheet_records.session_id', $sessionId)
                ->where('broadsheets.total', '>', 0)
                ->count();

            return ['fully_entered' => $fullyEntered, 'partially_entered' => 0];
        }

        $totalAssessments = count($assessmentIds);

        // Get all students in this class
        $students = DB::table('studentclass')
            ->where('sessionid', $sessionId)
            ->where('termid', $termId)
            ->where('schoolclassid', function($query) use ($subjectclassId) {
                $query->select('schoolclassid')
                    ->from('subjectclass')
                    ->where('id', $subjectclassId);
            })
            ->pluck('studentId')
            ->toArray();

        if (empty($students)) {
            return ['fully_entered' => 0, 'partially_entered' => 0];
        }

        // For each student, count how many assessments they have scores for
        $studentScores = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->leftJoin('broadsheet_assessment_scores', function($join) use ($assessmentIds) {
                $join->on('broadsheet_assessment_scores.broadsheet_id', '=', 'broadsheets.id')
                     ->whereIn('broadsheet_assessment_scores.assessment_id', $assessmentIds)
                     ->where('broadsheet_assessment_scores.score', '>', 0);
            })
            ->where('broadsheets.subjectclass_id', $subjectclassId)
            ->where('broadsheets.term_id', $termId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->whereIn('broadsheet_records.student_id', $students)
            ->select(
                'broadsheet_records.student_id',
                DB::raw('COUNT(DISTINCT broadsheet_assessment_scores.assessment_id) as scored_count')
            )
            ->groupBy('broadsheet_records.student_id')
            ->get()
            ->keyBy('student_id');

        $fullyEntered = 0;
        $partiallyEntered = 0;

        foreach ($students as $studentId) {
            $scoredCount = isset($studentScores[$studentId]) ? (int)$studentScores[$studentId]->scored_count : 0;

            if ($scoredCount >= $totalAssessments) {
                $fullyEntered++;
            } elseif ($scoredCount > 0) {
                $partiallyEntered++;
            }
        }

        return ['fully_entered' => $fullyEntered, 'partially_entered' => $partiallyEntered];
    }

    /**
     * Resolve a 3-state status for progress badges
     */
    protected function resolveEntryStatus($fullyEntered, $startedCount, $studentCount)
    {
        if ($studentCount > 0 && $fullyEntered >= $studentCount) {
            return 'complete';
        }
        if ($startedCount > 0) {
            return 'partial';
        }
        return 'not_started';
    }

    // =========================================================================
    // LOCK MANAGEMENT PAGE
    // =========================================================================

    public function lockManagement()
    {
        $pagetitle = "Scoresheet Lock Management";
        return view('admin.score-entry.lock-management', compact('pagetitle'));
    }

    public function getScoresheetsList(Request $request)
    {
        try {
            $query = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->join('users', 'users.id', '=', 'subjectteacher.staffid')
                ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->join('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->select([
                    'subjectclass.id as subjectclass_id',
                    'users.id as teacher_id',
                    'users.name as teacher_name',
                    'subject.subject as subject_name',
                    'subject.subject_code',
                    'schoolclass.id as schoolclass_id',
                    DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as class_name"),
                    'subjectteacher.termid',
                    'subjectteacher.sessionid',
                    'schoolterm.term as term_name',
                    'schoolsession.session as session_name',
                    'subjectclass.teacher_editing_enabled',

                    DB::raw("(
                        SELECT sl.is_active
                        FROM scoresheet_locks sl
                        WHERE sl.subjectclass_id = subjectclass.id
                          AND sl.term_id    = subjectteacher.termid
                          AND sl.session_id = subjectteacher.sessionid
                          AND sl.is_active  = 1
                        LIMIT 1
                    ) as global_lock_active"),

                    DB::raw("(
                        SELECT sl.reason
                        FROM scoresheet_locks sl
                        WHERE sl.subjectclass_id = subjectclass.id
                          AND sl.term_id    = subjectteacher.termid
                          AND sl.session_id = subjectteacher.sessionid
                          AND sl.is_active  = 1
                        LIMIT 1
                    ) as global_lock_reason"),

                    DB::raw("(
                        SELECT u2.name
                        FROM scoresheet_locks sl
                        LEFT JOIN users u2 ON u2.id = sl.locked_by
                        WHERE sl.subjectclass_id = subjectclass.id
                          AND sl.term_id    = subjectteacher.termid
                          AND sl.session_id = subjectteacher.sessionid
                          AND sl.is_active  = 1
                        LIMIT 1
                    ) as global_lock_by"),

                    DB::raw("(
                        SELECT sl.locked_at
                        FROM scoresheet_locks sl
                        WHERE sl.subjectclass_id = subjectclass.id
                          AND sl.term_id    = subjectteacher.termid
                          AND sl.session_id = subjectteacher.sessionid
                          AND sl.is_active  = 1
                        LIMIT 1
                    ) as global_lock_at"),

                    DB::raw("(
                        SELECT COUNT(*)
                        FROM broadsheets b
                        WHERE b.subjectclass_id = subjectclass.id
                          AND b.is_locked = 1
                    ) as individually_locked_count"),

                    DB::raw("(
                        SELECT COUNT(*)
                        FROM broadsheets b
                        WHERE b.subjectclass_id = subjectclass.id
                    ) as total_students"),
                ]);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'LIKE', "%{$search}%")
                      ->orWhere('subject.subject', 'LIKE', "%{$search}%")
                      ->orWhere('subject.subject_code', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('term_id')) {
                $query->where('subjectteacher.termid', $request->term_id);
            }

            if ($request->filled('session_id')) {
                $query->where('subjectteacher.sessionid', $request->session_id);
            }

            if ($request->filled('class_id')) {
                $query->where('schoolclass.id', $request->class_id);
            }

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'open':
                        $query->where('subjectclass.teacher_editing_enabled', 1)
                              ->whereRaw("(
                                  SELECT COUNT(*) FROM scoresheet_locks sl
                                  WHERE sl.subjectclass_id = subjectclass.id
                                    AND sl.term_id    = subjectteacher.termid
                                    AND sl.session_id = subjectteacher.sessionid
                                    AND sl.is_active  = 1
                              ) = 0")
                              ->whereRaw("(
                                  SELECT COUNT(*) FROM broadsheets b
                                  WHERE b.subjectclass_id = subjectclass.id AND b.is_locked = 1
                              ) = 0");
                        break;
                    case 'individual':
                        $query->whereRaw("(
                                  SELECT COUNT(*) FROM broadsheets b
                                  WHERE b.subjectclass_id = subjectclass.id AND b.is_locked = 1
                              ) > 0")
                              ->whereRaw("(
                                  SELECT COUNT(*) FROM scoresheet_locks sl
                                  WHERE sl.subjectclass_id = subjectclass.id
                                    AND sl.term_id    = subjectteacher.termid
                                    AND sl.session_id = subjectteacher.sessionid
                                    AND sl.is_active  = 1
                              ) = 0");
                        break;
                    case 'global':
                        $query->whereRaw("(
                                  SELECT COUNT(*) FROM scoresheet_locks sl
                                  WHERE sl.subjectclass_id = subjectclass.id
                                    AND sl.term_id    = subjectteacher.termid
                                    AND sl.session_id = subjectteacher.sessionid
                                    AND sl.is_active  = 1
                              ) > 0");
                        break;
                    case 'disabled':
                        $query->where('subjectclass.teacher_editing_enabled', 0);
                        break;
                }
            }

            $results = $query
                ->groupBy(
                    'subjectclass.id', 'users.id', 'users.name',
                    'subject.subject', 'subject.subject_code',
                    'schoolclass.id', 'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'subjectteacher.termid', 'subjectteacher.sessionid',
                    'schoolterm.term', 'schoolsession.session',
                    'subjectclass.teacher_editing_enabled'
                )
                ->orderBy('users.name')
                ->orderBy('subject.subject')
                ->get();

            $terms    = Schoolterm::select('id', 'term')->get();
            $sessions = Schoolsession::select('id', 'session')->orderBy('id', 'desc')->get();
            $classes  = DB::table('schoolclass')
                            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm')
                            ->get();

            return response()->json([
                'success' => true,
                'data'    => $results,
                'filters' => [
                    'terms'    => $terms,
                    'sessions' => $sessions,
                    'classes'  => $classes->map(fn ($cls) => (object)[
                        'id'         => $cls->id,
                        'schoolclass'=> $cls->schoolclass,
                        'arm'        => $cls->arm ? (object)['arm' => $cls->arm] : null,
                    ]),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get scoresheets list error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkLockManagement(Request $request)
    {
        $request->validate([
            'action'                => 'required|in:lock_individual,unlock_individual,lock_global,unlock_global,disable_editing,enable_editing',
            'subjectclass_ids'      => 'required|array',
            'subjectclass_ids.*'    => 'exists:subjectclass,id',
            'reason'                => 'nullable|string|max:500',
            'term_id'               => 'required_if:action,lock_global,unlock_global|nullable|exists:schoolterm,id',
            'session_id'            => 'required_if:action,lock_global,unlock_global|nullable|exists:schoolsession,id',
        ]);

        $action          = $request->action;
        $subjectclassIds = $request->subjectclass_ids;
        $reason          = $request->reason;
        $userId          = auth()->id();

        DB::beginTransaction();
        try {
            $results      = [];
            $lockedCount  = 0;
            $unlockedCount = 0;

            foreach ($subjectclassIds as $subjectclassId) {
                $subjectClass = Subjectclass::with('subject')->find($subjectclassId);
                if (!$subjectClass) continue;

                switch ($action) {
                    case 'lock_individual':
                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->where('is_locked', false)
                            ->update([
                                'is_locked'   => true,
                                'locked_by'   => $userId,
                                'locked_at'   => now(),
                                'lock_reason' => $reason ?: 'Locked by admin',
                            ]);
                        $lockedCount += $count;
                        $results[] = "Locked {$count} scoresheets for {$subjectClass->subject->subject}";
                        break;

                    case 'unlock_individual':
                        $globalLock = ScoresheetLock::where([
                            'subjectclass_id' => $subjectclassId,
                            'term_id'         => $request->term_id,
                            'session_id'      => $request->session_id,
                            'is_active'       => true,
                        ])->first();

                        if ($globalLock) {
                            $results[] = "Cannot unlock {$subjectClass->subject->subject}: Global lock active.";
                            continue 2;
                        }

                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->where('is_locked', '=', 1)
                            ->update([
                                'is_locked'           => false,
                                'locked_by'           => null,
                                'locked_at'           => null,
                                'lock_reason'         => null,
                                'scheduled_unlock_at' => null,
                            ]);
                        $unlockedCount += $count;
                        $results[] = "Unlocked {$count} scoresheets for {$subjectClass->subject->subject}";
                        break;

                    case 'lock_global':
                        ScoresheetLock::updateOrCreate(
                            ['subjectclass_id' => $subjectclassId, 'term_id' => $request->term_id, 'session_id' => $request->session_id],
                            ['is_active' => true, 'locked_by' => $userId, 'locked_at' => now(), 'reason' => $reason ?: 'Global lock applied by admin']
                        );
                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->update(['is_locked' => true, 'locked_by' => $userId, 'locked_at' => now(), 'lock_reason' => $reason ?: 'Global lock applied']);
                        $lockedCount += $count;
                        $results[] = "Global lock applied to {$subjectClass->subject->subject} - Locked {$count} scoresheets";
                        break;

                    case 'unlock_global':
                        ScoresheetLock::where(['subjectclass_id' => $subjectclassId, 'term_id' => $request->term_id, 'session_id' => $request->session_id])
                            ->update(['is_active' => false]);
                        $results[] = "Global lock removed from {$subjectClass->subject->subject}";
                        break;

                    case 'disable_editing':
                        $subjectClass->teacher_editing_enabled           = false;
                        $subjectClass->teacher_editing_disabled_at       = now();
                        $subjectClass->teacher_editing_disabled_by       = $userId;
                        $subjectClass->save();
                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->update(['is_locked' => true, 'locked_by' => $userId, 'locked_at' => now(), 'lock_reason' => $reason ?: 'Teacher editing disabled by admin']);
                        $lockedCount += $count;
                        $results[] = "Teacher editing disabled for {$subjectClass->subject->subject} - Locked {$count} scoresheets";
                        break;

                    case 'enable_editing':
                        $subjectClass->teacher_editing_enabled           = true;
                        $subjectClass->teacher_editing_disabled_at       = null;
                        $subjectClass->teacher_editing_disabled_by       = null;
                        $subjectClass->save();
                        $results[] = "Teacher editing enabled for {$subjectClass->subject->subject}";
                        break;
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => implode("\n", $results),
                'data'    => ['results' => $results, 'locked_count' => $lockedCount, 'unlocked_count' => $unlockedCount],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk lock management error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SCORESHEET — terminal / mock
    // =========================================================================

    public function showScoresheet($subjectclassId, $teacherId, $termId, $sessionId, $type = 'terminal')
    {
        session([
            'admin_score_entry_subjectclass_id' => $subjectclassId,
            'admin_score_entry_teacher_id'      => $teacherId,
            'admin_score_entry_term_id'         => $termId,
            'admin_score_entry_session_id'      => $sessionId,
            'admin_score_entry_type'            => $type,
            'schoolclass_id'                    => null,
            'subjectclass_id'                   => $subjectclassId,
            'staff_id'                          => $teacherId,
            'term_id'                           => $termId,
            'session_id'                        => $sessionId,
        ]);

        if ($type === 'mock') {
            return $this->showMockScoresheet($subjectclassId, $teacherId, $termId, $sessionId);
        }

        $subjectClass = Subjectclass::with(['subject', 'schoolclass.arm', 'schoolclass.classcategories'])
            ->findOrFail($subjectclassId);

        $teacher    = User::findOrFail($teacherId);
        $term       = Schoolterm::findOrFail($termId);
        $session    = Schoolsession::findOrFail($sessionId);
        $schoolclass = $subjectClass->schoolclass;

        session(['schoolclass_id' => $schoolclass->id ?? null]);

        $broadsheets = $this->getBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);
        $assessments = collect();

        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')->orderBy('id')->get();

            $this->updateClassMetrics($subjectclassId, $teacherId, $termId, $sessionId);
            $this->computeDynamicTotals($broadsheets, $assessments, $schoolclass, $termId, $sessionId);
            $this->updateSubjectPositions($subjectclassId, $teacherId, $termId, $sessionId);
            $this->updateClassPositions($schoolclass->id, $termId, $sessionId);

            $broadsheets = $this->getBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);
            $this->computeOverallGPAAndCGPA($broadsheets, $schoolclass, $termId, $sessionId);
        }

        $pagetitle = sprintf(
            'Admin: %s – %s (%s) | %s %s | %s %s',
            $teacher->name,
            $subjectClass->subject->subject,
            $subjectClass->subject->subject_code,
            $schoolclass->schoolclass,
            $schoolclass->arm->arm ?? '',
            $term->term,
            $session->session
        );

        $is_senior = $schoolclass && $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()->is_senior ?? false
            : false;

        $globalLock = ScoresheetLock::where([
            'subjectclass_id' => $subjectclassId,
            'term_id'         => $termId,
            'session_id'      => $sessionId,
            'is_active'       => true,
        ])->first();

        $lockedCount           = $broadsheets->where('is_locked', true)->count();
        $teacherEditingEnabled = $subjectClass->teacher_editing_enabled;

        return view('admin.score-entry.scoresheet', compact(
            'broadsheets', 'pagetitle', 'is_senior', 'assessments',
            'subjectclassId', 'teacherId', 'termId', 'sessionId',
            'teacher', 'subjectClass', 'term', 'session', 'schoolclass',
            'globalLock', 'lockedCount', 'teacherEditingEnabled'
        ));
    }

    public function showMockScoresheet($subjectclassId, $teacherId, $termId, $sessionId)
    {
        $subjectClass = Subjectclass::with(['subject', 'schoolclass.arm', 'schoolclass.classcategories'])
            ->findOrFail($subjectclassId);

        $teacher    = User::findOrFail($teacherId);
        $term       = Schoolterm::findOrFail($termId);
        $session    = Schoolsession::findOrFail($sessionId);
        $schoolclass = $subjectClass->schoolclass;

        session(['schoolclass_id' => $schoolclass->id ?? null]);

        $broadsheets = $this->getMockBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);

        if ($broadsheets->isNotEmpty()) {
            $this->updateMockClassMetrics($subjectclassId, $teacherId, $termId, $sessionId);
            $this->updateMockSubjectPositions($subjectclassId, $teacherId, $termId, $sessionId);
            $broadsheets = $this->getMockBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);
        }

        $pagetitle = sprintf(
            'Admin Mock: %s – %s (%s) | %s %s | %s %s',
            $teacher->name,
            $subjectClass->subject->subject,
            $subjectClass->subject->subject_code,
            $schoolclass->schoolclass,
            $schoolclass->arm->arm ?? '',
            $term->term,
            $session->session
        );

        $is_senior = $schoolclass && $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()->is_senior ?? false
            : false;

        return view('admin.score-entry.mock-scoresheet', compact(
            'broadsheets', 'pagetitle', 'is_senior', 'subjectclassId',
            'teacherId', 'termId', 'sessionId', 'teacher', 'subjectClass',
            'term', 'session', 'schoolclass'
        ));
    }

    // =========================================================================
    // SINGLE UPDATE — terminal with lock check and audit
    // =========================================================================

    public function singleUpdate(Request $request)
    {
        try {
            $broadsheetId = $request->input('broadsheet_id');
            $lockCheck    = $this->checkLockStatus($broadsheetId);
            if (!$lockCheck['allowed']) {
                return response()->json(['success' => false, 'message' => $lockCheck['message'], 'locked' => true], 423);
            }

            $validated = $request->validate([
                'broadsheet_id'     => 'required|exists:broadsheets,id',
                'assessment_id'     => 'required|exists:assessments,id',
                'score'             => 'required|numeric|min:0',
                'is_sub'            => 'boolean',
                'sub_assessment_id' => 'nullable|exists:sub_assessments,id',
                'total'             => 'nullable|numeric',
                'raw_total'         => 'nullable|numeric',
            ]);

            $broadsheetId    = $validated['broadsheet_id'];
            $assessmentId    = $validated['assessment_id'];
            $score           = $validated['score'];
            $isSub           = $validated['is_sub'] ?? false;
            $subAssessmentId = $validated['sub_assessment_id'] ?? null;

            if ($isSub && !$subAssessmentId) {
                return response()->json(['success' => false, 'message' => 'Sub-assessment ID required.'], 422);
            }

            $broadsheet = Broadsheets::findOrFail($broadsheetId);
            $model      = $isSub ? SubAssessment::findOrFail($subAssessmentId) : Assessment::findOrFail($assessmentId);

            if ($score > $model->max_score) {
                return response()->json(['success' => false, 'message' => "Score cannot exceed maximum of {$model->max_score}."], 422);
            }

            $fkValue          = $broadsheet->broadSheet_record_id ?? $broadsheet->broadsheet_record_id;
            $broadsheetRecord = BroadsheetRecord::find($fkValue);

            $schoolclassId = $broadsheetRecord?->schoolclass_id
                ?? (int) ($request->input('schoolclass_id') ?: session('schoolclass_id'))
                ?: 0;

            $sessionId = $broadsheetRecord?->session_id
                ?? $request->input('session_id')
                ?? session('admin_score_entry_session_id');

            if (!$sessionId) {
                return response()->json(['success' => false, 'message' => 'Session context missing — please reload the scoresheet.'], 200);
            }

            $termId    = $broadsheet->term_id ?? session('admin_score_entry_term_id');
            $schoolclass = Schoolclass::with('classcategories')->find($schoolclassId);
            $isSenior  = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? $schoolclass->classcategories->first()->is_senior ?? false : false;

            DB::transaction(function () use (
                $broadsheetId, $assessmentId, $score, $broadsheet, $isSub,
                $subAssessmentId, $broadsheetRecord, $schoolclass, $sessionId, $termId
            ) {
                if ($isSub) {
                    BroadsheetSubAssessmentScore::updateOrCreate(
                        ['broadsheet_id' => $broadsheetId, 'sub_assessment_id' => $subAssessmentId, 'assessment_id' => $assessmentId],
                        ['score' => $score]
                    );
                    $assessment = Assessment::with('subAssessments')->find($assessmentId);
                    if ($assessment) {
                        $subMaxSum  = $assessment->subAssessments->sum('max_score');
                        $subTotal   = BroadsheetSubAssessmentScore::where('broadsheet_id', $broadsheetId)->where('assessment_id', $assessmentId)->sum('score');
                        $normalized = $subMaxSum > 0 ? ($subTotal / $subMaxSum) * $assessment->max_score : 0;
                        BroadsheetAssessmentScore::updateOrCreate(
                            ['broadsheet_id' => $broadsheetId, 'assessment_id' => $assessmentId],
                            ['score' => max(0, min($normalized, $assessment->max_score))]
                        );
                    }
                } else {
                    BroadsheetAssessmentScore::updateOrCreate(
                        ['broadsheet_id' => $broadsheetId, 'assessment_id' => $assessmentId],
                        ['score' => $score]
                    );
                }

                $assessments = collect();
                if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                    $categoryIds = $schoolclass->classcategories->pluck('id');
                    $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->get();
                }
                $broadsheet->load(['assessmentScores', 'subAssessmentScores']);
                $this->computeDynamicTotals(collect([$broadsheet]), $assessments, $schoolclass, $termId, $sessionId);
            });

            $this->updateAuditTrail($broadsheetId, auth()->id(), 'admin');
            $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $sessionId);
            $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $sessionId);
            $this->updateClassPositions($schoolclassId, $termId, $sessionId);

            $studentId = $broadsheetRecord?->student_id
                ?? DB::table('broadsheet_records')->where('id', $fkValue ?? 0)->value('student_id')
                ?? 0;

            $gpaCgpaData = $this->computeOverallForStudent($studentId, $schoolclass, $termId, $sessionId, $isSenior);
            $broadsheet->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Score updated successfully!',
                'data'    => [
                    'total'                        => $broadsheet->total,
                    // Both figures returned under their real names so the UI can show
                    // the raw running sum ("cum") and the per-term average ("cum_ave")
                    // as separate columns.
                    'cum'                          => $broadsheet->cum,
                    'cum_ave'                      => $broadsheet->cum_ave,
                    'bf'                           => $broadsheet->bf,
                    'grade'                        => $broadsheet->grade,
                    'remark'                       => $broadsheet->remark,
                    'subject_position_class'       => $broadsheet->subject_position_class,
                    'subject_position_class_total' => $broadsheet->subject_position_class_total,
                    'arm_position'                 => $broadsheet->arm_position,
                    'arm_position_cum'             => $broadsheet->arm_position_cum,
                    'gpa'                          => round($gpaCgpaData['gpa'], 2),
                    'gpa_grade'                    => $gpaCgpaData['gpa_grade'] ?? 'F',
                    'cgpa'                         => round($gpaCgpaData['cgpa'], 2),
                    'num_subjects'                 => $gpaCgpaData['num_subjects'] ?? 0,
                    'total_grade_points'           => $gpaCgpaData['total_grade_points'] ?? 0.0,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Admin singleUpdate error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 200);
        }
    }

    public function mockSingleUpdate(Request $request)
    {
        try {
            $validated    = $request->validate([
                'broadsheet_id' => 'required|exists:broadsheetmock,id',
                'exam'          => 'required|numeric|min:0|max:100',
            ]);
            $broadsheetId = $validated['broadsheet_id'];
            $examScore    = (float) $validated['exam'];
            $broadsheet   = \App\Models\BroadsheetsMock::findOrFail($broadsheetId);
            $mockRecord   = \App\Models\BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);
            $schoolclassId = $mockRecord?->schoolclass_id ?? 0;
            $sessionId     = $mockRecord?->session_id ?? session('admin_score_entry_session_id');
            $schoolclass   = Schoolclass::with('classcategories')->find($schoolclassId);
            $examScore     = max(0, min($examScore, 100));
            $total         = round($examScore, 2);
            $grade         = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? $schoolclass->classcategories->first()->calculateGrade($total)
                : $this->getDefaultGrade($total);
            $remark        = $this->getRemark($grade);
            $broadsheet->exam   = $examScore;
            $broadsheet->total  = $total;
            $broadsheet->grade  = $grade;
            $broadsheet->remark = $remark;
            $broadsheet->save();
            $this->updateMockClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $sessionId);
            $this->updateMockSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $sessionId);
            $broadsheet->refresh();
            return response()->json(['success' => true, 'message' => 'Score updated successfully!', 'data' => ['total' => $broadsheet->total, 'grade' => $broadsheet->grade, 'remark' => $broadsheet->remark, 'position' => $broadsheet->subject_position_class]]);
        } catch (\Exception $e) {
            Log::error('Admin mockSingleUpdate error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // BULK UPDATE — terminal
    // =========================================================================

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'scores'            => 'required|array',
            'scores.*.id'       => 'required|exists:broadsheets,id',
            'scores.*.assessments' => 'sometimes|array',
            'scores.*.total'    => 'nullable|numeric',
            'scores.*.raw_total'=> 'nullable|numeric',
            'term_id'           => 'required|exists:schoolterm,id',
            'session_id'        => 'required|exists:schoolsession,id',
            'subjectclass_id'   => 'required|exists:subjectclass,id',
            'staff_id'          => 'required|exists:users,id',
            'schoolclass_id'    => 'required|exists:schoolclass,id',
            'assessment_id'     => 'nullable|exists:assessments,id',
            'is_sub'            => 'nullable|boolean',
        ]);

        $scores          = $validated['scores'];
        $lockedIds       = [];
        foreach ($scores as $sd) {
            $lc = $this->checkLockStatus($sd['id']);
            if (!$lc['allowed']) $lockedIds[] = $sd['id'];
        }
        if (!empty($lockedIds)) {
            return response()->json(['success' => false, 'message' => count($lockedIds) . ' scoresheet(s) are locked.', 'locked_ids' => $lockedIds], 423);
        }

        $term_id         = $validated['term_id'];
        $session_id      = $validated['session_id'];
        $subjectclass_id = $validated['subjectclass_id'];
        $staff_id        = $validated['staff_id'];
        $schoolclass_id  = $validated['schoolclass_id'];
        $assessment_id   = $validated['assessment_id'] ?? null;
        $is_sub          = (bool) ($validated['is_sub'] ?? false);

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
        if (!$schoolclass) return response()->json(['success' => false, 'message' => 'School class not found'], 404);

        $assessments = collect();
        if ($schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->get();
        }

        $updatedCount = 0;
        $errors       = [];

        DB::transaction(function () use (
            $scores, $term_id, $session_id, $subjectclass_id, $staff_id, $schoolclass_id,
            $schoolclass, $assessments, $is_sub, $assessment_id, &$updatedCount, &$errors
        ) {
            foreach ($scores as $scoreData) {
                $broadsheetId   = $scoreData['id'];
                $broadsheet     = Broadsheets::find($broadsheetId);
                if (!$broadsheet) { $errors[] = "Broadsheet ID {$broadsheetId} not found."; continue; }

                $assessmentsData = $scoreData['assessments'] ?? [];
                if (empty($assessmentsData)) continue;

                $localErrors = [];

                if ($is_sub && $assessment_id) {
                    $parentAssessment = $assessments->where('id', $assessment_id)->first()
                        ?? Assessment::with('subAssessments')->find($assessment_id);

                    foreach ($assessmentsData as $subId => $inputScore) {
                        $subId     = (int) $subId;
                        $inputScore = max(0, (float) $inputScore);
                        $subModel  = SubAssessment::find($subId);
                        if (!$subModel || $subModel->assessment_id != $assessment_id) { $localErrors[] = "SubAssessment {$subId} invalid."; continue; }
                        \App\Models\BroadsheetSubAssessmentScore::updateOrCreate(
                            ['broadsheet_id' => $broadsheetId, 'sub_assessment_id' => $subId, 'assessment_id' => $assessment_id],
                            ['score' => min($inputScore, $subModel->max_score)]
                        );
                    }
                    if ($parentAssessment) {
                        $subMaxSum  = $parentAssessment->subAssessments->sum('max_score');
                        $subTotal   = \App\Models\BroadsheetSubAssessmentScore::where('broadsheet_id', $broadsheetId)->where('assessment_id', $assessment_id)->sum('score');
                        $normalized = $subMaxSum > 0 ? ($subTotal / $subMaxSum) * $parentAssessment->max_score : 0;
                        BroadsheetAssessmentScore::updateOrCreate(
                            ['broadsheet_id' => $broadsheetId, 'assessment_id' => $assessment_id],
                            ['score' => max(0, min($normalized, $parentAssessment->max_score))]
                        );
                    }
                } else {
                    foreach ($assessmentsData as $componentId => $inputScore) {
                        $componentId = (int) $componentId;
                        $inputScore  = max(0, (float) $inputScore);
                        $model       = $assessments->where('id', $componentId)->first();
                        if (!$model) { $localErrors[] = "Assessment {$componentId} invalid."; continue; }
                        BroadsheetAssessmentScore::updateOrCreate(
                            ['broadsheet_id' => $broadsheetId, 'assessment_id' => $componentId],
                            ['score' => min($inputScore, $model->max_score)]
                        );
                    }
                }

                if (!empty($localErrors)) { $errors[] = "Broadsheet {$broadsheetId}: " . implode(', ', $localErrors); continue; }

                $broadsheet->load(['assessmentScores', 'subAssessmentScores']);
                $this->computeDynamicTotals(collect([$broadsheet]), $assessments, $schoolclass, $term_id, $session_id);
                $this->updateAuditTrail($broadsheetId, auth()->id(), 'admin');
                $updatedCount++;
            }
            $this->updateClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        });

        $this->updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateClassPositions($schoolclass_id, $term_id, $session_id);

        $updatedBroadsheets = $this->getBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);
        $this->computeOverallGPAAndCGPA($updatedBroadsheets, $schoolclass, $term_id, $session_id);

        $response = ['success' => true, 'message' => "{$updatedCount} score(s) updated!", 'data' => ['broadsheets' => $updatedBroadsheets, 'assessments' => $assessments]];
        if (!empty($errors)) $response['warnings'] = $errors;
        return response()->json($response, 200);
    }

    public function mockBulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'scores'           => 'required|array',
            'scores.*.id'      => 'required|exists:broadsheetmock,id',
            'scores.*.exam'    => 'nullable|numeric|min:0|max:100',
            'term_id'          => 'required|exists:schoolterm,id',
            'session_id'       => 'required|exists:schoolsession,id',
            'subjectclass_id'  => 'required|exists:subjectclass,id',
            'staff_id'         => 'required|exists:users,id',
            'schoolclass_id'   => 'required|exists:schoolclass,id',
        ]);

        $scores          = $validated['scores'];
        $term_id         = $validated['term_id'];
        $session_id      = $validated['session_id'];
        $subjectclass_id = $validated['subjectclass_id'];
        $staff_id        = $validated['staff_id'];
        $schoolclass_id  = $validated['schoolclass_id'];
        $schoolclass     = Schoolclass::with('classcategories')->find($schoolclass_id);
        $updatedCount    = 0;

        DB::transaction(function () use ($scores, $session_id, $schoolclass, &$updatedCount) {
            foreach ($scores as $scoreData) {
                $broadsheet = \App\Models\BroadsheetsMock::find($scoreData['id']); if (!$broadsheet) continue;
                $examScore  = max(0, min((float) ($scoreData['exam'] ?? 0), 100));
                $total      = round($examScore, 2);
                $grade      = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->calculateGrade($total) : $this->getDefaultGrade($total);
                $broadsheet->exam   = $examScore; $broadsheet->total  = $total; $broadsheet->grade  = $grade; $broadsheet->remark = $this->getRemark($grade); $broadsheet->save();
                $updatedCount++;
            }
        });

        $this->updateMockClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        $updatedBroadsheets = $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);
        return response()->json(['success' => true, 'message' => "{$updatedCount} mock score(s) updated!", 'data' => ['broadsheets' => $updatedBroadsheets]]);
    }

    public function destroy(Request $request)
    {
        $id   = $request->input('id');
        $type = $request->input('type', 'terminal');

        if ($type === 'mock') {
            $broadsheet    = \App\Models\BroadsheetsMock::findOrFail($id);
            $subjectclassid = $broadsheet->subjectclass_id; $staffid = $broadsheet->staff_id; $termid = $broadsheet->term_id;
            $mockRecord    = \App\Models\BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);
            $broadsheet->delete();
            if ($mockRecord) { $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $mockRecord->session_id); $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $mockRecord->session_id); }
        } else {
            $broadsheet    = Broadsheets::findOrFail($id);
            $subjectclassid = $broadsheet->subjectclass_id; $staffid = $broadsheet->staff_id; $termid = $broadsheet->term_id;
            $broadsheetRecord = BroadsheetRecord::find($broadsheet->broadSheet_record_id);
            BroadsheetAssessmentScore::where('broadsheet_id', $id)->delete();
            \App\Models\BroadsheetSubAssessmentScore::where('broadsheet_id', $id)->delete();
            $broadsheet->delete();
            if ($broadsheetRecord) {
                $this->updateClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
                $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
                $this->updateClassPositions($broadsheetRecord->schoolclass_id, $termid, $broadsheetRecord->session_id);
            }
        }
        return response()->json(['success' => true, 'message' => 'Score deleted successfully!']);
    }

    // =========================================================================
    // RESULTS — AJAX refresh
    // =========================================================================

    public function results(Request $request)
    {
        try {
            $subjectclass_id = session('admin_score_entry_subjectclass_id') ?? session('subjectclass_id');
            $schoolclass_id  = session('schoolclass_id') ?? $request->get('schoolclass_id');
            $term_id         = session('admin_score_entry_term_id') ?? session('term_id');
            $session_id      = session('admin_score_entry_session_id') ?? session('session_id');
            $type            = session('admin_score_entry_type', 'terminal');

            if (!$subjectclass_id || !$term_id || !$session_id) {
                return response()->json(['success' => false, 'message' => 'Missing session data', 'scores' => []], 400);
            }

            if ($type === 'mock') {
                $broadsheets = $this->getMockBroadsheets(null, $term_id, $session_id, $schoolclass_id, $subjectclass_id);
                $scoresData  = $broadsheets->map(fn($b) => ['id' => $b->id, 'admissionno' => $b->admissionno, 'fname' => $b->fname, 'lname' => $b->lname, 'exam' => $b->exam, 'total' => $b->total, 'grade' => $b->grade, 'remark' => $b->remark, 'position' => $b->position, 'cmin' => $b->cmin, 'cmax' => $b->cmax, 'avg' => $b->avg]);
                return response()->json(['success' => true, 'scores' => $scoresData, 'type' => 'mock']);
            }

            $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->orderBy('id')->get();
            }

            $broadsheets = Broadsheets::where(['subjectclass_id' => $subjectclass_id, 'term_id' => $term_id])
                ->with(['assessmentScores', 'subAssessmentScores', 'lastModifiedBy', 'enteredBy', 'lockedBy'])
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->where('broadsheet_records.session_id', $session_id)
                ->orderBy('studentRegistration.lastname')->orderBy('studentRegistration.firstname')
                ->get([
                    'broadsheets.id', 'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname', 'studentRegistration.lastname as lname',
                    'broadsheets.total', 'broadsheets.bf',
                    // Both figures returned under their real names — see getBroadsheets() note above.
                    'broadsheets.cum',
                    'broadsheets.cum_ave',
                    'broadsheets.grade',
                    'broadsheets.avg', 'broadsheets.subject_position_class as position',
                    'broadsheets.subject_position_class_total as position_total',
                    'broadsheets.arm_position', 'broadsheets.arm_position_cum', 'broadsheets.term_id',
                    'broadsheets.is_locked', 'broadsheets.last_modified_at', 'broadsheets.last_modified_by',
                    'broadsheets.entry_source', 'broadsheets.entered_at', 'broadsheets.lock_reason', 'broadsheets.scheduled_unlock_at',
                ]);

            $this->computeOverallGPAAndCGPA($broadsheets, $schoolclass, $term_id, $session_id);

            $scoresData = $broadsheets->map(function ($b) use ($assessments) {
                $assessmentData = [];
                foreach ($assessments as $a) {
                    $s = $b->assessmentScores->where('assessment_id', $a->id)->first();
                    $assessmentData[$a->id] = ['name' => $a->name, 'max_score' => $a->max_score, 'score' => $s ? $s->score : 0];
                }
                return [
                    'id' => $b->id, 'admissionno' => $b->admissionno, 'fname' => $b->fname, 'lname' => $b->lname,
                    'assessments' => $assessmentData, 'total' => $b->total, 'bf' => $b->bf,
                    'cum' => $b->cum, 'cum_ave' => $b->cum_ave,
                    'avg' => $b->avg ?? 0, 'gpa' => $b->gpa ?? 0, 'gpa_grade' => $b->gpa_grade ?? 'F',
                    'cgpa' => $b->cgpa ?? 0, 'grade' => $b->grade, 'position' => $b->position,
                    'position_total' => $b->position_total, 'arm_position' => $b->arm_position,
                    'arm_position_cum' => $b->arm_position_cum, 'num_subjects' => $b->num_subjects ?? 0,
                    'total_grade_points' => $b->total_grade_points ?? 0.0, 'is_locked' => $b->is_locked,
                    'lock_reason' => $b->lock_reason, 'scheduled_unlock_at' => $b->scheduled_unlock_at,
                    'last_modified_at' => $b->last_modified_at ? $b->last_modified_at->format('Y-m-d H:i:s') : null,
                    'last_modified_by_name' => optional($b->lastModifiedBy)->name,
                    'entered_by_name' => optional($b->enteredBy)->name,
                    'entered_at' => $b->entered_at ? $b->entered_at->format('Y-m-d H:i:s') : null,
                    'entry_source' => $b->entry_source,
                ];
            });

            return response()->json(['success' => true, 'assessments' => $assessments, 'scores' => $scoresData]);
        } catch (\Exception $e) {
            Log::error('Admin results error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal server error.'], 500);
        }
    }

    // =========================================================================
    // BROADSHEET PREVIEW — compact payload for the index-page modal
    // =========================================================================

    public function broadsheetPreview(Request $request)
    {
        $request->validate([
            'subjectclass_id' => 'required|exists:subjectclass,id',
            'teacher_id'      => 'required|exists:users,id',
            'term_id'         => 'required|exists:schoolterm,id',
            'session_id'      => 'required|exists:schoolsession,id',
            'type'            => 'nullable|in:terminal,mock',
        ]);

        $subjectclassId = (int) $request->subjectclass_id;
        $teacherId       = (int) $request->teacher_id;
        $termId          = (int) $request->term_id;
        $sessionId       = (int) $request->session_id;
        $type            = $request->input('type', 'terminal');

        $subjectClass = Subjectclass::with(['subject', 'schoolclass.arm', 'schoolclass.classcategories'])
            ->find($subjectclassId);

        if (!$subjectClass) {
            return response()->json(['success' => false, 'message' => 'Subject/class not found.'], 404);
        }

        $teacher     = User::find($teacherId);
        $schoolclass = $subjectClass->schoolclass;
        $term        = Schoolterm::find($termId);
        $session     = Schoolsession::find($sessionId);

        $meta = [
            'subject_name'   => $subjectClass->subject->subject ?? '',
            'subject_code'   => $subjectClass->subject->subject_code ?? '',
            'teacher_name'   => $teacher->name ?? '',
            'class_name'     => trim(($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm->arm ?? '')),
            'term_name'      => $term->term ?? '',
            'session_name'   => $session->session ?? '',
            'type'           => $type,
            'subjectclass_id'=> $subjectclassId,
            'teacher_id'     => $teacherId,
            'term_id'        => $termId,
            'session_id'     => $sessionId,
        ];

        if ($type === 'mock') {
            $broadsheets = $this->getMockBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id ?? null, $subjectclassId);

            $rows = $broadsheets->map(fn ($b) => [
                'admissionno' => $b->admissionno,
                'name'        => trim(($b->lname ?? '') . ' ' . ($b->fname ?? '')),
                'exam'        => (float) ($b->exam ?? 0),
                'total'       => (float) ($b->total ?? 0),
                'grade'       => $b->grade,
                'remark'      => $b->remark,
                'position'    => $b->position,
                'entered'     => (float) ($b->exam ?? 0) > 0,
            ]);

            $studentCount = DB::table('studentclass')
                ->where('sessionid', $sessionId)->where('termid', $termId)
                ->where('schoolclassid', $schoolclass->id ?? 0)->count();

            $enteredCount = $rows->where('entered', true)->count();

            return response()->json([
                'success' => true,
                'meta'    => $meta,
                'summary' => [
                    'student_count'  => $studentCount,
                    'entered_count'  => $enteredCount,
                    'percentage'     => $studentCount > 0 ? round(($enteredCount / $studentCount) * 100, 1) : 0,
                    'class_average'  => round($rows->avg('total') ?? 0, 1),
                    'highest'        => round($rows->max('total') ?? 0, 1),
                    'lowest'         => $rows->where('entered', true)->min('total') !== null
                        ? round($rows->where('entered', true)->min('total'), 1) : 0,
                ],
                'rows' => $rows->values(),
            ]);
        }

        // ── Terminal ────────────────────────────────────────────────────────
        $broadsheets = $this->getBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id ?? null, $subjectclassId);
        $broadsheets->load('assessmentScores');

        $assessments = collect();
        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->orderBy('id')->get(['id', 'name', 'max_score']);
        }
        $requiredAssessments = $assessments->count();

        $rows = $broadsheets->map(function ($b) use ($assessments, $requiredAssessments) {
            $scoredCount = $assessments->isEmpty()
                ? ((float) $b->total > 0 ? 1 : 0)
                : $b->assessmentScores->where('score', '>', 0)->pluck('assessment_id')->unique()->count();

            return [
                'admissionno'    => $b->admissionno,
                'name'           => trim(($b->lname ?? '') . ' ' . ($b->fname ?? '')),
                'total'          => (float) ($b->total ?? 0),
                'cum'            => (float) ($b->cum ?? 0),
                'cum_ave'        => (float) ($b->cum_ave ?? 0),
                'grade'          => $b->grade,
                'position'       => $b->position,
                'scored_count'   => $scoredCount,
                'fully_entered'  => $requiredAssessments > 0 ? $scoredCount >= $requiredAssessments : ((float) $b->total > 0),
                'is_locked'      => (bool) $b->is_locked,
                'assessment_scores' => $b->assessmentScores->keyBy('assessment_id')->map(fn($s) => (float)$s->score)->toArray(),
            ];
        });

        $studentCount = DB::table('studentclass')
            ->where('sessionid', $sessionId)->where('termid', $termId)
            ->where('schoolclassid', $schoolclass->id ?? 0)->count();

        $fullyEnteredCount = $rows->where('fully_entered', true)->count();
        $startedCount      = $rows->where('scored_count', '>', 0)->count();

        return response()->json([
            'success' => true,
            'meta'    => $meta,
            'assessments' => $assessments,
            'summary' => [
                'student_count'        => $studentCount,
                'fully_entered_count'  => $fullyEnteredCount,
                'partial_count'        => max(0, $startedCount - $fullyEnteredCount),
                'not_started_count'    => max(0, $studentCount - $startedCount),
                'percentage'           => $studentCount > 0 ? round(($fullyEnteredCount / $studentCount) * 100, 1) : 0,
                'status'               => $this->resolveEntryStatus($fullyEnteredCount, $startedCount, $studentCount),
                'class_average'        => round($rows->avg('total') ?? 0, 1),
                'highest'              => round($rows->max('total') ?? 0, 1),
                'lowest'               => $rows->where('fully_entered', true)->min('total') !== null
                    ? round($rows->where('fully_entered', true)->min('total'), 1) : 0,
                'required_assessments' => $requiredAssessments,
            ],
            'rows' => $rows->values(),
        ]);
    }

    public function export(Request $request)
    {
        $schoolclassId  = $request->input('schoolclass_id',  session('schoolclass_id'));
        $subjectclassId = $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id'));
        $termId         = $request->input('term_id',         session('admin_score_entry_term_id'));
        $sessionId      = $request->input('session_id',      session('admin_score_entry_session_id'));
        $staffId        = $request->input('staff_id',        session('admin_score_entry_teacher_id'));

        $subjectClass = Subjectclass::with('subject')->find($subjectclassId);
        $schoolclass  = Schoolclass::find($schoolclassId);
        $term         = Schoolterm::find($termId);
        $session      = Schoolsession::find($sessionId);

        $subjectName  = preg_replace('/[^a-zA-Z0-9-]/', '_', $subjectClass?->subject?->subject ?? 'subject');
        $className    = preg_replace('/[^a-zA-Z0-9-]/', '_', $schoolclass?->schoolclass ?? 'class');
        $termName     = preg_replace('/[^a-zA-Z0-9-]/', '_', $term?->term ?? 'term');
        $sessionName  = preg_replace('/[^a-zA-Z0-9-]/', '_', $session?->session ?? 'session');

        $filename = "admin_{$subjectName}_{$className}_{$termName}_{$sessionName}_scoresheet.xlsx";
        $export   = new AdminRecordsheetExport(
            (int) $schoolclassId,
            (int) $subjectclassId,
            (int) $termId,
            (int) $sessionId,
            (int) $staffId
        );

        return Excel::download($export, $filename);
    }

    // =========================================================================
    // BULK EXPORT — multiple scoresheets from index, returned as a ZIP
    // =========================================================================

    public function bulkExport(Request $request)
    {
        $request->validate([
            'subjects'                   => 'required|array|min:1',
            'subjects.*.subjectclass_id' => 'required|exists:subjectclass,id',
            'subjects.*.teacher_id'      => 'required|exists:users,id',
            'subjects.*.schoolclass_id'  => 'required|exists:schoolclass,id',
            'subjects.*.term_id'         => 'required|exists:schoolterm,id',
            'subjects.*.session_id'      => 'required|exists:schoolsession,id',
        ]);

        $subjects = $request->input('subjects');
        $tempDir  = storage_path('app/temp');

        try {
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            if (!is_writable($tempDir)) {
                Log::error("AdminBulkExport: temp dir not writable: {$tempDir}");
                return response()->json(['success' => false, 'message' => 'Temp directory is not writable.'], 500);
            }

            $zip     = new \ZipArchive();
            $zipName = 'admin_scoresheets_' . now()->format('Y-m-d_His') . '.zip';
            $zipPath = $tempDir . '/' . $zipName;

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return response()->json(['success' => false, 'message' => 'Could not create ZIP archive.'], 500);
            }

            $added     = 0;
            $tempFiles = [];

            foreach ($subjects as $subjectData) {
                $subjectclassId = (int) $subjectData['subjectclass_id'];
                $teacherId      = (int) $subjectData['teacher_id'];
                $schoolclassId  = (int) $subjectData['schoolclass_id'];
                $termId         = (int) $subjectData['term_id'];
                $sessionId      = (int) $subjectData['session_id'];

                $hasScores = DB::table('broadsheets')
                    ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                    ->where('broadsheets.subjectclass_id', $subjectclassId)
                    ->where('broadsheets.term_id', $termId)
                    ->where('broadsheet_records.session_id', $sessionId)
                    ->exists();

                if (!$hasScores) {
                    Log::info("AdminBulkExport: skipping subjectclass {$subjectclassId} — no scores");
                    continue;
                }

                $info = DB::table('subjectclass')
                    ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                    ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                    ->join('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                    ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                    ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                    ->where('subjectclass.id', $subjectclassId)
                    ->select(
                        'subject.subject as subject_name',
                        'subject.subject_code',
                        'schoolclass.schoolclass as class_name',
                        'schoolarm.arm as arm_name',
                        'schoolterm.term as term_name',
                        'schoolsession.session as session_name'
                    )
                    ->first();

                $subjectName = preg_replace('/[^a-zA-Z0-9-]/', '_', $info?->subject_name ?? 'subject');
                $className   = preg_replace('/[^a-zA-Z0-9-]/', '_', $info?->class_name  ?? 'class');
                $armName     = preg_replace('/[^a-zA-Z0-9-]/', '_', $info?->arm_name    ?? '');
                $termName    = preg_replace('/[^a-zA-Z0-9-]/', '_', $info?->term_name   ?? 'term');
                $sessionName = preg_replace('/[^a-zA-Z0-9-]/', '_', $info?->session_name ?? 'session');

                $filenameInZip = 'admin_' . $subjectName
                    . '_' . $className
                    . ($armName ? '_' . $armName : '')
                    . '_' . $termName
                    . '_' . $sessionName
                    . '_scoresheet.xlsx';

                $export = new AdminRecordsheetExport(
                    $schoolclassId,
                    $subjectclassId,
                    $termId,
                    $sessionId,
                    $teacherId
                );

                try {
                    $xlsxContent = \Maatwebsite\Excel\Facades\Excel::raw(
                        $export,
                        \Maatwebsite\Excel\Excel::XLSX
                    );

                    if (empty($xlsxContent)) {
                        Log::warning("AdminBulkExport: empty content for subjectclass {$subjectclassId}");
                        continue;
                    }

                    $absolutePath = $tempDir . '/' . uniqid('sheet_') . '.xlsx';
                    $written      = file_put_contents($absolutePath, $xlsxContent);

                    if ($written === false || $written === 0) {
                        Log::warning("AdminBulkExport: file_put_contents failed for {$absolutePath}");
                        continue;
                    }

                    $zip->addFile($absolutePath, $filenameInZip);
                    $tempFiles[] = $absolutePath;
                    $added++;

                } catch (\Exception $e) {
                    Log::error("AdminBulkExport: failed for subjectclass {$subjectclassId}: " . $e->getMessage());
                    continue;
                }
            }

            $zip->close();

            foreach ($tempFiles as $f) {
                @unlink($f);
            }

            if ($added === 0) {
                @unlink($zipPath);
                return response()->json([
                    'success' => false,
                    'message' => 'No scoresheets could be generated. Check logs for details.',
                ], 422);
            }

            return response()->download($zipPath, $zipName, [
                'Content-Type'        => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipName . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Admin bulkExport error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            if (!empty($zipPath) && file_exists($zipPath)) {
                @unlink($zipPath);
            }
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // IMPORT — single scoresheet (from scoresheet view)
    // =========================================================================

    public function import(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

            $importData = [
                'subjectclass_id' => $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id')),
                'staff_id'        => $request->input('staff_id',        session('admin_score_entry_teacher_id')),
                'term_id'         => $request->input('term_id',         session('admin_score_entry_term_id')),
                'session_id'      => $request->input('session_id',      session('admin_score_entry_session_id')),
                'schoolclass_id'  => $request->input('schoolclass_id',  session('schoolclass_id')),
            ];

            if (empty($importData['subjectclass_id']) || empty($importData['staff_id'])) {
                return response()->json(['success' => false, 'message' => 'Missing session data. Please open the scoresheet first.'], 422);
            }

            $importer = new AdminScoresheetImport($importData);
            $importer->validateExcelFile($request->file('file'));
            Excel::import($importer, $request->file('file'));

            return $this->buildImportResponse($importer, $importData);
        } catch (\Exception $e) {
            Log::error('Admin import failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // BULK IMPORT — upload a ZIP of xlsx files from the index page
    // =========================================================================

    public function bulkImport(Request $request)
    {
        try {
            $request->validate([
                'zip_file'   => 'required|file|mimes:zip',
                'term_id'    => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
            ]);

            $termId    = (int) $request->term_id;
            $sessionId = (int) $request->session_id;

            $zip     = new \ZipArchive();
            $zipPath = $request->file('zip_file')->getRealPath();

            if ($zip->open($zipPath) !== true) {
                return response()->json(['success' => false, 'message' => 'Could not open ZIP file.'], 422);
            }

            $extractDir = storage_path('app/temp/bulk_import_' . uniqid());
            mkdir($extractDir, 0755, true);
            $zip->extractTo($extractDir);
            $zip->close();

            $xlsxFiles   = glob($extractDir . '/*.xlsx');
            $xlsxFiles   = array_merge($xlsxFiles, glob($extractDir . '/**/*.xlsx'));

            if (empty($xlsxFiles)) {
                $this->cleanDir($extractDir);
                return response()->json(['success' => false, 'message' => 'No .xlsx files found in the uploaded ZIP.'], 422);
            }

            $totalSuccess  = 0;
            $totalFailures = [];
            $filesProcessed = 0;

            foreach ($xlsxFiles as $xlsxPath) {
                $importData = $this->resolveImportDataFromFilename(
                    basename($xlsxPath), $termId, $sessionId
                );

                if (!$importData) {
                    $totalFailures[] = [
                        'file'   => basename($xlsxPath),
                        'errors' => ['Could not match file to a subject/class. Ensure the filename was not changed from the exported name.'],
                    ];
                    continue;
                }

                try {
                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $xlsxPath,
                        basename($xlsxPath),
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        null,
                        true
                    );

                    $importer = new AdminScoresheetImport($importData);
                    Excel::import($importer, $uploadedFile);

                    $totalSuccess  += $importer->getSuccessCount();
                    $failures       = $importer->getFailures();

                    foreach ($failures as $f) {
                        $totalFailures[] = array_merge($f, ['file' => basename($xlsxPath)]);
                    }

                    $filesProcessed++;

                    $this->updateClassMetrics(
                        (int) $importData['subjectclass_id'],
                        (int) $importData['staff_id'],
                        $termId,
                        $sessionId
                    );
                    $this->updateSubjectPositions(
                        (int) $importData['subjectclass_id'],
                        (int) $importData['staff_id'],
                        $termId,
                        $sessionId
                    );
                    $this->updateClassPositions(
                        (int) $importData['schoolclass_id'],
                        $termId,
                        $sessionId
                    );
                } catch (\Exception $e) {
                    $totalFailures[] = [
                        'file'   => basename($xlsxPath),
                        'errors' => [$e->getMessage()],
                    ];
                }
            }

            $this->cleanDir($extractDir);

            if ($totalSuccess === 0 && !empty($totalFailures)) {
                return response()->json([
                    'success'  => false,
                    'message'  => 'No records imported.',
                    'failures' => $totalFailures,
                ], 422);
            }

            $response = [
                'success'         => true,
                'message'         => "Imported {$totalSuccess} score(s) from {$filesProcessed} file(s).",
                'files_processed' => $filesProcessed,
                'total_success'   => $totalSuccess,
            ];

            if (!empty($totalFailures)) {
                $response['warning']  = true;
                $response['failures'] = $totalFailures;
                $response['message']  = "Imported {$totalSuccess} score(s) from {$filesProcessed} file(s) with " . count($totalFailures) . ' warning(s).';
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Admin bulkImport error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Bulk import failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // Helper: resolve subjectclass from filename
    // =========================================================================

    protected function resolveImportDataFromFilename(string $filename, int $termId, int $sessionId): ?array
    {
        $bare = preg_replace('/^admin_/', '', $filename);
        $bare = preg_replace('/_scoresheet\.xlsx$/i', '', $bare);

        $candidates = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->join('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->where('subjectteacher.termid', $termId)
            ->where('subjectteacher.sessionid', $sessionId)
            ->select([
                'subjectclass.id as subjectclass_id',
                'subjectteacher.staffid as staff_id',
                'schoolclass.id as schoolclass_id',
                'subject.subject as subject_name',
                'schoolclass.schoolclass',
                'schoolterm.term',
                'schoolsession.session',
            ])
            ->get();

        foreach ($candidates as $c) {
            $expected = preg_replace('/[^a-zA-Z0-9-]/', '_', $c->subject_name)
                . '_' . preg_replace('/[^a-zA-Z0-9-]/', '_', $c->schoolclass)
                . '_' . preg_replace('/[^a-zA-Z0-9-]/', '_', $c->term)
                . '_' . preg_replace('/[^a-zA-Z0-9-]/', '_', $c->session);

            if (strtolower($bare) === strtolower($expected)) {
                return [
                    'subjectclass_id' => $c->subjectclass_id,
                    'staff_id'        => $c->staff_id,
                    'schoolclass_id'  => $c->schoolclass_id,
                    'term_id'         => $termId,
                    'session_id'      => $sessionId,
                ];
            }
        }

        Log::warning("[AdminBulkImport] Could not match file '{$filename}' (bare='{$bare}') to any subjectclass.");
        return null;
    }

    // =========================================================================
    // Helper: build import JSON response
    // =========================================================================

    protected function buildImportResponse(AdminScoresheetImport $importer, array $importData): \Illuminate\Http\JsonResponse
    {
        $successCount = $importer->getSuccessCount();
        $failures     = $importer->getFailures();

        $broadsheets = $this->getBroadsheets(
            $importData['staff_id'],
            $importData['term_id'],
            $importData['session_id'],
            $importData['schoolclass_id'],
            $importData['subjectclass_id']
        );

        $schoolclass = Schoolclass::with('classcategories')->find($importData['schoolclass_id']);
        $assessments = collect();
        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->orderBy('id')->get();
        }

        $formattedBroadsheets = $this->formatBroadsheetsForResponse($broadsheets, $assessments);

        if ($successCount === 0 && !empty($failures)) {
            return response()->json(['success' => false, 'message' => 'No records imported.', 'errors' => $failures], 422);
        }

        $responseData = [
            'success' => true,
            'message' => "Successfully imported {$successCount} score(s)!",
            'data'    => ['broadsheets' => $formattedBroadsheets, 'assessments' => $assessments],
        ];

        if (!empty($failures)) {
            $responseData['warning'] = true;
            $responseData['message'] = "Imported {$successCount} record(s) with " . count($failures) . " warning(s).";
            $responseData['failures'] = $failures;
        }

        $this->updateClassMetrics(
            (int) $importData['subjectclass_id'],
            (int) $importData['staff_id'],
            (int) $importData['term_id'],
            (int) $importData['session_id']
        );
        $this->updateSubjectPositions(
            (int) $importData['subjectclass_id'],
            (int) $importData['staff_id'],
            (int) $importData['term_id'],
            (int) $importData['session_id']
        );
        $this->updateClassPositions(
            (int) $importData['schoolclass_id'],
            (int) $importData['term_id'],
            (int) $importData['session_id']
        );

        return response()->json($responseData);
    }

    // =========================================================================
    // LOCK MANAGEMENT METHODS
    // =========================================================================

    protected function checkLockStatus($broadsheetId)
    {
        $broadsheet = Broadsheets::find($broadsheetId);
        if (!$broadsheet) return ['allowed' => false, 'message' => 'Record not found'];

        $subjectClass = Subjectclass::find($broadsheet->subjectclass_id);
        if ($subjectClass && !$subjectClass->teacher_editing_enabled)
            return ['allowed' => false, 'message' => 'Teacher editing has been disabled for this subject.'];

        if ($broadsheet->is_locked) {
            $reason = $broadsheet->lock_reason ?? 'This scoresheet has been locked by an administrator';
            return ['allowed' => false, 'message' => $reason];
        }

        $globalLock = ScoresheetLock::where([
            'subjectclass_id' => $broadsheet->subjectclass_id,
            'term_id'         => $broadsheet->term_id,
            'session_id'      => $broadsheet->session_id,
            'is_active'       => true,
        ])->first();

        if ($globalLock) return ['allowed' => false, 'message' => $globalLock->reason ?? 'This scoresheet is under global lock'];

        return ['allowed' => true];
    }

    protected function updateAuditTrail($broadsheetId, $userId, $source)
    {
        $broadsheet = Broadsheets::find($broadsheetId); if (!$broadsheet) return;
        $now        = now();
        $updateData = ['last_modified_by' => $userId, 'last_modified_at' => $now];
        if (is_null($broadsheet->entered_by)) {
            $updateData['entered_by']   = $userId;
            $updateData['entered_at']   = $now;
            $updateData['entry_source'] = $source;
        }
        $broadsheet->update($updateData);
    }

    public function lockScoresheet(Request $request)
    {
        $request->validate(['broadsheet_id' => 'required|exists:broadsheets,id', 'reason' => 'nullable|string|max:500']);
        try {
            $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);
            if ($broadsheet->is_locked) return response()->json(['success' => false, 'message' => 'Already locked.'], 422);
            $broadsheet->is_locked   = true;
            $broadsheet->locked_by   = auth()->id();
            $broadsheet->locked_at   = now();
            $broadsheet->lock_reason = $request->reason ?: 'Locked by administrator';
            $broadsheet->save();
            return response()->json(['success' => true, 'message' => 'Scoresheet locked successfully.', 'data' => ['is_locked' => true, 'locked_by_name' => auth()->user()->name, 'locked_at' => now()->format('Y-m-d H:i:s')]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to lock scoresheet.'], 500);
        }
    }

    public function unlockScoresheet(Request $request)
    {
        $request->validate(['broadsheet_id' => 'required|exists:broadsheets,id']);
        try {
            $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);
            $globalLock = ScoresheetLock::where(['subjectclass_id' => $broadsheet->subjectclass_id, 'term_id' => $broadsheet->term_id, 'session_id' => $broadsheet->session_id, 'is_active' => true])->first();
            if ($globalLock) return response()->json(['success' => false, 'message' => 'Cannot unlock: Subject is under global lock.'], 422);
            if (!$broadsheet->is_locked) return response()->json(['success' => false, 'message' => 'Already unlocked.'], 422);
            $broadsheet->is_locked   = false; $broadsheet->locked_by = null; $broadsheet->locked_at = null; $broadsheet->lock_reason = null; $broadsheet->save();
            return response()->json(['success' => true, 'message' => 'Scoresheet unlocked successfully.', 'data' => ['is_locked' => false]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to unlock scoresheet.'], 500);
        }
    }

    public function lockScoresheetWithSchedule(Request $request)
    {
        $request->validate(['broadsheet_id' => 'required|exists:broadsheets,id', 'reason' => 'nullable|string|max:500', 'scheduled_unlock_at' => 'required|date|after:now']);
        $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);
        if ($broadsheet->is_locked) return response()->json(['success' => false, 'message' => 'Already locked.'], 422);
        $broadsheet->lock($request->reason, auth()->id());
        $broadsheet->scheduleUnlock($request->scheduled_unlock_at, auth()->id());
        return response()->json(['success' => true, 'message' => 'Scoresheet locked with scheduled unlock at ' . $request->scheduled_unlock_at, 'data' => ['is_locked' => true, 'scheduled_unlock_at' => $request->scheduled_unlock_at]]);
    }

    public function lockBatchScoresheets(Request $request)
    {
        $request->validate(['subjectclass_ids' => 'required|array', 'subjectclass_ids.*' => 'exists:subjectclass,id', 'reason' => 'nullable|string|max:500', 'lock_type' => 'sometimes|in:individual,global', 'term_id' => 'required_if:lock_type,global', 'session_id' => 'required_if:lock_type,global']);
        $subjectclassIds = $request->subjectclass_ids; $reason = $request->reason; $userId = auth()->id(); $lockType = $request->lock_type ?? 'individual';
        DB::beginTransaction();
        try {
            $lockedCount = 0;
            foreach ($subjectclassIds as $scId) {
                if ($lockType === 'global') ScoresheetLock::updateOrCreate(['subjectclass_id' => $scId, 'term_id' => $request->term_id, 'session_id' => $request->session_id], ['is_active' => true, 'locked_by' => $userId, 'locked_at' => now(), 'reason' => $reason ?: 'Batch lock applied by admin']);
                $lockedCount += Broadsheets::where('subjectclass_id', $scId)->where('is_locked', false)->update(['is_locked' => true, 'locked_by' => $userId, 'locked_at' => now(), 'lock_reason' => $reason ?: 'Batch lock applied by admin']);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => "Successfully locked {$lockedCount} scoresheet(s).", 'data' => ['locked_count' => $lockedCount]]);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function lockBatchWithSchedule(Request $request)
    {
        $request->validate(['subjectclass_ids' => 'required|array', 'subjectclass_ids.*' => 'exists:subjectclass,id', 'reason' => 'nullable|string|max:500', 'lock_type' => 'required|in:individual,global', 'scheduled_unlock_at' => 'required|date|after:now', 'term_id' => 'required_if:lock_type,global', 'session_id' => 'required_if:lock_type,global']);
        $subjectclassIds = $request->subjectclass_ids; $reason = $request->reason; $userId = auth()->id(); $lockType = $request->lock_type; $scheduledUnlockAt = $request->scheduled_unlock_at;
        DB::beginTransaction();
        try {
            $lockedCount = 0;
            foreach ($subjectclassIds as $scId) {
                if ($lockType === 'global') { $lock = ScoresheetLock::updateOrCreate(['subjectclass_id' => $scId, 'term_id' => $request->term_id, 'session_id' => $request->session_id], ['is_active' => true, 'locked_by' => $userId, 'locked_at' => now(), 'reason' => $reason ?: 'Batch lock applied by admin']); $lock->scheduleUnlock($scheduledUnlockAt); }
                $lockedCount += Broadsheets::where('subjectclass_id', $scId)->where('is_locked', false)->update(['is_locked' => true, 'locked_by' => $userId, 'locked_at' => now(), 'lock_reason' => $reason ?: 'Batch lock applied by admin', 'scheduled_unlock_at' => $scheduledUnlockAt]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => "Locked {$lockedCount} scoresheet(s) with scheduled unlock at {$scheduledUnlockAt}.", 'data' => ['locked_count' => $lockedCount]]);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function unlockBatchScoresheets(Request $request)
    {
        $request->validate(['subjectclass_ids' => 'required|array', 'subjectclass_ids.*' => 'exists:subjectclass,id', 'unlock_type' => 'sometimes|in:individual,global', 'term_id' => 'required_if:unlock_type,global', 'session_id' => 'required_if:unlock_type,global']);
        $subjectclassIds = $request->subjectclass_ids; $unlockType = $request->unlock_type ?? 'individual';
        DB::beginTransaction();
        try {
            $unlockedCount = 0;
            foreach ($subjectclassIds as $scId) {
                if ($unlockType === 'global') ScoresheetLock::where(['subjectclass_id' => $scId, 'term_id' => $request->term_id, 'session_id' => $request->session_id])->update(['is_active' => false]);
                $unlockedCount += Broadsheets::where('subjectclass_id', $scId)->where('is_locked', true)->update(['is_locked' => false, 'locked_by' => null, 'locked_at' => null, 'lock_reason' => null, 'scheduled_unlock_at' => null]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => "Successfully unlocked {$unlockedCount} scoresheet(s).", 'data' => ['unlocked_count' => $unlockedCount]]);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function disableTeacherEditing(Request $request)
    {
        $request->validate(['subjectclass_ids' => 'required|array', 'subjectclass_ids.*' => 'exists:subjectclass,id', 'reason' => 'nullable|string|max:500']);
        $subjectclassIds = $request->subjectclass_ids; $reason = $request->reason; $userId = auth()->id();
        DB::beginTransaction();
        try {
            $lockedCount = 0;
            foreach ($subjectclassIds as $scId) {
                $sc = Subjectclass::find($scId);
                if ($sc) { $sc->teacher_editing_enabled = false; $sc->teacher_editing_disabled_at = now(); $sc->teacher_editing_disabled_by = $userId; $sc->save(); }
                $lockedCount += Broadsheets::where('subjectclass_id', $scId)->update(['is_locked' => true, 'locked_by' => $userId, 'locked_at' => now(), 'lock_reason' => $reason ?: 'Teacher editing disabled by admin']);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => "Teacher editing disabled. {$lockedCount} scoresheets locked.", 'data' => ['locked_count' => $lockedCount]]);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function enableTeacherEditing(Request $request)
    {
        $request->validate(['subjectclass_ids' => 'required|array', 'subjectclass_ids.*' => 'exists:subjectclass,id']);
        $subjectclassIds = $request->subjectclass_ids;
        DB::beginTransaction();
        try {
            $unlockedCount = 0;
            foreach ($subjectclassIds as $scId) {
                $sc = Subjectclass::find($scId);
                if ($sc) { $sc->teacher_editing_enabled = true; $sc->teacher_editing_disabled_at = null; $sc->teacher_editing_disabled_by = null; $sc->save(); }
                $unlockedCount += Broadsheets::where('subjectclass_id', $scId)->where('is_locked', true)->update(['is_locked' => false, 'locked_by' => null, 'locked_at' => null, 'lock_reason' => null]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => "Teacher editing enabled. {$unlockedCount} scoresheets unlocked.", 'data' => ['unlocked_count' => $unlockedCount]]);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getLockStatus(Request $request)
    {
        $request->validate(['subjectclass_id' => 'required|exists:subjectclass,id', 'term_id' => 'required|exists:schoolterm,id', 'session_id' => 'required|exists:schoolsession,id']);
        $subjectclass = Subjectclass::findOrFail($request->subjectclass_id);
        $globalLock   = ScoresheetLock::where(['subjectclass_id' => $request->subjectclass_id, 'term_id' => $request->term_id, 'session_id' => $request->session_id, 'is_active' => true])->first();
        $lockedCount  = Broadsheets::where(['subjectclass_id' => $request->subjectclass_id, 'term_id' => $request->term_id, 'is_locked' => true])->whereHas('broadsheetRecord', fn($q) => $q->where('session_id', $request->session_id))->count();
        $totalCount   = Broadsheets::where(['subjectclass_id' => $request->subjectclass_id, 'term_id' => $request->term_id])->whereHas('broadsheetRecord', fn($q) => $q->where('session_id', $request->session_id))->count();
        return response()->json(['success' => true, 'data' => ['teacher_editing_enabled' => $subjectclass->teacher_editing_enabled, 'global_lock_exists' => !is_null($globalLock), 'global_lock_info' => $globalLock ? ['locked_by' => optional($globalLock->lockedBy)->name, 'locked_at' => $globalLock->locked_at->format('Y-m-d H:i:s'), 'reason' => $globalLock->reason, 'scheduled_unlock_at' => $globalLock->scheduled_unlock_at] : null, 'locked_count' => $lockedCount, 'total_count' => $totalCount, 'lock_percentage' => $totalCount > 0 ? round(($lockedCount / $totalCount) * 100) : 0]]);
    }

    public function updateAllArmPositions(Request $request)
    {
        try {
            $request->validate(['schoolclass_id' => 'required|exists:schoolclass,id', 'term_id' => 'required|exists:schoolterm,id', 'session_id' => 'required|exists:schoolsession,id']);
            $schoolclassId = $request->schoolclass_id; $termId = $request->term_id; $sessionId = $request->session_id;
            $baseClass     = DB::table('schoolclass')->where('id', $schoolclassId)->first(['schoolclass', 'classcategoryid']);
            if (!$baseClass) return response()->json(['success' => false, 'message' => 'Base class not found']);
            $allArms = DB::table('schoolclass')->where('schoolclass', $baseClass->schoolclass)->where('classcategoryid', $baseClass->classcategoryid)->get();
            $subjectclassRecords = DB::table('subjectclass')->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')->whereIn('subjectclass.schoolclassid', $allArms->pluck('id'))->select('subjectteacher.subjectid', DB::raw('MIN(subjectclass.id) as representative_id'))->groupBy('subjectteacher.subjectid')->get();
            $subjectsProcessed = 0;
            foreach ($subjectclassRecords as $record) {
                $repSubjectclass = DB::table('subjectclass')->where('id', $record->representative_id)->first();
                if (!$repSubjectclass) continue;
                $this->updateSubjectPositions($record->representative_id, $repSubjectclass->staffid ?? 0, $termId, $sessionId);
                $subjectsProcessed++;
            }
            return response()->json(['success' => true, 'message' => "Positions updated! Processed {$subjectsProcessed} subject(s) across " . $allArms->count() . " arms.", 'data' => ['arms_count' => $allArms->count(), 'subjects_processed' => $subjectsProcessed]]);
        } catch (\Exception $e) {
            Log::error('Admin updateAllArmPositions error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GRADE PREVIEW ENDPOINT
    // =========================================================================

    public function calculateGradeForScore(Request $request)
    {
        $request->validate(['schoolclass_id' => 'required|exists:schoolclass,id', 'total' => 'required|numeric|min:0|max:100', 'cum' => 'required|numeric|min:0|max:100']);
        $schoolclass = Schoolclass::with('classcategories')->findOrFail($request->schoolclass_id);
        $category    = $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first() : null;
        $totalGrade  = $category ? $category->calculateGrade($request->total) : $this->getDefaultGrade($request->total);
        $cumGrade    = $category ? $category->calculateGrade($request->cum)   : $this->getDefaultGrade($request->cum);
        return response()->json(['success' => true, 'total_grade' => $totalGrade, 'cum_grade' => $cumGrade, 'remark' => $this->getRemark($totalGrade)]);
    }

    // =========================================================================
    // DOWNLOAD METHODS
    // =========================================================================

    public function downloadMarksSheet(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id'));
            $staffid        = $request->input('staff_id',        session('admin_score_entry_teacher_id'));
            $termid         = $request->input('term_id',         session('admin_score_entry_term_id'));
            $sessionid      = $request->input('session_id',      session('admin_score_entry_session_id'));
            $schoolclassid  = $request->input('schoolclass_id',  session('schoolclass_id'));
            $type           = $request->input('type',            session('admin_score_entry_type', 'terminal'));
            if ($type === 'mock') return $this->downloadMockMarksSheet($subjectclassid, $staffid, $termid, $sessionid, $schoolclassid);
            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            if ($broadsheets->isEmpty()) return response()->json(['success' => false, 'message' => 'No students found.'], 404);
            $teacher     = User::find($staffid); $teacherName = $teacher ? ($teacher->name ?? '') : '';
            $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) { $categoryIds = $schoolclass->classcategories->pluck('id'); $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->orderBy('id')->get(); }
            $school = SchoolInformation::first();
            $pdf    = Pdf::loadView('admin.score-entry.marksheet-pdf', ['broadsheets' => $broadsheets, 'assessments' => $assessments, 'classInfo' => $broadsheets->first(), 'school' => $school, 'teacherName' => $teacherName, 'isAdminView' => true]);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->download('admin-marks-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Admin marks sheet download error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function downloadScoresPdf(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id'));
            $staffid        = $request->input('staff_id',        session('admin_score_entry_teacher_id'));
            $termid         = $request->input('term_id',         session('admin_score_entry_term_id'));
            $sessionid      = $request->input('session_id',      session('admin_score_entry_session_id'));
            $schoolclassid  = $request->input('schoolclass_id',  session('schoolclass_id'));
            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            if ($broadsheets->isEmpty()) return response()->json(['success' => false, 'message' => 'No students found.'], 404);
            $broadsheets->load(['assessmentScores']);
            $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) { $categoryIds = $schoolclass->classcategories->pluck('id'); $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->orderBy('id')->get(); }
            $teacher = User::find($staffid); $teacherName = $teacher ? ($teacher->name ?? '') : '';
            $school  = SchoolInformation::first();
            $pdf     = Pdf::loadView('admin.score-entry.scores-pdf', ['broadsheets' => $broadsheets, 'assessments' => $assessments, 'classInfo' => $broadsheets->first(), 'school' => $school, 'teacherName' => $teacherName, 'isAdminView' => true]);
            $pdf->setPaper('a4', 'landscape');
            $subject  = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->subject ?? 'subject');
            $class    = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->schoolclass ?? 'class');
            $termName = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->term ?? 'term');
            return $pdf->download("admin-scores-{$subject}-{$class}-{$termName}-" . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Admin scores PDF error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    protected function downloadMockMarksSheet($subjectclassid, $staffid, $termid, $sessionid, $schoolclassid)
    {
        $broadsheets = $this->getMockBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
        if ($broadsheets->isEmpty()) return response()->json(['success' => false, 'message' => 'No mock scores found.'], 404);
        $teacher = User::find($staffid); $teacherName = $teacher ? ($teacher->name ?? '') : '';
        $school  = SchoolInformation::first();
        $pdf     = Pdf::loadView('admin.score-entry.mock-marksheet-pdf', ['broadsheets' => $broadsheets, 'classInfo' => $broadsheets->first(), 'school' => $school, 'teacherName' => $teacherName, 'isAdminView' => true]);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('admin-mock-marks-sheet-' . date('Y-m-d') . '.pdf');
    }

    // =========================================================================
    // STUDENT RESULT MANAGER
    // =========================================================================

    public function studentResultManager()
    {
        $pagetitle = "Student Result Manager - Enter/Edit Results per Student";
        $terms     = \App\Models\Schoolterm::orderBy('id')->get();
        $sessions  = \App\Models\Schoolsession::orderBy('id', 'desc')->get();
        $classes   = \App\Models\Schoolclass::with('armRelation')->orderBy('schoolclass')->get();
        return view('admin.score-entry.student-result-manager', compact('pagetitle', 'terms', 'sessions', 'classes'));
    }

    public function getStudentResults(Request $request)
    {
        try {
            $request->validate(['class_id' => 'required|exists:schoolclass,id', 'term_id' => 'required|exists:schoolterm,id', 'session_id' => 'required|exists:schoolsession,id']);
            $classId   = (int) $request->class_id; $termId = (int) $request->term_id; $sessionId = (int) $request->session_id;
            $studentRows = DB::table('studentRegistration as sr')->join('studentclass as sc', 'sc.studentId', '=', 'sr.id')->leftJoin('studentpicture as sp', 'sp.studentid', '=', 'sr.id')->where('sc.schoolclassid', $classId)->where('sc.sessionid', $sessionId)->select('sr.id as student_id', 'sr.admissionNo as admissionno', 'sr.firstname', 'sr.lastname', 'sr.othername', 'sp.picture')->orderBy('sr.lastname')->orderBy('sr.firstname')->get();
            if ($studentRows->isEmpty()) return response()->json(['success' => false, 'message' => "No students found for class_id={$classId}, session_id={$sessionId}."]);
            $schoolclass = Schoolclass::with('classcategories', 'armRelation')->find($classId);
            $isSenior    = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? ($schoolclass->classcategories->first()->is_senior ?? false) : false;
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) { $categoryIds = $schoolclass->classcategories->pluck('id'); $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->orderBy('id')->get(); }
            $allSubjectClassRows = DB::table('subjectclass as sjc')->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')->join('subject as s', 's.id', '=', 'st.subjectid')->where('sjc.schoolclassid', $classId)->where('st.termid', $termId)->where('st.sessionid', $sessionId)->select('s.id as subject_id', 's.subject as subject_name', 's.subject_code', 'sjc.id as subjectclass_id', 'st.staffid as staff_id')->distinct()->get()->keyBy('subjectclass_id');
            $results = [];
            foreach ($studentRows as $student) {
                $sid = (int) $student->student_id;
                $registeredSubjectclassIds = DB::table('subjectRegistrationStatus')->where('studentid', $sid)->where('termid', $termId)->where('sessionid', $sessionId)->whereIn('Status', ['active', 'Active', 'ACTIVE', '1'])->where(function ($q) use ($classId) { $q->whereIn('subjectclassid', DB::table('subjectclass')->where('schoolclassid', $classId)->pluck('id')); })->pluck('subjectclassid')->map(fn($v) => (int) $v)->unique()->values();
                if ($registeredSubjectclassIds->isEmpty()) $registeredSubjectclassIds = $allSubjectClassRows->keys()->map(fn($v) => (int) $v)->values();
                $studentSubjects = []; $totalScores = 0;
                foreach ($registeredSubjectclassIds as $sjcId) {
                    $subjInfo = $allSubjectClassRows->get($sjcId); if (!$subjInfo) continue;
                    $subjectId = (int) $subjInfo->subject_id; $subjectclassId = (int) $sjcId; $staffId = (int) ($subjInfo->staff_id ?? auth()->id());
                    $broadsheetRecord = BroadsheetRecord::firstOrCreate(['student_id' => $sid, 'subject_id' => $subjectId, 'schoolclass_id' => $classId, 'session_id' => $sessionId]);
                    $broadsheet = Broadsheets::firstOrCreate(['broadSheet_record_id' => $broadsheetRecord->id, 'term_id' => $termId, 'subjectclass_id' => $subjectclassId], ['staff_id' => $staffId, 'entered_by' => auth()->id(), 'entered_at' => now(), 'entry_source' => 'admin_student_manager']);
                    $assessmentScores = []; $totalRaw = 0.0;
                    foreach ($assessments as $assessment) {
                        $scoreRec = BroadsheetAssessmentScore::where(['broadsheet_id' => $broadsheet->id, 'assessment_id' => $assessment->id])->first();
                        $scoreValue = $scoreRec ? (float) $scoreRec->score : 0.0;
                        $assessmentScores[] = ['assessment_id' => (int) $assessment->id, 'assessment_name' => $assessment->name, 'max_score' => (float) $assessment->max_score, 'score' => $scoreValue];
                        $totalRaw += $scoreValue;
                    }
                    $totalRaw = round($totalRaw, 2);
                    $bf       = $this->getPreviousTermCum($sid, $subjectId, $termId, $sessionId);

                    // Raw cumulative sum (bf + this term's total) and its per-term average.
                    // "cum" in the response below is the AVERAGED figure (what teachers/
                    // parents expect to see), matching the historical meaning of "cum".
                    $cumData = $this->computeCumulative($totalRaw, $bf, $termId);
                    $cum     = $cumData['cum'];      // raw running sum -> stored in broadsheets.cum
                    $cumAve  = $cumData['cum_ave'];  // divided value  -> stored in broadsheets.cum_ave

                    $grade    = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->calculateGrade($totalRaw) : $this->getDefaultGrade($totalRaw);
                    $remark   = $this->getRemark($grade);
                    if (abs((float) $broadsheet->total - $totalRaw) > 0.01 || abs((float) $broadsheet->bf - $bf) > 0.01 || abs((float) $broadsheet->cum - $cum) > 0.01 || abs((float) $broadsheet->cum_ave - $cumAve) > 0.01 || $broadsheet->grade !== $grade || $broadsheet->remark !== $remark) { $broadsheet->total = $totalRaw; $broadsheet->bf = $bf; $broadsheet->cum = $cum; $broadsheet->cum_ave = $cumAve; $broadsheet->grade = $grade; $broadsheet->remark = $remark; $broadsheet->last_modified_by = auth()->id(); $broadsheet->last_modified_at = now(); $broadsheet->save(); }
                    $totalScores += $totalRaw;
                    $studentSubjects[] = ['subject_id' => $subjectId, 'subject_name' => $subjInfo->subject_name, 'subject_code' => $subjInfo->subject_code ?? '', 'subjectclass_id' => $subjectclassId, 'broadsheet_id' => (int) $broadsheet->id, 'total' => $totalRaw, 'bf' => (float) $bf, 'cum' => (float) $cum, 'cum_ave' => (float) $cumAve, 'grade' => $grade, 'remark' => $remark, 'assessment_scores' => $assessmentScores];
                }
                $numSubj = count($studentSubjects); $average = $numSubj > 0 ? round($totalScores / $numSubj, 2) : 0.0;
                $gradePoints  = array_map(fn($sub) => $this->getGradePoint($sub['total'], $isSenior), $studentSubjects);
                $gpa          = !empty($gradePoints) ? round(array_sum($gradePoints) / count($gradePoints), 2) : 0.0;
                $averageGrade = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->calculateGrade($average) : $this->getDefaultGrade($average);
                $photoUrl     = asset('storage/student_avatars/unnamed.jpg');
                if (!empty($student->picture)) $photoUrl = asset('storage/student_avatars/' . basename($student->picture));
                $results[] = ['student_id' => $sid, 'admission_no' => $student->admissionno ?? '', 'full_name' => trim(($student->lastname ?? '') . ' ' . ($student->firstname ?? '') . ' ' . ($student->othername ?? '')), 'firstname' => $student->firstname ?? '', 'lastname' => $student->lastname ?? '', 'photo' => $photoUrl, 'subjects' => $studentSubjects, 'average' => $average, 'average_grade' => $averageGrade, 'gpa' => $gpa, 'total_subjects' => $numSubj];
            }
            return response()->json(['success' => true, 'assessments' => $assessments, 'data' => $results, 'debug' => ['student_count' => count($results), 'assessment_count' => $assessments->count(), 'class_subjects' => $allSubjectClassRows->count()]]);
        } catch (\Exception $e) {
            Log::error('getStudentResults: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function updateStudentSubjectScore(Request $request)
    {
        try {
            $request->validate(['student_id' => 'required|exists:studentRegistration,id', 'subject_id' => 'required|exists:subject,id', 'subjectclass_id' => 'required|exists:subjectclass,id', 'term_id' => 'required|exists:schoolterm,id', 'session_id' => 'required|exists:schoolsession,id', 'class_id' => 'required|exists:schoolclass,id', 'scores' => 'required|array', 'scores.*.assessment_id' => 'required|exists:assessments,id', 'scores.*.score' => 'required|numeric|min:0']);
            $studentId = (int) $request->student_id; $subjectId = (int) $request->subject_id; $subjectclassId = (int) $request->subjectclass_id; $termId = (int) $request->term_id; $sessionId = (int) $request->session_id; $classId = (int) $request->class_id;
            $staffId   = DB::table('subjectclass as sjc')->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')->where('sjc.id', $subjectclassId)->value('st.staffid') ?? auth()->id();
            DB::beginTransaction();
            $broadsheetRecord = BroadsheetRecord::firstOrCreate(['student_id' => $studentId, 'subject_id' => $subjectId, 'schoolclass_id' => $classId, 'session_id' => $sessionId]);
            $broadsheet = Broadsheets::firstOrCreate(['broadSheet_record_id' => $broadsheetRecord->id, 'term_id' => $termId, 'subjectclass_id' => $subjectclassId], ['staff_id' => $staffId, 'entered_by' => auth()->id(), 'entered_at' => now(), 'entry_source' => 'admin_student_manager']);
            $totalRaw = 0.0;
            foreach ($request->scores as $scoreData) {
                $assessment = Assessment::find($scoreData['assessment_id']); $maxScore = $assessment ? (float) $assessment->max_score : 100.0; $scoreVal = min(max((float) $scoreData['score'], 0), $maxScore);
                BroadsheetAssessmentScore::updateOrCreate(['broadsheet_id' => $broadsheet->id, 'assessment_id' => (int) $scoreData['assessment_id']], ['score' => $scoreVal]);
                $totalRaw += $scoreVal;
            }
            $totalRaw    = round($totalRaw, 2);
            $schoolclass = Schoolclass::with('classcategories')->find($classId);
            $bf          = $this->getPreviousTermCum($studentId, $subjectId, $termId, $sessionId);

            // Raw cumulative sum + averaged value, per the new cumulative rules.
            $cumData = $this->computeCumulative($totalRaw, $bf, $termId);
            $cum     = $cumData['cum'];
            $cumAve  = $cumData['cum_ave'];

            $grade       = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->calculateGrade($totalRaw) : $this->getDefaultGrade($totalRaw);
            $remark      = $this->getRemark($grade);
            $broadsheet->total = $totalRaw; $broadsheet->bf = $bf; $broadsheet->cum = $cum; $broadsheet->cum_ave = $cumAve; $broadsheet->grade = $grade; $broadsheet->remark = $remark; $broadsheet->last_modified_by = auth()->id(); $broadsheet->last_modified_at = now(); $broadsheet->save();
            DB::commit();
            $this->updateSubjectPositions($subjectclassId, $staffId, $termId, $sessionId);
            return response()->json(['success' => true, 'message' => 'Score updated successfully!', 'data' => ['subject_id' => $subjectId, 'broadsheet_id' => $broadsheet->id, 'total' => $totalRaw, 'bf' => (float) $bf, 'cum' => (float) $cum, 'cum_ave' => (float) $cumAve, 'grade' => $grade, 'remark' => $remark, 'assessment_scores' => $request->scores]]);
        } catch (\Exception $e) {
            DB::rollBack(); Log::error('updateStudentSubjectScore: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkUpdateStudentScores(Request $request)
    {
        try {
            $request->validate(['student_id' => 'required|exists:studentRegistration,id', 'class_id' => 'required|exists:schoolclass,id', 'term_id' => 'required|exists:schoolterm,id', 'session_id' => 'required|exists:schoolsession,id', 'subjects' => 'required|array', 'subjects.*.subject_id' => 'required|exists:subject,id', 'subjects.*.subjectclass_id' => 'required|exists:subjectclass,id', 'subjects.*.scores' => 'required|array']);
            $updatedSubjects = []; $errors = [];
            foreach ($request->subjects as $subjectData) {
                $subRequest = new Request(['student_id' => $request->student_id, 'subject_id' => $subjectData['subject_id'], 'subjectclass_id' => $subjectData['subjectclass_id'], 'term_id' => $request->term_id, 'session_id' => $request->session_id, 'class_id' => $request->class_id, 'scores' => $subjectData['scores']]);
                $result = $this->updateStudentSubjectScore($subRequest);
                $body   = json_decode($result->getContent(), true);
                if (!empty($body['success'])) $updatedSubjects[] = $body['data'];
                else $errors[] = 'Subject ' . $subjectData['subject_id'] . ': ' . ($body['message'] ?? 'unknown error');
            }
            if (!empty($errors) && empty($updatedSubjects)) return response()->json(['success' => false, 'message' => implode('; ', $errors)], 500);
            return response()->json(['success' => true, 'message' => count($updatedSubjects) . ' subject(s) saved!', 'data' => $updatedSubjects, 'warnings' => $errors ?: null]);
        } catch (\Exception $e) {
            Log::error('bulkUpdateStudentScores: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // QUERY HELPERS
    // =========================================================================

    protected function getBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = Broadsheets::query()
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->with(['assessmentScores', 'subAssessmentScores', 'enteredBy', 'lastModifiedBy', 'lockedBy', 'unlockScheduledBy'])
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                    ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) $join->where('subjectclass.id', $subjectClassId);
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $sessionId)
            ->orderBy('studentRegistration.lastname', 'asc')
            ->orderBy('studentRegistration.firstname', 'asc');
        if ($schoolClassId) $query->where('schoolclass.id', $schoolClassId);
        return $query->get([
            'broadsheets.id', 'studentRegistration.admissionNO as admissionno', 'broadsheet_records.student_id as student_id',
            'studentRegistration.firstname as fname', 'studentRegistration.lastname as lname', 'studentRegistration.othername as mname',
            'subject.subject as subject', 'subject.subject_code as subject_code', 'broadsheet_records.subject_id',
            'schoolclass.schoolclass', 'schoolclass.id as schoolclass_id', 'schoolclass.classcategoryid',
            'schoolarm.arm', 'schoolarm.id as arm_id', 'schoolterm.term', 'schoolsession.session',
            'subjectclass.id as subjectclid', 'broadsheets.staff_id', 'broadsheets.term_id',
            'broadsheet_records.session_id as sessionid', 'studentpicture.picture',
            'broadsheets.total', 'broadsheets.bf',
            // Both cumulative figures are returned under their real names:
            // "cum"     = raw running sum (BF + this term's total)
            // "cum_ave" = that sum divided by the term number (what's shown as "Cum Ave")
            'broadsheets.cum',
            'broadsheets.cum_ave',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position', 'broadsheets.subject_position_class_total as position_total',
            'broadsheets.arm_position', 'broadsheets.arm_position_cum', 'broadsheets.remark', 'broadsheets.vettedstatus',
            'broadsheets.avg', 'broadsheets.cmin', 'broadsheets.cmax', 'broadsheets.is_locked', 'broadsheets.lock_reason',
            'broadsheets.entered_at', 'broadsheets.last_modified_at', 'broadsheets.entry_source',
            'broadsheets.entered_by', 'broadsheets.last_modified_by', 'broadsheets.scheduled_unlock_at',
        ]);
    }

    protected function getMockBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = \App\Models\BroadsheetsMock::query()
            ->where('broadsheetmock.staff_id', $staffId)
            ->where('broadsheetmock.term_id', $termId)
            ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheetmock.subjectclass_id')
                    ->on('broadsheet_records_mock.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records_mock.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) $join->where('subjectclass.id', $subjectClassId);
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records_mock.schoolclass_id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->where('broadsheet_records_mock.session_id', $sessionId)
            ->orderBy('studentRegistration.lastname', 'asc')
            ->orderBy('studentRegistration.firstname', 'asc');
        if ($schoolClassId) $query->where('schoolclass.id', $schoolClassId);
        return $query->get([
            'broadsheetmock.id', 'studentRegistration.admissionNO as admissionno', 'broadsheet_records_mock.student_id as student_id',
            'studentRegistration.firstname as fname', 'studentRegistration.lastname as lname', 'studentRegistration.othername as mname',
            'subject.subject as subject', 'subject.subject_code as subject_code', 'broadsheet_records_mock.subject_id',
            'schoolclass.schoolclass', 'schoolclass.id as schoolclass_id', 'schoolarm.arm',
            'schoolterm.term', 'schoolsession.session', 'subjectclass.id as subjectclid',
            'broadsheetmock.staff_id', 'broadsheetmock.term_id', 'broadsheet_records_mock.session_id as sessionid',
            'studentpicture.picture', 'broadsheetmock.exam', 'broadsheetmock.total', 'broadsheetmock.grade',
            'broadsheetmock.subject_position_class as position', 'broadsheetmock.remark',
            'broadsheetmock.cmin', 'broadsheetmock.cmax', 'broadsheetmock.avg', 'broadsheetmock.vettedstatus',
        ]);
    }

    protected function formatBroadsheetsForResponse($broadsheets, $assessments)
    {
        $formatted = [];
        foreach ($broadsheets as $b) {
            $assessmentScores = [];
            foreach ($assessments as $a) {
                $s = $b->assessmentScores->where('assessment_id', $a->id)->first();
                $assessmentScores[] = ['assessment_id' => $a->id, 'assessment_name' => $a->name, 'max_score' => $a->max_score, 'score' => $s ? floatval($s->score) : 0];
            }
            $formatted[] = ['id' => $b->id, 'admissionno' => $b->admissionno, 'fname' => $b->fname, 'lname' => $b->lname, 'mname' => $b->mname, 'total' => floatval($b->total), 'bf' => floatval($b->bf), 'cum' => floatval($b->cum), 'cum_ave' => floatval($b->cum_ave ?? 0), 'grade' => $b->grade, 'position' => $b->position, 'position_total' => $b->position_total, 'arm_position' => $b->arm_position, 'arm_position_cum' => $b->arm_position_cum, 'remark' => $b->remark, 'avg' => floatval($b->avg ?? 0), 'assessment_scores' => $assessmentScores];
        }
        return $formatted;
    }

    // =========================================================================
    // POSITION / METRICS HELPERS
    // =========================================================================

    protected function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')->where('id', $subjectclassid)->first(['subjectteacherid']); if (!$subjectClass) return;
        $subjectTeacher = DB::table('subjectteacher')->where('id', $subjectClass->subjectteacherid)->first(['subjectid']); if (!$subjectTeacher) return;
        $subjectId = $subjectTeacher->subjectid;
        // NOTE: min/max/avg here are computed from cum_ave (the divided figure), not the
        // raw running-sum "cum" column — otherwise these numbers would balloon term-on-term
        // even though "Ave remains Average of each subject per class" should stay stable.
        $metrics = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassid)->where('broadsheets.staff_id', $staffid)->where('broadsheets.term_id', $termid)->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')->where('broadsheet_records.session_id', $sessionid)->where('broadsheet_records.subject_id', $subjectId)->select([DB::raw('MIN(broadsheets.cum_ave) as class_min'), DB::raw('MAX(broadsheets.cum_ave) as class_max'), DB::raw('SUM(broadsheets.cum_ave) as cum_sum'), DB::raw('COUNT(broadsheets.id) as student_count')])->first();
        $classMin = $metrics->class_min ?? 0; $classMax = $metrics->class_max ?? 0; $classAvg = $metrics->student_count > 0 ? round($metrics->cum_sum / $metrics->student_count, 1) : 0;
        Broadsheets::where('subjectclass_id', $subjectclassid)->where('staff_id', $staffid)->where('term_id', $termid)->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')->where('broadsheet_records.session_id', $sessionid)->where('broadsheet_records.subject_id', $subjectId)->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        // NOTE: ranking still reads the RAW running-sum "broadsheets.cum" column below.
        // This is intentional and needs no change: every row being ranked here is for the
        // SAME term_id, so every student's cum_ave = cum / term_id is divided by the exact
        // same constant. Dividing by a shared constant never changes relative ordering, so
        // ranking by raw cum produces identical positions to ranking by cum_ave.
        Log::info('[Admin updateSubjectPositions] START', [
            'subjectclass_id' => $subjectclass_id,
            'term_id'         => $term_id,
            'session_id'      => $session_id,
        ]);

        $subjectClass = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->where('subjectclass.id', $subjectclass_id)
            ->first(['subjectclass.schoolclassid', 'subjectteacher.subjectid']);

        if (!$subjectClass) {
            Log::warning('[Admin updateSubjectPositions] subjectClass not found', compact('subjectclass_id'));
            return;
        }

        $subjectId     = $subjectClass->subjectid;
        $schoolclassId = $subjectClass->schoolclassid;

        $baseClass = DB::table('schoolclass')
            ->where('id', $schoolclassId)
            ->first(['schoolclass', 'classcategoryid']);

        if (!$baseClass) {
            Log::warning('[Admin updateSubjectPositions] baseClass not found', compact('schoolclassId'));
            return;
        }

        $allArmIds = DB::table('schoolclass')
            ->where('schoolclass', $baseClass->schoolclass)
            ->where('classcategoryid', $baseClass->classcategoryid)
            ->pluck('id');

        $allSubjectClassIds = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->whereIn('subjectclass.schoolclassid', $allArmIds)
            ->where('subjectteacher.subjectid', $subjectId)
            ->pluck('subjectclass.id');

        $allStudents = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->whereIn('broadsheets.subjectclass_id', $allSubjectClassIds)
            ->where('broadsheets.term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->whereExists(function ($query) use ($term_id, $session_id) {
                $query->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass as srs_sc', 'srs_sc.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->whereColumn('srs_sc.subjectid', 'broadsheet_records.subject_id')
                    ->whereColumn('subjectRegistrationStatus.studentid', 'broadsheet_records.student_id')
                    ->where('subjectRegistrationStatus.termid', $term_id)
                    ->where('subjectRegistrationStatus.sessionid', $session_id)
                    ->where('subjectRegistrationStatus.Status', 1);
            })
            ->get([
                'broadsheets.id',
                'broadsheets.cum',
                'broadsheets.total',
                'broadsheet_records.schoolclass_id',
                'broadsheet_records.student_id',
            ]);

        if ($allStudents->isEmpty()) {
            $this->nullOutStalePositions($allSubjectClassIds, $term_id, $session_id);
            return;
        }

        $lastVal = null;
        $currentRank = 0;
        foreach ($allStudents->sortByDesc('cum')->values() as $idx => $b) {
            if ($lastVal === null || $b->cum != $lastVal) {
                $currentRank = $idx + 1;
                $lastVal = $b->cum;
            }
            DB::table('broadsheets')->where('id', $b->id)->update(['subject_position_class' => $currentRank]);
        }

        $lastVal = null;
        $currentRank = 0;
        foreach ($allStudents->sortByDesc('total')->values() as $idx => $b) {
            if ($lastVal === null || $b->total != $lastVal) {
                $currentRank = $idx + 1;
                $lastVal = $b->total;
            }
            DB::table('broadsheets')->where('id', $b->id)->update(['subject_position_class_total' => $currentRank]);
        }

        foreach ($allStudents->groupBy('schoolclass_id') as $armClassId => $studentsInArm) {
            $lastVal = null;
            $currentRank = 0;
            foreach ($studentsInArm->sortByDesc('total')->values() as $idx => $b) {
                if ($lastVal === null || $b->total != $lastVal) {
                    $currentRank = $idx + 1;
                    $lastVal = $b->total;
                }
                DB::table('broadsheets')->where('id', $b->id)->update(['arm_position' => $currentRank]);
            }

            $lastVal = null;
            $currentRank = 0;
            foreach ($studentsInArm->sortByDesc('cum')->values() as $idx => $b) {
                if ($lastVal === null || $b->cum != $lastVal) {
                    $currentRank = $idx + 1;
                    $lastVal = $b->cum;
                }
                DB::table('broadsheets')->where('id', $b->id)->update(['arm_position_cum' => $currentRank]);
            }
        }

        $this->nullOutStalePositions($allSubjectClassIds, $term_id, $session_id);
    }

    protected function nullOutStalePositions($subjectClassIds, $term_id, $session_id)
    {
        DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->whereIn('broadsheets.subjectclass_id', $subjectClassIds)
            ->where('broadsheets.term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->whereNotExists(function ($query) use ($term_id, $session_id) {
                $query->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass as srs_sc', 'srs_sc.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->whereColumn('srs_sc.subjectid', 'broadsheet_records.subject_id')
                    ->whereColumn('subjectRegistrationStatus.studentid', 'broadsheet_records.student_id')
                    ->where('subjectRegistrationStatus.termid', $term_id)
                    ->where('subjectRegistrationStatus.sessionid', $session_id)
                    ->where('subjectRegistrationStatus.Status', 1);
            })
            ->update([
                'subject_position_class'       => null,
                'subject_position_class_total' => null,
                'arm_position'                 => null,
                'arm_position_cum'             => null,
            ]);
    }

    protected function updateClassPositions($schoolclassid, $termid, $sessionid)
    {
        $rank = 0; $lastScore = null; $rows = 0;
        $pos = PromotionStatus::where('schoolclassid', $schoolclassid)->where('termid', $termid)->where('sessionid', $sessionid)->orderBy('subjectstotalscores', 'DESC')->get();
        foreach ($pos as $row) {
            $rows++;
            if ($lastScore !== $row->subjectstotalscores) { $lastScore = $row->subjectstotalscores; $rank = $rows; }
            $suffix = 'th'; if ($rank == 1) $suffix = 'st'; elseif ($rank == 2) $suffix = 'nd'; elseif ($rank == 3) $suffix = 'rd';
            PromotionStatus::where('id', $row->id)->update(['position' => $rank . $suffix]);
        }
    }

    protected function updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')->where('id', $subjectclassid)->first(['subjectteacherid']); if (!$subjectClass) return;
        $subjectTeacher = DB::table('subjectteacher')->where('id', $subjectClass->subjectteacherid)->first(['subjectid']); if (!$subjectTeacher) return;
        $subjectId = $subjectTeacher->subjectid;
        $metrics = \App\Models\BroadsheetsMock::query()->where('broadsheetmock.subjectclass_id', $subjectclassid)->where('broadsheetmock.staff_id', $staffid)->where('broadsheetmock.term_id', $termid)->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')->where('broadsheet_records_mock.session_id', $sessionid)->where('broadsheet_records_mock.subject_id', $subjectId)->select([DB::raw('MIN(broadsheetmock.total) as class_min'), DB::raw('MAX(broadsheetmock.total) as class_max'), DB::raw('SUM(broadsheetmock.total) as total_sum'), DB::raw('COUNT(broadsheetmock.id) as student_count')])->first();
        $classMin = $metrics->class_min ?? 0; $classMax = $metrics->class_max ?? 0; $classAvg = $metrics->student_count > 0 ? round((float) $metrics->total_sum / $metrics->student_count, 1) : 0;
        $ids = \App\Models\BroadsheetsMock::query()->where('broadsheetmock.subjectclass_id', $subjectclassid)->where('broadsheetmock.staff_id', $staffid)->where('broadsheetmock.term_id', $termid)->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')->where('broadsheet_records_mock.session_id', $sessionid)->where('broadsheet_records_mock.subject_id', $subjectId)->pluck('broadsheetmock.id');
        \App\Models\BroadsheetsMock::whereIn('id', $ids)->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }

    protected function updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        $broadsheets = \App\Models\BroadsheetsMock::query()->where('broadsheetmock.subjectclass_id', $subjectclass_id)->where('broadsheetmock.staff_id', $staff_id)->where('broadsheetmock.term_id', $term_id)->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')->where('broadsheet_records_mock.session_id', $session_id)->orderByDesc('broadsheetmock.total')->orderBy('broadsheetmock.id')->get(['broadsheetmock.id', 'broadsheetmock.total', 'broadsheetmock.subject_position_class']);
        if ($broadsheets->isEmpty()) return;
        $rank = 0; $lastTotal = null; $lastPosition = 0;
        foreach ($broadsheets as $b) { $rank++; if ($lastTotal === null || $b->total != $lastTotal) { $lastPosition = $rank; $lastTotal = $b->total; } if ($b->subject_position_class != $lastPosition) \App\Models\BroadsheetsMock::where('id', $b->id)->update(['subject_position_class' => $lastPosition]); }
    }

    // =========================================================================
    // GPA / CGPA HELPERS
    // =========================================================================

    protected function computeOverallGPAAndCGPA($broadsheets, $schoolclass, $termId, $sessionId)
    {
        if (!$schoolclass || $schoolclass->classcategories->isEmpty()) return;
        $isSenior = $schoolclass->classcategories->first()->is_senior ?? false;
        foreach ($broadsheets as $b) {
            $data = $this->computeOverallForStudent($b->student_id, $schoolclass, $termId, $sessionId, $isSenior);
            $b->gpa = round($data['gpa'], 2); $b->cgpa = round($data['cgpa'], 2); $b->gpa_grade = $data['gpa_grade'] ?? 'F'; $b->num_subjects = $data['num_subjects'] ?? 0; $b->total_grade_points = $data['total_grade_points'] ?? 0.0;
        }
    }

    protected function computeOverallForStudent($studentId, $schoolclass, $termId, $sessionId, $isSenior)
    {
        $currentBroadsheets = Broadsheets::where('broadsheets.term_id', $termId)->whereHas('broadsheetRecord', fn($q) => $q->where('student_id', $studentId)->where('session_id', $sessionId))->whereExists(function ($query) use ($studentId, $termId, $sessionId) { $query->select(DB::raw(1))->from('subjectRegistrationStatus')->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')->where('subjectRegistrationStatus.studentid', $studentId)->where('subjectRegistrationStatus.termid', $termId)->where('subjectRegistrationStatus.sessionid', $sessionId); })->get(['broadsheets.total']);
        $category = $schoolclass->classcategories->first(); $averageTotal = $currentBroadsheets->avg('total') ?? 0.0;
        $gpaGrade = $category ? $category->calculateGrade($averageTotal) : $this->getDefaultGrade($averageTotal);
        $termGradePoints = $currentBroadsheets->map(fn($b) => $this->getGradePoint($b->total, $isSenior));
        $gpa = $termGradePoints->avg() ?? 0.0; $numSubjects = $currentBroadsheets->count(); $totalGradePoints = $termGradePoints->sum();
        $annualGPAs = [];
        $sessions = DB::table('broadsheet_records')->join('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')->join('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')->where('broadsheet_records.student_id', $studentId)->where('classcategories.is_senior', $isSenior)->select('broadsheet_records.session_id')->distinct()->orderByDesc('broadsheet_records.session_id')->limit(3)->pluck('session_id');
        foreach ($sessions as $targetSession) {
            $sessionGPAs = [];
            for ($t = 1; $t <= 3; $t++) {
                $tb = Broadsheets::where('broadsheets.term_id', $t)->whereHas('broadsheetRecord', fn($q) => $q->where('student_id', $studentId)->where('session_id', $targetSession))->whereExists(function ($query) use ($studentId, $t, $targetSession) { $query->select(DB::raw(1))->from('subjectRegistrationStatus')->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')->where('subjectRegistrationStatus.studentid', $studentId)->where('subjectRegistrationStatus.termid', $t)->where('subjectRegistrationStatus.sessionid', $targetSession); })->get(['broadsheets.total']);
                $sessionGPAs[] = $tb->map(fn($b) => $this->getGradePoint($b->total, $isSenior))->avg() ?? 0.0;
            }
            $annualGPAs[] = collect($sessionGPAs)->avg() ?? 0.0;
        }
        return ['gpa' => $gpa, 'cgpa' => collect($annualGPAs)->avg() ?? 0.0, 'gpa_grade' => $gpaGrade, 'num_subjects' => $numSubjects, 'total_grade_points' => $totalGradePoints];
    }

    protected function getGradePoint($score, $isSenior = false)
    {
        if (!$isSenior) { if ($score >= 70) return 5.0; if ($score >= 60) return 4.0; if ($score >= 50) return 3.0; if ($score >= 40) return 2.0; return 0.0; }
        if ($score >= 75) return 5.0; if ($score >= 65) return 4.0; if ($score >= 50) return 3.0; if ($score >= 45) return 2.0; if ($score >= 40) return 1.0; return 0.0;
    }

    /**
     * Compute the raw cumulative sum and the cumulative average for a term.
     *
     * Rules (per term):
     *   Term 1: bf = 0                         -> cum = total     -> cum_ave = cum / 1
     *   Term 2: bf = Term 1's raw cum           -> cum = bf+total  -> cum_ave = cum / 2
     *   Term 3: bf = Term 2's raw cum           -> cum = bf+total  -> cum_ave = cum / 3
     *
     * "cum" is the raw running SUM across terms (BF + current term's total).
     * "cum_ave" is that sum divided by the term number — this is what used to be
     * (misleadingly) stored in the old "cum" column and is what should be DISPLAYED
     * to users. BF for the following term is always taken from the previous term's
     * raw "cum" (see getPreviousTermCum()), never from cum_ave.
     */
    protected function computeCumulative(float $totalRaw, float $bf, int $termId): array
    {
        $cum    = round($totalRaw + $bf, 2);
        $cumAve = $termId > 0 ? round($cum / $termId, 2) : $cum;

        return ['cum' => $cum, 'cum_ave' => $cumAve];
    }

    protected function computeDynamicTotals($broadsheets, $assessments, $schoolclass, $termId, $sessionId)
    {
        foreach ($broadsheets as $broadsheet) {
            $assessmentScores = $broadsheet->assessmentScores ?? collect(); $totalRaw = 0;
            foreach ($assessments as $a) { $scoreObj = $assessmentScores->where('assessment_id', $a->id)->first(); $totalRaw += $scoreObj ? (float) $scoreObj->score : 0; }
            $subjectId = $broadsheet->subject_id;
            if (!$subjectId) $subjectId = DB::table('broadsheet_records')->where('id', $broadsheet->broadSheet_record_id ?? $broadsheet->broadsheet_record_id)->value('subject_id');
            $newBf     = $this->getPreviousTermCum($broadsheet->student_id, $subjectId, $termId, $sessionId);

            // Raw sum + its per-term average, per the new cumulative rules above.
            $cumData   = $this->computeCumulative($totalRaw, $newBf, $termId);
            $newCum    = $cumData['cum'];      // raw running sum
            $newCumAve = $cumData['cum_ave'];  // divided value shown to users

            $newGrade  = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->calculateGrade($totalRaw) : $this->getDefaultGrade($totalRaw);
            $newRemark = $this->getRemark($newGrade);
            $changed   = abs($broadsheet->total - $totalRaw) > 0.001 || abs($broadsheet->bf - $newBf) > 0.001 || abs($broadsheet->cum - $newCum) > 0.001 || abs($broadsheet->cum_ave - $newCumAve) > 0.001 || $broadsheet->grade !== $newGrade || $broadsheet->remark !== $newRemark;
            if ($changed) { $broadsheet->total = $totalRaw; $broadsheet->bf = $newBf; $broadsheet->cum = $newCum; $broadsheet->cum_ave = $newCumAve; $broadsheet->grade = $newGrade; $broadsheet->remark = $newRemark; $broadsheet->save(); }
        }
    }

    protected function getDefaultGrade($score)
    {
        if ($score >= 70) return 'A'; if ($score >= 60) return 'B'; if ($score >= 50) return 'C'; if ($score >= 40) return 'D'; return 'F';
    }

    protected function getRemark($grade)
    {
        return match ($grade) {
            'A', 'A1' => 'Excellent', 'B', 'B2', 'B3' => 'Very Good', 'C', 'C4', 'C5', 'C6' => 'Good', 'D', 'D7', 'E8' => 'Pass', default => 'Fail',
        };
    }

    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) return 0;
        // NOTE: reads the RAW running-sum "cum" column (not cum_ave), because BF for the
        // next term must be the previous term's raw total sum, not its per-term average.
        $prev = DB::table('broadsheets')->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')->where('broadsheet_records.student_id', $studentId)->where('broadsheet_records.subject_id', $subjectId)->where('broadsheet_records.session_id', $sessionId)->where('broadsheets.term_id', $termId - 1)->value('broadsheets.cum');
        return $prev !== null ? round((float) $prev, 2) : 0;
    }

    // =========================================================================
    // Utility: clean up temp directory
    // =========================================================================

    protected function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->cleanDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    // =========================================================================
    // BULK EXPORT PDF — multiple scoresheets → PDF(s), zipped when > 1
    // =========================================================================

    public function bulkExportPdf(Request $request)
    {
        $request->validate([
            'subjects'                   => 'required|array|min:1',
            'subjects.*.subjectclass_id' => 'required|exists:subjectclass,id',
            'subjects.*.teacher_id'      => 'required|exists:users,id',
            'subjects.*.schoolclass_id'  => 'required|exists:schoolclass,id',
            'subjects.*.term_id'         => 'required|exists:schoolterm,id',
            'subjects.*.session_id'      => 'required|exists:schoolsession,id',
        ]);

        $subjects  = $request->input('subjects');
        $school    = \App\Models\SchoolInformation::first();
        $tempDir   = storage_path('app/temp');
        $isSingle  = count($subjects) === 1;

        try {
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $pdfFiles  = [];
            $generated = 0;

            foreach ($subjects as $subjectData) {
                $subjectclassId = (int) $subjectData['subjectclass_id'];
                $teacherId      = (int) $subjectData['teacher_id'];
                $schoolclassId  = (int) $subjectData['schoolclass_id'];
                $termId         = (int) $subjectData['term_id'];
                $sessionId      = (int) $subjectData['session_id'];

                $hasScores = DB::table('broadsheets')
                    ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                    ->where('broadsheets.subjectclass_id', $subjectclassId)
                    ->where('broadsheets.term_id', $termId)
                    ->where('broadsheet_records.session_id', $sessionId)
                    ->exists();

                if (!$hasScores) {
                    Log::info("[AdminBulkExportPdf] skipping subjectclass {$subjectclassId} — no scores");
                    continue;
                }

                $broadsheets = $this->getBroadsheets(
                    $teacherId, $termId, $sessionId, $schoolclassId, $subjectclassId
                );

                if ($broadsheets->isEmpty()) {
                    continue;
                }

                $schoolclass = Schoolclass::with('classcategories')->find($schoolclassId);
                $assessments = collect();
                if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                    $categoryIds = $schoolclass->classcategories->pluck('id');
                    $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                        ->with('subAssessments')
                        ->orderBy('id')
                        ->get();
                }

                $teacher     = User::find($teacherId);
                $teacherName = $teacher ? $teacher->name : '';

                $first       = $broadsheets->first();
                $subjectSlug = preg_replace('/[^a-zA-Z0-9-]/', '_', $first->subject ?? 'subject');
                $classSlug   = preg_replace('/[^a-zA-Z0-9-]/', '_',
                    trim(($first->schoolclass ?? '') . ' ' . ($first->arm ?? ''))
                );
                $termSlug    = preg_replace('/[^a-zA-Z0-9-]/', '_', $first->term    ?? 'term');
                $sessionSlug = preg_replace('/[^a-zA-Z0-9-]/', '_', $first->session ?? 'session');

                $filenameInZip = "admin_{$subjectSlug}_{$classSlug}_{$termSlug}_{$sessionSlug}_scoresheet.pdf";

                try {
                    $pdf = Pdf::loadView(
                        'admin.score-entry.scoresheet-pdf',
                        [
                            'broadsheets' => $broadsheets,
                            'assessments' => $assessments,
                            'classInfo'   => $first,
                            'school'      => $school,
                            'teacherName' => $teacherName,
                        ]
                    );

                    $orientation = $assessments->count() > 4 ? 'landscape' : 'portrait';
                    $pdf->setPaper('a4', $orientation);

                    $pdfContent  = $pdf->output();
                    $tempPath    = $tempDir . '/' . uniqid('pdf_') . '.pdf';
                    file_put_contents($tempPath, $pdfContent);

                    $pdfFiles[] = ['path' => $tempPath, 'filename' => $filenameInZip];
                    $generated++;

                } catch (\Exception $e) {
                    Log::error("[AdminBulkExportPdf] PDF render failed for subjectclass {$subjectclassId}: " . $e->getMessage());
                    continue;
                }
            }

            if ($generated === 0) {
                foreach ($pdfFiles as $f) { @unlink($f['path']); }
                return response()->json([
                    'success' => false,
                    'message' => 'No scoresheets could be generated. Ensure scores exist for the selected subjects.',
                ], 422);
            }

            if ($isSingle && count($pdfFiles) === 1) {
                $file        = $pdfFiles[0];
                $pdfContent  = file_get_contents($file['path']);
                @unlink($file['path']);

                return response($pdfContent, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
                    'Content-Length'      => strlen($pdfContent),
                ]);
            }

            $zipName = 'admin_scoresheets_pdf_' . now()->format('Y-m-d_His') . '.zip';
            $zipPath = $tempDir . '/' . $zipName;

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                foreach ($pdfFiles as $f) { @unlink($f['path']); }
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create ZIP archive.',
                ], 500);
            }

            foreach ($pdfFiles as $f) {
                $zip->addFile($f['path'], $f['filename']);
            }
            $zip->close();

            foreach ($pdfFiles as $f) { @unlink($f['path']); }

            return response()->download($zipPath, $zipName, [
                'Content-Type'        => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipName . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Admin bulkExportPdf error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            foreach (($pdfFiles ?? []) as $f) { @unlink($f['path']); }
            return response()->json([
                'success' => false,
                'message' => 'PDF export failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
