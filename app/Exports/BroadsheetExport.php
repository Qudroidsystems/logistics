<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class BroadsheetExport implements FromArray, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        $class   = $this->data['schoolclass']->schoolclass ?? 'Class';
        $arm     = $this->data['schoolclass']->arm_name ?? '';
        $session = $this->data['schoolsession']->session ?? '';
        $term    = $this->data['schoolterm']->term ?? '';
        $title = trim("{$class} {$arm} {$session} {$term}");
        // Remove invalid characters for sheet name (max 31 chars)
        $title = preg_replace('/[\/\\\\:*?"<>|]/', '_', $title);
        return substr($title, 0, 31);
    }

    public function array(): array
    {
        $rows        = [];
        $schoolInfo  = $this->data['schoolInfo'];
        $schoolclass = $this->data['schoolclass'];
        $session     = $this->data['schoolsession'];
        $term        = $this->data['schoolterm'];
        $assessments = $this->data['assessments'];
        $subjects    = $this->data['subjects'];
        $students    = $this->data['studentRows'];
        $selected    = $this->data['selectedColumns'] ?? [];

        // ── School header rows ─────────────────────────────────────────────────
        $rows[] = [$schoolInfo->school_name ?? 'SCHOOL NAME'];
        $rows[] = [$schoolInfo->school_address ?? ''];
        if (!empty($schoolInfo->school_motto)) {
            $rows[] = ['Motto: ' . $schoolInfo->school_motto];
        }
        $rows[] = []; // spacer

        $rows[] = [
            'CLASS BROADSHEET',
            '', '',
            'Class: ' . ($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm_name ?? ''),
            'Session: ' . ($session->session ?? ''),
            'Term: ' . ($term->term ?? ''),
            'Total Students: ' . $this->data['totalStudents'],
            'Generated: ' . $this->data['generatedAt'],
        ];
        $rows[] = []; // spacer

        // ── Determine active columns ───────────────────────────────────────────
        $showAdmNo    = empty($selected) || in_array('admission_no', $selected);
        $showName     = empty($selected) || in_array('name', $selected);
        $showGender   = in_array('gender', $selected);
        $showTotal    = empty($selected) || in_array('total', $selected);
        $showBF       = in_array('bf', $selected);
        $showCum      = empty($selected) || in_array('cum', $selected);
        $showGrade    = empty($selected) || in_array('grade', $selected);
        $showPosition = empty($selected) || in_array('position', $selected);
        $showAvg      = in_array('class_average', $selected);
        $showRemark   = in_array('remark', $selected);
        $showGPA      = in_array('gpa', $selected);
        $showCGPA     = in_array('cgpa', $selected);

        // ── Subject-level header (row 1 of header: subject names span multiple cols) ──
        $headerRow1 = ['SN'];
        if ($showAdmNo)  $headerRow1[] = 'Adm. No';
        if ($showName)   $headerRow1[] = 'Student Name';
        if ($showGender) $headerRow1[] = 'Gender';

        foreach ($subjects as $subInfo) {
            $colCount = 0;
            foreach ($assessments as $a) {
                if (empty($selected) || in_array('assessment_' . $a->id, $selected)) $colCount++;
            }
            if ($showTotal)    $colCount++;
            if ($showBF)       $colCount++;
            if ($showCum)      $colCount++;
            if ($showGrade)    $colCount++;
            if ($showPosition) $colCount++;
            if ($showAvg)      $colCount++;
            if ($showRemark)   $colCount++;

            $headerRow1[] = $subInfo['subject_name'] . ' (' . ($subInfo['subject_code'] ?? '') . ')';
            for ($i = 1; $i < $colCount; $i++) $headerRow1[] = '';
        }
        if ($showGPA)  $headerRow1[] = 'GPA';
        if ($showCGPA) $headerRow1[] = 'CGPA';
        $rows[] = $headerRow1;

        // ── Sub-header row (assessment names + score labels) ──────────────────
        $headerRow2 = [];
        $headerRow2[] = ''; // SN
        if ($showAdmNo)  $headerRow2[] = '';
        if ($showName)   $headerRow2[] = '';
        if ($showGender) $headerRow2[] = '';

        foreach ($subjects as $subInfo) {
            foreach ($assessments as $a) {
                if (empty($selected) || in_array('assessment_' . $a->id, $selected)) {
                    $headerRow2[] = $a->name . ' (' . $a->max_score . ')';
                }
            }
            if ($showTotal)    $headerRow2[] = 'Total';
            if ($showBF)       $headerRow2[] = 'BF';
            if ($showCum)      $headerRow2[] = 'Cum';
            if ($showGrade)    $headerRow2[] = 'Grade';
            if ($showPosition) $headerRow2[] = 'Pos';
            if ($showAvg)      $headerRow2[] = 'Avg';
            if ($showRemark)   $headerRow2[] = 'Remark';
        }
        if ($showGPA)  $headerRow2[] = '';
        if ($showCGPA) $headerRow2[] = '';
        $rows[] = $headerRow2;

        // ── Data rows ─────────────────────────────────────────────────────────
        $sn = 0;
        foreach ($students as $stu) {
            $sn++;
            $row = [(string)$sn];
            if ($showAdmNo)  $row[] = $stu['admissionno'];
            if ($showName)   $row[] = trim($stu['lastname'] . ', ' . $stu['firstname']);
            if ($showGender) $row[] = $stu['gender'] ?? '';

            foreach ($subjects as $subId => $subInfo) {
                $subData = $stu['subjects'][$subId] ?? [];

                foreach ($assessments as $a) {
                    if (empty($selected) || in_array('assessment_' . $a->id, $selected)) {
                        $row[] = $subData['assessments'][$a->id] ?? 0;
                    }
                }
                if ($showTotal)    $row[] = $subData['total'] ?? '';
                if ($showBF)       $row[] = $subData['bf'] ?? '';
                if ($showCum)      $row[] = $subData['cum'] ?? '';
                if ($showGrade)    $row[] = $subData['grade'] ?? '';
                if ($showPosition) $row[] = $subData['position'] ?? '';
                if ($showAvg)      $row[] = $subData['class_average'] ?? '';
                if ($showRemark)   $row[] = $subData['remark'] ?? '';
            }
            if ($showGPA)  $row[] = $stu['gpa'];
            if ($showCGPA) $row[] = $stu['cgpa'];
            $rows[] = $row;
        }

        // ── Stats footer ──────────────────────────────────────────────────────
        $rows[] = [];
        $statsRow = ['', 'CLASS AVERAGE', ''];
        if ($showGender) $statsRow[] = '';

        foreach ($subjects as $subId => $subInfo) {
            $stats = $this->data['subjectStats'][$subId] ?? [];
            foreach ($assessments as $a) {
                if (empty($selected) || in_array('assessment_' . $a->id, $selected)) $statsRow[] = '';
            }
            if ($showTotal)    $statsRow[] = $stats['avg'] ?? '';
            if ($showBF)       $statsRow[] = '';
            if ($showCum)      $statsRow[] = '';
            if ($showGrade)    $statsRow[] = '';
            if ($showPosition) $statsRow[] = '';
            if ($showAvg)      $statsRow[] = $stats['avg'] ?? '';
            if ($showRemark)   $statsRow[] = '';
        }
        $rows[] = $statsRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style header rows (rows 7 and 8 after the 6 meta rows)
                $headerRow1 = 7;
                $headerRow2 = 8;

                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Dark header background for subject names row
                $sheet->getStyle("A{$headerRow1}:{$lastCol}{$headerRow1}")->applyFromArray([
                    'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1e3a5f']],
                    'font'  => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true, 'size' => 9],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                ]);

                // Lighter header for assessment sub-headers
                $sheet->getStyle("A{$headerRow2}:{$lastCol}{$headerRow2}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563eb']],
                    'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Zebra striping for data rows
                for ($r = $headerRow2 + 1; $r <= $lastRow - 2; $r++) {
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F4FA']],
                        ]);
                    }
                }

                // Borders for all data
                $sheet->getStyle("A{$headerRow1}:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFD1D5DB'],
                        ],
                    ],
                ]);

                // Freeze panes below header
                $sheet->freezePane("A" . ($headerRow2 + 1));

                // Row height for headers
                $sheet->getRowDimension($headerRow1)->setRowHeight(35);
                $sheet->getRowDimension($headerRow2)->setRowHeight(25);
            },
        ];
    }
}
