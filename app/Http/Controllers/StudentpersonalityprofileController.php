<?php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\Studentpersonalityprofile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentpersonalityprofileController extends Controller
{
    /**
     * Display the student personality profile page (blade view — kept for backward compat).
     */
    public function studentpersonalityprofile($id, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle = "Student Personality Profile";

        $students = Student::where('studentRegistration.id', $id)
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->get([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as fname',
                'studentRegistration.home_address2 as homeaddress',
                'studentRegistration.lastname as lastname',
                'studentRegistration.othername as othername',
                'studentRegistration.dateofbirth as dateofbirth',
                'studentRegistration.gender as gender',
                'studentRegistration.updated_at as updated_at',
                'studentpicture.picture as picture',
            ]);

        Studentpersonalityprofile::firstOrCreate([
            'studentid'    => $id,
            'schoolclassid' => $schoolclassid,
            'sessionid'    => $sessionid,
            'termid'       => $termid,
        ]);

        $studentpp = Studentpersonalityprofile::where('studentid', $id)
            ->where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->where('termid', $termid)
            ->get();

        $scores = Broadsheets::where('broadsheet_records.student_id', $id)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.schoolclass_id', $schoolclassid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->orderBy('subject.subject')
            ->get([
                'subject.subject as subject_name',
                'subject.subject_code',
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.subject_position_class as position',
                'broadsheets.avg as class_average',
            ]);

        $mockScores = BroadsheetsMock::where('broadsheet_records_mock.student_id', $id)
            ->where('broadsheetmock.term_id', $termid)
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.schoolclass_id', $schoolclassid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->get([
                'subject.subject as subject_name',
                'subject.subject_code',
                'broadsheetmock.exam',
                'broadsheetmock.total',
                'broadsheetmock.grade',
                'broadsheetmock.subject_position_class as position',
                'broadsheetmock.avg as class_average',
            ]);

        $schoolclass   = Schoolclass::where('id', $schoolclassid)->first(['schoolclass', 'arm']);
        $schoolterm    = Schoolterm::where('id', $termid)->value('term')    ?? 'N/A';
        $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

        return view('studentpersonalityprofile.edit')
            ->with('students',      $students)
            ->with('studentpp',     $studentpp)
            ->with('scores',        $scores)
            ->with('mockScores',    $mockScores)
            ->with('staffid',       Auth::user()->id)
            ->with('studentid',     $id)
            ->with('schoolclassid', $schoolclassid)
            ->with('sessionid',     $sessionid)
            ->with('termid',        $termid)
            ->with('pagetitle',     $pagetitle)
            ->with('schoolclass',   $schoolclass)
            ->with('schoolterm',    $schoolterm)
            ->with('schoolsession', $schoolsession);
    }

    /**
     * ─────────────────────────────────────────────────────────────────────────
     * NEW: Return student profile data as JSON for the AJAX slide-over drawer.
     *
     * Route: GET /studentpersonalityprofile/data/{id}/{schoolclassid}/{sessionid}/{termid}
     * Name:  myclass.studentpersonalityprofile.data
     * ─────────────────────────────────────────────────────────────────────────
     */
    public function profileData($id, $schoolclassid, $sessionid, $termid): \Illuminate\Http\JsonResponse
    {
        // Fetch student
        $student = Student::where('studentRegistration.id', $id)
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->first([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.othername as othername',
                'studentRegistration.gender as gender',
                'studentpicture.picture as picture',
            ]);

        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        // Ensure profile row exists
        Studentpersonalityprofile::firstOrCreate([
            'studentid'     => $id,
            'schoolclassid' => $schoolclassid,
            'sessionid'     => $sessionid,
            'termid'        => $termid,
        ]);

        $profile = Studentpersonalityprofile::where('studentid', $id)
            ->where('schoolclassid', $schoolclassid)
            ->where('sessionid', $sessionid)
            ->where('termid', $termid)
            ->first();

        // Terminal scores
        $scores = Broadsheets::where('broadsheet_records.student_id', $id)
            ->where('broadsheets.term_id', $termid)
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.schoolclass_id', $schoolclassid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->orderBy('subject.subject')
            ->get([
                'subject.subject as subject_name',
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.subject_position_class as position',
                'broadsheets.avg as class_average',
            ]);

        // Mock scores
        $mockScores = BroadsheetsMock::where('broadsheet_records_mock.student_id', $id)
            ->where('broadsheetmock.term_id', $termid)
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.schoolclass_id', $schoolclassid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->get([
                'subject.subject as subject_name',
                'broadsheetmock.exam',
                'broadsheetmock.total',
                'broadsheetmock.grade',
                'broadsheetmock.subject_position_class as position',
                'broadsheetmock.avg as class_average',
            ]);

        // Class / term / session names
        $schoolclass = Schoolclass::where('schoolclass.id', $schoolclassid)
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->first(['schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm']);

        $schoolterm    = Schoolterm::where('id', $termid)->value('term')         ?? '';
        $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? '';

        $studentName = trim(
            ($student->lastname  ?? '') . ' ' .
            ($student->fname     ?? '') . ' ' .
            ($student->othername ?? '')
        );

        return response()->json([
            'success'       => true,
            'student_name'  => $studentName,
            'admissionno'   => $student->admissionno,
            'gender'        => $student->gender,
            'schoolclass'   => $schoolclass
                ? trim($schoolclass->schoolclass . ' ' . ($schoolclass->arm ?? ''))
                : 'N/A',
            'term'          => $schoolterm,
            'session'       => $schoolsession,
            'studentid'     => (int) $id,
            'schoolclassid' => (int) $schoolclassid,
            'sessionid'     => (int) $sessionid,
            'termid'        => (int) $termid,
            'profile'       => $profile,
            'scores'        => $scores,
            'mock_scores'   => $mockScores,
        ]);
    }

    /**
     * Save or update the student personality profile.
     */
    public function save(Request $request)
    {
        $request->validate([
            'studentid'                    => 'required|exists:studentRegistration,id',
            'schoolclassid'                => 'required|exists:schoolclass,id',
            'termid'                       => 'required|exists:schoolterm,id',
            'sessionid'                    => 'required|exists:schoolsession,id',
            'staffid'                      => 'nullable|exists:users,id',
            'punctuality'                  => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'neatness'                     => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'leadership'                   => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'attitude'                     => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'reading'                      => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'honesty'                      => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'cooperation'                  => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'selfcontrol'                  => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'politeness'                   => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'physicalhealth'               => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'stability'                    => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'gamesandsports'               => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'attendance'                   => 'nullable|integer|min:0|max:366',
            'attentiveness_in_class'       => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'class_participation'          => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'relationship_with_others'     => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'doing_assignment'             => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'writing_skill'                => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'reading_skill'                => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'spoken_english_communication' => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'hand_writing'                 => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'club'                         => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'music'                        => 'nullable|in:Excellent,Very Good,Good,Fairly Good,Poor',
            'classteachercomment'          => 'nullable|string|max:1000',
            'principalscomment'            => 'nullable|string|max:1000',
            'remark_on_other_activities'   => 'nullable|string|max:1000',
        ]);

        $studentpp = Studentpersonalityprofile::where('studentid', $request->studentid)
            ->where('schoolclassid', $request->schoolclassid)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->firstOrNew();

        try {
            $input = $request->only([
                'studentid', 'staffid', 'schoolclassid', 'termid', 'sessionid',
                'punctuality', 'neatness', 'leadership', 'attitude', 'reading',
                'honesty', 'cooperation', 'selfcontrol', 'politeness', 'physicalhealth',
                'stability', 'gamesandsports', 'attendance',
                'attentiveness_in_class', 'class_participation', 'relationship_with_others',
                'doing_assignment', 'writing_skill', 'reading_skill',
                'spoken_english_communication', 'hand_writing', 'club', 'music',
                'classteachercomment', 'principalscomment', 'remark_on_other_activities',
            ]);

            $studentpp->fill($input)->save();

            // Support both AJAX (JSON) and regular form (redirect) responses
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student Personality Profile updated successfully',
                ]);
            }

            return redirect()->back()->with('success', 'Student Personality Profile Updated successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }
}
