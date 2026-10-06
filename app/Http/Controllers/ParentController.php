<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ParentRegistration;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ParentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View parent', ['only' => ['index', 'show', 'getParentsOptimized']]);
        $this->middleware('permission:Create parent', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update parent', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete parent', ['only' => ['destroy', 'destroyMultiple']]);
    }

    /**
     * Display a listing of parents with optimized server-side pagination.
     */
    public function index(Request $request)
    {
        $pagetitle = "Parent / Guardian Management";

        // Get filter options
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass, ' - ', schoolarm.arm) as class_display")
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $schoolterms = Schoolterm::select('id', 'term as name')->get();
        $schoolsessions = Schoolsession::select('id', 'session as name')->get();

        // Get statistics
        $totalParents = ParentRegistration::count();
        $parentsWithFather = ParentRegistration::whereNotNull('father')->where('father', '!=', '')->count();
        $parentsWithMother = ParentRegistration::whereNotNull('mother')->where('mother', '!=', '')->count();
        $parentsWithBoth = ParentRegistration::whereNotNull('father')->where('father', '!=', '')
            ->whereNotNull('mother')->where('mother', '!=', '')->count();

        return view('parent.index', compact(
            'pagetitle',
            'schoolclasses',
            'schoolterms',
            'schoolsessions',
            'totalParents',
            'parentsWithFather',
            'parentsWithMother',
            'parentsWithBoth'
        ));
    }

    /**
     * Get parents with server-side pagination and filtering.
     */
    public function getParentsOptimized(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
            $classId = $request->get('class_id', 'all');
            $termId = $request->get('term_id', 'all');
            $sessionId = $request->get('session_id', 'all');
            $hasPhone = $request->get('has_phone', 'all');

            // Build ID query first
            $idQuery = ParentRegistration::query()
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'parentRegistration.studentId')
                ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
                ->select('parentRegistration.id');

            // Search filter
            if (!empty($search)) {
                $idQuery->where(function ($q) use ($search) {
                    $q->where('parentRegistration.father', 'LIKE', "%{$search}%")
                      ->orWhere('parentRegistration.mother', 'LIKE', "%{$search}%")
                      ->orWhere('parentRegistration.father_phone', 'LIKE', "%{$search}%")
                      ->orWhere('parentRegistration.mother_phone', 'LIKE', "%{$search}%")
                      ->orWhere('parentRegistration.parent_email', 'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.admissionNo', 'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.firstname', 'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.lastname', 'LIKE', "%{$search}%");
                });
            }

            // Class filter
            if ($classId !== 'all' && !empty($classId)) {
                $idQuery->where('studentclass.schoolclassid', $classId);
            }

            // Term filter
            if ($termId !== 'all' && !empty($termId)) {
                $idQuery->where('studentclass.termid', $termId);
            }

            // Session filter
            if ($sessionId !== 'all' && !empty($sessionId)) {
                $idQuery->where('studentclass.sessionid', $sessionId);
            }

            // Phone filter
            if ($hasPhone !== 'all') {
                if ($hasPhone === 'has_father_phone') {
                    $idQuery->whereNotNull('parentRegistration.father_phone')
                            ->where('parentRegistration.father_phone', '!=', '');
                } elseif ($hasPhone === 'has_mother_phone') {
                    $idQuery->whereNotNull('parentRegistration.mother_phone')
                            ->where('parentRegistration.mother_phone', '!=', '');
                } elseif ($hasPhone === 'has_any_phone') {
                    $idQuery->where(function ($q) {
                        $q->whereNotNull('parentRegistration.father_phone')->where('father_phone', '!=', '')
                          ->orWhereNotNull('parentRegistration.mother_phone')->where('mother_phone', '!=', '');
                    });
                } elseif ($hasPhone === 'no_phone') {
                    $idQuery->where(function ($q) {
                        $q->whereNull('parentRegistration.father_phone')->orWhere('father_phone', '=', '')
                          ->whereNull('parentRegistration.mother_phone')->orWhere('mother_phone', '=', '');
                    });
                }
            }

            $idQuery->groupBy('parentRegistration.id');

            $paginatedIds = $idQuery->paginate($perPage, ['parentRegistration.id'], 'page', $request->get('page', 1));
            $parentIds = $paginatedIds->pluck('id')->toArray();

            if (empty($parentIds)) {
                return response()->json([
                    'success' => true,
                    'data' => new \Illuminate\Pagination\LengthAwarePaginator(
                        [], 0, $perPage,
                        $request->get('page', 1),
                        ['path' => $request->url(), 'query' => $request->query()]
                    )
                ]);
            }

            // Get full parent data
            $parents = ParentRegistration::query()
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'parentRegistration.studentId')
                ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->whereIn('parentRegistration.id', $parentIds)
                ->select([
                    'parentRegistration.*',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.student_status',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'schoolterm.term as term_name',
                    'schoolsession.session as session_name',
                    'studentpicture.picture',
                ])
                ->orderBy('parentRegistration.id', 'desc')
                ->get();

            $groupedParents = $parents->groupBy('id')->map(fn($g) => $g->first())->values();

            $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
                $groupedParents,
                $paginatedIds->total(),
                $paginatedIds->perPage(),
                $paginatedIds->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Process data for display
            $processedParents = $paginatedData->getCollection()->map(function ($parent) {
                return [
                    'id' => $parent->id,
                    'student_id' => $parent->studentId,
                    'admissionNo' => $parent->admissionNo,
                    'student_name' => trim(($parent->lastname ?? '') . ' ' . ($parent->firstname ?? '')),
                    'student_gender' => $parent->gender,
                    'student_status' => $parent->student_status,
                    'student_picture' => $parent->picture,
                    'father' => $parent->father,
                    'father_phone' => $parent->father_phone,
                    'father_occupation' => $parent->father_occupation,
                    'father_title' => $parent->father_title,
                    'father_city' => $parent->father_city,
                    'mother' => $parent->mother,
                    'mother_phone' => $parent->mother_phone,
                    'mother_occupation' => $parent->mother_occupation,
                    'mother_title' => $parent->mother_title,
                    'parent_email' => $parent->parent_email,
                    'parent_address' => $parent->parent_address,
                    'office_address' => $parent->office_address,
                    'schoolclass' => $parent->schoolclass,
                    'arm' => $parent->arm,
                    'term_name' => $parent->term_name,
                    'session_name' => $parent->session_name,
                    'created_at' => $parent->created_at,
                    'updated_at' => $parent->updated_at,
                ];
            });

            $paginatedData->setCollection($processedParents);

            // Get statistics for the filtered set
            $stats = [
                'total' => $processedParents->count(),
                'has_father' => $processedParents->filter(fn($p) => !empty($p['father']))->count(),
                'has_mother' => $processedParents->filter(fn($p) => !empty($p['mother']))->count(),
                'has_both' => $processedParents->filter(fn($p) => !empty($p['father']) && !empty($p['mother']))->count(),
                'has_father_phone' => $processedParents->filter(fn($p) => !empty($p['father_phone']))->count(),
                'has_mother_phone' => $processedParents->filter(fn($p) => !empty($p['mother_phone']))->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $paginatedData,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getParentsOptimized: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch parents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new parent record.
     */
    public function create()
    {
        // Not used - using modal instead
        return response()->json(['success' => false, 'message' => 'Use modal instead']);
    }

    /**
     * Store a newly created parent record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'studentId' => 'required|exists:studentRegistration,id|unique:parentRegistration,studentId',
            'father' => 'nullable|string|max:255',
            'mother' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:20',
            'mother_phone' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:255',
            'father_city' => 'nullable|string|max:255',
            'father_title' => 'nullable|in:Mr,Dr,Prof,Chief',
            'mother_title' => 'nullable|in:Mrs,Dr,Prof,Chief',
            'office_address' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'parent_address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parent = ParentRegistration::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Parent record created successfully',
                'parent' => $parent
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error creating parent: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to create parent record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified parent record.
     */
    public function show($id)
    {
        try {
            $parent = ParentRegistration::where('parentRegistration.id', $id)
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'parentRegistration.studentId')
                ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->select([
                    'parentRegistration.*',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.dateofbirth',
                    'studentRegistration.student_status',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'schoolterm.term as term_name',
                    'schoolsession.session as session_name',
                    'studentpicture.picture',
                ])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'parent' => $parent
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parent record not found'
            ], 404);
        }
    }

    /**
     * Show the form for editing a parent record.
     */
    public function edit($id)
    {
        try {
            $parent = ParentRegistration::where('parentRegistration.id', $id)
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'parentRegistration.studentId')
                ->select([
                    'parentRegistration.*',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                ])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'parent' => $parent
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parent record not found'
            ], 404);
        }
    }

    /**
     * Update the specified parent record.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'father' => 'nullable|string|max:255',
            'mother' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:20',
            'mother_phone' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:255',
            'father_city' => 'nullable|string|max:255',
            'father_title' => 'nullable|in:Mr,Dr,Prof,Chief',
            'mother_title' => 'nullable|in:Mrs,Dr,Prof,Chief',
            'office_address' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'parent_address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parent = ParentRegistration::findOrFail($id);
            $parent->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Parent record updated successfully',
                'parent' => $parent
            ]);

        } catch (\Exception $e) {
            Log::error("Error updating parent: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to update parent record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified parent record.
     */
    public function destroy($id)
    {
        try {
            $parent = ParentRegistration::findOrFail($id);
            $parent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Parent record deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Error deleting parent: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete parent record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete multiple parent records.
     */
    public function destroyMultiple(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array|exists:parentRegistration,id'
            ]);

            $count = ParentRegistration::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => $count . ' parent record(s) deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk delete error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete parent records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students without parent records (for selection in create modal).
     */
    public function getStudentsWithoutParent(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $query = Student::select('id', 'admissionNo', 'firstname', 'lastname', 'othername')
                ->whereDoesntHave('parent');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('admissionNo', 'LIKE', "%{$search}%")
                      ->orWhere('firstname', 'LIKE', "%{$search}%")
                      ->orWhere('lastname', 'LIKE', "%{$search}%")
                      ->orWhereRaw("CONCAT(lastname, ' ', firstname) LIKE ?", ["%{$search}%"]);
                });
            }

            $students = $query->orderBy('lastname')->limit(50)->get();

            return response()->json([
                'success' => true,
                'students' => $students
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch students: ' . $e->getMessage()
            ], 500);
        }
    }
}