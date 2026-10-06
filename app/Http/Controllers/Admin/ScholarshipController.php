<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Models\ScholarshipType;
use App\Models\ScholarshipAssignment;
use App\Models\ScholarshipApplication;
use App\Models\Student;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Services\Scholarship\ScholarshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ScholarshipController extends Controller
{
    protected $scholarshipService;

    public function __construct(ScholarshipService $scholarshipService)
    {
        $this->scholarshipService = $scholarshipService;

        $this->middleware('permission:View scholarship',   ['only' => ['index', 'show', 'showAssignments', 'showApplications']]);
        $this->middleware('permission:Create scholarship', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update scholarship', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete scholarship', ['only' => ['destroy', 'bulkDestroy']]);
        $this->middleware('permission:Approve scholarship',['only' => ['approve', 'approveApplication']]);
        $this->middleware('permission:Revoke scholarship', ['only' => ['revoke']]);
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Scholarship::with(['type', 'createdBy', 'approvedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title',          'like', "%{$search}%")
                  ->orWhere('scholarship_no','like', "%{$search}%")
                  ->orWhere('description',  'like', "%{$search}%");
            });
        }

        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('type_id')) $query->where('scholarship_type_id', $request->type_id);

        $query->orderBy($request->get('sort', 'id'), $request->get('order', 'desc'));

        $scholarships       = $query->paginate(15);
        $totalScholarships  = Scholarship::count();
        $activeScholarships = Scholarship::where('status', 'active')->count();
        $totalAwarded       = ScholarshipAssignment::where('status', 'active')->sum('value');
        $totalStudents      = ScholarshipAssignment::where('status', 'active')->distinct('student_id')->count('student_id');
        $scholarshipTypes   = ScholarshipType::where('is_active', true)->get();
        $pagetitle          = 'Scholarship Management';

        return view('admin.scholarship.index', compact(
            'pagetitle', 'scholarships', 'scholarshipTypes',
            'totalScholarships', 'activeScholarships', 'totalAwarded', 'totalStudents'
        ));
    }

    // ── Create ────────────────────────────────────────────────────────────

    public function create()
    {
        $scholarshipTypes = ScholarshipType::where('is_active', true)->get();
        $classes          = Schoolclass::with('armRelation')->get();
        $terms            = Schoolterm::all();
        $sessions         = Schoolsession::all();
        $pagetitle        = 'Create New Scholarship';

        return view('admin.scholarship.create', compact(
            'pagetitle', 'scholarshipTypes', 'classes', 'terms', 'sessions'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'scholarship_type_id'   => 'required|exists:scholarship_types,id',
                'title'                 => 'required|string|max:255',
                'description'           => 'nullable|string',
                'value_type'            => 'required|in:percentage,fixed_amount',
                'value'                 => 'required|numeric|min:0.01',
                'cap_amount'            => 'nullable|numeric|min:0',
                'requires_application'  => 'boolean',
                'eligible_classes'      => 'nullable|array',
                'eligible_classes.*'    => 'exists:schoolclass,id',
                'eligible_status_ids'   => 'nullable|array',
                'excluded_bill_categories' => 'nullable|array',
                'effective_from'        => 'required|date',
                'effective_to'          => 'nullable|date|after:effective_from',
                'max_recipients'        => 'nullable|integer|min:1',
                'renewal_frequency'     => 'nullable|integer|min:1',
                'budget_amount'         => 'nullable|numeric|min:0',
                'status'                => 'required|in:draft,active,expired,suspended',
            ]);

            $validated['scholarship_no']        = $this->generateScholarshipNumber();
            $validated['created_by']            = auth()->id();
            $validated['requires_application']  = $request->boolean('requires_application');
            $validated['eligible_classes']      = json_encode($validated['eligible_classes']         ?? []);
            $validated['eligible_status_ids']   = json_encode($validated['eligible_status_ids']      ?? []);
            $validated['excluded_bill_categories'] = json_encode($validated['excluded_bill_categories'] ?? []);

            $scholarship = Scholarship::create($validated);

            return response()->json([
                'success'     => true,
                'message'     => 'Scholarship created successfully.',
                'scholarship' => $scholarship,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating scholarship: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create scholarship. Please try again.',
            ], 500);
        }
    }

    // ── Show ──────────────────────────────────────────────────────────────

    public function show($id)
    {
        $scholarship = Scholarship::with(['type', 'createdBy', 'approvedBy', 'assignments.student'])
            ->findOrFail($id);

        $assignments  = $scholarship->assignments()->with('student')->orderBy('created_at', 'desc')->paginate(20);
        $totalAmount  = $scholarship->assignments()->sum('value');
        $activeCount  = $scholarship->assignments()->where('status', 'active')->count();
        $pagetitle    = 'Scholarship Details: ' . $scholarship->title;

        return view('admin.scholarship.show', compact(
            'pagetitle', 'scholarship', 'assignments', 'totalAmount', 'activeCount'
        ));
    }

    // ── Edit ──────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $scholarship      = Scholarship::findOrFail($id);
        $scholarshipTypes = ScholarshipType::where('is_active', true)->get();
        $classes          = Schoolclass::with('armRelation')->get();
        $pagetitle        = 'Edit Scholarship: ' . $scholarship->title;

        return view('admin.scholarship.edit', compact(
            'pagetitle', 'scholarship', 'scholarshipTypes', 'classes'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        try {
            $scholarship = Scholarship::findOrFail($id);

            $validated = $request->validate([
                'scholarship_type_id'      => 'required|exists:scholarship_types,id',
                'title'                    => 'required|string|max:255',
                'description'              => 'nullable|string',
                'value_type'               => 'required|in:percentage,fixed_amount',
                'value'                    => 'required|numeric|min:0.01',
                'cap_amount'               => 'nullable|numeric|min:0',
                'requires_application'     => 'boolean',
                'eligible_classes'         => 'nullable|array',
                'eligible_status_ids'      => 'nullable|array',
                'excluded_bill_categories' => 'nullable|array',
                'effective_from'           => 'required|date',
                'effective_to'             => 'nullable|date|after:effective_from',
                'max_recipients'           => 'nullable|integer|min:1',
                'renewal_frequency'        => 'nullable|integer|min:1',
                'budget_amount'            => 'nullable|numeric|min:0',
                'status'                   => 'required|in:draft,active,expired,suspended',
            ]);

            $validated['eligible_classes']         = json_encode($validated['eligible_classes']         ?? []);
            $validated['eligible_status_ids']      = json_encode($validated['eligible_status_ids']      ?? []);
            $validated['excluded_bill_categories'] = json_encode($validated['excluded_bill_categories'] ?? []);

            $scholarship->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Scholarship updated successfully.',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating scholarship: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scholarship.',
            ], 500);
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy($id)
    {
        try {
            $scholarship = Scholarship::findOrFail($id);

            if ($scholarship->assignments()->where('status', 'active')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete scholarship with active assignments.',
                ], 400);
            }

            $scholarship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scholarship deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting scholarship: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scholarship.',
            ], 500);
        }
    }

    // ── Bulk Destroy ──────────────────────────────────────────────────────

    public function bulkDestroy(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No scholarships selected.',
                ], 400);
            }

            $scholarshipsWithActive = Scholarship::whereIn('id', $ids)
                ->whereHas('assignments', fn($q) => $q->where('status', 'active'))
                ->count();

            if ($scholarshipsWithActive > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete {$scholarshipsWithActive} scholarship(s) with active assignments.",
                ], 400);
            }

            $deleted = Scholarship::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} scholarship(s) deleted successfully.",
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting scholarships: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scholarships.',
            ], 500);
        }
    }

    // ── Approve ───────────────────────────────────────────────────────────

    public function approve($id)
    {
        try {
            $scholarship = Scholarship::findOrFail($id);
            $scholarship->update([
                'status'      => 'active',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Scholarship approved successfully.']);

        } catch (\Exception $e) {
            Log::error('Error approving scholarship: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to approve scholarship.'], 500);
        }
    }

    // ── Revoke ────────────────────────────────────────────────────────────

    public function revoke($id)
    {
        try {
            $scholarship = Scholarship::findOrFail($id);

            $scholarship->assignments()->where('status', 'active')->update([
                'status'            => 'revoked',
                'revocation_reason' => request()->input('reason', 'Scholarship revoked by administrator'),
            ]);

            $scholarship->update(['status' => 'suspended']);

            return response()->json(['success' => true, 'message' => 'Scholarship revoked successfully.']);

        } catch (\Exception $e) {
            Log::error('Error revoking scholarship: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to revoke scholarship.'], 500);
        }
    }

    // ── Show Assignments ──────────────────────────────────────────────────

    public function showAssignments(Request $request, $scholarshipId = null)
    {
        $query = ScholarshipAssignment::with(['scholarship', 'student', 'assignedBy', 'approvedBy']);

        if ($scholarshipId) {
            $query->where('scholarship_id', $scholarshipId);
            $scholarship = Scholarship::findOrFail($scholarshipId);
            $pagetitle   = 'Scholarship Assignments: ' . $scholarship->title;
        } else {
            $pagetitle = 'All Scholarship Assignments';
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('firstname',   'like', "%{$search}%")
                  ->orWhere('lastname',  'like', "%{$search}%")
                  ->orWhere('admissionNo','like', "%{$search}%");
            });
        }

        $assignments  = $query->orderBy('created_at', 'desc')->paginate(20);
        $statusCounts = [
            'pending'  => ScholarshipAssignment::where('status', 'pending')->count(),
            'approved' => ScholarshipAssignment::where('status', 'approved')->count(),
            'active'   => ScholarshipAssignment::where('status', 'active')->count(),
            'expired'  => ScholarshipAssignment::where('status', 'expired')->count(),
            'revoked'  => ScholarshipAssignment::where('status', 'revoked')->count(),
        ];
        $scholarships = Scholarship::whereIn('status', ['active', 'draft'])->get();

        return view('admin.scholarship.assignments', compact(
            'pagetitle', 'assignments', 'statusCounts', 'scholarshipId', 'scholarships'
        ));
    }

    // ── Show Applications ─────────────────────────────────────────────────

    public function showApplications(Request $request)
    {
        $query = ScholarshipApplication::with(['scholarship', 'student', 'reviewedBy']);

        if ($request->filled('status')) $query->where('status', $request->status);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('firstname',    'like', "%{$search}%")
                  ->orWhere('lastname',   'like', "%{$search}%")
                  ->orWhere('admissionNo','like', "%{$search}%");
            });
        }

        $applications = $query->orderBy('submitted_at', 'desc')->paginate(20);
        $statusCounts = [
            'draft'        => ScholarshipApplication::where('status', 'draft')->count(),
            'submitted'    => ScholarshipApplication::where('status', 'submitted')->count(),
            'under_review' => ScholarshipApplication::where('status', 'under_review')->count(),
            'approved'     => ScholarshipApplication::where('status', 'approved')->count(),
            'rejected'     => ScholarshipApplication::where('status', 'rejected')->count(),
        ];
        $pagetitle = 'Scholarship Applications';

        return view('admin.scholarship.applications', compact('pagetitle', 'applications', 'statusCounts'));
    }

    // ── Approve Application ───────────────────────────────────────────────

    public function approveApplication(Request $request, $applicationId)
    {
        try {
            $result = $this->scholarshipService->processApplication(
                $applicationId, 'approve', $request->input('notes')
            );

            if ($result) {
                return response()->json([
                    'success'    => true,
                    'message'    => 'Scholarship application approved and assigned successfully.',
                    'assignment' => $result,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to process application.'], 500);

        } catch (\Exception $e) {
            Log::error('Error approving application: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to approve application: ' . $e->getMessage()], 500);
        }
    }

    // ── Reject Application ────────────────────────────────────────────────

    public function rejectApplication(Request $request, $applicationId)
    {
        try {
            $request->validate(['rejection_reason' => 'required|string|min:5']);

            $this->scholarshipService->processApplication(
                $applicationId, 'reject', $request->input('rejection_reason')
            );

            return response()->json(['success' => true, 'message' => 'Scholarship application rejected.']);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error rejecting application: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to reject application.'], 500);
        }
    }

    // ── Assign to Student ─────────────────────────────────────────────────

    public function assignToStudent(Request $request)
    {
        try {
            $validated = $request->validate([
                'scholarship_id' => 'required|exists:scholarships,id',
                'student_id'     => 'required|exists:studentRegistration,id',
                'effective_from' => 'required|date',
                'effective_to'   => 'nullable|date|after:effective_from',
                'reason'         => 'nullable|string',
            ]);

            $scholarship = Scholarship::findOrFail($validated['scholarship_id']);

            $existing = ScholarshipAssignment::where('scholarship_id', $validated['scholarship_id'])
                ->where('student_id', $validated['student_id'])
                ->whereIn('status', ['active', 'pending', 'approved'])
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student already has an active/pending assignment for this scholarship.',
                ], 400);
            }

            $assignment = ScholarshipAssignment::create([
                'scholarship_id' => $validated['scholarship_id'],
                'student_id'     => $validated['student_id'],
                'status'         => 'active',
                'approved_at'    => now(),
                'effective_from' => $validated['effective_from'],
                'effective_to'   => $validated['effective_to'],
                'value_type'     => $scholarship->value_type,
                'value'          => $scholarship->value,
                'cap_amount'     => $scholarship->cap_amount,
                'reason'         => $validated['reason'] ?? null,
                'assigned_by'    => auth()->id(),
                'approved_by'    => auth()->id(),
            ]);

            if ($scholarship->value_type === 'fixed_amount') {
                $scholarship->increment('utilized_amount', $scholarship->value);
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Scholarship assigned successfully.',
                'assignment' => $assignment,
            ]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error assigning scholarship: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to assign scholarship.'], 500);
        }
    }

    // ── Revoke Assignment ─────────────────────────────────────────────────

    public function revokeAssignment(Request $request, $assignmentId)
    {
        try {
            $assignment = ScholarshipAssignment::findOrFail($assignmentId);

            $assignment->update([
                'status'            => 'revoked',
                'revocation_reason' => $request->input('reason', 'Revoked by administrator'),
                'effective_to'      => now(),
            ]);

            $scholarship = $assignment->scholarship;
            if ($assignment->value_type === 'fixed_amount') {
                $scholarship->decrement('utilized_amount', $assignment->value);
            }

            return response()->json(['success' => true, 'message' => 'Scholarship assignment revoked successfully.']);

        } catch (\Exception $e) {
            Log::error('Error revoking assignment: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to revoke assignment.'], 500);
        }
    }

    // ── Get Eligible Students (AJAX / Select2) ────────────────────────────
    public function getEligibleStudents(Request $request)
    {
        try {
            $scholarship       = null;
            $eligibleClasses   = [];
            $eligibleStatusIds = [];

            if ($request->filled('scholarship_id')) {
                $scholarship = Scholarship::find($request->scholarship_id);
                if ($scholarship) {
                    $eligibleClasses   = json_decode($scholarship->eligible_classes,    true) ?? [];
                    $eligibleStatusIds = json_decode($scholarship->eligible_status_ids, true) ?? [];
                }
            }

            $query = Student::with(['picture', 'currentTerm.schoolClass.armRelation']);

            // Apply class eligibility filter
            if (!empty($eligibleClasses)) {
                $query->whereHas('currentTerm', function ($q) use ($eligibleClasses) {
                    $q->whereIn('schoolclassId', $eligibleClasses);
                });
            }

            // Apply status filter
            if (!empty($eligibleStatusIds)) {
                $query->whereIn('statusId', $eligibleStatusIds);
            }

            // Apply search filter
            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function ($q) use ($search) {
                    $q->where('firstname',     'like', "%{$search}%")
                    ->orWhere('lastname',    'like', "%{$search}%")
                    ->orWhere('admissionNo', 'like', "%{$search}%");
                });
            }

            // Exclude students who already have this scholarship
            if ($scholarship) {
                $query->whereDoesntHave('scholarshipAssignments', function ($q) use ($scholarship) {
                    $q->where('scholarship_id', $scholarship->id)
                    ->whereIn('status', ['active', 'pending', 'approved']);
                });
            }

            $students = $query->select('id', 'firstname', 'lastname', 'admissionNo')
                ->orderBy('lastname')
                ->limit(50)
                ->get()
                ->map(function ($student) {
                    // Get current class information using the getCurrentTermInfo method
                    $currentTermInfo = $student->getCurrentTermInfo();
                    $currentClassDisplay = 'Not Assigned';

                    if ($currentTermInfo) {
                        $className = $currentTermInfo['current_class'] ?? '';
                        $armName = $currentTermInfo['current_class_arm'] ?? '';
                        $currentClassDisplay = trim($className . ' ' . $armName);
                        $currentClassDisplay = $currentClassDisplay ?: 'Not Assigned';
                    }

                    // Get avatar URL
                    $avatarUrl = null;
                    if ($student->picture && $student->picture->picture) {
                        // Check if the picture path already includes 'storage/'
                        $picturePath = $student->picture->picture;
                        if (str_starts_with($picturePath, 'student_avatars/') || str_starts_with($picturePath, 'storage/')) {
                            $avatarUrl = asset($picturePath);
                        } else {
                            $avatarUrl = asset('storage/student_avatars/' . $picturePath);
                        }
                    }

                    return [
                        'id'            => $student->id,
                        'firstname'     => $student->firstname,
                        'lastname'      => $student->lastname,
                        'admissionNo'   => $student->admissionNo,
                        'current_class' => $currentClassDisplay,
                        'avatar'        => $avatarUrl,
                    ];
                });

            return response()->json(['success' => true, 'students' => $students]);

        } catch (\Exception $e) {
            Log::error('Error getting eligible students: ' . $e->getMessage());
            return response()->json([
                'success'  => false,
                'students' => [],
                'message'  => 'Failed to get eligible students.',
            ], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function generateScholarshipNumber(): string
    {
        $year   = date('Y');
        $prefix = 'SCH-' . $year . '-';

        $last = Scholarship::withTrashed()
            ->where('scholarship_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(scholarship_no, -4) AS UNSIGNED) DESC')
            ->value('scholarship_no');

        $seq = $last ? (int) substr($last, -4) + 1 : 1;

        do {
            $number = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $exists = Scholarship::withTrashed()->where('scholarship_no', $number)->exists();
            $seq++;
        } while ($exists);

        return $number;
    }
}
