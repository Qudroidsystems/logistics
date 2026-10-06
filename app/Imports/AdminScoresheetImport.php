<?php

namespace App\Imports;

use App\Models\Assessment;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetRecord;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Student;
use App\Models\Subjectclass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;

class AdminScoresheetImport implements ToCollection, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected array $importData;
    protected int   $successCount   = 0;
    protected array $importFailures = [];
    protected       $assessments;
    protected       $schoolclass    = null;
    protected       $subjectId      = null;

    // The canonical staff_id that belongs to this subjectclass (from subjectteacher)
    protected ?int  $canonicalStaffId = null;

    // Column mapping discovered from the header row
    protected int   $admissionColumnIndex = -1;
    protected array $assessmentColumnMap  = [];
    protected int   $headerRowIndex       = -1;

    public function __construct(array $importData)
    {
        $this->importData = $importData;

        try {
            // Load assessments for this class category
            if (!empty($importData['schoolclass_id'])) {
                $this->schoolclass = Schoolclass::with('classcategories')
                    ->find($importData['schoolclass_id']);

                if ($this->schoolclass && $this->schoolclass->classcategories->isNotEmpty()) {
                    $categoryIds       = $this->schoolclass->classcategories->pluck('id');
                    $this->assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                        ->orderBy('id')
                        ->get();

                    Log::info('[AdminImport] Loaded assessments: '
                        . $this->assessments->pluck('name')->implode(', '));
                } else {
                    $this->assessments = collect();
                }
            } else {
                $this->assessments = collect();
            }

            // Resolve subject ID from subjectclass
            if (!empty($importData['subjectclass_id'])) {
                $subjectClass    = Subjectclass::find($importData['subjectclass_id']);
                $this->subjectId = $subjectClass ? $subjectClass->subjectid : null;
            }

            // Resolve the canonical staff_id from subjectteacher — this is the
            // teacher who owns the subjectclass, regardless of who is importing.
            // We use this when CREATING a new broadsheet row so it is consistent
            // with rows the teacher themselves would have created.
            if (!empty($importData['subjectclass_id'])) {
                $this->canonicalStaffId = (int) DB::table('subjectclass')
                    ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                    ->where('subjectclass.id', $importData['subjectclass_id'])
                    ->value('subjectteacher.staffid');

                Log::info("[AdminImport] Canonical staff_id resolved: {$this->canonicalStaffId}");
            }

        } catch (\Exception $e) {
            Log::error('[AdminImport] Constructor error: ' . $e->getMessage());
            throw $e;
        }
    }

    // =========================================================================
    // ToCollection entry point
    // =========================================================================

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->importFailures[] = ['row' => 0, 'errors' => ['The Excel file is empty.']];
            return;
        }

        Log::info('[AdminImport] Total rows in file: ' . $rows->count());

        $this->findHeaderRow($rows);

        if ($this->headerRowIndex === -1) {
            $this->importFailures[] = [
                'row'    => 0,
                'errors' => [
                    'Could not find a header row containing "Admission No". '
                    . 'Please ensure the exported file has not been structurally modified.',
                ],
            ];
            return;
        }

        Log::info("[AdminImport] Header row at index {$this->headerRowIndex}, "
            . "admission col {$this->admissionColumnIndex}");
        Log::info('[AdminImport] Assessment map: ' . json_encode($this->assessmentColumnMap));

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                if ($index <= $this->headerRowIndex) {
                    continue;
                }

                $rowValues = array_values(is_array($row) ? $row : $row->toArray());

                $hasData = false;
                foreach ($rowValues as $v) {
                    if ($v !== null && $v !== '') {
                        $hasData = true;
                        break;
                    }
                }
                if (!$hasData) {
                    continue;
                }

                $admVal = $rowValues[$this->admissionColumnIndex] ?? null;
                if ($admVal === null || trim((string) $admVal) === '') {
                    Log::debug("[AdminImport] Skipping row " . ($index + 1) . " — no admission no.");
                    continue;
                }

                try {
                    $this->processRow($rowValues, $index + 1);
                } catch (\Exception $e) {
                    $this->importFailures[] = [
                        'row'    => $index + 1,
                        'errors' => [$e->getMessage()],
                    ];
                    Log::warning("[AdminImport] Row " . ($index + 1) . " error: " . $e->getMessage());
                }
            }

            DB::commit();
            Log::info("[AdminImport] Done. Success={$this->successCount}, "
                . "Failures=" . count($this->importFailures));

            session([
                'import_progress' => 100,
                'import_status'   => 'completed',
                'import_message'  => 'Import completed!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[AdminImport] Transaction failed: ' . $e->getMessage());
            $this->importFailures[] = ['row' => 0, 'errors' => ['Transaction failed: ' . $e->getMessage()]];
            session([
                'import_progress' => 0,
                'import_status'   => 'error',
                'import_message'  => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Header discovery
    // =========================================================================

    protected function findHeaderRow(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowValues = array_values(is_array($row) ? $row : $row->toArray());

            foreach ($rowValues as $colIndex => $value) {
                if (!is_string($value)) {
                    continue;
                }
                $lower = strtolower(trim($value));
                if (
                    str_contains($lower, 'admission')
                    || str_contains($lower, 'adm no')
                    || str_contains($lower, 'student id')
                ) {
                    $this->headerRowIndex       = $index;
                    $this->admissionColumnIndex = $colIndex;
                    $this->buildAssessmentColumnMap($rowValues);
                    return;
                }
            }
        }
    }

    protected function buildAssessmentColumnMap(array $headerValues): void
    {
        foreach ($headerValues as $colIndex => $headerText) {
            if ($colIndex === $this->admissionColumnIndex) {
                continue;
            }
            if (empty($headerText) || !is_string($headerText)) {
                continue;
            }

            $clean = trim(preg_replace('/\s*\([^)]*\)/', '', $headerText));

            foreach ($this->assessments as $assessment) {
                if (strcasecmp($assessment->name, $clean) === 0) {
                    $this->assessmentColumnMap[$assessment->id] = $colIndex;
                    Log::info("[AdminImport] Mapped '{$assessment->name}' → col {$colIndex}");
                    break;
                }
            }
        }
    }

    // =========================================================================
    // Row processing
    // =========================================================================

    protected function processRow(array $rowValues, int $rowNumber): void
    {
        $admissionNo = trim((string) ($rowValues[$this->admissionColumnIndex] ?? ''));
        if ($admissionNo === '') {
            throw new \Exception("Admission number is required.");
        }

        Log::debug("[AdminImport] Row {$rowNumber}: {$admissionNo}");

        // Find student
        $student = Student::where('admissionNO', $admissionNo)
            ->orWhere('admissionno', $admissionNo)
            ->first();

        if (!$student) {
            throw new \Exception("Student '{$admissionNo}' not found in the database.");
        }

        // Find or create BroadsheetRecord
        $broadsheetRecord = BroadsheetRecord::firstOrCreate([
            'student_id'     => $student->id,
            'subject_id'     => $this->subjectId,
            'schoolclass_id' => $this->importData['schoolclass_id'],
            'session_id'     => $this->importData['session_id'],
        ]);

        // -----------------------------------------------------------------
        // KEY FIX: Look up the existing broadsheet WITHOUT staff_id.
        //
        // Previously firstOrNew() included staff_id as a lookup key, which
        // meant an admin importing with a different staff_id than the teacher
        // would create a DUPLICATE broadsheet row instead of updating the
        // existing one, so scores were written to an orphaned record.
        //
        // We now find the row by its natural unique keys (record + subjectclass
        // + term), then only fall back to creating a new row if none exists.
        // When creating, we use canonicalStaffId (the teacher who owns the
        // subjectclass) so the row is consistent with teacher-entered records.
        // -----------------------------------------------------------------
        $broadsheet = Broadsheets::where('broadSheet_record_id', $broadsheetRecord->id)
            ->where('subjectclass_id', $this->importData['subjectclass_id'])
            ->where('term_id', $this->importData['term_id'])
            ->first();

        $isNew = false;

        if (!$broadsheet) {
            $isNew      = true;
            $broadsheet = new Broadsheets();
            $broadsheet->broadSheet_record_id = $broadsheetRecord->id;
            $broadsheet->subjectclass_id      = $this->importData['subjectclass_id'];
            $broadsheet->term_id              = $this->importData['term_id'];
            // Use the canonical teacher staff_id, not the admin's user id
            $broadsheet->staff_id             = $this->canonicalStaffId ?? $this->importData['staff_id'];
            $broadsheet->entered_by           = auth()->id();
            $broadsheet->entered_at           = now();
            $broadsheet->entry_source         = 'admin_import';
            $broadsheet->save();

            Log::info("[AdminImport] Created new broadsheet id={$broadsheet->id} for {$admissionNo}");
        } else {
            Log::info("[AdminImport] Found existing broadsheet id={$broadsheet->id} for {$admissionNo} "
                . "(staff_id on record: {$broadsheet->staff_id})");
        }

        // --- Save assessment scores ---
        $totalScore = 0.0;

        foreach ($this->assessments as $assessment) {
            $colIndex = $this->assessmentColumnMap[$assessment->id] ?? null;
            $score    = 0.0;

            if ($colIndex !== null && isset($rowValues[$colIndex]) && $rowValues[$colIndex] !== '') {
                $score = (float) $rowValues[$colIndex];
            }

            $maxScore = (float) $assessment->max_score;

            if ($score > $maxScore) {
                throw new \Exception(
                    "{$assessment->name} score ({$score}) exceeds maximum ({$maxScore}) for '{$admissionNo}'."
                );
            }

            BroadsheetAssessmentScore::updateOrCreate(
                [
                    'broadsheet_id' => $broadsheet->id,
                    'assessment_id' => $assessment->id,
                ],
                ['score' => $score]
            );

            $totalScore += $score;
        }

        // Derived values
        $bf    = $this->getPreviousTermCum(
            $student->id,
            $this->subjectId,
            (int) $this->importData['term_id'],
            (int) $this->importData['session_id']
        );
        $cum   = ((int) $this->importData['term_id'] === 1 || $bf == 0)
            ? round($totalScore, 2)
            : round(($totalScore + $bf) / 2, 2);
        $grade  = $this->calculateGrade($totalScore);
        $remark = $this->getRemark($grade);

        $broadsheet->total            = round($totalScore, 2);
        $broadsheet->bf               = round($bf, 2);
        $broadsheet->cum              = $cum;
        $broadsheet->grade            = $grade;
        $broadsheet->remark           = $remark;
        $broadsheet->last_modified_by = auth()->id();
        $broadsheet->last_modified_at = now();
        $broadsheet->vettedstatus     = 0;

        // Preserve original entry metadata if the row already existed
        if ($isNew) {
            $broadsheet->entry_source = 'admin_import';
        } else {
            $broadsheet->entry_source = $broadsheet->entry_source ?? 'admin_import';
        }

        $broadsheet->save();

        $this->successCount++;

        if ($this->successCount % 10 === 0) {
            session(['import_progress' => min(60 + ($this->successCount / 100) * 30, 95)]);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function getPreviousTermCum(int $studentId, ?int $subjectId, int $termId, int $sessionId): float
    {
        if ($termId <= 1) {
            return 0.0;
        }
        $prev = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->where('broadsheets.term_id', $termId - 1)
            ->value('broadsheets.cum');

        return $prev !== null ? round((float) $prev, 2) : 0.0;
    }

    protected function calculateGrade(float $score): string
    {
        if ($this->schoolclass && $this->schoolclass->classcategories->isNotEmpty()) {
            $isSenior = $this->schoolclass->classcategories->first()->is_senior ?? false;

            if ($isSenior) {
                if ($score >= 75) return 'A1';
                if ($score >= 70) return 'B2';
                if ($score >= 65) return 'B3';
                if ($score >= 60) return 'C4';
                if ($score >= 55) return 'C5';
                if ($score >= 50) return 'C6';
                if ($score >= 45) return 'D7';
                if ($score >= 40) return 'E8';
                return 'F9';
            }

            if ($score >= 70) return 'A';
            if ($score >= 60) return 'B';
            if ($score >= 50) return 'C';
            if ($score >= 40) return 'D';
            return 'F';
        }

        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    protected function getRemark(string $grade): string
    {
        return match ($grade) {
            'A', 'A1'              => 'Excellent',
            'B', 'B2', 'B3'        => 'Very Good',
            'C', 'C4', 'C5', 'C6'  => 'Good',
            'D', 'D7', 'E8'        => 'Pass',
            default                => 'Fail',
        };
    }

    // =========================================================================
    // Public accessors
    // =========================================================================

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailures(): array
    {
        return $this->importFailures;
    }

    // =========================================================================
    // Validation (called before import in the controller)
    // =========================================================================

    public function validateExcelFile($file): bool
    {
        $rows = $this->toCollection($file);

        if ($rows->isEmpty()) {
            throw new \Exception('The Excel file is empty.');
        }

        Log::info('[AdminImport] Validation passed. Rows: ' . $rows->count());
        return true;
    }
}
