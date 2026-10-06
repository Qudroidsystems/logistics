<?php

namespace App\Http\Controllers;

use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\Studentclass;
use App\Models\StudentPicture;
use App\Models\StudentRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ViewStudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-class-student', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create my-class-student', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update my-class-student', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete my-class-student', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $pagetitle = "My Class Students";

        $query = Studentclass::query()
            ->where('schoolclassid', $request->input('schoolclassid'))
            // ->where('termid', $request->input('termid'))
            ->where('sessionid', $request->input('sessionid'))
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id');

        // Apply filters
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                  ->orWhere('studentRegistration.firstname', 'like', "%{$search}%")
                  ->orWhere('studentRegistration.lastname', 'like', "%{$search}%")
                  ->orWhere('studentRegistration.othername', 'like', "%{$search}%");
            });
        }
        if ($gender = $request->input('gender')) {
            if ($gender !== 'all') {
                $query->where('studentRegistration.gender', $gender);
            }
        }
        if ($admissionno = $request->input('admissionno')) {
            if ($admissionno !== 'all') {
                $query->where('studentRegistration.admissionNo', $admissionno);
            }
        }

        $allstudents = $query->select([
            'studentRegistration.admissionNo as admissionno',
            'studentRegistration.firstname as firstname',
            'studentRegistration.lastname as lastname',
            'studentRegistration.id as stid',
            'studentRegistration.othername as othername',
            'studentRegistration.gender as gender',
            'studentpicture.picture as picture',
        ])->latest('studentclass.created_at')->get();

        $studentcount = Studentclass::where('schoolclassid', $request->input('schoolclassid'))
            ->where('sessionid', $request->input('sessionid'))
            ->where('termid', $request->input('termid'))->count();

        $male = Studentclass::where('schoolclassid', $request->input('schoolclassid'))
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->where('sessionid', $request->input('sessionid'))
            ->where('termid', $request->input('termid'))
            ->where('gender', 'Male')->count();

        $female = Studentclass::where('schoolclassid', $request->input('schoolclassid'))
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->where('sessionid', $request->input('sessionid'))
            ->where('termid', $request->input('termid'))
            ->where('gender', 'Female')->count();

        $session = Schoolsession::where('id', $request->input('sessionid'))->get();
        $term = Schoolterm::where('id', $request->input('termid'))->get();
        $schoolclass = Schoolclass::where('schoolclass.id', $request->input('schoolclassid'))
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm']);

        if (config('app.debug')) {
            Log::info('Students fetched:', $allstudents->toArray());
        }

        return view('viewstudents.index', compact('allstudents', 'term', 'session', 'schoolclass', 'studentcount', 'male', 'female', 'pagetitle'))
            ->with('schoolclassid', $request->input('schoolclassid'))
            ->with('termid', $request->input('termid'))
            ->with('sessionid', $request->input('sessionid'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Log::debug("Creating student", $request->all());

        if (!auth()->user()->hasPermissionTo('Create student')) {
            Log::warning("User ID " . auth()->user()->id . " attempted to create student without 'Create student' permission");
            return response()->json([
                'success' => false,
                'message' => 'User does not have the right permissions',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'admissionno' => 'required|string|max:255|unique:studentRegistration,admissionNo',
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'othername' => 'nullable|string|max:255',
                'gender' => 'required|in:Male,Female',
                'schoolclassid' => 'required|exists:schoolclass,id',
                'termid' => 'required|exists:schoolterm,id',
                'sessionid' => 'required|exists:schoolsession,id',
            ]);

            $student = Student::create([
                'admissionNo' => $validated['admissionno'],
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'othername' => $validated['othername'],
                'gender' => $validated['gender'],
            ]);

            Studentclass::create([
                'studentId' => $student->id,
                'schoolclassid' => $validated['schoolclassid'],
                'termid' => $validated['termid'],
                'sessionid' => $validated['sessionid'],
            ]);

            Log::debug("Student created successfully: ID {$student->id}");
            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'student' => [
                    'id' => $student->id,
                    'admissionno' => $student->admissionNo,
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'othername' => $student->othername,
                    'gender' => $student->gender,
                    'picture' => null, // Assuming no picture is uploaded during creation
                ],
            ], 201);
        } catch (ValidationException $e) {
            Log::error("Validation error creating student: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Create student error: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  int  $termid
     * @param  int  $sessionid
     * @return \Illuminate\Http\Response
     */
    public function show($id, $termid, $sessionid, Request $request): \Illuminate\View\View
    {
        return $this->index($request->merge(['schoolclassid' => $id, 'termid' => $termid, 'sessionid' => $sessionid]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        Log::debug("Updating student ID: {$id}", $request->all());

        try {
            $validated = $request->validate([
                'admissionno' => 'required|string|max:255|unique:studentRegistration,admissionNo,' . $id,
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'othername' => 'nullable|string|max:255',
                'gender' => 'required|in:Male,Female',
                'schoolclassid' => 'required|exists:schoolclass,id',
                'termid' => 'required|exists:schoolterm,id',
                'sessionid' => 'required|exists:schoolsession,id',
            ]);

            $student = Student::findOrFail($id);
            $student->update([
                'admissionNo' => $validated['admissionno'],
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'othername' => $validated['othername'],
                'gender' => $validated['gender'],
            ]);

            $studentClass = Studentclass::where('studentId', $id)
                ->where('schoolclassid', $validated['schoolclassid'])
                ->where('termid', $validated['termid'])
                ->where('sessionid', $validated['sessionid'])
                ->first();

            if ($studentClass) {
                $studentClass->update([
                    'schoolclassid' => $validated['schoolclassid'],
                    'termid' => $validated['termid'],
                    'sessionid' => $validated['sessionid'],
                ]);
            } else {
                Studentclass::create([
                    'studentId' => $student->id,
                    'schoolclassid' => $validated['schoolclassid'],
                    'termid' => $validated['termid'],
                    'sessionid' => $validated['sessionid'],
                ]);
            }

            Log::debug("Student ID: {$id} updated successfully");
            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'student' => [
                    'id' => $student->id,
                    'admissionno' => $student->admissionNo,
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'othername' => $student->othername,
                    'gender' => $student->gender,
                    'picture' => StudentPicture::where('studentid', $student->id)->first()->picture ?? null,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error("Update student error for ID {$id}: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        Log::debug("Attempting to delete student ID: {$id}");
        try {
            $student = Student::findOrFail($id);

            // Delete related Studentclass entries
            Log::debug("Deleting Studentclass for student ID: {$id}");
            Studentclass::where('studentId', $id)->delete();

            // Delete related StudentPicture
            Log::debug("Deleting StudentPicture for student ID: {$id}");
            $picture = StudentPicture::where('studentid', $id)->first();
            if ($picture && $picture->picture && $picture->picture !== 'unnamed.jpg') {
                Storage::delete('public/student_avatars/' . $picture->picture);
            }
            StudentPicture::where('studentid', $id)->delete();

            // Delete the student
            Log::debug("Deleting student ID: {$id}");
            $student->delete();

            Log::debug("Student ID: {$id} deleted successfully");
            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Delete student error for ID {$id}: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student: ' . $e->getMessage(),
            ], 500);
        }
    }
}
