<?php

namespace App\Http\Controllers;

use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\StudentCurrentTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StudentClassOperationsController
 *
 * Owns "roster" operations: acting on the SET of students that match a
 * class + term + session, as opposed to StudentController which owns
 * "directory" operations (find/view/edit one student at a time).
 *
 * Split out of StudentController on {{ date }} to separate two different
 * mental models that were previously mixed into one 2000+ line controller:
 *   - Directory:  "find this one student and do something to them"
 *   - Class Ops:  "give me everyone in Class X / Session Y and act on them"
 *
 * Endpoints moved here verbatim (no logic changes) from StudentController:
 *   - bulkUpdateCurrentTerm()
 *   - updateCurrentTerm()
 *   - getStudentsByClassAndSession()
 *   - bulkUpdateStatus()
 *   - getStudentsInTerm()
 *   - removeFromTerm()
 *   - bulkRemoveFromTerm()
 */
class StudentClassOperationsController extends Controller
{
    public function __construct()
    {
        // Mirrors the permission gates StudentController used for the
        // equivalent actions before the split.
        $this->middleware('permission:Update student', ['only' => [
            'bulkUpdateCurrentTerm', 'updateCurrentTerm', 'bulkUpdateStatus',
        ]]);
        $this->middleware('permission:Delete student', ['only' => [
            'removeFromTerm', 'bulkRemoveFromTerm',
        ]]);
    }

    /**
     * Class & Term Operations page.
     */
    public function index()
    {
        $pagetitle = 'Class & Term Operations';

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass, ' - ', schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $schoolterms    = Schoolterm::select('id', 'term as name')->get();
        $schoolsessions = Schoolsession::select('id', 'session as name')->get();

        return view('student.class-operations', compact(
            'schoolclasses', 'schoolterms', 'schoolsessions', 'pagetitle'
        ));
    }

    /**
     * ── Roster Actions: Class Assignment ────────────────────────────
     */

