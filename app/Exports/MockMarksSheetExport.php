<?php

namespace App\Exports;

use App\Models\Broadsheets;
use App\Models\SchoolInformation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MockMarksSheetExport
{
    protected $subjectclassid;
    protected $staffid;
    protected $termid;
    protected $sessionid;
    protected $schoolclassid;

    public function __construct($subjectclassid, $staffid, $termid, $sessionid, $schoolclassid)
    {
        $this->subjectclassid = $subjectclassid;
        $this->staffid = $staffid;
        $this->termid = $termid;
        $this->sessionid = $sessionid;
        $this->schoolclassid = $schoolclassid;
    }

    public function download()
    {
        try {
            $schoolInfo = SchoolInformation::getActiveSchool();
            if (!$schoolInfo) {
                throw new \Exception('School information not found');
            }
            Log::info('School Info:', ['schoolInfo' => $schoolInfo]);
    
            $classInfo = $this->getClassAndSubjectInfo();
            if (!$classInfo) {
                throw new \Exception('Class and subject information not found');
            }
            Log::info('Class Info:', ['classInfo' => $classInfo]);
    
            $students = $this->getStudentsList();
            Log::info('Students:', ['count' => $students->count()]);
    
            $data = [
                'school' => $schoolInfo, // Match the variable name in your Blade template
                'classInfo' => $classInfo,
                'broadsheets' => $students, // Match the variable name in your Blade template
            ];
    
            $pdf = Pdf::loadView('subjectscoresheet.markssheetmock', $data);
            $pdf->setPaper('A4', 'landscape'); // Changed to landscape for better table fit
    
            $filename = $this->generateFilename($classInfo);
            Log::info('Generated Filename:', ['filename' => $filename]);
    
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PDF Generation Error:', [
                'message' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'parameters' => [
                    'subjectclassid' => $this->subjectclassid,
                    'staffid' => $this->staffid,
                    'termid' => $this->termid,
                    'sessionid' => $this->sessionid,
                    'schoolclassid' => $this->schoolclassid
                ]
            ]);
            throw $e;
        }
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
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->where('subjectclass.id', $this->subjectclassid)
            ->where('subjectteacher.staffid', $this->staffid)
            ->where('broadsheets.term_id', $this->termid)
            ->where('broadsheet_records.session_id', $this->sessionid)
            ->select([
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'users.name as teacher_name',
                'schoolterm.term',
                'schoolsession.session',
                'classcategories.ca1score as max_ca1',
                'classcategories.ca2score as max_ca2',
                'classcategories.ca3score as max_ca3',
                'classcategories.examscore as max_exam',
            ])
            ->first();
    }

    protected function getStudentsList()
    {
        return DB::table('broadsheets')
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheets.subjectclass_id', $this->subjectclassid)
            ->where('broadsheets.staff_id', $this->staffid)
            ->where('broadsheets.term_id', $this->termid)
            ->where('broadsheet_records.session_id', $this->sessionid)
            ->orderBy('studentRegistration.lastname')
            ->select([
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentRegistration.othername as mname',
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
                'broadsheets.avg',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolterm.term',
                'schoolsession.session'
            ])
            ->get();
    }

    protected function generateFilename($classInfo)
    {
        if (!$classInfo) {
            return 'Marks_Sheet_' . date('Y-m-d_H-i-s') . '.pdf';
        }

        // Clean and format filename components
        $teacherName = $this->cleanFilename($classInfo->teacher_name ?? 'Teacher');
        $subject = $this->cleanFilename($classInfo->subject ?? 'Subject');
        $subjectCode = $classInfo->subject_code ? '_' . $this->cleanFilename($classInfo->subject_code) : '';
        $schoolClass = $this->cleanFilename($classInfo->schoolclass ?? 'Class');
        $arm = $classInfo->arm ? '_' . $this->cleanFilename($classInfo->arm) : '';
        $term = $this->cleanFilename($classInfo->term ?? 'Term');
        $session = $this->cleanFilename($classInfo->session ?? date('Y'));

        return sprintf(
            'Marks_Sheet_%s_%s%s_%s%s_%s_%s.pdf',
            $teacherName,
            $subject,
            $subjectCode,
            $schoolClass,
            $arm,
            $term,
            $session
        );
    }

    private function cleanFilename($string)
    {
        // Remove special characters and replace spaces with underscores
        $cleaned = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
        return str_replace(' ', '_', trim($cleaned));
    }
}
?>