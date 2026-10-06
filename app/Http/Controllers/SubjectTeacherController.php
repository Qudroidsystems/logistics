<?php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subject;
use App\Models\Subjectclass;
use App\Models\SubjectRegistrationStatus;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SubjectTeacherController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View subject-teacher|Create subject-teacher|Update subject-teacher|Delete subject-teacher', ['only' => ['index']]);
        $this->middleware('permission:Create subject-teacher', ['only' => ['store']]);
        $this->middleware('permission:Update subject-teacher', ['only' => ['update', 'updatesubjectteacher']]);
        $this->middleware('permission:Delete subject-teacher', ['only' => ['destroy', 'deletesubjectteacher', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Subject Teacher Management";

        try {
            $terms = Schoolterm::orderBy('term', 'asc')->get();
            $schoolsessions = Schoolsession::orderBy('session', 'asc')->get();
            $subjects = Subject::orderBy('subject', 'asc')->get();

            // Alphabetical order for teachers in Add/Edit modal
            $staffs = User::whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Student');
            })
                ->orderBy('name', 'asc')
                ->get(['users.id as userid', 'users.name as name', 'users.avatar as avatar']);

            return view('subjectteacher.index')
                ->with('terms', $terms)
                ->with('schoolsessions', $schoolsessions)
                ->with('staffs', $staffs)
                ->with('subjects', $subjects)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('SubjectTeacher Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading subject teachers: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    //
    // Grouped so that one row = one teacher + subject + session.
    // The `term_info` column lists every term this group covers.
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $subjectteacher = SubjectTeacher::leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->select([
                    // Representative row ID so edit/delete still work
                    DB::raw('MIN(subjectteacher.id) as id'),
                    'subjectteacher.staffid as staffid',
                    'subjectteacher.subjectid as subjectid',
                    'subjectteacher.sessionid as sessionid',

                    'users.id as userid',
                    'users.name as staffname',
                    'users.avatar as avatar',

                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',

                    'schoolsession.session as sessionname',

                    DB::raw('MAX(subjectteacher.created_at) as created_at'),
                    DB::raw('MAX(subjectteacher.updated_at) as updated_at'),
                ])
                ->groupBy([
                    'subjectteacher.staffid',
                    'subjectteacher.subjectid',
                    'subjectteacher.sessionid',
                    'users.id',
                    'users.name',
                    'users.avatar',
                    'subject.subject',
                    'subject.subject_code',
                    'schoolsession.session',
                ])
                ->orderByDesc(DB::raw('MAX(subjectteacher.created_at)'));

            return DataTables::of($subjectteacher)
                ->addIndexColumn()

                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                ->addColumn('teacher_info', function ($row) {
                    $staffname = $this->cleanUtf8String($row->staffname ?? 'Unknown');
                    $defaultUrl = asset('storage/staff_avatars/unnamed.jpg');
                    $avatarUrl = $defaultUrl;
                    $hasImage = false;

                    $avatar = trim($row->avatar ?? '');
                    $isDefault = in_array($avatar, ['unnamed.jpg', 'unnamed.png', ''], true);

                    if (!$isDefault && $avatar !== '') {
                        if (Storage::exists('public/staff_avatars/' . $avatar)) {
                            $avatarUrl = asset('storage/staff_avatars/' . $avatar);
                            $hasImage = true;
                        } else {
                            $diskPath = public_path('storage/staff_avatars/' . $avatar);
                            if (file_exists($diskPath)) {
                                $avatarUrl = asset('storage/staff_avatars/' . $avatar);
                                $hasImage = true;
                            }
                        }
                    }

                    if ($hasImage) {
                        $avatarHtml = '<img src="' . e($avatarUrl) . '" alt="' . e($staffname) . '" class="teacher-avatar" onerror="this.onerror=null;this.src=\'' . e($defaultUrl) . '\'">';
                    } else {
                        $words = preg_split('/\s+/', trim($staffname));
                        $initials = implode('', array_map(
                            fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'),
                            array_slice($words, 0, 2)
                        ));
                        $avatarHtml = '<div class="avatar-initials">' . e($initials) . '</div>';
                    }

                    return '<div class="d-flex align-items-center gap-2">
                        ' . $avatarHtml . '
                        <span class="fw-semibold text-dark">' . e($staffname) . '</span>
                    </div>';
                })

                ->addColumn('subject_info', function ($row) {
                    return '<div>
                        <span class="fw-semibold">' . e($this->cleanUtf8String($row->subjectname ?? '')) . '</span>
                        <br><small class="text-muted">' . e($row->subjectcode ?? 'N/A') . '</small>
                    </div>';
                })

                // Show ALL terms for this teacher+subject+session as badges
                ->addColumn('term_info', function ($row) {
                    $terms = SubjectTeacher::where('staffid', $row->staffid)
                        ->where('subjectid', $row->subjectid)
                        ->where('sessionid', $row->sessionid)
                        ->join('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                        ->orderBy('schoolterm.term', 'asc')
                        ->pluck('schoolterm.term', 'subjectteacher.termid')
                        ->toArray();

                    $html = '';
                    foreach ($terms as $termId => $termName) {
                        $termClass = match(true) {
                            str_contains($termName, 'First')  => 'term-first',
                            str_contains($termName, 'Second') => 'term-second',
                            str_contains($termName, 'Third')  => 'term-third',
                            default => 'term-other'
                        };
                        $html .= '<span class="st-badge st-badge-term ' . $termClass . '">' . e($termName) . '</span> ';
                    }
                    return $html ?: '<span class="text-muted">—</span>';
                })

                ->addColumn('session_info', function ($row) {
                    return '<span class="st-badge st-badge-session">' . e($this->cleanUtf8String($row->sessionname ?? 'N/A')) . '</span>';
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
                    // All term IDs for this teacher+subject+session
                    $termIds = SubjectTeacher::where('staffid', $row->staffid)
                        ->where('subjectid', $row->subjectid)
                        ->where('sessionid', $row->sessionid)
                        ->pluck('termid')
                        ->toArray();

                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user()->can('Update subject-teacher')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-st-btn" title="Edit" '
                            . 'data-id="%s" data-staffid="%s" data-subjectid="%s" '
                            . 'data-sessionid="%s" data-termids="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            $row->userid,
                            $row->subjectid,
                            $row->sessionid,
                            implode(',', $termIds)
                        );
                    }

                    if (auth()->user()->can('Delete subject-teacher')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-st-btn" title="Delete" '
                            . 'data-id="%s" data-teacher="%s" data-subject="%s">'
                            . '<i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->staffname ?? 'Unknown Teacher')),
                            e($this->cleanUtf8String($row->subjectname ?? 'Unknown Subject'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'teacher_info', 'subject_info', 'term_info', 'session_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('SubjectTeacher DataTable error:', [
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
                    'total' => SubjectTeacher::count(),
                    'unique_teachers' => SubjectTeacher::distinct('staffid')->count('staffid'),
                    'unique_subjects' => SubjectTeacher::distinct('subjectid')->count('subjectid'),
                    'unique_sessions' => SubjectTeacher::distinct('sessionid')->count('sessionid'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('SubjectTeacher stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'unique_teachers' => 0, 'unique_subjects' => 0, 'unique_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        Log::info('Store SubjectTeacher Request Data:', $request->all());

        $validator = Validator::make($request->all(), [
            'staffid' => 'required|exists:users,id',
            'subjectids' => 'required|array|min:1',
            'subjectids.*' => 'exists:subject,id',
            'termid' => 'required|array|min:1',
            'termid.*' => 'exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id',
        ], [
            'staffid.required' => 'Please select a teacher.',
            'staffid.exists' => 'Selected teacher does not exist.',
            'subjectids.required' => 'Please select at least one subject.',
            'subjectids.array' => 'Subjects must be an array.',
            'subjectids.min' => 'Please select at least one subject.',
            'subjectids.*.exists' => 'One or more selected subjects do not exist.',
            'termid.required' => 'Please select at least one term.',
            'termid.array' => 'Terms must be an array.',
            'termid.min' => 'Please select at least one term.',
            'termid.*.exists' => 'One or more selected terms do not exist.',
            'sessionid.required' => 'Please select a session.',
            'sessionid.exists' => 'Selected session does not exist.',
        ]);

        if ($validator->fails()) {
            Log::error('Store SubjectTeacher Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $staffid = $request->input('staffid');
            $subjectids = $request->input('subjectids');
            $termids = $request->input('termid');
            $sessionid = $request->input('sessionid');

            $existing = SubjectTeacher::where('staffid', $staffid)
                ->whereIn('subjectid', $subjectids)
                ->whereIn('termid', $termids)
                ->where('sessionid', $sessionid)
                ->pluck('subjectid')
                ->toArray();

            if (!empty($existing)) {
                $existingSubjects = Subject::whereIn('id', $existing)->pluck('subject')->toArray();
                return response()->json([
                    'success' => false,
                    'message' => 'The teacher is already assigned to: ' . implode(', ', $existingSubjects) . ' for one or more selected terms and session.'
                ], 422);
            }

            $createdRecords = [];
            foreach ($termids as $termid) {
                foreach ($subjectids as $subjectid) {
                    $subjectteacher = SubjectTeacher::create([
                        'staffid' => $staffid,
                        'subjectid' => $subjectid,
                        'termid' => $termid,
                        'sessionid' => $sessionid,
                    ]);
                    $createdRecords[] = $subjectteacher;
                }
            }

            DB::commit();

            Log::info('SubjectTeacher created successfully', [
                'count' => count($createdRecords)
            ]);

            return response()->json([
                'success' => true,
                'message' => count($createdRecords) . ' Subject Teacher(s) added successfully.',
                'data' => $createdRecords
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating subject teacher:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subject teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        Log::info('Update SubjectTeacher Request Data:', $request->all());

        $validator = Validator::make($request->all(), [
            'staffid' => 'required|exists:users,id',
            'subjectids' => 'required|array|min:1',
            'subjectids.*' => 'exists:subject,id',
            'termid' => 'required|array|min:1',
            'termid.*' => 'exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id',
        ], [
            'staffid.required' => 'Please select a teacher.',
            'staffid.exists' => 'Selected teacher does not exist.',
            'subjectids.required' => 'Please select at least one subject.',
            'subjectids.array' => 'Subjects must be an array.',
            'subjectids.min' => 'Please select at least one subject.',
            'subjectids.*.exists' => 'One or more selected subjects do not exist.',
            'termid.required' => 'Please select at least one term.',
            'termid.array' => 'Terms must be an array.',
            'termid.min' => 'Please select at least one term.',
            'termid.*.exists' => 'One or more selected terms do not exist.',
            'sessionid.required' => 'Please select a session.',
            'sessionid.exists' => 'Selected session does not exist.',
        ]);

        if ($validator->fails()) {
            Log::error('Update SubjectTeacher Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $staffid = $request->input('staffid');
            $subjectids = $request->input('subjectids');
            $termids = $request->input('termid');
            $sessionid = $request->input('sessionid');

            $current = SubjectTeacher::find($id);
            if (!$current) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject teacher record not found.'
                ], 404);
            }

            // Delete ALL records for this teacher+subject+session (all terms)
            SubjectTeacher::where('staffid', $current->staffid)
                ->where('subjectid', $current->subjectid)
                ->where('sessionid', $current->sessionid)
                ->delete();

            $conflict = SubjectTeacher::where('staffid', $staffid)
                ->whereIn('subjectid', $subjectids)
                ->whereIn('termid', $termids)
                ->where('sessionid', $sessionid)
                ->exists();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected teacher is already assigned to one or more of these subjects for the selected terms and session.'
                ], 422);
            }

            $createdRecords = [];
            foreach ($termids as $termid) {
                foreach ($subjectids as $subjectid) {
                    $subjectteacher = SubjectTeacher::create([
                        'staffid' => $staffid,
                        'subjectid' => $subjectid,
                        'termid' => $termid,
                        'sessionid' => $sessionid,
                    ]);
                    $createdRecords[] = $subjectteacher;
                }
            }

            $this->updateRelatedRecords($staffid, $id);

            DB::commit();

            Log::info('SubjectTeacher updated successfully', [
                'count' => count($createdRecords)
            ]);

            return response()->json([
                'success' => true,
                'message' => count($createdRecords) . ' Subject Teacher(s) updated successfully.',
                'data' => $createdRecords
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating subject teacher:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subject teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE RELATED RECORDS
    // =========================================================================

    private function updateRelatedRecords($staffid, $subjectTeacherId)
    {
        try {
            $sub = SubjectTeacher::where('subjectteacher.id', $subjectTeacherId)
                ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
                ->leftJoin('broadsheets', 'broadsheets.subjectclass_id', '=', 'subjectclass.id')
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->select([
                    'broadsheets.subjectclass_id as subclass',
                    'broadsheets.term_id as term',
                    'broadsheet_records.session_id as session'
                ])
                ->get();

            foreach ($sub as $value) {
                if ($value->subclass && $value->term && $value->session) {
                    Broadsheets::where('subjectclass_id', $value->subclass)
                        ->where('term_id', $value->term)
                        ->whereIn('broadsheet_record_id', function ($query) use ($value) {
                            $query->select('id')
                                ->from('broadsheet_records')
                                ->where('session_id', $value->session);
                        })
                        ->update(['staff_id' => $staffid]);

                    SubjectRegistrationStatus::where('subjectclassid', $value->subclass)
                        ->where('termid', $value->term)
                        ->where('sessionid', $value->session)
                        ->update(['staffid' => $staffid]);
                }
            }

            Log::info('Updated related records for subject teacher', [
                'staffid' => $staffid,
                'subjectteacher_id' => $subjectTeacherId
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating related records:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // =========================================================================
    // DESTROY (single)
    //
    // NOTE: because the DataTable row represents a GROUP (teacher + subject +
    // session, possibly spanning multiple terms), deleting a row deletes ALL
    // term records that belong to that group.
    // =========================================================================

    public function destroy($id)
    {
        try {
            $subjectteacher = SubjectTeacher::find($id);
            if (!$subjectteacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject Teacher not found.'
                ], 404);
            }

            // Every record in the group
            $groupIds = SubjectTeacher::where('staffid', $subjectteacher->staffid)
                ->where('subjectid', $subjectteacher->subjectid)
                ->where('sessionid', $subjectteacher->sessionid)
                ->pluck('id')
                ->toArray();

            // Block if ANY of them is used by a Subjectclass
            $inUse = Subjectclass::whereIn('subjectteacherid', $groupIds)->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject teacher is assigned to one or more classes and cannot be deleted.'
                ], 422);
            }

            SubjectTeacher::whereIn('id', $groupIds)->delete();

            Log::info('SubjectTeacher group deleted:', ['ids' => $groupIds]);

            return response()->json([
                'success' => true,
                'message' => 'Subject Teacher deleted successfully.',
                'data' => ['ids' => $groupIds]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting subject teacher:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DELETE SUBJECT TEACHER (AJAX)
    // =========================================================================

    public function deletesubjectteacher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subjectteacherid' => 'required|exists:subjectteacher,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $id = $request->subjectteacherid;
            $subjectteacher = SubjectTeacher::find($id);

            if (!$subjectteacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject Teacher not found.'
                ], 404);
            }

            $groupIds = SubjectTeacher::where('staffid', $subjectteacher->staffid)
                ->where('subjectid', $subjectteacher->subjectid)
                ->where('sessionid', $subjectteacher->sessionid)
                ->pluck('id')
                ->toArray();

            $inUse = Subjectclass::whereIn('subjectteacherid', $groupIds)->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject teacher is assigned to one or more classes and cannot be deleted.'
                ], 422);
            }

            SubjectTeacher::whereIn('id', $groupIds)->delete();

            Log::info('SubjectTeacher group deleted via AJAX:', ['ids' => $groupIds]);

            return response()->json([
                'success' => true,
                'message' => 'Subject Teacher has been removed.',
                'data' => ['ids' => $groupIds]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting subject teacher via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY
    //
    // Each incoming ID is a representative group ID; we expand each into its
    // full group before checking usage and deleting.
    // =========================================================================

    public function deleteMultiple(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subject teachers selected.'
                ], 400);
            }

            $existingIds = SubjectTeacher::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds = array_diff($ids, $existingIds);

            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected subject teachers do not exist.'
                ], 400);
            }

            // Expand each representative ID into its full group
            $allGroupIds = [];
            foreach ($existingIds as $repId) {
                $rep = SubjectTeacher::find($repId);
                if (!$rep) continue;

                $groupIds = SubjectTeacher::where('staffid', $rep->staffid)
                    ->where('subjectid', $rep->subjectid)
                    ->where('sessionid', $rep->sessionid)
                    ->pluck('id')
                    ->toArray();

                $allGroupIds = array_merge($allGroupIds, $groupIds);
            }
            $allGroupIds = array_unique($allGroupIds);

            $inUse = Subjectclass::whereIn('subjectteacherid', $allGroupIds)->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some subject teachers are assigned to classes and cannot be deleted.'
                ], 422);
            }

            DB::beginTransaction();
            $deleted = SubjectTeacher::whereIn('id', $allGroupIds)->delete();
            DB::commit();

            Log::info('Bulk delete completed', [
                'total_groups' => count($ids),
                'total_rows'   => count($allGroupIds),
                'deleted'      => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' subject teacher record(s) deleted successfully.',
                'deleted_count' => $deleted
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting subject teachers: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // GET SUBJECTS (for edit modal)
    // =========================================================================

    public function getSubjects($id)
    {
        try {
            $subjectteacher = SubjectTeacher::find($id);
            if (!$subjectteacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject Teacher not found.'
                ], 404);
            }

            $subjectTeachers = SubjectTeacher::where('staffid', $subjectteacher->staffid)
                ->where('subjectid', $subjectteacher->subjectid)
                ->where('sessionid', $subjectteacher->sessionid)
                ->select('subjectid', 'termid')
                ->get();

            $subjectIds = $subjectTeachers->pluck('subjectid')->unique()->toArray();
            $termIds = $subjectTeachers->pluck('termid')->unique()->toArray();

            return response()->json([
                'success' => true,
                'staffid' => $subjectteacher->staffid,
                'termIds' => $termIds ?: [],
                'sessionid' => $subjectteacher->sessionid,
                'subjectIds' => $subjectIds ?: []
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting subjects:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get subjects: ' . $e->getMessage()
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