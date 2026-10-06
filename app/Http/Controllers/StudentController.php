<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\Broadsheet;
use App\Models\BroadsheetRecord;
use App\Models\BroadsheetRecordMock;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\ParentRegistration;
use App\Models\PromotionStatus;
use App\Models\ReportHistory;
use App\Models\Schoolclass;
use App\Models\Schoolhouse;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\StudentBatchModel;
use App\Models\StudentBillInvoice;
use App\Models\StudentBillPayment;
use App\Models\StudentBillPaymentBook;
use App\Models\StudentBillPaymentRecord;
use App\Models\Studentclass;
use App\Models\StudentCurrentTerm;
use App\Models\Studenthouse;
use App\Models\Studenthouses;
use App\Models\Studentpersonalityprofile;
use App\Models\Studentpersonalityprofiles;
use App\Models\Studentpicture;
use App\Models\Subjectclass;
use App\Models\Club;
use App\Models\Sport;
use App\Models\StudentClub;
use App\Models\StudentSport;
use App\Models\SubjectRegistrationStatus;
use App\Models\ScholarshipAssignment;
use App\Models\DiscountAssignment;
use App\Models\ScholarshipApplication;
use App\Models\PaymentBatch;
use App\Traits\ImageManager as TraitsImageManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Jobs\ProcessStudentBatchImport;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    use TraitsImageManager;

    public function __construct()
    {
        $this->middleware("permission:View student|Show Student|Create student|Update student|Delete student", ["only" => ["index", "store"]]);
        $this->middleware("permission:Create student", ["only" => ["create", "store"]]);
        $this->middleware("permission:Update student", ["only" => ["edit", "update"]]);
        $this->middleware("permission:Delete student", ["only" => ["destroy", "deletestudent"]]);
        $this->middleware("permission:Create student-bulk-upload", ["only" => ["bulkupload"]]);
        $this->middleware("permission:Create student-bulk-uploadsave", ["only" => ["bulkuploadsave"]]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Student Management";

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass, ' - ', schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')
            ->get();
        $schoolterms = Schoolterm::select('id', 'term as name')->get();
        $schoolsessions = Schoolsession::select('id', 'session as name')->get();
        $currentSession = Schoolsession::where('status', 'Current')->first();
        $schoolhouses = Schoolhouse::all();
        $clubs = Club::orderBy('club')->get(['id', 'club']);
        $sports = Sport::orderBy('sport')->get(['id', 'sport']);

        $status_counts = Student::groupBy('statusId')
            ->selectRaw("CASE WHEN statusId = 1 THEN 'Old Student' ELSE 'New Student' END as student_status, COUNT(*) as student_count")
            ->pluck('student_count', 'student_status')
            ->toArray();
        $status_counts = [
            'Old Student' => $status_counts['Old Student'] ?? 0,
            'New Student' => $status_counts['New Student'] ?? 0
        ];

        $student_status_counts = Student::groupBy('student_status')
            ->selectRaw('student_status, COUNT(*) as status_count')
            ->pluck('status_count', 'student_status')
            ->toArray();
        $student_status_counts = [
            'Active'   => $student_status_counts['Active'] ?? 0,
            'Inactive' => $student_status_counts['Inactive'] ?? 0
        ];

        $gender_counts = Student::groupBy('gender')
            ->selectRaw('gender, COUNT(*) as gender_count')
            ->pluck('gender_count', 'gender')
            ->toArray();
        $gender_counts = [
            'Male'   => $gender_counts['Male'] ?? 0,
            'Female' => $gender_counts['Female'] ?? 0
        ];

        $religion_counts = Student::groupBy('religion')
            ->selectRaw('religion, COUNT(*) as religion_count')
            ->pluck('religion_count', 'religion')
            ->toArray();
        $religion_counts = [
            'Christianity' => $religion_counts['Christianity'] ?? 0,
            'Islam'        => $religion_counts['Islam'] ?? 0,
            'Others'       => $religion_counts['Others'] ?? 0
        ];

        $total_population = Student::count();

        $staff_count = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereNotIn('name', ['Student']);
        })->count();

        $currentTerm = Schoolterm::where('status', 'Current')->first();

        return view('student.index', compact(
            'schoolclasses',
            'schoolterms',
            'schoolsessions',
            'schoolhouses',
            'clubs',
            'sports',
            'currentSession',
            'status_counts',
            'student_status_counts',
            'gender_counts',
            'religion_counts',
            'pagetitle',
            'total_population',
            'staff_count',
            'currentTerm',
        ));
    }

    /**
     * Get students optimized with server-side pagination and filtering.
     * Fixed: robust N/A date handling, no ONLY_FULL_GROUP_BY issues.
     */
    public function getStudentsOptimized(Request $request)
    {
        try {
            $perPage   = $request->get('per_page', 12);
            $search    = $request->get('search', '');
            $classId   = $request->get('class_id', 'all');
            $status    = $request->get('status', 'all');
            $gender    = $request->get('gender', 'all');
            $sessionId = $request->get('session_id', 'all');

            Log::info('getStudentsOptimized called', compact('perPage','search','classId','status','gender','sessionId'));

            // --- Build ID query first to avoid ONLY_FULL_GROUP_BY ---
            $idQuery = Student::query()
                ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
                ->select('studentRegistration.id');

            if (!empty($search)) {
                $idQuery->where(function ($q) use ($search) {
                    $q->where('studentRegistration.firstname', 'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.lastname',  'LIKE', "%{$search}%")
                      ->orWhere('studentRegistration.admissionNo','LIKE',"%{$search}%")
                      ->orWhere('studentRegistration.othername', 'LIKE', "%{$search}%");
                });
            }

            if ($classId !== 'all' && !empty($classId)) {
                $idQuery->where('studentclass.schoolclassid', $classId);
            }

            if ($status !== 'all' && !empty($status)) {
                if (in_array($status, ['1', '2'])) {
                    $idQuery->where('studentRegistration.statusId', $status);
                } elseif (in_array($status, ['Active', 'Inactive'])) {
                    $idQuery->where('studentRegistration.student_status', $status);
                }
            }

            if ($gender !== 'all' && !empty($gender)) {
                $idQuery->where('studentRegistration.gender', $gender);
            }

            if ($sessionId !== 'all' && !empty($sessionId)) {
                $idQuery->where('studentclass.sessionid', $sessionId);
            }

            $idQuery->groupBy('studentRegistration.id');

            $paginatedIds = $idQuery->paginate($perPage, ['studentRegistration.id'], 'page', $request->get('page', 1));
            $studentIds   = $paginatedIds->pluck('id')->toArray();

            if (empty($studentIds)) {
                return response()->json([
                    'success' => true,
                    'data'    => new \Illuminate\Pagination\LengthAwarePaginator(
                        [], 0, $perPage,
                        $request->get('page', 1),
                        ['path' => $request->url(), 'query' => $request->query()]
                    )
                ]);
            }

            $students = Student::query()
                ->leftJoin('studentpicture',      'studentpicture.studentid',      '=', 'studentRegistration.id')
                ->leftJoin('studentclass',        'studentclass.studentId',        '=', 'studentRegistration.id')
                ->leftJoin('schoolclass',         'schoolclass.id',                '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm',           'schoolarm.id',                  '=', 'schoolclass.arm')
                ->leftJoin('parentRegistration',  'parentRegistration.studentId',  '=', 'studentRegistration.id')
                ->leftJoin('studenthouses',       'studenthouses.studentid',       '=', 'studentRegistration.id')
                ->leftJoin('schoolhouses',        'schoolhouses.id',               '=', 'studenthouses.schoolhouse')
                ->leftJoin('studentclubs',        'studentclubs.studentid',        '=', 'studentRegistration.id')
                ->leftJoin('clubs',                'clubs.id',                      '=', 'studentclubs.clubid')
                ->leftJoin('studentsports',       'studentsports.studentid',       '=', 'studentRegistration.id')
                ->leftJoin('sports',               'sports.id',                     '=', 'studentsports.sportid')
                ->whereIn('studentRegistration.id', $studentIds)
                ->select([
                    'studentRegistration.*',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'studentclass.schoolclassid',
                    'studentclass.termid',
                    'studentclass.sessionid',
                    'parentRegistration.father',
                    'parentRegistration.mother',
                    'parentRegistration.father_phone',
                    'parentRegistration.mother_phone',
                    'parentRegistration.father_occupation',
                    'parentRegistration.father_city',
                    'parentRegistration.office_address',
                    'parentRegistration.parent_email',
                    'parentRegistration.parent_address',
                    'parentRegistration.father_title',
                    'parentRegistration.mother_title',
                    'parentRegistration.guardian_name',
                    'parentRegistration.guardian_relationship',
                    'parentRegistration.guardian_phone',
                    'parentRegistration.whatsapp_number',
                    'schoolhouses.house as school_house',
                    'studentclubs.clubid',
                    'clubs.club as club_name',
                    'studentsports.sportid',
                    'sports.sport as sport_name',
                ])
                ->orderBy('studentRegistration.created_at', 'desc')
                ->get();

            $groupedStudents = $students->groupBy('id')->map(fn($g) => $g->first())->values();

            $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
                $groupedStudents,
                $paginatedIds->total(),
                $paginatedIds->perPage(),
                $paginatedIds->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $processedStudents = $paginatedData->getCollection()->map(function ($student) {
                try {
                    $age = null;
                    if ($student->dateofbirth && $student->dateofbirth !== 'N/A'
                        && !str_contains($student->dateofbirth, 'N/A')
                        && !str_contains($student->dateofbirth, '0000-00-00')) {
                        try { $age = Carbon::parse($student->dateofbirth)->age; } catch (\Exception $e) {}
                    }

                    $safeDate = function ($value) {
                        if (!$value || $value === 'N/A' || str_contains($value, 'N/A') || str_contains($value, '0000-00-00')) {
                            return null;
                        }
                        try { return Carbon::parse($value)->format('Y-m-d'); } catch (\Exception $e) { return null; }
                    };

                    $safeDatetime = function ($value) {
                        if (!$value || $value === 'N/A' || str_contains($value, 'N/A')) return null;
                        try { return Carbon::parse($value)->format('Y-m-d H:i:s'); } catch (\Exception $e) { return null; }
                    };

                    return [
                        'id'                => $student->id,
                        'admissionNo'       => $student->admissionNo,
                        'admission_date'    => $safeDate($student->admission_date),
                        'admissionYear'     => $student->admissionYear,
                        'firstname'         => $student->firstname ?? '',
                        'lastname'          => $student->lastname  ?? '',
                        'othername'         => $student->othername ?? '',
                        'fullname'          => trim(($student->lastname ?? '').' '.($student->firstname ?? '').' '.($student->othername ?? '')),
                        'gender'            => $student->gender,
                        'statusId'          => $student->statusId,
                        'student_status'    => $student->student_status,
                        'created_at'        => $safeDatetime($student->created_at),
                        'updated_at'        => $safeDatetime($student->updated_at),
                        'picture'           => $student->picture,
                        'schoolclass'       => $student->schoolclass,
                        'arm'               => $student->arm,
                        'schoolclassid'     => $student->schoolclassid,
                        'termid'            => $student->termid,
                        'sessionid'         => $student->sessionid,
                        'age'               => $age,
                        'dateofbirth'       => $safeDate($student->dateofbirth),
                        'title'             => $student->title,
                        'placeofbirth'      => $student->placeofbirth,
                        'phone_number'      => $student->phone_number,
                        'email'             => $student->email,
                        'permanent_address' => $student->home_address2,
                        'future_ambition'   => $student->future_ambition,
                        'nationality'       => $student->nationality,
                        'state'             => $student->state,
                        'local'             => $student->local,
                        'city'              => $student->city,
                        'religion'          => $student->religion,
                        'blood_group'       => $student->blood_group,
                        'genotype'          => $student->genotype,
                        'mother_tongue'     => $student->mother_tongue,
                        'emergency_contact_name'       => $student->emergency_contact_name,
                        'emergency_contact_phone'      => $student->emergency_contact_phone,
                        'allergies_medical_conditions' => $student->allergies_medical_conditions,
                        'nin_number'        => $student->nin_number,
                        'student_category'  => $student->student_category,
                        'last_school'       => $student->last_school,
                        'last_class'        => $student->last_class,
                        'reason_for_leaving'=> $student->reason_for_leaving,
                        'father_name'       => $student->father,
                        'father_title'      => $student->father_title,
                        'father_phone'      => $student->father_phone,
                        'father_occupation' => $student->father_occupation,
                        'father_city'       => $student->father_city,
                        'mother_name'       => $student->mother,
                        'mother_title'      => $student->mother_title,
                        'mother_phone'      => $student->mother_phone,
                        'parent_email'      => $student->parent_email,
                        'parent_address'    => $student->parent_address,
                        'guardian_name'         => $student->guardian_name,
                        'guardian_relationship' => $student->guardian_relationship,
                        'guardian_phone'        => $student->guardian_phone,
                        'whatsapp_number'       => $student->whatsapp_number,
                        'office_address'    => $student->office_address,
                        'school_house'      => $student->school_house,
                        'clubid'            => $student->clubid,
                        'club_name'         => $student->club_name,
                        'sportid'           => $student->sportid,
                        'sport_name'        => $student->sport_name,
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing student ID '.($student->id ?? 'unknown').': '.$e->getMessage());
                    return [
                        'id'        => $student->id,
                        'admissionNo'=> $student->admissionNo,
                        'firstname' => $student->firstname ?? '',
                        'lastname'  => $student->lastname  ?? '',
                        'fullname'  => trim(($student->lastname ?? '').' '.($student->firstname ?? '')),
                        'error'     => 'Failed to process student data',
                    ];
                }
            });

            $paginatedData->setCollection($processedStudents);

            return response()->json(['success' => true, 'data' => $paginatedData]);

        } catch (\Exception $e) {
            Log::error('Error in getStudentsOptimized: '.$e->getMessage()."\n".$e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Failed to fetch students: '.$e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        Log::debug('Creating new student', $request->all());

        try {
            $statesLgas = json_decode(file_get_contents(public_path('states_lgas.json')), true);
            $states     = array_column($statesLgas, 'state');
            $lgas       = collect($statesLgas)->pluck('lgas', 'state')->toArray();

            $validator = Validator::make($request->all(), [
                'avatar'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'admissionMode'      => 'required|in:auto,manual',
                'title'              => 'nullable|in:Master,Miss',
                'admissionNo'        => ['required','string','max:255','unique:studentRegistration,admissionNo'],
                'admissionYear'      => 'required|integer|min:1900|max:'.date('Y'),
                'admissionDate'      => 'required|date|before_or_equal:today',
                'firstname'          => 'required|string|max:255',
                'lastname'           => 'required|string|max:255',
                'othername'          => 'nullable|string|max:255',
                'gender'             => 'required|in:Male,Female',
                'dateofbirth'        => 'required|date|before:today',
                'placeofbirth'       => 'required|string|max:255',
                'nationality'        => 'required|string|max:255',
                'age'                => 'required|integer|min:1|max:100',
                'blood_group'        => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'genotype'           => 'nullable|in:AA,AS,SS,AC,SC,CC',
                'mother_tongue'      => 'nullable|string|max:255',
                'religion'           => 'required|in:Christianity,Islam,Others',
                'sport_house'        => 'nullable|string|max:255',
                'phone_number'       => 'nullable|string|max:20',
                'email'              => 'nullable|email|max:255',
                'nin_number'         => 'nullable|string|max:20',
                'city'               => 'nullable|string|max:255',
                'emergency_contact_name'       => 'nullable|string|max:255',
                'emergency_contact_phone'      => 'nullable|string|max:20',
                'allergies_medical_conditions' => 'nullable|string|max:1000',
                'state'              => ['required','string','max:255', function ($a,$v,$fail) use ($states) {
                    if (!in_array($v,$states)) $fail('The selected state is invalid.');
                }],
                'local'              => ['required','string','max:255', function ($a,$v,$fail) use ($request,$lgas) {
                    $s = $request->input('state');
                    if (!isset($lgas[$s]) || !in_array($v,$lgas[$s])) $fail('The selected LGA is invalid for the chosen state.');
                }],
                'future_ambition'    => 'required|string|max:500',
                'permanent_address'  => 'required|string|max:255',
                'student_category'   => 'required|in:Day,Boarding',
                'schoolclassid'      => 'required|exists:schoolclass,id',
                'schoolhouseid'      => 'required|exists:schoolhouses,id',
                'clubid'             => 'nullable|exists:clubs,id',
                'sportid'            => 'nullable|exists:sports,id',
                'termid'             => 'required|exists:schoolterm,id',
                'sessionid'          => 'required|exists:schoolsession,id',
                'statusId'           => 'required|in:1,2',
                'student_status'     => 'required|in:Active,Inactive',
                'father_title'       => 'nullable|in:Mr,Dr,Prof',
                'mother_title'       => 'nullable|in:Mrs,Dr,Prof',
                'father_name'        => 'nullable|string|max:255',
                'mother_name'        => 'nullable|string|max:255',
                'father_occupation'  => 'nullable|string|max:255',
                'father_city'        => 'nullable|string|max:255',
                'office_address'     => 'nullable|string|max:255',
                'father_phone'       => 'nullable|string|max:20',
                'mother_phone'       => 'nullable|string|max:20',
                'parent_email'       => 'nullable|email|max:255',
                'parent_address'     => 'nullable|string|max:255',
                'guardian_name'         => 'nullable|string|max:255',
                'guardian_relationship' => 'nullable|string|max:100',
                'guardian_phone'        => 'nullable|string|max:20',
                'whatsapp_number'       => 'nullable|string|max:20',
                'last_school'        => 'nullable|string|max:255',
                'last_class'         => 'nullable|string|max:255',
                'reason_for_leaving' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()], 422);
                }
                return redirect()->route('student.index')->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $student = new Student();

            if ($request->admissionMode === 'auto') {
                $admissionResponse = $this->getLastAdmissionNumber(new Request(['year' => $request->admissionYear]));
                $admissionData     = json_decode($admissionResponse->getContent(), true);
                if (!$admissionData['success']) throw new \Exception('Failed to generate admission number: '.$admissionData['message']);
                $student->admissionNo = $admissionData['admissionNo'];
            } else {
                $student->admissionNo = $request->admissionNo;
            }

            $student->admission_date     = $request->admissionDate;
            $student->title              = $request->title;
            $student->admissionYear      = $request->admissionYear;
            $student->firstname          = $request->firstname;
            $student->lastname           = $request->lastname;
            $student->othername          = $request->othername;
            $student->gender             = $request->gender;
            $student->dateofbirth        = $request->dateofbirth;
            $student->age                = $request->age;
            $student->blood_group        = $request->blood_group;
            $student->genotype           = $request->genotype;
            $student->mother_tongue      = $request->mother_tongue;
            $student->religion           = $request->religion;
            $student->sport_house        = $request->sport_house;
            $student->phone_number       = $request->phone_number;
            $student->email              = $request->email;
            $student->nin_number         = $request->nin_number;
            $student->city               = $request->city;
            $student->emergency_contact_name       = $request->emergency_contact_name;
            $student->emergency_contact_phone      = $request->emergency_contact_phone;
            $student->allergies_medical_conditions = $request->allergies_medical_conditions;
            $student->state              = $request->state;
            $student->local              = $request->local;
            $student->nationality        = $request->nationality;
            $student->placeofbirth       = $request->placeofbirth;
            $student->future_ambition    = $request->future_ambition;
            $student->home_address2      = $request->permanent_address;
            $student->student_category   = $request->student_category;
            $student->statusId           = $request->statusId;
            $student->student_status     = $request->student_status;
            $student->last_school        = $request->last_school;
            $student->last_class         = $request->last_class;
            $student->reason_for_leaving = $request->reason_for_leaving;
            $student->registeredBy       = auth()->user()->id;
            $student->save();

            $studentId = $student->id;

            Studentclass::create([
                'studentId'     => $studentId,
                'schoolclassid' => $request->schoolclassid,
                'termid'        => $request->termid,
                'sessionid'     => $request->sessionid,
            ]);

            PromotionStatus::create([
                'studentId'       => $studentId,
                'schoolclassid'   => $request->schoolclassid,
                'termid'          => $request->termid,
                'sessionid'       => $request->sessionid,
                'promotionStatus' => 'PROMOTED',
                'classstatus'     => 'CURRENT',
            ]);

            $parent               = new ParentRegistration();
            $parent->studentId    = $studentId;
            $parent->father_title = $request->father_title;
            $parent->mother_title = $request->mother_title;
            $parent->father       = $request->father_name;
            $parent->mother       = $request->mother_name;
            $parent->father_phone = $request->father_phone;
            $parent->mother_phone = $request->mother_phone;
            $parent->father_occupation = $request->father_occupation;
            $parent->father_city  = $request->father_city;
            $parent->office_address = $request->office_address;
            $parent->parent_email = $request->parent_email;
            $parent->parent_address = $request->parent_address;
            $parent->guardian_name         = $request->guardian_name;
            $parent->guardian_relationship = $request->guardian_relationship;
            $parent->guardian_phone        = $request->guardian_phone;
            $parent->whatsapp_number       = $request->whatsapp_number;
            $parent->save();

            $picture            = new Studentpicture();
            $picture->studentid = $studentId;
            if ($request->hasFile('avatar')) {
                $path = $this->storeImage($request->file('avatar'), 'images/student_avatars');
                $picture->picture = basename($path);
            } else {
                $picture->picture = 'unnamed.jpg';
            }
            $picture->save();

            $studenthouses            = new Studenthouse();
            $studenthouses->studentid = $studentId;
            $studenthouses->schoolhouse = $request->schoolhouseid;
            $studenthouses->termid    = $request->termid;
            $studenthouses->sessionid = $request->sessionid;
            $studenthouses->save();

            if ($request->filled('clubid')) {
                StudentClub::create([
                    'studentid' => $studentId,
                    'clubid'    => $request->clubid,
                    'termid'    => $request->termid,
                    'sessionid' => $request->sessionid,
                ]);
            }

            if ($request->filled('sportid')) {
                StudentSport::create([
                    'studentid' => $studentId,
                    'sportid'   => $request->sportid,
                    'termid'    => $request->termid,
                    'sessionid' => $request->sessionid,
                ]);
            }

            $studentpersonalityprofiles              = new Studentpersonalityprofile();
            $studentpersonalityprofiles->studentid   = $studentId;
            $studentpersonalityprofiles->schoolclassid = $request->schoolclassid;
            $studentpersonalityprofiles->termid      = $request->termid;
            $studentpersonalityprofiles->sessionid   = $request->sessionid;
            $studentpersonalityprofiles->save();

            StudentCurrentTerm::create([
                'studentId'     => $studentId,
                'schoolclassId' => $request->schoolclassid,
                'termId'        => $request->termid,
                'sessionId'     => $request->sessionid,
                'is_current'    => true,
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student created successfully',
                    'student' => ['id' => $student->id, 'admissionNo' => $student->admissionNo],
                ], 201);
            }

            return redirect()->route('student.index')->with('success', 'Student created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating student: {$e->getMessage()}\n{$e->getTraceAsString()}");
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success'=>false,'message'=>'Failed to create student: '.$e->getMessage()], 500);
            }
            return redirect()->route('student.index')->with('error', 'Failed to create student: '.$e->getMessage());
        }
    }

    protected function storeImage($file, $directory)
    {
        try {
            return $file->store($directory, 'public');
        } catch (\Exception $e) {
            Log::error("Error storing image: {$e->getMessage()}");
            throw $e;
        }
    }

    public function data(Request $request): JsonResponse
    {
        try {
            $students = Student::leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('studentclass',  'studentclass.studentId',  '=', 'studentRegistration.id')
                ->leftJoin('schoolclass',   'schoolclass.id',           '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm',     'schoolarm.id',             '=', 'schoolclass.arm')
                ->select([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.statusId',
                    'studentRegistration.student_status',
                    'studentRegistration.created_at',
                    'studentpicture.picture',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'studentclass.schoolclassid',
                ])
                ->latest()
                ->get();

            return response()->json(['success' => true, 'students' => $students], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching students: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to fetch students: '.$e->getMessage()], 500);
        }
    }

    protected function generateAdmissionNumber()
    {
        $lastAdmission = Student::max('admissionNo');
        $year   = date('Y');
        $number = $lastAdmission ? (int) substr($lastAdmission, -4) + 1 : 1;
        return sprintf('TCC/2025/%04d', $number);
    }

    public function show($id)
    {
        try {
            $student = Student::where('studentRegistration.id', $id)
                ->leftJoin('studentclass',      'studentclass.studentId',    '=','studentRegistration.id')
                ->leftJoin('parentRegistration','parentRegistration.studentId','=','studentRegistration.id')
                ->leftJoin('studentpicture',    'studentpicture.studentid',  '=','studentRegistration.id')
                ->leftJoin('schoolclass',       'schoolclass.id',            '=','studentclass.schoolclassid')
                ->leftJoin('schoolarm',         'schoolarm.id',              '=','schoolclass.arm')
                ->leftJoin('schoolterm',        'schoolterm.id',             '=','studentclass.termid')
                ->leftJoin('schoolsession',     'schoolsession.id',          '=','studentclass.sessionid')
                ->leftJoin('studenthouses',     'studenthouses.studentId',   '=','studentRegistration.id')
                ->leftJoin('schoolhouses',      'schoolhouses.id',           '=','studenthouses.schoolhouse')
                ->where('schoolsession.status', 'Current')
                ->select([
                    'studentRegistration.id as id',
                    'studentRegistration.admissionNo as student_id',
                    'studentRegistration.firstname as first_name',
                    'studentRegistration.lastname as last_name',
                    'studentRegistration.othername as middle_name',
                    'studentRegistration.gender as gender',
                    'studentRegistration.dateofbirth as date_of_birth',
                    'studentRegistration.blood_group as blood_group',
                    'studentRegistration.admission_date as admission_date',
                    'studentRegistration.student_category as student_category',
                    'studentRegistration.mother_tongue as mother_tongue',
                    'studentRegistration.sport_house as sport_house',
                    'studentRegistration.phone_number as phone_number',
                    'studentRegistration.email as email',
                    'studentRegistration.nin_number as nin_number',
                    'studentRegistration.city as city',
                    'studentRegistration.statusId as statusId',
                    'studentRegistration.student_status as student_status',
                    'studentpicture.picture as picture',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as arm',
                    'schoolterm.term as term',
                    'schoolsession.session as session',
                    'schoolhouses.house as schoolhouse',
                ])
                ->firstOrFail();

            $billPayments     = StudentBillPayment::where('student_id', $id)->with(['schoolBill','studentBillPaymentRecords'])->get();
            $billPaymentBooks = StudentBillPaymentBook::where('student_id', $id)->get();

            return view('student.show', compact('student','billPayments','billPaymentBooks'));
        } catch (\Exception $e) {
            return redirect()->route('student.index')->with('error', 'Student not found.');
        }
    }

    public function create()
    {
        $pagetitle    = "Create Student";
        $schoolclasses = Schoolclass::leftJoin('schoolarm','schoolarm.id','=','schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass,' - ',schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')->get();
        $schoolterms   = Schoolterm::select('id','term as name')->get();
        $schoolsessions= Schoolsession::select('id','session as name')->get();
        $currentSession= Schoolsession::where('status','Current')->first();

        return view('student.create', compact('schoolclasses','schoolterms','schoolsessions','currentSession','pagetitle'));
    }

    /**
     * Return student data for the edit modal — always fetches fresh from DB.
     */
    public function edit($student)
    {
        try {
            $studentData = Student::where('studentRegistration.id', $student)
                ->leftJoin('studentpicture',    'studentRegistration.id', '=','studentpicture.studentid')
                ->leftJoin('studentclass',      'studentRegistration.id', '=','studentclass.studentId')
                ->leftJoin('parentRegistration','studentRegistration.id', '=','parentRegistration.studentId')
                ->leftJoin('schoolclass',       'schoolclass.id',         '=','studentclass.schoolclassid')
                ->leftJoin('schoolarm',         'schoolarm.id',           '=','schoolclass.arm')
                ->leftJoin('schoolterm',        'schoolterm.id',          '=','studentclass.termid')
                ->leftJoin('schoolsession',     'schoolsession.id',       '=','studentclass.sessionid')
                ->leftJoin('studenthouses',     'studenthouses.studentId','=','studentRegistration.id')
                ->leftJoin('schoolhouses',      'schoolhouses.id',        '=','studenthouses.schoolhouse')
                ->leftJoin('studentclubs',      'studentclubs.studentid', '=','studentRegistration.id')
                ->leftJoin('clubs',             'clubs.id',               '=','studentclubs.clubid')
                ->leftJoin('studentsports',     'studentsports.studentid','=','studentRegistration.id')
                ->leftJoin('sports',            'sports.id',              '=','studentsports.sportid')
                ->select([
                    'studentRegistration.id',
                    'studentRegistration.admissionNo',
                    'studentRegistration.admissionYear',
                    'studentRegistration.admission_date as admissionDate',
                    'studentRegistration.title',
                    'studentRegistration.firstname',
                    'studentRegistration.lastname',
                    'studentRegistration.othername',
                    'studentRegistration.gender',
                    'studentRegistration.dateofbirth',
                    'studentRegistration.age',
                    'studentRegistration.blood_group',
                    'studentRegistration.genotype',
                    'studentRegistration.mother_tongue',
                    'studentRegistration.emergency_contact_name',
                    'studentRegistration.emergency_contact_phone',
                    'studentRegistration.allergies_medical_conditions',
                    'studentRegistration.religion',
                    'studentRegistration.sport_house',
                    'studentRegistration.phone_number',
                    'studentRegistration.email',
                    'studentRegistration.nin_number',
                    'studentRegistration.city',
                    'studentRegistration.state',
                    'studentRegistration.local',
                    'studentRegistration.nationality',
                    'studentRegistration.placeofbirth',
                    'studentRegistration.future_ambition',
                    'studentRegistration.home_address2 as permanent_address',
                    'studentRegistration.student_category',
                    'studentRegistration.statusId',
                    'studentRegistration.student_status',
                    'studentRegistration.last_school',
                    'studentRegistration.last_class',
                    'studentRegistration.reason_for_leaving',
                    'studentclass.schoolclassid',
                    'studentclass.termid',
                    'studentclass.sessionid',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'schoolterm.term as term_name',
                    'schoolsession.session as session_name',
                    'parentRegistration.father_title',
                    'parentRegistration.mother_title',
                    'parentRegistration.father as father_name',
                    'parentRegistration.mother as mother_name',
                    'parentRegistration.father_occupation',
                    'parentRegistration.father_city',
                    'parentRegistration.office_address',
                    'parentRegistration.father_phone',
                    'parentRegistration.mother_phone',
                    'parentRegistration.parent_email',
                    'parentRegistration.parent_address',
                    'parentRegistration.guardian_name',
                    'parentRegistration.guardian_relationship',
                    'parentRegistration.guardian_phone',
                    'parentRegistration.whatsapp_number',
                    'studentpicture.picture',
                    'studenthouses.schoolhouse as schoolhouseid',
                    'schoolhouses.house as school_house',
                    'studentclubs.clubid',
                    'clubs.club as club_name',
                    'studentsports.sportid',
                    'sports.sport as sport_name',
                ])
                ->first();

            if (!$studentData) {
                return response()->json(['success'=>false,'message'=>'Student not found'], 404);
            }

            return response()->json(['success'=>true,'student'=>$studentData], 200);

        } catch (\Exception $e) {
            Log::error("Error fetching student ID {$student}: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Server error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Update student.
     *
     * KEY FIXES vs original:
     * 1. Studentclass  — find-then-update (not updateOrCreate) so changing class
     *    updates the existing row instead of inserting a duplicate.
     * 2. PromotionStatus — scoped to all four fields; correct and unchanged.
     * 3. StudentCurrentTerm — correct; unchanged.
     */

// ============================================================================
// FIXED update() METHOD — drop this into StudentController.php
// ============================================================================


public function update(Request $request, $id): JsonResponse
{
    Log::debug('Updating student', ['id' => $id, 'data' => $request->except(['avatar', '_token'])]);

    try {
        // Load states/LGAs safely
        $states = [];
        $lgas   = [];

        $jsonPath = public_path('states_lgas.json');
        if (file_exists($jsonPath)) {
            try {
                $statesLgas = json_decode(file_get_contents($jsonPath), true);
                if (is_array($statesLgas)) {
                    $states = array_column($statesLgas, 'state');
                    $lgas   = collect($statesLgas)->pluck('lgas', 'state')->toArray();
                }
            } catch (\Exception $e) {
                Log::warning('Could not load states_lgas.json: ' . $e->getMessage());
            }
        }

        $hasStateData = !empty($states);

        // Validation rules
        $rules = [
            'avatar'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'admissionMode'      => 'required|in:auto,manual',
            'admissionNo'        => 'required|string|max:255|unique:studentRegistration,admissionNo,' . $id,
            'admissionYear'      => 'required|integer|min:1900|max:' . date('Y'),
            'admissionDate'      => 'required|date|before_or_equal:today',
            'title'              => 'nullable|in:Master,Miss',
            'firstname'          => 'required|string|max:255',
            'lastname'           => 'required|string|max:255',
            'othername'          => 'nullable|string|max:255',
            'gender'             => 'required|in:Male,Female',
            'dateofbirth'        => 'required|date|before:today',
            'placeofbirth'       => 'required|string|max:255',
            'nationality'        => 'required|string|max:255',
            'age'                => 'required|integer|min:1|max:100',
            'blood_group'        => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'genotype'           => 'nullable|in:AA,AS,SS,AC,SC,CC',
            'mother_tongue'      => 'nullable|string|max:255',
            'religion'           => 'required|in:Christianity,Islam,Others',
            'sport_house'        => 'nullable|string|max:255',
            'phone_number'       => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'nin_number'         => 'nullable|string|max:20',
            'city'               => 'nullable|string|max:255',
            'emergency_contact_name'       => 'nullable|string|max:255',
            'emergency_contact_phone'      => 'nullable|string|max:20',
            'allergies_medical_conditions' => 'nullable|string|max:1000',
            'state'              => 'required|string|max:255',
            'local'              => 'required|string|max:255',
            'future_ambition'    => 'required|string|max:500',
            'permanent_address'  => 'required|string|max:255',
            'student_category'   => 'required|in:Day,Boarding',
            'schoolclassid'      => 'required|exists:schoolclass,id',
            'schoolhouseid'      => 'nullable|exists:schoolhouses,id',
            'clubid'             => 'nullable|exists:clubs,id',
            'sportid'            => 'nullable|exists:sports,id',
            'termid'             => 'required|exists:schoolterm,id',
            'sessionid'          => 'required|exists:schoolsession,id',
            'statusId'           => 'required|in:1,2',
            'student_status'     => 'required|in:Active,Inactive',
            'father_title'       => 'nullable|in:Mr,Dr,Prof',
            'mother_title'       => 'nullable|in:Mrs,Dr,Prof',
            'father_name'        => 'nullable|string|max:255',
            'mother_name'        => 'nullable|string|max:255',
            'father_occupation'  => 'nullable|string|max:255',
            'father_city'        => 'nullable|string|max:255',
            'office_address'     => 'nullable|string|max:255',
            'father_phone'       => 'nullable|string|max:20',
            'mother_phone'       => 'nullable|string|max:20',
            'parent_email'       => 'nullable|email|max:255',
            'parent_address'     => 'nullable|string|max:255',
            'guardian_name'         => 'nullable|string|max:255',
            'guardian_relationship' => 'nullable|string|max:100',
            'guardian_phone'        => 'nullable|string|max:20',
            'whatsapp_number'       => 'nullable|string|max:20',
            'last_school'        => 'nullable|string|max:255',
            'last_class'         => 'nullable|string|max:255',
            'reason_for_leaving' => 'nullable|string|max:500',
        ];

        if ($hasStateData) {
            $rules['state'] = [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($states) {
                    if (!in_array($value, $states)) {
                        $fail('The selected state is invalid.');
                    }
                },
            ];

            $rules['local'] = [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($request, $lgas) {
                    $selectedState = $request->input('state');
                    if (!isset($lgas[$selectedState]) || !in_array($value, $lgas[$selectedState])) {
                        $fail('The selected LGA is invalid for the chosen state.');
                    }
                },
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Log::warning('Validation failed for student update', [
                'id'     => $id,
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        // 1. Core student record
        $student = Student::findOrFail($id);

        if ($request->admissionMode === 'auto') {
            $admissionResponse = $this->getLastAdmissionNumber(new Request(['year' => $request->admissionYear]));
            $admissionData     = json_decode($admissionResponse->getContent(), true);
            if (!$admissionData['success']) throw new \Exception('Failed to generate admission number: '.$admissionData['message']);
            $student->admissionNo = $admissionData['admissionNo'];
        } else {
            $student->admissionNo = $request->admissionNo;
        }
        $student->admission_date     = $request->admissionDate;
        $student->title              = $request->title;
        $student->admissionYear      = $request->admissionYear;
        $student->firstname          = $request->firstname;
        $student->lastname           = $request->lastname;
        $student->othername          = $request->othername;
        $student->gender             = $request->gender;
        $student->dateofbirth        = $request->dateofbirth;
        $student->age                = $request->age;
        $student->blood_group        = $request->blood_group;
        $student->genotype           = $request->genotype;
        $student->mother_tongue      = $request->mother_tongue;
        $student->religion           = $request->religion;
        $student->sport_house        = $request->sport_house;
        $student->phone_number       = $request->phone_number;
        $student->email              = $request->email;
        $student->nin_number         = $request->nin_number;
        $student->city               = $request->city;
        $student->emergency_contact_name       = $request->emergency_contact_name;
        $student->emergency_contact_phone      = $request->emergency_contact_phone;
        $student->allergies_medical_conditions = $request->allergies_medical_conditions;
        $student->state              = $request->state;
        $student->local              = $request->local;
        $student->nationality        = $request->nationality;
        $student->placeofbirth       = $request->placeofbirth;
        $student->future_ambition    = $request->future_ambition;
        $student->home_address2      = $request->permanent_address;
        $student->student_category   = $request->student_category;
        $student->statusId           = $request->statusId;
        $student->student_status     = $request->student_status;
        $student->last_school        = $request->last_school;
        $student->last_class         = $request->last_class;
        $student->reason_for_leaving = $request->reason_for_leaving;
        $student->registeredBy       = auth()->user()->id;
        $student->save();

        // 2. Studentclass
        $existingClass = Studentclass::where('studentId', $id)
            ->where('termid',    $request->termid)
            ->where('sessionid', $request->sessionid)
            ->first();

        if ($existingClass) {
            $existingClass->update(['schoolclassid' => $request->schoolclassid]);
        } else {
            Studentclass::create([
                'studentId'     => $id,
                'schoolclassid' => $request->schoolclassid,
                'termid'        => $request->termid,
                'sessionid'     => $request->sessionid,
            ]);
        }

        // 3. PromotionStatus
        PromotionStatus::updateOrCreate(
            [
                'studentId'     => $id,
                'schoolclassid' => $request->schoolclassid,
                'termid'        => $request->termid,
                'sessionid'     => $request->sessionid,
            ],
            [
                'promotionStatus' => 'PROMOTED',
                'classstatus'     => 'CURRENT',
            ]
        );

        // 4. Parent
        $parent = ParentRegistration::firstOrNew(['studentId' => $id]);
        $parent->father_title      = $request->father_title;
        $parent->mother_title      = $request->mother_title;
        $parent->father            = $request->father_name;
        $parent->mother            = $request->mother_name;
        $parent->father_phone      = $request->father_phone;
        $parent->mother_phone      = $request->mother_phone;
        $parent->father_occupation = $request->father_occupation;
        $parent->father_city       = $request->father_city;
        $parent->office_address    = $request->office_address;
        $parent->parent_email      = $request->parent_email;
        $parent->parent_address    = $request->parent_address;
        $parent->guardian_name         = $request->guardian_name;
        $parent->guardian_relationship = $request->guardian_relationship;
        $parent->guardian_phone        = $request->guardian_phone;
        $parent->whatsapp_number       = $request->whatsapp_number;
        $parent->save();

        // 5. Picture
        $picture = Studentpicture::firstOrNew(['studentid' => $id]);
        if ($request->hasFile('avatar')) {
            if ($picture->picture && $picture->picture !== 'unnamed.jpg') {
                $oldPath = storage_path('app/public/images/student_avatars/' . $picture->picture);
                if (file_exists($oldPath)) {
                    try {
                        Storage::delete('public/images/student_avatars/' . $picture->picture);
                    } catch (\Exception $e) {
                        Log::warning('Could not delete old avatar: ' . $e->getMessage());
                    }
                }
            }
            $path            = $this->storeImage($request->file('avatar'), 'images/student_avatars');
            $picture->picture = basename($path);
        }
        if (!$picture->picture) {
            $picture->picture = 'unnamed.jpg';
        }
        $picture->save();

        // 6. Student house
        if ($request->filled('schoolhouseid')) {
            Studenthouse::updateOrCreate(
                [
                    'studentid' => $id,
                    'termid'    => $request->termid,
                    'sessionid' => $request->sessionid,
                ],
                ['schoolhouse' => $request->schoolhouseid]
            );
        }

        // Club & Sport — keyed on studentid alone (matches their actual
        // primary key), so a student has at most one of each at a time,
        // with termid/sessionid updated to reflect when it was last set.
        if ($request->filled('clubid')) {
            StudentClub::updateOrCreate(
                ['studentid' => $id],
                ['clubid' => $request->clubid, 'termid' => $request->termid, 'sessionid' => $request->sessionid]
            );
        }

        if ($request->filled('sportid')) {
            StudentSport::updateOrCreate(
                ['studentid' => $id],
                ['sportid' => $request->sportid, 'termid' => $request->termid, 'sessionid' => $request->sessionid]
            );
        }

        // 7. Personality profile
        Studentpersonalityprofile::firstOrCreate([
            'studentid'     => $id,
            'schoolclassid' => $request->schoolclassid,
            'termid'        => $request->termid,
            'sessionid'     => $request->sessionid,
        ]);

        // 8. StudentCurrentTerm - FIXED
        // First, check if there's already a record for this specific combination
        $existingTerm = StudentCurrentTerm::where('studentId', $id)
            ->where('termId', $request->termid)
            ->where('sessionId', $request->sessionid)
            ->first();

        if ($existingTerm) {
            // Update existing record
            $existingTerm->update([
                'schoolclassId' => $request->schoolclassid,
                'is_current'    => true
            ]);

            // Set all other term records for this student to is_current = false
            StudentCurrentTerm::where('studentId', $id)
                ->where('id', '!=', $existingTerm->id)
                ->update(['is_current' => false]);
        } else {
            // Create new record - first, set all existing to false
            StudentCurrentTerm::where('studentId', $id)->update(['is_current' => false]);

            // Then create the new record
            StudentCurrentTerm::create([
                'studentId'     => $id,
                'schoolclassId' => $request->schoolclassid,
                'termId'        => $request->termid,
                'sessionId'     => $request->sessionid,
                'is_current'    => true,
            ]);
        }

        DB::commit();

        return response()->json([
            'success'  => true,
            'message'  => 'Student updated successfully',
            'redirect' => route('student.index'),
            'student'  => [
                'id'                 => $student->id,
                'admissionNo'        => $student->admissionNo,
                'admissionYear'      => $student->admissionYear,
                'title'              => $student->title,
                'firstname'          => $student->firstname,
                'lastname'           => $student->lastname,
                'othername'          => $student->othername,
                'gender'             => $student->gender,
                'dateofbirth'        => $student->dateofbirth,
                'placeofbirth'       => $student->placeofbirth,
                'nationality'        => $student->nationality,
                'religion'           => $student->religion,
                'last_school'        => $student->last_school,
                'last_class'         => $student->last_class,
                'schoolclassid'      => $request->schoolclassid,
                'termid'             => $request->termid,
                'sessionid'          => $request->sessionid,
                'phone_number'       => $student->phone_number,
                'nin_number'         => $student->nin_number,
                'blood_group'        => $student->blood_group,
                'genotype'           => $student->genotype,
                'mother_tongue'      => $student->mother_tongue,
                'emergency_contact_name'       => $student->emergency_contact_name,
                'emergency_contact_phone'      => $student->emergency_contact_phone,
                'allergies_medical_conditions' => $student->allergies_medical_conditions,
                'father_name'        => $parent->father          ?? '',
                'father_phone'       => $parent->father_phone    ?? '',
                'father_occupation'  => $parent->father_occupation ?? '',
                'mother_name'        => $parent->mother          ?? '',
                'mother_phone'       => $parent->mother_phone    ?? '',
                'parent_address'     => $parent->parent_address  ?? '',
                'guardian_name'         => $parent->guardian_name         ?? '',
                'guardian_relationship' => $parent->guardian_relationship ?? '',
                'guardian_phone'        => $parent->guardian_phone        ?? '',
                'whatsapp_number'       => $parent->whatsapp_number       ?? '',
                'student_category'   => $student->student_category,
                'reason_for_leaving' => $student->reason_for_leaving,
                'picture'            => $picture->picture        ?? 'unnamed.jpg',
                'state'              => $student->state,
                'local'              => $student->local,
                'statusId'           => $student->statusId,
                'student_status'     => $student->student_status,
                'future_ambition'    => $student->future_ambition,
                'permanent_address'  => $student->home_address2,
                'clubid'             => $request->clubid,
                'sportid'            => $request->sportid,
            ],
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        Log::error("Student ID {$id} not found during update");
        return response()->json(['success' => false, 'message' => 'Student not found'], 404);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error updating student ID {$id}: {$e->getMessage()}\n{$e->getTraceAsString()}");
        return response()->json([
            'success' => false,
            'message' => 'Failed to update student: ' . $e->getMessage(),
        ], 500);
    }
}



    protected function deleteImage($filename)
    {
        try {
            if ($filename && $filename !== 'unnamed.jpg'
                && Storage::exists('public/images/student_avatars/'.$filename)) {
                Storage::delete('public/images/student_avatars/'.$filename);
            }
        } catch (\Exception $e) {
            Log::error("Error deleting image: {$e->getMessage()}");
            throw $e;
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $student = Student::findOrFail($id);

            $picture = Studentpicture::where('studentid', $id)->first();
            if ($picture && $picture->picture) $this->deleteImage($picture->picture);

            $billPayments = StudentBillPayment::where('student_id', $id)->get();
            foreach ($billPayments as $bp) {
                StudentBillPaymentRecord::where('student_bill_payment_id', $bp->id)->delete();
                $bp->delete();
            }
            StudentBillPaymentBook::where('student_id', $id)->delete();
            StudentBillInvoice::where('student_id', $id)->delete();
            Studentclass::where('studentId', $id)->delete();
            PromotionStatus::where('studentId', $id)->delete();
            ParentRegistration::where('studentId', $id)->delete();
            Studentpicture::where('studentid', $id)->delete();

            $broadsheetRecords = BroadsheetRecord::where('student_id', $id)->get();
            foreach ($broadsheetRecords as $record) {
                Broadsheets::where('broadsheet_record_id', $record->id)->delete();
                $record->delete();
            }

            SubjectRegistrationStatus::where('studentId', $id)->delete();
            Studenthouse::where('studentid', $id)->delete();
            StudentClub::where('studentid', $id)->delete();
            StudentSport::where('studentid', $id)->delete();
            Studentpersonalityprofile::where('studentid', $id)->delete();
            StudentCurrentTerm::where('studentId', $id)->delete();

            // Scholarship/discount assignments soft-delete by default, which leaves the row
            // physically present and still blocking the FK RESTRICT below -- force-delete them.
            ScholarshipAssignment::where('student_id', $id)->forceDelete();
            DiscountAssignment::where('student_id', $id)->forceDelete();
            ScholarshipApplication::where('student_id', $id)->delete();
            PaymentBatch::where('student_id', $id)->delete();

            $student->delete();

            DB::commit();

            return response()->json(['success'=>true,'message'=>'Student deleted successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting student: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to delete student: '.$e->getMessage()], 500);
        }
    }

    public function destroyMultiple(Request $request): JsonResponse
    {
        try {
            $ids = $request->validate(['ids'=>'required|array|exists:studentRegistration,id'])['ids'];
            DB::beginTransaction();

            foreach ($ids as $id) {
                $picture = Studentpicture::where('studentid', $id)->first();
                if ($picture && $picture->picture) $this->deleteImage($picture->picture);

                $billPayments = StudentBillPayment::where('student_id', $id)->get();
                foreach ($billPayments as $bp) {
                    StudentBillPaymentRecord::where('student_bill_payment_id', $bp->id)->delete();
                    $bp->delete();
                }
                StudentBillPaymentBook::where('student_id', $id)->delete();
                StudentBillInvoice::where('student_id', $id)->delete();
                Studentclass::where('studentId', $id)->delete();
                PromotionStatus::where('studentId', $id)->delete();
                ParentRegistration::where('studentId', $id)->delete();
                Studentpicture::where('studentid', $id)->delete();
                Broadsheet::where('studentId', $id)->delete();
                SubjectRegistrationStatus::where('studentId', $id)->delete();
                Studenthouse::where('studentid', $id)->delete();
                StudentClub::where('studentid', $id)->delete();
                StudentSport::where('studentid', $id)->delete();
                Studentpersonalityprofile::where('studentid', $id)->delete();
                StudentCurrentTerm::where('studentId', $id)->delete();

                ScholarshipAssignment::where('student_id', $id)->forceDelete();
                DiscountAssignment::where('student_id', $id)->forceDelete();
                ScholarshipApplication::where('student_id', $id)->delete();
                PaymentBatch::where('student_id', $id)->delete();
            }

            Student::whereIn('id', $ids)->delete();

            DB::commit();

            return response()->json(['success'=>true,'message'=>'Students deleted successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bulk delete error: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to delete students: '.$e->getMessage()], 500);
        }
    }

    public function deletestudent(Request $request)
    {
        return $this->destroy($request->input('id'));
    }

    public function deletestudentbatch(Request $request): JsonResponse
    {
        $batchId = $request->input('studentbatchid');
        try {
            if (!Schema::hasTable('student_batch_upload')) throw new \Exception('student_batch_upload table does not exist');
            if (!Schema::hasColumn('studentRegistration','batchid')) throw new \Exception('batchid column missing');

            $batch = StudentBatchModel::findOrFail($batchId);
            DB::beginTransaction();

            $studentIds = Student::where('batchid', $batch->id)->pluck('id');
            foreach ($studentIds as $studentId) {
                $picture = Studentpicture::where('studentid', $studentId)->first();
                if ($picture && $picture->picture) $this->deleteImage($picture->picture);

                $billPayments = StudentBillPayment::where('student_id', $studentId)->get();
                foreach ($billPayments as $bp) {
                    StudentBillPaymentRecord::where('student_bill_payment_id', $bp->id)->delete();
                    $bp->delete();
                }
                StudentBillPaymentBook::where('student_id', $studentId)->delete();
                StudentBillInvoice::where('student_id', $studentId)->delete();

                $bsRecords = BroadsheetRecord::where('student_id', $studentId)->get();
                foreach ($bsRecords as $r) { Broadsheets::where('broadsheet_record_id', $r->id)->delete(); $r->delete(); }

                $bsMockRecords = BroadsheetRecordMock::where('student_id', $studentId)->get();
                foreach ($bsMockRecords as $r) { BroadsheetsMock::where('broadsheet_records_mock_id', $r->id)->delete(); $r->delete(); }

                Studentclass::where('studentId', $studentId)->delete();
                PromotionStatus::where('studentId', $studentId)->delete();
                ParentRegistration::where('studentId', $studentId)->delete();
                Studentpicture::where('studentid', $studentId)->delete();
                SubjectRegistrationStatus::where('studentId', $studentId)->delete();
                Studenthouse::where('studentid', $studentId)->delete();
                StudentClub::where('studentid', $studentId)->delete();
                StudentSport::where('studentid', $studentId)->delete();
                Studentpersonalityprofile::where('studentid', $studentId)->delete();
                StudentCurrentTerm::where('studentId', $studentId)->delete();

                ScholarshipAssignment::where('student_id', $studentId)->forceDelete();
                DiscountAssignment::where('student_id', $studentId)->forceDelete();
                ScholarshipApplication::where('student_id', $studentId)->delete();
                PaymentBatch::where('student_id', $studentId)->delete();
            }

            Student::where('batchid', $batch->id)->delete();
            $batch->delete();

            DB::commit();

            return response()->json(['success'=>true,'message'=>'Batch Upload has been removed'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Batch not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting batch ID {$batchId}: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to delete batch: '.$e->getMessage()], 500);
        }
    }

    public function bulkupload()
    {
        $pagetitle     = "Bulk Upload Students";
        $schoolclasses = Schoolclass::leftJoin('schoolarm','schoolarm.id','=','schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass,' - ',schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')->get();
        $schoolterms   = Schoolterm::select('id','term as name')->get();
        $schoolsessions= Schoolsession::select('id','session as name')->get();

        return view('student.bulkupload', compact('schoolclasses','schoolterms','schoolsessions','pagetitle'));
    }

    public function batchindex()
    {
        $pagetitle = "Student Batch Management";
        $batch = StudentBatchModel::leftJoin('schoolclass',   'schoolclass.id',   '=','student_batch_upload.schoolclassid')
            ->leftJoin('schoolsession','schoolsession.id','=','student_batch_upload.session')
            ->leftJoin('schoolterm',   'schoolterm.id',   '=','student_batch_upload.termid')
            ->leftJoin('schoolarm',    'schoolarm.id',    '=','schoolclass.arm')
            ->orderBy('upload_date','desc')
            ->get([
                'student_batch_upload.id as id',
                'student_batch_upload.title as title',
                'schoolclass.schoolclass as schoolclass',
                'schoolterm.term as term',
                'schoolsession.session as session',
                'schoolarm.arm as arm',
                'student_batch_upload.status as status',
                'student_batch_upload.updated_at as upload_date',
            ]);

        $schoolclasses = Schoolclass::leftJoin('schoolarm','schoolarm.id','=','schoolclass.arm')
            ->selectRaw("schoolclass.id, CONCAT(schoolclass.schoolclass,' - ',schoolarm.arm) as class_display, schoolclass.schoolclass, schoolarm.arm")
            ->orderBy('schoolclass.schoolclass')->get();
        $schoolterms   = Schoolterm::select('id','term as name')->get();
        $schoolsessions= Schoolsession::select('id','session as name')->get();

        return view('student.batchindex', compact('batch','schoolclasses','schoolterms','schoolsessions','pagetitle'));
    }

  public function bulkuploadsave(Request $request)
{
    $validator = Validator::make($request->all(), [
        'filesheet'    => 'required|mimes:xlsx,csv,xls|max:10240', // 10MB
        'title'        => 'required|string|max:255',
        'termid'       => 'required|exists:schoolterm,id',
        'sessionid'    => 'required|exists:schoolsession,id',
        'schoolclassid'=> 'required|exists:schoolclass,id',
    ]);

    if ($validator->fails()) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        return redirect()->back()->withErrors($validator)->withInput();
    }

    if (StudentBatchModel::where('title', $request->title)->exists()) {
        $message = 'Title already used. Please choose another.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('success', $message);
    }

    try {
        // Persist to disk before the request ends — a queue worker runs in a
        // separate process and cannot see PHP's ephemeral temp-upload file.
        $storedPath = $request->file('filesheet')->store('batch-imports', 'local');

        $batch = StudentBatchModel::create([
            'title'         => $request->title,
            'schoolclassid' => $request->schoolclassid,
            'termid'        => $request->termid,
            'session'       => $request->sessionid,
            'status'        => 'Processing',
        ]);

        $progressKey = 'batch_import_' . $batch->id . '_' . uniqid();

        Cache::put($progressKey, [
            'status'   => 'queued',
            'progress' => 0,
            'total'    => 0,
            'message'  => 'Waiting to start...',
        ], now()->addMinutes(30));

        ProcessStudentBatchImport::dispatch(
            $storedPath,
            $batch->id,
            $progressKey,
            (int) $request->schoolclassid,
            (int) $request->termid,
            (int) $request->sessionid,
            auth()->id()
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'message'      => 'Batch upload queued for processing.',
                'batch_id'     => $batch->id,
                'progress_key' => $progressKey,
            ]);
        }

        return redirect()->back()->with('success', 'Batch upload queued for processing.');

    } catch (\Exception $e) {
        Log::error("Error queuing batch import: {$e->getMessage()}");
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Failed to queue import: ' . $e->getMessage()], 500);
        }
        return redirect()->back()->with('status', 'Failed to queue import: ' . $e->getMessage());
    }
}

public function getBatchImportProgress(Request $request)
{
    $request->validate(['progress_key' => 'required|string']);

    $progress = Cache::get($request->progress_key, [
        'status'   => 'unknown',
        'progress' => 0,
        'total'    => 0,
        'message'  => 'No progress data found (it may have expired).',
    ]);

    return response()->json(['success' => true, 'progress' => $progress]);
}
    public function getLastAdmissionNumber(Request $request)
    {
        try {
            $year = $request->query('year', date('Y'));
            if (!preg_match('/^\d{4}$/', $year)) {
                return response()->json(['success'=>false,'message'=>'Invalid year format'], 400);
            }

            $lastStudent = Student::where('admissionNo', 'LIKE', "TCC/{$year}/%")->orderBy('id','desc')->first();
            $lastNumber  = 870;

            if ($lastStudent && $lastStudent->admissionNo) {
                $parts = explode('/', $lastStudent->admissionNo);
                if (count($parts) === 3 && is_numeric($parts[2])) {
                    $lastNumber = max(870, (int)$parts[2]);
                }
            }

            return response()->json(['success'=>true,'admissionNo'=>sprintf('TCC/%s/%04d',$year,$lastNumber+1)], 200);

        } catch (\Exception $e) {
            Log::error("Error generating admission number: {$e->getMessage()}");
            return response()->json(['success'=>false,'message'=>'Failed to generate admission number'], 500);
        }
    }

    public function generateReport(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        Log::info('=== GENERATE REPORT STARTED ===');

        $reportId      = null;
        $currentTerms  = null;
        $reportStudents= null;

        try {
            $request->validate([
                'class_id'               => 'nullable|exists:schoolclass,id',
                'term_id'                => 'nullable|exists:schoolterm,id',
                'session_id'             => 'nullable|exists:schoolsession,id',
                'status'                 => 'nullable|in:1,2,Active,Inactive',
                'columns'                => 'required|string',
                'columns_order'          => 'nullable|string',
                'format'                 => 'required|in:pdf,excel',
                'orientation'            => 'nullable|in:portrait,landscape',
                'include_header'         => 'nullable|boolean',
                'include_logo'           => 'nullable|boolean',
                'exclude_photos'         => 'nullable|boolean',
                'template'               => 'nullable|in:default,detailed,simple',
                'confidential'           => 'nullable|boolean',
                'preview'                => 'nullable|boolean',
                'optimize_large_reports' => 'nullable|boolean',
            ]);

            $user = auth()->user();
            if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized.'], 401);
            if (!$user->hasAnyRole(['Staff','Admin','Super Admin'])) {
                return response()->json(['success'=>false,'message'=>'Access denied.'], 403);
            }

            $columns     = array_filter(explode(',', $request->columns));
            $columnOrder = [];
            if ($request->filled('columns_order')) {
                $columnOrder = array_filter(explode(',', $request->columns_order));
                $columns     = array_values(array_intersect($columnOrder, $columns));
            }

            $template = $request->input('template', 'default');
            if ($template === 'detailed') {
                $columns = array_unique(array_merge($columns, ['photo','admissionNo','firstname','lastname','othername','gender','dateofbirth','age','class','status']));
            } elseif ($template === 'simple') {
                $columns = array_values(array_intersect($columns, ['photo','admissionNo','firstname','lastname','class','status']));
            }

            if ($request->boolean('exclude_photos')) {
                $columns = array_filter($columns, fn($c) => $c !== 'photo');
            }

            if (empty($columns)) return response()->json(['success'=>false,'message'=>'No columns selected'], 422);

            $termName      = 'All Terms';
            $sessionName   = 'All Sessions';
            $selectedTerm  = null;
            $selectedSession = null;

            if ($request->filled('term_id')) {
                $selectedTerm = Schoolterm::find($request->term_id);
                $termName     = $selectedTerm ? $selectedTerm->term : 'Unknown Term';
            }
            if ($request->filled('session_id')) {
                $selectedSession = Schoolsession::find($request->session_id);
                $sessionName     = $selectedSession ? $selectedSession->session : 'Unknown Session';
            }

            $query = StudentCurrentTerm::query()
                ->with(['student.picture','student.parent','schoolClass.armRelation','term','session'])
                ->select('student_current_term.*');

            if ($request->filled('class_id'))   $query->where('schoolclassId', $request->class_id);
            if ($request->filled('term_id'))     $query->where('termId',        $request->term_id);
            if ($request->filled('session_id'))  $query->where('sessionId',     $request->session_id);

            if ($request->filled('status')) {
                $query->whereHas('student', function ($q) use ($request) {
                    if (in_array($request->status, ['1','2'])) {
                        $q->where('statusId', $request->status);
                    } else {
                        $q->where('student_status', $request->status);
                    }
                });
            }

            $currentTerms = $query->get();

            if ($currentTerms->isEmpty()) {
                return response()->json(['success'=>false,'message'=>'No students found in the selected term and session.'], 404);
            }

            $isLargeReport  = $currentTerms->count() > 100;
            $optimizeLarge  = $request->boolean('optimize_large_reports', true);

            if ($isLargeReport && $optimizeLarge && !$request->boolean('exclude_photos')) {
                $columns = array_filter($columns, fn($c) => $c !== 'photo');
            }

            $reportId = uniqid('report_');
            Cache::put($reportId, ['status'=>'processing','progress'=>0,'total'=>$currentTerms->count(),'message'=>'Processing...'], now()->addMinutes(10));

            $reportStudents = $currentTerms->map(function ($currentTerm) use ($isLargeReport) {
                $student = $currentTerm->student;
                $picture = $student->picture;
                $parent  = $student->parent;

                $photoBase64 = null;
                $hasPhoto    = false;

                if ($picture && $picture->picture && $picture->picture !== 'unnamed.jpg') {
                    $hasPhoto = true;
                    if (!$isLargeReport) $photoBase64 = $this->getOptimizedImageForPDF($picture->picture);
                }

                $currentClass   = $currentTerm->schoolClass->schoolclass ?? null;
                $currentArm     = $currentTerm->schoolClass->armRelation->arm ?? null;
                $currentTermName= $currentTerm->term->term ?? null;
                $currentSession = $currentTerm->session->session ?? null;

                return (object) [
                    'id'                  => $student->id,
                    'admissionNo'         => $student->admissionNo,
                    'admissionYear'       => $student->admissionYear,
                    'admission_date'      => $student->admission_date,
                    'title'               => $student->title,
                    'firstname'           => $student->firstname,
                    'lastname'            => $student->lastname,
                    'othername'           => $student->othername,
                    'gender'              => $student->gender,
                    'dateofbirth'         => $student->dateofbirth,
                    'age'                 => $student->age,
                    'blood_group'         => $student->blood_group,
                    'genotype'            => $student->genotype,
                    'mother_tongue'       => $student->mother_tongue,
                    'emergency_contact_name'       => $student->emergency_contact_name,
                    'emergency_contact_phone'      => $student->emergency_contact_phone,
                    'allergies_medical_conditions' => $student->allergies_medical_conditions,
                    'religion'            => $student->religion,
                    'phone_number'        => $student->phone_number,
                    'email'               => $student->email,
                    'nin_number'          => $student->nin_number,
                    'city'                => $student->city,
                    'state'               => $student->state,
                    'local'               => $student->local,
                    'nationality'         => $student->nationality,
                    'placeofbirth'        => $student->placeofbirth,
                    'future_ambition'     => $student->future_ambition,
                    'permanent_address'   => $student->home_address2,
                    'student_category'    => $student->student_category,
                    'statusId'            => $student->statusId,
                    'student_status'      => $student->student_status,
                    'last_school'         => $student->last_school,
                    'last_class'          => $student->last_class,
                    'reason_for_leaving'  => $student->reason_for_leaving,
                    'created_at'          => $student->created_at,
                    'current_term_id'     => $currentTerm->termId,
                    'current_session_id'  => $currentTerm->sessionId,
                    'current_class_id'    => $currentTerm->schoolclassId,
                    'is_current'          => $currentTerm->is_current,
                    'current_class_name'  => $currentClass,
                    'current_arm'         => $currentArm,
                    'current_term_name'   => $currentTermName,
                    'current_session_name'=> $currentSession,
                    'schoolclass'         => $currentClass,
                    'arm_name'            => $currentArm,
                    'termid'              => $currentTerm->termId,
                    'sessionid'           => $currentTerm->sessionId,
                    'picture'             => $picture ? $picture->picture : null,
                    'picture_base64'      => $photoBase64,
                    'has_photo'           => $hasPhoto,
                    'photo_initials'      => substr($student->firstname??'',0,1).substr($student->lastname??'',0,1),
                    'father_name'         => $parent ? $parent->father : null,
                    'mother_name'         => $parent ? $parent->mother : null,
                    'father_phone'        => $parent ? $parent->father_phone : null,
                    'mother_phone'        => $parent ? $parent->mother_phone : null,
                    'parent_email'        => $parent ? $parent->parent_email : null,
                    'parent_address'      => $parent ? $parent->parent_address : null,
                    'father_occupation'   => $parent ? $parent->father_occupation : null,
                    'father_city'         => $parent ? $parent->father_city : null,
                    'guardian_name'         => $parent ? $parent->guardian_name : null,
                    'guardian_relationship' => $parent ? $parent->guardian_relationship : null,
                    'guardian_phone'        => $parent ? $parent->guardian_phone : null,
                    'whatsapp_number'       => $parent ? $parent->whatsapp_number : null,
                ];
            });

            Cache::put($reportId, ['status'=>'complete','progress'=>$currentTerms->count(),'total'=>$currentTerms->count(),'message'=>'Complete'], now()->addMinutes(10));

            $className = 'All Classes';
            if ($request->filled('class_id')) {
                $class     = Schoolclass::with('armRelation')->find($request->class_id);
                $className = $class ? $class->schoolclass.($class->armRelation ? ' - '.$class->armRelation->arm : '') : 'All Classes';
            }

            $format      = $request->input('format');
            $orientation = $request->query('orientation','portrait');
            $schoolInfo  = SchoolInformation::where('is_active', true)->first();

            $data = [
                'students'        => $reportStudents,
                'columns'         => $columns,
                'title'           => $request->boolean('confidential') ? 'CONFIDENTIAL - Student Master List Report' : 'Student Master List Report',
                'className'       => $className,
                'termName'        => $termName,
                'sessionName'     => $sessionName,
                'generated'       => now()->format('d M Y h:i A'),
                'generated_by'    => $user->name,
                'total'           => $reportStudents->count(),
                'males'           => $reportStudents->where('gender','Male')->count(),
                'females'         => $reportStudents->where('gender','Female')->count(),
                'orientation'     => $orientation,
                'include_header'  => $request->boolean('include_header', true),
                'include_logo'    => $request->boolean('include_logo', true),
                'school_info'     => $schoolInfo,
                'school_logo_base64' => null,
                'selected_term'   => $selectedTerm,
                'selected_session'=> $selectedSession,
                'template'        => $template,
                'confidential'    => $request->boolean('confidential'),
                'report_id'       => $reportId,
                'is_large_report' => $isLargeReport,
                'warning'         => $isLargeReport ? 'Large report detected. Photos may be excluded for performance.' : null,
            ];

            if ($data['include_logo'] && $schoolInfo && $format === 'pdf') {
                $data['school_logo_base64'] = $this->getSchoolLogoBase64($schoolInfo);
            }

            $filename = 'student-report-'.now()->format('Y-m-d-His').($request->boolean('confidential') ? '-CONFIDENTIAL' : '');

            if ($request->boolean('preview')) {
                $data['students'] = $reportStudents->take(5);
                $data['is_preview'] = true;
                $data['warning']  = 'PREVIEW - Showing first 5 records only';
                return Pdf::loadView('student.reports.student_report_pdf', $data)
                    ->setPaper('A4', $orientation)
                    ->setOptions(['isRemoteEnabled'=>true,'isHtml5ParserEnabled'=>true,'defaultFont'=>'DejaVu Sans'])
                    ->stream('preview-report.pdf');
            }

            if ($format === 'excel') {
                if (!class_exists('App\Exports\StudentReportExport')) throw new \Exception('StudentReportExport class not found.');
                return Excel::download(new \App\Exports\StudentReportExport($data), $filename.'.xlsx');
            }

            $view = 'student.reports.student_report_pdf';
            if ($template === 'detailed' && view()->exists('student.reports.detailed_report_pdf')) $view = 'student.reports.detailed_report_pdf';
            if ($template === 'simple'   && view()->exists('student.reports.simple_report_pdf'))   $view = 'student.reports.simple_report_pdf';

            return Pdf::loadView($view, $data)
                ->setPaper('A4', $orientation)
                ->setOptions(['isRemoteEnabled'=>true,'isHtml5ParserEnabled'=>true,'defaultFont'=>'DejaVu Sans'])
                ->download($filename.'.pdf');

        } catch (\Exception $e) {
            Log::error('Error generating report: '.$e->getMessage()."\n".$e->getTraceAsString());

            if (isset($reportId)) {
                try {
                    Cache::put($reportId, ['status'=>'failed','progress'=>0,'total'=>0,'message'=>$e->getMessage()], now()->addMinutes(10));
                } catch (\Exception $ce) {}
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success'=>false,'message'=>'Server error: '.$e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to generate report: '.$e->getMessage());
        }
    }

    public function getReportProgress(Request $request)
    {
        $request->validate(['report_id'=>'required|string']);
        return response()->json(['success'=>true,'progress'=>Cache::get($request->report_id, ['status'=>'unknown','progress'=>0,'total'=>0,'message'=>'Not found'])]);
    }

    private function getOptimizedImageForPDF($imagePath, $maxWidth = 100)
    {
        if (!$imagePath) return null;
        $cacheKey = 'optimized_image_'.md5($imagePath.'_'.$maxWidth);
        if (Cache::has($cacheKey)) return Cache::get($cacheKey);

        $possiblePaths = [
            storage_path('app/public/images/student_avatars/'.$imagePath),
            public_path('storage/images/student_avatars/'.$imagePath),
            storage_path('app/public/student_avatars/'.$imagePath),
            public_path('storage/student_avatars/'.$imagePath),
        ];
        $foundPath = null;
        foreach ($possiblePaths as $p) { if (file_exists($p)) { $foundPath = $p; break; } }
        if (!$foundPath) return null;

        try {
            $imageData = base64_encode(file_get_contents($foundPath));
            $mimeType  = mime_content_type($foundPath);
            $result    = 'data:'.$mimeType.';base64,'.$imageData;
            Cache::put($cacheKey, $result, now()->addHours(24));
            return $result;
        } catch (\Exception $e) {
            Log::warning('Failed to encode image at '.$foundPath.': '.$e->getMessage());
            return null;
        }
    }

    private function getSchoolLogoBase64($schoolInfo)
    {
        if (!$schoolInfo || !$schoolInfo->school_logo) return null;
        $cacheKey = 'school_logo_'.md5($schoolInfo->school_logo);
        if (Cache::has($cacheKey)) return Cache::get($cacheKey);

        $possiblePaths = [
            storage_path('app/public/'.$schoolInfo->school_logo),
            public_path('storage/'.$schoolInfo->school_logo),
        ];
        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                try {
                    $result = 'data:'.mime_content_type($p).';base64,'.base64_encode(file_get_contents($p));
                    Cache::put($cacheKey, $result, now()->addHours(24));
                    return $result;
                } catch (\Exception $e) {}
            }
        }
        return null;
    }

    public function getCurrentTerm($studentId)
    {
        try {
            $currentTerm = StudentCurrentTerm::getCurrentForStudent($studentId);
            if (!$currentTerm) return response()->json(['success'=>false,'message'=>'No current term found'], 404);
            return response()->json(['success'=>true,'data'=>$currentTerm]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getActiveTerm($studentId)
    {
        try {
            $activeTerm    = Schoolterm::where('status', true)->first();
            $activeSession = Schoolsession::where('status', 'Current')->first();

            if (!$activeTerm || !$activeSession) return response()->json(['success'=>false,'message'=>'No active term/session found'], 404);

            $activeTermRecord = StudentCurrentTerm::with(['schoolClass.armRelation','term','session'])
                ->where('studentId', $studentId)
                ->where('termId',    $activeTerm->id)
                ->where('sessionId', $activeSession->id)
                ->first();

            if (!$activeTermRecord) return response()->json(['success'=>false,'message'=>'Student not registered in current active term'], 404);

            return response()->json(['success'=>true,'data'=>[
                'id'            => $activeTermRecord->id,
                'studentId'     => $activeTermRecord->studentId,
                'schoolclassId' => $activeTermRecord->schoolclassId,
                'termId'        => $activeTermRecord->termId,
                'sessionId'     => $activeTermRecord->sessionId,
                'is_current'    => $activeTermRecord->is_current,
                'schoolClass'   => $activeTermRecord->schoolClass ? ['id'=>$activeTermRecord->schoolClass->id,'schoolclass'=>$activeTermRecord->schoolClass->schoolclass,'armRelation'=>$activeTermRecord->schoolClass->armRelation ? ['id'=>$activeTermRecord->schoolClass->armRelation->id,'arm'=>$activeTermRecord->schoolClass->armRelation->arm] : null] : null,
                'term'          => $activeTermRecord->term    ? ['id'=>$activeTermRecord->term->id,   'term'=>$activeTermRecord->term->term,       'status'=>$activeTermRecord->term->status]    : null,
                'session'       => $activeTermRecord->session ? ['id'=>$activeTermRecord->session->id,'session'=>$activeTermRecord->session->session,'status'=>$activeTermRecord->session->status] : null,
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getCurrentInfo($id)
    {
        try {
            $student = Student::with(['currentTerm.schoolClass.armRelation','currentTerm.term','currentTerm.session'])->findOrFail($id);
            if (!$student->currentTerm) return response()->json(['success'=>false,'message'=>'No current term assigned'], 404);

            $ct = $student->currentTerm;
            return response()->json(['success'=>true,'data'=>[
                'student_id'       => $student->id,
                'admission_no'     => $student->admissionNo,
                'name'             => $student->firstname.' '.$student->lastname,
                'current_class_id' => $ct->schoolclassId,
                'current_class'    => $ct->schoolClass ? $ct->schoolClass->schoolclass : 'N/A',
                'current_class_arm'=> $ct->schoolClass && $ct->schoolClass->armRelation ? $ct->schoolClass->armRelation->arm : 'N/A',
                'current_term_id'  => $ct->termId,
                'current_term'     => $ct->term    ? $ct->term->term       : 'N/A',
                'current_session_id'=> $ct->sessionId,
                'current_session'  => $ct->session ? $ct->session->session : 'N/A',
                'is_current'       => $ct->is_current,
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getAllRegisteredTerms($id)
    {
        try {
            $terms = StudentCurrentTerm::where('studentId', $id)
                ->with(['schoolClass.armRelation','term','session'])
                ->orderBy('sessionId','desc')->orderBy('termId','desc')
                ->get()->map(fn($t) => [
                    'id'           => $t->id,
                    'term_id'      => $t->termId,
                    'term_name'    => $t->term    ? $t->term->term       : 'N/A',
                    'session_id'   => $t->sessionId,
                    'session_name' => $t->session ? $t->session->session : 'N/A',
                    'class_id'     => $t->schoolclassId,
                    'class_name'   => $t->schoolClass ? $t->schoolClass->schoolclass : 'N/A',
                    'arm_name'     => $t->schoolClass && $t->schoolClass->armRelation ? $t->schoolClass->armRelation->arm : 'N/A',
                    'is_current'   => $t->is_current,
                    'created_at'   => $t->created_at,
                    'updated_at'   => $t->updated_at,
                ]);

            return response()->json(['success'=>true,'data'=>$terms]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function getBatchImportErrors($id)
    {
        try {
            $batch = StudentBatchModel::findOrFail($id);

            $errors = $batch->import_errors
                ? json_decode($batch->import_errors, true)
                : [];

            return response()->json([
                'success' => true,
                'title'   => $batch->title,
                'status'  => $batch->status,
                'errors'  => $errors,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Batch not found'], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching batch import errors: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

/**
 * Generate a locked Excel template for batch student upload.
 */
public function generateBatchTemplate(Request $request)
{
    $request->validate([
        'schoolclassid' => 'required|exists:schoolclass,id',
        'termid'        => 'required|exists:schoolterm,id',
        'sessionid'     => 'required|exists:schoolsession,id',
        'rows'          => 'nullable|integer|min:1|max:500',
    ]);

    try {
        $class = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(
                'schoolclass.id',
                'schoolclass.schoolclass',
                'schoolarm.arm'
            )
            ->where('schoolclass.id', $request->schoolclassid)
            ->firstOrFail();

        $term    = Schoolterm::findOrFail($request->termid);
        $session = Schoolsession::findOrFail($request->sessionid);

        $className = $class->schoolclass . ($class->arm ? ' ' . $class->arm : '');
        $rows      = (int) $request->input('rows', 30);

        // ── Build a filesystem-safe filename from class/arm, term, and session ──
        $sanitize = function (string $value): string {
            $value = str_replace(['/', '\\'], '-', $value);          // "2026/2027" -> "2026-2027"
            $value = preg_replace('/[^A-Za-z0-9\- ]/', '', $value);  // strip anything else unsafe
            $value = preg_replace('/\s+/', '-', trim($value));       // spaces -> dashes
            $value = preg_replace('/-+/', '-', $value);              // collapse repeated dashes
            return $value;
        };

        $filename = sprintf(
            '%s_%s_%s_Batch-Template_%s.xlsx',
            $sanitize($className),
            $sanitize($term->term),
            $sanitize($session->session),
            now()->format('Ymd-His')
        );

        return Excel::download(
            new \App\Exports\StudentBatchTemplateExport(
                (int) $request->schoolclassid,
                (int) $request->termid,
                (int) $request->sessionid,
                $rows,
                $className,
                $term->term,
                $session->session
            ),
            $filename
        );
    } catch (\Exception $e) {
        Log::error('Failed to generate batch template: ' . $e->getMessage());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage(),
            ], 500);
        }

        return redirect()->back()->with('error', 'Failed to generate template: ' . $e->getMessage());
    }
}


/**
 * Delete multiple student batches (and all students belonging to them).
 */
public function deleteStudentBatchMultiple(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'ids'   => 'required|array|min:1',
        'ids.*' => 'required|integer|exists:student_batch_upload,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid batch selection: ' . $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    try {
        if (!Schema::hasTable('student_batch_upload')) {
            throw new \Exception('student_batch_upload table does not exist');
        }
        if (!Schema::hasColumn('studentRegistration', 'batchid')) {
            throw new \Exception('batchid column missing on studentRegistration');
        }

        DB::beginTransaction();

        $batches      = StudentBatchModel::whereIn('id', $request->ids)->get();
        $deletedCount = 0;

        foreach ($batches as $batch) {
            $studentIds = Student::where('batchid', $batch->id)->pluck('id');

            foreach ($studentIds as $studentId) {
                // Picture
                $picture = Studentpicture::where('studentid', $studentId)->first();
                if ($picture && $picture->picture) {
                    $this->deleteImage($picture->picture);
                }

                // Bill payments
                $billPayments = StudentBillPayment::where('student_id', $studentId)->get();
                foreach ($billPayments as $bp) {
                    StudentBillPaymentRecord::where('student_bill_payment_id', $bp->id)->delete();
                    $bp->delete();
                }
                StudentBillPaymentBook::where('student_id', $studentId)->delete();
                StudentBillInvoice::where('student_id', $studentId)->delete();

                // Broadsheet records (real + mock)
                $bsRecords = BroadsheetRecord::where('student_id', $studentId)->get();
                foreach ($bsRecords as $r) {
                    Broadsheets::where('broadsheet_record_id', $r->id)->delete();
                    $r->delete();
                }

                $bsMockRecords = BroadsheetRecordMock::where('student_id', $studentId)->get();
                foreach ($bsMockRecords as $r) {
                    BroadsheetsMock::where('broadsheet_records_mock_id', $r->id)->delete();
                    $r->delete();
                }

                // Related records
                Studentclass::where('studentId', $studentId)->delete();
                PromotionStatus::where('studentId', $studentId)->delete();
                ParentRegistration::where('studentId', $studentId)->delete();
                Studentpicture::where('studentid', $studentId)->delete();
                SubjectRegistrationStatus::where('studentId', $studentId)->delete();
                Studenthouse::where('studentid', $studentId)->delete();
                StudentClub::where('studentid', $studentId)->delete();
                StudentSport::where('studentid', $studentId)->delete();
                Studentpersonalityprofile::where('studentid', $studentId)->delete();
                StudentCurrentTerm::where('studentId', $studentId)->delete();
            }

            // Delete students belonging to this batch, then the batch itself
            Student::where('batchid', $batch->id)->delete();
            $batch->delete();
            $deletedCount++;
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} batch(es) deleted successfully.",
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'One or more batches were not found.',
        ], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error deleting multiple batches: {$e->getMessage()}\n{$e->getTraceAsString()}");
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete batches: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Generate one or more locked Excel templates for batch student upload,
 * across any combination of selected classes, terms, and sessions.
 * A single combination downloads as .xlsx; multiple combinations are
 * bundled into a .zip.
 */
public function generateBatchTemplateBulk(Request $request)
{
    $validator = Validator::make($request->all(), [
        'schoolclassids'   => 'required|array|min:1',
        'schoolclassids.*' => 'integer|exists:schoolclass,id',
        'termids'          => 'required|array|min:1',
        'termids.*'        => 'integer|exists:schoolterm,id',
        'sessionids'       => 'required|array|min:1',
        'sessionids.*'     => 'integer|exists:schoolsession,id',
        'rows'             => 'nullable|integer|min:1|max:500',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed: ' . $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    $classIds   = array_values(array_unique($request->input('schoolclassids')));
    $termIds    = array_values(array_unique($request->input('termids')));
    $sessionIds = array_values(array_unique($request->input('sessionids')));
    $rows       = (int) $request->input('rows', 30);

    $totalCombinations = count($classIds) * count($termIds) * count($sessionIds);
    $maxCombinations   = 60;

    if ($totalCombinations > $maxCombinations) {
        return response()->json([
            'success' => false,
            'message' => "That's {$totalCombinations} combinations — please select {$maxCombinations} or fewer at a time.",
        ], 422);
    }

    $sanitize = function (string $value): string {
        $value = str_replace(['/', '\\'], '-', $value);
        $value = preg_replace('/[^A-Za-z0-9\- ]/', '', $value);
        $value = preg_replace('/\s+/', '-', trim($value));
        $value = preg_replace('/-+/', '-', $value);
        return trim($value, '-');
    };

    $batchDirName     = 'batch-templates-' . now()->format('Ymd-His') . '-' . uniqid();
    $batchDirRelative = 'temp/' . $batchDirName;
    $batchDirAbsolute = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $batchDirName);

    try {
        $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm')
            ->whereIn('schoolclass.id', $classIds)
            ->get()->keyBy('id');

        $terms    = Schoolterm::whereIn('id', $termIds)->get()->keyBy('id');
        $sessions = Schoolsession::whereIn('id', $sessionIds)->get()->keyBy('id');

        // ── 1. Create the temp dir explicitly (recursive) and verify writable ──
        if (!is_dir($batchDirAbsolute)) {
            if (!@mkdir($batchDirAbsolute, 0775, true) && !is_dir($batchDirAbsolute)) {
                throw new \Exception(
                    "Could not create temp directory. Check that storage/app/ exists and is writable. Path: {$batchDirAbsolute}"
                );
            }
        }

        if (!is_writable($batchDirAbsolute)) {
            throw new \Exception("Temp directory is not writable: {$batchDirAbsolute}");
        }

        if (!class_exists('ZipArchive')) {
            throw new \Exception('PHP ZipArchive extension is not installed. Enable ext-zip in php.ini.');
        }

        $generatedFiles = [];

        foreach ($classIds as $classId) {
            $class     = $classes[$classId] ?? null;
            if (!$class) continue;

            $className = trim($class->schoolclass . ($class->arm ? ' ' . $class->arm : ''));

            foreach ($termIds as $termId) {
                $term = $terms[$termId] ?? null;
                if (!$term) continue;

                foreach ($sessionIds as $sessionId) {
                    $session = $sessions[$sessionId] ?? null;
                    if (!$session) continue;

                    $filename = sprintf(
                        '%s_%s_%s_Batch-Template.xlsx',
                        $sanitize($className) ?: 'Class',
                        $sanitize($term->term) ?: 'Term',
                        $sanitize($session->session) ?: 'Session'
                    );

                    $absolutePath = $batchDirAbsolute . DIRECTORY_SEPARATOR . $filename;

                    // Ensure unique filename
                    if (file_exists($absolutePath)) {
                        $filename = pathinfo($filename, PATHINFO_FILENAME)
                            . '_' . substr(uniqid(), -5) . '.xlsx';
                        $absolutePath = $batchDirAbsolute . DIRECTORY_SEPARATOR . $filename;
                    }

                    // ── 2. Build the export and grab the raw bytes ──
                    // Using Excel::raw() avoids Storage/disk lookup entirely,
                    // so there's no ambiguity about where the file lands.
                    try {
                        $export = new \App\Exports\StudentBatchTemplateExport(
                            (int) $classId,
                            (int) $termId,
                            (int) $sessionId,
                            $rows,
                            $className,
                            $term->term,
                            $session->session
                        );

                        $binary = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
                    } catch (\Throwable $e) {
                        Log::error("Excel::raw failed for class={$classId} term={$termId} session={$sessionId}: " . $e->getMessage());
                        throw new \Exception(
                            "Failed to build template for {$className} / {$term->term} / {$session->session}: " . $e->getMessage()
                        );
                    }

                    if (empty($binary)) {
                        throw new \Exception("Template for {$className} / {$term->term} / {$session->session} produced no data.");
                    }

                    // ── 3. Write the bytes ourselves — deterministic and synchronous ──
                    $written = @file_put_contents($absolutePath, $binary);
                    if ($written === false) {
                        $err = error_get_last();
                        throw new \Exception(
                            "Could not write template to disk: {$filename}. "
                            . ($err['message'] ?? 'Unknown write error.')
                        );
                    }

                    clearstatcache(true, $absolutePath);

                    if (!is_file($absolutePath) || filesize($absolutePath) === 0) {
                        throw new \Exception("Template file was written but is empty or missing: {$filename}");
                    }

                    $generatedFiles[] = [
                        'path'     => $absolutePath,
                        'filename' => $filename,
                    ];
                }
            }
        }

        if (empty($generatedFiles)) {
            throw new \Exception('No templates were generated. Check that your class/term/session selections are valid.');
        }

        // ── 4. Single combination — return the xlsx directly ──
        if (count($generatedFiles) === 1) {
            $file = $generatedFiles[0];
            return response()->download($file['path'], $file['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // ── 5. Multiple combinations — zip them up ──
        $zipFilename = 'Batch-Templates_' . now()->format('Ymd-His') . '.zip';
        $zipAbsolute = $batchDirAbsolute . '.zip';

        $zip = new \ZipArchive();
        $openResult = $zip->open($zipAbsolute, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new \Exception("Could not create zip archive (ZipArchive error code: {$openResult}).");
        }

        foreach ($generatedFiles as $file) {
            if (!is_readable($file['path'])) {
                $zip->close();
                throw new \Exception("Template file became unreadable before zipping: {$file['filename']}");
            }

            if (!$zip->addFile($file['path'], $file['filename'])) {
                $zip->close();
                throw new \Exception("Failed to add {$file['filename']} to the zip archive.");
            }
        }

        if (!$zip->close()) {
            throw new \Exception('Failed to finalize the zip archive.');
        }

        if (!is_file($zipAbsolute) || filesize($zipAbsolute) === 0) {
            throw new \Exception('Zip archive was not created on disk or is empty.');
        }

        // xlsx files are inside the zip now — clean them up.
        foreach ($generatedFiles as $file) {
            @unlink($file['path']);
        }

        // Delete the zip after response finishes streaming.
        $zipPathToDelete = $zipAbsolute;
        $dirToDelete     = $batchDirAbsolute;
        app()->terminating(function () use ($zipPathToDelete, $dirToDelete) {
            if (is_file($zipPathToDelete)) @unlink($zipPathToDelete);
            if (is_dir($dirToDelete)) {
                $files = glob($dirToDelete . DIRECTORY_SEPARATOR . '*') ?: [];
                foreach ($files as $f) @unlink($f);
                @rmdir($dirToDelete);
            }
        });

        return response()->download($zipAbsolute, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(false);

    } catch (\Throwable $e) {
        Log::error('Failed to generate bulk batch templates: ' . $e->getMessage(), [
            'classIds'   => $classIds,
            'termIds'    => $termIds,
            'sessionIds' => $sessionIds,
            'trace'      => $e->getTraceAsString(),
        ]);

        // Best-effort cleanup
        if (isset($batchDirAbsolute) && is_dir($batchDirAbsolute)) {
            $files = glob($batchDirAbsolute . DIRECTORY_SEPARATOR . '*') ?: [];
            foreach ($files as $f) @unlink($f);
            @rmdir($batchDirAbsolute);
        }
        if (isset($zipAbsolute) && is_file($zipAbsolute)) {
            @unlink($zipAbsolute);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to generate template(s): ' . $e->getMessage(),
        ], 500);
    }
}
}