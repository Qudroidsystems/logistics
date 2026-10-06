<?php

namespace App\Http\Controllers;

use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View subjects|Create subjects|Update subjects|Delete subjects', ['only' => ['index']]);
        $this->middleware('permission:Create subjects', ['only' => ['store']]);
        $this->middleware('permission:Update subjects', ['only' => ['update', 'updatesubject']]);
        $this->middleware('permission:Delete subjects', ['only' => ['destroy', 'deletesubject', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX — also supplies teachers, terms and sessions for the Add modal
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Subject Management";

        try {
            $terms          = Schoolterm::orderBy('term', 'asc')->get();
            $schoolsessions = Schoolsession::orderBy('session', 'asc')->get();

            // Same staff query used by SubjectTeacherController
            $staffs = User::whereHas('roles', function ($q) {
                    $q->where('name', '!=', 'Student');
                })
                ->orderBy('name', 'asc')
                ->get(['users.id as userid', 'users.name as name']);

            return view('subject.index')
                ->with('terms', $terms)
                ->with('schoolsessions', $schoolsessions)
                ->with('staffs', $staffs)
                ->with('pagetitle', $pagetitle);
        } catch (\Exception $e) {
            Log::error('Subject Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading subjects: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $subjects = Subject::select('subject.*');

            return DataTables::of($subjects)
                ->addIndexColumn()

                // ── Checkbox ──────────────────────────────────────────────────
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                // ── Subject Name with ID ─────────────────────────────────────
                ->addColumn('subject_info', function ($row) {
                    return '<div>
                        <span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->subject ?? '')) . '</span>
                        <small class="text-muted d-block">ID: ' . $row->id . '</small>
                    </div>';
                })

                // ── Subject Code Badge ──────────────────────────────────────
                ->addColumn('code_info', function ($row) {
                    return '<span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-3 py-2" style="border-radius:6px;font-size:12px;">
                        ' . e($row->subject_code ?? 'N/A') . '
                    </span>';
                })

                // ── Remark Badge ─────────────────────────────────────────────
                ->addColumn('remark_info', function ($row) {
                    return '<span class="sub-badge sub-badge-remark">' . e($this->cleanUtf8String($row->remark ?? 'N/A')) . '</span>';
                })

                // ── Usage Count ──────────────────────────────────────────────
                ->addColumn('usage_count', function ($row) {
                    $count = DB::table('subjectteacher')->where('subjectid', $row->id)->count();
                    if ($count > 0) {
                        return '<span class="badge bg-info text-white">' . $count . ' Assignment(s)</span>';
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

                    if (auth()->user()->can('Update subjects')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-subject-btn" title="Edit" '
                            . 'data-id="%s" data-subject="%s" data-code="%s" data-remark="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->subject ?? '')),
                            e($row->subject_code ?? ''),
                            e($this->cleanUtf8String($row->remark ?? ''))
                        );
                    }

                    if (auth()->user()->can('Delete subjects')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-subject-btn" title="Delete" '
                            . 'data-id="%s" data-subject="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->subject ?? 'Unknown Subject'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'subject_info', 'code_info', 'remark_info', 'usage_count', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('Subject DataTable error:', [
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
            $totalSubjects = Subject::count();
            $subjectsWithTeachers = DB::table('subjectteacher')
                ->distinct('subjectid')
                ->count('subjectid');
            $recentlyUpdated = Subject::where('updated_at', '>=', now()->subDays(30))->count();

            return response()->json([
                'stats' => [
                    'total' => $totalSubjects,
                    'with_teachers' => $subjectsWithTeachers,
                    'recently_updated' => $recentlyUpdated,
                    'showing' => $totalSubjects,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Subject stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'with_teachers' => 0, 'recently_updated' => 0, 'showing' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE — one or many subjects, each optionally assigned to a teacher
    //
    // Payload:
    //   subjects[n][subject|subject_code|remark|staffid]   (staffid optional)
    //   termid[]   + sessionid    (required only if any row has a staffid)
    // =========================================================================

    public function store(Request $request)
    {
        // Backward compatibility: wrap a single-subject payload
        if (!$request->has('subjects') && $request->has('subject')) {
            $request->merge(['subjects' => [[
                'subject'      => $request->input('subject'),
                'subject_code' => $request->input('subject_code'),
                'remark'       => $request->input('remark'),
            ]]]);
        }

        // Normalise + trim
        $rows = collect($request->input('subjects', []))
            ->filter(fn ($r) => is_array($r))
            ->map(function ($r) {
                $staffid = trim((string) ($r['staffid'] ?? ''));
                return [
                    'subject'      => trim((string) ($r['subject'] ?? '')),
                    'subject_code' => trim((string) ($r['subject_code'] ?? '')),
                    'remark'       => trim((string) ($r['remark'] ?? '')),
                    'staffid'      => $staffid !== '' ? $staffid : null,
                ];
            })
            ->values()
            ->all();

        $request->merge(['subjects' => $rows]);

        $anyTeacher = collect($rows)->contains(fn ($r) => $r['staffid'] !== null);

        // Assigning teachers is a subject-teacher action → needs that permission
        if ($anyTeacher && !auth()->user()->can('Create subject-teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign subject teachers.',
            ], 403);
        }

        $rules = [
            'subjects'                => 'required|array|min:1|max:50',
            'subjects.*.subject'      => 'required|unique:subject,subject',
            'subjects.*.subject_code' => 'required|min:3|unique:subject,subject_code',
            'subjects.*.remark'       => 'required',
            'subjects.*.staffid'      => 'nullable|exists:users,id',
        ];

        if ($anyTeacher) {
            $rules['termid']    = 'required|array|min:1';
            $rules['termid.*']  = 'exists:schoolterm,id';
            $rules['sessionid'] = 'required|exists:schoolsession,id';
        }

        $validator = Validator::make($request->all(), $rules, [
            'subjects.required'                => 'Please add at least one subject!',
            'subjects.min'                     => 'Please add at least one subject!',
            'subjects.max'                     => 'You can add at most 50 subjects at a time.',
            'subjects.*.subject.required'      => 'Please enter a subject name!',
            'subjects.*.subject.unique'        => 'This subject name is already taken!',
            'subjects.*.subject_code.required' => 'Please enter a subject code!',
            'subjects.*.subject_code.min'      => 'Subject code must be at least 3 characters!',
            'subjects.*.subject_code.unique'   => 'This subject code is already taken!',
            'subjects.*.remark.required'       => 'Please enter a remark!',
            'subjects.*.staffid.exists'        => 'Selected teacher does not exist!',
            'termid.required'                  => 'Please select at least one term for the teacher assignment.',
            'termid.min'                       => 'Please select at least one term for the teacher assignment.',
            'termid.*.exists'                  => 'One or more selected terms do not exist.',
            'sessionid.required'               => 'Please select a session for the teacher assignment.',
            'sessionid.exists'                 => 'Selected session does not exist.',
        ]);

        // Duplicates inside the same submission (case-insensitive)
        $validator->after(function ($v) use ($rows) {
            $seenNames = [];
            $seenCodes = [];
            foreach ($rows as $i => $row) {
                $name = mb_strtolower($row['subject']);
                $code = mb_strtolower($row['subject_code']);

                if ($name !== '') {
                    if (isset($seenNames[$name])) {
                        $v->errors()->add("subjects.$i.subject", 'Duplicate subject name in this list!');
                    }
                    $seenNames[$name] = true;
                }
                if ($code !== '') {
                    if (isset($seenCodes[$code])) {
                        $v->errors()->add("subjects.$i.subject_code", 'Duplicate subject code in this list!');
                    }
                    $seenCodes[$code] = true;
                }
            }
        });

        if ($validator->fails()) {
            $firstKey = $validator->errors()->keys()[0] ?? null;
            $prefix   = ($firstKey && preg_match('/^subjects\.(\d+)\./', $firstKey, $m))
                ? 'Row ' . ((int) $m[1] + 1) . ': '
                : '';

            return response()->json([
                'success' => false,
                'message' => $prefix . $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $created     = [];
            $assignments = 0;
            $termIds     = $anyTeacher ? array_values(array_unique($request->input('termid', []))) : [];
            $sessionId   = $anyTeacher ? $request->input('sessionid') : null;

            foreach ($rows as $row) {
                $subject = Subject::create([
                    'subject'      => $row['subject'],
                    'subject_code' => $row['subject_code'],
                    'remark'       => $row['remark'],
                ]);
                $created[] = $subject;

                // Brand-new subject → no existing assignment can conflict
                if ($row['staffid'] !== null) {
                    foreach ($termIds as $termId) {
                        SubjectTeacher::create([
                            'staffid'   => $row['staffid'],
                            'subjectid' => $subject->id,
                            'termid'    => $termId,
                            'sessionid' => $sessionId,
                        ]);
                        $assignments++;
                    }
                }
            }

            DB::commit();

            Log::info('Subjects Created:', ['subjects' => count($created), 'assignments' => $assignments]);

            $message = count($created) . ' subject(s) added successfully.';
            if ($assignments > 0) {
                $message .= " {$assignments} teacher assignment(s) created.";
            }

            return response()->json([
                'success'     => true,
                'message'     => $message,
                'data'        => $created,
                'assignments' => $assignments,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating subjects:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subjects: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|unique:subject,subject,' . $id,
            'subject_code' => 'required|min:3|unique:subject,subject_code,' . $id,
            'remark' => 'required',
        ], [
            'subject.required' => 'Please enter a subject name!',
            'subject.unique' => 'This subject name is already taken!',
            'subject_code.required' => 'Please enter a subject code!',
            'subject_code.min' => 'Subject code must be at least 3 characters!',
            'subject_code.unique' => 'This subject code is already taken!',
            'remark.required' => 'Please enter a remark!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subject = Subject::find($id);
            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found.'
                ], 404);
            }

            $subject->update([
                'subject' => $request->input('subject'),
                'subject_code' => $request->input('subject_code'),
                'remark' => $request->input('remark'),
            ]);

            Log::info('Subject Updated:', $subject->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully.',
                'data' => $subject
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating subject:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subject: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY (single)
    // =========================================================================

    public function destroy($id)
    {
        try {
            $subject = Subject::find($id);
            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found.'
                ], 404);
            }

            // Check if subject is being used
            $inUse = DB::table('subjectteacher')->where('subjectid', $id)->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject is being used by one or more subject teachers and cannot be deleted.'
                ], 422);
            }

            $subject->delete();

            Log::info('Subject Deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting subject:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DELETE SUBJECT (AJAX)
    // =========================================================================

    public function deletesubject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subjectid' => 'required|exists:subject,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $subject = Subject::find($request->subjectid);

            // Check if subject is being used
            $inUse = DB::table('subjectteacher')->where('subjectid', $request->subjectid)->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject is being used by one or more subject teachers and cannot be deleted.'
                ], 422);
            }

            $subject->delete();

            Log::info('Subject Deleted via AJAX:', ['id' => $request->subjectid]);

            return response()->json([
                'success' => true,
                'message' => 'Subject has been removed.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting subject via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject: ' . $e->getMessage()
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
                    'message' => 'No subjects selected.'
                ], 400);
            }

            $existingIds = Subject::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds = array_diff($ids, $existingIds);

            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected subjects do not exist.'
                ], 400);
            }

            // Check if any are in use
            $inUse = DB::table('subjectteacher')
                ->whereIn('subjectid', $ids)
                ->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some subjects are being used by subject teachers and cannot be deleted.'
                ], 422);
            }

            DB::beginTransaction();
            $deleted = Subject::whereIn('id', $ids)->delete();
            DB::commit();

            Log::info('Bulk delete completed', [
                'total' => count($ids),
                'deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' subject(s) deleted successfully.',
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
                'message' => 'Error deleting subjects: ' . $e->getMessage()
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