    public function bulkUpdateCurrentTerm(Request $request)
    {
        $request->validate([
            'student_ids'   => 'required|array',
            'student_ids.*' => 'exists:studentRegistration,id',
            'schoolclassId' => 'required|exists:schoolclass,id',
            'termId'        => 'required|exists:schoolterm,id',
            'sessionId'     => 'required|exists:schoolsession,id',
            'is_current'    => 'sometimes|boolean',
        ]);
        try {
            DB::beginTransaction();
            $success = 0; $failed = 0; $results = [];
            foreach ($request->student_ids as $studentId) {
                try {
                    if (!Student::find($studentId)) { $results[$studentId]='Not found'; $failed++; continue; }
                    StudentCurrentTerm::registerTerm($studentId, $request->schoolclassId, $request->termId, $request->sessionId, $request->input('is_current', true));
                    $results[$studentId]='Success'; $success++;
                } catch (\Exception $e) {
                    Log::error("Error registering term for student {$studentId}: ".$e->getMessage());
                    $results[$studentId]='Failed: '.$e->getMessage(); $failed++;
                }
            }
            DB::commit();
            return response()->json(['success'=>true,'message'=>"Registered term for {$success} student(s). Failed: {$failed}.",'data'=>$results,'summary'=>['total'=>count($request->student_ids),'success'=>$success,'failed'=>$failed]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function updateCurrentTerm(Request $request, $studentId)
    {
        $request->validate(['schoolclassId'=>'required|exists:schoolclass,id','termId'=>'required|exists:schoolterm,id','sessionId'=>'required|exists:schoolsession,id','is_current'=>'sometimes|boolean']);
        try {
            if (!Student::find($studentId)) return response()->json(['success'=>false,'message'=>'Student not found'], 404);
            $currentTerm = StudentCurrentTerm::registerTerm($studentId, $request->schoolclassId, $request->termId, $request->sessionId, $request->input('is_current', true));
            return response()->json(['success'=>true,'message'=>'Term registered successfully','data'=>$currentTerm]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getStudentsByClassAndSession(Request $request)
    {
        try {
            $request->validate(['class_id'=>'required|exists:schoolclass,id','session_id'=>'required|exists:schoolsession,id']);

            $students = Student::query()
                ->leftJoin('studentclass',  'studentclass.studentId',  '=','studentRegistration.id')
                ->leftJoin('studentpicture','studentpicture.studentid','=','studentRegistration.id')
                ->leftJoin('schoolclass',   'schoolclass.id',          '=','studentclass.schoolclassid')
                ->leftJoin('schoolarm',     'schoolarm.id',            '=','schoolclass.arm')
                ->where('studentclass.schoolclassid', $request->class_id)
                ->where('studentclass.sessionid',     $request->session_id)
                ->select([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.statusId',
                    'studentRegistration.student_status',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                ])->get();

            $processedStudents = $students->map(function ($student) {
                $s = new \stdClass();
                $s->id             = $student->id;
                $s->admissionNo    = $student->admissionNo;
                $s->firstname      = $student->firstname;
                $s->lastname       = $student->lastname;
                $s->othername      = $student->othername;
                $s->gender         = $student->gender;
                $s->statusId       = $student->statusId;
                $s->student_status = $student->student_status;
                $s->picture        = $student->picture;
                $s->schoolclass    = $student->schoolclass;
                $s->arm            = $student->arm;
                return $s;
            });

            return response()->json([
                'success'  => true,
                'students' => $processedStudents,
                'stats'    => [
                    'total'        => $processedStudents->count(),
                    'active'       => $processedStudents->where('student_status','Active')->count(),
                    'inactive'     => $processedStudents->where('student_status','Inactive')->count(),
                    'old_students' => $processedStudents->where('statusId',1)->count(),
                    'new_students' => $processedStudents->where('statusId',2)->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getStudentsByClassAndSession: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'student_ids'   => 'required|array',
                'student_ids.*' => 'exists:studentRegistration,id',
                'update_type'   => 'required|in:activity_status,student_type',
                'value'         => 'required',
            ]);

            DB::beginTransaction();

            $updated = 0;
            if ($request->update_type === 'activity_status') {
                if (!in_array($request->value, ['Active','Inactive'])) throw new \Exception('Invalid activity status value.');
                $updated = Student::whereIn('id', $request->student_ids)->update(['student_status'=>$request->value]);
            } else {
                if (!in_array($request->value, ['old','new'])) throw new \Exception('Invalid student type value.');
                $updated = Student::whereIn('id', $request->student_ids)->update(['statusId'=>$request->value==='old'?1:2]);
            }

            DB::commit();
            return response()->json(['success'=>true,'message'=>"Successfully updated {$updated} student(s)",'updated_count'=>$updated]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * ── Term Registration Management ────────────────────────────────
     */

    public function getStudentsInTerm(Request $request)
    {
        try {
            $request->validate([
                'term_id'    => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
                'class_id'   => 'nullable|exists:schoolclass,id',
            ]);

            $query = StudentCurrentTerm::with(['student.picture','schoolClass.armRelation','term','session'])
                ->where('termId',    $request->term_id)
                ->where('sessionId', $request->session_id);

            if ($request->filled('class_id')) $query->where('schoolclassId', $request->class_id);

            $registrations = $query->get();

            $formattedStudents = $registrations->map(function ($reg) {
                $student = $reg->student;
                if (!$student) return null;
                return [
                    'registration_id' => $reg->id,
                    'student_id'      => $student->id,
                    'admissionNo'     => $student->admissionNo ?? 'N/A',
                    'firstname'       => $student->firstname ?? '',
                    'lastname'        => $student->lastname  ?? '',
                    'othername'       => $student->othername ?? '',
                    'fullname'        => trim(($student->lastname??'').' '.($student->firstname??'').' '.($student->othername??'')),
                    'gender'          => $student->gender ?? 'N/A',
                    'class'           => $reg->schoolClass ? $reg->schoolClass->schoolclass : 'N/A',
                    'arm'             => $reg->schoolClass && $reg->schoolClass->armRelation ? $reg->schoolClass->armRelation->arm : '',
                    'term'            => $reg->term    ? $reg->term->term       : 'N/A',
                    'session'         => $reg->session ? $reg->session->session : 'N/A',
                    'is_current'      => $reg->is_current,
                    'picture'         => $student->picture ? $student->picture->picture : null,
                    'registered_at'   => $reg->created_at ? $reg->created_at->format('d M Y') : 'N/A',
                ];
            })->filter()->values();

            return response()->json(['success'=>true,'students'=>$formattedStudents,'total'=>$formattedStudents->count()]);

        } catch (\Exception $e) {
            Log::error('Error fetching students in term: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function removeFromTerm(Request $request)
    {
        try {
            $request->validate(['registration_id'=>'required|exists:student_current_term,id']);
            DB::beginTransaction();
            $reg         = StudentCurrentTerm::findOrFail($request->registration_id);
            $studentName = $reg->student ? $reg->student->firstname.' '.$reg->student->lastname : 'Unknown';
            $reg->delete();
            DB::commit();
            return response()->json(['success'=>true,'message'=>'Student removed from term registration successfully','student_name'=>$studentName]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function bulkRemoveFromTerm(Request $request)
    {
        try {
            $request->validate(['registration_ids'=>'required|array','registration_ids.*'=>'exists:student_current_term,id']);
            DB::beginTransaction();
            $count = StudentCurrentTerm::whereIn('id', $request->registration_ids)->delete();
            DB::commit();
            return response()->json(['success'=>true,'message'=>"Successfully removed {$count} student(s) from term registration",'removed_count'=>$count]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }
}