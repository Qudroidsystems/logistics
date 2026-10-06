<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Club;
use App\Models\Sport;

/**
 * Generates a locked-down spreadsheet template for batch student uploads.
 *
 * Column layout (0-indexed) — MUST stay in sync with App\Imports\StudentsImport:
 *
 *  0  admissionno        8  placeofbirth      16 termid (locked)      24 mother_name
 *  1  surname            9  nationality        17 sessionid (locked)  25 mother_phone
 *  2  firstname          10 state              18 father_title        26 mother_occupation
 *  3  othername          11 local              19 father_name         27 mother_office_address
 *  4  gender             12 religion           20 father_phone        28 parent_address
 *  5  homeaddress        13 lastschool         21 office_address      29 parent_religion
 *  6  dob                14 lastclass          22 father_occupation
 *  7  age                15 schoolclassid (locked) 23 mother_title
 *
 * Extended fields — appended at the end (not inserted into the block above)
 * so templates/imports already in circulation keep working unchanged:
 *  30 blood_group                  35 guardian_name
 *  31 genotype                     36 guardian_relationship
 *  32 emergency_contact_name       37 guardian_phone
 *  33 emergency_contact_phone      38 whatsapp_number (parent/guardian)
 *  34 allergies_medical_conditions 39 club (matched by name)
 *                                  40 sport (matched by name)
 */
