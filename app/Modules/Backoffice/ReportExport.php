<?php

namespace App\Modules\Backoffice;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** One ReportService table as a spreadsheet: bold headings, naira and percentage columns formatted as numbers. */
class ReportExport implements FromArray, WithHeadings, WithColumnFormatting, ShouldAutoSize, WithStyles
{
    public function __construct(private array $table)
    {
    }

    public function array(): array
    {
        return $this->table['rows'];
    }

    public function headings(): array
    {
        return array_map(function ($h, $f) {
            return match ($f) {
                'money' => "{$h} (₦)", 'pct' => "{$h} (%)", 'minutes' => "{$h} (min)", 'km' => "{$h} (km)", default => $h,
            };
        }, $this->table['headings'], $this->table['formats']);
    }

    public function columnFormats(): array
    {
        $out = [];
        foreach ($this->table['formats'] as $i => $f) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $fmt = match ($f) {
                'money' => '#,##0.00', 'pct', 'km', 'rating' => '0.0#', 'int', 'minutes' => '#,##0', default => null,
            };
            if ($fmt) {
                $out[$col] = $fmt;
            }
        }

        return $out;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
