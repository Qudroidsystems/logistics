<?php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subjectclass;
use App\Models\SubjectVetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SubjectVettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View subject-vettings|Create subject-vettings|Update subject-vettings|Delete subject-vettings', ['only' => ['index', 'data', 'stats', 'searchSubjectClasses', 'getSelectedSubjectClasses']]);
        $this->middleware('permission:Create subject-vettings', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update subject-vettings', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete subject-vettings', ['only' => ['destroy', 'bulkDelete']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Subject Vetting Management";

        try {
            $currentSession = Schoolsession::where('status', 'Current')->first()
                ?? Schoolsession::latest()->first();

            $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->get([
                    'schoolclass.id as id',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as arm_name',
                ])
                ->map(function ($c) {
                    $c->label = trim($c->schoolclass . ($c->arm_name ? ' (' . $c->arm_name . ')' : ''));
                    return $c;
                })
                ->sortBy('schoolclass');

            $staff    = User::whereHas('roles', fn($q) => $q->where('name', '!=', 'Student'))
                ->orderBy('name')->get(['users.id', 'users.name', 'users.avatar']);

            $terms    = Schoolterm::orderBy('term')->get(['id', 'term']);
            $sessions = Schoolsession::orderByDesc('session')->get(['id', 'session', 'status']);

            return view('subjectvetting.index')
                ->with(compact('schoolclasses', 'staff', 'terms', 'sessions', 'pagetitle', 'currentSession'));

        } catch (\Exception $e) {
            Log::error('SubjectVetting Index Error', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading subject vetting: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $query = SubjectVetting::query()
                ->leftJoin('subjectclass', 'subject_vettings.subjectclassid', '=', 'subjectclass.id')
                ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users as vetting_user', 'subject_vettings.userid', '=', 'vetting_user.id')
                ->leftJoin('users as teacher_user', 'subjectteacher.staffid', '=', 'teacher_user.id')
                ->leftJoin('schoolterm', 'subject_vettings.termid', '=', 'schoolterm.id')
                ->leftJoin('schoolsession', 'subject_vettings.sessionid', '=', 'schoolsession.id')
                ->select([
                    'subject_vettings.id as svid',
                    'subject_vettings.userid as vetting_userid',
                    'vetting_user.name as vetting_username',
                    'vetting_user.avatar as vetting_picture',
                    'subjectclass.id as subjectclassid',
                    'schoolclass.id as schoolclassid',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as arm_name',
                    'subjectteacher.staffid as subtid',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'teacher_user.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'subject_vettings.status',
                    'subject_vettings.updated_at',
                ]);

            // Optional server-side filters (passed via DataTable ajax data)
            if ($request->filled('filter_term') && $request->input('filter_term') !== '') {
                $query->where('subject_vettings.termid', $request->input('filter_term'));
            }
            if ($request->filled('filter_session') && $request->input('filter_session') !== '') {
                $query->where('subject_vettings.sessionid', $request->input('filter_session'));
            }
            if ($request->filled('filter_status') && $request->input('filter_status') !== '') {
                $query->where('subject_vettings.status', $request->input('filter_status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->filterColumn('vetting_username', function ($query, $keyword) {
                    $query->where('vetting_user.name', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('subjectname', function ($query, $keyword) {
                    $query->where('subject.subject', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('subjectcode', function ($query, $keyword) {
                    $query->where('subject.subject_code', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('sclass', function ($query, $keyword) {
                    $query->where('schoolclass.schoolclass', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('arm_name', function ($query, $keyword) {
                    $query->where('schoolarm.arm', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('teachername', function ($query, $keyword) {
                    $query->where('teacher_user.name', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('termname', function ($query, $keyword) {
                    $query->where('schoolterm.term', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('sessionname', function ($query, $keyword) {
                    $query->where('schoolsession.session', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('formatted_date', function ($query, $keyword) {
                    $query->whereRaw("DATE(subject_vettings.updated_at) LIKE ?", ["%{$keyword}%"]);
                })

                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->svid . '">';
                })

                ->addColumn('vetting_info', function ($row) {
                    $name = $this->cleanUtf8String($row->vetting_username ?? 'Unknown');
                    $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                    $defaultUrl = asset('storage/staff_avatars/unnamed.jpg');

                    $avatarUrl = null;
                    if (!empty($row->vetting_picture) && !in_array(trim($row->vetting_picture), ['unnamed.jpg', 'unnamed.png', ''], true)) {
                        if (\Storage::exists('public/staff_avatars/' . $row->vetting_picture)) {
                            $avatarUrl = asset('storage/staff_avatars/' . $row->vetting_picture);
                        }
                    }

                    $avatarHtml = $avatarUrl
                        ? '<img src="' . e($avatarUrl) . '" alt="' . e($name) . '" class="sv-avatar" onerror="this.onerror=null;this.src=\'' . e($defaultUrl) . '\'">'
                        : '<div class="sv-avatar-initials">' . e($initial) . '</div>';

                    return '<div class="d-flex align-items-center gap-2">'
                        . $avatarHtml
                        . '<span class="fw-semibold">' . e($name) . '</span>'
                        . '</div>';
                })

                ->addColumn('subject_info', function ($row) {
                    return '<div>'
                        . '<span class="fw-semibold">' . e($this->cleanUtf8String($row->subjectname ?? '')) . '</span>'
                        . '<br><small class="text-muted">' . e($row->subjectcode ?? 'N/A') . '</small>'
                        . '</div>';
                })

                ->addColumn('class_info', function ($row) {
                    return '<span class="sv-badge sv-badge-class">' . e($this->cleanUtf8String($row->sclass ?? '—')) . '</span>';
                })

                ->addColumn('arm_info', function ($row) {
                    return $row->arm_name
                        ? e($this->cleanUtf8String($row->arm_name))
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('teacher_info', function ($row) {
                    return $row->teachername
                        ? '<span class="fw-medium">' . e($this->cleanUtf8String($row->teachername)) . '</span>'
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('term_info', function ($row) {
                    if (empty($row->termname)) return '<span class="text-muted">—</span>';
                    $cls = match(true) {
                        str_contains($row->termname, 'First')  => 'sv-badge-term-first',
                        str_contains($row->termname, 'Second') => 'sv-badge-term-second',
                        str_contains($row->termname, 'Third')  => 'sv-badge-term-third',
                        default => 'sv-badge-term-other'
                    };
                    return '<span class="sv-badge ' . $cls . '">' . e($row->termname) . '</span>';
                })

                ->addColumn('session_info', function ($row) {
                    if (empty($row->sessionname)) return '<span class="text-muted">—</span>';
                    return '<span class="sv-badge sv-badge-session">' . e($row->sessionname) . '</span>';
                })

                ->addColumn('status_info', function ($row) {
                    $cls = match ($row->status ?? 'pending') {
                        'completed' => 'sv-badge-status-completed',
                        'rejected'  => 'sv-badge-status-rejected',
                        default     => 'sv-badge-status-pending',
                    };
                    return '<span class="sv-badge ' . $cls . '">' . e(ucfirst($row->status ?? 'pending')) . '</span>';
                })

                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) return '<span class="text-muted small">—</span>';
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user() && auth()->user()->can('Update subject-vettings')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-sv-btn" title="Edit" '
                            . 'data-id="%s" data-vetting-userid="%s" data-sessionid="%s" data-termid="%s" '
                            . 'data-subjectclassid="%s" data-subjectname="%s" data-sclass="%s" '
                            . 'data-arm="%s" data-teachername="%s" data-termname="%s" '
                            . 'data-sessionname="%s" data-status="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->svid,
                            $row->vetting_userid,
                            $row->sessionid,
                            $row->termid,
                            $row->subjectclassid,
                            e($this->cleanUtf8String($row->subjectname ?? '')),
                            e($this->cleanUtf8String($row->sclass ?? '')),
                            e($this->cleanUtf8String($row->arm_name ?? '')),
                            e($this->cleanUtf8String($row->teachername ?? '')),
                            e($row->termname ?? ''),
                            e($row->sessionname ?? ''),
                            e($row->status ?? 'pending')
                        );
                    }

                    if (auth()->user() && auth()->user()->can('Delete subject-vettings')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-sv-btn" title="Delete" '
                            . 'data-id="%s"><i class="ph-trash"></i></button>',
                            $row->svid
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'vetting_info', 'subject_info', 'class_info', 'arm_info', 'teacher_info', 'term_info', 'session_info', 'status_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('SubjectVetting DataTable error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // STATS  (supports optional filter_term / filter_session)
    // =========================================================================

    public function stats(Request $request)
    {
        try {
            $base = SubjectVetting::query();

            if ($request->filled('filter_term') && $request->input('filter_term') !== '') {
                $base->where('termid', $request->input('filter_term'));
            }
            if ($request->filled('filter_session') && $request->input('filter_session') !== '') {
                $base->where('sessionid', $request->input('filter_session'));
            }

            $total     = (clone $base)->count();
            $pending   = (clone $base)->where('status', 'pending')->count();
            $completed = (clone $base)->where('status', 'completed')->count();
            $rejected  = (clone $base)->where('status', 'rejected')->count();

            return response()->json([
                'stats' => [
                    'total'     => $total,
                    'pending'   => $pending,
                    'completed' => $completed,
                    'rejected'  => $rejected,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('SubjectVetting stats error', ['error' => $e->getMessage()]);
            return response()->json([
                'stats' => ['total' => 0, 'pending' => 0, 'completed' => 0, 'rejected' => 0],
            ]);
        }
    }

    // =========================================================================
    // AJAX SUBJECT SEARCH (used by modals)
    // =========================================================================

    public function searchSubjectClasses(Request $request)
    {
        try {
            $query      = $request->get('q', '');
            $excludeIds = $request->get('exclude_ids', '');
            $termIds    = $request->get('term_ids', '');

            if (strlen($query) < 2) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $subjectClasses = Subjectclass::select(
                    'subjectclass.id',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as schoolarm',
                    'users.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'schoolsession.status as sessionstatus'
                )
                ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->where(function ($q) use ($query) {
                    $q->where('subject.subject', 'LIKE', "%{$query}%")
                      ->orWhere('subject.subject_code', 'LIKE', "%{$query}%")
                      ->orWhere('schoolclass.schoolclass', 'LIKE', "%{$query}%")
                      ->orWhere('schoolarm.arm', 'LIKE', "%{$query}%")
                      ->orWhere('users.name', 'LIKE', "%{$query}%")
                      ->orWhere('schoolsession.session', 'LIKE', "%{$query}%")
                      ->orWhere('schoolterm.term', 'LIKE', "%{$query}%");
                })
                ->when(!empty($excludeIds), function ($q) use ($excludeIds) {
                    if (is_array($excludeIds)) {
                        $q->whereNotIn('subjectclass.id', $excludeIds);
                    } elseif (str_contains($excludeIds, ',')) {
                        $q->whereNotIn('subjectclass.id', explode(',', $excludeIds));
                    } else {
                        $q->where('subjectclass.id', '!=', $excludeIds);
                    }
                })
                ->when(!empty($termIds), function ($q) use ($termIds) {
                    $ids = is_array($termIds) ? $termIds : explode(',', $termIds);
                    $q->whereIn('schoolterm.id', $ids);
                })
                ->orderBy('schoolterm.id')
                ->orderBy('subject.subject')
                ->limit(30)
                ->get();

            return response()->json(['success' => true, 'data' => $subjectClasses]);

        } catch (\Exception $e) {
            Log::error('Error searching subject classes: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Search failed.'], 500);
        }
    }

    public function getSelectedSubjectClasses(Request $request)
    {
        try {
            $ids = $request->get('ids', []);
            if (empty($ids)) return response()->json([]);

            $idsArray = is_array($ids) ? $ids : explode(',', $ids);

            return response()->json(
                Subjectclass::select(
                        'subjectclass.id',
                        'subject.subject as subjectname',
                        'subject.subject_code as subjectcode',
                        'schoolclass.schoolclass as sclass',
                        'schoolarm.arm as schoolarm',
                        'users.name as teachername',
                        'schoolterm.id as termid',
                        'schoolterm.term as termname',
                        'schoolsession.id as sessionid',
                        'schoolsession.session as sessionname'
                    )
                    ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                    ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                    ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                    ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                    ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                    ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                    ->whereIn('subjectclass.id', $idsArray)
                    ->get()
            );
        } catch (\Exception $e) {
            Log::error('Error getting selected subject classes: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'userid'           => 'required|exists:users,id',
                'termid'           => 'required|array|min:1',
                'termid.*'         => 'exists:schoolterm,id',
                'sessionid'        => 'required|exists:schoolsession,id',
                'subjectclassid'   => 'required|array|min:1',
                'subjectclassid.*' => 'exists:subjectclass,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }

            $userId          = $request->input('userid');
            $termIds         = $request->input('termid', []);
            $sessionId       = $request->input('sessionid');
            $subjectClassIds = array_unique($request->input('subjectclassid', []));

            // Staff cannot vet their own subject-class
            $subjectClassTeachers = Subjectclass::whereIn('subjectclass.id', $subjectClassIds)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->pluck('subjectteacher.staffid')
                ->toArray();

            if (in_array($userId, $subjectClassTeachers)) {
                return response()->json(['success' => false, 'message' => 'The selected staff member cannot vet their own subject-class assignment.'], 422);
            }

            // Prevent duplicate vetting assignment for same subject-class/term/session
            $existing = SubjectVetting::whereIn('subjectclassid', $subjectClassIds)
                ->whereIn('termid', $termIds)
                ->where('sessionid', $sessionId)
                ->pluck('subjectclassid')
                ->toArray();

            if (!empty($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected subject-classes are already assigned for vetting in the selected term/session.',
                ], 422);
            }

            DB::beginTransaction();
            $created = [];
            foreach ($termIds as $termId) {
                foreach ($subjectClassIds as $subjectClassId) {
                    $created[] = SubjectVetting::create([
                        'userid'         => $userId,
                        'subjectclassid' => $subjectClassId,
                        'termid'         => $termId,
                        'sessionid'      => $sessionId,
                        'status'         => 'pending',
                    ]);
                }
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' Subject Vetting assignment(s) added successfully.',
                'data'    => $created,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing subject vetting: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'userid'         => 'required|exists:users,id',
                'subjectclassid' => 'required|exists:subjectclass,id',
                'termid'         => 'required|exists:schoolterm,id',
                'sessionid'      => 'required|exists:schoolsession,id',
                'status'         => 'required|in:pending,completed,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }

            $userId         = $request->input('userid');
            $subjectClassId = $request->input('subjectclassid');
            $termId         = $request->input('termid');
            $sessionId      = $request->input('sessionid');
            $status         = $request->input('status');

            $subjectClass = Subjectclass::where('subjectclass.id', $subjectClassId)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->first(['subjectteacher.staffid']);

            if ($subjectClass && $subjectClass->staffid == $userId) {
                return response()->json(['success' => false, 'message' => 'The selected staff member cannot vet their own subject-class assignment.'], 422);
            }

            $existing = SubjectVetting::where('subjectclassid', $subjectClassId)
                ->where('termid', $termId)
                ->where('sessionid', $sessionId)
                ->where('id', '!=', $id)
                ->exists();

            if ($existing) {
                return response()->json(['success' => false, 'message' => 'This subject-class is already assigned for vetting in the selected term/session.'], 422);
            }

            $vetting = SubjectVetting::find($id);
            if (!$vetting) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }

            $vetting->update([
                'userid'         => $userId,
                'subjectclassid' => $subjectClassId,
                'termid'         => $termId,
                'sessionid'      => $sessionId,
                'status'         => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subject Vetting assignment updated successfully.',
                'data'    => $vetting,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating subject vetting: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DESTROY / BULK
    // =========================================================================

    public function destroy($id)
    {
        try {
            $vetting = SubjectVetting::find($id);
            if (!$vetting) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }

            DB::transaction(function () use ($vetting) {
                Broadsheets::where('vettedby', $vetting->userid)
                    ->where('subjectclass_id', $vetting->subjectclassid)
                    ->where('term_id', $vetting->termid)
                    ->update(['vettedby' => null, 'vettedstatus' => null]);

                $vetting->delete();
            });

            return response()->json(['success' => true, 'message' => 'Subject Vetting assignment deleted successfully.'], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting subject vetting: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No records selected.'], 422);
            }

            DB::transaction(function () use ($ids) {
                $vettings = SubjectVetting::whereIn('id', $ids)->get();
                foreach ($vettings as $v) {
                    Broadsheets::where('vettedby', $v->userid)
                        ->where('subjectclass_id', $v->subjectclassid)
                        ->where('term_id', $v->termid)
                        ->update(['vettedby' => null, 'vettedstatus' => null]);
                }
                SubjectVetting::whereIn('id', $ids)->delete();
            });

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' record(s) deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in bulk delete: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function cleanUtf8String($string)
    {
        if (empty($string)) return '';
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}