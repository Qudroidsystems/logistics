<?php
// app/Http/Controllers/MyScoreSheetController.php

namespace App\Http\Controllers;

use App\Exports\MarksSheetExport;
use App\Exports\MockMarksSheetExport;
use App\Exports\MockRecordsheetExport;
use App\Exports\RecordsheetExport;
use App\Http\Controllers\Controller;
use App\Imports\ScoresheetImport;
use App\Models\Assessment;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetRecord;
use App\Models\BroadsheetRecordMock;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\BroadsheetSubAssessmentScore;
use App\Models\PromotionStatus;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\SubAssessment;
use App\Models\Subjectclass;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class MyScoreSheetController extends Controller
{
    // =========================================================================
    // LOCK PROTECTION HELPER
    // =========================================================================

    /**
     * Check if teacher can edit a scoresheet
     */
    protected function checkTeacherCanEdit($broadsheetId)
    {
        $broadsheet = Broadsheets::find($broadsheetId);
        if (!$broadsheet) {
            return ['allowed' => false, 'message' => 'Record not found'];
        }

        if (!$broadsheet->isEditableByTeacher()) {
            $reason = $broadsheet->lock_reason ?? 'This scoresheet has been locked by an administrator';
            return ['allowed' => false, 'message' => $reason];
        }

        // Also check if subjectclass has teacher editing disabled
        $subjectClass = $broadsheet->subjectclass;
        if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
            return ['allowed' => false, 'message' => 'Teacher editing has been disabled for this subject by an administrator.'];
        }

        return ['allowed' => true];
    }

    // =========================================================================
    // TERMINAL SCORESHEET — LIST / DETAIL
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle   = 'My Scoresheets';
        $broadsheets = collect();

        if (!$request->ajax()) {
            $termId    = $request->query('termid', 'ALL');
            $sessionId = $request->query('sessionid', 'ALL');
            if ($termId !== 'ALL' && $sessionId !== 'ALL') {
                $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);
            }
        }

        if ($request->ajax()) {
            $termId    = $request->input('termid', 'ALL');
            $sessionId = $request->input('sessionid', 'ALL');
            if ($termId === 'ALL' || $sessionId === 'ALL') {
                return response()->json(['success' => false, 'message' => 'Please select both term and session.'], 422);
            }
            $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);
            return response()->json(['success' => true, 'data' => ['broadsheets' => $broadsheets]]);
        }

        return view('subjectscoresheet.index', compact('pagetitle', 'broadsheets'));
    }

    public function subjectscoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        session([
            'schoolclass_id'  => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id'        => $staffid,
            'term_id'         => $termid,
            'session_id'      => $sessionid,
        ]);

        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
        $assessments = collect();

        if ($broadsheets->isNotEmpty() && $schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('id')
                ->get();

            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->computeDynamicTotals($broadsheets, $assessments, $schoolclass, $termid, $sessionid);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            $this->computeOverallGPAAndCGPA($broadsheets, $schoolclass, $termid, $sessionid);

            $pagetitle = sprintf(
                'Scoresheet for %s (%s) - %s %s - %s %s',
                $broadsheets->first()->subject,
                $broadsheets->first()->subject_code,
                $broadsheets->first()->schoolclass,
                $broadsheets->first()->arm,
                $broadsheets->first()->term,
                $broadsheets->first()->session
            );
        } else {
            $pagetitle = 'Subject Scoresheet';
        }

        $is_senior = $schoolclass && $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()->is_senior ?? false
            : false;

        $globalLock = \App\Models\ScoresheetLock::where([
            'subjectclass_id' => $subjectclassid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'is_active' => true,
        ])->first();

        $subjectClass = Subjectclass::find($subjectclassid);
        $teacherEditingEnabled = $subjectClass ? $subjectClass->teacher_editing_enabled : true;

        return view('subjectscoresheet.index', compact(
            'broadsheets', 'pagetitle', 'is_senior', 'assessments',
            'globalLock', 'teacherEditingEnabled', 'schoolclass'
        ));
    }

    // =========================================================================
    // SINGLE UPDATE SCORE (terminal) — WITH LOCK CHECK
    // =========================================================================

    public function singleUpdateScore(Request $request)
    {
        try {
            // Check lock status first
            $broadsheetId = $request->input('broadsheet_id');
            $lockCheck = $this->checkTeacherCanEdit($broadsheetId);
            if (!$lockCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $lockCheck['message'],
                    'locked' => true
                ], 423);
            }

            $validated = $request->validate([
                'broadsheet_id' => 'required|exists:broadsheets,id',
                'assessment_id' => 'required|exists:assessments,id',
                'score' => 'required|numeric|min:0',
                'is_sub' => 'boolean',
                'sub_assessment_id' => 'nullable|exists:sub_assessments,id',
                'total' => 'nullable|numeric',
                'raw_total' => 'nullable|numeric',
            ]);

            $broadsheetId = $validated['broadsheet_id'];
            $assessmentId = $validated['assessment_id'];
            $score = $validated['score'];
            $isSub = $validated['is_sub'] ?? false;
            $subAssessmentId = $validated['sub_assessment_id'] ?? null;

            if ($isSub && !$subAssessmentId) {
                return response()->json(['success' => false, 'message' => 'Sub-assessment ID required.'], 422);
            }

            $broadsheet = Broadsheets::findOrFail($broadsheetId);
            $model = $isSub ? SubAssessment::findOrFail($subAssessmentId) : Assessment::findOrFail($assessmentId);

            if ($score > $model->max_score) {
                return response()->json(['success' => false, 'message' => "Score cannot exceed maximum of {$model->max_score}."], 422);
            }

            $fkValue = $broadsheet->broadSheet_record_id ?? $broadsheet->broadsheet_record_id;
            $broadsheetRecord = BroadsheetRecord::find($fkValue);

            $schoolclassId = $broadsheetRecord?->schoolclass_id
                ?? (int) ($request->input('schoolclass_id') ?: session('schoolclass_id'))
                ?: 0;

            $sessionId = $broadsheetRecord?->session_id
                ?? $request->input('session_id')
                ?? session('session_id');

            if (!$sessionId) {
                return response()->json(['success' => false, 'message' => 'Session context missing — please reload the scoresheet.'], 200);
            }

            $termId = $broadsheet->term_id ?? session('term_id');
            $schoolclass = Schoolclass::with('classcategories')->find($schoolclassId);
            $isSenior = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? $schoolclass->classcategories->first()->is_senior ?? false : false;

            DB::transaction(function () use (
                $broadsheetId,
                $assessmentId,
                $score,
                $broadsheet,
                $isSub,
                $subAssessmentId,
                $broadsheetRecord,
                $schoolclass,
                $sessionId
            ) {
                if ($isSub) {
                    BroadsheetSubAssessmentScore::updateOrCreate(
                        [
                            'broadsheet_id' => $broadsheetId,
                            'sub_assessment_id' => $subAssessmentId,
                            'assessment_id' => $assessmentId,
                        ],
                        ['score' => $score]
                    );
                    $assessment = Assessment::with('subAssessments')->find($assessmentId);
                    if ($assessment) {
                        $subMaxSum = $assessment->subAssessments->sum('max_score');
                        $subTotal = BroadsheetSubAssessmentScore::where('broadsheet_id', $broadsheetId)
                            ->where('assessment_id', $assessmentId)
                            ->sum('score');
                        $normalized = $subMaxSum > 0
                            ? ($subTotal / $subMaxSum) * $assessment->max_score
                            : 0;
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
                    $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                        ->with('subAssessments')
                        ->get();
                }
                $broadsheet->load(['assessmentScores', 'subAssessmentScores']);
                $this->computeDynamicTotals(
                    collect([$broadsheet]),
                    $assessments,
                    $schoolclass,
                    $broadsheet->term_id,
                    $sessionId
                );
            });

            $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $sessionId);
            $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $sessionId);
            $this->updateClassPositions($schoolclassId, $termId, $sessionId);

            $studentId = $broadsheetRecord?->student_id
                ?? DB::table('broadsheet_records')->where('id', $fkValue ?? 0)->value('student_id')
                ?? 0;

            $gpaCgpaData = $this->computeOverallForStudent(
                $studentId,
                $schoolclass,
                $termId,
                $sessionId,
                $isSenior
            );

            $broadsheet->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Score updated successfully!',
                'data' => [
                    'total' => $broadsheet->total,
                    // Both figures returned under their real names so the UI can show the
                    // raw running sum ("cum") and the per-term average ("cum_ave") separately.
                    'cum' => $broadsheet->cum,
                    'cum_ave' => $broadsheet->cum_ave,
                    'bf' => $broadsheet->bf,
                    'grade' => $broadsheet->grade,
                    'remark' => $broadsheet->remark,
                    'subject_position_class' => $broadsheet->subject_position_class,
                    'subject_position_class_total' => $broadsheet->subject_position_class_total,
                    'arm_position' => $broadsheet->arm_position,
                    'arm_position_cum' => $broadsheet->arm_position_cum,
                    'gpa' => round($gpaCgpaData['gpa'], 2),
                    'gpa_grade' => $gpaCgpaData['gpa_grade'] ?? 'F',
                    'cgpa' => round($gpaCgpaData['cgpa'], 2),
                    'num_subjects' => $gpaCgpaData['num_subjects'] ?? 0,
                    'total_grade_points' => $gpaCgpaData['total_grade_points'] ?? 0.0,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('singleUpdateScore error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 200);
        }
    }

    // =========================================================================
    // BULK UPDATE SCORES — WITH LOCK CHECK
    // =========================================================================

    public function bulkUpdateScores(Request $request)
    {
        // Check each broadsheet for lock status
        $scores = $request->input('scores', []);
        $lockedIds = [];

        foreach ($scores as $scoreData) {
            $check = $this->checkTeacherCanEdit($scoreData['id']);
            if (!$check['allowed']) {
                $lockedIds[] = $scoreData['id'];
            }
        }

        if (!empty($lockedIds)) {
            return response()->json([
                'success' => false,
                'message' => count($lockedIds) . ' score(s) are locked and cannot be edited.',
                'locked_ids' => $lockedIds
            ], 423);
        }

        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.id' => 'required|exists:broadsheets,id',
            'scores.*.assessments' => 'sometimes|array',
            'scores.*.total' => 'nullable|numeric',
            'scores.*.raw_total' => 'nullable|numeric',
            'term_id' => 'required|exists:schoolterm,id',
            'session_id' => 'required|exists:schoolsession,id',
            'subjectclass_id' => 'required|exists:subjectclass,id',
            'staff_id' => 'required|exists:users,id',
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'assessment_id' => 'nullable|exists:assessments,id',
            'is_sub' => 'nullable|boolean',
        ]);

        $scores = $validated['scores'];
        $term_id = $validated['term_id'];
        $session_id = $validated['session_id'];
        $subjectclass_id = $validated['subjectclass_id'];
        $staff_id = $validated['staff_id'];
        $schoolclass_id = $validated['schoolclass_id'];
        $assessment_id = $validated['assessment_id'] ?? null;
        $is_sub = (bool) ($validated['is_sub'] ?? false);

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
        if (!$schoolclass) {
            return response()->json(['success' => false, 'message' => 'School class not found'], 404);
        }

        $assessments = collect();
        if ($schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->get();
        }

        $updatedCount = 0;
        $errors = [];

        DB::transaction(function () use (
            $scores,
            $term_id,
            $session_id,
            $subjectclass_id,
            $staff_id,
            $schoolclass_id,
            $schoolclass,
            $assessments,
            $is_sub,
            $assessment_id,
            &$updatedCount,
            &$errors
        ) {
            foreach ($scores as $scoreData) {
                $broadsheetId = $scoreData['id'];
                $broadsheet = Broadsheets::find($broadsheetId);
                if (!$broadsheet) {
                    $errors[] = "Broadsheet ID {$broadsheetId} not found.";
                    continue;
                }

                $assessmentsData = $scoreData['assessments'] ?? [];
                if (empty($assessmentsData)) {
                    continue;
                }

                $localErrors = [];

                if ($is_sub && $assessment_id) {
                    $parentAssessment = $assessments->where('id', $assessment_id)->first()
                        ?? Assessment::with('subAssessments')->find($assessment_id);

                    foreach ($assessmentsData as $subId => $inputScore) {
                        $subId = (int) $subId;
                        $inputScore = max(0, (float) $inputScore);

                        $subModel = SubAssessment::find($subId);
                        if (!$subModel || $subModel->assessment_id != $assessment_id) {
                            $localErrors[] = "SubAssessment {$subId} invalid or does not belong to assessment {$assessment_id}.";
                            continue;
                        }

                        BroadsheetSubAssessmentScore::updateOrCreate(
                            [
                                'broadsheet_id' => $broadsheetId,
                                'sub_assessment_id' => $subId,
                                'assessment_id' => $assessment_id,
                            ],
                            ['score' => min($inputScore, $subModel->max_score)]
                        );
                    }

                    if ($parentAssessment) {
                        $subMaxSum = $parentAssessment->subAssessments->sum('max_score');
                        $subTotal = BroadsheetSubAssessmentScore::where('broadsheet_id', $broadsheetId)
                            ->where('assessment_id', $assessment_id)
                            ->sum('score');
                        $normalized = $subMaxSum > 0
                            ? ($subTotal / $subMaxSum) * $parentAssessment->max_score
                            : 0;

                        BroadsheetAssessmentScore::updateOrCreate(
                            ['broadsheet_id' => $broadsheetId, 'assessment_id' => $assessment_id],
                            ['score' => max(0, min($normalized, $parentAssessment->max_score))]
                        );
                    }
                } else {
                    foreach ($assessmentsData as $componentId => $inputScore) {
                        $componentId = (int) $componentId;
                        $inputScore = max(0, (float) $inputScore);

                        $model = $assessments->where('id', $componentId)->first();
                        if (!$model) {
                            $localErrors[] = "Assessment {$componentId} invalid.";
                            continue;
                        }

                        BroadsheetAssessmentScore::updateOrCreate(
                            ['broadsheet_id' => $broadsheetId, 'assessment_id' => $componentId],
                            ['score' => min($inputScore, $model->max_score)]
                        );
                    }
                }

                if (!empty($localErrors)) {
                    $errors[] = "Broadsheet {$broadsheetId}: " . implode(', ', $localErrors);
                    continue;
                }

                $broadsheet->load(['assessmentScores', 'subAssessmentScores']);
                $this->computeDynamicTotals(
                    collect([$broadsheet]),
                    $assessments,
                    $schoolclass,
                    $term_id,
                    $session_id
                );
                $updatedCount++;
            }

            $this->updateClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        });

        $this->updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateClassPositions($schoolclass_id, $term_id, $session_id);

        $updatedBroadsheets = $this->getBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);
        $this->computeOverallGPAAndCGPA($updatedBroadsheets, $schoolclass, $term_id, $session_id);

        $response = [
            'success' => true,
            'message' => "{$updatedCount} score(s) updated!",
            'data' => [
                'broadsheets' => $updatedBroadsheets,
                'assessments' => $assessments,
            ],
        ];
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        return response()->json($response, 200);
    }

    // =========================================================================
    // DESTROY — with lock check
    // =========================================================================

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        // Check lock status before deletion
        $lockCheck = $this->checkTeacherCanEdit($id);
        if (!$lockCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $lockCheck['message'],
                'locked' => true
            ], 423);
        }

        $broadsheet = Broadsheets::findOrFail($id);
        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid = $broadsheet->staff_id;
        $termid = $broadsheet->term_id;
        $broadsheetRecord = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadSheet_record_id)
            ->first();

        BroadsheetAssessmentScore::where('broadsheet_id', $id)->delete();
        BroadsheetSubAssessmentScore::where('broadsheet_id', $id)->delete();
        $broadsheet->delete();

        if ($broadsheetRecord) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateClassPositions($broadsheetRecord->schoolclass_id, $termid, $broadsheetRecord->session_id);
        }

        return response()->json(['success' => true, 'message' => 'Score deleted successfully!']);
    }

    // =========================================================================
    // MOCK SCORESHEET METHODS (with lock checks)
    // =========================================================================

    public function mockSingleUpdateScore(Request $request)
    {
        try {
            // For mock scores, check if the teacher can edit the corresponding terminal subject
            $broadsheetId = $request->input('broadsheet_id');
            $broadsheet = \App\Models\BroadsheetsMock::find($broadsheetId);
            if ($broadsheet) {
                // Check if the subject has teacher editing enabled
                $subjectClass = Subjectclass::find($broadsheet->subjectclass_id);
                if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Teacher editing has been disabled for this subject by an administrator.',
                        'locked' => true
                    ], 423);
                }
            }

            $validated = $request->validate([
                'broadsheet_id' => 'required|exists:broadsheetmock,id',
                'exam' => 'required|numeric|min:0|max:100',
            ]);

            $broadsheetId = $validated['broadsheet_id'];
            $examScore = (float) $validated['exam'];

            $broadsheet = \App\Models\BroadsheetsMock::findOrFail($broadsheetId);
            $mockRecord = BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);
            $schoolclassId = $mockRecord?->schoolclass_id ?? 0;
            $sessionId = $mockRecord?->session_id ?? session('session_id');
            $schoolclass = Schoolclass::with('classcategories')->find($schoolclassId);

            $examScore = max(0, min($examScore, 100));
            $total = round($examScore, 2);

            $grade = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? $schoolclass->classcategories->first()->calculateGrade($total)
                : $this->getDefaultGrade($total);
            $remark = $this->getRemark($grade);

            $broadsheet->exam = $examScore;
            $broadsheet->total = $total;
            $broadsheet->grade = $grade;
            $broadsheet->remark = $remark;
            $broadsheet->save();

            $this->updateMockClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $sessionId);
            $this->updateMockSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $sessionId);

            $broadsheet->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Score updated successfully!',
                'data' => [
                    'total' => $broadsheet->total,
                    'grade' => $broadsheet->grade,
                    'remark' => $broadsheet->remark,
                    'position' => $broadsheet->subject_position_class,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('mockSingleUpdateScore error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    public function mockBulkUpdateScores(Request $request)
    {
        $scores = $request->input('scores', []);

        // Check each mock broadsheet's subject lock status
        foreach ($scores as $scoreData) {
            $broadsheet = \App\Models\BroadsheetsMock::find($scoreData['id']);
            if ($broadsheet) {
                $subjectClass = Subjectclass::find($broadsheet->subjectclass_id);
                if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Teacher editing has been disabled for this subject by an administrator.',
                        'locked' => true
                    ], 423);
                }
            }
        }

        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.id' => 'required|exists:broadsheetmock,id',
            'scores.*.exam' => 'nullable|numeric|min:0|max:100',
            'term_id' => 'required|exists:schoolterm,id',
            'session_id' => 'required|exists:schoolsession,id',
            'subjectclass_id' => 'required|exists:subjectclass,id',
            'staff_id' => 'required|exists:users,id',
            'schoolclass_id' => 'required|exists:schoolclass,id',
        ]);

        $scores = $validated['scores'];
        $term_id = $validated['term_id'];
        $session_id = $validated['session_id'];
        $subjectclass_id = $validated['subjectclass_id'];
        $staff_id = $validated['staff_id'];
        $schoolclass_id = $validated['schoolclass_id'];

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
        if (!$schoolclass) {
            return response()->json(['success' => false, 'message' => 'School class not found.'], 404);
        }

        $updatedCount = 0;
        $errors = [];

        DB::transaction(function () use ($scores, $session_id, $schoolclass, &$updatedCount, &$errors) {
            foreach ($scores as $scoreData) {
                $broadsheetId = $scoreData['id'];
                $examScore = isset($scoreData['exam']) ? (float) $scoreData['exam'] : 0;

                $broadsheet = \App\Models\BroadsheetsMock::find($broadsheetId);
                if (!$broadsheet) {
                    $errors[] = "Mock broadsheet ID {$broadsheetId} not found.";
                    continue;
                }

                $examScore = max(0, min($examScore, 100));
                $total = round($examScore, 2);

                $grade = $schoolclass->classcategories->isNotEmpty()
                    ? $schoolclass->classcategories->first()->calculateGrade($total)
                    : $this->getDefaultGrade($total);
                $remark = $this->getRemark($grade);

                $broadsheet->exam = $examScore;
                $broadsheet->total = $total;
                $broadsheet->grade = $grade;
                $broadsheet->remark = $remark;
                $broadsheet->save();

                $updatedCount++;
            }
        });

        $this->updateMockClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);

        $updatedBroadsheets = $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

        $response = [
            'success' => true,
            'message' => "{$updatedCount} score(s) updated successfully!",
            'data' => ['broadsheets' => $updatedBroadsheets],
        ];
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        return response()->json($response, 200);
    }

    public function mockDestroy(Request $request)
    {
        $id = $request->input('id');
        $broadsheet = \App\Models\BroadsheetsMock::findOrFail($id);

        // Check if subject has teacher editing enabled
        $subjectClass = Subjectclass::find($broadsheet->subjectclass_id);
        if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher editing has been disabled for this subject by an administrator.',
                'locked' => true
            ], 423);
        }

        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid = $broadsheet->staff_id;
        $termid = $broadsheet->term_id;
        $mockRecord = BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);

        $broadsheet->delete();

        if ($mockRecord) {
            $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $mockRecord->session_id);
            $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $mockRecord->session_id);
        }

        return response()->json(['success' => true, 'message' => 'Score deleted successfully!']);
    }

    // =========================================================================
    // RESULTS AND OTHER METHODS (same as before)
    // =========================================================================

    public function results()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id = session('schoolclass_id');
            $term_id = session('term_id');
            $session_id = session('session_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json(['success' => false, 'message' => 'Missing session data', 'scores' => []], 400);
            }

            $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->orderBy('id')->get();
            }

            $broadsheets = Broadsheets::where(['subjectclass_id' => $subjectclass_id, 'term_id' => $term_id])
                ->with(['assessmentScores', 'subAssessmentScores'])
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->where('broadsheet_records.session_id', $session_id)
                ->orderBy('studentRegistration.lastname')
                ->orderBy('studentRegistration.firstname')
                ->get([
                    'broadsheets.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'broadsheets.total',
                    'broadsheets.bf',
                    // Both cumulative figures returned under their real names — see
                    // getBroadsheets() note below.
                    'broadsheets.cum',
                    'broadsheets.cum_ave',
                    'broadsheets.grade',
                    'broadsheets.avg',
                    'broadsheets.subject_position_class as position',
                    'broadsheets.subject_position_class_total as position_total',
                    'broadsheets.arm_position',
                    'broadsheets.arm_position_cum',
                    'broadsheets.term_id',
                ]);

            $this->computeOverallGPAAndCGPA($broadsheets, $schoolclass, $term_id, $session_id);

            $scoresData = $broadsheets->map(function ($b) use ($assessments) {
                $assessmentData = [];
                foreach ($assessments as $a) {
                    $s = $b->assessmentScores->where('assessment_id', $a->id)->first();
                    $assessmentData[$a->id] = [
                        'name' => $a->name,
                        'max_score' => $a->max_score,
                        'score' => $s ? $s->score : 0,
                    ];
                }
                return [
                    'id' => $b->id,
                    'admissionno' => $b->admissionno,
                    'fname' => $b->fname,
                    'lname' => $b->lname,
                    'assessments' => $assessmentData,
                    'total' => $b->total,
                    'bf' => $b->bf,
                    'cum' => $b->cum,
                    'cum_ave' => $b->cum_ave,
                    'avg' => $b->avg ?? 0,
                    'gpa' => $b->gpa,
                    'gpa_grade' => $b->gpa_grade ?? 'F',
                    'cgpa' => $b->cgpa,
                    'grade' => $b->grade,
                    'position' => $b->position,
                    'position_total' => $b->position_total,
                    'arm_position' => $b->arm_position,
                    'arm_position_cum' => $b->arm_position_cum,
                    'num_subjects' => $b->num_subjects ?? 0,
                    'total_grade_points' => $b->total_grade_points ?? 0.0,
                ];
            });

            return response()->json(['success' => true, 'assessments' => $assessments, 'scores' => $scoresData]);
        } catch (\Exception $e) {
            Log::error('results error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal server error.'], 500);
        }
    }

    public function mockResults()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id = session('schoolclass_id');
            $term_id = session('term_id');
            $session_id = session('session_id');
            $staff_id = session('staff_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json(['success' => false, 'message' => 'Missing session data.', 'scores' => []], 400);
            }

            $broadsheets = $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

            $scoresData = $broadsheets->map(fn($b) => [
                'id' => $b->id,
                'admissionno' => $b->admissionno,
                'fname' => $b->fname,
                'lname' => $b->lname,
                'exam' => $b->exam,
                'total' => $b->total,
                'grade' => $b->grade,
                'remark' => $b->remark,
                'position' => $b->position,
                'cmin' => $b->cmin,
                'cmax' => $b->cmax,
                'avg' => $b->avg,
            ]);

            return response()->json(['success' => true, 'scores' => $scoresData]);
        } catch (\Exception $e) {
            Log::error('mockResults error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GRADE PREVIEW ENDPOINTS
    // =========================================================================

    public function calculateGradeForScore(Request $request)
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'total' => 'required|numeric|min:0|max:100',
            'cum' => 'required|numeric|min:0|max:100',
        ]);

        $schoolclass = Schoolclass::with('classcategories')->findOrFail($request->schoolclass_id);
        $category = $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()
            : null;

        $totalGrade = $category
            ? $category->calculateGrade($request->total)
            : $this->getDefaultGrade($request->total);

        $cumGrade = $category
            ? $category->calculateGrade($request->cum)
            : $this->getDefaultGrade($request->cum);

        return response()->json([
            'success' => true,
            'total_grade' => $totalGrade,
            'cum_grade' => $cumGrade,
            'remark' => $this->getRemark($totalGrade),
        ]);
    }

    public function calculateGradePreview(Request $request)
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'cum' => 'required|numeric|min:0|max:100',
        ]);
        $schoolclass = Schoolclass::with('classcategories')->findOrFail($request->schoolclass_id);
        $grade = $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()->calculateGrade($request->cum)
            : $this->getDefaultGrade($request->cum);
        return response()->json(['grade' => $grade]);
    }

    // =========================================================================
    // PDF DOWNLOADS
    // =========================================================================

    public function downloadMarksSheet(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('subjectclass_id'));
            $staffid = $request->input('staff_id', session('staff_id'));
            $termid = $request->input('term_id', session('term_id'));
            $sessionid = $request->input('session_id', session('session_id'));
            $schoolclassid = $request->input('schoolclass_id', session('schoolclass_id'));

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data. Please open the scoresheet first.'], 400);
            }

            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No students found for this subject.'], 404);
            }

            $teacher = \App\Models\User::find($staffid);
            $teacherName = $teacher ? ($teacher->name ?? trim($teacher->firstname . ' ' . $teacher->lastname)) : '';

            $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)->with('subAssessments')->orderBy('id')->get();
            }

            $school = SchoolInformation::first();
            $pdf = Pdf::loadView('subjectscoresheet.marksheet', [
                'broadsheets' => $broadsheets,
                'assessments' => $assessments,
                'classInfo' => $broadsheets->first(),
                'school' => $school,
                'teacherName' => $teacherName,
            ]);
            $pdf->setPaper('a4', 'landscape');

            return $pdf->download('marks-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Marks sheet download error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function downloadScoresPdf(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('subjectclass_id'));
            $staffid = $request->input('staff_id', session('staff_id'));
            $termid = $request->input('term_id', session('term_id'));
            $sessionid = $request->input('session_id', session('session_id'));
            $schoolclassid = $request->input('schoolclass_id', session('schoolclass_id'));

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data. Please open the scoresheet first.'], 400);
            }

            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No students found for this subject.'], 404);
            }

            $broadsheets->load(['assessmentScores']);

            $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                    ->with('subAssessments')
                    ->orderBy('id')
                    ->get();
            }

            $teacher = \App\Models\User::find($staffid);
            $teacherName = $teacher
                ? ($teacher->name ?? trim($teacher->firstname . ' ' . $teacher->lastname))
                : '';

            $school = SchoolInformation::first();

            $pdf = Pdf::loadView('subjectscoresheet.scores-pdf', [
                'broadsheets' => $broadsheets,
                'assessments' => $assessments,
                'classInfo' => $broadsheets->first(),
                'school' => $school,
                'teacherName' => $teacherName,
            ]);
            $pdf->setPaper('a4', 'landscape');

            $subject = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->subject ?? 'subject');
            $class = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->schoolclass ?? 'class');
            $termName = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->term ?? 'term');
            $filename = "scores-{$subject}-{$class}-{$termName}-" . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Scores PDF download error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    public function mockDownloadMarkSheet(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('subjectclass_id'));
            $staffid = $request->input('staff_id', session('staff_id'));
            $termid = $request->input('term_id', session('term_id'));
            $sessionid = $request->input('session_id', session('session_id'));
            $schoolclassid = $request->input('schoolclass_id', session('schoolclass_id'));

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            }

            $broadsheets = $this->getMockBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No mock scores found.'], 404);
            }

            $teacher = \App\Models\User::find($staffid);
            $teacherName = $teacher ? ($teacher->name ?? trim($teacher->firstname . ' ' . $teacher->lastname)) : '';

            $school = SchoolInformation::first();
            $pdf = Pdf::loadView('subjectscoresheet.mock-marksheet', [
                'broadsheets' => $broadsheets,
                'classInfo' => $broadsheets->first(),
                'school' => $school,
                'teacherName' => $teacherName,
            ]);
            $pdf->setPaper('a4', 'landscape');

            return $pdf->download('mock-marks-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Mock marks sheet error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // EXPORT (Excel)
    // =========================================================================

    public function export(Request $request)
    {
        $schoolclassId = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId = session('term_id');
        $sessionId = session('session_id');
        $staffId = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return back()->with('error', 'Missing session data. Please open the scoresheet first.');
        }

        $subjectClass = Subjectclass::with('subject')->find($subjectclassId);
        $schoolclass = Schoolclass::find($schoolclassId);
        $term = Schoolterm::find($termId);
        $session = Schoolsession::find($sessionId);

        $subjectName = preg_replace('/[^a-zA-Z0-9-]/', '_', $subjectClass?->subject?->subject ?? 'subject');
        $className = preg_replace('/[^a-zA-Z0-9-]/', '_', $schoolclass?->schoolclass ?? 'class');
        $arm = preg_replace('/[^a-zA-Z0-9-]/', '_', $schoolclass?->arm_name ?? '');
        $termName = preg_replace('/[^a-zA-Z0-9-]/', '_', $term?->term ?? 'term');
        $sessionName = preg_replace('/[^a-zA-Z0-9-]/', '_', $session?->session ?? 'session');

        $filename = "{$subjectName}_{$className}" . ($arm && $arm !== '_' ? "_{$arm}" : '') . "_{$termName}_{$sessionName}_scoresheet.xlsx";

        $export = new RecordsheetExport($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId);
        session(['last_export_password' => $export->getPassword()]);

        return Excel::download($export, $filename);
    }

    // =========================================================================
    // IMPORT
    // =========================================================================

    public function import(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

            $importData = [
                'subjectclass_id' => $request->input('subjectclass_id', session('subjectclass_id')),
                'staff_id' => $request->input('staff_id', session('staff_id')),
                'term_id' => $request->input('term_id', session('term_id')),
                'session_id' => $request->input('session_id', session('session_id')),
                'schoolclass_id' => $request->input('schoolclass_id', session('schoolclass_id')),
            ];

            if (empty($importData['subjectclass_id']) || empty($importData['staff_id'])) {
                return response()->json(['success' => false, 'message' => 'Missing session data. Please refresh and try again.'], 422);
            }

            $importer = new ScoresheetImport($importData);
            try {
                $importer->validateExcelFile($request->file('file'));
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            Excel::import($importer, $request->file('file'));

            $failures = $importer->getFailures();
            $successCount = $importer->getSuccessCount();

            session()->forget(['import_progress', 'import_status', 'import_message']);

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
                'data' => ['broadsheets' => $formattedBroadsheets, 'assessments' => $assessments],
            ];

            if (!empty($failures)) {
                $responseData['warning'] = true;
                $responseData['message'] = "Imported {$successCount} record(s) with " . count($failures) . " warning(s).";
                $responseData['failures'] = $failures;
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Import failed', ['error' => $e->getMessage()]);
            session()->forget(['import_progress', 'import_status', 'import_message']);
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // QUERY HELPERS (same as before)
    // =========================================================================

    protected function getBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = Broadsheets::query()
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->with(['assessmentScores', 'subAssessmentScores', 'subjectclass'])
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                    ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) {
                    $join->where('subjectclass.id', $subjectClassId);
                }
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

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        return $query->get([
            'broadsheets.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolclass.classcategoryid',
            'schoolarm.arm',
            'schoolarm.id as arm_id',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheets.staff_id',
            'broadsheets.term_id',
            'broadsheet_records.session_id as sessionid',
            'studentpicture.picture',
            'broadsheets.total',
            'broadsheets.bf',
            // Both cumulative figures returned under their real names:
            // "cum"     = raw running sum (BF + this term's total)
            // "cum_ave" = that sum divided by the term number
            'broadsheets.cum',
            'broadsheets.cum_ave',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.subject_position_class_total as position_total',
            'broadsheets.arm_position',
            'broadsheets.arm_position_cum',
            'broadsheets.remark',
            'broadsheets.vettedstatus',
            'broadsheets.avg',
            'broadsheets.cmin',
            'broadsheets.cmax',
            'broadsheets.is_locked',
            'broadsheets.entered_at',
            'broadsheets.last_modified_at',
            'broadsheets.entry_source',
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
                if ($subjectClassId) {
                    $join->where('subjectclass.id', $subjectClassId);
                }
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

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        return $query->get([
            'broadsheetmock.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records_mock.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records_mock.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheetmock.staff_id',
            'broadsheetmock.term_id',
            'broadsheet_records_mock.session_id as sessionid',
            'studentpicture.picture',
            'broadsheetmock.exam',
            'broadsheetmock.total',
            'broadsheetmock.grade',
            'broadsheetmock.subject_position_class as position',
            'broadsheetmock.remark',
            'broadsheetmock.cmin',
            'broadsheetmock.cmax',
            'broadsheetmock.avg',
            'broadsheetmock.vettedstatus',
        ]);
    }

    protected function formatBroadsheetsForResponse($broadsheets, $assessments)
    {
        $formatted = [];
        foreach ($broadsheets as $b) {
            $assessmentScores = [];
            foreach ($assessments as $a) {
                $s = $b->assessmentScores->where('assessment_id', $a->id)->first();
                $assessmentScores[] = [
                    'assessment_id' => $a->id,
                    'assessment_name' => $a->name,
                    'max_score' => $a->max_score,
                    'score' => $s ? floatval($s->score) : 0,
                ];
            }
            $formatted[] = [
                'id' => $b->id,
                'admissionno' => $b->admissionno,
                'fname' => $b->fname,
                'lname' => $b->lname,
                'mname' => $b->mname,
                'total' => floatval($b->total),
                'bf' => floatval($b->bf),
                'cum' => floatval($b->cum),
                'cum_ave' => floatval($b->cum_ave ?? 0),
                'grade' => $b->grade,
                'position' => $b->position,
                'position_total' => $b->position_total,
                'arm_position' => $b->arm_position,
                'arm_position_cum' => $b->arm_position_cum,
                'remark' => $b->remark,
                'avg' => floatval($b->avg ?? 0),
                'assessment_scores' => $assessmentScores,
            ];
        }
        return $formatted;
    }

    // =========================================================================
    // POSITION / METRICS HELPERS (same as before)
    // =========================================================================

    protected function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')->where('id', $subjectclassid)->first(['subjectteacherid']);
        if (!$subjectClass) {
            return;
        }
        $subjectTeacher = DB::table('subjectteacher')->where('id', $subjectClass->subjectteacherid)->first(['subjectid']);
        if (!$subjectTeacher) {
            return;
        }
        $subjectId = $subjectTeacher->subjectid;

        // NOTE: min/max/avg here are computed from cum_ave (the divided figure), not the
        // raw running-sum "cum" column — otherwise these numbers would balloon term-on-term
        // even though "Ave remains Average of each subject per class" should stay stable.
        $metrics = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassid)
            ->where('broadsheets.staff_id', $staffid)
            ->where('broadsheets.term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheets.cum_ave) as class_min'),
                DB::raw('MAX(broadsheets.cum_ave) as class_max'),
                DB::raw('SUM(broadsheets.cum_ave) as cum_sum'),
                DB::raw('COUNT(broadsheets.id) as student_count'),
            ])->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->cum_sum / $metrics->student_count, 1) : 0;

        Broadsheets::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }


protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
{
    // NOTE: ranking still reads the RAW running-sum "broadsheets.cum" column below.
    // This is intentional and needs no change: every row being ranked here is for the
    // SAME term_id, so every student's cum_ave = cum / term_id is divided by the exact
    // same constant. Dividing by a shared constant never changes relative ordering, so
    // ranking by raw cum produces identical positions to ranking by cum_ave.
    Log::info('[updateSubjectPositions] START', [
        'subjectclass_id' => $subjectclass_id,
        'term_id'         => $term_id,
        'session_id'      => $session_id,
    ]);

    $subjectClass = DB::table('subjectclass')
        ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
        ->where('subjectclass.id', $subjectclass_id)
        ->first(['subjectclass.schoolclassid', 'subjectteacher.subjectid']);

    if (!$subjectClass) {
        Log::warning('[updateSubjectPositions] subjectClass not found', compact('subjectclass_id'));
        return;
    }

    $subjectId     = $subjectClass->subjectid;
    $schoolclassId = $subjectClass->schoolclassid;

    $baseClass = DB::table('schoolclass')
        ->where('id', $schoolclassId)
        ->first(['schoolclass', 'classcategoryid']);

    if (!$baseClass) {
        Log::warning('[updateSubjectPositions] baseClass not found', compact('schoolclassId'));
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

    Log::info('[updateSubjectPositions] Resolved IDs', [
        'subject_id'           => $subjectId,
        'all_arm_ids'          => $allArmIds->toArray(),
        'all_subjectclass_ids' => $allSubjectClassIds->toArray(),
    ]);

    // FIXED: Get all students with their scores, including registration filter
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

    Log::info('[updateSubjectPositions] After registration filter', [
        'filtered_count' => $allStudents->count(),
    ]);

    if ($allStudents->isEmpty()) {
        Log::warning('[updateSubjectPositions] No students after filter — positions NOT updated');
        $this->nullOutStalePositions($allSubjectClassIds, $term_id, $session_id);
        return;
    }

    // Class-wide position by cum (using DENSE_RANK logic for ties)
    $lastVal = null;
    $currentRank = 0;
    $sortedByCum = $allStudents->sortByDesc('cum')->values();
    foreach ($sortedByCum as $idx => $b) {
        if ($lastVal === null || $b->cum != $lastVal) {
            $currentRank = $idx + 1;
            $lastVal = $b->cum;
        }
        DB::table('broadsheets')->where('id', $b->id)->update(['subject_position_class' => $currentRank]);
    }

    // Class-wide position by total
    $lastVal = null;
    $currentRank = 0;
    $sortedByTotal = $allStudents->sortByDesc('total')->values();
    foreach ($sortedByTotal as $idx => $b) {
        if ($lastVal === null || $b->total != $lastVal) {
            $currentRank = $idx + 1;
            $lastVal = $b->total;
        }
        DB::table('broadsheets')->where('id', $b->id)->update(['subject_position_class_total' => $currentRank]);
    }

    // Arm-specific positions by total
    foreach ($allStudents->groupBy('schoolclass_id') as $armClassId => $studentsInArm) {
        $lastVal = null;
        $currentRank = 0;
        $sortedByTotalArm = $studentsInArm->sortByDesc('total')->values();
        foreach ($sortedByTotalArm as $idx => $b) {
            if ($lastVal === null || $b->total != $lastVal) {
                $currentRank = $idx + 1;
                $lastVal = $b->total;
            }
            DB::table('broadsheets')->where('id', $b->id)->update(['arm_position' => $currentRank]);
        }

        // Arm-specific positions by cum
        $lastVal = null;
        $currentRank = 0;
        $sortedByCumArm = $studentsInArm->sortByDesc('cum')->values();
        foreach ($sortedByCumArm as $idx => $b) {
            if ($lastVal === null || $b->cum != $lastVal) {
                $currentRank = $idx + 1;
                $lastVal = $b->cum;
            }
            DB::table('broadsheets')->where('id', $b->id)->update(['arm_position_cum' => $currentRank]);
        }
    }

    // Null out positions for unregistered students
    $this->nullOutStalePositions($allSubjectClassIds, $term_id, $session_id);

    Log::info('[updateSubjectPositions] DONE', [
        'students_ranked' => $allStudents->count(),
    ]);
}

/**
 * Helper method to null-out positions for unregistered students
 */
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
        $rank = 0;
        $lastScore = null;
        $rows = 0;
        $pos = PromotionStatus::where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->orderBy('subjectstotalscores', 'DESC')
            ->get();

        foreach ($pos as $row) {
            $rows++;
            if ($lastScore !== $row->subjectstotalscores) {
                $lastScore = $row->subjectstotalscores;
                $rank = $rows;
            }
            $suffix = match ($rank) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th'
            };
            PromotionStatus::where('id', $row->id)->update(['position' => $rank . $suffix]);
        }
    }

    protected function updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')->where('id', $subjectclassid)->first(['subjectteacherid']);
        if (!$subjectClass) {
            return;
        }
        $subjectTeacher = DB::table('subjectteacher')->where('id', $subjectClass->subjectteacherid)->first(['subjectid']);
        if (!$subjectTeacher) {
            return;
        }
        $subjectId = $subjectTeacher->subjectid;

        $metrics = \App\Models\BroadsheetsMock::query()
            ->where('broadsheetmock.subjectclass_id', $subjectclassid)
            ->where('broadsheetmock.staff_id', $staffid)
            ->where('broadsheetmock.term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheetmock.total) as class_min'),
                DB::raw('MAX(broadsheetmock.total) as class_max'),
                DB::raw('SUM(broadsheetmock.total) as total_sum'),
                DB::raw('COUNT(broadsheetmock.id) as student_count'),
            ])->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round((float) $metrics->total_sum / $metrics->student_count, 1) : 0;

        $ids = \App\Models\BroadsheetsMock::query()
            ->where('broadsheetmock.subjectclass_id', $subjectclassid)
            ->where('broadsheetmock.staff_id', $staffid)
            ->where('broadsheetmock.term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->pluck('broadsheetmock.id');

        \App\Models\BroadsheetsMock::whereIn('id', $ids)->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }

    protected function updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        $broadsheets = \App\Models\BroadsheetsMock::query()
            ->where('broadsheetmock.subjectclass_id', $subjectclass_id)
            ->where('broadsheetmock.staff_id', $staff_id)
            ->where('broadsheetmock.term_id', $term_id)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $session_id)
            ->orderByDesc('broadsheetmock.total')
            ->orderBy('broadsheetmock.id')
            ->get(['broadsheetmock.id', 'broadsheetmock.total', 'broadsheetmock.subject_position_class']);

        if ($broadsheets->isEmpty()) {
            return;
        }

        $rank = 0;
        $lastTotal = null;
        $lastPosition = 0;
        foreach ($broadsheets as $b) {
            $rank++;
            if ($lastTotal === null || $b->total != $lastTotal) {
                $lastPosition = $rank;
                $lastTotal = $b->total;
            }
            if ($b->subject_position_class != $lastPosition) {
                \App\Models\BroadsheetsMock::where('id', $b->id)->update(['subject_position_class' => $lastPosition]);
            }
        }
    }

    // =========================================================================
    // GPA / CGPA HELPERS
    // =========================================================================

    protected function computeOverallGPAAndCGPA($broadsheets, $schoolclass, $termId, $sessionId)
    {
        if (!$schoolclass || $schoolclass->classcategories->isEmpty()) {
            return;
        }
        $isSenior = $schoolclass->classcategories->first()->is_senior ?? false;
        foreach ($broadsheets as $b) {
            $data = $this->computeOverallForStudent($b->student_id, $schoolclass, $termId, $sessionId, $isSenior);
            $b->gpa = round($data['gpa'], 2);
            $b->cgpa = round($data['cgpa'], 2);
            $b->gpa_grade = $data['gpa_grade'] ?? 'F';
            $b->num_subjects = $data['num_subjects'] ?? 0;
            $b->total_grade_points = $data['total_grade_points'] ?? 0.0;
        }
    }

    protected function computeOverallForStudent($studentId, $schoolclass, $termId, $sessionId, $isSenior)
    {
        $currentBroadsheets = Broadsheets::where('broadsheets.term_id', $termId)
            ->whereHas('broadsheetRecord', fn($q) => $q->where('student_id', $studentId)->where('session_id', $sessionId))
            ->whereExists(function ($query) use ($studentId, $termId, $sessionId) {
                $query->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')
                    ->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')
                    ->where('subjectRegistrationStatus.studentid', $studentId)
                    ->where('subjectRegistrationStatus.termid', $termId)
                    ->where('subjectRegistrationStatus.sessionid', $sessionId);
            })
            ->get(['broadsheets.total']);

        $category = $schoolclass->classcategories->first();
        $averageTotal = $currentBroadsheets->avg('total') ?? 0.0;
        $gpaGrade = $category
            ? $category->calculateGrade($averageTotal)
            : $this->getDefaultGrade($averageTotal);

        $termGradePoints = $currentBroadsheets->map(fn($b) => $this->getGradePoint($b->total, $isSenior));
        $gpa = $termGradePoints->avg() ?? 0.0;
        $numSubjects = $currentBroadsheets->count();
        $totalGradePoints = $termGradePoints->sum();

        $annualGPAs = [];
        $sessions = DB::table('broadsheet_records')
            ->join('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->join('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->where('broadsheet_records.student_id', $studentId)
            ->where('classcategories.is_senior', $isSenior)
            ->select('broadsheet_records.session_id')
            ->distinct()
            ->orderByDesc('broadsheet_records.session_id')
            ->limit(3)
            ->pluck('session_id');

        foreach ($sessions as $targetSession) {
            $sessionGPAs = [];
            for ($t = 1; $t <= 3; $t++) {
                $tb = Broadsheets::where('broadsheets.term_id', $t)
                    ->whereHas('broadsheetRecord', fn($q) => $q->where('student_id', $studentId)->where('session_id', $targetSession))
                    ->whereExists(function ($query) use ($studentId, $t, $targetSession) {
                        $query->select(DB::raw(1))
                            ->from('subjectRegistrationStatus')
                            ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                            ->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')
                            ->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')
                            ->where('subjectRegistrationStatus.studentid', $studentId)
                            ->where('subjectRegistrationStatus.termid', $t)
                            ->where('subjectRegistrationStatus.sessionid', $targetSession);
                    })
                    ->get(['broadsheets.total']);

                $sessionGPAs[] = $tb->map(fn($b) => $this->getGradePoint($b->total, $isSenior))->avg() ?? 0.0;
            }
            $annualGPAs[] = collect($sessionGPAs)->avg() ?? 0.0;
        }

        return [
            'gpa' => $gpa,
            'cgpa' => collect($annualGPAs)->avg() ?? 0.0,
            'gpa_grade' => $gpaGrade,
            'num_subjects' => $numSubjects,
            'total_grade_points' => $totalGradePoints,
        ];
    }

    protected function getGradePoint($score, $isSenior = false)
    {
        if (!$isSenior) {
            if ($score >= 70) {
                return 5.0;
            }
            if ($score >= 60) {
                return 4.0;
            }
            if ($score >= 50) {
                return 3.0;
            }
            if ($score >= 40) {
                return 2.0;
            }
            return 0.0;
        }
        if ($score >= 75) {
            return 5.0;
        }
        if ($score >= 65) {
            return 4.0;
        }
        if ($score >= 50) {
            return 3.0;
        }
        if ($score >= 45) {
            return 2.0;
        }
        if ($score >= 40) {
            return 1.0;
        }
        return 0.0;
    }

    /**
     * Compute the raw cumulative sum and the cumulative average for a term.
     *
     * Rules (per term):
     *   Term 1: bf = 0                -> cum = total     -> cum_ave = cum / 1
     *   Term 2: bf = Term 1's raw cum -> cum = bf + total -> cum_ave = cum / 2
     *   Term 3: bf = Term 2's raw cum -> cum = bf + total -> cum_ave = cum / 3
     *
     * "cum" is the raw running SUM across terms (BF + current term's total).
     * "cum_ave" is that sum divided by the term number — this is the figure that
     * should be DISPLAYED to users (what "cum" used to mean before the rename).
     * BF for the next term always comes from the previous term's raw "cum"
     * (see getPreviousTermCum()), never from cum_ave.
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
            $assessmentScores = $broadsheet->assessmentScores ?? collect();
            $totalRaw = 0;
            foreach ($assessments as $a) {
                $scoreObj = $assessmentScores->where('assessment_id', $a->id)->first();
                $totalRaw += $scoreObj ? (float) $scoreObj->score : 0;
            }

            $subjectId = $broadsheet->subject_id;
            if (!$subjectId) {
                $subjectId = DB::table('broadsheet_records')
                    ->where('id', $broadsheet->broadSheet_record_id ?? $broadsheet->broadsheet_record_id)
                    ->value('subject_id');
            }

            $newBf = $this->getPreviousTermCum($broadsheet->student_id, $subjectId, $termId, $sessionId);

            // Raw sum + its per-term average, per the new cumulative rules above.
            $cumData   = $this->computeCumulative($totalRaw, $newBf, $termId);
            $newCum    = $cumData['cum'];      // raw running sum
            $newCumAve = $cumData['cum_ave'];  // divided value shown to users

            $newGrade = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? $schoolclass->classcategories->first()->calculateGrade($totalRaw)
                : $this->getDefaultGrade($totalRaw);
            $newRemark = $this->getRemark($newGrade);

            $changed = abs($broadsheet->total - $totalRaw) > 0.001
                || abs($broadsheet->bf - $newBf) > 0.001
                || abs($broadsheet->cum - $newCum) > 0.001
                || abs($broadsheet->cum_ave - $newCumAve) > 0.001
                || $broadsheet->grade !== $newGrade
                || $broadsheet->remark !== $newRemark;

            if ($changed) {
                $broadsheet->total = $totalRaw;
                $broadsheet->bf = $newBf;
                $broadsheet->cum = $newCum;
                $broadsheet->cum_ave = $newCumAve;
                $broadsheet->grade = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }
    }

    protected function getDefaultGrade($score)
    {
        if ($score >= 70) {
            return 'A';
        }
        if ($score >= 60) {
            return 'B';
        }
        if ($score >= 50) {
            return 'C';
        }
        if ($score >= 40) {
            return 'D';
        }
        return 'F';
    }

    protected function getRemark($grade)
    {
        return match ($grade) {
            'A', 'A1' => 'Excellent',
            'B', 'B2', 'B3' => 'Very Good',
            'C', 'C4', 'C5', 'C6' => 'Good',
            'D', 'D7', 'E8' => 'Pass',
            default => 'Fail',
        };
    }

    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) {
            return 0;
        }
        // NOTE: reads the RAW running-sum "cum" column (not cum_ave), because BF for the
        // next term must be the previous term's raw total sum, not its per-term average.
        $prev = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->where('broadsheets.term_id', $termId - 1)
            ->value('broadsheets.cum');
        return $prev !== null ? round((float) $prev, 2) : 0;
    }

    public function updateAllArmPositions(Request $request)
    {
        try {
            $request->validate([
                'schoolclass_id' => 'required|exists:schoolclass,id',
                'term_id' => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
            ]);

            $schoolclassId = $request->schoolclass_id;
            $termId = $request->term_id;
            $sessionId = $request->session_id;

            $baseClass = DB::table('schoolclass')->where('id', $schoolclassId)->first(['schoolclass', 'classcategoryid']);
            if (!$baseClass) {
                return response()->json(['success' => false, 'message' => 'Base class not found']);
            }

            $allArms = DB::table('schoolclass')
                ->where('schoolclass', $baseClass->schoolclass)
                ->where('classcategoryid', $baseClass->classcategoryid)
                ->get();

            if ($allArms->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No arms found for this class']);
            }

            $subjectclassRecords = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->whereIn('subjectclass.schoolclassid', $allArms->pluck('id'))
                ->select('subjectteacher.subjectid', DB::raw('MIN(subjectclass.id) as representative_id'))
                ->groupBy('subjectteacher.subjectid')
                ->get();

            $subjectsProcessed = 0;
            foreach ($subjectclassRecords as $record) {
                $repSubjectclass = DB::table('subjectclass')->where('id', $record->representative_id)->first();
                if (!$repSubjectclass) {
                    continue;
                }
                $this->updateSubjectPositions($record->representative_id, $repSubjectclass->staffid ?? 0, $termId, $sessionId);
                $subjectsProcessed++;
            }

            return response()->json([
                'success' => true,
                'message' => "Positions updated! Processed {$subjectsProcessed} subject(s) across " . $allArms->count() . " arms.",
                'data' => [
                    'arms_count' => $allArms->count(),
                    'subjects_processed' => $subjectsProcessed,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('updateAllArmPositions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update positions: ' . $e->getMessage(),
            ], 500);
        }
    }
}
