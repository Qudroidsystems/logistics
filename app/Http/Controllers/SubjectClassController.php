<?php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subjectclass;
use App\Models\SubjectRegistrationStatus;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SubjectClassController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:View subject-class|Create subject-class|Update subject-class|Delete subject-class',
            ['only' => ['index']]
        );
        $this->middleware('permission:Create subject-class', ['only' => ['store']]);
        $this->middleware('permission:Update subject-class', ['only' => ['update', 'updateclass']]);
        $this->middleware('permission:Delete subject-class', ['only' => ['destroy', 'deletesubjectclass', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Subject Class Management";

        try {
            $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
                ->sortBy('schoolclass');

            $subjectteacher = SubjectTeacher::leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->get([
                    'subjectteacher.id as id',
                    'subjectteacher.staffid as subtid',
                    'subjectteacher.subjectid as subid',
                    'subject.id as subjectid',
                    'subject.subject as subject',
                    'subject.subject_code as subjectcode',
                    'users.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                ])
                ->sortBy('subject');

            $allStaff = User::orderBy('name')->get(['id', 'name']);

            // ── Filter lists for the Add modal ─────────────────────────
            // Only include sessions/terms that are actually used by at least
            // one SubjectTeacher, so the chips don't show empty values.
            $filterSessions = Schoolsession::whereIn(
                    'id',
                    SubjectTeacher::query()->select('sessionid')->distinct()
                )
                ->orderBy('session', 'asc')
                ->get(['id', 'session']);

            $filterTerms = Schoolterm::whereIn(
                    'id',
                    SubjectTeacher::query()->select('termid')->distinct()
                )
                ->orderBy('term', 'asc')
                ->get(['id', 'term']);

            return view('subjectclass.index')
                ->with('schoolclasses', $schoolclasses)
                ->with('subjectteacher', $subjectteacher)
                ->with('allStaff', $allStaff)
                ->with('filterSessions', $filterSessions)
                ->with('filterTerms', $filterTerms)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('SubjectClass Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading subject classes: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX (with server-side search)
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $query = Subjectclass::leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->select([
                    'subjectclass.id as id',
                    'schoolclass.id as schoolclassid',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as schoolarm',
                    'subjectteacher.id as subjectteacherid',
                    'subjectteacher.staffid as staffid',
                    'subjectteacher.subjectid as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'users.name as teachername',
                    'users.avatar as picture',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'subjectclass.created_at',
                    'subjectclass.updated_at'
                ]);

            // ── Global search ───────────────────────────────────────────
            $search = $request->input('search.value');
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                      ->orWhere('subject.subject', 'like', "%{$search}%")
                      ->orWhere('subject.subject_code', 'like', "%{$search}%")
                      ->orWhere('schoolclass.schoolclass', 'like', "%{$search}%")
                      ->orWhere('schoolarm.arm', 'like', "%{$search}%")
                      ->orWhere('schoolterm.term', 'like', "%{$search}%")
                      ->orWhere('schoolsession.session', 'like', "%{$search}%");
                });
            }

            // ── Per-column search ──────────────────────────────────────
            $columns = $request->input('columns', []);
            foreach ($columns as $col) {
                if (isset($col['searchable']) && $col['searchable'] === 'true'
                    && !empty($col['search']['value'])) {
                    $val = $col['search']['value'];
                    switch ($col['data']) {
                        case 'teacher_info':
                            $query->where('users.name', 'like', "%{$val}%");
                            break;
                        case 'subject_info':
                            $query->where(function ($q) use ($val) {
                                $q->where('subject.subject', 'like', "%{$val}%")
                                  ->orWhere('subject.subject_code', 'like', "%{$val}%");
                            });
                            break;
                        case 'class_info':
                            $query->where(function ($q) use ($val) {
                                $q->where('schoolclass.schoolclass', 'like', "%{$val}%")
                                  ->orWhere('schoolarm.arm', 'like', "%{$val}%");
                            });
                            break;
                        case 'term_info':
                            $query->where('schoolterm.term', 'like', "%{$val}%");
                            break;
                        case 'session_info':
                            $query->where('schoolsession.session', 'like', "%{$val}%");
                            break;
                    }
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                ->addColumn('teacher_info', function ($row) {
                    $staffname  = $this->cleanUtf8String($row->teachername ?? 'Unknown');
                    $defaultUrl = asset('storage/staff_avatars/unnamed.jpg');
                    $avatarUrl  = $defaultUrl;
                    $hasImage   = false;

                    $avatar    = trim($row->picture ?? '');
                    $isDefault = in_array($avatar, ['unnamed.jpg', 'unnamed.png', ''], true);

                    if (!$isDefault && $avatar !== '') {
                        if (Storage::exists('public/staff_avatars/' . $avatar)) {
                            $avatarUrl = asset('storage/staff_avatars/' . $avatar);
                            $hasImage  = true;
                        } else {
                            $diskPath = public_path('storage/staff_avatars/' . $avatar);
                            if (file_exists($diskPath)) {
                                $avatarUrl = asset('storage/staff_avatars/' . $avatar);
                                $hasImage  = true;
                            }
                        }
                    }

                    if ($hasImage) {
                        $avatarHtml = '<img src="' . e($avatarUrl) . '" alt="' . e($staffname) . '" class="teacher-avatar" onerror="this.onerror=null;this.src=\'' . e($defaultUrl) . '\'">';
                    } else {
                        $words    = preg_split('/\s+/', trim($staffname));
                        $initials = implode('', array_map(
                            fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'),
                            array_slice($words, 0, 2)
                        ));
                        $avatarHtml = '<div class="avatar-initials">' . e($initials) . '</div>';
                    }

                    return '<div class="d-flex align-items-center gap-2">'
                        . $avatarHtml
                        . '<span class="fw-semibold text-dark">' . e($staffname) . '</span>'
                        . '</div>';
                })

                ->addColumn('subject_info', function ($row) {
                    return '<div>'
                        . '<span class="fw-semibold">' . e($this->cleanUtf8String($row->subjectname ?? '')) . '</span>'
                        . '<br><small class="text-muted">' . e($this->cleanUtf8String($row->subjectcode ?? 'N/A')) . '</small>'
                        . '</div>';
                })

                ->addColumn('class_info', function ($row) {
                    $class = $this->cleanUtf8String($row->schoolclass ?? '');
                    $arm   = $this->cleanUtf8String($row->schoolarm ?? '');
                    return '<span class="sc-badge sc-badge-class">' . e($class) . ' (' . e($arm) . ')</span>';
                })

                ->addColumn('term_info', function ($row) {
                    $term      = $this->cleanUtf8String($row->termname ?? 'N/A');
                    $termClass = match(true) {
                        str_contains($term, 'First')  => 'sc-badge-term-first',
                        str_contains($term, 'Second') => 'sc-badge-term-second',
                        str_contains($term, 'Third')  => 'sc-badge-term-third',
                        default                       => 'sc-badge-term-other'
                    };
                    return '<span class="sc-badge ' . $termClass . '">' . e($term) . '</span>';
                })

                ->addColumn('session_info', function ($row) {
                    return '<span class="sc-badge sc-badge-session">'
                        . e($this->cleanUtf8String($row->sessionname ?? 'N/A'))
                        . '</span>';
                })

                ->addColumn('registration_count', function ($row) {
                    $count = SubjectRegistrationStatus::where('subjectclassid', $row->id)->count();
                    if ($count > 0) {
                        return '<span class="badge bg-info text-white">' . $count . ' Student(s)</span>';
                    }
                    return '<span class="text-muted">—</span>';
                })

                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) {
                        return '<span class="text-muted small">—</span>';
                    }
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user()->can('Update subject-class')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-sc-btn" title="Change Teacher" '
                            . 'data-id="%s" data-staffid="%s" data-teachername="%s" '
                            . 'data-subjectname="%s" data-subjectcode="%s" '
                            . 'data-termname="%s" data-sessionname="%s" '
                            . 'data-classname="%s (%s)">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            $row->staffid,
                            e($this->cleanUtf8String($row->teachername ?? '')),
                            e($this->cleanUtf8String($row->subjectname ?? '')),
                            e($this->cleanUtf8String($row->subjectcode ?? '')),
                            e($this->cleanUtf8String($row->termname ?? '')),
                            e($this->cleanUtf8String($row->sessionname ?? '')),
                            e($this->cleanUtf8String($row->schoolclass ?? '')),
                            e($this->cleanUtf8String($row->schoolarm ?? ''))
                        );
                    }

                    if (auth()->user()->can('Delete subject-class')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-sc-btn" title="Delete" '
                            . 'data-id="%s" data-subject="%s" data-teacher="%s">'
                            . '<i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->subjectname ?? 'Unknown Subject')),
                            e($this->cleanUtf8String($row->teachername ?? 'Unknown Teacher'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns([
                    'checkbox', 'teacher_info', 'subject_info', 'class_info',
                    'term_info', 'session_info', 'registration_count',
                    'formatted_date', 'action'
                ])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('SubjectClass DataTable error:', [
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
                    'total' => Subjectclass::count(),
                    'unique_teachers' => Subjectclass::join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                        ->distinct('subjectteacher.staffid')
                        ->count('subjectteacher.staffid'),
                    'unique_classes' => Subjectclass::distinct('schoolclassid')->count('schoolclassid'),
                    'unique_subjects' => Subjectclass::join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                        ->distinct('subjectteacher.subjectid')
                        ->count('subjectteacher.subjectid'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('SubjectClass stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => [
                    'total' => 0,
                    'unique_teachers' => 0,
                    'unique_classes' => 0,
                    'unique_subjects' => 0
                ],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

   public function store(Request $request): JsonResponse
{
    // Accept a single id or an array of ids for backward compatibility
    $request->merge([
        'schoolclassid' => array_values(array_filter((array) $request->input('schoolclassid', []))),
    ]);

    $validator = Validator::make($request->all(), [
        'schoolclassid'      => 'required|array|min:1',
        'schoolclassid.*'    => 'required|exists:schoolclass,id',
        'subjectteacherid'   => 'required|array|min:1',
        'subjectteacherid.*' => 'required|exists:subjectteacher,id',
    ], [
        'schoolclassid.required'      => 'Please select at least one class!',
        'schoolclassid.min'           => 'Please select at least one class!',
        'schoolclassid.*.exists'      => 'One or more selected classes do not exist!',
        'subjectteacherid.required'   => 'Please select at least one subject teacher!',
        'subjectteacherid.*.required' => 'Please select at least one subject teacher!',
        'subjectteacherid.*.exists'   => 'One or more selected subject teachers do not exist!',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    $schoolClassIds    = array_unique($request->input('schoolclassid'));
    $subjectTeacherIds = array_unique($request->input('subjectteacherid'));

    DB::beginTransaction();
    try {
        $createdRecords = [];
        $skippedCount   = 0;

        $subjectTeachers = SubjectTeacher::whereIn('id', $subjectTeacherIds)->get()->keyBy('id');

        foreach ($schoolClassIds as $schoolClassId) {
            foreach ($subjectTeacherIds as $subjectTeacherId) {
                $subjectTeacher = $subjectTeachers->get($subjectTeacherId);
                if (!$subjectTeacher) {
                    continue;
                }

                $exists = Subjectclass::where('schoolclassid', $schoolClassId)
                    ->where('subjectteacherid', $subjectTeacherId)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $createdRecords[] = Subjectclass::create([
                    'schoolclassid'    => $schoolClassId,
                    'subjectteacherid' => $subjectTeacherId,
                    'subjectid'        => $subjectTeacher->subjectid,
                ]);
            }
        }

        DB::commit();

        if (empty($createdRecords)) {
            return response()->json([
                'success' => false,
                'message' => 'All selected assignments already exist for the chosen class(es).',
            ], 422);
        }

        $message = count($createdRecords) . ' Subject Class(es) added successfully.';
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} already existed and were skipped.)";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $createdRecords,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating subject class:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to create subject class: ' . $e->getMessage(),
        ], 500);
    }
}

    // =========================================================================
    // UPDATE — swap the STAFF MEMBER on an existing subject-class assignment
    // =========================================================================

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_staffid' => 'required|exists:users,id',
        ], [
            'new_staffid.required' => 'Please select a staff member!',
            'new_staffid.exists'   => 'Selected staff member does not exist!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $newStaffId = (int) $request->input('new_staffid');

        $subjectclass = Subjectclass::find($id);
        if (!$subjectclass) {
            return response()->json([
                'success' => false,
                'message' => 'Subject Class not found.',
            ], 404);
        }

        $currentSubjectTeacher = SubjectTeacher::find($subjectclass->subjectteacherid);
        if (!$currentSubjectTeacher) {
            return response()->json([
                'success' => false,
                'message' => 'Current subject teacher record not found.',
            ], 404);
        }

        $oldStaffId = (int) $currentSubjectTeacher->staffid;
        $subjectId  = $currentSubjectTeacher->subjectid;
        $termId     = $currentSubjectTeacher->termid;
        $sessionId  = $currentSubjectTeacher->sessionid;

        if ($oldStaffId === $newStaffId) {
            return response()->json([
                'success' => true,
                'message' => 'No change — the selected teacher is already assigned.',
            ], 200);
        }

        $newSubjectTeacher = SubjectTeacher::firstOrCreate(
            [
                'staffid'   => $newStaffId,
                'subjectid' => $subjectId,
                'termid'    => $termId,
                'sessionid' => $sessionId,
            ]
        );

        $duplicate = Subjectclass::where('schoolclassid', $subjectclass->schoolclassid)
            ->where('subjectteacherid', $newSubjectTeacher->id)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'The selected teacher is already assigned to this subject and class for the same term and session.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $subjectclass->update([
                'subjectteacherid' => $newSubjectTeacher->id,
            ]);

            Broadsheets::where('subjectclass_id', $id)
                ->update(['staff_id' => $newStaffId]);

            BroadsheetsMock::where('subjectclass_id', $id)
                ->update(['staff_id' => $newStaffId]);

            SubjectRegistrationStatus::where('subjectclassid', $id)
                ->update(['staffid' => $newStaffId]);

            DB::table('student_subject_register_record')
                ->where('subjectclassid', $id)
                ->update(['staffid' => $newStaffId]);

            DB::commit();

            $newStaff = User::find($newStaffId);
            $oldStaff = User::find($oldStaffId);

            Log::info('SubjectClass staff swapped', [
                'subjectclass_id'       => $id,
                'old_staff_id'          => $oldStaffId,
                'new_staff_id'          => $newStaffId,
                'new_subjectteacher_id' => $newSubjectTeacher->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Teacher changed from {$oldStaff?->name} to {$newStaff?->name} successfully. All related records updated.",
                'data'    => $subjectclass->fresh(),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('SubjectClass staff swap failed', [
                'subjectclass_id' => $id,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id): JsonResponse
    {
        try {
            $subjectclass = Subjectclass::find($id);

            if (!$subjectclass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject Class not found.',
                ], 404);
            }

            $hasBroadsheets   = Broadsheets::where('subjectclass_id', $id)->exists();
            $hasRegistrations = SubjectRegistrationStatus::where('subjectclassid', $id)->exists();
            $hasMockRecords   = BroadsheetsMock::where('subjectclass_id', $id)->exists();

            if ($hasBroadsheets || $hasRegistrations || $hasMockRecords) {
                $details = [];
                if ($hasBroadsheets)   $details[] = 'broadsheet score records';
                if ($hasRegistrations) $details[] = 'student subject registrations';
                if ($hasMockRecords)   $details[] = 'mock broadsheet records';

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this Subject Class because it has existing '
                               . implode(', ', $details) . '. '
                               . 'Please unregister all students from this subject class before deleting it.',
                ], 422);
            }

            $subjectclass->delete();

            Log::info('SubjectClass deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Subject Class deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting subject class:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject class: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DELETE SUBJECT CLASS (AJAX)
    // =========================================================================

    public function deletesubjectclass(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subjectclassid' => 'required|exists:subjectclass,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $id = $request->subjectclassid;
            $subjectclass = Subjectclass::find($id);

            if (!$subjectclass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject Class not found.',
                ], 404);
            }

            $hasBroadsheets   = Broadsheets::where('subjectclass_id', $id)->exists();
            $hasRegistrations = SubjectRegistrationStatus::where('subjectclassid', $id)->exists();
            $hasMockRecords   = BroadsheetsMock::where('subjectclass_id', $id)->exists();

            if ($hasBroadsheets || $hasRegistrations || $hasMockRecords) {
                $details = [];
                if ($hasBroadsheets)   $details[] = 'broadsheet score records';
                if ($hasRegistrations) $details[] = 'student subject registrations';
                if ($hasMockRecords)   $details[] = 'mock broadsheet records';

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this Subject Class because it has existing '
                               . implode(', ', $details) . '. '
                               . 'Please unregister all students from this subject class before deleting it.',
                ], 422);
            }

            $subjectclass->delete();

            Log::info('SubjectClass deleted via AJAX:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Subject Class has been removed.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting subject class via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject class: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

   public function deleteMultiple(Request $request): JsonResponse
{
    try {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $request->input('ids', []))
        )));

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No subject classes selected.',
            ], 400);
        }

        $existingIds = Subjectclass::whereIn('id', $ids)->pluck('id')
            ->map(fn($v) => (int) $v)->all();

        if (empty($existingIds)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected subject classes no longer exist.',
            ], 404);
        }

        // One query per table instead of several per row
        $blockedIds = array_values(array_unique(array_merge(
            Broadsheets::whereIn('subjectclass_id', $existingIds)->pluck('subjectclass_id')->map(fn($v) => (int) $v)->all(),
            BroadsheetsMock::whereIn('subjectclass_id', $existingIds)->pluck('subjectclass_id')->map(fn($v) => (int) $v)->all(),
            SubjectRegistrationStatus::whereIn('subjectclassid', $existingIds)->pluck('subjectclassid')->map(fn($v) => (int) $v)->all(),
            DB::table('student_subject_register_record')->whereIn('subjectclassid', $existingIds)->pluck('subjectclassid')->map(fn($v) => (int) $v)->all()
        )));

        $deletableIds = array_values(array_diff($existingIds, $blockedIds));

        if (empty($deletableIds)) {
            return response()->json([
                'success'     => false,
                'message'     => 'None of the selected assignments can be deleted because they have scores or student registrations. Unregister the students first.',
                'blocked_ids' => $blockedIds,
            ], 422);
        }

        DB::beginTransaction();
        $deleted = Subjectclass::whereIn('id', $deletableIds)->delete();
        DB::commit();

        $skipped = count($blockedIds);
        $message = $deleted . ' subject class(es) deleted successfully.';
        if ($skipped > 0) {
            $message .= " {$skipped} skipped because they have scores or student registrations.";
        }

        Log::info('Bulk delete completed', [
            'requested' => count($ids),
            'deleted'   => $deleted,
            'skipped'   => $skipped,
        ]);

        return response()->json([
            'success'       => true,
            'message'       => $message,
            'deleted_count' => $deleted,
            'skipped_count' => $skipped,
            'blocked_ids'   => $blockedIds,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Bulk delete failed:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Error deleting subject classes: ' . $e->getMessage(),
        ], 500);
    }
}
    // =========================================================================
    // ASSIGNMENTS
    // =========================================================================

    public function assignments($subjectClassId): JsonResponse
    {
        try {
            $subjectclass = Subjectclass::where('id', $subjectClassId)
                ->select('schoolclassid', 'subjectteacherid')
                ->first();

            if (!$subjectclass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject Class not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'schoolclassid'    => $subjectclass->schoolclassid,
                    'subjectteacherid' => [$subjectclass->subjectteacherid],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignments.',
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
        $string = (string) $string;
        if (function_exists('mb_scrub')) {
            $string = mb_scrub($string, 'UTF-8');
        } else {
            $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        }
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);
        return $string ?? '';
    }
}
