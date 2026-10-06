<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Schoolarm;
use Illuminate\View\View;
use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use App\Models\Classcategory;
use App\Models\Schoolsession;
use Illuminate\Http\Response;
use App\Models\PromotionStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SchoolInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\CompulsorySubjectClass;
use App\Models\AttendanceSummary;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetsMock;
use App\Models\Studentpersonalityprofile;
use App\Services\PromotionEvaluator;
use App\Services\ClassPositionService;
use Illuminate\Pagination\LengthAwarePaginator;

class ViewStudentReportController extends Controller
{
    protected ClassPositionService $positionService;

    public function __construct(ClassPositionService $positionService)
    {
        $this->positionService = $positionService;

        $this->middleware('permission:View student-report', ['only' => [
            'index', 'show', 'registeredClasses', 'classBroadsheet',
            'studentresult', 'studentmockresult', 'exportStudentResultPdf', 'exportClassResultsPdf'
        ]]);
        $this->middleware('permission:Create student-report', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update student-report', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete student-report', ['only' => ['destroy']]);

        Log::channel('pdf')->info('ViewStudentReportController initialized', ['timestamp' => now()]);
    }

    // =========================================================================
    // FORMAT ORDINAL
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

    protected function calculateGrade($score)
    {
        return $this->calculateSeniorGrade($score);
    }

    protected function getGradePoint($score)
    {
        if ($score === null || $score == 0) return 0.0;
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

    protected function getGpaGrade($gpa)
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

    // =========================================================================
    // GPA / CGPA
    // =========================================================================

    protected function computeOverallGPAAndCGPAForStudent($studentId, $schoolclass, $termId, $sessionId)
    {
        $classIds = Schoolclass::where('schoolclass', $schoolclass->schoolclass)->pluck('id')->toArray();

        $currentTermBroadsheets = Broadsheets::where('broadsheets.term_id', $termId)
            ->whereHas('broadsheetRecord', function ($q) use ($studentId, $sessionId) {
                $q->where('student_id', $studentId)->where('session_id', $sessionId);
            })
            ->whereExists(function ($query) use ($studentId, $termId, $sessionId, $classIds) {
                $query->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')
                    ->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')
                    ->whereIn('subjectclass.schoolclassid', $classIds)
                    ->where('subjectRegistrationStatus.studentid', $studentId)
                    ->where('subjectRegistrationStatus.termid', $termId)
                    ->where('subjectRegistrationStatus.sessionid', $sessionId);
            })
            ->get(['broadsheets.total']);

        $termGradePoints    = $currentTermBroadsheets->map(fn($b) => $this->getGradePoint($b->total));
        $gpa                = $termGradePoints->avg() ?? 0.0;
        $num_subjects       = $currentTermBroadsheets->count();
        $total_grade_points = $termGradePoints->sum();

        $termGPAs = [];
        for ($t = 1; $t <= $termId; $t++) {
            $termBroadsheets = Broadsheets::where('broadsheets.term_id', $t)
                ->whereHas('broadsheetRecord', function ($q) use ($studentId, $sessionId) {
                    $q->where('student_id', $studentId)->where('session_id', $sessionId);
                })
                ->whereExists(function ($query) use ($studentId, $t, $sessionId, $classIds) {
                    $query->select(DB::raw(1))
                        ->from('subjectRegistrationStatus')
                        ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                        ->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')
                        ->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')
                        ->whereIn('subjectclass.schoolclassid', $classIds)
                        ->where('subjectRegistrationStatus.studentid', $studentId)
                        ->where('subjectRegistrationStatus.termid', $t)
                        ->where('subjectRegistrationStatus.sessionid', $sessionId);
                })
                ->get(['broadsheets.total']);

            if ($termBroadsheets->isNotEmpty()) {
                $gp  = $termBroadsheets->map(fn($b) => $this->getGradePoint($b->total));
                $tGPA = $gp->avg() ?? 0.0;
                if ($tGPA > 0) $termGPAs[] = $tGPA;
            }
        }

        $cgpa     = !empty($termGPAs) ? collect($termGPAs)->avg() : 0.0;
        $gpaGrade = $this->getGpaGrade($gpa);

        return [
            'gpa'                => round($gpa, 2),
            'cgpa'               => round($cgpa, 2),
            'gpa_grade'          => $gpaGrade,
            'num_subjects'       => $num_subjects,
            'total_grade_points' => round($total_grade_points, 1),
            'calculated_gpa'     => $num_subjects > 0 ? round($total_grade_points / $num_subjects, 2) : 0.0,
        ];
    }

    // =========================================================================
    // CLASS POSITIONS AND AVERAGES
    //
    // FIX (Aug 2026): this now delegates to ClassPositionService, which
    // computes arm positions per arm-group instead of relative to a single
    // fixed arm. The old inline version here nulled out arm_position /
    // arm_position_cum for every arm other than the one that triggered the
    // recalculation, which is why some students' Arm Pos / Class Pos columns
    // intermittently went missing depending on which arm's report was last
    // generated. See ClassPositionService::recalculate() for details.
    // =========================================================================

    protected function calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid)
    {
        return $this->positionService->recalculate($schoolclassid, $sessionid, $termid);
    }

    // =========================================================================
    // ATTENDANCE SUMMARY
    // =========================================================================

