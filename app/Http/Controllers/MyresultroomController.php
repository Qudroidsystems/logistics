<?php

namespace App\Http\Controllers;

use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\SubjectTeacher;
use App\Models\BroadsheetsMock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Validation\ValidationException;

class MyresultroomController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View myresult-room|Create myresult-room|Update myresult-room|Delete myresult-room', ['only' => ['index']]);
        $this->middleware('permission:Create myresult-room', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update myresult-room', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete myresult-room', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "My Result Room";
        $user = auth()->user();

        if (!$user) {
            Log::warning('Unauthenticated access attempt to MyresultroomController', ['request' => $request->all()]);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 403);
            }
            return redirect()->route('login');
        }

        $terms = Schoolterm::orderBy('id')->get();
        $sessions = Schoolsession::orderBy('id', 'desc')->get();
        $mysubjects = collect();
        $subjectTeachers = collect();

        if ($request->isMethod('post') || $request->has(['termid', 'sessionid'])) {
            try {
                $validated = $request->validate([
                    'termid' => ['required', 'integer', 'exists:schoolterm,id'],
                    'sessionid' => ['required', 'integer', 'exists:schoolsession,id'],
                ]);

                Log::info('Filter request received', ['user_id' => $user->id, 'validated' => $validated]);

                $subjectsQuery = SubjectTeacher::where('subjectteacher.staffid', $user->id)
                    ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                    ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
                    ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                    ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                    ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                    ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                    ->leftJoin('schoolclass_classcategory', 'schoolclass_classcategory.schoolclass_id', '=', 'schoolclass.id')
                    ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
                    ->where('subjectteacher.sessionid', $validated['sessionid'])
                    ->where('subjectteacher.termid', $validated['termid'])
                    ->whereNotNull('subjectclass.id')
                    ->groupBy(
                        'subjectteacher.id',
                        'users.id',
                        'users.name',
                        'subject.subject',
                        'subject.subject_code',
                        'schoolterm.id',
                        'subjectclass.id',
                        'schoolclass.id',
                        'subjectteacher.sessionid',
                        DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))"),
                        'schoolterm.term',
                        'schoolsession.session'
                    )
                    ->orderBy('schoolclass.schoolclass')
                    ->orderBy('schoolarm.arm');

                $subjectTeachersData = $subjectsQuery->get([
                    'subjectteacher.id as id',
                    'users.id as userid',
                    'users.name as staffname',
                    'subject.subject as subject',
                    'subject.subject_code as subjectcode',
                    'schoolterm.id as termid',
                    'subjectclass.id as subjectclassid',
                    'schoolclass.id as schoolclassid',
                    'subjectteacher.sessionid as sessionid',
                    \DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as schoolclass"),
                    DB::raw("GROUP_CONCAT(DISTINCT classcategories.category ORDER BY classcategories.category SEPARATOR ', ') as classcategories"),
                    'schoolterm.term as term',
                    'schoolsession.session as session',
                ]);

                if ($subjectTeachersData->isEmpty()) {
                    Log::info('No subjects found for user', [
                        'user_id' => $user->id,
                        'termid' => $validated['termid'],
                        'sessionid' => $validated['sessionid'],
                    ]);
                } else {
                    Log::info('Found subjects', [
                        'count' => $subjectTeachersData->count(),
                        'user_id' => $user->id,
                    ]);
                }

                $mysubjects = $subjectTeachersData->map(function ($subject) use ($user) {
                    try {
                        $broadsheetExists = Broadsheets::where('staff_id', $user->id)
                            ->where('subjectclass_id', $subject->subjectclassid)
                            ->where('term_id', $subject->termid)
                            ->whereHas('broadsheetRecord', function ($query) use ($subject) {
                                $query->where('session_id', $subject->sessionid);
                            })
                            ->exists();

                        $broadsheetMockExists = BroadsheetsMock::where('staff_id', $user->id)
                            ->where('subjectclass_id', $subject->subjectclassid)
                            ->where('term_id', $subject->termid)
                            ->whereHas('broadsheetRecordMock', function ($query) use ($subject) {
                                $query->where('session_id', $subject->sessionid);
                            })
                            ->exists();
                    } catch (\Exception $e) {
                        Log::error('Error checking broadsheet existence', [
                            'user_id' => $user->id,
                            'subjectclass_id' => $subject->subjectclassid,
                            'term_id' => $subject->termid,
                            'session_id' => $subject->sessionid,
                            'error' => $e->getMessage(),
                        ]);
                        $broadsheetExists = false;
                        $broadsheetMockExists = false;
                    }

                    return (object) [
                        'id' => $subject->id,
                        'schoolclass' => $subject->schoolclass,
                        'classcategories' => $subject->classcategories ?? 'N/A',
                        'subject' => $subject->subject,
                        'subjectcode' => $subject->subjectcode,
                        'term' => $subject->term,
                        'session' => $subject->session,
                        'userid' => $subject->userid,
                        'subjectclassid' => $subject->subjectclassid,
                        'schoolclassid' => $subject->schoolclassid,
                        'session_id' => $subject->sessionid,
                        'termid' => $subject->termid,
                        'broadsheet_exists' => $broadsheetExists,
                        'broadsheet_mock_exists' => $broadsheetMockExists,
                    ];
                })->filter();

                $subjectTeachers = $subjectTeachersData->map(function ($subject) {
                    return (object) [
                        'subjectclassid' => $subject->subjectclassid,
                        'userid' => $subject->userid,
                        'staffname' => $subject->staffname ?? 'Unknown',
                        'subjectname' => $subject->subject,
                        'termid' => $subject->termid,
                        'term' => $subject->term,
                        'schoolclass' => $subject->schoolclass . ' (' . ($subject->classcategories ?? 'N/A') . ')',
                    ];
                })->filter();

                Log::info('Processed data', [
                    'mysubjects_count' => $mysubjects->count(),
                    'subjectTeachers_count' => $subjectTeachers->count(),
                    'user_id' => $user->id
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Data loaded successfully',
                        'data' => [
                            'mysubjects' => $mysubjects->values(),
                            'subjectTeachers' => $subjectTeachers->values(),
                        ],
                    ], 200);
                }

            } catch (ValidationException $e) {
                Log::warning('Validation failed', ['errors' => $e->errors(), 'user_id' => $user->id]);
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid input: ' . implode(', ', array_merge(...array_values($e->errors()))),
                        'errors' => $e->errors(),
                    ], 422);
                }
                return back()->withErrors($e->errors())->withInput();
            } catch (\Throwable $e) {
                Log::error('Error loading subjects', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id,
                    'request_data' => $request->all()
                ]);
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Server error: ' . $e->getMessage(),
                    ], 500);
                }
                return back()->with('error', 'Server error: ' . $e->getMessage());
            }
        }

        return view('myresultroom.index', compact('pagetitle', 'terms', 'sessions', 'mysubjects', 'subjectTeachers'));
    }
}
