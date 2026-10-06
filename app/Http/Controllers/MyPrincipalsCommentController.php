<?php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\BroadsheetRecordMock;
use App\Models\Principalscomment;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Studentclass;
use App\Models\Studentpersonalityprofile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MyPrincipalsCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-principals-comment',   ['only' => ['index']]);
        $this->middleware('permission:Update my-principals-comment', ['only' => ['classBroadsheet', 'updateComments']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index()
    {
        $pagetitle = "My Principal's Comment Assignments";

        $assignments = Principalscomment::where('staffId', Auth::id())
            ->join('schoolclass', 'principalscomments.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolsession', 'principalscomments.sessionid', '=', 'schoolsession.id')
            ->leftJoin('schoolterm', 'principalscomments.termid', '=', 'schoolterm.id')
            ->select([
                'principalscomments.id',
                'schoolclass.id as schoolclassid',
                'schoolclass.schoolclass as sclass',
                'schoolarm.arm as schoolarm',
                'schoolsession.id as session_id',
                'schoolsession.session as session_name',
                'schoolterm.id as term_id',
                'schoolterm.term as term_name',
                'principalscomments.updated_at',
            ])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        return view('myprincipalscomment.index')->with(compact('assignments', 'pagetitle'));
    }

    // =========================================================================
    // CLASS BROADSHEET
    // =========================================================================

    public function classBroadsheet(Request $request, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle = "Principal's Comment & Class Broadsheet";

        // Scoring mode: 'cumulative' (default), 'term', or 'mock'
        $scoringMode = $request->get('scoring_mode', 'cumulative');
        if (!in_array($scoringMode, ['cumulative', 'term', 'mock'])) {
            $scoringMode = 'cumulative';
        }

        // Grade basis: 'cum_ave' (default) or 'total'
        $gradeBasis = $request->get('grade_basis', 'cum_ave');
        if (!in_array($gradeBasis, ['cum_ave', 'total'])) {
            $gradeBasis = 'cum_ave';
        }

        // ------------------------------------------------------------------
        // 1.  Students enrolled in this class / session
        // ------------------------------------------------------------------
        $students = Studentclass::where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get([
                'studentRegistration.id          as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname   as fname',
                'studentRegistration.lastname    as lastname',
                'studentRegistration.othername   as othername',
                'studentRegistration.gender      as gender',
                'studentpicture.picture          as picture',
            ]);

        // ------------------------------------------------------------------
        // 2. School / class meta
        // ------------------------------------------------------------------
        $schoolclass = Schoolclass::with(['arm', 'classcategories'])->findOrFail($schoolclassid);

        $armName = '';
        if ($schoolclass->arm && is_object($schoolclass->arm)) {
            $armName = $schoolclass->arm->arm ?? '';
        } elseif ($schoolclass->arms && is_object($schoolclass->arms)) {
            $armName = $schoolclass->arms->arm ?? '';
        }

        $schoolclass->full_class_name = trim($schoolclass->schoolclass . ' ' . $armName);
        $schoolterm    = Schoolterm::find($termid);
        $schooltermName = $schoolterm?->term ?? 'N/A';
        $schoolsession = Schoolsession::find($sessionid)?->session ?? 'N/A';

        $isSenior = $schoolclass->classcategories->isNotEmpty()
            ? ($schoolclass->classcategories->first()->is_senior ?? false)
            : false;

        $studentIds = $students->pluck('id')->map(fn($v) => (int)$v)->toArray();

        // ------------------------------------------------------------------
        // 3a. Terminal broadsheet rows (with proper BF/Cum like BroadsheetController)
        // ------------------------------------------------------------------
        $termScoreMap = [];
        $cumScoreMap = [];
        $cumAveMap = [];  // ← NEW: Cumulative Average
        $bfMap = [];
        // Position maps: [student_id][subject_name] => position value
        $posClassCumMap   = [];
        $posClassTotalMap = [];
        $posArmTotalMap   = [];
        $posArmCumMap     = [];

        if ($scoringMode !== 'mock') {
            // Fetch previous term cum scores for BF computation
            $prevCumMap = $this->fetchPreviousTermCums($studentIds, $sessionid, $termid, [$schoolclassid]);

            $broadsheetRows = Broadsheets::where('broadsheet_records.schoolclass_id', $schoolclassid)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'broadsheet_records.student_id',
                    'broadsheet_records.subject_id',
                    'subject.subject as subject_name',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.remark',
                    'broadsheets.subject_position_class     as pos_class_cum',
                    'broadsheets.subject_position_class_total as pos_class_total',
                    'broadsheets.arm_position               as pos_arm_total',
                    'broadsheets.arm_position_cum           as pos_arm_cum',
                ])
                ->get();

            foreach ($broadsheetRows as $row) {
                $sid     = (int)$row->student_id;
                $subj    = $row->subject_name;
                $subId   = (int)$row->subject_id;
                $rawTotal = (float)($row->total ?? 0);

                // BF resolution
                $prevCum = $prevCumMap[$sid][$subId] ?? null;
                if ($prevCum !== null && $prevCum > 0) {
                    $bf = $prevCum;
                } elseif (!empty($row->bf) && (float)$row->bf > 0) {
                    $bf = (float)$row->bf;
                } else {
                    $bf = 0.0;
                }

                // CUM: BF + Total (raw sum)
                $cum = round($bf + $rawTotal, 2);
                
                // CUM AVE: Cum ÷ term number
                $cumAve = $termid > 0 ? round($cum / $termid, 2) : $cum;

                $termScoreMap[$sid][$subj] = $rawTotal;
                $cumScoreMap[$sid][$subj]  = $cum;
                $cumAveMap[$sid][$subj]    = $cumAve;
                $bfMap[$sid][$subj]        = $bf;

                // Position maps
                $posClassCumMap[$sid][$subj]   = $row->pos_class_cum   ?? null;
                $posClassTotalMap[$sid][$subj] = $row->pos_class_total ?? null;
                $posArmTotalMap[$sid][$subj]   = $row->pos_arm_total   ?? null;
                $posArmCumMap[$sid][$subj]     = $row->pos_arm_cum     ?? null;
            }
        }

        // ------------------------------------------------------------------
        // 3b. Mock broadsheet rows
        // ------------------------------------------------------------------
        $mockScoreMap     = [];
        $mockPositionMap  = [];
        $hasMockData      = false;

        $mockRows = BroadsheetsMock::where('broadsheet_records_mock.schoolclass_id', $schoolclassid)
            ->where('broadsheetmock.term_id', $termid)
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->orderBy('subject.subject')
            ->select([
                'broadsheet_records_mock.student_id',
                'subject.subject as subject_name',
                'broadsheetmock.total',
                'broadsheetmock.grade',
                'broadsheetmock.remark',
                'broadsheetmock.subject_position_class as pos_class',
                'broadsheetmock.avg as class_avg',
                'broadsheetmock.cmin',
                'broadsheetmock.cmax',
            ])
            ->get();

        foreach ($mockRows as $row) {
            $sid  = (int)$row->student_id;
            $subj = $row->subject_name;
            $mockScoreMap[$sid][$subj]    = (float)($row->total ?? 0);
            $mockPositionMap[$sid][$subj] = $row->pos_class ?? null;
        }

        $hasMockData = $mockRows->isNotEmpty();

        // ------------------------------------------------------------------
        // 4.  Distinct, ordered subject list (per mode)
        // ------------------------------------------------------------------
        if ($scoringMode === 'mock') {
            $subjects = collect($mockRows)
                ->pluck('subject_name')
                ->unique()->sort()->values()->toArray();
        } else {
            $subjects = collect($broadsheetRows ?? [])
                ->pluck('subject_name')
                ->unique()->sort()->values()->toArray();
        }

        // ------------------------------------------------------------------
        // 5.  Grade analysis per student (mode-aware + grade basis aware)
        // ------------------------------------------------------------------
        $studentGrades        = [];
        $studentGradeAnalysis = [];

        foreach ($students as $student) {
            $sid = $student->id;

            $studentGradeAnalysis[$sid] = [
                'grades'        => [],
                'counts'        => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0],
                'weak_subjects' => [],
            ];

            foreach ($subjects as $subject) {
                if ($scoringMode === 'mock') {
                    $activeScore = $mockScoreMap[$sid][$subject] ?? 0;
                    [$activeGrade, $activeGradeLetter] = $this->gradeFromScore((float)$activeScore, $isSenior);

                    $entry = [
                        'subject' => $subject,
                        'mock_score' => $activeScore,
                        'mock_grade' => $activeGrade,
                        'mock_grade_letter' => $activeGradeLetter,
                        'cum_score' => 0,
                        'cum_grade' => '-',
                        'cum_grade_letter' => '',
                        'cum_ave_score' => 0,
                        'cum_ave_grade' => '-',
                        'cum_ave_grade_letter' => '',
                        'term_score' => 0,
                        'term_grade' => '-',
                        'term_grade_letter' => '',
                        'score' => $activeScore,
                        'grade' => $activeGrade,
                        'grade_letter' => $activeGradeLetter,
                    ];
                } else {
                    $cumTotal  = $cumScoreMap[$sid][$subject]  ?? 0;
                    $cumAve    = $cumAveMap[$sid][$subject]    ?? 0;
                    $termTotal = $termScoreMap[$sid][$subject] ?? 0;

                    [$cumGrade, $cumGradeLetter]   = $this->gradeFromScore((float)$cumTotal, $isSenior);
                    [$cumAveGrade, $cumAveGradeLetter] = $this->gradeFromScore((float)$cumAve, $isSenior);
                    [$termGrade, $termGradeLetter] = $this->gradeFromScore((float)$termTotal, $isSenior);

                    // Determine which score to use based on grade basis
                    $activeScore = $gradeBasis === 'total' ? $termTotal : $cumAve;
                    [$activeGrade, $activeGradeLetter] = $this->gradeFromScore((float)$activeScore, $isSenior);

                    $entry = [
                        'subject' => $subject,
                        'cum_score' => $cumTotal,
                        'cum_grade' => $cumGrade,
                        'cum_grade_letter' => $cumGradeLetter,
                        'cum_ave_score' => $cumAve,
                        'cum_ave_grade' => $cumAveGrade,
                        'cum_ave_grade_letter' => $cumAveGradeLetter,
                        'term_score' => $termTotal,
                        'term_grade' => $termGrade,
                        'term_grade_letter' => $termGradeLetter,
                        'mock_score' => 0,
                        'mock_grade' => '-',
                        'mock_grade_letter' => '',
                        'score' => $activeScore,
                        'grade' => $activeGrade,
                        'grade_letter' => $activeGradeLetter,
                        'bf_score' => $bfMap[$sid][$subject] ?? 0,
                    ];
                }

                $studentGrades[$sid][]                  = $entry;
                $studentGradeAnalysis[$sid]['grades'][] = $entry;
                $studentGradeAnalysis[$sid]['counts'][$entry['grade_letter']]++;

                if (in_array($entry['grade_letter'], ['C', 'D', 'E', 'F'])) {
                    $studentGradeAnalysis[$sid]['weak_subjects'][] = [
                        'subject' => $subject,
                        'grade' => $entry['grade'],
                        'grade_letter' => $entry['grade_letter'],
                        'cumulative_score' => $entry['cum_score'] ?? 0,
                        'cum_ave_score' => $entry['cum_ave_score'] ?? 0,
                        'term_score' => $entry['term_score'] ?? 0,
                        'mock_score' => $entry['mock_score'] ?? 0,
                    ];
                }
            }
        }

        // ------------------------------------------------------------------
        // 6.  Saved principal comments keyed by student id
        // ------------------------------------------------------------------
        $profiles = Studentpersonalityprofile::where('schoolclassid', $schoolclassid)
            ->where('termid',    $termid)
            ->where('sessionid', $sessionid)
            ->pluck('principalscomment', 'studentid')
            ->toArray();

        // ------------------------------------------------------------------
        // 7.  Standard personalised comments (second-person)
        // ------------------------------------------------------------------
        $baseTemplates = [
            "Excellent result {NAME}, keep it up!",
            "A very good result {NAME}, keep it up!",
            "Good result {NAME}, keep it up!",
            "Average result {NAME}, there's still room for improvement next term.",
            "{NAME}, you can do better next term.",
            "{NAME}, you need to sit up and be serious.",
            "{NAME}, wake up and be serious.",
        ];

        $standardPersonalizedComments = [];

        foreach ($students as $student) {
            $sid       = $student->id;
            $firstName = $student->fname;

            $weakSubjects = $studentGradeAnalysis[$sid]['weak_subjects'] ?? [];
            $advice       = '';

            if (!empty($weakSubjects)) {
                usort($weakSubjects, fn ($a, $b) =>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$a['grade_letter']]
                    <=>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$b['grade_letter']]
                );

                $subjectList = array_map(
                    fn ($ws) => strtoupper($ws['subject']) . ' (' . $ws['grade'] . ')',
                    $weakSubjects
                );
                $advice = "\n\nYou should work harder in "
                        . $this->formatList($subjectList)
                        . " to improve your performance.";
            }

            $options = [];
            foreach ($baseTemplates as $template) {
                $options[] = str_replace('{NAME}', $firstName, $template) . $advice;
            }

            $standardPersonalizedComments[$sid] = $options;
        }

        // ------------------------------------------------------------------
        // 8.  Intelligent comments (third-person, gender-aware)
        // ------------------------------------------------------------------
        $intelligentComments = [];

        foreach ($students as $student) {
            $sid       = $student->id;
            $firstName = $student->fname;
            $analysis  = $studentGradeAnalysis[$sid];

            $gradeParts = [];
            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $g) {
                $count = $analysis['counts'][$g] ?? 0;
                if ($count > 0) {
                    $gradeParts[] = "$count {$g}" . ($count > 1 ? "'s" : '');
                }
            }
            $gradeSummary = !empty($gradeParts) ? $this->formatList($gradeParts) : 'no grades recorded';

            $totalGrades    = array_sum($analysis['counts']);
            $goodGrades     = ($analysis['counts']['A'] ?? 0) + ($analysis['counts']['B'] ?? 0);
            $percentageGood = $totalGrades > 0 ? ($goodGrades / $totalGrades) * 100 : 0;

            $baseComment = match (true) {
                $percentageGood >= 80 => "Excellent result {NAME}, keep it up!",
                $percentageGood >= 70 => "A very good result {NAME}, keep it up!",
                $percentageGood >= 60 => "Good result {NAME}, keep it up!",
                $percentageGood >= 50 => "Average result {NAME}, there's still room for improvement next term.",
                $percentageGood >= 40 => "{NAME}, you can do better next term.",
                $percentageGood >= 30 => "{NAME}, you need to sit up and be serious.",
                default               => "{NAME}, wake up and be serious.",
            };

            // Term/cum info suffix only for non-mock cumulative mode
            $termInfo = '';
            if ($scoringMode === 'cumulative') {
                $basisLabel = $gradeBasis === 'total' ? 'Term Total' : 'Cumulative Average';
                $termInfo = " (based on {$basisLabel})";
            } elseif ($scoringMode === 'mock') {
                $termInfo = ' (Mock examination result)';
            }

            $comment    = "$firstName has $gradeSummary$termInfo. "
                        . str_replace('{NAME}', $firstName, $baseComment);
            $pronoun    = strtoupper($student->gender) === 'MALE' ? 'He'  : 'She';
            $possessive = strtoupper($student->gender) === 'MALE' ? 'his' : 'her';

            $weakSubjects = $analysis['weak_subjects'] ?? [];
            if (!empty($weakSubjects)) {
                usort($weakSubjects, fn ($a, $b) =>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$a['grade_letter']]
                    <=>
                    ['F' => 0, 'E' => 1, 'D' => 2, 'C' => 3][$b['grade_letter']]
                );
                $subjectList  = array_map(fn ($ws) => $ws['subject'] . ' (' . $ws['grade'] . ')', $weakSubjects);
                $comment     .= "\n\n$pronoun should work harder in "
                              . $this->formatList($subjectList)
                              . " to improve $possessive performance.";
            }

            $intelligentComments[$sid] = $comment;
        }

        // ------------------------------------------------------------------
        // 9.  Student analytics — mode-aware totals, averages, positions
        // ------------------------------------------------------------------
        $studentTotals     = [];
        $studentTermTotals = [];
        $studentMockTotals = [];
        $studentCumAveTotals = [];

        foreach ($students as $student) {
            $sid      = $student->id;
            $cumSum   = 0;
            $cumAveSum = 0;
            $termSum  = 0;
            $mockSum  = 0;
            $count    = 0;
            $mockCount = 0;

            foreach ($subjects as $subject) {
                $cum    = $cumScoreMap[$sid][$subject]  ?? null;
                $cumAve = $cumAveMap[$sid][$subject]    ?? null;
                $term   = $termScoreMap[$sid][$subject] ?? null;
                $mock   = $mockScoreMap[$sid][$subject] ?? null;

                if (!is_null($cum) && $cum > 0) {
                    $cumSum += $cum;
                    $count++;
                }
                if (!is_null($cumAve) && $cumAve > 0) {
                    $cumAveSum += $cumAve;
                }
                if (!is_null($term)) {
                    $termSum += $term;
                }
                if (!is_null($mock) && $mock > 0) {
                    $mockSum += $mock;
                    $mockCount++;
                }
            }

            $studentTotals[$sid] = [
                'total'    => $cumSum,
                'average'  => $count > 0 ? round($cumSum / $count, 1) : 0,
                'subjects' => $count,
            ];
            $studentCumAveTotals[$sid] = [
                'total'    => $cumAveSum,
                'average'  => $count > 0 ? round($cumAveSum / $count, 1) : 0,
            ];
            $studentTermTotals[$sid] = [
                'total'   => $termSum,
                'average' => $count > 0 ? round($termSum / $count, 1) : 0,
            ];
            $studentMockTotals[$sid] = [
                'total'   => $mockSum,
                'average' => $mockCount > 0 ? round($mockSum / $mockCount, 1) : 0,
                'subjects' => $mockCount,
            ];
        }

        // Class averages
        $classCumSubjects  = array_sum(array_column($studentTotals, 'subjects'));
        $classCumSum       = array_sum(array_column($studentTotals, 'total'));
        $classCumAverage   = $classCumSubjects > 0 ? round($classCumSum / $classCumSubjects, 1) : 0;

        $classCumAveSum    = array_sum(array_column($studentCumAveTotals, 'total'));
        $classCumAveAverage = $classCumSubjects > 0 ? round($classCumAveSum / $classCumSubjects, 1) : 0;

        $classTermSum      = array_sum(array_column($studentTermTotals, 'total'));
        $classTermAverage  = $classCumSubjects > 0 ? round($classTermSum / $classCumSubjects, 1) : 0;

        $classMockSubjects = array_sum(array_column($studentMockTotals, 'subjects'));
        $classMockSum      = array_sum(array_column($studentMockTotals, 'total'));
        $classMockAverage  = $classMockSubjects > 0 ? round($classMockSum / $classMockSubjects, 1) : 0;

        $activeClassAverage = match ($scoringMode) {
            'term' => $classTermAverage,
            'mock' => $classMockAverage,
            default => $gradeBasis === 'total' ? $classTermAverage : $classCumAveAverage,
        };

        $classAnalytics = [
            'average'        => $activeClassAverage,
            'cum_average'    => $classCumAverage,
            'cum_ave_average' => $classCumAveAverage,
            'term_average'   => $classTermAverage,
            'mock_average'   => $classMockAverage,
            'total_students' => $students->count(),
        ];

        // Positions (overall ranking by active mode + grade basis)
        $sortedStudents = $students->sortByDesc(function ($s) use ($scoringMode, $gradeBasis, $studentTermTotals, $studentTotals, $studentMockTotals, $studentCumAveTotals) {
            return match ($scoringMode) {
                'term' => $studentTermTotals[$s->id]['average'] ?? 0,
                'mock' => $studentMockTotals[$s->id]['average'] ?? 0,
                default => $gradeBasis === 'total' 
                    ? ($studentTermTotals[$s->id]['average'] ?? 0)
                    : ($studentCumAveTotals[$s->id]['average'] ?? 0),
            };
        })->values();

        $positions = [];
        $rank      = 1;
        $prevAvg   = null;

        foreach ($sortedStudents as $index => $student) {
            $avg = match ($scoringMode) {
                'term' => $studentTermTotals[$student->id]['average'],
                'mock' => $studentMockTotals[$student->id]['average'],
                default => $gradeBasis === 'total'
                    ? ($studentTermTotals[$student->id]['average'] ?? 0)
                    : ($studentCumAveTotals[$student->id]['average'] ?? 0),
            };
            if ($index > 0 && $avg < $prevAvg) {
                $rank = $index + 1;
            }
            $positions[$student->id] = $rank;
            $prevAvg = $avg;
        }

        $studentAnalytics = [];
        foreach ($students as $student) {
            $sid      = $student->id;
            $position = $positions[$sid] ?? null;

            $studentAnalytics[$sid] = [
                // Cumulative
                'total_score'  => $studentTotals[$sid]['total'],
                'average'      => $studentTotals[$sid]['average'],
                // Cumulative Average
                'cum_ave_total' => $studentCumAveTotals[$sid]['total'],
                'cum_ave_average' => $studentCumAveTotals[$sid]['average'],
                // Term
                'term_total'   => $studentTermTotals[$sid]['total'],
                'term_average' => $studentTermTotals[$sid]['average'],
                // Mock
                'mock_total'   => $studentMockTotals[$sid]['total'],
                'mock_average' => $studentMockTotals[$sid]['average'],
                'subjects'     => $studentTotals[$sid]['subjects'],
                'position'     => $position,
                'position_text' => $position ? $this->getPositionSuffix($position) : '-',
                'grade_counts'  => $studentGradeAnalysis[$sid]['counts'] ?? [],
                'grade_basis'   => $gradeBasis,
            ];
        }

        // ------------------------------------------------------------------
        // 10. Render view
        // ------------------------------------------------------------------
        return view('myprincipalscomment.classbroadsheet')
            ->with(compact(
                'students',
                'subjects',
                'termScoreMap',
                'cumScoreMap',
                'cumAveMap',
                'bfMap',
                'mockScoreMap',
                'mockPositionMap',
                'posClassCumMap',
                'posClassTotalMap',
                'posArmTotalMap',
                'posArmCumMap',
                'profiles',
                'schoolclass',
                'schoolterm',
                'schooltermName',
                'schoolsession',
                'schoolclassid',
                'sessionid',
                'termid',
                'pagetitle',
                'studentGrades',
                'studentGradeAnalysis',
                'intelligentComments',
                'standardPersonalizedComments',
                'studentAnalytics',
                'classAnalytics',
                'isSenior',
                'scoringMode',
                'hasMockData',
                'gradeBasis'
            ));
    }

    // =========================================================================
    // UPDATE COMMENTS
    // =========================================================================

    public function updateComments(Request $request, $schoolclassid, $sessionid, $termid)
    {
        Log::info('Update Comments Request Received', [
            'schoolclassid'  => $schoolclassid,
            'sessionid'      => $sessionid,
            'termid'         => $termid,
            'auth_id'        => Auth::id(),
            'request_method' => $request->method(),
            'ajax'           => $request->ajax(),
        ]);

        $request->validate(['teacher_comments.*' => 'nullable|string|max:5000']);

        $comments     = $request->input('teacher_comments', []);
        $updatedCount = 0;
        $createdCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($comments as $studentId => $comment) {
                if (is_null($comment) || trim($comment) === '') {
                    $skippedCount++;
                    continue;
                }

                $comment = trim(strip_tags($comment));
                $comment = html_entity_decode($comment, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $existing = Studentpersonalityprofile::where('studentid',    $studentId)
                    ->where('schoolclassid', $schoolclassid)
                    ->where('sessionid',     $sessionid)
                    ->where('termid',        $termid)
                    ->first();

                if ($existing) {
                    if ($existing->principalscomment !== $comment) {
                        $existing->update([
                            'staffid'           => Auth::id(),
                            'principalscomment' => $comment,
                        ]);
                        $updatedCount++;
                    }
                } else {
                    Studentpersonalityprofile::create([
                        'studentid'         => $studentId,
                        'schoolclassid'     => $schoolclassid,
                        'sessionid'         => $sessionid,
                        'termid'            => $termid,
                        'staffid'           => Auth::id(),
                        'principalscomment' => $comment,
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            $totalProcessed = $updatedCount + $createdCount;
            $message = $totalProcessed > 0
                ? "Successfully saved: $updatedCount updated, $createdCount created. Skipped: $skippedCount empty comments."
                : "No changes detected. $skippedCount empty comments skipped.";

            Log::info('Update completed', [
                'updated' => $updatedCount,
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'updated' => $updatedCount,
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error saving principals comments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Fetch previous term's cum scores for BF computation.
     */
    private function fetchPreviousTermCums(
        array $studentIds,
        int   $sessionid,
        int   $currentTermId,
        array $classIds
    ): array {
        if (empty($studentIds) || $currentTermId == 1) return [];

        $prevTerm = \App\Models\Schoolterm::where('id', '<', $currentTermId)
            ->orderByDesc('id')
            ->first();

        if (!$prevTerm) return [];

        $rows = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $prevTerm->id)
            ->where('broadsheet_records.session_id', $sessionid)
            ->whereIn('broadsheet_records.schoolclass_id', $classIds)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->select([
                'broadsheet_records.student_id',
                'broadsheet_records.subject_id',
                'broadsheets.cum',
            ])
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->student_id][(int)$r->subject_id] = (float)$r->cum;
        }

        return $map;
    }

    /**
     * Return [grade, gradeLetter] for a score.
     */
    private function gradeFromScore(float $score, bool $isSenior): array
    {
        if ($isSenior) {
            if ($score >= 75) return ['A1', 'A'];
            if ($score >= 70) return ['B2', 'B'];
            if ($score >= 65) return ['B3', 'B'];
            if ($score >= 60) return ['C4', 'C'];
            if ($score >= 55) return ['C5', 'C'];
            if ($score >= 50) return ['C6', 'C'];
            if ($score >= 45) return ['D7', 'D'];
            if ($score >= 40) return ['E8', 'E'];
            return ['F9', 'F'];
        }

        if ($score >= 70) return ['A', 'A'];
        if ($score >= 60) return ['B', 'B'];
        if ($score >= 50) return ['C', 'C'];
        if ($score >= 40) return ['D', 'D'];
        return ['F', 'F'];
    }

    private function formatList(array $items): string
    {
        $count = count($items);
        if ($count === 0) return '';
        if ($count === 1) return $items[0];
        if ($count === 2) return implode(' and ', $items);
        return implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);
    }

    private function getPositionSuffix(int $num): string
    {
        if ($num % 100 >= 11 && $num % 100 <= 13) {
            return $num . 'th';
        }
        return match ($num % 10) {
            1       => $num . 'st',
            2       => $num . 'nd',
            3       => $num . 'rd',
            default => $num . 'th',
        };
    }
}