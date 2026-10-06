<?php
// app/Http/Controllers/PromotionController.php

namespace App\Http\Controllers;

use Exception;
use Illuminate\View\View;
use App\Models\Schoolclass;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\PromotionStatus;
use App\Models\PromotionSetting;
use App\Models\CompulsorySubjectClass;
use App\Models\Broadsheets;
use App\Models\Student;
use App\Models\Schoolterm;
use App\Models\SchoolInformation;
use App\Models\ParentRegistration;
use App\Models\StudentCurrentTerm;
use App\Models\PromotionActionLog;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\PromotionEvaluator;
use Illuminate\Pagination\LengthAwarePaginator;

class PromotionController extends Controller
{
    protected PromotionEvaluator $promotionEvaluator;

    public function __construct(PromotionEvaluator $promotionEvaluator)
    {
        $this->middleware('permission:View promotion',   ['only' => ['index', 'getStudentDetails', 'studentList', 'history']]);
        $this->middleware('permission:Update promotion', ['only' => ['update', 'destroy', 'bulkPromote', 'advanceTerm', 'clearDecision', 'revertBatch']]);
        $this->promotionEvaluator = $promotionEvaluator;
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): View|JsonResponse
    {
        $pagetitle   = 'Student Promotion Management';
        $allstudents = new LengthAwarePaginator([], 0, 10);

        $hasFilters = $request->filled('schoolclassid')
            && $request->filled('sessionid')
            && $request->input('schoolclassid') !== 'ALL'
            && $request->input('sessionid')     !== 'ALL';

        if ($hasFilters) {
            $schoolclassId = (int) $request->input('schoolclassid');
            $sessionId     = (int) $request->input('sessionid');
            $termId        = (int) $request->input('termid', 3);

            // ── Pre-flight: can we skip the evaluator entirely? ───────────────
            //
            // We short-circuit to awaitingResult() when:
            //   (a) no active settings exist for this class at all, OR
            //   (b) active settings exist but NONE of them cover this
            //       session+term combination.
            //
            // This mirrors exactly what PromotionEvaluator::evaluate() does
            // internally, but avoids paying the cost of fetching scores and
            // spinning up the evaluator for every student when the answer
            // is already known to be "awaiting".
            //
            // We do NOT short-circuit when settings DO match — the evaluator
            // must run so it can apply rules and produce a real verdict.
            $averageBasis  = $this->resolveAverageBasis($request->input('average_basis'));

            try {
                $shouldSkipEvaluator = $this->classHasNoApplicableSetting(
                    $schoolclassId, $sessionId, $termId
                );

                // Class position, computed once for the whole class from the
                // same registered-subject scores the averages use.
                $classPositions = $this->buildClassPositions(
                    $schoolclassId, $sessionId, $termId, $averageBasis
                );

                // Cohort = students currently placed in this class/session
                // (studentclass) PLUS anyone with results recorded for it
                // (broadsheet_records). studentclass only keeps the current
                // placement, so on its own a past session showed nobody.
                // One row per student -- no duplicates.
                $cohortIds = $this->cohortStudentIds($schoolclassId, $sessionId);
                $classMeta = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->where('schoolclass.id', $schoolclassId)
                    ->first(['schoolclass.schoolclass', 'schoolarm.arm']);
                $sessionName = Schoolsession::where('id', $sessionId)->value('session');

                $query = DB::table('studentRegistration')
                    ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                    ->whereIn('studentRegistration.id', $cohortIds);

                if ($search = $request->input('search')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                          ->orWhere('studentRegistration.firstname',  'like', "%{$search}%")
                          ->orWhere('studentRegistration.lastname',   'like', "%{$search}%")
                          ->orWhere('studentRegistration.othername',  'like', "%{$search}%");
                    });
                }

                $allstudents = $query->select([
                    'studentRegistration.id          as stid',
                    'studentRegistration.admissionNo as admissionno',
                    'studentRegistration.firstname   as firstname',
                    'studentRegistration.lastname    as lastname',
                    'studentRegistration.othername   as othername',
                    'studentRegistration.gender      as gender',
                    DB::raw('MAX(studentpicture.picture) as picture'),
                ])
                    ->groupBy(
                        'studentRegistration.id', 'studentRegistration.admissionNo',
                        'studentRegistration.firstname', 'studentRegistration.lastname',
                        'studentRegistration.othername', 'studentRegistration.gender'
                    )
                    ->orderBy('studentRegistration.lastname')
                    ->orderBy('studentRegistration.firstname')
                    ->paginate(100)
                    ->withQueryString();

                $pageIds = $allstudents->getCollection()->pluck('stid')->all();
                $savedStatuses = PromotionStatus::whereIn('studentId', $pageIds)
                    ->where('schoolclassid', $schoolclassId)
                    ->where('sessionid',     $sessionId)
                    ->where('termid',        $termId)
                    ->get()
                    ->keyBy(fn ($p) => (int) $p->studentId);

                $allstudents->getCollection()->transform(
                    function ($student) use (
                        $schoolclassId, $sessionId, $termId, $shouldSkipEvaluator,
                        $averageBasis, $classPositions, $savedStatuses, $classMeta, $sessionName
                    ) {
                        $student->schoolclassID = $schoolclassId;
                        $student->sessionid     = $sessionId;
                        $student->termid        = $termId;
                        $student->schoolclass   = $classMeta?->schoolclass;
                        $student->schoolarm     = $classMeta?->arm;
                        $student->session       = $sessionName;

                        $scores         = $this->getStudentScores(
                            $student->stid, $schoolclassId, $sessionId, $termId
                        );
                        $overallAverage = $this->calculateOverallAverage($scores, $averageBasis);

                        $student->promotion_recommendation = $shouldSkipEvaluator
                            ? $this->promotionEvaluator->awaitingResult($overallAverage)
                            : $this->promotionEvaluator->evaluate(
                                studentId:      $student->stid,
                                schoolclassid:  $schoolclassId,
                                termid:         $termId,
                                sessionid:      $sessionId,
                                scores:         $scores,
                                overallAverage: $overallAverage
                            );

                        $student->overall_average = $overallAverage;
                        $student->average_basis   = $averageBasis;
                        $student->position        = $this->formatOrdinal(
                            $classPositions[(int) $student->stid] ?? null
                        );

                        $existingStatus = $savedStatuses->get((int) $student->stid);
                        $student->promotion_status = $existingStatus?->promotionStatus;
                        $student->promotion_id     = $existingStatus?->id;

                        return $student;
                    }
                );

            } catch (Exception $e) {
                Log::error('Promotion query failed', [
                    'request' => $request->all(),
                    'error'   => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]);
                $allstudents = new LengthAwarePaginator([], 0, 10);
            }
        }

        $schoolsessions = Schoolsession::get();
        $schoolclasses  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);
        $terms = \App\Models\Schoolterm::orderBy('term')->get();

        if ($request->ajax()) {
            return response()->json([
                'tableBody'    => view('promotions.partials.student_rows', compact('allstudents'))->render(),
                'pagination'   => $allstudents->links('pagination::bootstrap-5')->render(),
                'studentCount' => $allstudents->total(),
            ]);
        }

        return view('promotions.index', compact(
            'allstudents', 'schoolsessions', 'schoolclasses', 'terms', 'pagetitle'
        ));
    }

    // =========================================================================
    // STUDENT DETAILS (modal)
    // =========================================================================

    public function getStudentDetails($studentId, $schoolclassId, $sessionId, $termId): JsonResponse
    {
        try {
            $student = Student::where('studentRegistration.id', $studentId)
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->select([
                    'studentRegistration.id          as stid',
                    'studentRegistration.admissionNo  as admissionno',
                    'studentRegistration.firstname    as firstname',
                    'studentRegistration.lastname     as lastname',
                    'studentRegistration.othername    as othername',
                    'studentRegistration.gender       as gender',
                    'studentpicture.picture           as picture',
                ])
                ->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found'], 404);
            }

            $averageBasis   = $this->resolveAverageBasis(request()->input('average_basis'));
            $scores         = $this->getStudentScores($studentId, $schoolclassId, $sessionId, $termId);
            $overallAverage = $this->calculateOverallAverage($scores, $averageBasis);

            // Same term-aware short-circuit as index()
            $shouldSkipEvaluator = $this->classHasNoApplicableSetting(
                $schoolclassId, $sessionId, $termId
            );

            $promotionResult = $shouldSkipEvaluator
                ? $this->promotionEvaluator->awaitingResult($overallAverage)
                : $this->promotionEvaluator->evaluate(
                    studentId:     $studentId,
                    schoolclassid: $schoolclassId,
                    termid:        $termId,
                    sessionid:     $sessionId,
                    scores:        $scores,
                    overallAverage: $overallAverage
                );

            // Compulsory subjects for this class/term/session
            $compulsoryQuery = CompulsorySubjectClass::where('schoolclassid', $schoolclassId)
                ->where(function ($q) use ($termId, $sessionId) {
                    $q->where(function ($q2) use ($termId, $sessionId) {
                        $q2->where('termid', $termId)->where('sessionid', $sessionId);
                    })->orWhere(function ($q2) use ($sessionId) {
                        $q2->whereNull('termid')->where('sessionid', $sessionId);
                    })->orWhere(function ($q2) {
                        $q2->whereNull('termid')->whereNull('sessionid');
                    });
                })
                ->with('subject')
                ->get();

            $compulsorySubjectIds = $compulsoryQuery->pluck('subjectId')->toArray();

            // Per-subject min-grade overrides from the matched rule (if any)
            $appliedRule  = $promotionResult['applied_rule'] ?? null;
            $ruleSubjects = [];
            if ($appliedRule && isset($promotionResult['settings_id'])) {
                $settings = PromotionSetting::find($promotionResult['settings_id']);
                if ($settings && $settings->promotion_rules) {
                    foreach ($settings->promotion_rules as $rule) {
                        if ($rule['rule_name'] === $appliedRule['name']) {
                            foreach ($rule['compulsory_section']['subjects'] ?? [] as $subject) {
                                $ruleSubjects[$subject['subject_id']] = $subject['min_grade'] ?? null;
                            }
                            break;
                        }
                    }
                }
            }

            // ── Build ALL subjects list ───────────────────────────────────────
            $allSubjects = [];

            foreach ($scores as $score) {
                $isCompulsory     = in_array($score->subject_id, $compulsorySubjectIds);
                $minGradeFromRule = $ruleSubjects[$score->subject_id] ?? null;
                $minGradeFromComp = $compulsoryQuery->firstWhere('subjectId', $score->subject_id)?->min_grade;
                $requiredMinGrade = $minGradeFromRule ?? $minGradeFromComp ?? null;

                $passStatus = $this->determinePassStatus($score->grade, $requiredMinGrade, $isCompulsory);

                $allSubjects[] = [
                    'subject_id'         => $score->subject_id,
                    'subject_name'       => $score->subject_name,
                    'subject_code'       => $score->subject_code ?? '',
                    'total'              => $score->total,
                    'cum_ave'            => $score->cum_ave ?? null,
                    'grade'              => $score->grade,
                    'is_compulsory'      => $isCompulsory,
                    'required_min_grade' => $requiredMinGrade,
                    'pass_status'        => $passStatus,
                    'pass_status_label'  => $this->getPassStatusLabel($passStatus),
                    'pass_status_class'  => $this->getPassStatusClass($passStatus),
                ];
            }

            // Add compulsory subjects with no score (Not Sat)
            foreach ($compulsoryQuery as $compulsory) {
                $alreadyAdded = collect($allSubjects)->firstWhere('subject_id', $compulsory->subjectId);
                if (!$alreadyAdded && $compulsory->subject) {
                    $minGradeFromRule = $ruleSubjects[$compulsory->subjectId] ?? null;
                    $requiredMinGrade = $minGradeFromRule ?? $compulsory->min_grade;

                    $allSubjects[] = [
                        'subject_id'         => $compulsory->subjectId,
                        'subject_name'       => $compulsory->subject->subject ?? 'Unknown',
                        'subject_code'       => $compulsory->subject->subject_code ?? '',
                        'total'              => null,
                        'grade'              => null,
                        'is_compulsory'      => true,
                        'required_min_grade' => $requiredMinGrade,
                        'pass_status'        => 'not_sat',
                        'pass_status_label'  => 'Not Attempted',
                        'pass_status_class'  => 'secondary',
                    ];
                }
            }

            // Sort: compulsory first, then alphabetically
            usort($allSubjects, function ($a, $b) {
                if ($a['is_compulsory'] !== $b['is_compulsory']) {
                    return $b['is_compulsory'] - $a['is_compulsory'];
                }
                return strcmp($a['subject_name'], $b['subject_name']);
            });

            // ── Compulsory subjects summary ───────────────────────────────────
            $compulsorySubjectsWithStatus = $compulsoryQuery->map(
                function ($cs) use ($scores, $ruleSubjects) {
                    $scoreEntry       = $scores->firstWhere('subject_id', $cs->subjectId);
                    $studentGrade     = $scoreEntry?->grade ?? null;
                    $studentTotal     = $scoreEntry?->total ?? null;
                    $minGradeFromRule = $ruleSubjects[$cs->subjectId] ?? null;
                    $requiredMinGrade = $minGradeFromRule ?? $cs->min_grade;

                    $passStatus      = $scoreEntry === null
                        ? 'not_sat'
                        : ($this->gradePassFail($studentGrade, $requiredMinGrade) ? 'pass' : 'fail');
                    $ruleRequirement = $minGradeFromRule
                        ? "Rule requires: ≥ {$minGradeFromRule}"
                        : ($cs->min_grade ? "Default: ≥ {$cs->min_grade}" : 'No requirement');

                    return [
                        'csc_id'            => $cs->id,
                        'subject_id'        => $cs->subjectId,
                        'subject'           => $cs->subject?->subject ?? 'N/A',
                        'subject_code'      => $cs->subject?->subject_code ?? '',
                        'required_min_grade'=> $requiredMinGrade,
                        'rule_requirement'  => $ruleRequirement,
                        'student_grade'     => $studentGrade,
                        'student_total'     => $studentTotal,
                        'pass_status'       => $passStatus,
                        'pass_status_label' => $this->getPassStatusLabel($passStatus),
                        'pass_status_class' => $this->getPassStatusClass($passStatus),
                    ];
                }
            );

            // ── Statistics ────────────────────────────────────────────────────
            $passedCompulsory = $compulsorySubjectsWithStatus->where('pass_status', 'pass')->count();
            $failedCompulsory = $compulsorySubjectsWithStatus->where('pass_status', 'fail')->count();
            $notSatCompulsory = $compulsorySubjectsWithStatus->where('pass_status', 'not_sat')->count();

            $creditGrades = $this->getCreditGrades($schoolclassId);
            $creditCount  = $scores->filter(fn($s) => in_array($s->grade, $creditGrades))->count();

            // Bio-data + parent contact for the modal's Student Info card.
            $studentBio = Student::where('id', $studentId)
                ->select(['gender', 'dateofbirth', 'admission_date', 'phone_number', 'home_address2 as home_address'])
                ->first();
            $parentInfo = ParentRegistration::where('studentId', $studentId)
                ->select(['father', 'father_phone', 'mother', 'mother_phone', 'parent_email'])
                ->first();

            return response()->json([
                'success'             => true,
                'student'             => $student,
                'student_bio'         => $studentBio,
                'parent_info'         => $parentInfo,
                'class_history'       => $this->buildClassHistory((int) $studentId),
                'average_basis'       => $averageBasis,
                'position'            => $this->formatOrdinal(
                    $this->buildClassPositions((int) $schoolclassId, (int) $sessionId, (int) $termId, $averageBasis)[(int) $studentId] ?? null
                ),
                'promotion_result'    => $promotionResult,
                'overall_average'     => $overallAverage,
                'all_subjects'        => $allSubjects,
                'compulsory_subjects' => $compulsorySubjectsWithStatus,
                'statistics'          => [
                    'total_subjects'     => count($allSubjects),
                    'compulsory_count'   => $compulsoryQuery->count(),
                    'passed_compulsory'  => $passedCompulsory,
                    'failed_compulsory'  => $failedCompulsory,
                    'not_sat_compulsory' => $notSatCompulsory,
                    'credit_count'       => $creditCount,
                ],
                'scores_count'        => $scores->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting student details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get student details: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE (single student)
    // =========================================================================

    public function update(Request $request, $studentId): JsonResponse
    {
        $request->validate([
            'new_schoolclassid' => 'required|exists:schoolclass,id',
            'new_sessionid'     => 'required|exists:schoolsession,id',
            'new_termid'        => 'required|integer|min:1|max:3',
            'promotion'         => 'boolean',
            'repeat'            => 'boolean',
            'trial'             => 'boolean',
            'see_principal'     => 'boolean',
        ]);

        if ($request->boolean('promotion') && $request->boolean('repeat')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot select both promotion and repeat.',
            ], 422);
        }

        $promotionStatus = match (true) {
            $request->boolean('promotion')     => 'PROMOTED',
            $request->boolean('trial')         => 'TRIAL',
            $request->boolean('see_principal') => 'SEE_PRINCIPAL',
            $request->boolean('repeat')        => 'REPEAT',
            default                            => 'PARENTS_TO_SEE_PRINCIPAL',
        };

        $batchId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($studentId, $request, $promotionStatus, $batchId) {
                $before       = $this->snapshotStudentState((int) $studentId);
                $newClassId   = $request->new_schoolclassid;
                $newSessionId = $request->new_sessionid;
                $newTermId    = $request->new_termid;

                $existingClass = Studentclass::where('studentId', $studentId)
                    ->where('sessionid', $newSessionId)
                    ->where('termid',    $newTermId)
                    ->first();

                if ($existingClass) {
                    $existingClass->update(['schoolclassid' => $newClassId]);
                } else {
                    Studentclass::create([
                        'studentId'     => $studentId,
                        'schoolclassid' => $newClassId,
                        'sessionid'     => $newSessionId,
                        'termid'        => $newTermId,
                    ]);
                }

                PromotionStatus::updateOrCreate(
                    [
                        'studentId'     => $studentId,
                        'schoolclassid' => $newClassId,
                        'sessionid'     => $newSessionId,
                        'termid'        => $newTermId,
                    ],
                    [
                        'promotionStatus' => $promotionStatus,
                        'classstatus'     => 'CURRENT',
                        'position'        => null,
                    ]
                );

                DB::table('student_current_term')
                    ->where('studentId', $studentId)
                    ->update(['is_current' => false]);

                \App\Models\StudentCurrentTerm::updateOrCreate(
                    [
                        'studentId'     => $studentId,
                        'schoolclassId' => $newClassId,
                        'termId'        => $newTermId,
                        'sessionId'     => $newSessionId,
                    ],
                    ['is_current' => true]
                );

                $this->logPromotionAction(
                    $batchId, 'promote', (int) $studentId, $before,
                    (int) $newClassId, (int) $newSessionId, (int) $newTermId,
                    $this->describeTarget('Set ' . $promotionStatus, $newClassId, $newSessionId, $newTermId)
                );
            });

            return response()->json([
                'success'  => true,
                'message'  => 'Promotion updated successfully.',
                'batch_id' => $batchId,
            ]);

        } catch (Exception $e) {
            Log::error('Promotion update failed', [
                'studentId' => $studentId,
                'request'   => $request->all(),
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to update promotion.'], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(Request $request, $studentId): JsonResponse
    {
        $request->validate([
            'schoolclassid' => 'required|exists:schoolclass,id',
            'sessionid'     => 'required|exists:schoolsession,id',
            'termid'        => 'required|exists:schoolterm,id',
        ]);

        $batchId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($studentId, $request, $batchId) {
                $before = $this->snapshotStudentState((int) $studentId);

                Studentclass::where('studentId',     $studentId)
                    ->where('schoolclassid', $request->input('schoolclassid'))
                    ->where('sessionid',     $request->input('sessionid'))
                    ->where('termid',        $request->input('termid'))
                    ->delete();

                PromotionStatus::where('studentId',     $studentId)
                    ->where('schoolclassid', $request->input('schoolclassid'))
                    ->where('sessionid',     $request->input('sessionid'))
                    ->where('termid',        $request->input('termid'))
                    ->delete();

                $this->logPromotionAction(
                    $batchId, 'remove', (int) $studentId, $before,
                    (int) $request->input('schoolclassid'), (int) $request->input('sessionid'), (int) $request->input('termid'),
                    $this->describeTarget('Removed from', $request->input('schoolclassid'), $request->input('sessionid'), $request->input('termid'))
                );
            });

            return response()->json([
                'success'  => true,
                'message'  => 'Student removed from class. You can undo this from History.',
                'batch_id' => $batchId,
            ]);

        } catch (Exception $e) {
            Log::error('Student removal failed', [
                'studentId' => $studentId,
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to remove student.'], 500);
        }
    }

    // =========================================================================
    // BULK PROMOTE
    // =========================================================================

    public function bulkPromote(Request $request): JsonResponse
    {
        $request->validate([
            'student_ids'       => 'required|array',
            'student_ids.*'     => 'exists:studentRegistration,id',
            'new_schoolclassid' => 'required|exists:schoolclass,id',
            'new_sessionid'     => 'required|exists:schoolsession,id',
            'new_termid'        => 'required|integer|min:1|max:3',
            'promotion_type'    => 'required|in:promoted,trial,see_principal,repeat',
        ]);

        $successCount = 0;
        $failCount    = 0;

        $promotionStatus = match ($request->promotion_type) {
            'promoted'      => 'PROMOTED',
            'trial'         => 'TRIAL',
            'see_principal' => 'SEE_PRINCIPAL',
            'repeat'        => 'REPEAT',
            default         => 'PARENTS_TO_SEE_PRINCIPAL',
        };

        $batchId     = (string) Str::uuid();
        $description = $this->describeTarget(
            'Bulk ' . $promotionStatus, $request->new_schoolclassid, $request->new_sessionid, $request->new_termid
        );

        foreach ($request->student_ids as $studentId) {
            try {
                DB::transaction(function () use ($studentId, $request, $promotionStatus, $batchId, $description) {
                    $before       = $this->snapshotStudentState((int) $studentId);
                    $newClassId   = $request->new_schoolclassid;
                    $newSessionId = $request->new_sessionid;
                    $newTermId    = $request->new_termid;

                    $existingClass = Studentclass::where('studentId', $studentId)
                        ->where('sessionid', $newSessionId)
                        ->where('termid',    $newTermId)
                        ->first();

                    if ($existingClass) {
                        $existingClass->update(['schoolclassid' => $newClassId]);
                    } else {
                        Studentclass::create([
                            'studentId'     => $studentId,
                            'schoolclassid' => $newClassId,
                            'sessionid'     => $newSessionId,
                            'termid'        => $newTermId,
                        ]);
                    }

                    PromotionStatus::updateOrCreate(
                        [
                            'studentId'     => $studentId,
                            'schoolclassid' => $newClassId,
                            'sessionid'     => $newSessionId,
                            'termid'        => $newTermId,
                        ],
                        [
                            'promotionStatus' => $promotionStatus,
                            'classstatus'     => 'CURRENT',
                            'position'        => null,
                        ]
                    );

                    $this->logPromotionAction(
                        $batchId, 'bulk_promote', (int) $studentId, $before,
                        (int) $newClassId, (int) $newSessionId, (int) $newTermId, $description
                    );
                });
                $successCount++;
            } catch (Exception $e) {
                $failCount++;
                Log::error('Bulk promotion failed for student', [
                    'studentId' => $studentId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'       => true,
            'message'       => "{$successCount} students promoted successfully. {$failCount} failed.",
            'success_count' => $successCount,
            'fail_count'    => $failCount,
            'batch_id'      => $successCount > 0 ? $batchId : null,
        ]);
    }

    // =========================================================================
    // ADVANCE TERM -- same class & session, move selected students to the
    // next term. A term rollover, not a promotion decision.
    // =========================================================================

    public function advanceTerm(Request $request): JsonResponse
    {
        $request->validate([
            'student_ids'    => 'required|array|min:1',
            'student_ids.*'  => 'exists:studentRegistration,id',
            'schoolclassid'  => 'required|exists:schoolclass,id',
            'sessionid'      => 'required|exists:schoolsession,id',
            'current_termid' => 'required|exists:schoolterm,id',
        ]);

        $schoolclassId = (int) $request->schoolclassid;
        $sessionId     = (int) $request->sessionid;
        $newTermId     = $this->nextTermId((int) $request->current_termid);

        if (!$newTermId) {
            return response()->json([
                'success' => false,
                'message' => 'This is the last term of the session -- use Bulk Promote to move students into a new class/session instead.',
            ], 422);
        }

        $newTermName  = Schoolterm::where('id', $newTermId)->value('term') ?? "Term {$newTermId}";
        $successCount = 0;
        $failCount    = 0;

        $batchId     = (string) Str::uuid();
        $description = $this->describeTarget('Advanced to', $schoolclassId, $sessionId, $newTermId);

        foreach ($request->student_ids as $studentId) {
            try {
                DB::transaction(function () use ($studentId, $schoolclassId, $sessionId, $newTermId, $batchId, $description) {
                    $before = $this->snapshotStudentState((int) $studentId);

                    // Keep ONE studentclass row per student for this
                    // class/session: move its term forward rather than
                    // adding a second row (score entry, broadsheets and
                    // this screen all list students by class + session).
                    $row = Studentclass::where('studentId', $studentId)
                        ->where('schoolclassid', $schoolclassId)
                        ->where('sessionid',     $sessionId)
                        ->orderByDesc('termid')
                        ->first();

                    if ($row) {
                        $row->update(['termid' => $newTermId]);
                    } else {
                        Studentclass::create([
                            'studentId'     => $studentId,
                            'schoolclassid' => $schoolclassId,
                            'sessionid'     => $sessionId,
                            'termid'        => $newTermId,
                        ]);
                    }

                    PromotionStatus::updateOrCreate(
                        [
                            'studentId'     => $studentId,
                            'schoolclassid' => $schoolclassId,
                            'sessionid'     => $sessionId,
                            'termid'        => $newTermId,
                        ],
                        [
                            'promotionStatus' => 'ADVANCED',
                            'classstatus'     => 'CURRENT',
                        ]
                    );

                    DB::table('student_current_term')
                        ->where('studentId', $studentId)
                        ->update(['is_current' => false]);

                    StudentCurrentTerm::updateOrCreate(
                        [
                            'studentId'     => $studentId,
                            'schoolclassId' => $schoolclassId,
                            'termId'        => $newTermId,
                            'sessionId'     => $sessionId,
                        ],
                        ['is_current' => true]
                    );

                    $this->logPromotionAction(
                        $batchId, 'advance_term', (int) $studentId, $before,
                        $schoolclassId, $sessionId, $newTermId, $description
                    );
                });
                $successCount++;
            } catch (Exception $e) {
                $failCount++;
                Log::error('Advance term failed for student', [
                    'studentId' => $studentId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'       => $successCount > 0,
            'message'       => "{$successCount} student(s) advanced to {$newTermName}." . ($failCount ? " {$failCount} failed." : ''),
            'success_count' => $successCount,
            'fail_count'    => $failCount,
            'new_termid'    => $newTermId,
            'batch_id'      => $successCount > 0 ? $batchId : null,
        ]);
    }

    // =========================================================================
    // STUDENT LIST -- printable, grouped by the live System Recommendation.
    // Scope: 'class' (one arm), 'class_wide' (all arms of a class level) or
    // 'school' (every class).
    // =========================================================================

    public function studentList(Request $request): View|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'scope'         => 'required|in:class,class_wide,school',
                'schoolclassid' => 'required_if:scope,class,class_wide|nullable|integer|exists:schoolclass,id',
                'sessionid'     => 'required|integer|exists:schoolsession,id',
                'termid'        => 'required|integer|exists:schoolterm,id',
                'average_basis' => 'nullable|in:total,cum',
                'format'        => 'nullable|in:compact,detailed',
            ]);

            $format       = $validated['format'] ?? 'compact';
            $scope        = $validated['scope'];
            $sessionId    = (int) $validated['sessionid'];
            $termId       = (int) $validated['termid'];
            $averageBasis = $this->resolveAverageBasis($validated['average_basis'] ?? null);

            if ($scope === 'school') {
                ini_set('max_execution_time', 600);
                ini_set('memory_limit', '1024M');
                $classesInScope = Schoolclass::with('armRelation')->orderBy('schoolclass')->orderBy('arm')->get();
                $scopeLabel = 'Whole School';
            } else {
                $anchor = Schoolclass::with('armRelation')->findOrFail((int) $validated['schoolclassid']);
                if ($scope === 'class_wide') {
                    $classesInScope = Schoolclass::with('armRelation')
                        ->where('schoolclass', $anchor->schoolclass)->orderBy('arm')->get();
                    $scopeLabel = $anchor->schoolclass . ' — All Arms';
                } else {
                    $classesInScope = collect([$anchor]);
                    $scopeLabel = trim($anchor->schoolclass . ' ' . ($anchor->armRelation->arm ?? ''));
                }
            }

            $order = ['promoted', 'trial', 'see_principal', 'repeated', 'awaiting'];
            $labels = [
                'promoted' => 'Promoted', 'trial' => 'On Trial', 'see_principal' => 'See Principal',
                'repeated' => 'Advised to Repeat', 'awaiting' => 'Awaiting Decision', '__other' => 'Other',
            ];

            $classGroups = [];
            $overall     = array_fill_keys(array_merge($order, ['__other']), 0);
            $grandTotal  = 0;

            foreach ($classesInScope as $schoolclass) {
                $classId  = (int) $schoolclass->id;
                $cohort   = $this->cohortStudentIds($classId, $sessionId);
                if (empty($cohort)) continue;

                $skip      = $this->classHasNoApplicableSetting($classId, $sessionId, $termId);
                $positions = $this->buildClassPositions($classId, $sessionId, $termId, $averageBasis);
                $armLabel  = $schoolclass->armRelation->arm ?? null;

                $students = DB::table('studentRegistration as sr')
                    ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 'sr.id')
                    ->whereIn('sr.id', $cohort)
                    ->groupBy('sr.id', 'sr.admissionNo', 'sr.firstname', 'sr.lastname', 'sr.othername', 'sr.gender', 'sr.dateofbirth')
                    ->orderBy('sr.lastname')->orderBy('sr.firstname')
                    ->get([
                        'sr.id', 'sr.admissionNo', 'sr.firstname', 'sr.lastname', 'sr.othername',
                        'sr.gender', 'sr.dateofbirth', DB::raw('MAX(sp.picture) as picture'),
                    ]);

                $grouped = array_fill_keys(array_merge($order, ['__other']), []);

                foreach ($students as $stu) {
                    $scores = $this->getStudentScores($stu->id, $classId, $sessionId, $termId);
                    $avg    = $this->calculateOverallAverage($scores, $averageBasis);
                    $rec    = $skip
                        ? $this->promotionEvaluator->awaitingResult($avg)
                        : $this->promotionEvaluator->evaluate(
                            studentId: (int) $stu->id, schoolclassid: $classId, termid: $termId,
                            sessionid: $sessionId, scores: $scores, overallAverage: $avg
                        );

                    $status = $rec['status'] ?? 'awaiting';
                    if ($status === 'repeat') $status = 'repeated';
                    $bucket = array_key_exists($status, $grouped) ? $status : '__other';

                    $grouped[$bucket][] = [
                        'admissionno'     => $stu->admissionNo,
                        'name'            => trim($stu->lastname . ', ' . $stu->firstname . ' ' . ($stu->othername ?? '')),
                        'gender'          => $stu->gender,
                        'arm'             => $armLabel,
                        'overall_average' => $avg,
                        'position'        => $this->formatOrdinal($positions[(int) $stu->id] ?? null),
                        'label'           => $rec['status_label'] ?? $labels[$bucket],
                        'rule'            => $rec['applied_rule']['name'] ?? null,
                        // extra keys used by the detailed (topclass-style) format
                        'id'              => (int) $stu->id,
                        'firstname'       => $stu->firstname,
                        'lastname'        => $stu->lastname,
                        'dateofbirth'     => $stu->dateofbirth,
                        'picture'         => $stu->picture,
                        'promotion_status'=> $bucket,
                        'promotion_label' => $rec['status_label'] ?? $labels[$bucket],
                    ];
                    $overall[$bucket]++;
                    $grandTotal++;
                }

                $grouped = array_filter($grouped, fn ($g) => count($g) > 0);
                if (empty($grouped)) continue;

                $classGroups[] = [
                    'schoolclassid' => $classId,
                    'schoolclass'   => $schoolclass->schoolclass,
                    'arm'           => $armLabel,
                    'label'         => trim($schoolclass->schoolclass . ' ' . $armLabel),
                    'grouped'       => $grouped,
                    'totalStudents' => array_sum(array_map('count', $grouped)),
                ];
            }

            $schoolInfo = SchoolInformation::getActiveSchool() ?? new \stdClass();
            $logo       = $this->getLogoBase64($schoolInfo);
            $overallNonZero = array_filter($overall, fn ($c) => $c > 0);

            if ($format === 'detailed') {
                $listFields = ['admissionno', 'gender', 'overall_average', 'position'];
                if ($scope !== 'class') $listFields[] = 'arm';

                return view('promotions.student_list_detailed', [
                    'scope'               => $scope,
                    'scopeLabel'          => $scopeLabel,
                    'classGroups'         => $classGroups,
                    'overallGrouped'      => $overallNonZero,
                    'recommendationOrder' => $order,
                    'grandTotal'          => $grandTotal,
                    'listFields'          => $listFields,
                    'showPhotos'          => false,
                    'showSn'              => true,
                    'schoolInfo'          => $schoolInfo,
                    'schoolLogoBase64'    => $logo,
                    'schoolsession'       => Schoolsession::find($sessionId),
                    'schoolterm'          => Schoolterm::find($termId),
                    'averageBasis'        => $averageBasis,
                    'generatedAt'         => now()->format('d M Y, H:i'),
                    'pagetitle'           => 'Student Promotion List',
                ]);
            }

            return view('promotions.student_list', [
                'scope'        => $scope,
                'scopeLabel'   => $scopeLabel,
                'classGroups'  => $classGroups,
                'overall'      => $overallNonZero,
                'labels'       => $labels,
                'grandTotal'   => $grandTotal,
                'schoolInfo'   => $schoolInfo,
                'logo'         => $logo,
                'sessionName'  => Schoolsession::where('id', $sessionId)->value('session'),
                'termName'     => Schoolterm::where('id', $termId)->value('term'),
                'averageBasis' => $averageBasis,
                'generatedAt'  => now()->format('d M Y, H:i'),
                'pagetitle'    => 'Student Promotion List',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid input.');
        } catch (\Throwable $e) {
            Log::error('Promotion student list error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return redirect()->back()->with('error', 'Failed to generate student list: ' . $e->getMessage());
        }
    }

    private function nextTermId(int $currentTermId): ?int
    {
        return Schoolterm::where('id', '>', $currentTermId)->orderBy('id')->value('id');
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

    // =========================================================================
    // CLEAR DECISION -- delete the saved decision for one student/term,
    // leaving their class placement alone. Undoable like everything else.
    // =========================================================================

    public function clearDecision(Request $request, $studentId): JsonResponse
    {
        $request->validate([
            'schoolclassid' => 'required|exists:schoolclass,id',
            'sessionid'     => 'required|exists:schoolsession,id',
            'termid'        => 'required|exists:schoolterm,id',
        ]);

        $batchId = (string) Str::uuid();

        try {
            $deleted = DB::transaction(function () use ($studentId, $request, $batchId) {
                $before = $this->snapshotStudentState((int) $studentId);

                $n = PromotionStatus::where('studentId',     $studentId)
                    ->where('schoolclassid', $request->schoolclassid)
                    ->where('sessionid',     $request->sessionid)
                    ->where('termid',        $request->termid)
                    ->delete();

                if ($n > 0) {
                    $this->logPromotionAction(
                        $batchId, 'clear_decision', (int) $studentId, $before,
                        (int) $request->schoolclassid, (int) $request->sessionid, (int) $request->termid,
                        $this->describeTarget('Cleared decision for', $request->schoolclassid, $request->sessionid, $request->termid)
                    );
                }
                return $n;
            });

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'No saved decision to clear for this term.'], 404);
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Decision cleared. You can undo this from History.',
                'batch_id' => $batchId,
            ]);
        } catch (Exception $e) {
            Log::error('Clear promotion decision failed', ['studentId' => $studentId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to clear decision.'], 500);
        }
    }

    // =========================================================================
    // HISTORY -- recent promotion actions (one row per click / batch), each
    // with its students, for the History & Undo panel.
    // =========================================================================

    public function history(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 30), 1), 100);

        $batchIds = PromotionActionLog::select('batch_id', DB::raw('MAX(id) as last_id'))
            ->when($request->filled('sessionid') && $request->input('sessionid') !== 'ALL',
                fn ($q) => $q->where('session_id', $request->input('sessionid')))
            ->groupBy('batch_id')
            ->orderByDesc('last_id')
            ->limit($limit)
            ->pluck('batch_id');

        $logs = PromotionActionLog::whereIn('batch_id', $batchIds)->orderBy('id')->get();

        $studentNames = DB::table('studentRegistration')
            ->whereIn('id', $logs->pluck('student_id')->unique())
            ->get(['id', 'admissionNo', 'firstname', 'lastname'])
            ->keyBy('id');
        $userNames = DB::table('users')
            ->whereIn('id', $logs->pluck('performed_by')->merge($logs->pluck('reverted_by'))->filter()->unique())
            ->pluck('name', 'id');

        $actionLabels = [
            'promote' => 'Promotion', 'bulk_promote' => 'Bulk promotion', 'advance_term' => 'Advance term',
            'clear_decision' => 'Decision cleared', 'remove' => 'Removed from class',
        ];

        $batches = $logs->groupBy('batch_id')
            ->map(function ($items, $batchId) use ($studentNames, $userNames, $actionLabels) {
                $first  = $items->first();
                $active = $items->whereNull('reverted_at');

                return [
                    'batch_id'       => $batchId,
                    'action'         => $first->action,
                    'action_label'   => $actionLabels[$first->action] ?? $first->action,
                    'description'    => $first->description,
                    'performed_by'   => $userNames[$first->performed_by] ?? 'System',
                    'performed_at'   => $first->created_at?->format('d M Y, H:i'),
                    'total'          => $items->count(),
                    'active'         => $active->count(),
                    'fully_reverted' => $active->isEmpty(),
                    'students'       => $items->map(function ($l) use ($studentNames, $userNames) {
                        $st = $studentNames->get($l->student_id);
                        return [
                            'student_id'  => (int) $l->student_id,
                            'name'        => $st ? trim($st->lastname . ', ' . $st->firstname) : 'Student #' . $l->student_id,
                            'admissionno' => $st->admissionNo ?? '',
                            'reverted'    => $l->reverted_at !== null,
                            'reverted_at' => $l->reverted_at?->format('d M Y, H:i'),
                            'reverted_by' => $l->reverted_by ? ($userNames[$l->reverted_by] ?? null) : null,
                        ];
                    })->values(),
                ];
            })
            ->sortByDesc(fn ($b) => $b['performed_at'])
            ->values();

        // keep newest-first order from $batchIds
        $order   = $batchIds->flip();
        $batches = $batches->sortBy(fn ($b) => $order[$b['batch_id']] ?? PHP_INT_MAX)->values();

        return response()->json(['success' => true, 'batches' => $batches]);
    }

    // =========================================================================
    // REVERT -- restore each student's studentclass / promotionStatus /
    // student_current_term rows to exactly what they were before the
    // action. Whole batch, or one student in it.
    //
    // Safety checks, per student:
    //  * a LATER, still-active promotion action for the same student must
    //    be reverted first (undo newest-first), and
    //  * if the rows were changed some other way since the action (another
    //    screen, a manual edit), it stops and asks -- `force` overrides.
    // =========================================================================

    public function revertBatch(Request $request, string $batchId): JsonResponse
    {
        $request->validate([
            'student_id' => 'nullable|integer',
            'force'      => 'boolean',
        ]);
        $force = $request->boolean('force');

        $logs = PromotionActionLog::where('batch_id', $batchId)
            ->whereNull('reverted_at')
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->orderByDesc('id')
            ->get();

        if ($logs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Nothing left to revert in this action.'], 404);
        }

        $names = DB::table('studentRegistration')->whereIn('id', $logs->pluck('student_id'))
            ->get(['id', 'firstname', 'lastname'])->keyBy('id');
        $nameOf = fn ($id) => ($n = $names->get($id)) ? trim($n->lastname . ', ' . $n->firstname) : "Student #{$id}";

        $reverted = 0;
        $blocked  = [];
        $drifted  = false;

        foreach ($logs as $log) {
            $later = PromotionActionLog::where('student_id', $log->student_id)
                ->where('id', '>', $log->id)
                ->whereNull('reverted_at')
                ->exists();
            if ($later) {
                $blocked[] = $nameOf($log->student_id) . ' — has a newer promotion action; undo that first.';
                continue;
            }

            try {
                $done = DB::transaction(function () use ($log, $force, &$drifted, &$blocked, $nameOf) {
                    $current = $this->snapshotStudentState((int) $log->student_id);
                    if (!$force && !$this->statesMatch($current, $log->after_state ?? [])) {
                        $drifted   = true;
                        $blocked[] = $nameOf($log->student_id) . ' — records changed since this action (outside this screen).';
                        return false;
                    }

                    $this->restoreStudentState((int) $log->student_id, $log->before_state ?? []);
                    $log->update(['reverted_at' => now(), 'reverted_by' => auth()->id()]);
                    return true;
                });
                if ($done) $reverted++;
            } catch (Exception $e) {
                Log::error('Promotion revert failed', ['log_id' => $log->id, 'error' => $e->getMessage()]);
                $blocked[] = $nameOf($log->student_id) . ' — failed: ' . $e->getMessage();
            }
        }

        $msg = $reverted
            ? "Reverted {$reverted} student(s)."
            : 'Nothing was reverted.';
        if ($blocked) $msg .= ' ' . count($blocked) . ' could not be reverted.';

        return response()->json([
            'success'     => $reverted > 0,
            'message'     => $msg,
            'reverted'    => $reverted,
            'blocked'     => $blocked,
            'can_force'   => $drifted,
        ], $reverted > 0 || $blocked ? 200 : 422);
    }

    // ── Undo-log helpers ────────────────────────────────────────────────────

    private const UNDO_TABLES = [
        'studentclass'         => 'studentId',
        'promotionStatus'      => 'studentId',
        'student_current_term' => 'studentId',
    ];

    /** Every row this screen can change for one student, as plain arrays. */
    private function snapshotStudentState(int $studentId): array
    {
        $snap = [];
        foreach (self::UNDO_TABLES as $table => $col) {
            $snap[$table] = DB::table($table)->where($col, $studentId)->orderBy('id')->get()
                ->map(fn ($r) => (array) $r)->values()->all();
        }
        return $snap;
    }

    /** Make the student's rows in each table exactly match $snapshot. */
    private function restoreStudentState(int $studentId, array $snapshot): void
    {
        foreach (self::UNDO_TABLES as $table => $col) {
            $rows    = $snapshot[$table] ?? [];
            $keepIds = array_values(array_filter(array_map(fn ($r) => $r['id'] ?? null, $rows)));

            DB::table($table)->where($col, $studentId)
                ->when(!empty($keepIds), fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();

            foreach ($rows as $row) {
                $id = $row['id'] ?? null;
                if ($id && DB::table($table)->where('id', $id)->exists()) {
                    DB::table($table)->where('id', $id)->update(array_diff_key($row, ['id' => true]));
                } else {
                    DB::table($table)->insert($row);
                }
            }
        }
    }

    /** Compare two snapshots, ignoring timestamps and int/string differences. */
    private function statesMatch(array $a, array $b): bool
    {
        $norm = function (array $snap) {
            $out = [];
            foreach (self::UNDO_TABLES as $table => $col) {
                $rows = [];
                foreach ($snap[$table] ?? [] as $r) {
                    unset($r['created_at'], $r['updated_at']);
                    ksort($r);
                    $rows[(string) ($r['id'] ?? '')] = array_map(fn ($v) => $v === null ? null : (string) $v, $r);
                }
                ksort($rows);
                $out[$table] = $rows;
            }
            return $out;
        };
        return $norm($a) == $norm($b);
    }

    private function logPromotionAction(
        string $batchId, string $action, int $studentId, array $before,
        $classId, $sessionId, $termId, ?string $description
    ): void {
        PromotionActionLog::create([
            'batch_id'       => $batchId,
            'action'         => $action,
            'student_id'     => $studentId,
            'schoolclass_id' => $classId ?: null,
            'session_id'     => $sessionId ?: null,
            'term_id'        => $termId ?: null,
            'description'    => $description ? Str::limit($description, 250, '') : null,
            'before_state'   => $before,
            'after_state'    => $this->snapshotStudentState($studentId),
            'performed_by'   => auth()->id(),
        ]);
    }

    private function describeTarget(string $verb, $classId, $sessionId, $termId): string
    {
        $cls = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('schoolclass.id', $classId)->first(['schoolclass.schoolclass', 'schoolarm.arm']);
        $parts = array_filter([
            $cls ? trim($cls->schoolclass . ' ' . ($cls->arm ?? '')) : null,
            Schoolsession::where('id', $sessionId)->value('session'),
            Schoolterm::where('id', $termId)->value('term'),
        ]);
        return trim($verb . ' ' . implode(' · ', $parts));
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Determine whether to skip the promotion evaluator entirely.
     *
     * Returns TRUE (skip) when:
     *   - No active settings exist for this class at all, OR
     *   - Active settings exist but none of them match this session+term.
     *
     * This mirrors the logic inside PromotionEvaluator::evaluate() so the
     * controller can avoid evaluating all 52 students when the answer is
     * trivially "awaiting" for every one of them.
     */
    private function classHasNoApplicableSetting(
        int $schoolclassId,
        int $sessionId,
        int $termId
    ): bool {
        $activeSettings = PromotionSetting::where('schoolclass_id', $schoolclassId)
            ->where('is_active', true)
            ->get(['id', 'session_id', 'term_id']);

        if ($activeSettings->isEmpty()) {
            return true; // No settings at all → skip
        }

        // Check whether any setting would score > 0 using the same matching
        // logic as findBestSettings() in the evaluator.
        foreach ($activeSettings as $setting) {
            $sid = $setting->session_id;
            $tid = $setting->term_id;

            // Exact session + term match
            if ($sid == $sessionId && $tid == $termId) return false;

            // Correct session, term is null = applies to all terms in this session
            if ($sid == $sessionId && $tid === null) return false;

            // No session constraint, correct term
            if ($sid === null && $tid == $termId) return false;

            // Global fallback (no session, no term)
            if ($sid === null && $tid === null) return false;

            // Different session with no term constraint — still a fallback match
            if ($sid !== null && $sid != $sessionId && $tid === null) return false;

            // All other combinations score 0 (wrong term, wrong session+term, etc.)
        }

        // No setting matched → skip evaluator
        return true;
    }

    /**
     * Broadsheet scores for one student in a class/session/term.
     *
     * total / cum / cum_ave are the values score entry maintains from the
     * class's DYNAMIC assessments (sum of broadsheet_assessment_scores;
     * cum = bf + total; cum_ave = cum / term) -- used as stored, never
     * re-derived from a fixed CA/exam formula.
     *
     * Only subjects the student is registered for count (same rule as the
     * broadsheet/position service). A student with no registrations at all
     * for the term falls back to every subject, like score entry does.
     */
    private function getStudentScores($studentId, $schoolclassId, $sessionId, $termId)
    {
        try {
            $q = Broadsheets::where('broadsheet_records.student_id', $studentId)
                ->where('broadsheets.term_id',               $termId)
                ->where('broadsheet_records.session_id',     $sessionId)
                ->where('broadsheet_records.schoolclass_id', $schoolclassId)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject',            'subject.id',             '=', 'broadsheet_records.subject_id');

            $this->scopeToRegisteredSubjects($q, (int) $sessionId, (int) $termId);

            return $q->select([
                    'subject.id            as subject_id',
                    'subject.subject       as subject_name',
                    'subject.subject_code  as subject_code',
                    'broadsheets.total     as total',
                    'broadsheets.bf        as bf',
                    'broadsheets.cum       as cum',
                    'broadsheets.cum_ave   as cum_ave',
                    'broadsheets.grade     as grade',
                ])
                ->get();

        } catch (Exception $e) {
            Log::error('Error getting student scores', [
                'student_id' => $studentId,
                'error'      => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Keep a broadsheet row only if the student is registered for that
     * subject this term -- or has no registrations at all this term.
     * Expects broadsheet_records to be joined.
     */
    private function scopeToRegisteredSubjects($query, int $sessionId, int $termId): void
    {
        $query->where(function ($w) use ($sessionId, $termId) {
            $w->whereExists(function ($r) use ($sessionId, $termId) {
                $r->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass as sjc_reg',   'sjc_reg.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->join('subjectteacher as st_reg', 'st_reg.id',  '=', 'sjc_reg.subjectteacherid')
                    ->whereColumn('st_reg.subjectid', 'broadsheet_records.subject_id')
                    ->whereColumn('subjectRegistrationStatus.studentid', 'broadsheet_records.student_id')
                    ->where('subjectRegistrationStatus.termid',    $termId)
                    ->where('subjectRegistrationStatus.sessionid', $sessionId);
            })->orWhereNotExists(function ($r) use ($sessionId, $termId) {
                $r->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->whereColumn('subjectRegistrationStatus.studentid', 'broadsheet_records.student_id')
                    ->where('subjectRegistrationStatus.termid',    $termId)
                    ->where('subjectRegistrationStatus.sessionid', $sessionId);
            });
        });
    }

    /**
     * Mean of the chosen per-subject figure, as a percentage (each subject
     * is out of 100). 'total' = this term's total, 'cum' = cum_ave (the
     * averaged cumulative score grading uses -- never the raw running sum).
     * Missing values are skipped, not counted as 0.
     */
    private function calculateOverallAverage($scores, string $basis = 'total'): ?float
    {
        if ($scores->isEmpty()) return null;

        $field  = $basis === 'cum' ? 'cum_ave' : 'total';
        $values = $scores->pluck($field)->filter(fn ($v) => $v !== null && is_numeric($v));

        return $values->isNotEmpty() ? round($values->avg(), 1) : 0;
    }

    private function resolveAverageBasis($basis): string
    {
        return in_array($basis, ['total', 'cum'], true) ? $basis : 'total';
    }

    /**
     * Students placed in this class/session now (studentclass) or with
     * results recorded for it (broadsheet_records).
     */
    private function cohortStudentIds(int $schoolclassId, int $sessionId): array
    {
        $current = Studentclass::where('schoolclassid', $schoolclassId)
            ->where('sessionid', $sessionId)
            ->pluck('studentId');

        $scored = DB::table('broadsheet_records')
            ->where('schoolclass_id', $schoolclassId)
            ->where('session_id',     $sessionId)
            ->distinct()
            ->pluck('student_id');

        return $current->merge($scored)->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
    }

    /**
     * [studentId => rank] for a class/session/term, ranked on each
     * student's average (same basis and registered-subject scoping as the
     * Overall Avg column). Competition ranking: 90, 90, 80 -> 1, 1, 3.
     */
    private function buildClassPositions(int $schoolclassId, int $sessionId, int $termId, string $basis): array
    {
        $field = $basis === 'cum' ? 'cum_ave' : 'total';

        $q = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.schoolclass_id', $schoolclassId)
            ->where('broadsheet_records.session_id',     $sessionId)
            ->where('broadsheets.term_id',               $termId)
            ->whereNotNull("broadsheets.{$field}");

        $this->scopeToRegisteredSubjects($q, $sessionId, $termId);

        $averages = $q->groupBy('broadsheet_records.student_id')
            ->select([
                'broadsheet_records.student_id as student_id',
                DB::raw("AVG(broadsheets.{$field}) as avg_value"),
            ])
            ->pluck('avg_value', 'student_id')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();

        return $this->competitionRank($averages);
    }

    private function competitionRank(array $valuesById): array
    {
        if (empty($valuesById)) return [];

        arsort($valuesById);

        $ranks = [];
        $position = 0;
        $prevValue = null;
        $prevRank  = null;

        foreach ($valuesById as $studentId => $value) {
            $position++;
            $rank = ($prevValue !== null && $value == $prevValue) ? $prevRank : $position;
            $ranks[(int) $studentId] = $rank;
            $prevValue = $value;
            $prevRank  = $rank;
        }

        return $ranks;
    }

    private function formatOrdinal(?int $position): ?string
    {
        if ($position === null) return null;
        if ($position % 100 >= 11 && $position % 100 <= 13) return "{$position}th";

        return match ($position % 10) {
            1       => "{$position}st",
            2       => "{$position}nd",
            3       => "{$position}rd",
            default => "{$position}th",
        };
    }

    /**
     * Every session/term the student has been in, newest first, with the
     * class for that term and the decision saved for it. Built from results
     * (broadsheet_records), promotionStatus and studentclass together --
     * studentclass alone only holds the current placement.
     */
    private function buildClassHistory(int $studentId): array
    {
        $periods = []; // "session_term" => [classId => weight]
        $add = function ($sess, $term, $class, int $weight) use (&$periods) {
            if (!$sess || !$term) return;
            $key = (int) $sess . '_' . (int) $term;
            $periods[$key] = $periods[$key] ?? [];
            if ($class) $periods[$key][(int) $class] = ($periods[$key][(int) $class] ?? 0) + $weight;
        };

        DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.student_id', $studentId)
            ->groupBy('broadsheet_records.session_id', 'broadsheets.term_id', 'broadsheet_records.schoolclass_id')
            ->select([
                'broadsheet_records.session_id', 'broadsheets.term_id',
                'broadsheet_records.schoolclass_id', DB::raw('COUNT(*) as n'),
            ])
            ->get()
            ->each(fn ($r) => $add($r->session_id, $r->term_id, $r->schoolclass_id, 1000 + (int) $r->n));

        $statuses = PromotionStatus::where('studentId', $studentId)->get();
        $statuses->each(fn ($r) => $add($r->sessionid, $r->termid, $r->schoolclassid, 10));

        Studentclass::where('studentId', $studentId)->get(['sessionid', 'termid', 'schoolclassid'])
            ->each(fn ($r) => $add($r->sessionid, $r->termid, $r->schoolclassid, 1));

        if (empty($periods)) return [];

        $sessionNames = Schoolsession::pluck('session', 'id');
        $termNames    = Schoolterm::pluck('term', 'id');
        $classIds     = collect($periods)->flatMap(fn ($c) => array_keys($c))->unique()->values();
        $classInfo    = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->whereIn('schoolclass.id', $classIds)
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
            ->keyBy('id');

        return collect($periods)
            ->map(function ($classes, $key) use ($sessionNames, $termNames, $classInfo, $statuses) {
                [$sess, $term] = array_map('intval', explode('_', $key));
                arsort($classes);
                $classId = array_key_first($classes);
                $cls     = $classId ? $classInfo->get($classId) : null;
                $status  = $statuses->first(fn ($p) =>
                    (int) $p->sessionid === $sess && (int) $p->termid === $term
                    && (!$classId || (int) $p->schoolclassid === (int) $classId)
                );

                return [
                    'session_id'       => $sess,
                    'term_id'          => $term,
                    'session'          => $sessionNames[$sess] ?? null,
                    'term'             => $termNames[$term] ?? null,
                    'class'            => $cls?->schoolclass,
                    'arm'              => $cls?->arm,
                    'promotion_status' => $status?->promotionStatus,
                ];
            })
            ->sortByDesc(fn ($h) => sprintf('%08d-%08d', $h['session_id'], $h['term_id']))
            ->values()
            ->all();
    }

    /**
     * Determine pass/fail for one subject.
     * Compulsory → evaluated against configured min_grade.
     * Optional   → only fail vs general fail threshold (F / F9 / E8).
     */
    private function determinePassStatus(?string $grade, ?string $requiredMinGrade, bool $isCompulsory): string
    {
        if (!$isCompulsory) {
            if ($grade === null || $grade === '') return 'optional_not_sat';
            return in_array(strtoupper(trim($grade)), ['F', 'F9', 'E8'], true)
                ? 'optional_fail'
                : 'optional_pass';
        }

        if (!$grade) return 'not_sat';
        return $this->gradePassFail($grade, $requiredMinGrade) ? 'pass' : 'fail';
    }

    private function getPassStatusLabel(string $status): string
    {
        return match ($status) {
            'pass',    'optional_pass'    => 'Passed',
            'fail',    'optional_fail'    => 'Failed',
            'not_sat', 'optional_not_sat' => 'Not Attempted',
            'optional'                   => 'Optional',
            default                      => 'Unknown',
        };
    }

    private function getPassStatusClass(string $status): string
    {
        return match ($status) {
            'pass',    'optional_pass'    => 'success',
            'fail',    'optional_fail'    => 'danger',
            'not_sat', 'optional_not_sat' => 'warning',
            'optional'                   => 'info',
            default                      => 'secondary',
        };
    }

    private function gradePassFail(?string $studentGrade, ?string $minGrade): bool
    {
        if ($studentGrade === null) return false;

        $gradeOrder = [
            'F9' => 0, 'E8' => 1, 'D7' => 2,
            'C6' => 3, 'C5' => 4, 'C4' => 5,
            'B3' => 6, 'B2' => 7, 'A1' => 8,
            'F'  => 0, 'D'  => 2, 'C'  => 5,
            'B'  => 7, 'A'  => 8,
        ];

        $sg = strtoupper(trim($studentGrade));

        if ($minGrade) {
            $mg          = strtoupper(trim($minGrade));
            $studentRank = $gradeOrder[$sg] ?? -1;
            $minRank     = $gradeOrder[$mg] ?? 0;
            return $studentRank >= $minRank;
        }

        return !in_array($sg, ['F', 'F9'], true);
    }

    private function getCreditGrades($schoolclassId): array
    {
        $classCategory = DB::table('schoolclass_classcategory')
            ->join('classcategories', 'classcategories.id', '=',
                'schoolclass_classcategory.classcategory_id')
            ->where('schoolclass_classcategory.schoolclass_id', $schoolclassId)
            ->select('classcategories.is_senior')
            ->first();

        return ($classCategory && $classCategory->is_senior)
            ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6']
            : ['A', 'B', 'C'];
    }
}
