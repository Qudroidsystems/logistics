<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\Principalscomment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PrincipalsCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View principals-comment|Create principals-comment|Update principals-comment|Delete principals-comment', ['only' => ['index']]);
        $this->middleware('permission:Create principals-comment', ['only' => ['store']]);
        $this->middleware('permission:Update principals-comment', ['only' => ['update']]);
        $this->middleware('permission:Delete principals-comment', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Principals Comment Management";

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm')
            ->get();

        $staff = User::select('id', 'name', 'avatar')->get();

        $sessions = Schoolsession::orderByDesc('session')->get(['id', 'session', 'status']);
        $terms = Schoolterm::orderBy('id')->get(['id', 'term']);

        $principalscomments = Principalscomment::join('users', 'principalscomments.staffId', '=', 'users.id')
            ->join('schoolclass', 'principalscomments.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->join('schoolsession', 'principalscomments.sessionid', '=', 'schoolsession.id')
            ->join('schoolterm', 'principalscomments.termid', '=', 'schoolterm.id')
            ->select([
                'principalscomments.id as pcid',
                'principalscomments.staffId as staffid',
                'principalscomments.schoolclassid',
                'users.name as staffname',
                'users.avatar as picture',
                'schoolclass.schoolclass as sclass',
                'schoolarm.arm as schoolarm',
                'schoolsession.session as session_name',
                'schoolterm.term as term_name',
                'principalscomments.updated_at'
            ])
            ->orderBy('users.name')
            ->get();

        return view('principalscomment.index')
            ->with(compact(
                'principalscomments',
                'schoolclasses',
                'staff',
                'sessions',
                'terms',
                'pagetitle'
            ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staffId' => 'required|exists:users,id',
            'sessionid' => 'required|exists:schoolsession,id',
            'termid' => 'required|exists:schoolterm,id',
            'schoolclassid' => 'required|array|min:1',
            'schoolclassid.*' => 'exists:schoolclass,id',
        ], [
            'staffId.required' => 'Please select a staff member!',
            'sessionid.required' => 'Please select a session!',
            'termid.required' => 'Please select a term!',
            'schoolclassid.required' => 'Please select at least one class!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $staffId = $request->input('staffId');
        $sessionId = $request->input('sessionid');
        $termId = $request->input('termid');
        $schoolClassIds = $request->input('schoolclassid', []);

        $createdRecords = [];
        foreach ($schoolClassIds as $schoolClassId) {
            $exists = Principalscomment::where('staffId', $staffId)
                ->where('schoolclassid', $schoolClassId)
                ->where('sessionid', $sessionId)
                ->where('termid', $termId)
                ->exists();

            if ($exists) {
                continue;
            }

            $record = Principalscomment::create([
                'staffId' => $staffId,
                'schoolclassid' => $schoolClassId,
                'sessionid' => $sessionId,
                'termid' => $termId,
            ]);

            $createdRecords[] = $record;
        }

        if (empty($createdRecords)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff is already assigned to all selected classes for the chosen session and term.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($createdRecords) . ' assignment(s) added successfully!',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'staffId' => 'required|exists:users,id',
            'schoolclassid' => 'required|exists:schoolclass,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $record = Principalscomment::findOrFail($id);

        $exists = Principalscomment::where('staffId', $request->staffId)
            ->where('schoolclassid', $request->schoolclassid)
            ->where('sessionid', $record->sessionid)
            ->where('termid', $record->termid)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This staff is already assigned to this class in this session/term.'
            ], 422);
        }

        $record->update([
            'staffId' => $request->staffId,
            'schoolclassid' => $request->schoolclassid,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        try {
            $record = Principalscomment::findOrFail($id);
            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment. Please try again.'
            ], 500);
        }
    }
}