    protected function getAttendanceSummary($studentId, $schoolclassId, $termId, $sessionId): array
    {
        try {
            $record = AttendanceSummary::where('student_id', $studentId)
                ->where('schoolclass_id', $schoolclassId)
                ->where('term_id', $termId)
                ->where('session_id', $sessionId)
                ->first();

            if ($record) {
                return [
                    'total_school_days'     => $record->total_school_days    ?? 0,
                    'days_present'          => $record->days_present         ?? 0,
                    'days_absent'           => $record->days_absent          ?? 0,
                    'days_sick_leave'       => $record->days_sick_leave      ?? 0,
                    'days_excused'          => $record->days_excused         ?? 0,
                    'days_late'             => $record->days_late            ?? 0,
                    'attendance_percentage' => $record->attendance_percentage ?? 0.0,
                    'found'                 => true,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error fetching attendance summary', [
                'student_id' => $studentId, 'error' => $e->getMessage(),
            ]);
        }

        return [
            'total_school_days' => 0, 'days_present' => 0, 'days_absent' => 0,
            'days_sick_leave'   => 0, 'days_excused'  => 0, 'days_late'   => 0,
            'attendance_percentage' => 0.0, 'found' => false,
        ];
    }

    // =========================================================================
    // GET STUDENT RESULT DATA - FIXED WITH TOTAL CALCULATION
    // =========================================================================

    private function getStudentResultData($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            if (!is_numeric($id) || !is_numeric($schoolclassid) || !is_numeric($sessionid) || !is_numeric($termid)) {
                Log::error('Invalid parameters in getStudentResultData', [
                    'student_id' => $id,
                    'schoolclassid' => $schoolclassid,
                    'sessionid' => $sessionid,
                    'termid' => $termid,
                ]);
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
                    'studentRegistration.home_address2 as permanent_address',
                    'studentRegistration.updated_at as updated_at',
                    'studentpicture.picture as picture',
                ])
                ->orderBy('studentRegistration.lastname', 'asc')
                ->get();

            if ($students->isEmpty()) $students = collect([]);

