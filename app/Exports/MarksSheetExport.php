<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarksSheetExport
{
    protected $subjectclassid;
    protected $staffid;
    protected $termid;
    protected $sessionid;
    protected $schoolclassid;

    public function __construct($subjectclassid, $staffid, $termid, $sessionid, $schoolclassid)
    {
        $this->subjectclassid = $subjectclassid;
        $this->staffid        = $staffid;
        $this->termid         = $termid;
        $this->sessionid      = $sessionid;
        $this->schoolclassid  = $schoolclassid;
    }

    public function download()
    {
        try {
            $school    = SchoolInformation::getActiveSchool();
            $classInfo = $this->getClassAndSubjectInfo();
            $students  = $this->getStudentsList();
            $assessments = $this->getAssessments();

            $data = [
                'school'      => $school,
                'classInfo'   => $classInfo,
                'broadsheets' => $students,
                'assessments' => $assessments,
            ];

            $pdf = Pdf::loadView('subjectscoresheet.markssheet', $data);
            $pdf->setPaper('A4', 'landscape');

            $filename = $this->generateFilename($classInfo);
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF Generation Error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Fetch the dynamic assessments for this school class.
     */
    protected function getAssessments()
    {
        $schoolclass = Schoolclass::with('classcategories')->find($this->schoolclassid);
        if (!$schoolclass || $schoolclass->classcategories->isEmpty()) {
            return collect();
        }
        $categoryIds = $schoolclass->classcategories->pluck('id');
        return Assessment::whereIn('classcategory_id', $categoryIds)
            ->with('subAssessments')
            ->orderBy('id')
            ->get();
    }

    protected function getClassAndSubjectInfo()
    {
        return DB::table('subjectclass')
            ->leftJoin('subject', 'subject.id', '=', 'subjectclass.subjectid')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('broadsheets', 'broadsheets.subjectclass_id', '=', 'subjectclass.id')
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('subjectclass.id', $this->subjectclassid)
            ->where('subjectteacher.staffid', $this->staffid)
            ->where('broadsheets.term_id', $this->termid)
            ->where('broadsheet_records.session_id', $this->sessionid)
            ->select([
                'subject.subject', 'subject.subject_code', 'schoolclass.schoolclass',
                'schoolarm.arm', 'users.name as teacher_name',
                'schoolterm.term', 'schoolsession.session',
            ])
            ->first();
    }

    protected function getStudentsList()
    {
        // Load broadsheets with assessment scores for dynamic columns
        return Broadsheets::where('broadsheets.subjectclass_id', $this->subjectclassid)
            ->where('broadsheets.staff_id', $this->staffid)
            ->where('broadsheets.term_id', $this->termid)
            ->with('assessmentScores')
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $this->sessionid)
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get([
                'broadsheets.id',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentRegistration.othername as mname',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
                'broadsheets.avg',
                'subject.subject', 'subject.subject_code',
                'schoolclass.schoolclass', 'schoolarm.arm',
                'schoolterm.term', 'schoolsession.session',
            ]);
    }

    protected function generateFilename($classInfo)
    {
        if (!$classInfo) return 'Marks_Sheet_' . date('Y-m-d_H-i-s') . '.pdf';
        $clean = fn($s) => str_replace(' ', '_', trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $s ?? '')));
        return sprintf('Marks_Sheet_%s_%s_%s%s_%s_%s.pdf',
            $clean($classInfo->teacher_name ?? 'Teacher'),
            $clean($classInfo->subject ?? 'Subject'),
            $clean($classInfo->schoolclass ?? 'Class'),
            $classInfo->arm ? '_' . $clean($classInfo->arm) : '',
            $clean($classInfo->term ?? 'Term'),
            $clean($classInfo->session ?? date('Y'))
        );
    }
}
