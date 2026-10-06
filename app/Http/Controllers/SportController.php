<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sport;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View sport|Create sport|Update sport|Delete sport', ['only' => ['index']]);
        $this->middleware('permission:Create sport', ['only' => ['store']]);
        $this->middleware('permission:Update sport', ['only' => ['update', 'updatesport']]);
        $this->middleware('permission:Delete sport', ['only' => ['destroy', 'deletesport', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Sport Management";

        try {
            $schoolterm = Schoolterm::all();
            $schoolsession = Schoolsession::all();
            $staff = User::whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Student');
            })->get(['users.id as userid', 'users.name as name']);

            return view('sport.index')
                ->with('schoolterm', $schoolterm)
                ->with('schoolsession', $schoolsession)
                ->with('staff', $staff)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('Sport Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading sports: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $sports = Sport::leftJoin('users', 'users.id', '=', 'sports.coachid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'sports.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'sports.sessionid')
                ->select([
                    'sports.id as id',
                    'sports.sport',
                    'sports.description',
                    'users.id as coachid',
                    'users.name as coach',
                    'schoolterm.id as termid',
                    'schoolterm.term as term',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as session',
                    'sports.created_at',
                    'sports.updated_at'
                ]);

            return DataTables::of($sports)
                ->addIndexColumn()

                // ── Checkbox ──────────────────────────────────────────────────
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                // ── Sport Name with ID ──────────────────────────────────────
                ->addColumn('sport_info', function ($row) {
                    return '<div>
                        <span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->sport ?? '')) . '</span>
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

                // ── Coach ────────────────────────────────────────────────────
                ->addColumn('coach_info', function ($row) {
                    return '<span class="fw-semibold">' . e($this->cleanUtf8String($row->coach ?? 'N/A')) . '</span>';
                })

                // ── Term Badge ──────────────────────────────────────────────
                ->addColumn('term_info', function ($row) {
                    return '<span class="sport-badge sport-badge-term">' . e($this->cleanUtf8String($row->term ?? 'N/A')) . '</span>';
                })

                // ── Session Badge ────────────────────────────────────────────
                ->addColumn('session_info', function ($row) {
                    return '<span class="sport-badge sport-badge-session">' . e($this->cleanUtf8String($row->session ?? 'N/A')) . '</span>';
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

                    if (auth()->user()->can('Update sport')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-sport-btn" title="Edit" '
                            . 'data-id="%s" data-sport="%s" data-description="%s" '
                            . 'data-coachid="%s" data-termid="%s" data-sessionid="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->sport ?? '')),
                            e($this->cleanUtf8String($row->description ?? '')),
                            $row->coachid,
                            $row->termid,
                            $row->sessionid
                        );
                    }

                    if (auth()->user()->can('Delete sport')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-sport-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->sport ?? 'Unknown Sport'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'sport_info', 'description_info', 'coach_info', 'term_info', 'session_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('Sport DataTable error:', [
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
                    'total' => Sport::count(),
                    'unique_coaches' => Sport::distinct('coachid')->count('coachid'),
                    'unique_terms' => Sport::distinct('termid')->count('termid'),
                    'unique_sessions' => Sport::distinct('sessionid')->count('sessionid'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Sport stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'unique_coaches' => 0, 'unique_terms' => 0, 'unique_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sport' => 'required|string|max:255|unique:sports,sport',
            'description' => 'nullable|string',
            'coachid' => 'required|exists:users,id',
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
            $sport = Sport::create($request->only(['sport', 'description', 'coachid', 'termid', 'sessionid']));

            Log::info('Sport Created:', $sport->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Sport created successfully',
                'data' => $sport
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating sport:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sport: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function updatesport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sports,id',
            'sport' => "required|string|max:255|unique:sports,sport,{$request->id}",
            'description' => 'nullable|string',
            'coachid' => 'required|exists:users,id',
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
            $sport = Sport::findOrFail($request->id);
            $sport->update($request->only(['sport', 'description', 'coachid', 'termid', 'sessionid']));

            Log::info('Sport Updated:', $sport->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Sport updated successfully',
                'data' => $sport
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating sport:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sport: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        try {
            $sport = Sport::findOrFail($id);
            $sport->delete();

            Log::info('Sport Deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Sport deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting sport:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sport: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DELETE SPORT (AJAX)
    // =========================================================================

    public function deletesport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sportid' => 'required|exists:sports,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $sport = Sport::findOrFail($request->sportid);
            $sport->delete();

            Log::info('Sport Deleted via AJAX:', ['id' => $request->sportid]);

            return response()->json([
                'success' => true,
                'message' => 'Sport deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting sport via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sport: ' . $e->getMessage()
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
                    'message' => 'No sports selected.'
                ], 400);
            }

            $existingIds = Sport::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds = array_diff($ids, $existingIds);
            
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected sports do not exist.'
                ], 400);
            }

            DB::beginTransaction();
            $deleted = Sport::whereIn('id', $ids)->delete();
            DB::commit();

            Log::info('Bulk delete completed', [
                'total' => count($ids),
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' sport(s) deleted successfully.',
                'deleted_count' => $deleted
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting sports: ' . $e->getMessage()
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