            $schoolclass = Schoolclass::with(['arms', 'classcategories'])->find($schoolclassid);
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
                    Log::error('Error loading assessments', ['error' => $e->getMessage()]);
                }
            }

            // Used to determine which grading scale (senior WAEC-style vs junior
            // letter grade) applies when deriving subject grades below.
            $isSenior = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? ($schoolclass->classcategories->first()->is_senior ?? false)
                : false;

            // Fetch the broadsheet records
            $scores = Broadsheets::where('broadsheet_records.student_id', $id)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->where('broadsheet_records.schoolclass_id', $schoolclassid)
                ->whereExists(function ($query) use ($id, $termid, $sessionid, $schoolclassid) {
                    $query->select(DB::raw(1))
                        ->from('subjectRegistrationStatus')
                        ->join('subjectclass as sjc_reg', 'sjc_reg.id', '=', 'subjectRegistrationStatus.subjectclassid')
                        ->join('subjectteacher as st_reg', 'st_reg.id', '=', 'sjc_reg.subjectteacherid')
                        ->whereColumn('st_reg.subjectid', 'broadsheet_records.subject_id')
                        ->where('subjectRegistrationStatus.studentid', $id)
                        ->where('subjectRegistrationStatus.termid', $termid)
                        ->where('subjectRegistrationStatus.sessionid', $sessionid)
                        ->where('sjc_reg.schoolclassid', $schoolclassid);
                })
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'subject.id as subject_id',
                    'subject.subject as subject_name',
                    'subject.subject_code',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.cum_ave',
                    'broadsheets.grade',
                    'broadsheets.remark',
                    'broadsheets.subject_position_class as position',
                    'broadsheets.subject_position_class_total as position_total',
                    'broadsheets.arm_position as arm_position',
                    'broadsheets.arm_position_cum as arm_position_cum',
                    'broadsheets.avg as class_average',
                    'broadsheets.id as broadsheet_id',
                    'broadsheets.vettedstatus',
                ])->get();

            // Process each score - CALCULATE TOTAL FROM ASSESSMENT SCORES
            foreach ($scores as $score) {
                $score->position_formatted         = ($score->position      && $score->position      > 0) ? $this->formatOrdinal($score->position)      : '-';
                $score->position_total_formatted   = ($score->position_total && $score->position_total > 0) ? $this->formatOrdinal($score->position_total) : '-';
                $score->arm_position_formatted     = ($score->arm_position  && $score->arm_position  > 0) ? $this->formatOrdinal($score->arm_position)  : '-';
                $score->arm_position_cum_formatted = ($score->arm_position_cum && $score->arm_position_cum > 0) ? $this->formatOrdinal($score->arm_position_cum) : '-';

                // --- FIX: CALCULATE TOTAL FROM ASSESSMENT SCORES ---
                try {
                    if (class_exists(\App\Models\BroadsheetAssessmentScore::class)) {
                        $assessmentScores = \App\Models\BroadsheetAssessmentScore::where('broadsheet_id', $score->broadsheet_id)
                            ->with('assessment')
                            ->orderBy('assessment_id')
                            ->get();

                        $arr = $assessmentScores->values();
                        $ca1 = $arr->count() > 0 ? (float)($arr->get(0)->score ?? 0) : 0;
                        $ca2 = $arr->count() > 1 ? (float)($arr->get(1)->score ?? 0) : 0;
                        $ca3 = $arr->count() > 2 ? (float)($arr->get(2)->score ?? 0) : 0;
                        $exam = $arr->count() > 3 ? (float)($arr->get(3)->score ?? 0) : 0;

                        $score->ca1 = $ca1;
                        $score->ca2 = $ca2;
                        $score->ca3 = $ca3;
                        $score->exam = $exam;

                        // CALCULATE TOTAL FROM INDIVIDUAL SCORES
                        $calculatedTotal = $ca1 + $ca2 + $ca3 + $exam;
                        $storedTotal = (float)($score->total ?? 0);

                        // ALWAYS use calculated total
                        $score->total = $calculatedTotal;
                        $score->total_stored_original = $storedTotal;
                        $score->total_calculated = $calculatedTotal;

                        // FIX (Aug 2026): grade must be determined by the Cumulative
                        // Average (cum_ave), not by the current-term Total. The grade
                        // previously came straight from the stored broadsheets.grade
                        // value, which was derived from Total upstream — that's why
                        // grades looked like they tracked Total instead of Cum. Ave.
                        // Recomputing it here from cum_ave keeps it consistent no
                        // matter how the stored grade was originally derived.
                        // Grade is based on the ROUNDED cum_ave (same value shown in
                        // the Cum Ave column) so the displayed grade always matches
                        // the displayed cum_ave, rather than a hidden decimal value.
                        $gradeSource   = (float) round($score->cum_ave ?? 0);
                        $score->grade  = $isSenior
                            ? $this->calculateSeniorGrade($gradeSource)
                            : $this->calculateJuniorGrade($gradeSource);
                        $score->remark = $this->getRemark($score->grade);

                        // Log discrepancy for debugging
                        if (abs($calculatedTotal - $storedTotal) > 0.01) {
                            Log::warning('Total discrepancy - USING CALCULATED VALUE', [
                                'student_id' => $id,
                                'subject' => $score->subject_name,
                                'stored' => $storedTotal,
                                'calculated' => $calculatedTotal,
                                'ca1' => $ca1,
                                'ca2' => $ca2,
                                'ca3' => $ca3,
                                'exam' => $exam
                            ]);
                        }

                        $score->assessment_scores = $assessmentScores;
                        $score->assessments       = $assessments;
                    }
                } catch (\Exception $e) {
                    Log::error('Error loading assessment scores', [
                        'error' => $e->getMessage(),
                        'broadsheet_id' => $score->broadsheet_id
                    ]);
                    $score->total = (float)($score->total ?? 0);
                    $score->ca1 = 0;
                    $score->ca2 = 0;
                    $score->ca3 = 0;
                    $score->exam = 0;
                }
            }

            // Recalculate totals summary
            $totalObtained   = 0;
            $totalObtainable = 0;

            foreach ($scores as $score) {
                if ($score->total !== null && is_numeric($score->total)) {
                    $totalObtained += (float) $score->total;
                }
                $totalObtainable += 100;
            }

            $totalPercentage = $totalObtainable > 0
                ? round(($totalObtained / $totalObtainable) * 100, 1)
                : 0;

            $totalsSummary = [
                'obtained'   => round($totalObtained, 1),
                'obtainable' => $totalObtainable,
                'percentage' => $totalPercentage,
            ];

            // GPA/CGPA - use the scores with corrected totals
            $gpaData = [];
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                try {
                    // Use the corrected scores for GPA calculation
                    $gpaData = $this->computeGPAFromScores($id, $schoolclass, $termid, $sessionid, $scores);
                } catch (\Exception $e) {
                    Log::error('Error calculating GPA/CGPA', ['student_id' => $id, 'error' => $e->getMessage()]);
                    $gpaData = ['gpa' => 0.0, 'cgpa' => 0.0, 'gpa_grade' => 'F9', 'num_subjects' => 0, 'total_grade_points' => 0, 'calculated_gpa' => 0.0];
                }
            }

            // Compulsory subjects
            $compulsorySubjects = [];
            try {
                $compulsorySubjects = CompulsorySubjectClass::where('schoolclassid', $schoolclassid)
                    ->pluck('subjectId')
                    ->toArray();
            } catch (\Exception $e) {
                Log::error('Error fetching compulsory subjects', ['schoolclassid' => $schoolclassid, 'error' => $e->getMessage()]);
            }

            foreach ($scores as $score) {
                $score->is_compulsory = in_array($score->subject_id, $compulsorySubjects);
            }

            // Promotion evaluation
            $promotionResult = [
                'status'              => 'awaiting',
                'is_promotional_term' => false,
                'failed_compulsory'   => [],
                'average_failed'      => false,
                'required_average'    => null,
                'actual_average'      => null,
                'compulsory_count'    => 0,
                'passed_compulsory'   => 0,
            ];

            try {
                $evaluator       = new PromotionEvaluator();
                $promotionResult = $evaluator->evaluate(
                    studentId:      $id,
                    schoolclassid:  $schoolclassid,
                    termid:         $termid,
                    sessionid:      $sessionid,
                    scores:         $scores,
                    overallAverage: $totalsSummary['percentage']
                );
            } catch (\Exception $e) {
                Log::error('Error evaluating promotion status', ['student_id' => $id, 'error' => $e->getMessage()]);
            }

            // Personality profile
            try {
                $studentpp = Studentpersonalityprofile::where('studentpersonalityprofiles.studentid', $id)
                    ->where('studentpersonalityprofiles.termid', $termid)
                    ->where('studentpersonalityprofiles.sessionid', $sessionid)
                    ->where('studentpersonalityprofiles.schoolclassid', $schoolclassid)
                    ->join('schoolsession', 'schoolsession.id', '=', 'studentpersonalityprofiles.sessionid')
                    ->join('schoolterm', 'schoolterm.id', '=', 'studentpersonalityprofiles.termid')
                    ->join('schoolclass', 'schoolclass.id', '=', 'studentpersonalityprofiles.schoolclassid')
                    ->select(
                        'studentpersonalityprofiles.*',
                        'schoolsession.session as session',
                        'schoolterm.term as term',
                        'schoolclass.schoolclass as schoolclass'
                    )
                    ->get();

                if ($studentpp->isEmpty()) $studentpp = collect();
            } catch (\Exception $e) {
                Log::error('Error fetching student personality profile', ['student_id' => $id, 'error' => $e->getMessage()]);
                $studentpp = collect();
            }

            $schoolsession    = Schoolsession::where('id', $sessionid)->first();
            $schoolterm       = Schoolterm::where('id', $termid)->first();
            $numberOfStudents = Studentclass::where('schoolclassid', $schoolclassid)->where('sessionid', $sessionid)->count();

            $schoolInfo = SchoolInformation::first();
            if (!$schoolInfo) {
                $schoolInfo                        = new \stdClass();
                $schoolInfo->id                    = 0;
                $schoolInfo->school_name           = 'School Name Not Found';
                $schoolInfo->school_logo           = null;
                $schoolInfo->school_stamp          = null;
                $schoolInfo->school_motto          = 'Motto Not Found';
                $schoolInfo->school_address        = 'Address Not Found';
                $schoolInfo->school_phone          = 'Phone Not Found';
                $schoolInfo->date_school_opened    = null;
                $schoolInfo->date_next_term_begins = null;
            } else {
                $schoolInfo->school_stamp = $schoolInfo->school_stamp ?? null;
            }

            $promotionStatusValue = null;
            try {
                $promotionStatus = PromotionStatus::where('student_id', $id)
                    ->where('session_id', $sessionid)
                    ->where('term_id', $termid)
                    ->first();
                if ($promotionStatus) $promotionStatusValue = $promotionStatus->status;
            } catch (\Exception $e) {
                Log::error('Error fetching promotion status', ['student_id' => $id, 'error' => $e->getMessage()]);
            }

            $attendanceSummary = $this->getAttendanceSummary($id, $schoolclassid, $termid, $sessionid);

            return [
                'students'             => $students,
                'studentpp'            => $studentpp,
                'scores'               => $scores,
                'studentid'            => $id,
                'schoolclassid'        => $schoolclassid,
                'sessionid'            => $sessionid,
                'termid'               => $termid,
                'schoolclass'          => $schoolclass,
                'schoolterm'           => $schoolterm,
                'schoolsession'        => $schoolsession,
                'numberOfStudents'     => $numberOfStudents,
                'schoolInfo'           => $schoolInfo,
                'promotionStatusValue' => $promotionStatusValue,
                'assessments'          => $assessments,
                'compulsorySubjects'   => $compulsorySubjects,
                'gpa_data'             => $gpaData,
                'totals_summary'       => $totalsSummary,
                'attendance_summary'   => $attendanceSummary,
                'promotion_result'     => $promotionResult,
            ];

        } catch (Exception $e) {
            Log::channel('pdf')->error('ERROR in getStudentResultData', [
                'student_id' => $id, 'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(), 'error_line' => $e->getLine(),
            ]);
            return [];
        }
    }

    /**
     * Compute GPA from already-corrected scores
     */
    private function computeGPAFromScores($studentId, $schoolclass, $termId, $sessionId, $scores)
    {
        $classIds = Schoolclass::where('schoolclass', $schoolclass->schoolclass)->pluck('id')->toArray();

        // FIX (Aug 2026): grade points must track Cumulative Average (cum_ave),
        // not the current-term Total, so GPA stays consistent with the subject
        // grades derived in getStudentResultData() above. Uses the rounded
        // cum_ave to match the grade/cum_ave shown on the report.
        $termGradePoints = $scores->map(function($score) {
            return $this->getGradePoint(round($score->cum_ave ?? 0));
        });

        $gpa                = $termGradePoints->avg() ?? 0.0;
        $num_subjects       = $scores->count();
        $total_grade_points = $termGradePoints->sum();

        // Calculate CGPA from previous terms
        $termGPAs = [];
        for ($t = 1; $t <= $termId; $t++) {
            if ($t == $termId) continue;

            $termBroadsheets = Broadsheets::where('broadsheets.term_id', $t)
                ->whereHas('broadsheetRecord', function ($q) use ($studentId, $sessionId) {
                    $q->where('student_id', $studentId)->where('session_id', $sessionId);
                })
                ->whereExists(function ($query) use ($studentId, $t, $sessionId, $classIds) {
                    $query->select(DB::raw(1))
                        ->from('subjectRegistrationStatus')
                        ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                        ->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')
                        ->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')
                        ->whereIn('subjectclass.schoolclassid', $classIds)
                        ->where('subjectRegistrationStatus.studentid', $studentId)
                        ->where('subjectRegistrationStatus.termid', $t)
                        ->where('subjectRegistrationStatus.sessionid', $sessionId);
                })
                ->get(['broadsheets.cum_ave']);

            if ($termBroadsheets->isNotEmpty()) {
                $gp  = $termBroadsheets->map(fn($b) => $this->getGradePoint(round($b->cum_ave ?? 0)));
                $tGPA = $gp->avg() ?? 0.0;
                if ($tGPA > 0) $termGPAs[] = $tGPA;
            }
        }

        $cgpa     = !empty($termGPAs) ? collect($termGPAs)->avg() : 0.0;
        $gpaGrade = $this->getGpaGrade($gpa);

        return [
            'gpa'                => round($gpa, 2),
            'cgpa'               => round($cgpa, 2),
            'gpa_grade'          => $gpaGrade,
            'num_subjects'       => $num_subjects,
            'total_grade_points' => round($total_grade_points, 1),
            'calculated_gpa'     => $num_subjects > 0 ? round($total_grade_points / $num_subjects, 2) : 0.0,
        ];
    }

    // =========================================================================
    // COLUMN OPTIONS
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
                // 'admission_no' => ['label' => 'Admission No',  'default' => true],
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

    // =========================================================================
    // GRADE PREVIEW
    // =========================================================================

    public function calculateGradePreview(Request $request)
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'total'          => 'required|numeric|min:0|max:100',
        ]);

        $schoolclass = Schoolclass::with('classcategories')->find($request->schoolclass_id);
        $isSenior = $schoolclass && $schoolclass->classcategories->isNotEmpty()
            ? ($schoolclass->classcategories->first()->is_senior ?? false)
            : false;

        if ($isSenior) {
            $grade = $this->calculateSeniorGrade($request->total);
        } else {
            $grade = $this->calculateJuniorGrade($request->total);
        }

        return response()->json(['grade' => $grade]);
    }

    // =========================================================================
    // STUDENT RESULT VIEWS
    // =========================================================================

    public function studentresult($id, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle         = "Student Personality Profile";
        $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);

        if (!$metricsCalculated) {
            return back()->with('error', 'Failed to calculate class metrics. Please try again.');
        }

        $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);
        return view('studentreports.studentresult')->with($data)->with('pagetitle', $pagetitle);
    }

    public function studentmockresult($id, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle         = "Student Mock Result";
        $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);

        if (!$metricsCalculated) {
            return back()->with('error', 'Failed to calculate class metrics. Please try again.');
        }

        $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);
        return view('studentreports.studentmockresult')->with($data)->with('pagetitle', $pagetitle);
    }

    // =========================================================================
    // CLASS BROADSHEET
    // =========================================================================

    public function classBroadsheet($schoolclassid, $sessionid, $termid): View
    {
        $class     = Schoolclass::findOrFail($schoolclassid);
        $session   = Schoolsession::findOrFail($sessionid);
        $pagetitle = "Broadsheet for {$class->schoolclass} - {$session->session} - Term {$termid}";

        return view('studentreports.broadsheet', [
            'class'     => $class,
            'session'   => $session,
            'term'      => $termid,
            'pagetitle' => $pagetitle,
        ]);
    }

    // =========================================================================
    // REGISTERED CLASSES
    // =========================================================================

    public function registeredClasses(Request $request)
    {
        $classId   = $request->query('class_id');
        $sessionId = $request->query('session_id');

        if (!$classId || !$sessionId || $classId === 'ALL' || $sessionId === 'ALL') {
            return response()->json(['success' => false, 'message' => 'Please select a valid class and session.'], 400);
        }

        // The session is already chosen explicitly via $sessionId, so no
        // "Current" status restriction is applied — any session works.
        $classes = Studentclass::query()
            ->join('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolclass.id', $classId)
            ->where('schoolsession.id', $sessionId)
            ->groupBy('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm', 'schoolsession.session')
            ->selectRaw('schoolclass.schoolclass as class_name, schoolarm.arm as name_arm, schoolsession.session as session_name, COUNT(DISTINCT studentclass.studentId) as student_count')
            ->get();

        return response()->json(['success' => true, 'data' => $classes]);
    }

    // =========================================================================
    // FORCE RECALCULATE POSITIONS (DEBUG)
    // =========================================================================

    public function forceRecalculatePositions($schoolclassid, $sessionid, $termid)
    {
        try {
            $result = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Positions recalculated successfully' : 'Failed to recalculate',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // PDF EXPORTS
    // =========================================================================

    public function exportStudentResultPdf($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            ini_set('max_execution_time', 600);

            // Uses the class export layout (the old studentresult_pdf view never existed).
            $pdf = $this->renderReportCardPdf((int) $id, (int) $schoolclassid, (int) $sessionid, (int) $termid);
            if (!$pdf) {
                return back()->with('error', 'No result data found for this student.');
            }

            $student  = Student::find($id);
            $name     = $student ? preg_replace('/[^A-Za-z0-9_-]+/', '_', $student->firstname . '_' . $student->lastname) : 'Student';
            $session  = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) Schoolsession::where('id', $sessionid)->value('session'));
            $filename = 'Terminal_Report_' . $name . '_' . $session . '_Term_' . $termid . '.pdf';

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (Exception $e) {
            Log::channel('pdf')->error('ERROR SINGLE STUDENT PDF', ['student_id' => $id, 'error_message' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * One student's terminal report card as PDF bytes (same layout as the
     * class export). Used by "Send results to parents".
     * Pass $recalculate = false when positions were already refreshed for this class.
     */
    public function renderReportCardPdf(int $studentId, int $schoolclassid, int $sessionid, int $termid, bool $recalculate = true): ?string
    {
        @ini_set('memory_limit', '1024M');

        if ($recalculate && !$this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid)) {
            return null;
        }

        $studentData = $this->getStudentResultData($studentId, $schoolclassid, $sessionid, $termid);
        if (empty($studentData) || empty($studentData['students']) || $studentData['students']->isEmpty()) {
            return null;
        }
        $studentData['selected_columns'] = [];
        $all = [$studentData];
        $this->fixImagePaths($all);

        $schoolclass = Schoolclass::where('id', $schoolclassid)->with(['arms', 'classcategories'])->first(['id', 'schoolclass', 'arm']);
        $className   = $schoolclass ? ($schoolclass->schoolclass . ($schoolclass->arms ? $schoolclass->arms->arm : '')) : 'Class';

        $pdf = Pdf::loadView('studentreports.class_results_pdf', [
            'allStudentData' => $all,
            'metadata'       => [
                'class_name'       => $className,
                'session'          => Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A',
                'term'             => $this->getTermName($termid),
                'generation_date'  => now()->format('Y-m-d H:i:s'),
                'student_count'    => 1,
                'selected_columns' => [],
                'grade_basis'      => 'total',
            ],
        ])->setPaper('A4', 'portrait')->setOptions([
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
            'enable_css_float'        => true,
        ]);

        $out = $pdf->output();
        return $out !== '' ? $out : null;
    }

    public function exportClassResultsPdf(Request $request)
    {
        try {
            $this->checkServerRequirements();
            $this->debugStorageStructure();

            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '512M');

            $schoolclassid   = $request->input('schoolclassid');
            $sessionid       = $request->input('sessionid');
            $termid          = $request->input('termid', 3);
            $studentIds      = $request->input('studentIds', []);
            $selectedColumns = $request->input('selectedColumns', []);
            $gradeBasis      = $request->input('grade_basis', 'total');
            if (!in_array($gradeBasis, ['total', 'cum_ave'], true)) {
                $gradeBasis = 'total';
            }

            if (!$schoolclassid || !$sessionid || !$termid) {
                return response()->json(['success' => false, 'message' => 'Missing required parameters'], 400);
            }

            $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
            if (!$metricsCalculated) {
                return response()->json(['success' => false, 'message' => 'Failed to calculate class metrics.'], 500);
            }

            $allStudentData = [];
            $failedCount    = 0;

            foreach ($studentIds as $studentId) {
                $studentData = $this->getStudentResultData($studentId, $schoolclassid, $sessionid, $termid);

                if (!empty($studentData) && !empty($studentData['students']) && $studentData['students']->isNotEmpty()) {
                    $studentData['selected_columns'] = $selectedColumns;
                    $allStudentData[]                = $studentData;
                } else {
                    $failedCount++;
                    Log::warning('Skipped student due to empty data', ['student_id' => $studentId]);
                }
            }

            if (empty($allStudentData)) {
                return response()->json(['success' => false, 'message' => 'Failed to process student data.'], 500);
            }

            $this->fixImagePaths($allStudentData);

            $schoolclass   = Schoolclass::where('id', $schoolclassid)->with(['arms', 'classcategories'])->first(['id', 'schoolclass', 'arm']);
            $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';
            $term          = $this->getTermName($termid);
            $className     = $schoolclass
                ? ($schoolclass->schoolclass . ($schoolclass->arms ? $schoolclass->arms->arm : ''))
                : 'Class';

            $filename = 'Class_Results_'
                . preg_replace('/[^A-Za-z0-9_-]/', '_', $className) . '_'
                . preg_replace('/[^A-Za-z0-9_-]/', '_', $schoolsession) . '_'
                . $term . '.pdf';

            $viewName = 'studentreports.class_results_pdf';
            if (!view()->exists($viewName)) {
                return response()->json(['success' => false, 'message' => 'PDF template not found: ' . $viewName], 500);
            }

            $viewData = [
                'allStudentData' => $allStudentData,
                'metadata'       => [
                    'class_name'       => $className,
                    'session'          => $schoolsession,
                    'term'             => $term,
                    'generation_date'  => now()->format('Y-m-d H:i:s'),
                    'student_count'    => count($allStudentData),
                    'selected_columns' => $selectedColumns,
                    'grade_basis'      => $gradeBasis,
                ],
            ];

            $pdf = Pdf::loadView($viewName, $viewData)
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
                    'enable_css_float'        => true,
                ]);

            $pdfContent = $pdf->output();

            if (empty($pdfContent)) {
                return response()->json(['success' => false, 'message' => 'Generated PDF content is empty'], 500);
            }

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (Exception $e) {
            Log::error('ERROR CLASS PDF EXPORT', [
                'error_message' => $e->getMessage(), 'error_line' => $e->getLine(),
            ]);
            return response()->json([
                'success'    => false,
                'message'    => 'Failed to generate PDF: ' . $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        }
    }

    // =========================================================================
    // IMAGE HELPERS
    // =========================================================================

    private function checkServerRequirements()
    {
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);
    }

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

        $possiblePaths = $isStudent ? [
            public_path('storage/student_avatars/' . $path),
            storage_path('app/public/student_avatars/' . $path),
            public_path('storage/' . $path),
            storage_path('app/public/' . $path),
            public_path($path),
        ] : [
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
            return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="#f0f0f0"/>
                <circle cx="50" cy="40" r="15" fill="#ddd"/>
                <rect x="35" y="60" width="30" height="25" fill="#ddd" rx="2"/>
                </svg>'
            );
        }

        try {
            $imageData = file_get_contents($imagePath);
            if (empty($imageData)) throw new \Exception('Empty image file');
            $ext      = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $mimeType = mime_content_type($imagePath) ?: (['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'][$ext] ?? 'image/jpeg');
            return "data:{$mimeType};base64," . base64_encode($imageData);
        } catch (\Exception $e) {
            Log::error('Failed to convert image to base64', ['path' => $imagePath, 'error' => $e->getMessage()]);
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f8f9fa"/></svg>');
        }
    }

    private function fixImagePaths(&$studentData)
    {
        $defaultStudentImage = public_path('storage/student_avatars/unnamed.jpg');
        $defaultSchoolLogo   = public_path('storage/school_logos/default.jpg');

        foreach ([
            $defaultStudentImage => 'Student',
            $defaultSchoolLogo   => 'School',
        ] as $path => $label) {
            if (!file_exists($path)) {
                $dir = dirname($path);
                if (!file_exists($dir)) mkdir($dir, 0755, true);
                $this->createPlaceholderImage($path, $label);
            }
        }

        foreach ($studentData as &$student) {
            $picturePath = $student['students'] && $student['students']->isNotEmpty()
                ? $this->getAbsoluteImagePath($student['students']->first()->picture, true)
                : null;

            $student['student_image_base64'] = ($picturePath && file_exists($picturePath))
                ? $this->imageToBase64($picturePath)
                : $this->imageToBase64($defaultStudentImage);

            if (isset($student['schoolInfo']) && !empty($student['schoolInfo']->school_logo)) {
                $logoPath = $this->getAbsoluteImagePath($student['schoolInfo']->school_logo, false);
                $student['school_logo_base64'] = ($logoPath && file_exists($logoPath) && filesize($logoPath) > 100)
                    ? $this->imageToBase64($logoPath)
                    : $this->imageToBase64($defaultSchoolLogo);
            } else {
                $student['school_logo_base64'] = $this->imageToBase64($defaultSchoolLogo);
            }

            if (isset($student['schoolInfo']) && !empty($student['schoolInfo']->school_stamp)) {
                $stampPath = $this->getAbsoluteImagePath($student['schoolInfo']->school_stamp, false);
                $student['school_stamp_base64'] = ($stampPath && file_exists($stampPath) && filesize($stampPath) > 100)
                    ? $this->imageToBase64($stampPath)
                    : null;
            } else {
                $student['school_stamp_base64'] = null;
            }
        }
    }

    private function createPlaceholderImage($path, $text)
    {
        try {
            $image = imagecreatetruecolor(300, 200);
            $bg    = imagecolorallocate($image, 240, 240, 240);
            $tc    = imagecolorallocate($image, 153, 153, 153);
            imagefill($image, 0, 0, $bg);
            $font = 5;
            imagestring($image, $font, (300 - imagefontwidth($font) * strlen($text)) / 2, 90, $text, $tc);
            imagejpeg($image, $path, 80);
            imagedestroy($image);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create placeholder image', ['path' => $path, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function debugStorageStructure()
    {
        foreach ([
            storage_path('app/public'), public_path('storage'),
            public_path('storage/school_logos'), public_path('storage/student_avatars'),
        ] as $path) {
            Log::info("Storage check: {$path}", ['exists' => file_exists($path), 'is_dir' => is_dir($path)]);
        }
    }

    private function getTermName($termid)
    {
        return [1 => 'First Term', 2 => 'Second Term', 3 => 'Third Term'][$termid] ?? 'Unknown Term';
    }

    // =========================================================================
    // MOCK SCORES
    // =========================================================================

    private function fetchMockScoresForDrawer($studentId, $schoolclassId, $sessionId, $termId): array
    {
        try {
            $rows = BroadsheetsMock::where('broadsheet_records_mock.student_id', $studentId)
                ->where('broadsheetmock.term_id', $termId)
                ->where('broadsheet_records_mock.session_id', $sessionId)
                ->where('broadsheet_records_mock.schoolclass_id', $schoolclassId)
                ->whereExists(function ($query) use ($studentId, $termId, $sessionId, $schoolclassId) {
                    $query->select(DB::raw(1))
                        ->from('subjectRegistrationStatus')
                        ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                        ->join('broadsheet_records_mock as brm_inner', 'brm_inner.subject_id', '=', 'subjectclass.subjectid')
                        ->whereColumn('brm_inner.id', 'broadsheetmock.broadsheet_records_mock_id')
                        ->where('subjectclass.schoolclassid', $schoolclassId)
                        ->where('subjectRegistrationStatus.studentid', $studentId)
                        ->where('subjectRegistrationStatus.termid', $termId)
                        ->where('subjectRegistrationStatus.sessionid', $sessionId);
                })
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
                ])
                ->get();

            return $rows->map(fn($r) => [
                'subject_name'  => $r->subject_name,
                'subject_code'  => $r->subject_code,
                'exam'          => $r->exam  !== null ? (float) $r->exam  : null,
                'total'         => $r->total !== null ? (float) $r->total : null,
                'grade'         => $r->grade,
                'remark'        => $r->remark,
                'position'      => $r->position,
                'class_average' => $r->class_average !== null ? (float) $r->class_average : null,
                'cmin'          => $r->cmin !== null ? (float) $r->cmin : null,
                'cmax'          => $r->cmax !== null ? (float) $r->cmax : null,
            ])->values()->toArray();

        } catch (\Exception $e) {
            Log::error('fetchMockScoresForDrawer error', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): View|JsonResponse
    {
        $pagetitle   = "Student Terminal Report Management";
        $allstudents = new LengthAwarePaginator([], 0, 10);

        if (
            $request->filled('schoolclassid') && $request->filled('sessionid') &&
            $request->input('schoolclassid') !== 'ALL' && $request->input('sessionid') !== 'ALL'
        ) {
            // No "Current" session restriction: the session picked in the
            // dropdown (sessionid) is what limits the results, so previous
            // sessions can be viewed too.
            $query = Studentclass::query()
                ->where('schoolclassid', $request->input('schoolclassid'))
                ->where('sessionid', $request->input('sessionid'))
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.firstname',  'like', "%{$search}%")
                      ->orWhere('studentRegistration.lastname',   'like', "%{$search}%")
                      ->orWhere('studentRegistration.othername',  'like', "%{$search}%");
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

        // All sessions, newest first (previously only status = 'Current').
        $schoolsessions = Schoolsession::orderByDesc('id')->get();
        $schoolclasses  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);

        if ($request->ajax()) {
            return response()->json([
                'tableBody'    => view('studentreports.partials.student_rows', compact('allstudents'))->render(),
                'pagination'   => $allstudents->links('pagination::bootstrap-5')->render(),
                'studentCount' => $allstudents->total(),
            ]);
        }

        return view('studentreports.index', compact('allstudents', 'schoolsessions', 'schoolclasses', 'pagetitle'));
    }

    // =========================================================================
    // DRAWER DATA
    // =========================================================================

    public function drawerData($studentId, $schoolclassId, $sessionId, $termId)
    {
        try {
            if (!is_numeric($studentId) || !is_numeric($schoolclassId) || !is_numeric($sessionId) || !is_numeric($termId)) {
                return response()->json(['success' => false, 'message' => 'Invalid parameters'], 400);
            }

            $resultData = $this->getStudentResultData($studentId, $schoolclassId, $sessionId, $termId);

            if (empty($resultData)) {
                return response()->json(['success' => false, 'message' => 'No data found'], 404);
            }

            $profile = null;
            try {
                $profile = Studentpersonalityprofile::where('studentid', $studentId)
                    ->where('termid', $termId)
                    ->where('sessionid', $sessionId)
                    ->where('schoolclassid', $schoolclassId)
                    ->first();
            } catch (\Exception $e) {
                Log::error('drawerData: error fetching personality profile', ['error' => $e->getMessage()]);
            }

            $student       = $resultData['students']->first();
            $schoolclass   = $resultData['schoolclass'];
            $schoolterm    = $resultData['schoolterm'];
            $schoolsession = $resultData['schoolsession'];

            $fullName = trim(
                strtoupper($student->lastname ?? '') . ' ' .
                ($student->fname ?? '') . ' ' .
                ($student->othername ?? '')
            );

            $assessmentsMeta = ($resultData['assessments'] ?? collect())->map(fn($a) => [
                'id'        => $a->id,
                'name'      => $a->name,
                'max_score' => (float) $a->max_score,
            ])->values()->toArray();

            $scores = ($resultData['scores'] ?? collect())->map(function ($score) {
                $assessmentScores = [];
                if (isset($score->assessment_scores)) {
                    foreach ($score->assessment_scores as $as) {
                        $assessmentScores[] = [
                            'assessment_id' => $as->assessment_id,
                            'score'         => $as->score !== null ? (float) $as->score : null,
                        ];
                    }
                }
                return [
                    'subject_name'      => $score->subject_name,
                    'subject_code'      => $score->subject_code,
                    'assessment_scores' => $assessmentScores,
                    'total'             => $score->total !== null ? (float) $score->total : null,
                    'bf'                => $score->bf    !== null ? (float) $score->bf    : null,
                    'cum'               => $score->cum     !== null ? (float) $score->cum     : null,
                    'cum_ave'           => $score->cum_ave  !== null ? (float) $score->cum_ave  : null,
                    'grade'             => $score->grade,
                    'remark'            => $score->remark,
                    'position'          => $score->position_formatted         ?? ($score->position          ? $this->formatOrdinal($score->position)          : '-'),
                    'position_total'    => $score->position_total_formatted   ?? ($score->position_total    ? $this->formatOrdinal($score->position_total)    : '-'),
                    'arm_position'      => $score->arm_position_formatted     ?? ($score->arm_position      ? $this->formatOrdinal($score->arm_position)      : '-'),
                    'arm_position_cum'  => $score->arm_position_cum_formatted ?? ($score->arm_position_cum  ? $this->formatOrdinal($score->arm_position_cum)  : '-'),
                    'class_average'     => $score->class_average !== null ? (float) $score->class_average : null,
                    'is_compulsory'     => $score->is_compulsory ?? false,
                    'vettedstatus'      => $score->vettedstatus,
                ];
            })->values()->toArray();

            $pictureUrl = null;
            if ($student && $student->picture) {
                $pictureUrl = asset('storage/student_avatars/' . basename($student->picture));
            }

            $attendance = $resultData['attendance_summary'] ?? [];
            if ($schoolterm) $attendance['term_name'] = $schoolterm->term ?? null;

            return response()->json([
                'success'          => true,
                'student_name'     => $fullName,
                'admissionno'      => $student->admissionNo ?? '—',
                'gender'           => $student->gender      ?? '—',
                'schoolclass'      => trim(($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arms->arm ?? '')),
                'term'             => $schoolterm->term       ?? '—',
                'session'          => $schoolsession->session ?? '—',
                'studentid'        => $studentId,
                'schoolclassid'    => $schoolclassId,
                'termid'           => $termId,
                'sessionid'        => $sessionId,
                'picture_url'      => $pictureUrl,
                'assessments'      => $assessmentsMeta,
                'scores'           => $scores,
                'mock_scores'      => $this->fetchMockScoresForDrawer($studentId, $schoolclassId, $sessionId, $termId),
                'profile'          => $profile ? $profile->toArray() : null,
                'attendance'       => $attendance,
                'gpa_data'         => $resultData['gpa_data']       ?? [],
                'totals_summary'   => $resultData['totals_summary']  ?? [],
                'promotion_result' => $resultData['promotion_result'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('drawerData error', ['student_id' => $studentId, 'error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // TEST PDF
    // =========================================================================

    public function testPdfGeneration(Request $request)
    {
        try {
            $testStudentId = Student::first()->id ?? null;
            $testClassId   = Schoolclass::first()->id ?? null;
            $testSessionId = Schoolsession::first()->id ?? null;

            if (!$testStudentId || !$testClassId || !$testSessionId) {
                return response()->json(['success' => false, 'message' => 'Test data not available']);
            }

            $studentData = $this->getStudentResultData($testStudentId, $testClassId, $testSessionId, 3);

            return response()->json([
                'success'             => !empty($studentData),
                'has_students'        => isset($studentData['students']) && !$studentData['students']->isEmpty(),
                'has_scores'          => isset($studentData['scores'])   && !$studentData['scores']->isEmpty(),
                'registered_subjects' => $studentData['scores']->count() ?? 0,
                'totals_summary'      => $studentData['totals_summary']   ?? [],
                'promotion_result'    => $studentData['promotion_result'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
