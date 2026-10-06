<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;
use App\Models\Classcategory;
use App\Models\SubAssessment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ClasscategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View class-category|Create class-category|Update class-category|Delete class-category', ['only' => ['index']]);
        $this->middleware('permission:Create class-category', ['only' => ['store']]);
        $this->middleware('permission:Update class-category', ['only' => ['update', 'updateclasscategory']]);
        $this->middleware('permission:Delete class-category', ['only' => ['destroy', 'deleteclasscategory', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Class Category Management";
        
        try {
            return view('classcategories.index')
                ->with('pagetitle', $pagetitle);
        } catch (\Exception $e) {
            Log::error('Class Category Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading class categories: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $categories = Classcategory::with(['assessments.subAssessments'])
                ->select('classcategories.*');

            return DataTables::of($categories)
                ->addIndexColumn()

                // ── Category Name ─────────────────────────────────────────
                ->addColumn('category_info', function ($row) {
                    return '<div>
                        <span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->category ?? '')) . '</span>
                        <small class="text-muted d-block">ID: ' . $row->id . '</small>
                    </div>';
                })

                // ── Assessment Info ──────────────────────────────────────
                ->addColumn('assessment_info', function ($row) {
                    $assessment = $row->assessments->first();
                    $subAssessments = $assessment ? $assessment->subAssessments : collect();
                    
                    if ($assessment) {
                        $html = '<div class="fw-semibold">' . e($this->cleanUtf8String($assessment->name ?? '')) . '</div>';
                        $html .= '<div class="small text-muted">Max Score: ' . number_format($assessment->max_score, 2) . '</div>';
                        if ($subAssessments->count() > 0) {
                            $html .= '<div class="mt-1">
                                <span class="badge bg-secondary-subtle text-secondary">
                                    ' . $subAssessments->count() . ' Sub-assessment(s)
                                </span>
                            </div>';
                        }
                        return $html;
                    } else {
                        return '<span class="text-muted">No Assessment</span>';
                    }
                })

                // ── Grade Type ───────────────────────────────────────────
                ->addColumn('grade_type', function ($row) {
                    if ($row->is_senior) {
                        return '<span class="cc-badge cc-badge-senior">Senior (A1-F9)</span>';
                    } else {
                        return '<span class="cc-badge cc-badge-junior">Junior (A-F)</span>';
                    }
                })

                // ── Sub Assessments Count ──────────────────────────────
                ->addColumn('sub_count', function ($row) {
                    $assessment = $row->assessments->first();
                    if ($assessment) {
                        $count = $assessment->subAssessments->count();
                        return '<span class="badge bg-info text-white">' . $count . ' Subs</span>';
                    }
                    return '<span class="text-muted">—</span>';
                })

                // ── Date ──────────────────────────────────────────────────
                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) {
                        return '<span class="text-muted small">—</span>';
                    }
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                // ── Actions ──────────────────────────────────────────────
                ->addColumn('action', function ($row) {
                    $assessment = $row->assessments->first();
                    $subAssessments = $assessment ? $assessment->subAssessments : collect();
                    
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user()->can('Update class-category')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-category-btn" title="Edit" '
                            . 'data-id="%s" data-category="%s" data-is_senior="%s" '
                            . 'data-assessment-name="%s" data-sub-assessments=\'%s\'>'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->category ?? '')),
                            $row->is_senior ? 1 : 0,
                            $assessment ? e($this->cleanUtf8String($assessment->name ?? '')) : '',
                            e($subAssessments->toJson())
                        );
                    }

                    if (auth()->user()->can('Delete class-category')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-category-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->category ?? 'Unknown Category'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['category_info', 'assessment_info', 'grade_type', 'sub_count', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('Class Category DataTable error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // STATS
    // =========================================================================

    public function stats()
    {
        try {
            return response()->json([
                'stats' => [
                    'total' => Classcategory::count(),
                    'senior' => Classcategory::where('is_senior', true)->count(),
                    'junior' => Classcategory::where('is_senior', false)->count(),
                    'with_assessment' => Classcategory::has('assessments')->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Class Category stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'senior' => 0, 'junior' => 0, 'with_assessment' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        Log::info('Store Class Category Request:', $request->all());

        $validator = \Validator::make($request->all(), [
            'category' => 'required|string|max:255|unique:classcategories,category',
            'is_senior' => 'required|boolean',
            'assessments' => 'required|array|size:1',
            'assessments.0.name' => 'required|string|max:100',
            'assessments.0.sub_assessments' => 'required|array|min:1',
            'assessments.0.sub_assessments.*.name' => 'nullable|string|max:100',
            'assessments.0.sub_assessments.*.max_score' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = Classcategory::create([
                'category' => $request->input('category'),
                'is_senior' => $request->input('is_senior'),
            ]);

            $assessmentData = $request->input('assessments')[0];
            $subAssessments = $assessmentData['sub_assessments'];
            $avg = count($subAssessments) > 0 ? array_sum(array_column($subAssessments, 'max_score')) / count($subAssessments) : 0;

            $assessment = Assessment::create([
                'classcategory_id' => $category->id,
                'name' => $assessmentData['name'],
                'max_score' => $avg,
            ]);

            foreach ($subAssessments as $sub) {
                SubAssessment::create([
                    'assessment_id' => $assessment->id,
                    'name' => $sub['name'] ?? null,
                    'max_score' => $sub['max_score'],
                ]);
            }

            DB::commit();
            Log::info('Class Category Created:', $category->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Class category and assessment created successfully',
                'data' => $category->load('assessments.subAssessments')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating class category:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create class category: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function updateclasscategory(Request $request)
    {
        Log::info('Update Class Category AJAX Request:', $request->all());

        $validator = \Validator::make($request->all(), [
            'id' => 'required|exists:classcategories,id',
            'category' => "required|string|max:255|unique:classcategories,category,{$request->id}",
            'is_senior' => 'required|boolean',
            'assessments' => 'required|array|size:1',
            'assessments.0.name' => 'required|string|max:100',
            'assessments.0.sub_assessments' => 'required|array|min:1',
            'assessments.0.sub_assessments.*.name' => 'nullable|string|max:100',
            'assessments.0.sub_assessments.*.max_score' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = Classcategory::findOrFail($request->id);
            $category->update([
                'category' => $request->input('category'),
                'is_senior' => $request->input('is_senior'),
            ]);

            // Delete existing assessment and sub-assessments
            Assessment::where('classcategory_id', $request->id)->delete();

            // Create new assessment and sub-assessments
            $assessmentData = $request->input('assessments')[0];
            $subAssessments = $assessmentData['sub_assessments'];
            $avg = count($subAssessments) > 0 ? array_sum(array_column($subAssessments, 'max_score')) / count($subAssessments) : 0;

            $assessment = Assessment::create([
                'classcategory_id' => $category->id,
                'name' => $assessmentData['name'],
                'max_score' => $avg,
            ]);

            foreach ($subAssessments as $sub) {
                SubAssessment::create([
                    'assessment_id' => $assessment->id,
                    'name' => $sub['name'] ?? null,
                    'max_score' => $sub['max_score'],
                ]);
            }

            DB::commit();
            Log::info('Class Category Updated via AJAX:', $category->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Class category and assessment updated successfully',
                'data' => $category->load('assessments.subAssessments')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating class category via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class category: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        try {
            $category = Classcategory::findOrFail($id);
            
            // Check if category is being used in schoolclass
            $inUse = DB::table('schoolclass')
                ->where('classcategoryid', $id)
                ->exists();
                
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This category is being used by one or more school classes and cannot be deleted.'
                ], 422);
            }
            
            $category->delete();

            Log::info('Class Category Deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Class category and its assessment deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting class category:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class category: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

   // =========================================================================
// BULK DESTROY
// =========================================================================

public function deleteMultiple(Request $request)
{
    try {
        // Get ids from request - handle both array and string formats
        $ids = $request->input('ids');
        
        // If ids is a string, try to decode it or convert to array
        if (is_string($ids)) {
            // Check if it's a JSON string
            $decoded = json_decode($ids, true);
            if (is_array($decoded)) {
                $ids = $decoded;
            } else {
                // If it's a comma-separated string
                $ids = array_map('trim', explode(',', $ids));
            }
        }
        
        // Ensure ids is an array
        if (!is_array($ids)) {
            $ids = [];
        }
        
        // Filter out any empty values
        $ids = array_filter($ids);
        
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No categories selected.'
            ], 400);
        }

        // Validate that all IDs exist
        $existingIds = Classcategory::whereIn('id', $ids)->pluck('id')->toArray();
        $invalidIds = array_diff($ids, $existingIds);
        
        if (!empty($invalidIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some selected categories do not exist.'
            ], 400);
        }

        // Check if any are in use by school classes
        $inUse = DB::table('schoolclass')
            ->whereIn('classcategoryid', $ids)
            ->exists();
            
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Some categories are being used by school classes and cannot be deleted.'
            ], 422);
        }

        DB::beginTransaction();
        
        $deleted = 0;
        foreach ($ids as $id) {
            $category = Classcategory::find($id);
            if ($category) {
                // Delete assessments and sub-assessments (cascade should handle this)
                $category->delete();
                $deleted++;
            }
        }

        DB::commit();

        Log::info('Bulk delete completed', [
            'total' => count($ids),
            'deleted' => $deleted
        ]);

        return response()->json([
            'success' => true,
            'message' => $deleted . ' category(ies) deleted successfully.',
            'deleted_count' => $deleted
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Bulk delete failed:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ids' => $request->input('ids', [])
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error deleting categories: ' . $e->getMessage()
        ], 500);
    }
}
    // =========================================================================
    // HELPERS
    // =========================================================================

    private function cleanUtf8String($string)
    {
        if (empty($string)) {
            return '';
        }
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        return $string;
    }
}