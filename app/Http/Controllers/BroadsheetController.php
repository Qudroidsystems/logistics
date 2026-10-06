<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Student;
use App\Models\Assessment;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\SchoolInformation;
use App\Models\BroadsheetAssessmentScore;
use App\Models\Subjectclass;
use App\Services\PromotionEvaluator;
use App\Services\BroadsheetRankingService;
use App\Services\BestStudentsService;
use App\Models\BroadsheetRankingSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BroadsheetController extends Controller
{
    private PromotionEvaluator $promotionEvaluator;
    private BestStudentsService $bestStudentsService;

    public function __construct(PromotionEvaluator $promotionEvaluator, BestStudentsService $bestStudentsService)
    {
        $this->middleware('permission:View student-report');
        $this->promotionEvaluator  = $promotionEvaluator;
        $this->bestStudentsService = $bestStudentsService;
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): View|JsonResponse
    {
        $pagetitle      = 'Class Broadsheet Generator';
        $schoolclasses  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
            ->orderBy('schoolclass.schoolclass')
            ->get();
        $schoolsessions = Schoolsession::orderByDesc('id')->get();
        $schoolterms    = Schoolterm::all();

        return view('broadsheet.index', compact(
            'pagetitle', 'schoolclasses', 'schoolsessions', 'schoolterms'
        ));
    }

    // =========================================================================
    // GET COLUMN OPTIONS (AJAX)
    // =========================================================================

    public function getColumnOptions(Request $request): JsonResponse
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
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('id')
                ->get();
        }

        $actualSubjectCount = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->where('sc.schoolclassid', $schoolclassid)
            ->distinct()
            ->count('sc.subjectid');

        $term              = Schoolterm::find($termid);
        $isPromotionalTerm = $term && $term->is_promotional;

        $columns = [
            'student_info' => [
                'sn'           => ['label' => 'SN',           'default' => true],
                'admission_no' => ['label' => 'Admission No', 'default' => true],
                'name'         => ['label' => 'Student Name', 'default' => true],
                'gender'       => ['label' => 'Gender',       'default' => false],
            ],
            'assessments' => [],
            'scores' => [
                'total'           => ['label' => 'Total',                 'default' => true],
                'bf'              => ['label' => 'BF',                    'default' => true],
                'cum'             => ['label' => 'Cum (raw sum)',         'default' => true],
                'cum_ave'         => ['label' => 'Cum Ave',               'default' => true],
                'grade'           => ['label' => 'Grade (per Grade Basis toggle)', 'default' => true],
                'pos_class_cum'   => ['label' => 'Class Pos (Cum)',       'default' => true],
                'pos_class_total' => ['label' => 'Class Pos (Total)',     'default' => false],
                'pos_arm_total'   => ['label' => 'Arm Pos (Total)',       'default' => true],
                'pos_arm_cum'     => ['label' => 'Arm Pos (Cum)',         'default' => true],
                'class_average'   => ['label' => 'Class Avg',             'default' => true],
                'remark'          => ['label' => 'Remark',                'default' => false],
            ],
            'summary' => [
                'position_cum'  => ['label' => 'Overall Pos (Cum)',  'default' => true],
                'position_term' => ['label' => 'Overall Pos (Term)', 'default' => true],
            ],
            'gpa_metrics' => [
                'gpa'                => ['label' => 'GPA',          'default' => true],
                'cgpa'               => ['label' => 'CGPA',         'default' => false],
                'gpa_grade'          => ['label' => 'GPA Grade',    'default' => false],
                'num_subjects'       => ['label' => 'No. Subjects', 'default' => false],
                'total_grade_points' => ['label' => 'Total GP',     'default' => false],
            ],
            'promotion' => [
                'promotion_status' => [
                    'label'   => 'Promotion Status',
                    'default' => $isPromotionalTerm,
                    'note'    => $isPromotionalTerm ? null : 'Non-promotional term',
                ],
                'promotion_label' => [
                    'label'   => 'Promotion Label (verbose)',
                    'default' => false,
                ],
                'promotion_rule_applied' => [
                    'label'   => 'Rule Applied',
                    'default' => true,
                ],
            ],
        ];

        foreach ($assessments as $a) {
            $columns['assessments']['assessment_' . $a->id] = [
                'label'               => $a->name . ' (' . $a->max_score . ')',
                'default'             => true,
                'assessment_id'       => $a->id,
                'max_score'           => $a->max_score,
                'has_sub_assessments' => $a->subAssessments->isNotEmpty(),
            ];
        }

        return response()->json([
            'success'             => true,
            'columns'             => $columns,
            'is_senior'           => $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? ($schoolclass->classcategories->first()->is_senior ?? false) : false,
            'subject_count'       => $actualSubjectCount,
            'assessment_count'    => count($columns['assessments']),
            'is_promotional_term' => $isPromotionalTerm,
            'grade_basis_options' => ['total' => 'Term Total', 'cum_ave' => 'Cumulative Average'],
            'default_grade_basis' => 'cum_ave',
        ]);
    }

    // =========================================================================
    // GET STUDENT PREVIEW (AJAX)
    // =========================================================================

    public function getStudentPreview(Request $request): JsonResponse
    {
        $schoolclassid = $request->input('schoolclassid');
        $classgroup    = $request->input('classgroup');
        $sessionid     = $request->input('sessionid');
        $termid        = $request->input('termid');

        if ($classgroup && $sessionid) {
            $matchingClasses = Schoolclass::where('schoolclass', $classgroup)->get();
            $classIds        = $matchingClasses->pluck('id')->toArray();
            $count           = Studentclass::whereIn('schoolclassid', $classIds)
                ->where('sessionid', $sessionid)->count();
            return response()->json(['success' => true, 'count' => $count, 'arms_count' => $matchingClasses->count()]);
        }

        if (!$schoolclassid || !$sessionid) {
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        $students = Studentclass::where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.gender',
                'studentpicture.picture',
            ])
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get();

        $subjectCount = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->where('sc.schoolclassid', $schoolclassid)
            ->distinct()->count('sc.subjectid');

        $assessmentCount = 0;
        $schoolclass     = Schoolclass::with('classcategories')->find($schoolclassid);
        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds     = $schoolclass->classcategories->pluck('id');
            $assessmentCount = Assessment::whereIn('classcategory_id', $categoryIds)->count();
        }

        return response()->json([
            'success'          => true,
            'count'            => $students->count(),
            'students'         => $students,
            'subject_count'    => $subjectCount,
            'assessment_count' => $assessmentCount,
        ]);
    }

    // =========================================================================
    // HELPER: Fetch previous term's cum scores
    // =========================================================================

    private function fetchPreviousTermCums(
        array $studentIds,
        int   $sessionid,
        int   $currentTermId,
        array $classIds
    ): array {
        if (empty($studentIds)) return [];

        $prevTerm = Schoolterm::where('id', '<', $currentTermId)
            ->orderByDesc('id')
            ->first();

        if (!$prevTerm) return [];

        // Scoped by student + session only (not schoolclass_id): senior results can be
        // keyed to a different class id than the name-matched arm, which would hide
        // their previous-term cumulative. $studentIds already limits the cohort.
        $rows = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $prevTerm->id)
            ->where('broadsheet_records.session_id', $sessionid)
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

    // =========================================================================
    // BUILD BROADSHEET DATA (single class)
    // $lite = true skips promotion evaluation — used by the Best Students report.
    // =========================================================================

    private function buildBroadsheetData(
        int    $schoolclassid,
        int    $sessionid,
        int    $termid,
        array  $selectedColumns = [],
        string $gradeBasis = 'cum_ave',
        bool   $lite = false
    ): array {
        $schoolInfo    = SchoolInformation::getActiveSchool() ?? new \stdClass();
        $schoolclass   = Schoolclass::with('classcategories')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.id', $schoolclassid)
            ->first();
        $schoolsession = Schoolsession::find($sessionid);
        $schoolterm    = Schoolterm::find($termid);

        $assessments = collect();
        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->orderBy('id')->get();
        }

        $subjectsMap    = [];
        $subjectClasses = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'sc.subjectid')
            ->where('sc.schoolclassid', $schoolclassid)
            ->select(['sc.subjectid', 'subject.subject as subject_name', 'subject.subject_code',
                      'sc.subjectteacherid', 'st.staffid'])
            ->distinct()->get();

        foreach ($subjectClasses as $sc) {
            $subjectsMap[(int)$sc->subjectid] = [
                'subject_id'       => (int)$sc->subjectid,
                'subject_name'     => $sc->subject_name,
                'subject_code'     => $sc->subject_code ?? '',
                'subjectteacherid' => $sc->subjectteacherid,
                'staffid'          => $sc->staffid,
            ];
        }

        $studentIds = Studentclass::where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->pluck('studentId')
            ->map(fn($v) => (int)$v)
            ->toArray();

        if (empty($studentIds)) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $schoolclass, $schoolsession, $schoolterm,
                $assessments, $subjectsMap, $selectedColumns,
                ['grade_basis' => $gradeBasis]
            );
        }

        $gradeCategory = $schoolclass && $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()
            : null;

        $prevCumMap = $this->fetchPreviousTermCums(
            $studentIds, $sessionid, $termid, [$schoolclassid]
        );

        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.schoolclass_id', $schoolclassid)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'broadsheets.id as broadsheet_id',
                'broadsheet_records.student_id',
                'broadsheet_records.subject_id',
                'subject.subject as subject_name',
                'subject.subject_code',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.gender',
                'studentpicture.picture',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.remark',
                'broadsheets.subject_position_class as pos_class_cum',
                'broadsheets.subject_position_class_total as pos_class_total',
                'broadsheets.arm_position as pos_arm_total',
                'broadsheets.arm_position_cum as pos_arm_cum',
                'broadsheets.avg as class_average',
                'broadsheets.vettedstatus',
            ])
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->orderBy('subject.subject')
            ->get();

        $broadsheetIds       = $broadsheets->pluck('broadsheet_id')->unique()->toArray();
        $assessmentScoresAll = BroadsheetAssessmentScore::whereIn('broadsheet_id', $broadsheetIds)
            ->get()->groupBy('broadsheet_id');

        $studentSubjectMap = [];
        foreach ($broadsheets as $row) {
            $sid = (int)$row->student_id;
            $sub = (int)$row->subject_id;

            if (!isset($subjectsMap[$sub])) {
                $subjectsMap[$sub] = [
                    'subject_id'   => $sub,
                    'subject_name' => $row->subject_name,
                    'subject_code' => $row->subject_code ?? '',
                ];
            }

            $assessmentScoreRow = $assessmentScoresAll->get($row->broadsheet_id, collect());
            $assessmentData     = [];
            foreach ($assessments as $a) {
                $score = $assessmentScoreRow->firstWhere('assessment_id', $a->id);
                $assessmentData[$a->id] = $score ? (float)$score->score : 0;
            }

            $rawTotal = (float)($row->total ?? 0);

            $prevCum = $prevCumMap[$sid][$sub] ?? null;
            if ($prevCum !== null && $prevCum > 0) {
                $bf = $prevCum;
            } elseif (!empty($row->bf) && (float)$row->bf > 0) {
                $bf = (float)$row->bf;
            } else {
                $bf = 0.0;
            }

            $cum    = round($bf + $rawTotal, 2);
            $cumAve = $termid > 0 ? round($cum / $termid, 2) : $cum;

            $totalGrade = $row->grade ?? ($gradeCategory ? $gradeCategory->calculateGrade($rawTotal) : '-');
            $cumGrade   = $gradeCategory ? $gradeCategory->calculateGrade($cumAve) : $totalGrade;

            $displayGrade = $gradeBasis === 'total' ? $totalGrade : $cumGrade;

            $studentSubjectMap[$sid][$sub] = [
                'total'           => $rawTotal,
                'bf'              => $bf,
                'cum'             => $cum,
                'cum_ave'         => $cumAve,
                'grade'           => $displayGrade,
                'total_grade'     => $totalGrade,
                'cum_grade'       => $cumGrade,
                'remark'          => $row->remark ?? '-',
                'pos_class_cum'   => $row->pos_class_cum   ?? null,
                'pos_class_total' => $row->pos_class_total ?? null,
                'pos_arm_total'   => $row->pos_arm_total   ?? null,
                'pos_arm_cum'     => $row->pos_arm_cum     ?? null,
                'class_average'   => (float)($row->class_average ?? 0),
                'assessments'     => $assessmentData,
            ];
        }

        $armLabels = [(int)$schoolclassid => (string)($schoolclass->arm_name ?? '')];

        return $this->assembleStudentRows(
            $studentIds, $sessionid, $schoolclassid, null,
            $studentSubjectMap, $subjectsMap, $assessments,
            $schoolInfo, $schoolclass, $schoolsession, $schoolterm,
            $selectedColumns, $armLabels, null, false, $gradeBasis, $lite
        );
    }

    // =========================================================================
    // SHARED: Assemble student rows
    // =========================================================================

    private function assembleStudentRows(
        array  $studentIds,
        int    $sessionid,
        ?int   $schoolclassid,
        ?array $classIds,
        array  $studentSubjectMap,
        array  $subjectsMap,
        $assessments,
        $schoolInfo,
        $schoolclass,
        $schoolsession,
        $schoolterm,
        array  $selectedColumns,
        array  $armLabels       = [],
        ?array $studentClassMap = null,
        bool   $isCombined      = false,
        string $gradeBasis      = 'cum_ave',
        bool   $lite            = false
    ): array {
        $query = Studentclass::where('sessionid', $sessionid);
        if ($schoolclassid) {
            $query->where('schoolclassid', $schoolclassid);
        } else {
            $query->whereIn('schoolclassid', $classIds ?? []);
        }

        $studentInfoRows = $query
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.gender',
                'studentRegistration.dateofbirth',
                'studentpicture.picture',
                'studentclass.schoolclassid',
            ])
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get();

        $termid          = $schoolterm ? $schoolterm->id : null;
        $shouldEvalPromo = !$lite && $termid && $sessionid;
        $className       = (string)($schoolclass->schoolclass ?? '');

        $studentRows = [];
        foreach ($studentInfoRows as $stu) {
            $sid       = (int)$stu->id;
            $subScores = $studentSubjectMap[$sid] ?? [];

            $cumValues    = [];
            $totalValues  = [];
            $gradePointsA = [];

            foreach ($subScores as $subData) {
                if (($subData['cum_ave'] ?? 0) > 0) {
                    $cumValues[] = $subData['cum_ave'];
                }
                if (($subData['total'] ?? 0) > 0) {
                    $totalValues[] = $subData['total'];
                }
                $gpaSourceScore = $gradeBasis === 'total'
                    ? ($subData['total']   ?? 0)
                    : ($subData['cum_ave'] ?? 0);
                if ($gpaSourceScore > 0) {
                    $gradePointsA[] = $this->getGradePoint($gpaSourceScore);
                }
            }

            $totalCum      = array_sum($cumValues);
            $totalTerm     = array_sum($totalValues);
            $numSubjects   = count($cumValues);
            $gpa           = count($gradePointsA) > 0
                ? round(array_sum($gradePointsA) / count($gradePointsA), 2) : 0.0;
            $classAvgScore = $numSubjects > 0 ? round($totalCum / $numSubjects, 1) : 0;
            $termAve       = count($totalValues) > 0 ? round($totalTerm / count($totalValues), 1) : 0;

            // Arm label from the preloaded map — no per-student queries
            $rowClassId = (int)$stu->schoolclassid;
            $armLabel   = (string)($armLabels[$rowClassId] ?? '');

            // ── Promotion evaluation ──
            $promoResult = null;
            if ($shouldEvalPromo) {
                $scoresForPromo = [];
                foreach ($subScores as $subjectId => $sd) {
                    $scoresForPromo[] = (object)[
                        'subject_id'   => $subjectId,
                        'grade'        => $sd['grade'] ?? null,
                        'total'        => $sd['total']   ?? 0,
                        'cum'          => $sd['cum']      ?? 0,
                        'cum_ave'      => $sd['cum_ave']  ?? 0,
                        'subject_name' => $subjectsMap[$subjectId]['subject_name'] ?? null,
                    ];
                }

                $evalClassId = ($isCombined && $studentClassMap && isset($studentClassMap[$sid]))
                    ? $studentClassMap[$sid]
                    : $schoolclassid;

                try {
                    $promoResult = $this->promotionEvaluator->evaluate(
                        $sid,
                        (int)$evalClassId,
                        (int)$termid,
                        (int)$sessionid,
                        $scoresForPromo,
                        $classAvgScore > 0 ? $classAvgScore : null
                    );

                    Log::debug('Promotion result for student ' . $sid, [
                        'status' => $promoResult['status'] ?? 'unknown',
                        'has_applied_rule' => isset($promoResult['applied_rule']),
                        'applied_rule' => $promoResult['applied_rule'] ?? null,
                    ]);

                } catch (\Exception $e) {
                    Log::warning('Promotion eval failed for student ' . $sid . ': ' . $e->getMessage());
                    $promoResult = $this->promotionEvaluator->awaitingResult($classAvgScore ?: null);
                }
            }

            // ── Extract the applied rule name ──
            $ruleApplied = null;
            if ($promoResult && isset($promoResult['applied_rule'])) {
                $appliedRule = $promoResult['applied_rule'];

                if (is_array($appliedRule)) {
                    $ruleApplied = $appliedRule['name'] ??
                                  $appliedRule['rule'] ??
                                  $appliedRule['label'] ??
                                  $appliedRule['rule_name'] ??
                                  null;

                    if (!$ruleApplied && isset($appliedRule['description'])) {
                        $ruleApplied = $appliedRule['description'];
                    }
                } elseif (is_string($appliedRule)) {
                    $ruleApplied = $appliedRule;
                }
            }

            // ── FALLBACK: If no rule was applied but we have a status, map it ──
            if (!$ruleApplied && $promoResult && isset($promoResult['status'])) {
                $status = $promoResult['status'];
                $statusRuleMap = [
                    'promoted' => 'Standard Promotion',
                    'trial' => 'Trial Promotion',
                    'see_principal' => 'Principal Review Required',
                    'repeated' => 'Repeat Year',
                    'awaiting' => 'Awaiting Decision'
                ];
                $ruleApplied = $statusRuleMap[$status] ?? 'Unknown Rule';
            }

            $studentRows[$sid] = [
                'id'                     => $sid,
                'admissionno'            => $stu->admissionno,
                'firstname'              => $stu->firstname,
                'lastname'               => $stu->lastname,
                'gender'                 => $stu->gender,
                'dateofbirth'            => $stu->dateofbirth,
                'picture'                => $stu->picture,
                'arm'                    => $armLabel,
                'class_name'             => $className,
                'class_label'            => trim($className . ' ' . $armLabel),
                'schoolclassid'          => $rowClassId,
                'subjects'               => $subScores,
                'total_cum'              => round($totalCum, 1),
                'total_term'             => round($totalTerm, 1),
                'cum_ave'                => $numSubjects > 0 ? round($totalCum / $numSubjects, 1) : 0,
                'term_ave'               => $termAve,
                'num_subjects'           => $numSubjects,
                'class_average'          => $classAvgScore,
                'gpa'                    => $gpa,
                'cgpa'                   => $gpa,
                'gpa_grade'              => $this->getGpaGrade($gpa),
                'total_grade_points'     => round(array_sum($gradePointsA), 1),
                'position_cum'           => 0,
                'position_term'          => 0,
                'promotion_status'       => $promoResult['status']       ?? 'awaiting',
                'promotion_label'        => $promoResult['status_label'] ?? $promoResult['label'] ?? 'Awaiting Decision',
                'promotion_rule_applied' => $ruleApplied,
                'promotion_data'         => $promoResult,
            ];
        }

        $posMapCum  = $this->buildPositionMap($studentRows, 'total_cum');
        $posMapTerm = $this->buildPositionMap($studentRows, 'total_term');

        foreach ($studentRows as $sid => &$row) {
            $row['position_cum']  = $posMapCum[(int)$sid]  ?? 0;
            $row['position_term'] = $posMapTerm[(int)$sid] ?? 0;
        }
        unset($row);

        $subjectStats = $this->buildSubjectStats($subjectsMap, $studentRows);
        uasort($subjectsMap, fn ($a, $b) => strcmp($a['subject_name'], $b['subject_name']));

        $result = [
            'schoolInfo'      => $schoolInfo,
            'schoolclass'     => $schoolclass,
            'schoolsession'   => $schoolsession,
            'schoolterm'      => $schoolterm,
            'assessments'     => $assessments,
            'subjects'        => $subjectsMap,
            'studentRows'     => array_values($studentRows),
            'subjectStats'    => $subjectStats,
            'selectedColumns' => $selectedColumns,
            'totalStudents'   => count($studentRows),
            'generatedAt'     => now()->format('d M Y, H:i'),
            'grade_basis'     => $gradeBasis,
        ];

        if ($isCombined) {
            $result['arm_labels']  = $armLabels;
            $result['is_combined'] = true;
        }

        return $result;
    }

    // =========================================================================
    // HELPER: Build position map
    // =========================================================================

    private function buildPositionMap(array $studentRows, string $key): array
    {
        $sorted = $studentRows;
        uasort($sorted, fn ($a, $b) => ($b[$key] ?? 0) <=> ($a[$key] ?? 0));

        $positionMap = [];
        $prevVal     = null;
        $prevPos     = 0;
        $counter     = 0;

        foreach ($sorted as $sid => $row) {
            $counter++;
            $val = (float)($row[$key] ?? 0);

            if ($prevVal !== null && $val === $prevVal) {
                $positionMap[(int)$sid] = $prevPos;
            } else {
                $positionMap[(int)$sid] = $counter;
                $prevPos = $counter;
            }
            $prevVal = $val;
        }

        return $positionMap;
    }

    // =========================================================================
    // HELPER: Subject stats
    // =========================================================================

    private function buildSubjectStats(array $subjectsMap, array $studentRows): array
    {
        $subjectStats = [];
        foreach ($subjectsMap as $sub => $subInfo) {
            $totals = [];
            foreach ($studentRows as $row) {
                $val = $row['subjects'][$sub]['total'] ?? 0;
                if ($val > 0) $totals[] = $val;
            }
            $count = count($totals);
            $subjectStats[$sub] = [
                'avg'     => $count > 0 ? round(array_sum($totals) / $count, 1) : 0,
                'highest' => $count > 0 ? max($totals) : 0,
                'lowest'  => $count > 0 ? min($totals) : 0,
                'passed'  => count(array_filter($totals, fn ($v) => $v >= 40)),
                'failed'  => count(array_filter($totals, fn ($v) => $v < 40)),
            ];
        }
        return $subjectStats;
    }

    // =========================================================================
    // HELPER: Empty result
    // =========================================================================

    private function emptyBroadsheetResult(
        $schoolInfo, $schoolclass, $schoolsession, $schoolterm,
        $assessments, $subjectsMap, array $selectedColumns, array $extra = []
    ): array {
        return array_merge([
            'schoolInfo'      => $schoolInfo,
            'schoolclass'     => $schoolclass,
            'schoolsession'   => $schoolsession,
            'schoolterm'      => $schoolterm,
            'assessments'     => $assessments,
            'subjects'        => $subjectsMap,
            'studentRows'     => [],
            'subjectStats'    => [],
            'selectedColumns' => $selectedColumns,
            'totalStudents'   => 0,
            'generatedAt'     => now()->format('d M Y, H:i'),
        ], $extra);
    }

    // =========================================================================
    // WEB VIEW
    // =========================================================================

    public function webView(Request $request): View|JsonResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'schoolclassid'   => 'required|integer|exists:schoolclass,id',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'grade_basis'     => 'nullable|in:total,cum_ave',
            ]);

            $gradeBasis = $request->input('grade_basis', 'cum_ave');

            $data = $this->buildBroadsheetData(
                (int)$validated['schoolclassid'],
                (int)$validated['sessionid'],
                (int)$validated['termid'],
                $request->input('selectedColumns', []),
                $gradeBasis
            );

            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['pagetitle']          = 'Class Broadsheet – Web View';

            $data = $this->attachRanking($data, $request);

            return view('broadsheet.web', $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid input.');
        } catch (\Exception $e) {
            Log::error('Broadsheet web view error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to generate broadsheet: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // STUDENT LIST (Printable promotion-ordered list)
    // =========================================================================

    public function studentList(Request $request): View|JsonResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'schoolclassid'        => 'required|integer|exists:schoolclass,id',
                'sessionid'            => 'required|integer|exists:schoolsession,id',
                'termid'               => 'required|integer',
                'list_fields'          => 'nullable|array',
                'recommendation_order' => 'nullable|array',
                'show_photos'          => 'nullable',
                'show_sn'              => 'nullable',
                'grade_basis'          => 'nullable|in:total,cum_ave',
            ]);

            $gradeBasis = $request->input('grade_basis', 'cum_ave');

            $data = $this->buildBroadsheetData(
                (int)$validated['schoolclassid'],
                (int)$validated['sessionid'],
                (int)$validated['termid'],
                [],
                $gradeBasis
            );

            $listFields = $request->input('list_fields', []);
            if (empty($listFields)) {
                $listFields = ['admissionno', 'firstname', 'lastname', 'arm', 'total_cum', 'cum_ave', 'position_cum', 'gpa_grade'];
            }

            $recommendationOrder = $request->input('recommendation_order', [
                'promoted', 'trial', 'see_principal', 'repeated', 'awaiting',
            ]);
            $showPhotos = filter_var($request->input('show_photos', false), FILTER_VALIDATE_BOOLEAN);
            $showSn     = filter_var($request->input('show_sn', true), FILTER_VALIDATE_BOOLEAN);

            $grouped = [];
            foreach ($recommendationOrder as $status) {
                $grouped[$status] = [];
            }
            $grouped['__other'] = [];

            foreach ($data['studentRows'] as $stu) {
                $status = $stu['promotion_status'] ?? 'awaiting';
                if (array_key_exists($status, $grouped)) {
                    $grouped[$status][] = $stu;
                } else {
                    $grouped['__other'][] = $stu;
                }
            }

            $grouped = array_filter($grouped, fn($g) => count($g) > 0);

            $data['grouped_students']     = $grouped;
            $data['list_fields']          = $listFields;
            $data['recommendation_order'] = $recommendationOrder;
            $data['show_photos']          = $showPhotos;
            $data['show_sn']              = $showSn;
            $data['school_logo_base64']   = $this->getLogoBase64($data['schoolInfo']);
            $data['pagetitle']            = 'Student Promotion List';
            $data['request_schoolclassid'] = (int)$validated['schoolclassid'];
            $data['request_sessionid']     = (int)$validated['sessionid'];
            $data['request_termid']        = (int)$validated['termid'];

            return view('broadsheet.student_list', $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid input.');
        } catch (\Exception $e) {
            Log::error('Student list error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to generate student list: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // EXPORT PDF
    // =========================================================================

    public function exportPdf(Request $request): \Illuminate\Http\Response|RedirectResponse
    {
        try {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $validated = $request->validate([
                'schoolclassid'   => 'required|integer|exists:schoolclass,id',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'paper_size'      => 'nullable|in:A0,A1,A2,A3,A4',
                'orientation'     => 'nullable|in:portrait,landscape',
                'grade_basis'     => 'nullable|in:total,cum_ave',
            ]);

            $selectedColumns = $request->input('selectedColumns', []);
            $orientation     = $request->input('orientation', 'landscape');
            $paperSize       = $request->input('paper_size', null);
            $gradeBasis      = $request->input('grade_basis', 'cum_ave');

            $data = $this->buildBroadsheetData(
                (int)$validated['schoolclassid'],
                (int)$validated['sessionid'],
                (int)$validated['termid'],
                $selectedColumns,
                $gradeBasis
            );
            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);

            $subjectCount = count($data['subjects'] ?? []);
            $perSubjCols  = $this->countActivePerSubjectCols($data['assessments'] ?? collect(), $selectedColumns);

            if (!$paperSize) {
                $total = $subjectCount * max(1, $perSubjCols);
                $paperSize = $total <= 30 ? 'A3' : ($total <= 60 ? 'A2' : ($total <= 100 ? 'A1' : 'A0'));
            }

            [$widthPt, $heightPt] = $this->computePdfDimensions($paperSize, $subjectCount, $perSubjCols);
            $data['pdf_width_pt']   = $widthPt;
            $data['pdf_height_pt']  = $heightPt;
            $data['pdf_paper_size'] = $paperSize;

            $pdf = Pdf::loadView('broadsheet.pdf', $data)
                ->setPaper([0, 0, $widthPt, $heightPt], $orientation)
                ->setOptions([
                    'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true,
                    'isFontSubsettingEnabled' => true, 'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96, 'enable_css_float' => false, 'enable_javascript' => false,
                ]);

            return $pdf->stream($this->buildFilename(
                ($data['schoolclass']->schoolclass ?? 'Class') . ' ' . ($data['schoolclass']->arm_name ?? ''),
                $data['schoolsession']->session ?? '',
                $data['schoolterm']->term ?? 'Term'
            ));
        } catch (\Exception $e) {
            Log::error('Broadsheet PDF export error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // EXPORT EXCEL
    // =========================================================================

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'schoolclassid'   => 'required|integer|exists:schoolclass,id',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'grade_basis'     => 'nullable|in:total,cum_ave',
            ]);

            $selectedColumns = $request->input('selectedColumns', []);
            $gradeBasis      = $request->input('grade_basis', 'cum_ave');
            $data = $this->buildBroadsheetData(
                (int)$validated['schoolclassid'], (int)$validated['sessionid'], (int)$validated['termid'],
                $selectedColumns, $gradeBasis
            );

            return Excel::download(
                new \App\Exports\BroadsheetExport($data),
                $this->buildFilename(
                    ($data['schoolclass']->schoolclass ?? 'Class') . ' ' . ($data['schoolclass']->arm_name ?? ''),
                    $data['schoolsession']->session ?? '',
                    $data['schoolterm']->term ?? 'Term', 'xlsx'
                )
            );
        } catch (\Exception $e) {
            Log::error('Broadsheet Excel export error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate Excel: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // ALL CLASSES WEB VIEW
    // =========================================================================

    public function allClassesWebView(Request $request): View|JsonResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'classgroup'      => 'required|string',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'grade_basis'     => 'nullable|in:total,cum_ave',
            ]);

            $gradeBasis = $request->input('grade_basis', 'cum_ave');

            $data = $this->buildAllClassesBroadsheetData(
                $validated['classgroup'],
                (int)$validated['sessionid'],
                (int)$validated['termid'],
                $request->input('selectedColumns', []),
                $gradeBasis
            );

            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['pagetitle']          = 'All Classes Broadsheet – ' . $validated['classgroup'];
            $data['is_combined']        = true;

            $data = $this->attachRanking($data, $request);

            return view('broadsheet.web', $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid input.');
        } catch (\Exception $e) {
            Log::error('All-classes broadsheet error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate broadsheet: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // ALL CLASSES EXPORT PDF
    // =========================================================================

    public function allClassesExportPdf(Request $request): \Illuminate\Http\Response|RedirectResponse
    {
        try {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $validated = $request->validate([
                'classgroup'      => 'required|string',
                'sessionid'       => 'required|integer|exists:schoolsession,id',
                'termid'          => 'required|integer',
                'selectedColumns' => 'nullable|array',
                'paper_size'      => 'nullable|in:A0,A1,A2,A3,A4',
                'orientation'     => 'nullable|in:portrait,landscape',
                'grade_basis'     => 'nullable|in:total,cum_ave',
            ]);

            $selectedColumns = $request->input('selectedColumns', []);
            $orientation     = $request->input('orientation', 'landscape');
            $paperSize       = $request->input('paper_size', 'A2');
            $gradeBasis      = $request->input('grade_basis', 'cum_ave');

            $data = $this->buildAllClassesBroadsheetData(
                $validated['classgroup'],
                (int)$validated['sessionid'],
                (int)$validated['termid'],
                $selectedColumns,
                $gradeBasis
            );
            $data['school_logo_base64'] = $this->getLogoBase64($data['schoolInfo']);
            $data['is_combined']        = true;

            $subjectCount = count($data['subjects'] ?? []);
            $perSubjCols  = $this->countActivePerSubjectCols($data['assessments'] ?? collect(), $selectedColumns);
            [$widthPt, $heightPt] = $this->computePdfDimensions($paperSize, $subjectCount, $perSubjCols);

            $pdf = Pdf::loadView('broadsheet.pdf', $data)
                ->setPaper([0, 0, $widthPt, $heightPt], $orientation)
                ->setOptions([
                    'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true,
                    'isFontSubsettingEnabled' => true, 'defaultFont' => 'DejaVu Sans',
                    'dpi' => 96, 'enable_css_float' => false, 'enable_javascript' => false,
                ]);

            return $pdf->stream($this->buildFilename(
                $validated['classgroup'],
                $data['schoolsession']->session ?? '',
                $data['schoolterm']->term ?? ''
            ));
        } catch (\Exception $e) {
            Log::error('All-classes PDF error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // BUILD ALL-CLASSES BROADSHEET DATA
    // =========================================================================

    private function buildAllClassesBroadsheetData(
        string $classgroup,
        int    $sessionid,
        int    $termid,
        array  $selectedColumns = [],
        string $gradeBasis = 'cum_ave'
    ): array {
        $schoolInfo    = SchoolInformation::getActiveSchool() ?? new \stdClass();
        $schoolsession = Schoolsession::find($sessionid);
        $schoolterm    = Schoolterm::find($termid);

        $matchingClasses = Schoolclass::with('classcategories')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.schoolclass', $classgroup)
            ->orderBy('schoolarm.arm')
            ->get();

        $combinedClass = (object)[
            'schoolclass' => $classgroup,
            'arm_name'    => $matchingClasses->isEmpty()
                ? '(All Arms)'
                : '(' . $matchingClasses->pluck('arm_name')->filter()->implode(', ') . ')',
            'id'          => null,
        ];

        if ($matchingClasses->isEmpty()) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
                collect(), [], $selectedColumns,
                ['classgroup' => $classgroup, 'arm_labels' => [], 'is_combined' => true, 'grade_basis' => $gradeBasis]
            );
        }

        $assessments   = collect();
        $gradeCategory = null;
        foreach ($matchingClasses as $cls) {
            if ($cls->classcategories->isNotEmpty()) {
                $assessments   = Assessment::whereIn(
                    'classcategory_id', $cls->classcategories->pluck('id')
                )->orderBy('id')->get();
                $gradeCategory = $cls->classcategories->first();
                break;
            }
        }

        $classIds = $matchingClasses->pluck('id')->map(fn($v) => (int)$v)->toArray();

        $subjectsMap    = [];
        $subjectClasses = DB::table('subjectclass as sc')
            ->join('subjectteacher as st', 'st.id', '=', 'sc.subjectteacherid')
            ->join('subject', 'subject.id', '=', 'sc.subjectid')
            ->whereIn('sc.schoolclassid', $classIds)
            ->select(['sc.subjectid', 'subject.subject as subject_name', 'subject.subject_code'])
            ->distinct()->get();

        foreach ($subjectClasses as $sc) {
            $subjectsMap[(int)$sc->subjectid] = [
                'subject_id'   => (int)$sc->subjectid,
                'subject_name' => $sc->subject_name,
                'subject_code' => $sc->subject_code ?? '',
            ];
        }

        $studentClassRecords = Studentclass::whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionid)
            ->get(['studentId', 'schoolclassid']);

        $studentClassMap = [];
        foreach ($studentClassRecords as $r) {
            $studentClassMap[(int)$r->studentId] = (int)$r->schoolclassid;
        }
        $allStudentIds = array_keys($studentClassMap);

        if (empty($allStudentIds)) {
            return $this->emptyBroadsheetResult(
                $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
                $assessments, $subjectsMap, $selectedColumns,
                ['classgroup' => $classgroup,
                 'arm_labels' => $matchingClasses->pluck('arm_name', 'id')->toArray(),
                 'is_combined' => true,
                 'grade_basis' => $gradeBasis]
            );
        }

        $prevCumMap = $this->fetchPreviousTermCums($allStudentIds, $sessionid, $termid, $classIds);

        // NOTE: scoped by student_id + term + session only. The schoolclass_id filter
        // is intentionally omitted here: senior results are sometimes keyed to a class
        // id that differs from the name-matched arm, which would drop the whole senior
        // sheet. Students are already limited to this class group for the chosen session.
        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $allStudentIds)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'broadsheets.id as broadsheet_id',
                'broadsheet_records.student_id',
                'broadsheet_records.subject_id',
                'broadsheet_records.schoolclass_id',
                'subject.subject as subject_name',
                'subject.subject_code',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.remark',
                'broadsheets.subject_position_class as pos_class_cum',
                'broadsheets.subject_position_class_total as pos_class_total',
                'broadsheets.arm_position as pos_arm_total',
                'broadsheets.arm_position_cum as pos_arm_cum',
                'broadsheets.avg as class_average',
            ])
            ->get();

        $broadsheetIds       = $broadsheets->pluck('broadsheet_id')->unique()->toArray();
        $assessmentScoresAll = BroadsheetAssessmentScore::whereIn('broadsheet_id', $broadsheetIds)
            ->get()->groupBy('broadsheet_id');

        $studentSubjectMap = [];
        foreach ($broadsheets as $row) {
            $sid = (int)$row->student_id;
            $sub = (int)$row->subject_id;

            if (!isset($subjectsMap[$sub])) {
                $subjectsMap[$sub] = [
                    'subject_id'   => $sub,
                    'subject_name' => $row->subject_name,
                    'subject_code' => $row->subject_code ?? '',
                ];
            }

            $assessmentScoreRow = $assessmentScoresAll->get($row->broadsheet_id, collect());
            $assessmentData     = [];
            foreach ($assessments as $a) {
                $score = $assessmentScoreRow->firstWhere('assessment_id', $a->id);
                $assessmentData[$a->id] = $score ? (float)$score->score : 0;
            }

            $rawTotal = (float)($row->total ?? 0);

            $prevCum = $prevCumMap[$sid][$sub] ?? null;
            if ($prevCum !== null && $prevCum > 0) {
                $bf = $prevCum;
            } elseif (!empty($row->bf) && (float)$row->bf > 0) {
                $bf = (float)$row->bf;
            } else {
                $bf = 0.0;
            }

            $cum    = round($bf + $rawTotal, 2);
            $cumAve = $termid > 0 ? round($cum / $termid, 2) : $cum;

            $totalGrade = $row->grade ?? ($gradeCategory ? $gradeCategory->calculateGrade($rawTotal) : '-');
            $cumGrade   = $gradeCategory ? $gradeCategory->calculateGrade($cumAve) : $totalGrade;
            $displayGrade = $gradeBasis === 'total' ? $totalGrade : $cumGrade;

            $studentSubjectMap[$sid][$sub] = [
                'total'           => $rawTotal,
                'bf'              => $bf,
                'cum'             => $cum,
                'cum_ave'         => $cumAve,
                'grade'           => $displayGrade,
                'total_grade'     => $totalGrade,
                'cum_grade'       => $cumGrade,
                'remark'          => $row->remark ?? '-',
                'pos_class_cum'   => $row->pos_class_cum   ?? null,
                'pos_class_total' => $row->pos_class_total ?? null,
                'pos_arm_total'   => $row->pos_arm_total   ?? null,
                'pos_arm_cum'     => $row->pos_arm_cum     ?? null,
                'class_average'   => (float)($row->class_average ?? 0),
                'assessments'     => $assessmentData,
            ];
        }

        $armLabels = $matchingClasses->pluck('arm_name', 'id')
            ->mapWithKeys(fn ($v, $k) => [(int)$k => (string)$v])->toArray();

        return $this->assembleStudentRows(
            $allStudentIds, $sessionid, null, $classIds,
            $studentSubjectMap, $subjectsMap, $assessments,
            $schoolInfo, $combinedClass, $schoolsession, $schoolterm,
            $selectedColumns, $armLabels, $studentClassMap, true, $gradeBasis
        );
    }

    // =========================================================================
    // RANKING — UNOFFICIAL best-student panel + toppers (per arm / per subject)
    // Operates only on the assembled $studentRows; never touches the official
    // position columns. Best-effort — failures are logged, not fatal.
    // =========================================================================

    private function attachRanking(array $data, Request $request): array
    {
        try {
            $rows = $data['studentRows'] ?? [];
            if (empty($rows)) return $data;

            $isSenior = false; $classIds = [];
            if (!empty($data['is_combined'])) {
                $group   = (string) $request->input('classgroup');
                $classes = Schoolclass::with('classcategories')->where('schoolclass', $group)->get();
                $classIds = $classes->pluck('id')->map(fn ($v) => (int) $v)->all();
                foreach ($classes as $c) {
                    if ($c->classcategories->isNotEmpty()) { $isSenior = (bool) $c->classcategories->first()->is_senior; break; }
                }
            } else {
                $scid = (int) $request->input('schoolclassid');
                $c = Schoolclass::with('classcategories')->find($scid);
                if ($c) {
                    $classIds = [$scid];
                    if ($c->classcategories->isNotEmpty()) $isSenior = (bool) $c->classcategories->first()->is_senior;
                }
            }

            $section  = $isSenior ? 'senior' : 'junior';
            $settings = BroadsheetRankingSetting::where('section', $section)->first()
                ?? BroadsheetRankingSetting::defaultFor($section);

            $compulsory = [];
            if ($settings->require_all_compulsory && $classIds) {
                $compulsory = DB::table('compulsory_subject_classes')
                    ->whereIn('schoolclassid', $classIds)
                    ->when($request->input('sessionid'), fn ($q) => $q->where('sessionid', (int) $request->input('sessionid')))
                    ->pluck('subjectId')->map(fn ($v) => (int) $v)->unique()->values()->all();
            }

            $override = $request->input('rank_by');
            $ranking  = app(BroadsheetRankingService::class)->rank($rows, $settings, $override, $isSenior, $compulsory);

            $data['ranking']  = $ranking;
            $data['rank_map'] = $ranking['rank_map'];

            // ── Toppers: best per arm + best per subject, same eligibility rules ──
            try {
                $data['toppers'] = $this->bestStudentsService->rank($rows, [
                    'measure'          => $override ?: $settings->primary_measure,
                    'tiebreakers'      => (array) ($settings->tiebreakers ?? []),
                    'top_n'            => (int) ($settings->top_n ?? 3),
                    'subject_top_n'    => (int) ($settings->subject_top_n ?? 3),
                    'min_subjects'     => (int) ($settings->min_subjects ?? 0),
                    'min_average'      => $settings->min_average,
                    'exclude_failed'   => (bool) $settings->exclude_failed,
                    'compulsory'       => $compulsory,
                    'core_subject_ids' => (array) ($settings->core_subject_ids ?? []),
                    'basis'            => $data['grade_basis'] ?? 'cum_ave',
                ], $data['subjects'] ?? []);
            } catch (\Throwable $e) {
                Log::warning('Broadsheet toppers attach failed: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::warning('Broadsheet ranking attach failed: ' . $e->getMessage());
        }
        return $data;
    }

    // =========================================================================
    // BEST STUDENTS REPORT — any mix of whole classes (all arms) and single arms
    // =========================================================================

    public function bestStudents(Request $request): View
    {
        return view('broadsheet.best-students', $this->bestStudentsFormData() + [
            'pagetitle' => 'Best Students Report',
            'report'    => null,
            'input'     => [],
        ]);
    }

    public function bestStudentsReport(Request $request): View|RedirectResponse
    {
        $measureKeys = implode(',', array_keys(BestStudentsService::MEASURES));

        $v = $request->validate([
            'class_groups'   => 'nullable|array',
            'class_groups.*' => 'string',
            'class_ids'      => 'nullable|array',
            'class_ids.*'    => 'integer',
            'sessionid'      => 'required|integer|exists:schoolsession,id',
            'termid'         => 'required|integer|exists:schoolterm,id',
            'measure'        => "required|in:{$measureKeys}",
            'top_n'          => 'required|integer|min:1|max:20',
            'subject_top_n'  => 'required|integer|min:1|max:10',
            'min_subjects'   => 'nullable|integer|min:0|max:40',
            'grade_basis'    => 'required|in:cum_ave,total',
        ]);

        $groups = array_values(array_filter((array) ($v['class_groups'] ?? [])));
        $ids    = array_map('intval', (array) ($v['class_ids'] ?? []));

        if (empty($groups) && empty($ids)) {
            return back()->withErrors(['class_ids' => 'Select at least one class or arm.'])->withInput();
        }

        try {
            @ini_set('max_execution_time', 300);

            $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
                ->where(function ($q) use ($groups, $ids) {
                    if ($groups) $q->orWhereIn('schoolclass.schoolclass', $groups);
                    if ($ids)    $q->orWhereIn('schoolclass.id', $ids);
                })
                ->orderBy('schoolclass.schoolclass')
                ->orderBy('schoolarm.arm')
                ->get();

            $sessionid = (int) $v['sessionid'];
            $termid    = (int) $v['termid'];
            $basis     = $v['grade_basis'];

            // ── Build lite rows per arm (no promotion evaluation) and merge ──
            $allRows     = [];
            $subjectsMap = [];
            $rowsByGroup = [];
            foreach ($classes as $cls) {
                $data = $this->buildBroadsheetData((int) $cls->id, $sessionid, $termid, [], $basis, true);
                foreach ($data['subjects'] as $subId => $info) {
                    $subjectsMap[$subId] = $subjectsMap[$subId] ?? $info;
                }
                foreach ($data['studentRows'] as $row) {
                    $allRows[(int) $row['id']]                   = $row;
                    $rowsByGroup[$row['class_name']][$row['id']] = $row;
                }
            }
            uasort($subjectsMap, fn ($a, $b) => strcmp($a['subject_name'], $b['subject_name']));

            $core = [];
            foreach (['junior', 'senior'] as $sec) {
                $s = BroadsheetRankingSetting::where('section', $sec)->first();
                if ($s) $core = array_merge($core, (array) ($s->core_subject_ids ?? []));
            }

            $opts = [
                'measure'          => $v['measure'],
                'tiebreakers'      => array_values(array_diff(['distinctions', 'lowest_score', 'cum_ave'], [$v['measure']])),
                'top_n'            => (int) $v['top_n'],
                'subject_top_n'    => (int) $v['subject_top_n'],
                'min_subjects'     => (int) ($v['min_subjects'] ?? 0),
                'exclude_failed'   => $request->boolean('exclude_failed'),
                'core_subject_ids' => array_values(array_unique(array_map('intval', $core))),
                'basis'            => $basis,
            ];

            // Overall across the whole selection + per arm + per subject
            $overall = $this->bestStudentsService->rank(array_values($allRows), $opts, $subjectsMap);

            // Per class (all selected arms of each class together)
            $byClass = [];
            ksort($rowsByGroup, SORT_NATURAL);
            foreach ($rowsByGroup as $className => $rows) {
                $r = $this->bestStudentsService->rank(array_values($rows), $opts, $subjectsMap);
                $byClass[$className] = [
                    'top'      => $r['overall'],
                    'arms'     => count(array_unique(array_column($rows, 'schoolclassid'))),
                    'students' => count($rows),
                ];
            }

            $schoolInfo = SchoolInformation::getActiveSchool() ?? new \stdClass();

            $report = [
                'overall'        => $overall['overall'],
                'by_arm'         => $overall['by_arm'],
                'by_subject'     => $overall['by_subject'],
                'by_class'       => $byClass,
                'measure_label'  => $overall['measure_label'],
                'basis'          => $basis,
                'eligible_count' => $overall['eligible_count'],
                'student_count'  => count($allRows),
                'selection'      => $classes->map(fn ($c) => trim($c->schoolclass . ' ' . ($c->arm_name ?? '')))->all(),
                'session'        => optional(Schoolsession::find($sessionid))->session,
                'term'           => optional(Schoolterm::find($termid))->term,
                'generatedAt'    => now()->format('d M Y, H:i'),
            ];

            return view('broadsheet.best-students', $this->bestStudentsFormData() + [
                'pagetitle'          => 'Best Students Report',
                'report'             => $report,
                'input'              => $request->all(),
                'schoolInfo'         => $schoolInfo,
                'school_logo_base64' => $this->getLogoBase64($schoolInfo),
            ]);
        } catch (\Throwable $e) {
            Log::error('Best students report error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage())->withInput();
        }
    }

    private function bestStudentsFormData(): array
    {
        $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        return [
            'classesByGroup' => $classes->groupBy('schoolclass'),
            'schoolsessions' => Schoolsession::orderByDesc('id')->get(),
            'schoolterms'    => Schoolterm::all(),
            'measures'       => BestStudentsService::MEASURES,
        ];
    }

    // =========================================================================
    // AJAX: Get class groups
    // =========================================================================

    public function getClassGroups(): JsonResponse
    {
        $groups = Schoolclass::select('schoolclass')->distinct()->orderBy('schoolclass')->pluck('schoolclass');
        return response()->json(['success' => true, 'groups' => $groups]);
    }

    // =========================================================================
    // PDF HELPERS
    // =========================================================================

    private function countActivePerSubjectCols($assessments, array $selectedColumns): float
    {
        $cols = 0.0;
        foreach ($assessments as $a) {
            if (empty($selectedColumns) || in_array('assessment_' . $a->id, $selectedColumns)) $cols++;
        }
        $scoreCols = ['total','bf','cum','cum_ave','grade','pos_class_cum','pos_class_total','pos_arm_total','pos_arm_cum','class_average','remark'];
        foreach ($scoreCols as $col) {
            if (empty($selectedColumns) || in_array($col, $selectedColumns)) $cols++;
        }
        foreach (['gpa','cgpa','gpa_grade','num_subjects','total_grade_points'] as $col) {
            if (empty($selectedColumns) || in_array($col, $selectedColumns)) $cols += 0.5;
        }
        return $cols;
    }

    private function computePdfDimensions(string $paperSize, int $subjectCount, float $perSubjCols): array
    {
        $heights = ['A0' => 2384, 'A1' => 1684, 'A2' => 1190, 'A3' => 842,  'A4' => 595];
        $widths  = ['A0' => 3370, 'A1' => 2384, 'A2' => 1684, 'A3' => 1190, 'A4' => 842];
        $needed  = 200 + ($subjectCount * max(1, ceil($perSubjCols)) * 22) + 57;
        return [
            max($widths[$paperSize] ?? 1190, $needed + 100),
            $heights[$paperSize] ?? 842,
        ];
    }

    private function buildFilename(string $class, string $session, string $term, string $ext = 'pdf'): string
    {
        $c = fn (string $s) => preg_replace('/[^A-Za-z0-9_\-]/', '_', trim($s));
        return 'Broadsheet_' . $c($class) . '_' . $c($session) . '_' . $c($term) . '.' . $ext;
    }

    // =========================================================================
    // GRADE HELPERS
    // =========================================================================

    private function getGradePoint(float $score): float
    {
        if ($score >= 75) return 5.0;
        if ($score >= 70) return 4.5;
        if ($score >= 65) return 4.0;
        if ($score >= 60) return 3.5;
        if ($score >= 55) return 3.0;
        if ($score >= 50) return 2.5;
        if ($score >= 45) return 2.0;
        if ($score >= 40) return 1.0;
        return 0.0;
    }

    private function getGpaGrade(float $gpa): string
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

    private function getLogoBase64($schoolInfo): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">'
            . '<rect width="80" height="80" rx="40" fill="#1e3a5f"/>'
            . '<text x="40" y="45" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">SCH</text>'
            . '</svg>'
        );

        if (!$schoolInfo || empty($schoolInfo->school_logo)) return $placeholder;

        foreach ([
            storage_path('app/public/' . $schoolInfo->school_logo),
            public_path('storage/' . $schoolInfo->school_logo),
            public_path($schoolInfo->school_logo),
        ] as $path) {
            if (file_exists($path) && filesize($path) > 100) {
                return 'data:' . (mime_content_type($path) ?: 'image/jpeg')
                    . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        return $placeholder;
    }
}
