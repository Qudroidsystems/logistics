<?php

namespace App\Http\Controllers;

use App\Models\PromotionSetting;
use App\Models\PromotionRuleTemplate;
use App\Models\CompulsorySubjectClass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use App\Models\Classcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PromotionSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View promotion|Update promotion');
    }

    // ── Index ─────────────────────────────────────────────────────────────────
    public function index()
    {
        $pagetitle = 'Promotion Settings';

        $settings = PromotionSetting::with(['schoolclass.arm', 'session', 'term'])
            ->orderBy('schoolclass_id')
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name')
            ->get();

        $sessions  = Schoolsession::orderBy('session', 'desc')->get();
        $terms     = Schoolterm::orderBy('term')->get();

        $templates = [];
        if (Schema::hasTable('promotion_rule_templates')) {
            $templates = PromotionRuleTemplate::select('id', 'name', 'grade_scale')->orderBy('name')->get();
        }

        return view('promotions.settings', compact(
            'settings', 'schoolclasses', 'sessions', 'terms', 'templates', 'pagetitle'
        ));
    }

    // ── Class meta: grade scale + subject counts ──────────────────────────────
    public function getClassPromotionData(Request $request)
    {
        try {
            $classId   = $request->query('classid');
            $termId    = $request->query('termid');
            $sessionId = $request->query('sessionid');

            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'Class required'], 422);
            }

            // Get class category to determine grade scale
            $classCategory = $this->getClassCategory($classId);
            $isSenior = $classCategory ? (bool)$classCategory->is_senior : false;

            // Set grade scale based on class category
            $gradeScale = $isSenior
                ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9']
                : ['A', 'B', 'C', 'D', 'F'];

            $passAverage = $classCategory ? $classCategory->promotion_pass_average : null;

            $subjectParams = [$classId];
            $subjectSql    = "SELECT DISTINCT s.id, s.subject, s.subject_code
                              FROM subjectclass sc
                              INNER JOIN subjectteacher st ON st.id = sc.subjectteacherid
                              INNER JOIN subject s ON s.id = sc.subjectid
                              WHERE sc.schoolclassid = ?";
            if ($termId && $termId !== '') { $subjectSql .= " AND st.termid = ?"; $subjectParams[] = $termId; }
            if ($sessionId && $sessionId !== '') { $subjectSql .= " AND st.sessionid = ?"; $subjectParams[] = $sessionId; }
            $allSubjects = DB::select($subjectSql, $subjectParams);

            $compulsorySubjects = $this->getCompulsorySubjects($classId, $termId, $sessionId);
            $compIds = array_column($compulsorySubjects, 'id');
            $otherSubjects = array_filter($allSubjects, fn($s) => !in_array((string)$s->id, array_map('strval', $compIds)));

            // Determine recommended grade grouping based on class type
            $recommendedGrouping = $isSenior ? 'exact' : 'grouped';

            Log::info('Class promotion data loaded', [
                'class_id' => $classId,
                'is_senior' => $isSenior,
                'grade_scale' => $gradeScale,
                'recommended_grouping' => $recommendedGrouping,
                'compulsory_subjects' => $compulsorySubjects,
                'subject_ids' => $compIds
            ]);

            return response()->json([
                'success'             => true,
                'pass_average'        => $passAverage,
                'grade_scale'         => $gradeScale,
                'is_senior'           => $isSenior,
                'recommended_grouping' => $recommendedGrouping,
                'compulsory_subjects' => $compulsorySubjects,
                'compulsory_count'    => count($compulsorySubjects),
                'other_count'         => count($otherSubjects),
                'total_subjects'      => count($allSubjects),
            ]);

        } catch (\Exception $e) {
            Log::error('Get Class Promotion Data Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Detect whether the given decoded rules array contains at least one rule
     * with real, evaluatable conditions — i.e. anything beyond an empty
     * shell. Used to warn when those conditions are about to be saved under
     * an Evaluation Mode ('average_only') that will never evaluate them.
     */
    private function rulesHaveSubstantiveConditions(array $rules): bool
    {
        foreach ($rules as $rule) {
            $hasCompSubjGrades = !empty(array_filter(
                $rule['compulsory_section']['subjects'] ?? [],
                fn($s) => !empty($s['min_grade'])
            ));
            $hasCompConds  = !empty($rule['compulsory_section']['count_conditions'] ?? []);
            $hasOtherConds = !empty($rule['other_section']['count_conditions'] ?? []);
            $hasAvgCond    = !empty($rule['average_condition']['enabled'] ?? false);

            if ($hasCompSubjGrades || $hasCompConds || $hasOtherConds || $hasAvgCond) {
                return true;
            }
        }

        return false;
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        Log::info('========== STORE PROMOTION SETTINGS ==========');
        Log::info('Raw request data', [
            'schoolclass_id' => $request->schoolclass_id,
            'session_id' => $request->session_id,
            'term_id' => $request->term_id,
            'rule_set_name' => $request->rule_set_name,
            'rule_logic' => $request->rule_logic,
            'promotion_pass_average' => $request->promotion_pass_average,
            'is_active' => $request->is_active,
            'promotion_rules_raw' => $request->promotion_rules
        ]);

        $v = $this->validateRequest($request);
        if ($v->fails()) {
            Log::error('Validation failed', ['errors' => $v->errors()]);
            return response()->json([
                'success' => false,
                'errors' => $v->errors(),
                'message' => 'Validation failed: ' . $v->errors()->first()
            ], 422);
        }

        try {
            [$sessionId, $termId] = $this->cleanIds($request);

            // Get class category to determine proper grouping
            $classCategory = $this->getClassCategory($request->schoolclass_id);
            $isSenior = $classCategory ? (bool)$classCategory->is_senior : false;

            // Parse and validate rules
            $rules = $this->parseRules($request, $isSenior);
            if ($rules instanceof \Illuminate\Http\JsonResponse) return $rules;

            Log::info('Parsed rules before save', [
                'rules_count' => count($rules),
                'is_senior' => $isSenior,
                'rules_structure' => $rules
            ]);

            if (empty($rules)) {
                return response()->json(['success' => false, 'message' => 'At least one promotion rule is required.'], 422);
            }

            $ruleLogic = $request->rule_logic ?? 'grade_count';

            // ── Guard: average_only mode makes per-rule conditions dead code ──
            // If the caller has built out rules with real conditions but chose
            // "Minimum Average Only" as the Evaluation Mode, those conditions
            // will never be evaluated by PromotionEvaluator — only the global
            // promotion_pass_average decides the outcome in that mode. Warn
            // loudly (server log) and surface a non-blocking notice in the
            // response so the UI can flag it without blocking the save.
            $averageOnlyWarning = null;
            if ($ruleLogic === 'average_only' && $this->rulesHaveSubstantiveConditions($rules)) {
                $averageOnlyWarning = 'Evaluation Mode is "Minimum Average Only" — the compulsory subject grades, '
                    . 'count conditions, and per-rule average conditions in the rules below will NOT be evaluated. '
                    . 'Only the Global Minimum Average field decides the outcome in this mode. '
                    . 'Switch to "Grade Count AND/OR Average" if you want the rules below to apply.';

                Log::warning('Saving rule set in average_only mode with fully-configured rules — those rules will be ignored at evaluation time', [
                    'schoolclass_id' => $request->schoolclass_id,
                    'rule_set_name'  => $request->rule_set_name,
                ]);
            }

            $isActive = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);
            $isDefault = filter_var($request->input('is_default', false), FILTER_VALIDATE_BOOLEAN);

            // Generate a unique rule set name if not provided
            $ruleSetName = $request->rule_set_name;
            if (empty($ruleSetName)) {
                $existingCount = PromotionSetting::where('schoolclass_id', $request->schoolclass_id)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->count();
                $ruleSetName = "Rule Set " . ($existingCount + 1);
            }

            // Get the next priority
            $maxPriority = PromotionSetting::where('schoolclass_id', $request->schoolclass_id)
                ->where('session_id', $sessionId)
                ->where('term_id', $termId)
                ->max('priority');
            $priority = ($maxPriority !== null) ? $maxPriority + 1 : 1;

            // If this is set as default, remove default from other rule sets for this class
            if ($isDefault) {
                PromotionSetting::where('schoolclass_id', $request->schoolclass_id)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->update(['is_default' => false]);
            }

            // FIX: Handle promotion_pass_average - preserve 0 as a valid value
            $avgValue = null;
            if ($request->has('promotion_pass_average') && $request->promotion_pass_average !== '' && $request->promotion_pass_average !== null) {
                $avgValue = (float) $request->promotion_pass_average;
                // Log to verify
                Log::info('Storing promotion setting with average', [
                    'raw_value' => $request->promotion_pass_average,
                    'converted_value' => $avgValue,
                ]);
            }

            $setting = PromotionSetting::create([
                'schoolclass_id'         => $request->schoolclass_id,
                'session_id'             => $sessionId,
                'term_id'                => $termId,
                'rule_set_name'          => $ruleSetName,
                'priority'               => $priority,
                'promoted_label'         => $request->promoted_label      ?? 'Promoted',
                'trial_label'            => $request->trial_label         ?? 'Promoted on Trial',
                'see_principal_label'    => $request->see_principal_label ?? 'Advised to See Principal',
                'repeat_label'           => $request->repeat_label        ?? 'Advice to Repeat',
                'rule_logic'             => $ruleLogic,
                'promotion_pass_average' => $avgValue,  // FIXED: Don't use ?: null operator
                'is_active'              => $isActive,
                'is_default'             => $isDefault,
                'template_id'            => $request->template_id ?: null,
            ]);

            $setting->promotion_rules = $rules;
            $setting->save();

            Log::info('Setting saved successfully', [
                'id' => $setting->id,
                'rule_set_name' => $ruleSetName,
                'promotion_pass_average' => $setting->promotion_pass_average,
                'rules_count' => count($setting->promotion_rules),
            ]);

            Log::info('New promotion rule set created', ['id' => $setting->id, 'name' => $ruleSetName]);

            $response = [
                'success' => true,
                'message' => "Rule set '{$ruleSetName}' created with " . count($rules) . ' rules.',
                'data' => $setting
            ];
            if ($averageOnlyWarning) {
                $response['warning'] = $averageOnlyWarning;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Store Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Error saving settings: ' . $e->getMessage()], 500);
        }
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        Log::info('========== UPDATE PROMOTION SETTINGS ==========');
        Log::info('Update request data', [
            'id' => $id,
            'schoolclass_id' => $request->schoolclass_id,
            'session_id' => $request->session_id,
            'term_id' => $request->term_id,
            'rule_set_name' => $request->rule_set_name,
            'promotion_pass_average' => $request->promotion_pass_average,
            'promotion_rules_raw' => $request->promotion_rules
        ]);

        $v = $this->validateRequest($request);
        if ($v->fails()) {
            Log::error('Validation failed', ['errors' => $v->errors()]);
            return response()->json([
                'success' => false,
                'errors' => $v->errors(),
                'message' => 'Validation failed: ' . $v->errors()->first()
            ], 422);
        }

        try {
            $setting = PromotionSetting::findOrFail($id);
            [$sessionId, $termId] = $this->cleanIds($request);

            // Get class category to determine proper grouping
            $classCategory = $this->getClassCategory($request->schoolclass_id ?? $setting->schoolclass_id);
            $isSenior = $classCategory ? (bool)$classCategory->is_senior : false;

            // Parse and validate rules
            $rules = $this->parseRules($request, $isSenior);
            if ($rules instanceof \Illuminate\Http\JsonResponse) return $rules;

            Log::info('Parsed rules before update', [
                'rules_count' => count($rules),
                'is_senior' => $isSenior,
                'rules_structure' => $rules
            ]);

            if (empty($rules)) {
                return response()->json(['success' => false, 'message' => 'At least one promotion rule is required.'], 422);
            }

            $ruleLogic = $request->rule_logic ?? 'grade_count';

            // ── Guard: average_only mode makes per-rule conditions dead code ──
            $averageOnlyWarning = null;
            if ($ruleLogic === 'average_only' && $this->rulesHaveSubstantiveConditions($rules)) {
                $averageOnlyWarning = 'Evaluation Mode is "Minimum Average Only" — the compulsory subject grades, '
                    . 'count conditions, and per-rule average conditions in the rules below will NOT be evaluated. '
                    . 'Only the Global Minimum Average field decides the outcome in this mode. '
                    . 'Switch to "Grade Count AND/OR Average" if you want the rules below to apply.';

                Log::warning('Updating rule set into average_only mode with fully-configured rules — those rules will be ignored at evaluation time', [
                    'settings_id'   => $id,
                    'rule_set_name' => $request->rule_set_name ?? $setting->rule_set_name,
                ]);
            }

            $isActive = filter_var($request->input('is_active', $setting->is_active), FILTER_VALIDATE_BOOLEAN);
            $isDefault = filter_var($request->input('is_default', $setting->is_default), FILTER_VALIDATE_BOOLEAN);

            // If this is set as default, remove default from other rule sets for this class
            if ($isDefault && !$setting->is_default) {
                PromotionSetting::where('schoolclass_id', $setting->schoolclass_id)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            // FIX: Handle promotion_pass_average - preserve 0 as a valid value
            $avgValue = $setting->promotion_pass_average; // Keep existing by default
            if ($request->has('promotion_pass_average')) {
                if ($request->promotion_pass_average !== '' && $request->promotion_pass_average !== null) {
                    $avgValue = (float) $request->promotion_pass_average;
                    Log::info('Updating promotion setting with average', [
                        'setting_id' => $id,
                        'raw_value' => $request->promotion_pass_average,
                        'converted_value' => $avgValue,
                    ]);
                } else {
                    $avgValue = null;
                }
            }

            $setting->update([
                'schoolclass_id'         => $request->schoolclass_id ?? $setting->schoolclass_id,
                'session_id'             => $sessionId,
                'term_id'                => $termId,
                'rule_set_name'          => $request->rule_set_name ?? $setting->rule_set_name,
                'promoted_label'         => $request->promoted_label      ?? 'Promoted',
                'trial_label'            => $request->trial_label         ?? 'Promoted on Trial',
                'see_principal_label'    => $request->see_principal_label ?? 'Advised to See Principal',
                'repeat_label'           => $request->repeat_label        ?? 'Advice to Repeat',
                'rule_logic'             => $ruleLogic,
                'promotion_pass_average' => $avgValue,  // FIXED: Don't use ?: null operator
                'is_active'              => $isActive,
                'is_default'             => $isDefault,
                'template_id'            => $request->template_id ?: null,
            ]);

            $setting->promotion_rules = $rules;
            $setting->save();

            Log::info('Setting updated successfully', [
                'id' => $id,
                'rule_set_name' => $setting->rule_set_name,
                'promotion_pass_average' => $setting->promotion_pass_average,
                'rules_count' => count($setting->promotion_rules),
            ]);

            $response = [
                'success' => true,
                'message' => "Rule set '{$setting->rule_set_name}' updated with " . count($rules) . ' rules.',
                'data' => $setting
            ];
            if ($averageOnlyWarning) {
                $response['warning'] = $averageOnlyWarning;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Update Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Error updating settings: ' . $e->getMessage()], 500);
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        try {
            $setting = PromotionSetting::find($id);
            if (!$setting) {
                return response()->json(['success' => false, 'message' => 'Promotion setting not found.'], 404);
            }

            $ruleSetName = $setting->rule_set_name;
            $setting->delete();

            Log::info('Promotion rule set deleted', ['id' => $id, 'name' => $ruleSetName]);
            return response()->json(['success' => true, 'message' => "Rule set '{$ruleSetName}' deleted successfully."]);
        } catch (\Exception $e) {
            Log::error('Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting: ' . $e->getMessage()], 500);
        }
    }

    // ── Toggle Active Status ──────────────────────────────────────────────────
    public function toggleActive(Request $request, $id)
    {
        try {
            $setting = PromotionSetting::findOrFail($id);
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $setting->is_active = $isActive;
            $setting->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'is_active' => $setting->is_active
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle Active Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ── Subjects by Class API ─────────────────────────────────────────────────
    public function subjectsByClass(Request $request)
    {
        try {
            $classId   = $request->query('classid');
            $termId    = $request->query('termid');
            $sessionId = $request->query('sessionid');

            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'Class required'], 422);
            }

            $sql = "SELECT DISTINCT s.id, s.subject, s.subject_code
                    FROM subjectclass sc
                    INNER JOIN subjectteacher st ON st.id = sc.subjectteacherid
                    INNER JOIN subject s ON s.id = sc.subjectid
                    WHERE sc.schoolclassid = ?";

            $params = [$classId];

            if ($termId && $termId !== 'null' && $termId !== '') {
                $sql .= " AND st.termid = ?";
                $params[] = $termId;
            }

            if ($sessionId && $sessionId !== 'null' && $sessionId !== '') {
                $sql .= " AND st.sessionid = ?";
                $params[] = $sessionId;
            }

            $results = DB::select($sql, $params);

            $subjects = array_map(function($row) {
                return [
                    'id' => (string)$row->id,
                    'subject' => $row->subject,
                    'subject_code' => $row->subject_code,
                ];
            }, $results);

            return response()->json([
                'success' => true,
                'subjects' => $subjects,
                'total' => count($subjects)
            ]);

        } catch (\Exception $e) {
            Log::error('Subjects by Class Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading subjects: ' . $e->getMessage()
            ], 500);
        }
    }

    // ── Compulsory Subjects by Class API ──────────────────────────────────────
    public function compulsoryByClass(Request $request)
    {
        try {
            $classId   = $request->query('classid');
            $termId    = $request->query('termid');
            $sessionId = $request->query('sessionid');

            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'Class required'], 422);
            }

            $compulsorySubjects = $this->getCompulsorySubjects($classId, $termId, $sessionId);

            return response()->json([
                'success' => true,
                'subjects' => $compulsorySubjects,
                'total' => count($compulsorySubjects)
            ]);

        } catch (\Exception $e) {
            Log::error('Compulsory Subjects Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading compulsory subjects: ' . $e->getMessage()
            ], 500);
        }
    }

    // ── Debug method to check saved rules ─────────────────────────────────────
    public function debugRules($id)
    {
        $setting = PromotionSetting::find($id);
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $classCategory = $this->getClassCategory($setting->schoolclass_id);

        $result = [
            'id' => $setting->id,
            'rule_set_name' => $setting->rule_set_name,
            'schoolclass_id' => $setting->schoolclass_id,
            'class_category' => $classCategory,
            'session_id' => $setting->session_id,
            'term_id' => $setting->term_id,
            'is_active' => $setting->is_active,
            'rule_logic' => $setting->rule_logic,
            'promotion_pass_average' => $setting->promotion_pass_average,
            'rules_count' => count($setting->promotion_rules ?? []),
            'average_only_but_has_substantive_rules' =>
                ($setting->rule_logic === 'average_only')
                    && $this->rulesHaveSubstantiveConditions($setting->promotion_rules ?? []),
            'rules' => []
        ];

        foreach ($setting->promotion_rules as $index => $rule) {
            $result['rules'][] = [
                'index' => $index,
                'rule_name' => $rule['rule_name'] ?? 'Unnamed',
                'status_label' => $rule['status_label'] ?? 'unknown',
                'grade_grouping' => $rule['grade_grouping'] ?? 'not set',
                'subjects' => $rule['compulsory_section']['subjects'] ?? [],
                'subject_ids' => array_column($rule['compulsory_section']['subjects'] ?? [], 'subject_id'),
                'min_grades' => array_column($rule['compulsory_section']['subjects'] ?? [], 'min_grade')
            ];
        }

        return response()->json($result);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Get class category (senior/junior) for a class
     */
    private function getClassCategory($classId): ?object
    {
        return DB::table('schoolclass_classcategory')
            ->join('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
            ->where('schoolclass_classcategory.schoolclass_id', $classId)
            ->select('classcategories.id', 'classcategories.category', 'classcategories.is_senior', 'classcategories.promotion_pass_average')
            ->first();
    }

    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'schoolclass_id'         => 'required|exists:schoolclass,id',
            'session_id'             => 'nullable|exists:schoolsession,id',
            'term_id'                => 'nullable|exists:schoolterm,id',
            'rule_set_name'          => 'nullable|string|max:255',
            'promoted_label'         => 'nullable|string|max:100',
            'trial_label'            => 'nullable|string|max:100',
            'see_principal_label'    => 'nullable|string|max:100',
            'repeat_label'           => 'nullable|string|max:100',
            'rule_logic'             => 'nullable|in:grade_count,average_only,both,subject_only',
            'promotion_pass_average' => 'nullable|numeric|min:0|max:100',
            'is_active'              => 'nullable|boolean',
            'is_default'             => 'nullable|boolean',
            'template_id'            => 'nullable|exists:promotion_rule_templates,id',
            'promotion_rules'        => 'nullable|json',
        ]);
    }

    private function cleanIds(Request $request): array
    {
        $s = $request->session_id ?: null;
        $t = $request->term_id    ?: null;
        if (in_array($s, ['null', ''])) $s = null;
        if (in_array($t, ['null', ''])) $t = null;
        return [$s, $t];
    }

    private function parseRules(Request $request, bool $isSenior = false)
    {
        if (!$request->filled('promotion_rules')) return [];

        $rules = json_decode($request->promotion_rules, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decode error', ['error' => json_last_error_msg()]);
            return response()->json(['success' => false, 'message' => 'Invalid JSON in promotion rules: ' . json_last_error_msg()], 422);
        }

        Log::info('Raw JSON decoded rules', ['rules' => $rules]);

        $validStatuses = ['promoted', 'trial', 'see_principal', 'repeat'];

        // Recommended grouping based on class type
        $recommendedGrouping = $isSenior ? 'exact' : 'grouped';

        foreach ($rules as $i => $rule) {
            $n = $i + 1;

            if (empty($rule['rule_name'])) {
                return response()->json(['success' => false, 'message' => "Rule {$n}: name required."], 422);
            }

            if (!in_array($rule['status_label'] ?? '', $validStatuses)) {
                return response()->json(['success' => false, 'message' => "Rule {$n}: invalid status."], 422);
            }

            // Auto-correct grade grouping based on class type
            $currentGrouping = $rule['grade_grouping'] ?? 'grouped';
            if ($isSenior && $currentGrouping === 'grouped') {
                Log::warning("Rule {$n}: Auto-correcting grade grouping from 'grouped' to 'exact' for senior class", [
                    'rule_name' => $rule['rule_name']
                ]);
                $rule['grade_grouping'] = 'exact';
            } elseif (!$isSenior && $currentGrouping === 'exact') {
                Log::warning("Rule {$n}: Auto-correcting grade grouping from 'exact' to 'grouped' for junior class", [
                    'rule_name' => $rule['rule_name']
                ]);
                $rule['grade_grouping'] = 'grouped';
            }

            // Ensure compulsory_section exists
            if (!isset($rule['compulsory_section'])) {
                $rule['compulsory_section'] = ['subjects' => [], 'count_conditions' => []];
            }

            // Ensure subjects array exists and validate subject_id
            if (!isset($rule['compulsory_section']['subjects'])) {
                $rule['compulsory_section']['subjects'] = [];
            }

            // Log subjects before filtering
            Log::info("Rule {$n} subjects before validation", [
                'rule_name' => $rule['rule_name'],
                'subjects_raw' => $rule['compulsory_section']['subjects']
            ]);

            // Filter out subjects without valid subject_id
            $validSubjects = [];
            foreach ($rule['compulsory_section']['subjects'] as $subject) {
                if (isset($subject['subject_id']) && !empty($subject['subject_id'])) {
                    $validSubjects[] = $subject;
                    Log::info("Valid subject found", [
                        'subject_id' => $subject['subject_id'],
                        'min_grade' => $subject['min_grade'] ?? 'not set'
                    ]);
                } else {
                    Log::warning('Subject without ID found in rule', [
                        'rule_index' => $i,
                        'rule_name' => $rule['rule_name'],
                        'subject' => $subject
                    ]);
                }
            }
            $rule['compulsory_section']['subjects'] = $validSubjects;

            // Log subjects after filtering
            Log::info("Rule {$n} subjects after validation", [
                'rule_name' => $rule['rule_name'],
                'valid_subjects_count' => count($validSubjects),
                'subject_ids' => array_column($validSubjects, 'subject_id')
            ]);

            // Ensure other_section exists
            if (!isset($rule['other_section'])) {
                $rule['other_section'] = ['count_conditions' => []];
            }

            if (!isset($rule['other_section']['count_conditions'])) {
                $rule['other_section']['count_conditions'] = [];
            }

            // Ensure average_condition exists
            if (!isset($rule['average_condition'])) {
                $rule['average_condition'] = ['enabled' => false, 'min_average' => 50, 'logic' => 'AND'];
            }

            $rules[$i] = $rule;
        }

        Log::info('Final processed rules', [
            'rules_count' => count($rules),
            'is_senior' => $isSenior,
            'recommended_grouping' => $recommendedGrouping,
            'rules_summary' => array_map(function($rule) {
                return [
                    'name' => $rule['rule_name'],
                    'grade_grouping' => $rule['grade_grouping'],
                    'subject_ids' => array_column($rule['compulsory_section']['subjects'] ?? [], 'subject_id')
                ];
            }, $rules)
        ]);

        return $rules;
    }

    private function getCompulsorySubjects($classId, $termId = null, $sessionId = null): array
    {
        $query = CompulsorySubjectClass::where('schoolclassid', $classId)
            ->where(function ($q) use ($termId, $sessionId) {
                $q->where(function ($q2) use ($termId, $sessionId) {
                    if ($termId)    $q2->where('termid', $termId);
                    if ($sessionId) $q2->where('sessionid', $sessionId);
                })->orWhere(function ($q2) {
                    $q2->whereNull('termid')->whereNull('sessionid');
                });
            })
            ->with('subject');

        $results = $query->get()->map(fn($cs) => [
            'id'           => (string) $cs->subjectId,
            'subject'      => $cs->subject?->subject ?? 'N/A',
            'subject_code' => $cs->subject?->subject_code ?? '',
            'default_min_grade' => $cs->min_grade ?? '',
        ])->unique('id')->values()->toArray();

        Log::info('getCompulsorySubjects result', [
            'class_id' => $classId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'subjects' => $results
        ]);

        return $results;
    }
}