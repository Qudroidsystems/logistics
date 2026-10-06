<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schoolarm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SchoolArmController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-arm|Create school-arm|Update school-arm|Delete school-arm', ['only' => ['index']]);
        $this->middleware('permission:Create school-arm', ['only' => ['store']]);
        $this->middleware('permission:Update school-arm', ['only' => ['update', 'updatearm']]);
        $this->middleware('permission:Delete school-arm', ['only' => ['destroy', 'deletearm', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "School Arm Management";

        try {
            return view('arm.index')
                ->with('pagetitle', $pagetitle);
        } catch (\Exception $e) {
            Log::error('School Arm Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading school arms: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $arms = Schoolarm::select('schoolarm.*');

            return DataTables::of($arms)
                ->addIndexColumn()

                // ── Checkbox ──────────────────────────────────────────────────
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                // ── Arm Name with ID ────────────────────────────────────────
                ->addColumn('arm_info', function ($row) {
                    return '<div>
                        <span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->arm ?? '')) . '</span>
                        <small class="text-muted d-block">ID: ' . $row->id . '</small>
                    </div>';
                })

                // ── Description ──────────────────────────────────────────────
                ->addColumn('description_info', function ($row) {
                    if ($row->description) {
                        return '<span class="text-muted">' . e($this->cleanUtf8String($row->description)) . '</span>';
                    }
                    return '<span class="text-muted">—</span>';
                })

                // ── Usage Count ──────────────────────────────────────────────
                ->addColumn('usage_count', function ($row) {
                    $count = DB::table('schoolclass')->where('arm', $row->id)->count();
                    if ($count > 0) {
                        return '<span class="badge bg-info text-white">' . $count . ' Class(es)</span>';
                    }
                    return '<span class="text-muted">—</span>';
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

                // ── Actions ──────────────────────────────────────────────────
                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user()->can('Update school-arm')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-arm-btn" title="Edit" '
                            . 'data-id="%s" data-arm="%s" data-description="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->arm ?? '')),
                            e($this->cleanUtf8String($row->description ?? ''))
                        );
                    }

                    if (auth()->user()->can('Delete school-arm')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-arm-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->arm ?? 'Unknown Arm'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'arm_info', 'description_info', 'usage_count', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('School Arm DataTable error:', [
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
            $totalArms = Schoolarm::count();
            $armsWithClasses = DB::table('schoolarm')
                ->join('schoolclass', 'schoolclass.arm', '=', 'schoolarm.id')
                ->distinct('schoolarm.id')
                ->count('schoolarm.id');
            
            $recentlyUpdated = Schoolarm::where('updated_at', '>=', now()->subDays(30))->count();

            return response()->json([
                'stats' => [
                    'total' => $totalArms,
                    'with_classes' => $armsWithClasses,
                    'recently_updated' => $recentlyUpdated,
                    'showing' => $totalArms,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('School Arm stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'with_classes' => 0, 'recently_updated' => 0, 'showing' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        Log::info('Store School Arm Request:', $request->all());

        $validator = \Validator::make($request->all(), [
            'arm' => 'required|string|max:255|unique:schoolarm,arm',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $arm = Schoolarm::create([
                'arm' => $request->input('arm'),
                'description' => $request->input('description') ?? '',
            ]);

            Log::info('School Arm Created:', $arm->toArray());

            return response()->json([
                'success' => true,
                'message' => 'School arm has been created successfully',
                'data' => $arm
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating school arm:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create school arm: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function updatearm(Request $request)
    {
        Log::info('Update School Arm AJAX Request:', $request->all());

        $validator = \Validator::make($request->all(), [
            'id' => 'required|exists:schoolarm,id',
            'arm' => "required|string|max:255|unique:schoolarm,arm,{$request->id}",
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $arm = Schoolarm::findOrFail($request->id);
            $arm->update([
                'arm' => $request->input('arm'),
                'description' => $request->input('description') ?? '',
            ]);

            Log::info('School Arm Updated via AJAX:', $arm->toArray());

            return response()->json([
                'success' => true,
                'message' => 'School arm has been updated successfully',
                'data' => $arm
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating school arm:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update school arm: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        try {
            $arm = Schoolarm::findOrFail($id);

            // Check if arm is being used
            $inUse = DB::table('schoolclass')->where('arm', $id)->exists();
            
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This arm is being used by one or more school classes and cannot be deleted.'
                ], 422);
            }

            $arm->delete();

            Log::info('School Arm Deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'School arm has been deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting school arm:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school arm: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DELETE ARM (AJAX)
    // =========================================================================

    public function deletearm(Request $request)
    {
        Log::info('Delete School Arm AJAX Request:', $request->all());

        $validator = \Validator::make($request->all(), [
            'armid' => 'required|exists:schoolarm,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $arm = Schoolarm::findOrFail($request->armid);

            // Check if arm is being used
            $inUse = DB::table('schoolclass')->where('arm', $request->armid)->exists();
            
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This arm is being used by one or more school classes and cannot be deleted.'
                ], 422);
            }

            $arm->delete();

            Log::info('School Arm Deleted via AJAX:', ['id' => $request->armid]);

            return response()->json([
                'success' => true,
                'message' => 'School arm has been deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting school arm via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school arm: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

    public function deleteMultiple(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No arms selected.'
                ], 400);
            }

            // Validate that all IDs exist
            $existingIds = Schoolarm::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds = array_diff($ids, $existingIds);
            
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected arms do not exist.'
                ], 400);
            }

            // Check if any are in use
            $inUse = DB::table('schoolclass')
                ->whereIn('arm', $ids)
                ->exists();
                
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some arms are being used by school classes and cannot be deleted.'
                ], 422);
            }

            DB::beginTransaction();
            
            $deleted = Schoolarm::whereIn('id', $ids)->delete();

            DB::commit();

            Log::info('Bulk delete completed', [
                'total' => count($ids),
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' arm(s) deleted successfully.',
                'deleted_count' => $deleted
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk delete failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting arms: ' . $e->getMessage()
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