<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class RecordsheetExport implements FromView, ShouldAutoSize, WithStyles, WithEvents, WithProperties
{
    use Exportable;

    protected $schoolclassId;
    protected $subjectclassId;
    protected $termId;
    protected $sessionId;
    protected $staffId;
    protected $assessments;
    protected $password;

    public function __construct($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId)
    {
        $this->schoolclassId  = $schoolclassId;
        $this->subjectclassId = $subjectclassId;
        $this->termId         = $termId;
        $this->sessionId      = $sessionId;
        $this->staffId        = $staffId;

        // Generate password based on subject, class, term, session
        $this->password = $this->generateFilePassword();

        // Load dynamic assessments
        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassId);
        $this->assessments = collect();
        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $this->assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->orderBy('id')
                ->get();
        }
    }

    /**
     * Generate a password for the Excel file
     */
    protected function generateFilePassword()
    {
        $subjectClass = \App\Models\Subjectclass::with('subject')->find($this->subjectclassId);
        $schoolclass = \App\Models\Schoolclass::find($this->schoolclassId);
        $term = \App\Models\SchoolTerm::find($this->termId);
        $session = \App\Models\SchoolSession::find($this->sessionId);

        $subjectCode = $subjectClass && $subjectClass->subject ? $subjectClass->subject->subject_code : 'SUBJ';
        $className = $schoolclass ? $schoolclass->schoolclass : 'CLASS';
        $termName = $term ? substr(preg_replace('/[^a-zA-Z]/', '', $term->term), 0, 3) : 'TRM';
        $sessionYear = $session ? preg_replace('/[^0-9]/', '', $session->session) : date('Y');

        // Generate password: e.g., "CDN_JSS1_1ST_2025"
        $password = strtoupper($subjectCode . '_' . $className . '_' . $termName . '_' . $sessionYear);
        $password = preg_replace('/[^A-Z0-9_]/', '', $password);

        return $password;
    }

    public function view(): View
    {
        $broadsheets = Broadsheets::where('broadsheets.subjectclass_id', $this->subjectclassId)
            ->where('broadsheets.staff_id', $this->staffId)
            ->where('broadsheets.term_id', $this->termId)
            ->with('assessmentScores')
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheets.subjectclass_id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $this->sessionId)
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname')
            ->get([
                'broadsheets.id',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentRegistration.othername as mname',
                'subject.subject', 'subject.subject_code',
                'schoolclass.schoolclass', 'schoolarm.arm',
                'schoolterm.term', 'schoolsession.session',
                'subjectclass.id as subjectclid', 'broadsheets.staff_id', 'broadsheets.term_id',
                'broadsheet_records.session_id as sessionid', 'users.name as staffname',
                'studentpicture.picture',
                'broadsheets.total', 'broadsheets.bf', 'broadsheets.cum',
                'broadsheets.grade', 'broadsheets.subject_position_class as position',
                'broadsheets.remark', 'broadsheets.avg', 'broadsheets.cmin', 'broadsheets.cmax',
            ]);

        $school = SchoolInformation::getActiveSchool();

        return view('exports.studentscoresheet', [
            'broadsheets' => $broadsheets,
            'assessments' => $this->assessments,
            'school'      => $school,
        ]);
    }

    public function properties(): array
    {
        $subjectClass = \App\Models\Subjectclass::with('subject')->find($this->subjectclassId);
        $schoolclass = \App\Models\Schoolclass::find($this->schoolclassId);
        $term = \App\Models\SchoolTerm::find($this->termId);
        $session = \App\Models\SchoolSession::find($this->sessionId);

        $subjectName = $subjectClass && $subjectClass->subject ? $subjectClass->subject->subject : 'subject';
        $className = $schoolclass ? $schoolclass->schoolclass : 'class';
        $termName = $term ? $term->term : 'term';
        $sessionName = $session ? $session->session : 'session';

        return [
            'creator'        => auth()->user()->name ?? 'Teacher',
            'lastModifiedBy' => auth()->user()->name ?? 'Teacher',
            'title'          => "{$subjectName}_{$className}_{$termName}_{$sessionName}_Scoresheet",
            'description'    => "Password protected scoresheet - Password: {$this->password}",
            'subject'        => $subjectName,
            'keywords'       => 'scoresheet,marks,excel,export,password_protected',
            'category'       => 'Education',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Get the last column letter
        $assessmentCount = $this->assessments->count();
        $lastCol = chr(ord('A') + 2 + $assessmentCount + 6);

        // FIRST: Unlock all cells in the data area (rows 7 and below)
        $sheet->getStyle('A7:' . $lastCol . '1000')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

        // THEN: Lock specific columns that should NOT be editable
        // Lock SN column (A)
        $sheet->getStyle('A7:A1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock Admission No column (B)
        $sheet->getStyle('B7:B1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock Student Name column (C)
        $sheet->getStyle('C7:C1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock Total column (after assessments)
        $totalCol = chr(ord('D') + $assessmentCount);
        $sheet->getStyle($totalCol . '7:' . $totalCol . '1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock BF column
        $bfCol = chr(ord('D') + $assessmentCount + 1);
        $sheet->getStyle($bfCol . '7:' . $bfCol . '1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock Cum column
        $cumCol = chr(ord('D') + $assessmentCount + 2);
        $sheet->getStyle($cumCol . '7:' . $cumCol . '1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock Grade column
        $gradeCol = chr(ord('D') + $assessmentCount + 3);
        $sheet->getStyle($gradeCol . '7:' . $gradeCol . '1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock Position column
        $positionCol = chr(ord('D') + $assessmentCount + 4);
        $sheet->getStyle($positionCol . '7:' . $positionCol . '1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock Remark column
        $remarkCol = chr(ord('D') + $assessmentCount + 5);
        $sheet->getStyle($remarkCol . '7:' . $remarkCol . '1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Lock header rows (rows 1-6)
        $sheet->getStyle('A1:' . $lastCol . '6')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Assessment score columns remain UNLOCKED (they can be edited)

        // Apply bold styling to headers
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastCol}3")->getFont()->setBold(true);
        $sheet->getStyle("A6:{$lastCol}6")->getFont()->setBold(true);

        // Freeze pane
        $sheet->freezePane('A7');

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A7');

                // Enable sheet protection with password
                // This prevents editing of locked cells
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword($this->password);

                // Protect the workbook structure with the same password
                // This prompts for password when OPENING the file
                $spreadsheet = $event->sheet->getDelegate()->getParent();
                $spreadsheet->getSecurity()->setLockWindows(true);
                $spreadsheet->getSecurity()->setLockStructure(true);
                $spreadsheet->getSecurity()->setWorkbookPassword($this->password);
            },
        ];
    }

    /**
     * Get the password
     */
    public function getPassword()
    {
        return $this->password;
    }
}
