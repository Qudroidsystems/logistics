<?php

namespace App\Exports;

use App\Models\Broadsheets;
use App\Models\SchoolInformation;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class MockRecordsheetExport implements FromView, ShouldAutoSize, WithStyles, WithEvents
{
    use Exportable;

    protected $schoolclassId;
    protected $subjectclassId;
    protected $termId;
    protected $sessionId;
    protected $staffId;

    public function __construct($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId)
    {
        $this->schoolclassId = $schoolclassId;
        $this->subjectclassId = $subjectclassId;
        $this->termId = $termId;
        $this->sessionId = $sessionId;
        $this->staffId = $staffId;
    }

    public function view(): View
    {
        // Use constructor parameters instead of session
        $subjectclass_id = $this->subjectclassId;
        $staff_id = $this->staffId;
        $term_id = $this->termId;
        $session_id = $this->sessionId;

        // Fetch broadsheets
        $broadsheets = Broadsheets::where('broadsheets.subjectclass_id', $subjectclass_id)
            ->where('broadsheets.staff_id', $staff_id)
            ->where('broadsheets.term_id', $term_id)
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
            ->where('broadsheet_records.session_id', $session_id)
            ->get([
                'broadsheets.id',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentRegistration.othername as mname',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'schoolterm.term',
                'schoolsession.session',
                'subjectclass.id as subjectclid',
                'broadsheets.staff_id',
                'broadsheets.term_id',
                'broadsheet_records.session_id as sessionid',
                'users.name as staffname',
                'studentpicture.picture',
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',
                'broadsheets.total',
                'broadsheets.grade',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
            ])->sortBy('admissionno');

        // Fetch active school information
        $school = SchoolInformation::getActiveSchool();

        return view('exports.studentscoresheet', compact('broadsheets', 'school'));
    }

    public function styles(Worksheet $sheet)
    {
        // Bold headers for school info and table headers
        $sheet->getStyle('A1:P1')->getFont()->setBold(true); // School Name
        $sheet->getStyle('A2:P2')->getFont()->setBold(true); // Address
        $sheet->getStyle('A3:P3')->getFont()->setBold(true); // Contact & Motto
        $sheet->getStyle('A6:P6')->getFont()->setBold(true); // Table Headers

        // Merge cells for school info
        $sheet->mergeCells('A1:P1');
        $sheet->mergeCells('A2:P2');
        $sheet->mergeCells('A3:P3');
        $sheet->mergeCells('A4:P4'); // Subject, Class, Term, Session
        $sheet->mergeCells('A5:P5'); // Empty row for spacing

        // Center align school info
        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Protect sheet with password
        $sheet->getProtection()->setPassword(env('EXCEL_PROTECTION_PASSWORD', 'password'));
        $sheet->getProtection()->setSheet(true);

        // Use constructor parameters instead of session
        $broadsheets_count = Broadsheets::where('broadsheets.subjectclass_id', $this->subjectclassId)
            ->where('broadsheets.staff_id', $this->staffId)
            ->where('broadsheets.term_id', $this->termId)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.session_id', $this->sessionId)
            ->count();

        // Unlock score input columns (D, E, F, H for CA1, CA2, CA3, EXAM)
        for ($i = 7; $i <= $broadsheets_count + 6; $i++) { // Start from row 7 due to 5 header rows
            $sheet->getStyle("D{$i}:F{$i}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
            $sheet->getStyle("H{$i}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
        }

        // Hide unnecessary columns (L, M, N, O, P for BROADSHEETID, STAFFID, etc.)
        foreach (['L', 'M', 'N', 'O', 'P'] as $column) {
            $sheet->getColumnDimension($column)->setVisible(false)->setWidth(0);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Freeze pane below header rows (row 6)
                $event->sheet->getDelegate()->freezePane('A7');
            },
        ];
    }
}