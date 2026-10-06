<?php

namespace App\Http\Controllers;

use App\Models\ClassTeacher;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ClassTeacherController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View class-teacher|Create class-teacher|Update class-teacher|Delete class-teacher', ['only' => ['index', 'data']]);
        $this->middleware('permission:Create class-teacher', ['only' => ['store']]);
        $this->middleware('permission:Update class-teacher', ['only' => ['update']]);
        $this->middleware('permission:Delete class-teacher', ['only' => ['destroy', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Class Teacher Management";

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id as id', 'schoolarm.arm as schoolarm', 'schoolclass.schoolclass as schoolclass'])
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $subjectteachers = User::whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Student');
        })->get(['users.id as userid', 'users.name as name', 'users.avatar as avatar']);

        $schoolterms    = Schoolterm::all();
        $schoolsessions = Schoolsession::all();

        return view('classteacher.index')
            ->with('schoolclass',     $schoolclass)
            ->with('subjectteachers', $subjectteachers)
            ->with('schoolterms',     $schoolterms)
            ->with('schoolsessions',  $schoolsessions)
            ->with('pagetitle',       $pagetitle);
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        $assignments = ClassTeacher::leftJoin('users',        'users.id',        '=', 'classteacher.staffid')
            ->leftJoin('schoolclass',   'schoolclass.id',   '=', 'classteacher.schoolclassid')
            ->leftJoin('schoolarm',     'schoolarm.id',     '=', 'schoolclass.arm')
            ->leftJoin('schoolterm',    'schoolterm.id',    '=', 'classteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'classteacher.sessionid')
            ->select([
                'classteacher.id         as id',
                'users.id                as staffid',
                'users.name              as staffname',
                'users.avatar            as avatar',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm           as schoolarm',
                'schoolterm.id           as termid',
                'schoolterm.term         as term',
                'schoolsession.id        as sessionid',
                'schoolsession.session   as session',
                'classteacher.updated_at as updated_at',
            ]);

        return DataTables::of($assignments)
            ->addIndexColumn()

            // ── Teacher cell with avatar / initials ───────────────────────
            ->addColumn('teacher_info', function ($row) {
                $staffname = $this->cleanUtf8String($row->staffname ?? 'Unknown');

                // ── Avatar resolution ─────────────────────────────────────
                // Always produce an absolute asset() URL. The zoom modal reads
                // data-image directly, so relative paths from Storage::url()
                // can silently break when APP_URL differs from the request host.
                $defaultUrl = asset('storage/staff_avatars/unnamed.jpg');
                $avatarUrl  = $defaultUrl;   // safe fallback for the modal
                $hasImage   = false;

                $avatar    = trim($row->avatar ?? '');
                $isDefault = in_array($avatar, ['unnamed.jpg', 'unnamed.png', ''], true);

                if (!$isDefault && $avatar !== '') {
                    // Pass 1 — Storage facade (handles configured disks / symlinks)
                    $storageCandidates = [
                        'public/staff_avatars/'      . $avatar => asset('storage/staff_avatars/'      . $avatar),
                        'public/images/staffavatar/' . $avatar => asset('storage/images/staffavatar/' . $avatar),
                        'public/staffavatar/'        . $avatar => asset('storage/staffavatar/'        . $avatar),
                    ];
                    foreach ($storageCandidates as $storagePath => $assetUrl) {
                        if (Storage::exists($storagePath)) {
                            $avatarUrl = $assetUrl;
                            $hasImage  = true;
                            break;
                        }
                    }

                    // Pass 2 — direct disk check when Storage misses (no symlink yet)
                    if (!$hasImage) {
                        $diskCandidates = [
                            public_path('storage/staff_avatars/'      . $avatar) => asset('storage/staff_avatars/'      . $avatar),
                            public_path('storage/images/staffavatar/' . $avatar) => asset('storage/images/staffavatar/' . $avatar),
                        ];
                        foreach ($diskCandidates as $diskPath => $assetUrl) {
                            if (file_exists($diskPath)) {
                                $avatarUrl = $assetUrl;
                                $hasImage  = true;
                                break;
                            }
                        }
                    }
                }

                // data-* attributes consumed by the blade JS zoom handler
                $dataAttrs = sprintf(
                    'data-staffname="%s" data-image="%s" data-has-image="%s"',
                    e($staffname),
                    e($avatarUrl),      // always an absolute URL
                    $hasImage ? 'true' : 'false'
                );

                if ($hasImage) {
                    $avatarHtml = sprintf(
                        '<img src="%s" alt="%s" class="teacher-avatar ct-avatar-trigger" %s '
                        . 'onerror="this.onerror=null;this.src=\'%s\'">',
                        e($avatarUrl),
                        e($staffname),
                        $dataAttrs,
                        e($defaultUrl)
                    );
                } else {
                    // Initials bubble — first letter of each of the first two words
                    $words    = preg_split('/\s+/', trim($staffname));
                    $initials = implode('', array_map(
                        fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'),
                        array_slice($words, 0, 2)
                    ));
                    $avatarHtml = sprintf(
                        '<div class="avatar-initials ct-avatar-trigger" %s>%s</div>',
                        $dataAttrs,
                        e($initials)
                    );
                }

                return '<div class="d-flex align-items-center gap-2">'
                    . $avatarHtml
                    . '<span class="fw-semibold text-dark">' . e($staffname) . '</span>'
                    . '</div>';
            })

            // ── Class badge ───────────────────────────────────────────────
            ->addColumn('class_info', function ($row) {
                $text = trim(
                    $this->cleanUtf8String($row->schoolclass ?? '')
                    . ' '
                    . $this->cleanUtf8String($row->schoolarm ?? '')
                );
                return '<span class="ct-badge ct-badge-class">' . e($text) . '</span>';
            })

            // ── Term badge ────────────────────────────────────────────────
            ->addColumn('term', function ($row) {
                return '<span class="ct-badge ct-badge-term">'
                    . e($this->cleanUtf8String($row->term ?? ''))
                    . '</span>';
            })

            // ── Session badge ─────────────────────────────────────────────
            ->addColumn('session', function ($row) {
                return '<span class="ct-badge ct-badge-session">'
                    . e($this->cleanUtf8String($row->session ?? ''))
                    . '</span>';
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
                $title = e(trim(
                    $this->cleanUtf8String($row->staffname   ?? '') . ' — '
                    . $this->cleanUtf8String($row->schoolclass ?? '') . ' '
                    . $this->cleanUtf8String($row->schoolarm   ?? '')
                ));

                $buttons = '<div class="d-flex gap-1">';

                if (auth()->user()->can('Update class-teacher')) {
                    $buttons .= sprintf(
                        '<button class="btn btn-sm btn-outline-secondary edit-assignment" title="Edit" '
                        . 'data-id="%s" data-staffid="%s" data-termid="%s" data-sessionid="%s">'
                        . '<i class="ph-pencil"></i></button>',
                        $row->id, $row->staffid, $row->termid, $row->sessionid
                    );
                }

                if (auth()->user()->can('Delete class-teacher')) {
                    $buttons .= sprintf(
                        '<button class="btn btn-sm btn-outline-danger delete-assignment" title="Delete" '
                        . 'data-id="%s" data-title="%s"><i class="ph-trash"></i></button>',
                        $row->id, $title
                    );
                }

                return $buttons . '</div>';
            })

            ->rawColumns(['teacher_info', 'class_info', 'term', 'session', 'formatted_date', 'action'])
            ->make(true);
    }

    // =========================================================================
    // STATS
    // =========================================================================

    public function stats()
    {
        try {
            return response()->json([
                'stats' => [
                    'total'           => ClassTeacher::count(),
                    'unique_teachers' => ClassTeacher::distinct('staffid')->count('staffid'),
                    'unique_classes'  => ClassTeacher::distinct('schoolclassid')->count('schoolclassid'),
                    'active_sessions' => ClassTeacher::distinct('sessionid')->count('sessionid'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ClassTeacher stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'unique_teachers' => 0, 'unique_classes' => 0, 'active_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staffid'         => 'required|exists:users,id',
            'schoolclassid'   => 'required|array|min:1',
            'schoolclassid.*' => 'exists:schoolclass,id',
            'termid'          => 'required|exists:schoolterm,id',
            'sessionid'       => 'required|exists:schoolsession,id',
        ], [
            'staffid.required'       => 'Please select a teacher.',
            'staffid.exists'         => 'Selected teacher does not exist.',
            'schoolclassid.required' => 'Please select at least one class.',
            'schoolclassid.min'      => 'Please select at least one class.',
            'schoolclassid.*.exists' => 'One or more selected classes do not exist.',
            'termid.required'        => 'Please select a term.',
            'termid.exists'          => 'Selected term does not exist.',
            'sessionid.required'     => 'Please select a session.',
            'sessionid.exists'       => 'Selected session does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $createdRecords = [];
        $duplicateNames = [];
        $conflictNames  = [];

        foreach ($request->input('schoolclassid') as $classId) {

            // Same teacher already assigned to this class/term/session
            $dup = ClassTeacher::where('staffid',      $request->input('staffid'))
                ->where('schoolclassid', $classId)
                ->where('termid',        $request->input('termid'))
                ->where('sessionid',     $request->input('sessionid'))
                ->exists();

            if ($dup) {
                $sc = Schoolclass::find($classId);
                $duplicateNames[] = $sc ? $this->cleanUtf8String($sc->schoolclass) : $classId;
                continue;
            }

            // A different teacher already has this class/term/session
            $conflict = ClassTeacher::where('schoolclassid', $classId)
                ->where('termid',    $request->input('termid'))
                ->where('sessionid', $request->input('sessionid'))
                ->where('staffid',   '!=', $request->input('staffid'))
                ->exists();

            if ($conflict) {
                $sc = Schoolclass::find($classId);
                $conflictNames[] = $sc ? $this->cleanUtf8String($sc->schoolclass) : $classId;
                continue;
            }

            $createdRecords[] = ClassTeacher::create([
                'staffid'       => $request->input('staffid'),
                'schoolclassid' => $classId,
                'termid'        => $request->input('termid'),
                'sessionid'     => $request->input('sessionid'),
            ]);
        }

        if (empty($createdRecords)) {
            $msg = 'No assignments created.';
            if (!empty($duplicateNames)) {
                $msg = 'Already assigned: ' . implode(', ', $duplicateNames) . '.';
            } elseif (!empty($conflictNames)) {
                $msg = 'These classes already have another teacher: ' . implode(', ', $conflictNames) . '.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        $msg = count($createdRecords) . ' class teacher assignment(s) added successfully.';
        if (!empty($duplicateNames) || !empty($conflictNames)) {
            $skipped = array_merge($duplicateNames, $conflictNames);
            $msg    .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }

        Log::info('ClassTeacher store', ['created' => count($createdRecords)]);
        return response()->json(['success' => true, 'message' => $msg, 'data' => $createdRecords], 201);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'staffid'         => 'required|exists:users,id',
            'schoolclassid'   => 'required|array|min:1',
            'schoolclassid.*' => 'exists:schoolclass,id',
            'termid'          => 'required|exists:schoolterm,id',
            'sessionid'       => 'required|exists:schoolsession,id',
        ], [
            'staffid.required'       => 'Please select a teacher.',
            'staffid.exists'         => 'Selected teacher does not exist.',
            'schoolclassid.required' => 'Please select at least one class.',
            'schoolclassid.min'      => 'Please select at least one class.',
            'schoolclassid.*.exists' => 'One or more selected classes do not exist.',
            'termid.required'        => 'Please select a term.',
            'termid.exists'          => 'Selected term does not exist.',
            'sessionid.required'     => 'Please select a session.',
            'sessionid.exists'       => 'Selected session does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $primary = ClassTeacher::find($id);
        if (!$primary) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        // ── Collect IDs for the ENTIRE original group ─────────────────────
        // One "edit" covers all rows that share the same original teacher +
        // original term + original session.  We must exclude every one of
        // these rows from the conflict check because they are about to be
        // deleted and replaced — they are NOT "another teacher's assignment".
        $originalGroupIds = ClassTeacher::where('staffid',   $primary->staffid)
            ->where('termid',    $primary->termid)
            ->where('sessionid', $primary->sessionid)
            ->pluck('id')
            ->toArray();

        // ── Conflict check — genuinely different teacher/group only ────────
        $conflictNames = [];
        foreach ($request->input('schoolclassid') as $classId) {
            $conflict = ClassTeacher::where('schoolclassid', $classId)
                ->where('termid',    $request->input('termid'))
                ->where('sessionid', $request->input('sessionid'))
                ->where('staffid',   '!=', $request->input('staffid'))
                ->whereNotIn('id',   $originalGroupIds)   // ← the critical exclusion
                ->exists();

            if ($conflict) {
                $sc = Schoolclass::find($classId);
                $conflictNames[] = $sc ? $this->cleanUtf8String($sc->schoolclass) : $classId;
            }
        }

        if (!empty($conflictNames)) {
            return response()->json([
                'success' => false,
                'message' => 'The following classes are already assigned to a different teacher for this term/session: '
                    . implode(', ', $conflictNames) . '.',
            ], 422);
        }

        // ── Delete original group then re-create ──────────────────────────
        ClassTeacher::whereIn('id', $originalGroupIds)->delete();

        $createdRecords = [];
        foreach ($request->input('schoolclassid') as $classId) {
            $createdRecords[] = ClassTeacher::create([
                'staffid'       => $request->input('staffid'),
                'schoolclassid' => $classId,
                'termid'        => $request->input('termid'),
                'sessionid'     => $request->input('sessionid'),
            ]);
        }

        Log::info('ClassTeacher update', ['id' => $id, 'new_records' => count($createdRecords)]);
        return response()->json([
            'success' => true,
            'message' => count($createdRecords) . ' class teacher assignment(s) updated successfully.',
            'data'    => $createdRecords,
        ], 200);
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        $ct = ClassTeacher::find($id);
        if (!$ct) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }
        $ct->delete();
        Log::info('ClassTeacher deleted', ['id' => $id]);
        return response()->json(['success' => true, 'message' => 'Assignment deleted successfully.'], 200);
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No assignments selected.'], 400);
        }
        $deleted = ClassTeacher::whereIn('id', $ids)->delete();
        Log::info('ClassTeacher bulk delete', ['count' => $deleted]);
        return response()->json([
            'success' => true,
            'message' => $deleted . ' assignment(s) deleted successfully.',
        ], 200);
    }

    // =========================================================================
    // ASSIGNMENTS — edit modal pre-load
    // =========================================================================

    public function assignments($staffId, $termId, $sessionId)
    {
        $classIds = ClassTeacher::where('staffid',   $staffId)
            ->where('termid',    $termId)
            ->where('sessionid', $sessionId)
            ->pluck('schoolclassid')
            ->toArray();

        return response()->json(['success' => true, 'classIds' => $classIds]);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $ct = ClassTeacher::find($id);
        if (!$ct) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }
        return response()->json(['success' => true, 'data' => $ct]);
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
