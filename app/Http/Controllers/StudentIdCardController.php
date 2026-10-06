<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolInformation;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentIdCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View id card|Generate id card|Print id card')
             ->except('verify');          // verify is public (QR scan landing)
    }

    /* ══════════════════════════════════════════
       INDEX
    ══════════════════════════════════════════ */
    public function index()
    {
        $pagetitle = 'Student ID Card Generator';

        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id,
                CONCAT(schoolclass.schoolclass,
                    CASE WHEN schoolarm.arm IS NOT NULL
                         THEN CONCAT(' - ', schoolarm.arm)
                         ELSE '' END
                ) as class_display")
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $schoolInfo = SchoolInformation::getActiveSchool();

        return view('student.idcard.index', compact('pagetitle', 'schoolclasses', 'schoolInfo'));
    }

    /* ══════════════════════════════════════════
       LOAD STUDENTS (AJAX)
    ══════════════════════════════════════════ */
    public function loadStudents(Request $request)
    {
        try {
            $perPage = in_array((int)$request->get('per_page'), [20, 40, 60, 100])
                ? (int)$request->get('per_page') : 20;
            $search  = trim($request->get('search', ''));
            $classId = $request->get('class_id');

            $query = DB::table('studentRegistration')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('studentclass',   'studentclass.studentId',   '=', 'studentRegistration.id')
                ->leftJoin('schoolclass',    'schoolclass.id',            '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm',      'schoolarm.id',              '=', 'schoolclass.arm')
                ->select([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                ])
                ->groupBy([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                ])
                ->orderBy('studentRegistration.lastname');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('studentRegistration.firstname',     'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.lastname',    'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.admissionNo', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($classId)) {
                $query->where('studentclass.schoolclassid', $classId);
            }

            return response()->json(['success' => true, 'data' => $query->paginate($perPage)]);

        } catch (\Exception $e) {
            Log::error('ID Card loadStudents: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ══════════════════════════════════════════
       PREVIEW
    ══════════════════════════════════════════ */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:studentRegistration,id',
            'orientation'   => 'required|in:portrait,landscape',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $students    = $this->fetchStudentsForCards($request->student_ids);
            $schoolInfo  = SchoolInformation::getActiveSchool();
            $orientation = $request->orientation;

            $html = view('student.idcard.preview',
                compact('students', 'schoolInfo', 'orientation'))->render();

            return response()->json(['success' => true, 'html' => $html, 'count' => $students->count()]);

        } catch (\Exception $e) {
            Log::error('ID Card preview: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ══════════════════════════════════════════
       DOWNLOAD PDF
    ══════════════════════════════════════════ */
    public function download(Request $request)
    {
        $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:studentRegistration,id',
            'orientation'   => 'required|in:portrait,landscape',
        ]);

        try {
            $students    = $this->fetchStudentsForCards($request->student_ids);
            $schoolInfo  = SchoolInformation::getActiveSchool();
            $orientation = $request->orientation;

            if ($students->isEmpty()) {
                return back()->with('error', 'No students found.');
            }

            $pdf = Pdf::loadView('student.idcard.pdf',
                compact('students', 'schoolInfo', 'orientation'))
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'isRemoteEnabled'      => true,
                    'isHtml5ParserEnabled' => true,
                    'defaultFont'          => 'DejaVu Sans',
                    'dpi'                  => 300,
                ]);

            return $pdf->download('student-id-cards-' . now()->format('Y-m-d-His') . '.pdf');

        } catch (\Exception $e) {
            Log::error('ID Card download: ' . $e->getMessage());
            return back()->with('error', 'PDF generation failed: ' . $e->getMessage());
        }
    }

    /* ══════════════════════════════════════════
       QR VERIFY  (public — no auth required)
       GET /student-id-cards/verify/{token}
    ══════════════════════════════════════════ */
    public function verify(string $token)
    {
        // Decode the base64 JSON payload embedded in the QR code
        $payload = json_decode(base64_decode($token), true);

        if (!$payload || empty($payload['id']) || empty($payload['adm'])) {
            return view('student.idcard.verify', [
                'valid'      => false,
                'student'    => null,
                'schoolInfo' => SchoolInformation::getActiveSchool(),
                'message'    => 'Invalid or unreadable QR code.',
            ]);
        }

        $student = DB::table('studentRegistration')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('studentclass',   'studentclass.studentId',   '=', 'studentRegistration.id')
            ->leftJoin('schoolclass',    'schoolclass.id',            '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm',      'schoolarm.id',              '=', 'schoolclass.arm')
            ->leftJoin('schoolsession',  'schoolsession.id',          '=', 'studentclass.sessionid')
            ->leftJoin('schoolterm',     'schoolterm.id',             '=', 'studentclass.termid')
            ->where('studentRegistration.id',          $payload['id'])
            ->where('studentRegistration.admissionNo', $payload['adm'])
            ->select([
                'studentRegistration.id',
                'studentRegistration.admissionNo',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentRegistration.dateofbirth',
                'studentRegistration.blood_group',
                'studentRegistration.nationality',
                'studentRegistration.student_status',
                'studentRegistration.student_category',
                'studentRegistration.admission_date',
                'studentRegistration.state',
                'studentRegistration.local',

                'studentpicture.picture',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolsession.session',
                'schoolterm.term',
            ])
            ->first();

        $schoolInfo = SchoolInformation::getActiveSchool();

        if (!$student) {
            return view('student.idcard.verify', [
                'valid'      => false,
                'student'    => null,
                'schoolInfo' => $schoolInfo,
                'message'    => 'Student not found. This ID card may be fraudulent.',
            ]);
        }

        return view('student.idcard.verify', [
            'valid'      => true,
            'student'    => $student,
            'schoolInfo' => $schoolInfo,
            'message'    => 'Identity verified successfully.',
        ]);
    }

    /* ══════════════════════════════════════════
       HELPER
    ══════════════════════════════════════════ */
    private function fetchStudentsForCards(array $ids)
    {
        return DB::table('studentRegistration')
            ->whereIn('studentRegistration.id', $ids)
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('studentclass',   'studentclass.studentId',   '=', 'studentRegistration.id')
            ->leftJoin('schoolclass',    'schoolclass.id',           '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm',      'schoolarm.id',             '=', 'schoolclass.arm')
            ->leftJoin('schoolsession',  'schoolsession.id',         '=', 'studentclass.sessionid')
            ->leftJoin('schoolterm',     'schoolterm.id',            '=', 'studentclass.termid')
            ->select([
                'studentRegistration.id',
                'studentRegistration.admissionNo',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentRegistration.dateofbirth',
                'studentRegistration.blood_group',
                'studentRegistration.nationality',
                'studentRegistration.student_status',
                'studentRegistration.student_category',
                'studentRegistration.admission_date',
                'studentRegistration.state',
                'studentRegistration.local',

                'studentpicture.picture',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolsession.session',
                'schoolterm.term',
            ])
            ->groupBy([
                'studentRegistration.id',
                'studentRegistration.admissionNo',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentRegistration.dateofbirth',
                'studentRegistration.blood_group',
                'studentRegistration.nationality',
                'studentRegistration.student_status',
                'studentRegistration.student_category',
                'studentRegistration.admission_date',
                'studentRegistration.state',
                'studentRegistration.local',

                'studentpicture.picture',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolsession.session',
                'schoolterm.term',
            ])
            ->orderBy('studentRegistration.lastname')
            ->get();
    }
}
