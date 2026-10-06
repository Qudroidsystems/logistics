<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\DiscountType;
use App\Models\DiscountAssignment;
use App\Models\Student;
use App\Models\Schoolclass;
use App\Models\SchoolBillModel;
use App\Services\Discount\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DiscountController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;

        $this->middleware('permission:View discount',    ['only' => ['index', 'show', 'showAssignments']]);
        $this->middleware('permission:Create discount',  ['only' => ['create', 'store']]);
        $this->middleware('permission:Update discount',  ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete discount',  ['only' => ['destroy', 'bulkDestroy']]);
        $this->middleware('permission:Approve discount', ['only' => ['approve']]);
    }

    // ─────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Discount::with(['type', 'createdBy', 'approvedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('discount_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type_id')) {
            $query->where('discount_type_id', $request->type_id);
        }

        $sort  = $request->get('sort', 'id');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        $discounts      = $query->paginate(15);
        $totalDiscounts = Discount::count();
        $activeDiscounts = Discount::where('status', 'active')->count();
        $discountTypes  = DiscountType::where('is_active', true)->get();
        $pagetitle      = 'Discount Management';

        return view('admin.discount.index', compact(
            'pagetitle', 'discounts', 'discountTypes', 'totalDiscounts', 'activeDiscounts'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────
    public function create()
    {
        $discountTypes = DiscountType::where('is_active', true)->get();
        $classes       = Schoolclass::with('armRelation')->get();
        $bills         = SchoolBillModel::where('is_active', true)->get();
        $pagetitle     = 'Create New Discount';

        return view('admin.discount.create', compact('pagetitle', 'discountTypes', 'classes', 'bills'));
    }

    // ─────────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'discount_type_id'               => 'required|exists:discount_types,id',
                'title'                          => 'required|string|max:255',
                'description'                    => 'nullable|string',
                'value_type'                     => 'required|in:percentage,fixed_amount',
                'value'                          => 'required|numeric|min:0.01',
                'max_amount'                     => 'nullable|numeric|min:0',
                'applicable_to'                  => 'required|in:all_bills,specific_bills,specific_categories',
                'applicable_bill_ids'            => 'nullable|array',
                'applicable_bill_ids.*'          => 'exists:school_bill,id',
                'applicable_categories'          => 'nullable|array',
                'eligible_classes'               => 'nullable|array',
                'eligible_classes.*'             => 'exists:schoolclass,id',
                'condition_type'                 => 'required|in:none,early_payment,min_amount,sibling_count',
                'condition_value'                => 'nullable|numeric|min:0',
                'days_before_due'                => 'nullable|integer|min:1',
                'stackable_with_scholarship'     => 'boolean',
                'stackable_with_other_discounts' => 'boolean',
                'stacking_priority'              => 'integer|min:1',
                'effective_from'                 => 'required|date',
                'effective_to'                   => 'nullable|date|after:effective_from',
                'status'                         => 'required|in:draft,active,expired,suspended',
            ]);

            $validated['discount_no']                    = $this->generateDiscountNumber();
            $validated['created_by']                     = auth()->id();
            $validated['stackable_with_scholarship']     = $request->has('stackable_with_scholarship');
            $validated['stackable_with_other_discounts'] = $request->has('stackable_with_other_discounts');
            $validated['applicable_bill_ids']            = json_encode($validated['applicable_bill_ids'] ?? []);
            $validated['applicable_categories']          = json_encode($validated['applicable_categories'] ?? []);
            $validated['eligible_classes']               = json_encode($validated['eligible_classes'] ?? []);

            $discount = Discount::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Discount created successfully.',
                    'redirect' => route('admin.discount.index'),
                    'discount' => $discount,
                ]);
            }

            return redirect()->route('admin.discount.index')->with('success', 'Discount created successfully.');

        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creating discount: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create discount: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to create discount.');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────
    public function show($id)
    {
        $discount = Discount::with(['type', 'createdBy', 'approvedBy', 'assignments.student'])->findOrFail($id);

        $assignments   = $discount->assignments()->with('student')->orderBy('created_at', 'desc')->paginate(20);
        $totalStudents = $discount->assignments()->count();
        $activeCount   = $discount->assignments()->where('status', 'active')->count();
        $pagetitle     = 'Discount Details: ' . $discount->title;

        return view('admin.discount.show', compact('pagetitle', 'discount', 'assignments', 'totalStudents', 'activeCount'));
    }

    // ─────────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $discount      = Discount::findOrFail($id);
        $discountTypes = DiscountType::where('is_active', true)->get();
        $classes       = Schoolclass::with('armRelation')->get();
        $bills         = SchoolBillModel::where('is_active', true)->get();
        $pagetitle     = 'Edit Discount: ' . $discount->title;

        return view('admin.discount.edit', compact('pagetitle', 'discount', 'discountTypes', 'classes', 'bills'));
    }

    // ─────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        try {
            $discount  = Discount::findOrFail($id);
            $validated = $request->validate([
                'discount_type_id'               => 'required|exists:discount_types,id',
                'title'                          => 'required|string|max:255',
                'description'                    => 'nullable|string',
                'value_type'                     => 'required|in:percentage,fixed_amount',
                'value'                          => 'required|numeric|min:0.01',
                'max_amount'                     => 'nullable|numeric|min:0',
                'applicable_to'                  => 'required|in:all_bills,specific_bills,specific_categories',
                'applicable_bill_ids'            => 'nullable|array',
                'applicable_categories'          => 'nullable|array',
                'eligible_classes'               => 'nullable|array',
                'condition_type'                 => 'required|in:none,early_payment,min_amount,sibling_count',
                'condition_value'                => 'nullable|numeric|min:0',
                'days_before_due'                => 'nullable|integer|min:1',
                'stackable_with_scholarship'     => 'boolean',
                'stackable_with_other_discounts' => 'boolean',
                'stacking_priority'              => 'integer|min:1',
                'effective_from'                 => 'required|date',
                'effective_to'                   => 'nullable|date|after:effective_from',
                'status'                         => 'required|in:draft,active,expired,suspended',
            ]);

            $validated['stackable_with_scholarship']     = $request->has('stackable_with_scholarship');
            $validated['stackable_with_other_discounts'] = $request->has('stackable_with_other_discounts');
            $validated['applicable_bill_ids']            = json_encode($validated['applicable_bill_ids'] ?? []);
            $validated['applicable_categories']          = json_encode($validated['applicable_categories'] ?? []);
            $validated['eligible_classes']               = json_encode($validated['eligible_classes'] ?? []);

            $discount->update($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Discount updated successfully.', 'redirect' => route('admin.discount.index')]);
            }
            return redirect()->route('admin.discount.index')->with('success', 'Discount updated successfully.');

        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating discount: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update discount: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to update discount.');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        try {
            $discount = Discount::findOrFail($id);

            if ($discount->assignments()->where('status', 'active')->exists()) {
                if (request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Cannot delete discount with active assignments.'], 400);
                }
                return back()->with('error', 'Cannot delete discount with active assignments.');
            }

            $discount->delete();

            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Discount deleted successfully.']);
            }
            return redirect()->route('admin.discount.index')->with('success', 'Discount deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting discount: ' . $e->getMessage());
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete discount.'], 500);
            }
            return back()->with('error', 'Failed to delete discount.');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // BULK DESTROY
    // ─────────────────────────────────────────────────────────────────
    public function bulkDestroy(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No discounts selected.'], 400);
            }

            $withActive = Discount::whereIn('id', $ids)
                ->whereHas('assignments', fn ($q) => $q->where('status', 'active'))
                ->count();

            if ($withActive > 0) {
                return response()->json(['success' => false, 'message' => "Cannot delete {$withActive} discount(s) with active assignments."], 400);
            }

            $deleted = Discount::whereIn('id', $ids)->delete();
            return response()->json(['success' => true, 'message' => "{$deleted} discount(s) deleted successfully."]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting discounts: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete discounts.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // APPROVE
    // ─────────────────────────────────────────────────────────────────
    public function approve($id)
    {
        try {
            $discount = Discount::findOrFail($id);
            $discount->update([
                'status'      => 'active',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Discount approved successfully.']);
            }
            return redirect()->back()->with('success', 'Discount approved successfully.');

        } catch (\Exception $e) {
            Log::error('Error approving discount: ' . $e->getMessage());
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to approve discount.'], 500);
            }
            return back()->with('error', 'Failed to approve discount.');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // SHOW ASSIGNMENTS PAGE
    // ─────────────────────────────────────────────────────────────────
    public function showAssignments(Request $request)
    {
        $query = DiscountAssignment::with(['discount', 'student', 'assignedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname',  'like', "%{$search}%")
                  ->orWhere('admissionNo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('discount_id')) {
            $query->where('discount_id', $request->discount_id);
        }

        $assignments = $query->orderBy('created_at', 'desc')->paginate(20);

        $statusCounts = [
            'active'  => DiscountAssignment::where('status', 'active')->count(),
            'expired' => DiscountAssignment::where('status', 'expired')->count(),
            'removed' => DiscountAssignment::where('status', 'removed')->count(),
        ];

        $discounts = Discount::where('status', 'active')->get();
        $pagetitle = 'Discount Assignments';

        return view('admin.discount.assignments', compact(
            'pagetitle', 'assignments', 'statusCounts', 'discounts'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // ASSIGN TO STUDENT
    // ─────────────────────────────────────────────────────────────────
    public function assignToStudent(Request $request)
    {
        try {
            $validated = $request->validate([
                'discount_id'    => 'required|exists:discounts,id',
                'student_id'     => 'required|exists:studentRegistration,id',
                'effective_from' => 'required|date',
                'effective_to'   => 'nullable|date|after:effective_from',
                'reason'         => 'nullable|string|max:1000',
            ]);

            $discount = Discount::findOrFail($validated['discount_id']);

            $existing = DiscountAssignment::where('discount_id', $validated['discount_id'])
                ->where('student_id', $validated['student_id'])
                ->where('status', 'active')
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student already has an active assignment for this discount.',
                ], 400);
            }

            $assignment = DiscountAssignment::create([
                'discount_id'    => $validated['discount_id'],
                'student_id'     => $validated['student_id'],
                'status'         => 'active',
                'effective_from' => $validated['effective_from'],
                'effective_to'   => $validated['effective_to'] ?? null,
                'value_type'     => $discount->value_type,
                'value'          => $discount->value,
                'max_amount'     => $discount->max_amount,
                'reason'         => $validated['reason'] ?? null,
                'assigned_by'    => auth()->id(),
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Discount assigned successfully.',
                'assignment' => $assignment,
            ]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error assigning discount: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to assign discount: ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // REMOVE ASSIGNMENT
    // ─────────────────────────────────────────────────────────────────
    public function removeAssignment(Request $request, $assignmentId)
    {
        try {
            $assignment = DiscountAssignment::findOrFail($assignmentId);
            $assignment->update([
                'status'       => 'removed',
                'effective_to' => now(),
                'reason'       => $request->input('reason', 'Removed by administrator'),
            ]);

            return response()->json(['success' => true, 'message' => 'Discount assignment removed successfully.']);

        } catch (\Exception $e) {
            Log::error('Error removing assignment: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove assignment.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET ELIGIBLE STUDENTS (AJAX — used by the assign modal search)
    // ─────────────────────────────────────────────────────────────────
    public function getEligibleStudents(Request $request)
    {
        try {
            $request->validate([
                'discount_id' => 'nullable|exists:discounts,id',
                'q'           => 'nullable|string|max:100',
            ]);

            $query = Student::query();

            // Filter by eligible classes if discount is selected
            if ($request->filled('discount_id')) {
                $discount = Discount::findOrFail($request->discount_id);
                $eligibleClasses = json_decode($discount->eligible_classes, true) ?? [];

                if (!empty($eligibleClasses)) {
                    $query->whereHas('currentTerm', function ($ct) use ($eligibleClasses) {
                        $ct->whereIn('schoolclassId', $eligibleClasses);
                    });
                }

                // Exclude students already assigned to this discount
                $query->whereDoesntHave('discountAssignments', function ($q) use ($discount) {
                    $q->where('discount_id', $discount->id)->where('status', 'active');
                });
            }

            // Text search
            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%")
                      ->orWhere('admissionNo', 'like', "%{$search}%");
                });
            }

            // Eager load relationships
            $query->with([
                'picture',
                'currentTerm' => function($q) {
                    $q->with(['schoolClass.armRelation', 'term', 'session']);
                },
                'schoolClass.schoolclass',
                'schoolClass.armRelation',
            ]);

            $students = $query
                ->select('id', 'firstname', 'lastname', 'admissionNo', 'gender')
                ->orderBy('lastname')
                ->limit(50)
                ->get()
                ->map(function ($student) {
                    // Get current term info
                    $currentTerm = $student->currentTerm;
                    $className = null;
                    $armName = null;
                    $termName = null;
                    $sessionName = null;

                    if ($currentTerm) {
                        // Get class and arm from current term
                        if ($currentTerm->schoolClass) {
                            $className = $currentTerm->schoolClass->schoolclass ?? null;
                            $armName = $currentTerm->schoolClass->armRelation->arm ?? null;
                        }

                        // Get term name
                        if ($currentTerm->term) {
                            $termName = $currentTerm->term->term;
                        }

                        // Get session name
                        if ($currentTerm->session) {
                            $sessionName = $currentTerm->session->session;
                        }
                    }

                    // Fallback to direct schoolClass if no current term
                    if (!$className && $student->schoolClass) {
                        $sc = $student->schoolClass;
                        if ($sc->schoolclass) {
                            $className = $sc->schoolclass->schoolclass ?? null;
                            $armName = $sc->armRelation->arm ?? null;
                        }
                    }

                    // Get student picture - using the same path as your payment portal
                    $pictureUrl = null;
                    if ($student->picture && $student->picture->picture) {
                        $picturePath = $student->picture->picture;
                        // Check if it's already a full path or just filename
                        if (!str_contains($picturePath, 'storage/') && $picturePath != 'unnamed.jpg') {
                            $pictureUrl = asset('storage/images/student_avatars/' . $picturePath);
                        } elseif ($picturePath != 'unnamed.jpg') {
                            $pictureUrl = asset($picturePath);
                        }
                    }

                    return [
                        'id' => $student->id,
                        'firstname' => $student->firstname,
                        'lastname' => $student->lastname,
                        'admissionNo' => $student->admissionNo,
                        'gender' => $student->gender,
                        'class_name' => $className,
                        'arm_name' => $armName,
                        'term' => $termName,
                        'session' => $sessionName,
                        'picture' => $pictureUrl,
                        'initials' => strtoupper(substr($student->firstname, 0, 1) . substr($student->lastname, 0, 1)),
                        'class_display' => $className && $armName ? "{$className} {$armName}" : ($className ?? 'N/A'),
                        'current_term_display' => $termName && $sessionName ? "{$termName} · {$sessionName}" : 'Not assigned',
                    ];
                });

            return response()->json([
                'success' => true,
                'students' => $students,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting eligible students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get eligible students: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GENERATE DISCOUNT NUMBER
    // ─────────────────────────────────────────────────────────────────
    private function generateDiscountNumber(): string
    {
        $year         = date('Y');
        $lastDiscount = Discount::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $sequence     = $lastDiscount ? intval(substr($lastDiscount->discount_no, -4)) + 1 : 1;

        return 'DSC-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
