<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class StaffUserBatchTemplateExport implements FromArray, WithHeadings, WithTitle, WithColumnWidths, WithEvents
{
    protected int $rows;

    public function __construct(int $rows = 30)
    {
        $this->rows = max(1, min(200, $rows));
    }

    public function title(): string
    {
        return 'Staff Users';
    }

    public function array(): array
    {
        // Pre-fill Role column (index 2) with "Staff"
        $blank = ['', '', 'Staff', ''];
        return array_fill(0, $this->rows, $blank);
    }

    public function headings(): array
    {
        return [
            'Full Name*',
            'Email Address*',
            'Role (locked)',
            'Password*',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 32,
            'C' => 16,
            'D' => 18,
        ];
    }

  public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet   = $event->sheet->getDelegate();
            $lastRow = $this->rows + 1;
            $lastCol = 'D';

            // Header styling
            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A5F'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(28);
            $sheet->freezePane('A2');

            // Locked Role column (C) – grey
            $sheet->getStyle("C2:C{$lastRow}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF'],
                ],
                'font' => [
                    'color' => ['rgb' => '6C757D'],
                ],
            ]);

            // Unlock editable columns
            foreach (['A', 'B', 'D'] as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_UNPROTECTED);
            }

            // Protect sheet
            $sheet->getProtection()->setSheet(true);
            $sheet->getProtection()->setSort(false);
            $sheet->getProtection()->setInsertRows(false);
            $sheet->getProtection()->setDeleteRows(false);

            // Instructions sheet – keep it AFTER the data sheet
            $spreadsheet = $sheet->getParent();
            $info = $spreadsheet->createSheet();
            $info->setTitle('Instructions');
            $info->fromArray([
                ['Staff User Batch Upload Template'],
                [''],
                ['Instructions:'],
                ['1. Fill one row per staff member on the "Staff Users" sheet.'],
                ['2. Do NOT edit the grey "Role (locked)" column – it is fixed to Staff.'],
                ['3. Required columns are marked with an asterisk (*).'],
                ['4. Password will be hashed automatically on import.'],
                ['5. Email must be unique in the system.'],
                ['6. Save the file and upload it via the Users page → Import Staff.'],
                [''],
                ['Notes:'],
                ['- Role is always set to "Staff". Other roles are not accepted.'],
                ['- Existing users with the same email will be skipped.'],
            ], null, 'A1');

            $info->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);
            $info->getStyle('A3')->applyFromArray(['font' => ['bold' => true]]);
            $info->getColumnDimension('A')->setWidth(80);

            // Important: do NOT move Instructions to index 0
            // Leave "Staff Users" as the first sheet
            $spreadsheet->setActiveSheetIndex(0);
        },
    ];
}
}