<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SchoolBillModel;

class SchoolBillController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-bills|Create school-bills|Update school-bills|Delete school-bills', ['only' => ['index']]);
        $this->middleware('permission:Create school-bills', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update school-bills', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete school-bills', ['only' => ['destroy', 'bulkDestroy']]);
    }

    /**
     * Display listing with manual pagination (Laravel 12 compatible).
     */
    public function index(Request $request)
    {
        // ── Stats endpoint ────────────────────────────────────────────
        if ($request->has('stats')) {
            $bills = SchoolBillModel::leftJoin('student_status', 'student_status.id', '=', 'school_bill.statusId')
                ->whereIn('student_status.id', [1, 2])
                ->select('school_bill.bill_amount', 'school_bill.statusId')
                ->get();

            return response()->json([
                'stats' => [
                    'total'        => $bills->count(),
                    'old'          => $bills->where('statusId', 1)->count(),
                    'new'          => $bills->where('statusId', 2)->count(),
                    'total_amount' => $bills->sum('bill_amount'),
                ]
            ]);
        }

        // ── Manual AJAX endpoint for DataTables ───────────────────────
        if ($request->ajax()) {
            try {
                $search = $request->get('search')['value'] ?? '';
                $start = $request->get('start', 0);
                $length = $request->get('length', 15);
                $orderColumn = $request->get('order')[0]['column'] ?? 1;
                $orderDir = $request->get('order')[0]['dir'] ?? 'desc';

                // Map column indexes
                $columns = ['id', 'id', 'title', 'bill_amount', 'description', 'statusId', 'updated_at', 'action'];
                $orderBy = $columns[$orderColumn] ?? 'id';

                // Build query
                $query = SchoolBillModel::leftJoin('student_status', 'student_status.id', '=', 'school_bill.statusId')
                    ->whereIn('student_status.id', [1, 2])
                    ->select([
                        'school_bill.id',
                        'school_bill.title',
                        'school_bill.description',
                        'school_bill.bill_amount',
                        'student_status.id as statusId',
                        'school_bill.updated_at',
                    ]);

                // Apply search
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('school_bill.title', 'like', "%{$search}%")
                          ->orWhere('school_bill.description', 'like', "%{$search}%")
                          ->orWhere('school_bill.bill_amount', 'like', "%{$search}%");
                    });
                }

                // Get total count
                $totalRecords = SchoolBillModel::count();
                $filteredRecords = $query->count();

                // Get paginated results
                $schoolbills = $query->orderBy($orderBy, $orderDir)
                    ->skip($start)
                    ->take($length)
                    ->get();

                // Format data
                $data = [];
                $counter = $start + 1;

                foreach ($schoolbills as $row) {
                    // Status badge
                    if ($row->statusId == 1) {
                        $statusName = '<span class="bill-badge bill-badge-old"><i class="ri-user-line me-1"></i>Old Student</span>';
                    } elseif ($row->statusId == 2) {
                        $statusName = '<span class="bill-badge bill-badge-new"><i class="ri-user-add-line me-1"></i>New Student</span>';
                    } else {
                        $statusName = '<span class="bill-badge bill-badge-unknown">Unknown</span>';
                    }

                    // Formatted amount
                    $formattedAmount = '₦&nbsp;' . number_format($row->bill_amount, 2);

                    // Formatted date
                    if ($row->updated_at) {
                        $formattedDate = '<span class="text-muted small">'
                            . date('d M Y', strtotime($row->updated_at))
                            . '<br><span style="font-size:10px">'
                            . date('H:i', strtotime($row->updated_at))
                            . '</span></span>';
                    } else {
                        $formattedDate = '<span class="text-muted small">N/A</span>';
                    }

                    // Description with truncation
                    $description = $row->description
                        ? (strlen($row->description) > 50
                            ? '<span class="text-muted">' . e(substr($row->description, 0, 50)) . '…</span>'
                            : '<span class="text-muted">' . e($row->description) . '</span>')
                        : '<span class="text-muted fst-italic">—</span>';

                    // Action buttons
                    $buttons = '<div class="btn-group btn-group-sm">';
                    if (auth()->user()->can('Update school-bills')) {
                        $buttons .= '<button class="btn btn-primary edit-bill" title="Edit"
                            data-id="' . $row->id . '"
                            data-title="' . addslashes($row->title) . '"
                            data-amount="' . $row->bill_amount . '"
                            data-description="' . addslashes($row->description) . '"
                            data-status="' . $row->statusId . '">
                            <i class="ri-pencil-line"></i>
                        </button>';
                    }
                    if (auth()->user()->can('Delete school-bills')) {
                        $buttons .= '<button class="btn btn-danger delete-bill" title="Delete"
                            data-id="' . $row->id . '"
                            data-title="' . addslashes($row->title) . '">
                            <i class="ri-delete-bin-line"></i>
                        </button>';
                    }
                    $buttons .= '</div>';

                    // Checkbox
                    $checkbox = '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';

                    $data[] = [
                        'checkbox' => $checkbox,
                        'index' => $counter++,
                        'title' => e($row->title),
                        'formatted_amount' => $formattedAmount,
                        'description' => $description,
                        'status_name' => $statusName,
                        'formatted_date' => $formattedDate,
                        'action' => $buttons,
                    ];
                }

                return response()->json([
                    'draw' => intval($request->get('draw', 1)),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $filteredRecords,
                    'data' => $data,
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        }

        $pagetitle = 'School Bill Management';
        return view('schoolbill.index', compact('pagetitle'));
    }

    /**
     * Store a newly created bill.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|min:1|unique:school_bill,title',
            'bill_amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'statusId'    => 'required|in:1,2',
        ], [
            'title.required'       => 'Please enter a bill title.',
            'title.unique'         => 'This bill title already exists.',
            'bill_amount.required' => 'Please enter a bill amount.',
            'bill_amount.numeric'  => 'Bill amount must be a number.',
            'bill_amount.min'      => 'Bill amount must be at least ₦1.',
            'statusId.required'    => 'Please select a student status.',
            'statusId.in'          => 'Invalid student status selected.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $amount = floatval(str_replace(['₦', ','], '', $request->bill_amount));

        $bill = SchoolBillModel::create([
            'title'       => $request->title,
            'bill_amount' => $amount,
            'description' => $request->description,
            'statusId'    => $request->statusId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School Bill created successfully.',
            'data'    => $bill,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $bill = SchoolBillModel::find($id);
        if (!$bill) {
            return response()->json(['success' => false, 'message' => 'Bill not found.'], 404);
        }
        return response()->json(['success' => true, 'data' => $bill]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $bill = SchoolBillModel::find($id);
        if (!$bill) {
            return response()->json(['success' => false, 'message' => 'Bill not found.'], 404);
        }
        return response()->json(['success' => true, 'data' => $bill]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $bill = SchoolBillModel::find($id);
        if (!$bill) {
            return response()->json(['success' => false, 'message' => 'Bill not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'required|min:1|unique:school_bill,title,' . $id,
            'bill_amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'statusId'    => 'required|in:1,2',
        ], [
            'title.required'       => 'Please enter a bill title.',
            'title.unique'         => 'This bill title already exists.',
            'bill_amount.required' => 'Please enter a bill amount.',
            'bill_amount.numeric'  => 'Bill amount must be a number.',
            'bill_amount.min'      => 'Bill amount must be at least ₦1.',
            'statusId.required'    => 'Please select a student status.',
            'statusId.in'          => 'Invalid student status selected.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $amount = floatval(str_replace(['₦', ','], '', $request->bill_amount));

        $bill->update([
            'title'       => $request->title,
            'bill_amount' => $amount,
            'description' => $request->description,
            'statusId'    => $request->statusId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School Bill updated successfully.',
            'data'    => $bill,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $bill = SchoolBillModel::find($id);
        if (!$bill) {
            return response()->json(['success' => false, 'message' => 'Bill not found.'], 404);
        }

        $bill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bill deleted successfully.',
        ]);
    }

    /**
     * Bulk delete bills.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No bills selected.',
            ], 400);
        }

        $deleted = SchoolBillModel::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . ' bill(s) deleted successfully.',
        ]);
    }
}
