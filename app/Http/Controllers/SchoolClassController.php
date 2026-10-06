<?php

namespace App\Http\Controllers;

use App\Models\Schoolarm;
use App\Models\Schoolclass;
use App\Models\ClassTeacher;
use Illuminate\Http\Request;
use App\Models\Classcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SchoolClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-class|Create school-class|Update school-class|Delete school-class', ['only' => ['index']]);
        $this->middleware('permission:Create school-class', ['only' => ['store']]);
        $this->middleware('permission:Update school-class', ['only' => ['update']]);
        $this->middleware('permission:Delete school-class', ['only' => ['destroy', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        Log::channel('schoolclass')->info('School Class Index Request');

        $pagetitle = "School Class Management";

        try {
            $arms = Schoolarm::all();
            $classcategories = Classcategory::all();

            return view('schoolclass.index')
                ->with('arms', $arms)
                ->with('classcategories', $classcategories)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::channel('schoolclass')->error('Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading school classes: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

   // =========================================================================
// DATATABLE — AJAX (FIXED)
// =========================================================================

public function data(Request $request)
{
    try {
        $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolclass_classcategory', 'schoolclass_classcategory.schoolclass_id', '=', 'schoolclass.id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
            ->select(
                'schoolclass.id',
                'schoolclass.schoolclass',
                'schoolarm.arm as arm_name',
                'schoolclass.arm as arm_id',
                DB::raw('GROUP_CONCAT(DISTINCT classcategories.category ORDER BY classcategories.category SEPARATOR ", ") as classcategory'),
                DB::raw('GROUP_CONCAT(DISTINCT classcategories.id ORDER BY classcategories.id SEPARATOR "," ) as classcategoryids'),
                'schoolclass.classcategoryid',
                'schoolclass.created_at',
                'schoolclass.updated_at'
            )
            ->groupBy('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm', 'schoolclass.arm', 'schoolclass.classcategoryid', 'schoolclass.created_at', 'schoolclass.updated_at');

        return DataTables::of($classes)
            ->addIndexColumn()

            // ── School Class with ID ───────────────────────────────────────
            ->addColumn('class_info', function ($row) {
                return '<div>
                    <span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->schoolclass ?? '')) . '</span>
                    <small class="text-muted d-block">ID: ' . $row->id . '</small>
                </div>';
            })

            // ── Arm Badge ──────────────────────────────────────────────────
            ->addColumn('arm_info', function ($row) {
                $armName = $this->cleanUtf8String($row->arm_name ?? 'N/A');
                return '<span class="sc-badge sc-badge-arm">' . e($armName) . '</span>
                    <small class="text-muted d-block">Arm ID: ' . ($row->arm_id ?? 'N/A') . '</small>';
            })

            // ── Categories ──────────────────────────────────────────────────
            ->addColumn('categories_info', function ($row) {
                $categoryNames = !empty($row->classcategory) ? explode(', ', $row->classcategory) : [];
                $html = '<div class="d-flex flex-wrap gap-1">';
                if (empty($categoryNames)) {
                    $html .= '<span class="text-muted">No categories</span>';
                } else {
                    foreach ($categoryNames as $catName) {
                        if (!empty($catName)) {
                            $html .= '<span class="sc-badge sc-badge-category">' . e($this->cleanUtf8String($catName)) . '</span>';
                        }
                    }
                }
                $html .= '</div>';
                $html .= '<small class="text-muted d-block">Category IDs: ' . ($row->classcategoryids ?? 'N/A') . '</small>';
                return $html;
            })

            // ── Date ──────────────────────────────────────────────────────
            ->addColumn('formatted_date', function ($row) {
                if (!$row->updated_at) {
                    return '<span class="text-muted small">—</span>';
                }
                return '<small class="text-muted">'
                    . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                    . '</small>';
            })

            // ── Actions ───────────────────────────────────────────────────
            ->addColumn('action', function ($row) {
                $title = e($this->cleanUtf8String($row->schoolclass ?? 'Unknown Class'));

                $buttons = '<div class="d-flex gap-1">';

                if (auth()->user()->can('Update school-class')) {
                    $buttons .= sprintf(
                        '<button class="btn btn-sm btn-outline-secondary edit-class-btn" title="Edit" '
                        . 'data-id="%s" data-schoolclass="%s" data-arm-id="%s" data-category-ids="%s">'
                        . '<i class="ph-pencil"></i></button>',
                        $row->id,
                        e($this->cleanUtf8String($row->schoolclass ?? '')),
                        $row->arm_id,
                        $row->classcategoryids
                    );
                }

                if (auth()->user()->can('Delete school-class')) {
                    $buttons .= sprintf(
                        '<button class="btn btn-sm btn-outline-danger delete-class-btn" title="Delete" '
                        . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                        $row->id,
                        $title
                    );
                }

                return $buttons . '</div>';
            })

            ->rawColumns(['class_info', 'arm_info', 'categories_info', 'formatted_date', 'action'])
            ->make(true);
            
    } catch (\Exception $e) {
        Log::channel('schoolclass')->error('DataTable error:', [
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
                    'total' => Schoolclass::count(),
                    'total_arms' => Schoolarm::count(),
                    'total_categories' => Classcategory::count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('schoolclass')->error('Stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'total_arms' => 0, 'total_categories' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE - UPDATED WITH classcategoryid FIX
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schoolclass' => 'required|string|max:255',
            'arm_id' => 'required|array|min:1',
            'arm_id.*' => 'exists:schoolarm,id',
            'classcategoryid' => 'required|array|min:1',
            'classcategoryid.*' => 'exists:classcategories,id',
        ], [
            'schoolclass.required' => 'Please enter a school class name.',
            'arm_id.required' => 'Please select at least one arm.',
            'arm_id.*.exists' => 'One or more selected arms do not exist.',
            'classcategoryid.required' => 'Please select at least one category.',
            'classcategoryid.*.exists' => 'One or more selected categories do not exist.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $armIds = $request->arm_id ?? [];
            foreach ($armIds as $armId) {
                $exists = Schoolclass::where('schoolclass', $request->schoolclass)
                    ->where('arm', $armId)
                    ->exists();
                if ($exists) {
                    $arm = Schoolarm::find($armId);
                    $armName = $arm ? $arm->arm : 'Unknown';
                    $validator->errors()->add(
                        'schoolclass',
                        "The combination of class '{$request->schoolclass}' and arm '{$armName}' already exists."
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdRecords = [];
            $armIds = $request->arm_id;
            $categoryIds = $request->classcategoryid;

            // Get the first category ID for the classcategoryid column
            // This is used as a default/primary category
            $primaryCategoryId = $categoryIds[0] ?? null;

            foreach ($armIds as $armId) {
                $schoolclass = new Schoolclass();
                $schoolclass->schoolclass = $request->schoolclass;
                $schoolclass->arm = $armId;
                $schoolclass->description = $request->description ?? 'Null';
                
                // CRITICAL FIX: Set classcategoryid to the first selected category
                // This ensures the column has a value
                $schoolclass->classcategoryid = $primaryCategoryId;
                
                $schoolclass->save();

                // Attach ALL categories to pivot table
                $schoolclass->classcategories()->attach($categoryIds);

                $arm = Schoolarm::find($armId);
                $categories = Classcategory::whereIn('id', $categoryIds)->get(['id', 'category']);

                $createdRecords[] = [
                    'id' => $schoolclass->id,
                    'schoolclass' => $schoolclass->schoolclass,
                    'arm_id' => $schoolclass->arm,
                    'arm_name' => $arm ? $arm->arm : 'Unknown',
                    'classcategories' => $categories->toArray(),
                    'classcategoryid' => $schoolclass->classcategoryid,
                    'description' => $schoolclass->description,
                    'updated_at' => $schoolclass->updated_at->toISOString(),
                    'created_at' => $schoolclass->created_at->toISOString()
                ];
            }

            DB::commit();

            Log::channel('schoolclass')->info('School classes created successfully', [
                'count' => count($createdRecords),
                'schoolclass' => $request->schoolclass,
                'primary_category_id' => $primaryCategoryId
            ]);

            return response()->json([
                'success' => true,
                'message' => count($createdRecords) . ' school class(es) added successfully!',
                'data' => $createdRecords
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('schoolclass')->error('Store failed:', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error storing school class: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE - UPDATED WITH classcategoryid FIX
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'schoolclass' => 'required|string|max:255',
            'arm_id' => 'required|exists:schoolarm,id',
            'classcategoryid' => 'required|array|min:1',
            'classcategoryid.*' => 'exists:classcategories,id',
        ], [
            'schoolclass.required' => 'Please enter a school class name.',
            'arm_id.required' => 'Please select one arm.',
            'arm_id.exists' => 'The selected arm does not exist.',
            'classcategoryid.required' => 'Please select at least one category.',
            'classcategoryid.*.exists' => 'One or more selected categories do not exist.',
        ]);

        $validator->after(function ($validator) use ($request, $id) {
            $armId = $request->arm_id;
            $exists = Schoolclass::where('schoolclass', $request->schoolclass)
                ->where('arm', $armId)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                $arm = Schoolarm::find($armId);
                $armName = $arm ? $arm->arm : 'Unknown';
                $validator->errors()->add(
                    'schoolclass',
                    "The combination of class '{$request->schoolclass}' and arm '{$armName}' already exists."
                );
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $schoolclass = Schoolclass::findOrFail($id);
            $schoolclass->schoolclass = $request->schoolclass;
            $schoolclass->arm = $request->arm_id;
            $schoolclass->description = $request->description ?? 'Null';
            
            // CRITICAL FIX: Update classcategoryid to the first selected category
            $categoryIds = $request->classcategoryid;
            $schoolclass->classcategoryid = $categoryIds[0] ?? $schoolclass->classcategoryid;
            
            $schoolclass->save();

            // Sync ALL categories to pivot table
            $schoolclass->classcategories()->sync($categoryIds);

            $arm = Schoolarm::find($schoolclass->arm);
            $categories = Classcategory::whereIn('id', $categoryIds)->get(['id', 'category']);

            $updatedRecord = [
                'id' => $schoolclass->id,
                'schoolclass' => $schoolclass->schoolclass,
                'arm_id' => $schoolclass->arm,
                'arm_name' => $arm ? $arm->arm : 'Unknown',
                'classcategories' => $categories->toArray(),
                'classcategoryid' => $schoolclass->classcategoryid,
                'description' => $schoolclass->description,
                'updated_at' => $schoolclass->updated_at->toISOString(),
                'created_at' => $schoolclass->created_at->toISOString()
            ];

            DB::commit();

            Log::channel('schoolclass')->info('School class updated successfully', $updatedRecord);

            return response()->json([
                'success' => true,
                'message' => 'School class updated successfully!',
                'data' => $updatedRecord
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('schoolclass')->error('Update failed:', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating school class: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $schoolclass = Schoolclass::find($id);
            if (!$schoolclass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found.'
                ], 404);
            }

            // Detach categories
            $schoolclass->classcategories()->detach();

            // Delete from class teacher table
            ClassTeacher::where('schoolclassid', $id)->delete();

            // Delete the school class
            $schoolclass->delete();

            DB::commit();

            Log::channel('schoolclass')->info('School class deleted successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'School class deleted successfully!'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('schoolclass')->error('Destroy failed:', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting school class: ' . $e->getMessage()
            ], 500);
        }
    }

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
                    'message' => 'No classes selected.'
                ], 400);
            }

            // Validate that all IDs exist
            $existingIds = Schoolclass::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds = array_diff($ids, $existingIds);
            
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected classes do not exist: ' . implode(', ', $invalidIds)
                ], 400);
            }

            DB::beginTransaction();
            
            $deleted = 0;
            foreach ($ids as $id) {
                $schoolclass = Schoolclass::find($id);
                if ($schoolclass) {
                    // Detach categories
                    $schoolclass->classcategories()->detach();
                    
                    // Delete from class teacher table
                    ClassTeacher::where('schoolclassid', $id)->delete();
                    
                    // Delete the school class
                    $schoolclass->delete();
                    $deleted++;
                }
            }

            DB::commit();

            Log::channel('schoolclass')->info('Bulk delete completed', [
                'total' => count($ids),
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' class(es) deleted successfully.',
                'deleted_count' => $deleted
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('schoolclass')->error('Bulk delete failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ids' => $request->input('ids', [])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting classes: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // GET SINGLE CLASS (for edit pre-load)
    // =========================================================================

    public function show($id)
    {
        try {
            $schoolclass = Schoolclass::with('classcategories')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $schoolclass->id,
                    'schoolclass' => $schoolclass->schoolclass,
                    'arm_id' => $schoolclass->arm,
                    'classcategoryid' => $schoolclass->classcategoryid,
                    'category_ids' => $schoolclass->classcategories->pluck('id')->toArray(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found'
            ], 404);
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