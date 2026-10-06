<?php

namespace App\Http\Controllers;

use App\Models\Schoolsession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SchoolsessionController extends Controller
{
    public function index()
    {
         #page title
         $pagetitle = "Session Management";

        $sessions = Schoolsession::paginate(5); // Paginate 5 sessions per page
        return view('session.index', compact('sessions'))->with('pagetitle', $pagetitle);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session' => 'required|string|unique:schoolsession,session',
            'sessionstatus' => 'required|in:Current,Past',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->sessionstatus === 'Current' && Schoolsession::where('status', 'Current')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A session with CURRENT status already exists.',
            ], 422);
        }

        $session = Schoolsession::create([
            'session' => $request->session,
            'status' => $request->sessionstatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School Session added successfully.',
            'session' => [
                'id' => $session->id,
                'session' => $session->session,
                'sessionstatus' => $session->status,
                'updated_at' => $session->updated_at,
            ],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'session' => 'required|string|unique:schoolsession,session,' . $id,
            'sessionstatus' => 'required|in:Current,Past',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->sessionstatus === 'Current') {
            $existingCurrent = Schoolsession::where('status', 'Current')->where('id', '!=', $id)->exists();
            if ($existingCurrent) {
                return response()->json([
                    'success' => false,
                    'message' => 'A session with CURRENT status already exists.',
                ], 422);
            }
        }

        $session = Schoolsession::findOrFail($id);
        $session->update([
            'session' => $request->session,
            'status' => $request->sessionstatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School Session updated successfully.',
            'session' => [
                'id' => $session->id,
                'session' => $session->session,
                'sessionstatus' => $session->status,
                'updated_at' => $session->updated_at,
            ],
        ]);
    }

    public function destroy($id)
    {
        $session = Schoolsession::findOrFail($id);
        $session->delete();
        return response()->json([
            'success' => true,
            'message' => 'School Session deleted successfully.',
        ]);
    }
}