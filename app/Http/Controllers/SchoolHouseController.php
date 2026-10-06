<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schoolhouse;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SchoolHouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View schoolhouse|Create schoolhouse|Update schoolhouse|Delete schoolhouse', ['only' => ['index']]);
        $this->middleware('permission:Create schoolhouse', ['only' => ['store']]);
        $this->middleware('permission:Update schoolhouse', ['only' => ['update', 'updatehouse']]);
        $this->middleware('permission:Delete schoolhouse', ['only' => ['destroy', 'deletehouse', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "School House Management";

        try {
            $schoolterm = Schoolterm::all();
            $schoolsession = Schoolsession::all();
            $staff = User::whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Student');
            })->get(['users.id as userid', 'users.name as name']);

            return view('schoolhouse.index')
                ->with('schoolterm', $schoolterm)
                ->with('schoolsession', $schoolsession)
                ->with('staff', $staff)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('School House Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading school houses: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $houses = Schoolhouse::leftJoin('users', 'users.id', '=', 'schoolhouses.housemasterid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'schoolhouses.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'schoolhouses.sessionid')
                ->select([
                    'schoolhouses.id as id',
                    'schoolhouses.house',
                    'schoolhouses.housecolour',
                    'users.id as housemasterid',
                    'users.name as housemaster',
                    'schoolterm.id as termid',
                    'schoolterm.term as term',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as session',
                    'schoolhouses.created_at',
                    'schoolhouses.updated_at'
                ]);

            return DataTables::of($houses)
                ->addIndexColumn()

                // ── Checkbox ──────────────────────────────────────────────────
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                // ── House Name with ID ──────────────────────────────────────
                ->addColumn('house_info', function ($row) {
                    return '<div>
                        <span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->house ?? '')) . '</span>
                        <small class="text-muted d-block">ID: ' . $row->id . '</small>
                    </div>';
                })

                // ── House Colour Badge ──────────────────────────────────────
                ->addColumn('colour_info', function ($row) {
                    $colour = $row->housecolour ?? '#cccccc';
                    return '<span class="sh-badge sh-badge-colour" style="background-color: ' . e($colour) . '; color: #fff;">' . e($colour) . '</span>';
                })

                // ── House Master ────────────────────────────────────────────
                ->addColumn('master_info', function ($row) {
                    return '<span class="fw-semibold">' . e($this->cleanUtf8String($row->housemaster ?? 'N/A')) . '</span>';
                })

                // ── Term Badge ──────────────────────────────────────────────
                ->addColumn('term_info', function ($row) {
                    return '<span class="sh-badge sh-badge-term">' . e($this->cleanUtf8String($row->term ?? 'N/A')) . '</span>';
                })

                // ── Session Badge ────────────────────────────────────────────
                ->addColumn('session_info', function ($row) {
                    return '<span class="sh-badge sh-badge-session">' . e($this->cleanUtf8String($row->session ?? 'N/A')) . '</span>';
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

                    if (auth()->user()->can('Update schoolhouse')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-house-btn" title="Edit" '
                            . 'data-id="%s" data-house="%s" data-housecolour="%s" '
                            . 'data-housemasterid="%s" data-termid="%s" data-sessionid="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->house ?? '')),
                            e($row->housecolour ?? ''),
                            $row->housemasterid,
                            $row->termid,
                            $row->sessionid
                        );
                    }

                    if (auth()->user()->can('Delete schoolhouse')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-house-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->house ?? 'Unknown House'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'house_info', 'colour_info', 'master_info', 'term_info', 'session_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('School House DataTable error:', [
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
                    'total' => Schoolhouse::count(),
                    'unique_masters' => Schoolhouse::distinct('housemasterid')->count('housemasterid'),
                    'unique_terms' => Schoolhouse::distinct('termid')->count('termid'),
                    'unique_sessions' => Schoolhouse::distinct('sessionid')->count('sessionid'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('School House stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'unique_masters' => 0, 'unique_terms' => 0, 'unique_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house' => 'required|string|max:255',
            'housecolour' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                }
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid' => 'required|exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $schoolhouse = Schoolhouse::where('house', $request->house)
                ->where('housemasterid', $request->housemasterid)
                ->where('housecolour', $request->housecolour)
                ->where('termid', $request->termid)
                ->where('sessionid', $request->sessionid)
                ->exists();

            if ($schoolhouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record already exists'
                ], 422);
            }

            $house = Schoolhouse::create($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));

            Log::info('School House Created:', $house->toArray());

            return response()->json([
                'success' => true,
                'message' => 'School house created successfully',
                'data' => $house
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating school house:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create school house: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function updatehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:schoolhouses,id',
            'house' => 'required|string|max:255',
            'housecolour' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                }
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid' => 'required|exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $schoolhouse = Schoolhouse::where('house', $request->house)
                ->where('housemasterid', $request->housemasterid)
                ->where('housecolour', $request->housecolour)
                ->where('termid', $request->termid)
                ->where('sessionid', $request->sessionid)
                ->where('id', '!=', $request->id)
                ->exists();

            if ($schoolhouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record already exists'
                ], 422);
            }

            $house = Schoolhouse::findOrFail($request->id);
            $house->update($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));

            Log::info('School House Updated:', $house->toArray());

            return response()->json([
                'success' => true,
                'message' => 'School house updated successfully',
                'data' => $house
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating school house:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update school house: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        try {
            $house = Schoolhouse::findOrFail($id);
            $house->delete();

            Log::info('School House Deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'School house deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting school house:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school house: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DELETE HOUSE (AJAX)
    // =========================================================================

    public function deletehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'houseid' => 'required|exists:schoolhouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $house = Schoolhouse::findOrFail($request->houseid);
            $house->delete();

            Log::info('School House Deleted via AJAX:', ['id' => $request->houseid]);

            return response()->json([
                'success' => true,
                'message' => 'School house deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting school house via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school house: ' . $e->getMessage()
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
                    'message' => 'No houses selected.'
                ], 400);
            }

            // Validate that all IDs exist
            $existingIds = Schoolhouse::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds = array_diff($ids, $existingIds);
            
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected houses do not exist.'
                ], 400);
            }

            DB::beginTransaction();
            
            $deleted = Schoolhouse::whereIn('id', $ids)->delete();

            DB::commit();

            Log::info('Bulk delete completed', [
                'total' => count($ids),
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' house(s) deleted successfully.',
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
                'message' => 'Error deleting houses: ' . $e->getMessage()
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