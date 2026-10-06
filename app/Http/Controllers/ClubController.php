<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Club;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class ClubController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View club|Create club|Update club|Delete club', ['only' => ['index']]);
        $this->middleware('permission:Create club', ['only' => ['store']]);
        $this->middleware('permission:Update club', ['only' => ['update', 'updateclub']]);
        $this->middleware('permission:Delete club', ['only' => ['destroy', 'deleteclub', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Club Management";

        try {
            $schoolterm = Schoolterm::all();
            $schoolsession = Schoolsession::all();
            $staff = User::whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Student');
            })->get(['users.id as userid', 'users.name as name']);

            return view('club.index')
                ->with('schoolterm', $schoolterm)
                ->with('schoolsession', $schoolsession)
                ->with('staff', $staff)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('Club Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading clubs: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $clubs = Club::leftJoin('users', 'users.id', '=', 'clubs.patronid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'clubs.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'clubs.sessionid')
                ->select([
                    'clubs.id as id',
                    'clubs.club',
                    'clubs.description',
                    'users.id as patronid',
                    'users.name as patron',
                    'schoolterm.id as termid',
                    'schoolterm.term as term',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as session',
                    'clubs.created_at',
                    'clubs.updated_at'
                ]);

            return DataTables::of($clubs)
                ->addIndexColumn()

                // ── Checkbox ──────────────────────────────────────────────────
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                // ── Club Name with ID ──────────────────────────────────────
                ->addColumn('club_info', function ($row) {
                    return '<div>
                        <span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->club ?? '')) . '</span>
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

                // ── Patron ──────────────────────────────────────────────────
                ->addColumn('patron_info', function ($row) {
                    return '<span class="fw-semibold">' . e($this->cleanUtf8String($row->patron ?? 'N/A')) . '</span>';
                })

                // ── Term Badge ──────────────────────────────────────────────
                ->addColumn('term_info', function ($row) {
                    return '<span class="club-badge club-badge-term">' . e($this->cleanUtf8String($row->term ?? 'N/A')) . '</span>';
                })

                // ── Session Badge ────────────────────────────────────────────
                ->addColumn('session_info', function ($row) {
                    return '<span class="club-badge club-badge-session">' . e($this->cleanUtf8String($row->session ?? 'N/A')) . '</span>';
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

                    if (auth()->user()->can('Update club')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-club-btn" title="Edit" '
                            . 'data-id="%s" data-club="%s" data-description="%s" '
                            . 'data-patronid="%s" data-termid="%s" data-sessionid="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->club ?? '')),
                            e($this->cleanUtf8String($row->description ?? '')),
                            $row->patronid,
                            $row->termid,
                            $row->sessionid
                        );
                    }

                    if (auth()->user()->can('Delete club')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-club-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->club ?? 'Unknown Club'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'club_info', 'description_info', 'patron_info', 'term_info', 'session_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('Club DataTable error:', [
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
                    'total' => Club::count(),
                    'unique_patrons' => Club::distinct('patronid')->count('patronid'),
                    'unique_terms' => Club::distinct('termid')->count('termid'),
                    'unique_sessions' => Club::distinct('sessionid')->count('sessionid'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Club stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'unique_patrons' => 0, 'unique_terms' => 0, 'unique_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club' => 'required|string|max:255|unique:clubs,club',
            'description' => 'nullable|string',
            'patronid' => 'required|exists:users,id',
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
            $club = Club::create($request->only(['club', 'description', 'patronid', 'termid', 'sessionid']));

            Log::info('Club Created:', $club->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Club created successfully',
                'data' => $club
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating club:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create club: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function updateclub(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:clubs,id',
            'club' => "required|string|max:255|unique:clubs,club,{$request->id}",
            'description' => 'nullable|string',
            'patronid' => 'required|exists:users,id',
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
            $club = Club::findOrFail($request->id);
            $club->update($request->only(['club', 'description', 'patronid', 'termid', 'sessionid']));

            Log::info('Club Updated:', $club->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Club updated successfully',
                'data' => $club
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating club:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update club: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        try {
            $club = Club::findOrFail($id);
            $club->delete();

            Log::info('Club Deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Club deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting club:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete club: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DELETE CLUB (AJAX)
    // =========================================================================

    public function deleteclub(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clubid' => 'required|exists:clubs,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $club = Club::findOrFail($request->clubid);
            $club->delete();

            Log::info('Club Deleted via AJAX:', ['id' => $request->clubid]);

            return response()->json([
                'success' => true,
                'message' => 'Club deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting club via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete club: ' . $e->getMessage()
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
                    'message' => 'No clubs selected.'
                ], 400);
            }

            $existingIds = Club::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds = array_diff($ids, $existingIds);
            
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected clubs do not exist.'
                ], 400);
            }

            DB::beginTransaction();
            $deleted = Club::whereIn('id', $ids)->delete();
            DB::commit();

            Log::info('Bulk delete completed', [
                'total' => count($ids),
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' club(s) deleted successfully.',
                'deleted_count' => $deleted
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting clubs: ' . $e->getMessage()
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