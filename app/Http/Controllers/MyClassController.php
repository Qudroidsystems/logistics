<?php

namespace App\Http\Controllers;

use App\Models\ClassTeacher;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Staffclasssetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class MyClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-class|Create my-class|Update my-class|Delete my-class', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create my-class', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update my-class', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete my-class', ['only' => ['destroy']]);
    }
public function index(Request $request): View
{
    $pagetitle = "Class Management";
    $user = auth()->user();
    $current = "Current";

    $query = ClassTeacher::where('staffid', $user->id)
        ->leftJoin('users', 'users.id', '=', 'classteacher.staffid')
        ->leftJoin('schoolclass', 'schoolclass.id', '=', 'classteacher.schoolclassid')
        ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
        ->leftJoin('schoolterm', 'schoolterm.id', '=', 'classteacher.termid')
        ->leftJoin('schoolsession', 'schoolsession.id', '=', 'classteacher.sessionid')
        ->select([
            'classteacher.id as id',
            'users.id as userid',
            'users.name as staffname',
            'schoolclass.schoolclass as schoolclass',
            'classteacher.termid as termid',
            'classteacher.sessionid as sessionid',
            'schoolarm.arm as schoolarm',
            'schoolclass.description as classcategory',
            'schoolterm.term as term',
            'schoolsession.session as session',
            'classteacher.updated_at as updated_at',
            'schoolclass.id as schoolclassid'
        ]);

    // Apply filters
    if ($request->has('search') && $request->search) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('schoolclass.schoolclass', 'like', "%{$search}%")
              ->orWhere('schoolarm.arm', 'like', "%{$search}%")
              ->orWhere('schoolterm.term', 'like', "%{$search}%")
              ->orWhere('schoolsession.session', 'like', "%{$search}%")
              ->orWhere('schoolclass.description', 'like', "%{$search}%");
        });
    }

    if ($request->has('schoolclassid') && $request->schoolclassid !== 'ALL') {
        $query->where('classteacher.schoolclassid', $request->schoolclassid);
    }

    if ($request->has('sessionid') && $request->sessionid !== 'ALL') {
        $query->where('classteacher.sessionid', $request->sessionid);
    }

    $myclass = $query->where('schoolsession.status', '=', $current)
        ->orderBy('schoolclass')
        ->paginate(5)
        ->appends($request->query());

    // Class history
    $myclasshistory = ClassTeacher::where('staffid', $user->id)
        ->leftJoin('users', 'users.id', '=', 'classteacher.staffid')
        ->leftJoin('schoolclass', 'schoolclass.id', '=', 'classteacher.schoolclassid')
        ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
        ->leftJoin('schoolterm', 'schoolterm.id', '=', 'classteacher.termid')
        ->leftJoin('schoolsession', 'schoolsession.id', '=', 'classteacher.sessionid')
        ->select([
            'classteacher.id as id',
            'users.id as userid',
            'users.name as staffname',
            'schoolclass.schoolclass as schoolclass',
            'classteacher.termid as termid',
            'classteacher.sessionid as sessionid',
            'schoolarm.arm as schoolarm',
            'schoolclass.description as classcategory',
            'schoolterm.term as term',
            'schoolsession.session as session',
            'classteacher.updated_at as updated_at',
            'schoolclass.id as schoolclassid'
        ])
        ->orderBy('session')
        ->get();

    // Class settings
    $classsetting = Staffclasssetting::where('staffid', $user->id)
        ->leftJoin('users', 'users.id', '=', 'staffclasssettings.staffid')
        ->leftJoin('schoolclass', 'schoolclass.id', '=', 'staffclasssettings.vschoolclassid')
        ->leftJoin('schoolterm', 'schoolterm.id', '=', 'staffclasssettings.termid')
        ->leftJoin('schoolsession', 'schoolsession.id', '=', 'staffclasssettings.sessionid')
        ->select([
            'users.id as userid',
            'users.name as staffname',
            'staffclasssettings.id as id',
            'staffclasssettings.created_at as created_at',
            'schoolclass.id as schoolclassid',
            'staffclasssettings.noschoolopened as noschoolopened',
            'staffclasssettings.termends as termends',
            'staffclasssettings.nexttermbegins as nexttermbegins',
            'schoolterm.term as term',
            'schoolsession.session as session',
        ])
        ->get();

    $terms = Schoolterm::all();
    $schoolsessions = Schoolsession::where('status', 'Current')->get();
    $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
        ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm'])
        ->get();

    if ($request->ajax()) {
        return response()->json([
            'tableBody' => view('myclass.partials.table-body', compact('myclass'))->render(),
            'pagination' => view('myclass.partials.pagination', compact('myclass'))->render(),
            'classCount' => $myclass->total()
        ]);
    }

    return view('myclass.index', compact(
        'pagetitle',
        'myclass',
        'myclasshistory',
        'classsetting',
        'terms',
        'schoolsessions',
        'schoolclasses'
    ))->with('sfid', $user->id);
}
    public function create(): View
    {
        $pagetitle = "Create my-Class Setting";
        $terms = Schoolterm::all();
        $schoolsessions = Schoolsession::where('status', 'Current')->get();
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);
        return view('myclass.create', compact('terms', 'schoolsessions', 'schoolclasses', 'pagetitle'));
    }

    public function store(Request $request): JsonResponse
    {
        Log::debug("Creating class setting", $request->all());

        if (!auth()->user()->hasPermissionTo('Create my-class')) {
            Log::warning("User ID " . auth()->user()->id . " attempted to create my-class setting without 'Create my-class' permission");
            return response()->json([
                'success' => false,
                'message' => 'User does not have the right permissions',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'staffid' => 'required|exists:users,id',
                'vschoolclassid' => 'required|exists:schoolclass,id',
                'termid' => 'required|exists:schoolterm,id',
                'sessionid' => 'required|exists:schoolsession,id',
                'noschoolopened' => 'nullable|integer|min:0',
                'termends' => 'nullable|date',
                'nexttermbegins' => 'nullable|date',
            ]);

            $check = Staffclasssetting::where('staffid', $validated['staffid'])
                ->where('vschoolclassid', $validated['vschoolclassid'])
                ->where('termid', $validated['termid'])
                ->where('sessionid', $validated['sessionid'])
                ->exists();

            if ($check) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class setting already exists for this term and session.',
                ], 422);
            }

            $setting = Staffclasssetting::create($validated);

            Log::debug("Class setting created successfully: ID {$setting->id}");
            return response()->json([
                'success' => true,
                'message' => 'Class setting created successfully',
                'setting' => $setting,
            ], 201);
        } catch (\Exception $e) {
            Log::error("Create my-class setting error: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to create my-class setting: ' . $e->getMessage(),
                'errors' => $e->errors ?? [],
            ], 422);
        }
    }

    public function show($id): View
    {
        $pagetitle = "Student Details";
        $student = ClassTeacher::where('classteacher.id', $id)
            ->leftJoin('users', 'users.id', '=', 'classteacher.staffid')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'classteacher.schoolclassid')
            ->leftJoin('studentclass', 'studentclass.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'classteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->first([
                'studentRegistration.id as student_id',
                'studentRegistration.admissionNo as admission_no',
                'studentRegistration.firstname as first_name',
                'studentRegistration.lastname as last_name',
                'studentRegistration.othername as other_name',
                'studentRegistration.gender as gender',
                'studentpicture.picture as picture',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as schoolarm',
                'schoolterm.term as term',
                'schoolsession.session as session',
                'schoolclass.id as schoolclassID',
                'schoolsession.id as sessionid'
            ]);

        return view('myclass.show', compact('student', 'pagetitle'));
    }

    public function edit($id): JsonResponse
    {
        try {
            $setting = Staffclasssetting::findOrFail($id);
            return response()->json([
                'success' => true,
                'setting' => $setting,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Fetch class setting error for ID {$id}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class setting: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        Log::debug("Updating class setting ID: {$id}", $request->all());

        try {
            $validated = $request->validate([
                'staffid' => 'required|exists:users,id',
                'vschoolclassid' => 'required|exists:schoolclass,id',
                'termid' => 'required|exists:schoolterm,id',
                'sessionid' => 'required|exists:schoolsession,id',
                'noschoolopened' => 'nullable|integer|min:0',
                'termends' => 'nullable|date',
                'nexttermbegins' => 'nullable|date',
            ]);

            $setting = Staffclasssetting::findOrFail($id);

            $check = Staffclasssetting::where('staffid', $validated['staffid'])
                ->where('vschoolclassid', $validated['vschoolclassid'])
                ->where('termid', $validated['termid'])
                ->where('sessionid', $validated['sessionid'])
                ->where('id', '!=', $id)
                ->exists();

            if ($check) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class setting already exists for this term and session.',
                ], 422);
            }

            $setting->update($validated);

            Log::debug("Class setting ID: {$id} updated successfully");
            return response()->json([
                'success' => true,
                'message' => 'Class setting updated successfully',
                'setting' => $setting,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Update my-class setting error for ID {$id}: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to update my-class setting: ' . $e->getMessage(),
                'errors' => $e->errors ?? [],
            ], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        Log::debug("Attempting to delete my-class setting ID: {$id}");
        try {
            $setting = Staffclasssetting::findOrFail($id);
            $setting->delete();

            Log::debug("Class setting ID: {$id} deleted successfully");
            return response()->json([
                'success' => true,
                'message' => 'Class setting deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Delete my-class setting error for ID {$id}: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete my-class setting: ' . $e->getMessage(),
            ], 500);
        }
    }
}