class StudentBatchTemplateExport implements FromArray, WithHeadings, WithTitle, WithColumnWidths, WithEvents
{
    protected const EDITABLE_COLUMNS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD',
        'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO',
    ];

    protected const LOCKED_COLUMNS = ['P', 'Q', 'R'];

    protected const TOTAL_COLUMNS = 41;

    protected int $schoolclassid;
    protected int $termid;
    protected int $sessionid;
    protected int $rows;
    protected string $className;
    protected string $termName;
    protected string $sessionName;

    public function __construct(
        int $schoolclassid,
        int $termid,
        int $sessionid,
        int $rows,
        string $className,
        string $termName,
        string $sessionName
    ) {
        $this->schoolclassid = $schoolclassid;
        $this->termid        = $termid;
        $this->sessionid     = $sessionid;
        $this->rows          = max(1, min(500, $rows));
        $this->className     = $className;
        $this->termName      = $termName;
        $this->sessionName   = $sessionName;
    }

    public function title(): string
    {
        return 'Student Data';
    }

    /**
     * Blank data rows, pre-filled with the locked class/term/session IDs
     * in columns P, Q, R (indexes 15, 16, 17).
     */
    public function array(): array
    {
        $blankRow = array_fill(0, self::TOTAL_COLUMNS, '');
        $blankRow[15] = $this->schoolclassid;
        $blankRow[16] = $this->termid;
        $blankRow[17] = $this->sessionid;

        return array_fill(0, $this->rows, $blankRow);
    }

    public function headings(): array
    {
        return [
            'Admission No*',
            'Surname*',
            'First Name*',
            'Other Name',
            'Gender*',
            'Home Address',
            'Date of Birth (YYYY-MM-DD)*',
            'Age',
            'Place of Birth',
            'Nationality',
            'State of Origin',
            'LGA',
            'Religion',
            'Last School',
            'Last Class',
            'Class ID (locked)',
            'Term ID (locked)',
            'Session ID (locked)',
            'Father Title',
            'Father Name',
            'Father Phone',
            'Office Address',
            'Father Occupation',
            'Mother Title',
            'Mother Name',
            'Mother Phone',
            'Mother Occupation',
            'Mother Office Address',
            'Parent Address',
            'Parent Religion',
            'Blood Group',
            'Genotype',
            'Emergency Contact Name',
            'Emergency Contact Phone',
            'Allergies / Medical Conditions',
            'Guardian Name (if applicable)',
            'Guardian Relationship to Student',
            'Guardian Phone',
            'Parent/Guardian WhatsApp Number',
            'Club (optional)',
            'Sport (optional)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A'  => 16, 'B'  => 16, 'C'  => 16, 'D'  => 16, 'E'  => 10,
            'F'  => 24, 'G'  => 16, 'H'  => 8,  'I'  => 18, 'J'  => 16,
            'K'  => 18, 'L'  => 18, 'M'  => 14, 'N'  => 20, 'O'  => 14,
            'P'  => 12, 'Q'  => 12, 'R'  => 12,
            'S'  => 12, 'T'  => 18, 'U'  => 16, 'V'  => 20, 'W'  => 18,
            'X'  => 12, 'Y'  => 18, 'Z'  => 16, 'AA' => 18, 'AB' => 22,
            'AC' => 20, 'AD' => 16,
            'AE' => 14, 'AF' => 10, 'AG' => 22, 'AH' => 22, 'AI' => 28,
            'AJ' => 20, 'AK' => 24, 'AL' => 16, 'AM' => 20,
            'AN' => 18, 'AO' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $this->rows + 1; // +1 for header row
                $lastCol = 'AO';

                // ----- Header styling -----
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4361EE'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'wrapText'   => true,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->freezePane('A2');

                // ----- Locked columns: grey fill so it's visually obvious -----
                foreach (self::LOCKED_COLUMNS as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E9ECEF'],
                        ],
                        'font' => [
                            'color' => ['rgb' => '6C757D'],
                        ],
                    ]);
                }

                // ----- Sheet protection: unlock everything except P/Q/R -----
                foreach (self::EDITABLE_COLUMNS as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_UNPROTECTED);
                }

                // Enable sheet protection (no password – only prevents accidental edits to ID columns)
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setSort(false);
                $sheet->getProtection()->setInsertRows(false);
                $sheet->getProtection()->setDeleteRows(false);

                // ----- Gender dropdown (column E) -----
                for ($row = 2; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("E{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Invalid Gender');
                    $validation->setError('Please select Male or Female from the dropdown.');
                    $validation->setFormula1('"Male,Female"');
                }

                // ----- Hidden "Lists" sheet for the State dropdown -----
                $spreadsheet = $sheet->getParent();
                $listSheet   = $spreadsheet->createSheet();
                $listSheet->setTitle('Lists');

                $states = [
                    'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
                    'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo',
                    'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa',
                    'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba',
                    'Yobe', 'Zamfara',
                ];

                foreach ($states as $i => $stateName) {
                    $listSheet->setCellValue('A' . ($i + 1), $stateName);
                }

                $listSheet->getColumnDimension('A')->setWidth(20);

                // ----- Club and Sport name lists (dynamic, from DB) -----
                $clubNames = Club::orderBy('club')->pluck('club')->filter()->values();
                foreach ($clubNames as $i => $clubName) {
                    $listSheet->setCellValue('B' . ($i + 1), $clubName);
                }
                $listSheet->getColumnDimension('B')->setWidth(20);

                $sportNames = Sport::orderBy('sport')->pluck('sport')->filter()->values();
                foreach ($sportNames as $i => $sportName) {
                    $listSheet->setCellValue('C' . ($i + 1), $sportName);
                }
                $listSheet->getColumnDimension('C')->setWidth(20);

                $listSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

                $stateRange = 'Lists!$A$1:$A$' . count($states);
                $clubRange  = 'Lists!$B$1:$B$' . max(1, $clubNames->count());
                $sportRange = 'Lists!$C$1:$C$' . max(1, $sportNames->count());

                for ($row = 2; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("K{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_WARNING);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Unrecognised State');
                    $validation->setError('This state is not in the standard list — double-check the spelling.');
                    $validation->setFormula1($stateRange);
                }

                // ----- Club dropdown (column AN) — warning-style since it's
                //       optional and matched by name on import -----
                for ($row = 2; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("AN{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_WARNING);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Unrecognised Club');
                    $validation->setError('This club name was not found — double-check the spelling, or leave blank.');
                    $validation->setFormula1($clubRange);
                }

                // ----- Sport dropdown (column AO) — warning-style, same reasoning -----
                for ($row = 2; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("AO{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_WARNING);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Unrecognised Sport');
                    $validation->setError('This sport name was not found — double-check the spelling, or leave blank.');
                    $validation->setFormula1($sportRange);
                }

                // ----- Blood Group dropdown (column AE) -----
                for ($row = 2; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("AE{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Invalid Blood Group');
                    $validation->setError('Please select a valid blood group from the dropdown.');
                    $validation->setFormula1('"A+,A-,B+,B-,AB+,AB-,O+,O-"');
                }

                // ----- Genotype dropdown (column AF) -----
                for ($row = 2; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("AF{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Invalid Genotype');
                    $validation->setError('Please select a valid genotype from the dropdown.');
                    $validation->setFormula1('"AA,AS,SS,AC,SC,CC"');
                }

                // ----- Instructions sheet -----
                $infoSheet = $spreadsheet->createSheet();
                $infoSheet->setTitle('Instructions');

                $infoSheet->fromArray([
                    ['Batch Upload Template'],
                    [''],
                    ['Class', $this->className],
                    ['Term', $this->termName],
                    ['Session', $this->sessionName],
                    [''],
                    ['Instructions:'],
                    ['1. Fill in one row per student on the "Student Data" sheet.'],
                    ['2. Do NOT edit the grey Class ID / Term ID / Session ID columns — they are locked and pre-filled.'],
                    ['3. Date of Birth must be in YYYY-MM-DD format (e.g. 2015-03-25).'],
                    ['4. Gender and State have dropdown lists — please use them instead of typing freely.'],
                    ['5. Required columns are marked with an asterisk (*).'],
                    ['6. Save the file and upload it back through the Batch Upload screen.'],
                    ['7. Blood Group and Genotype have dropdown lists — please use them instead of typing freely.'],
                    ['8. Guardian and Emergency Contact columns are optional but recommended where applicable.'],
                    ['9. Club and Sport are optional — pick from the dropdown list, or leave blank.'],
                    [''],
                    ['Notes:'],
                    ['- Admission No must be unique.'],
                    ['- Leave Age blank if you want the system to calculate it later.'],
                    ['- Parent information is optional but recommended.'],
                ], null, 'A1');

                $infoSheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                ]);
                $infoSheet->getStyle('A3:A5')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $infoSheet->getStyle('A7')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $infoSheet->getStyle('A14')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $infoSheet->getColumnDimension('A')->setWidth(28);
                $infoSheet->getColumnDimension('B')->setWidth(50);

                // Move Instructions sheet to the front.
                // NOTE: Spreadsheet has no insertSheet() method — addSheet()
                // is the correct API for re-attaching an already-detached
                // worksheet object at a specific index.
                $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($infoSheet));
                $spreadsheet->addSheet($infoSheet, 0);
                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}