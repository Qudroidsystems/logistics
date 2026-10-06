<?php
// app/Http/Controllers/PromotionRuleTemplateController.php

namespace App\Http\Controllers;

use App\Models\PromotionRuleTemplate;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PromotionRuleTemplateController extends Controller
{
    public function index()
    {
        $pagetitle = 'Promotion Rule Templates';

        // Get all templates
        $templates = PromotionRuleTemplate::orderBy('name')->get();
        $settings = $templates; // Alias for the view

        // Get schoolclasses for the modal form
        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name')
            ->get();

        // Get sessions and terms for the modal form
        $sessions = Schoolsession::orderBy('session', 'desc')->get();
        $terms = Schoolterm::orderBy('term')->get();

        return view('promotions.templates', compact('templates', 'settings', 'schoolclasses', 'sessions', 'terms', 'pagetitle'));
    }

    public function create()
    {
        $pagetitle = 'Create Promotion Rule Template';

        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name')
            ->get();

        $sessions = Schoolsession::orderBy('session', 'desc')->get();
        $terms = Schoolterm::orderBy('term')->get();

        return view('promotions.templates-create', compact('schoolclasses', 'sessions', 'terms', 'pagetitle'));
    }

    public function edit($id)
    {
        $pagetitle = 'Edit Promotion Rule Template';
        $template = PromotionRuleTemplate::findOrFail($id);

        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name')
            ->get();

        $sessions = Schoolsession::orderBy('session', 'desc')->get();
        $terms = Schoolterm::orderBy('term')->get();

        return view('promotions.templates-edit', compact('template', 'schoolclasses', 'sessions', 'terms', 'pagetitle'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:promotion_rule_templates',
            'description' => 'nullable|string',
            'grade_scale' => 'required|in:senior,junior',
            'promotion_rules' => 'required|json',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $template = PromotionRuleTemplate::create([
                'name' => $request->name,
                'description' => $request->description,
                'grade_scale' => $request->grade_scale,
                'promotion_rules' => json_decode($request->promotion_rules, true),
                'is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
            ]);

            return response()->json(['success' => true, 'message' => 'Template created successfully.', 'data' => $template]);
        } catch (\Exception $e) {
            Log::error('Template Store Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error creating template: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:promotion_rule_templates,name,' . $id,
            'description' => 'nullable|string',
            'grade_scale' => 'required|in:senior,junior',
            'promotion_rules' => 'required|json',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $template = PromotionRuleTemplate::findOrFail($id);
            $template->update([
                'name' => $request->name,
                'description' => $request->description,
                'grade_scale' => $request->grade_scale,
                'promotion_rules' => json_decode($request->promotion_rules, true),
                'is_active' => filter_var($request->input('is_active', $template->is_active), FILTER_VALIDATE_BOOLEAN),
            ]);

            return response()->json(['success' => true, 'message' => 'Template updated successfully.', 'data' => $template]);
        } catch (\Exception $e) {
            Log::error('Template Update Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating template: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $template = PromotionRuleTemplate::findOrFail($id);
            $template->delete();

            return response()->json(['success' => true, 'message' => 'Template deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Template Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting template: ' . $e->getMessage()], 500);
        }
    }

    public function toggleActive(Request $request, $id)
    {
        try {
            $template = PromotionRuleTemplate::findOrFail($id);
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $template->is_active = $isActive;
            $template->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'is_active' => $template->is_active
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle Active Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function loadForClass(Request $request, $id)
    {
        try {
            $template = PromotionRuleTemplate::findOrFail($id);
            $classId = $request->query('classid');
            $termId = $request->query('termid');
            $sessionId = $request->query('sessionid');

            // Get class grade scale
            $categoryData = DB::table('schoolclass_classcategory')
                ->join('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
                ->where('schoolclass_classcategory.schoolclass_id', $classId)
                ->select('classcategories.is_senior')
                ->first();

            $classIsSenior = $categoryData && $categoryData->is_senior;
            $templateIsSenior = $template->grade_scale === 'senior';

            if ($classIsSenior !== $templateIsSenior) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade scale mismatch. Template uses ' . $template->grade_scale . ' but class is ' . ($classIsSenior ? 'senior' : 'junior')
                ], 422);
            }

            $rules = $template->promotion_rules;

            return response()->json([
                'success' => true,
                'template' => $template,
                'merged_rules' => $rules,
                'message' => 'Template loaded successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Load Template Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading template: ' . $e->getMessage()
            ], 500);
        }
    }
}
