<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiblingGroup;
use App\Models\Student;
use App\Models\DiscountAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SiblingGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View sibling groups', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create sibling group', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update sibling group', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete sibling group', ['only' => ['destroy']]);
        $this->middleware('permission:Apply sibling discount', ['only' => ['applyDiscount']]);
    }

    public function index(Request $request)
    {
        $pagetitle = 'Sibling Groups Management';

        if ($request->ajax() || $request->wantsJson()) {
            $groups = SiblingGroup::with(['students', 'discountAssignments'])
                ->orderBy('created_at', 'desc')
                ->get();

            $totalSavings = DiscountAssignment::whereNotNull('sibling_group_id')
                ->where('status', 'active')
                ->sum('value');

            $stats = [
                'total_groups' => $groups->count(),
                'total_students' => $groups->sum('total_children'),
                'total_savings' => $totalSavings,
                'active_discounts' => DiscountAssignment::whereNotNull('sibling_group_id')->where('status', 'active')->count(),
            ];

            $formattedGroups = $groups->map(function($group) {
                return [
                    'id' => $group->id,
                    'group_no' => $group->group_no,
                    'family_name' => $group->family_name,
                    'parent_phone' => $group->parent_phone,
                    'parent_email' => $group->parent_email,
                    'address' => $group->address,
                    'total_children' => $group->students->count(),
                    'discount_type' => $group->discount_type,
                    'discount_value' => $group->discount_value,
                    'has_discount' => !is_null($group->discount_value),
                    'students' => $group->students->map(function($student) {
                        return [
                            'id' => $student->id,
                            'name' => $student->firstname . ' ' . $student->lastname,
                            'admission_no' => $student->admissionNo,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedGroups,
                'stats' => $stats
            ]);
        }

        return view('sibling.index', compact('pagetitle'));
    }

    public function create()
    {
        $pagetitle = 'Create Family Group';
        return view('sibling.create', compact('pagetitle'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'family_name' => 'required|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:studentRegistration,id',
            'discount_type' => 'nullable|in:percentage,fixed_per_child',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $groupCount = SiblingGroup::count();
            $groupNo = 'SG-' . date('Y') . '-' . str_pad($groupCount + 1, 4, '0', STR_PAD_LEFT);

            $group = SiblingGroup::create([
                'group_no' => $groupNo,
                'family_name' => $request->family_name,
                'parent_phone' => $request->parent_phone,
                'parent_email' => $request->parent_email,
                'address' => $request->address,
                'total_children' => count($request->student_ids),
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
            ]);

            // Attach students
            foreach ($request->student_ids as $studentId) {
                DB::table('sibling_group_students')->insert([
                    'sibling_group_id' => $group->id,
                    'student_id' => $studentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($request->discount_type && $request->discount_value) {
                $this->applySiblingDiscountToGroup($group, $request->student_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Family group created successfully!',
                'data' => $group
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store sibling group error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create group: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $group = SiblingGroup::find($id);

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found'
            ], 404);
        }

        $students = DB::table('sibling_group_students')
            ->where('sibling_group_id', $id)
            ->join('studentRegistration', 'sibling_group_students.student_id', '=', 'studentRegistration.id')
            ->select('studentRegistration.id', 'studentRegistration.firstname', 'studentRegistration.lastname', 'studentRegistration.admissionNo')
            ->get();

        $formattedStudents = $students->map(function($student) {
            $classInfo = $this->getStudentClassInfo($student->id);
            $classDisplay = $classInfo['class'];
            if ($classInfo['arm']) {
                $classDisplay .= ' ' . $classInfo['arm'];
            }

            return [
                'id' => $student->id,
                'name' => $student->firstname . ' ' . $student->lastname,
                'admission_no' => $student->admissionNo,
                'class' => $classDisplay,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'group' => $group,
                'students' => $formattedStudents,
                'total_children' => $formattedStudents->count()
            ]
        ]);
    }

    public function edit($id)
    {
        // Get group directly from database
        $groupData = DB::table('sibling_groups')->where('id', $id)->first();

        if (!$groupData) {
            abort(404, 'Group not found');
        }

        // Get students from pivot table with their details
        $students = DB::table('sibling_group_students')
            ->where('sibling_group_id', $id)
            ->join('studentRegistration', 'sibling_group_students.student_id', '=', 'studentRegistration.id')
            ->leftJoin('studentpicture', 'studentRegistration.id', '=', 'studentpicture.studentid')
            ->select(
                'studentRegistration.id',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.admissionNo',
                'studentpicture.picture as picture'
            )
            ->get();

        // Format initial students for JavaScript (ALWAYS return an array)
        $initialStudents = [];

        foreach ($students as $student) {
            // Get picture URL
            $pictureUrl = null;
            if ($student->picture && $student->picture != 'unnamed.jpg') {
                $pictureUrl = asset('storage/images/student_avatars/' . $student->picture);
            }

            // Get class info
            $classInfo = $this->getStudentClassInfo($student->id);
            $classDisplay = $classInfo['class'];
            if ($classInfo['arm']) {
                $classDisplay .= ' ' . $classInfo['arm'];
            }

            $initialStudents[] = [
                'id' => $student->id,
                'firstname' => $student->firstname,
                'lastname' => $student->lastname,
                'admission_no' => $student->admissionNo,
                'class' => $classDisplay,
                'picture' => $pictureUrl,
                'initials' => strtoupper(substr($student->firstname, 0, 1) . substr($student->lastname, 0, 1)),
            ];
        }

        // Create group object for the view
        $group = new \stdClass();
        $group->id = $groupData->id;
        $group->group_no = $groupData->group_no;
        $group->family_name = $groupData->family_name;
        $group->parent_phone = $groupData->parent_phone;
        $group->parent_email = $groupData->parent_email;
        $group->address = $groupData->address;
        $group->discount_type = $groupData->discount_type;
        $group->discount_value = $groupData->discount_value;
        $group->students = $students;

        $pagetitle = 'Edit Family Group - ' . $groupData->family_name;

        // Always pass $initialStudents explicitly — never leave it undefined
        return view('sibling.edit', [
            'group'           => $group,
            'pagetitle'       => $pagetitle,
            'initialStudents' => $initialStudents, // always an array, even if empty
        ]);
    }

    public function update(Request $request, $id)
    {
        $group = SiblingGroup::find($id);
        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'family_name' => 'required|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:studentRegistration,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $group->update([
                'family_name' => $request->family_name,
                'parent_phone' => $request->parent_phone,
                'parent_email' => $request->parent_email,
                'address' => $request->address,
                'total_children' => count($request->student_ids),
            ]);

            // Sync students
            DB::table('sibling_group_students')->where('sibling_group_id', $id)->delete();

            foreach ($request->student_ids as $studentId) {
                DB::table('sibling_group_students')->insert([
                    'sibling_group_id' => $id,
                    'student_id' => $studentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Family group updated successfully!',
                'data' => $group
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update sibling group error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update group: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $group = SiblingGroup::find($id);
        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('sibling_group_students')->where('sibling_group_id', $id)->delete();
            DiscountAssignment::where('sibling_group_id', $id)->delete();
            $group->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Family group deleted successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete sibling group error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete group: ' . $e->getMessage()
            ], 500);
        }
    }

    public function applyDiscount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:sibling_groups,id',
            'discount_type' => 'required|in:percentage,fixed_per_child',
            'discount_value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $group = SiblingGroup::find($request->group_id);

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found'
            ], 404);
        }

        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Percentage discount cannot exceed 100%'
            ], 422);
        }

        $group->update([
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
        ]);

        $studentIds = DB::table('sibling_group_students')
            ->where('sibling_group_id', $group->id)
            ->pluck('student_id')
            ->toArray();

        $result = $this->applySiblingDiscountToGroup($group, $studentIds);

        return response()->json([
            'success' => true,
            'message' => "Sibling discount applied to {$result['count']} student(s)!",
            'data' => $result
        ]);
    }

    private function applySiblingDiscountToGroup($group, $studentIds)
    {
        $applied = 0;

        foreach ($studentIds as $index => $studentId) {
            $discountValue = $group->discount_value;
            if ($group->discount_type === 'percentage' && $index > 0) {
                $discountValue = min($group->discount_value + ($index * 5), 50);
            }

            // Delete existing sibling discount assignments for this student and group
            DiscountAssignment::where('student_id', $studentId)
                ->where('sibling_group_id', $group->id)
                ->delete();

            // Create new assignment
            $assignment = new DiscountAssignment();
            $assignment->student_id = $studentId;
            $assignment->sibling_group_id = $group->id;
            $assignment->value_type = $group->discount_type === 'percentage' ? 'percentage' : 'fixed_amount';
            $assignment->value = $discountValue;
            $assignment->status = 'active';
            $assignment->effective_from = now();
            $assignment->effective_to = now()->addYear();
            $assignment->assigned_by = Auth::id();
            $assignment->save();

            $applied++;
        }

        return ['count' => $applied];
    }

    /**
     * Get student class information with fallback
     */
    private function getStudentClassInfo($studentId)
    {
        $result = [
            'class' => 'N/A',
            'arm' => '',
            'term' => 'N/A',
            'session' => 'N/A',
            'has_current_term' => false,
        ];

        try {
            // Try to get from StudentCurrentTerm
            $currentTerm = DB::table('student_current_term')
                ->where('studentId', $studentId)
                ->where('is_current', true)
                ->first();

            if ($currentTerm) {
                $result['has_current_term'] = true;

                $class = DB::table('schoolclass')->where('id', $currentTerm->schoolclassId)->first();
                if ($class) {
                    $result['class'] = $class->schoolclass ?? 'N/A';
                    if (isset($class->arm) && $class->arm) {
                        $arm = DB::table('schoolarm')->where('id', $class->arm)->first();
                        $result['arm'] = $arm->arm ?? '';
                    }
                }

                $term = DB::table('schoolterm')->where('id', $currentTerm->termId)->first();
                $result['term'] = $term->term ?? 'N/A';

                $session = DB::table('schoolsession')->where('id', $currentTerm->sessionId)->first();
                $result['session'] = $session->session ?? 'N/A';

                return $result;
            }

            // Fallback to Studentclass
            $studentClass = DB::table('studentclass')
                ->where('studentId', $studentId)
                ->orderBy('sessionid', 'desc')
                ->orderBy('termid', 'desc')
                ->first();

            if ($studentClass) {
                $class = DB::table('schoolclass')->where('id', $studentClass->schoolclassid)->first();
                if ($class) {
                    $result['class'] = $class->schoolclass ?? 'N/A';
                    if (isset($class->arm) && $class->arm) {
                        $arm = DB::table('schoolarm')->where('id', $class->arm)->first();
                        $result['arm'] = $arm->arm ?? '';
                    }
                }

                $term = DB::table('schoolterm')->where('id', $studentClass->termid)->first();
                $result['term'] = $term->term ?? 'N/A';

                $session = DB::table('schoolsession')->where('id', $studentClass->sessionid)->first();
                $result['session'] = $session->session ?? 'N/A';
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error getting student class info: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Search students for adding to group (AJAX)
     */
    public function searchStudents(Request $request)
    {
        try {
            $search = $request->input('q', '');

            if (strlen($search) < 2) {
                return response()->json([
                    'success' => true,
                    'students' => [],
                    'results' => []
                ]);
            }

            $students = Student::where('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('admissionNo', 'like', "%{$search}%")
                ->limit(20)
                ->get(['id', 'firstname', 'lastname', 'admissionNo']);

            $formattedStudents = $students->map(function($student) {
                $initials = strtoupper(substr($student->firstname, 0, 1) . substr($student->lastname, 0, 1));

                $pictureUrl = null;
                $picture = DB::table('studentpicture')
                    ->where('studentid', $student->id)
                    ->first();
                if ($picture && $picture->picture && $picture->picture != 'unnamed.jpg') {
                    $pictureUrl = asset('storage/images/student_avatars/' . $picture->picture);
                }

                $classInfo = $this->getStudentClassInfo($student->id);
                $classDisplay = $classInfo['class'];
                if ($classInfo['arm']) {
                    $classDisplay .= ' ' . $classInfo['arm'];
                }

                return [
                    'id' => $student->id,
                    'text' => $student->firstname . ' ' . $student->lastname . ' (' . $student->admissionNo . ')',
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'admission_no' => $student->admissionNo,
                    'class' => $classDisplay,
                    'picture' => $pictureUrl,
                    'initials' => $initials,
                ];
            });

            return response()->json([
                'success' => true,
                'students' => $formattedStudents,
                'results' => $formattedStudents->map(function($s) {
                    return [
                        'id' => $s['id'],
                        'text' => $s['text']
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Search students error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
                'students' => []
            ], 500);
        }
    }

    public function getStudentSiblings($studentId)
    {
        try {
            $group = DB::table('sibling_group_students')
                ->where('student_id', $studentId)
                ->first();

            if (!$group) {
                return response()->json([
                    'success' => true,
                    'siblings' => [],
                    'count' => 0
                ]);
            }

            $siblings = DB::table('sibling_group_students')
                ->where('sibling_group_id', $group->sibling_group_id)
                ->where('student_id', '!=', $studentId)
                ->join('studentRegistration', 'sibling_group_students.student_id', '=', 'studentRegistration.id')
                ->select('studentRegistration.id', 'studentRegistration.firstname', 'studentRegistration.lastname', 'studentRegistration.admissionNo')
                ->get();

            return response()->json([
                'success' => true,
                'siblings' => $siblings->map(function($sibling) {
                    return [
                        'id' => $sibling->id,
                        'name' => $sibling->firstname . ' ' . $sibling->lastname,
                        'admission_no' => $sibling->admissionNo,
                    ];
                }),
                'count' => $siblings->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Get student siblings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get siblings',
                'siblings' => [],
                'count' => 0
            ]);
        }
    }
}
