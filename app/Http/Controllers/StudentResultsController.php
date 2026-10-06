<?php

namespace App\Http\Controllers;

use App\Models\Broadsheet;
use App\Models\PromotionStatus;
use App\Models\Schoolarm;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Staffclasssetting;
use App\Models\Studentclass;
use App\Models\Studentpersonalityprofile;
use Illuminate\Http\Request;

class StudentResultsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:studentresults-list', ['only' => ['index', 'store']]);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $all_classes = Schoolclass::all();
        $arms = Schoolarm::all();

        return View('studentresults.index')->with('all_classes', $all_classes)
            ->with('arms', $arms);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $schoolsession = Schoolsession::all();
        $schoolterm = Schoolterm::all();

        return View('studentresults.show')->with('schoolclassid', $id)
            ->with('schoolsessions', $schoolsession)
            ->with('schoolterms', $schoolterm);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $check = Schoolclass::where('schoolclass.id', $id)
            ->leftJoin('subjectclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('broadsheet', 'broadsheet.subjectclassid', '=', 'subjectclass.id')
            ->where('broadsheet.termid', $request->termid)
            ->where('broadsheet.session', $request->sessionid)->exists();

        if ($check) {

            /* $students = Schoolclass::where('schoolclass.id',$id)
             ->leftJoin('subjectclass', 'subjectclass.schoolclassid','=','schoolclass.id')
             ->leftJoin('broadsheet', 'broadsheet.subjectclassid','=','subjectclass.id')
             ->where('broadsheet.termid',$request->termid)
             ->where('broadsheet.session',$request->sessionid)
             ->leftJoin('studentRegistration', 'studentRegistration.id','=','broadsheet.studentId')
             ->leftJoin('studentpicture','studentpicture.studentid','=','studentRegistration.id')
             ->get(['broadsheet.id as id','studentRegistration.admissionNO as admissionno','studentRegistration.firstname as fname',
             'studentRegistration.lastname as lname',
             'schoolclass.schoolclass as schoolclass','schoolclass.arm as arm',
             'subjectclass.id as subjectclid','broadsheet.staffid as staffid',
             'broadsheet.termid as termid','broadsheet.session as sessionid',
             'studentPicture.picture as picture','broadsheet.ca1 as ca1','broadsheet.ca2 as ca2','broadsheet.exam as exam','broadsheet.total  as total','broadsheet.grade as grade',
             'broadsheet.subjectpositionclass as position','broadsheet.remark as remark'])->sortBy('admissionno');*/

            $students = Studentclass::where('studentclass.schoolclassid', $id)
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
           // ->leftJoin('subjectclass', 'subjectclass.schoolclassid','=','schoolclass.id')
                ->where('studentclass.termid', $request->termid)
                ->where('studentclass.sessionid', $request->sessionid)
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->get(['studentRegistration.id as studentID', 'studentRegistration.admissionNO as admissionno', 'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname', 'studentpicture.picture as picture', 'studentRegistration.gender',
                    'schoolsession.id as sessionid', 'schoolterm.id as termid'])->sortBy('admissionno');

            $schoolclass = Schoolclass::where('id', $id)->get();
            $session = Schoolsession::where('id', $request->sessionid)->get();
            $term = Schoolterm::where('id', $request->termid)->get();

            return view('studentresults.classresults')->with('schoolclassid', $id)
                ->with('students', $students)
                ->with('session', $session)
                ->with('term', $term)
                ->with('schoolclass', $schoolclass);
        } else {
            return view('studentresults.noshow');
        }

    }

    public function viewresults($id, $schoolclassid, $termid, $sessionid)
    {

        $chkbroadsheets = Broadsheet::where('broadsheet.studentId', $id)->where('broadsheet.termid', $termid)->where('broadsheet.session', $sessionid)
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet.studentId')
            ->leftJoin('subjectRegistrationStatus', 'subjectRegistrationStatus.broadsheetid', '=', 'broadsheet.id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheet.subjectclassid')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('users', 'users.id', '=', 'broadsheet.staffid')
            ->leftJoin('staffbioinfo', 'staffbioinfo.userid', '=', 'users.id')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->exists();

        $broadsheets = Broadsheet::where('broadsheet.studentId', $id)->where('broadsheet.termid', $termid)->where('broadsheet.session', $sessionid)
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet.studentId')
            ->leftJoin('studentpersonalityprofiles', 'studentpersonalityprofiles.studentid', '=', 'broadsheet.studentId')
            ->leftJoin('subjectRegistrationStatus', 'subjectRegistrationStatus.broadsheetid', '=', 'broadsheet.id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('studenthouses', 'studenthouses.studentid', '=', 'studentRegistration.id')
            ->leftJoin('schoolhouses', 'schoolhouses.id', '=', 'studenthouses.schoolhouse')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheet.subjectclassid')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('users', 'users.id', '=', 'broadsheet.staffid')
            ->leftJoin('staffbioinfo', 'staffbioinfo.userid', '=', 'users.id')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->orderBy('subject', 'ASC')
            ->get(['broadsheet.id as bid', 'broadsheet.ca1 as ca1', 'broadsheet.ca2 as ca2', 'broadsheet.ca3 as ca3','broadsheet.exam as exam', 'studentpicture.picture as picture',
                'broadsheet.total  as total', 'broadsheet.grade as grade', 'schoolterm.term as term', 'schoolsession.session as session',
                'subject.subject as subject', 'subject.subject_code as subjectcode', 'schoolclass.schoolclass as schoolclass', 'schoolclass.arm as arm',
                'broadsheet.subjectpositionclass as position', 'broadsheet.remark as remark', 'broadsheet.cmin as cmin', 'broadsheet.cmax as cmax',
                'broadsheet.allsubjectstotalscores as subjectstotal', 'broadsheet.avg as avg', 'users.id as userid', 'users.name as staffname',
                'staffbioinfo.title as stafftitle', 'studentpersonalityprofiles.punctuality as punctuality', 'studentpersonalityprofiles.neatness as neatness',
                'studentpersonalityprofiles.politeness as politeness', 'studentpersonalityprofiles.cooperation as cooperation', 'studentpersonalityprofiles.leadership as leadership',
                'studentpersonalityprofiles.gamesandsports  as gamesandsports', 'studentpersonalityprofiles.physicalhealth as physicalhealth', 'studentpersonalityprofiles.attitude as attitude',
                'studentpersonalityprofiles.reading as reading', 'studentpersonalityprofiles.honesty as honesty', 'studentpersonalityprofiles.stability as stability',
                'studentpersonalityprofiles.selfcontrol as selfcontrol', 'studentpersonalityprofiles.classteachercomment as classteachercomment', 'studentpersonalityprofiles.principalscomment as principalscomment',
                'studentRegistration.admissionNo as admissionno', 'studentRegistration.tittle as tittle', 'studentRegistration.firstname as firstname', 'studentRegistration.lastname as lastname',
                'studentRegistration.gender as gender', 'studentRegistration.dateofbirth as dateofbirth', 'schoolhouses.house as house']);

        $subjectstotal = Broadsheet::where('broadsheet.studentId', $id)
            ->where('broadsheet.termid', $termid)
            ->where('broadsheet.session', $sessionid)
            ->sum('total');

        $subjectsavg = Broadsheet::where('broadsheet.studentId', $id)
            ->where('broadsheet.termid', $termid)
            ->where('broadsheet.session', $sessionid)
            ->avg('total');

        $classposition = Promotionstatus::select('*')
            ->where('studentId', $id)
            ->where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->get();
        $studentprofile = studentpersonalityprofile::where('studentId', $id)
            ->where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->get();

        $noofstudents = Studentclass::where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)->count();

        $chkclasssetting = Staffclasssetting::where('vschoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)->exists();

        $classsetting = Staffclasssetting::where('vschoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)->get();

        if ($chkbroadsheets) {
            return view('studentresults.viewresult')->with('broadsheets', $broadsheets)
                ->with('subjectsavg', $subjectsavg)
                ->with('classposition', $classposition)
                ->with('noofstudents', $noofstudents)
                ->with('studentprofile', $studentprofile)
                ->with('subjectstotal', $subjectstotal)
                ->with('classsetting', $classsetting)
                ->with('chkclasssetting', $chkclasssetting);
        } else {
            return view('studentresults.noresult');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
