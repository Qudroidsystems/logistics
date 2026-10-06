<?php

namespace App\Exports;

use App\Models\SchoolInformation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Renders through a table-only blade so the spreadsheet columns can never
 * drift from what the on-screen report shows.
 *
 * Also stamps the active SchoolInformation record (name, address, contact,
 * motto, logo) at the top so an exported file is identifiable on its own
 * once it leaves the app — printed, emailed, or handed to someone who
 * wasn't looking at the dashboard.
 *
 * The header block is a FIXED number of rows (see the blade) regardless of
 * whether a school record exists or which fields are filled in, so the
 * data-table row indices below never drift out of sync with the blade.
 */
class StaffAttendanceExport implements FromView, ShouldAutoSize, WithStyles, WithDrawings
{
    protected ?SchoolInformation $school;

    public function __construct(
        protected $rows,
        protected string $dateFrom,
        protected string $dateTo
    ) {
        $this->school = SchoolInformation::getActiveSchool();
    }

    public function view(): View
    {
        return view('attendance.admin.exports.staff-attendance-xlsx', [
            'rows'     => $this->rows,
            'dateFrom' => $this->dateFrom,
            'dateTo'   => $this->dateTo,
            'school'   => $this->school,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Row 1: school name. Row 4: report title. Row 7: table header.
            // These MUST match the fixed row layout in the blade exactly.
            1 => ['font' => ['bold' => true, 'size' => 15]],
            2 => ['font' => ['size' => 10, 'color' => ['rgb' => '6B7280']]],
            4 => ['font' => ['bold' => true, 'size' => 12]],
            7 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EEF2FF'],
                ],
            ],
        ];
    }

    /**
     * Embeds the school logo as a real image (not an HTML <img> tag, which
     * PhpSpreadsheet's HTML-to-Excel conversion renders inconsistently) —
     * only if a logo file actually exists on the public disk.
     */
    public function drawings()
    {
        if (!$this->school || !$this->school->school_logo) {
            return [];
        }

        if (filter_var($this->school->school_logo, FILTER_VALIDATE_URL)) {
            return []; // remote URL logos aren't embeddable this way — text header still shows the school name
        }

        if (!Storage::disk('public')->exists($this->school->school_logo)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('School Logo');
        $drawing->setPath(Storage::disk('public')->path($this->school->school_logo));
        $drawing->setHeight(50);
        $drawing->setCoordinates('H1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);

        return [$drawing];
    }
}