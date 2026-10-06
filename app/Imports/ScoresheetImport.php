<?php

namespace App\Imports;

use App\Models\Assessment;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetRecord;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\Subjectclass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;

class ScoresheetImport implements ToCollection, SkipsOnFailure
{
    use Importable, SkipsFailures;

    protected $importData;
    protected $successCount = 0;
    protected $failures = [];
    protected $assessments = [];
    protected $schoolclass = null;
    protected $subjectId = null;
    protected $admissionColumnIndex = -1;
    protected $assessmentColumnMap = [];
    protected $headerRowIndex = -1;
    protected $dataStartRow = -1;

    public function __construct(array $importData)
    {
        $this->importData = $importData;

        try {
            // Load assessments for this class
            if (!empty($importData['schoolclass_id'])) {
                $this->schoolclass = Schoolclass::with('classcategories')->find($importData['schoolclass_id']);
                if ($this->schoolclass && $this->schoolclass->classcategories->isNotEmpty()) {
                    $categoryIds = $this->schoolclass->classcategories->pluck('id');
                    $this->assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                        ->orderBy('id')
                        ->get();

                    Log::info('Loaded assessments: ' . json_encode($this->assessments->pluck('name')->toArray()));
                }
            }

            // Get subject ID
            if (!empty($importData['subjectclass_id'])) {
                $subjectClass = Subjectclass::find($this->importData['subjectclass_id']);
                $this->subjectId = $subjectClass ? $subjectClass->subjectid : null;
            }
        } catch (\Exception $e) {
            Log::error('ScoresheetImport constructor error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process the Excel collection
     */
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->failures[] = [
                'row' => 0,
                'errors' => ['The Excel file is empty.']
            ];
            return;
        }

        Log::info('Total rows in file: ' . $rows->count());

        // First, find the header row (contains "Admission No" or similar)
        $this->findHeaderRow($rows);

        if ($this->headerRowIndex === -1) {
            $this->failures[] = [
                'row' => 0,
                'errors' => ['Could not find header row with "Admission No". Please ensure your file has a column named "Admission No".']
            ];
            return;
        }

        Log::info("Header row found at index: {$this->headerRowIndex}");
        Log::info("Admission column index: {$this->admissionColumnIndex}");
        Log::info("Assessment mapping: " . json_encode($this->assessmentColumnMap));

        // Process data rows (rows after header)
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                // Skip rows before header
                if ($index <= $this->headerRowIndex) {
                    continue;
                }

                $rowArray = is_array($row) ? $row : $row->toArray();
                $rowValues = array_values($rowArray);

                // Skip empty rows
                $isEmpty = true;
                foreach ($rowValues as $value) {
                    if (!empty($value) && $value !== null && $value !== '') {
                        $isEmpty = false;
                        break;
                    }
                }

                if ($isEmpty) {
                    continue;
                }

                // Check if admission number exists
                if ($this->admissionColumnIndex === -1 ||
                    !isset($rowValues[$this->admissionColumnIndex]) ||
                    empty($rowValues[$this->admissionColumnIndex])) {
                    Log::warning("Skipping row with no admission number at row " . ($index + 1));
                    continue;
                }

                try {
                    $this->processRow($rowValues, $index + 1);
                } catch (\Exception $e) {
                    $this->failures[] = [
                        'row' => $index + 1,
                        'errors' => [$e->getMessage()]
                    ];
                    Log::error("Import row error at row " . ($index + 1) . ": " . $e->getMessage());
                }
            }

            DB::commit();

            Log::info("Import completed. Success: {$this->successCount}, Failures: " . count($this->failures));

            session(['import_progress' => 100, 'import_status' => 'completed', 'import_message' => 'Import completed!']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import transaction failed: ' . $e->getMessage());
            $this->failures[] = [
                'row' => 0,
                'errors' => ['Transaction failed: ' . $e->getMessage()]
            ];
            session(['import_progress' => 0, 'import_status' => 'error', 'import_message' => $e->getMessage()]);
        }
    }

    /**
     * Find the header row and build column mappings
     */
    protected function findHeaderRow(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowArray = is_array($row) ? $row : $row->toArray();
            $rowValues = array_values($rowArray);

            Log::info("Scanning row " . ($index + 1) . ": " . json_encode(array_slice($rowValues, 0, 10)));

            // Look for admission number column header
            foreach ($rowValues as $colIndex => $value) {
                if (is_string($value)) {
                    $valueLower = strtolower(trim($value));
                    if (strpos($valueLower, 'admission') !== false ||
                        strpos($valueLower, 'adm no') !== false ||
                        strpos($valueLower, 'student id') !== false) {

                        $this->headerRowIndex = $index;
                        $this->admissionColumnIndex = $colIndex;

                        Log::info("Found header at row " . ($index + 1) . ", column " . $colIndex . ": " . $value);

                        // Now build assessment column mapping from this row
                        $this->buildAssessmentColumnMap($rowValues);

                        return;
                    }
                }
            }
        }
    }

    /**
     * Build assessment column mapping from header row
     */
    protected function buildAssessmentColumnMap($headerValues)
    {
        foreach ($headerValues as $colIndex => $headerText) {
            // Skip the admission column
            if ($colIndex == $this->admissionColumnIndex) {
                continue;
            }

            if (empty($headerText) || !is_string($headerText)) {
                continue;
            }

            // Clean up header text (remove max score in parentheses)
            $cleanHeader = trim($headerText);
            $cleanHeader = preg_replace('/\s*\([^)]*\)/', '', $cleanHeader);
            $cleanHeader = trim($cleanHeader);

            // Look for matching assessment
            foreach ($this->assessments as $assessment) {
                if (strcasecmp($assessment->name, $cleanHeader) === 0) {
                    $this->assessmentColumnMap[$assessment->id] = $colIndex;
                    Log::info("Mapped '{$assessment->name}' to column {$colIndex} (Header: '{$headerText}')");
                    break;
                }
            }
        }
    }

    /**
     * Process a single row
     */
    protected function processRow($rowValues, $rowNumber)
    {
        // Get admission number
        $admissionNo = isset($rowValues[$this->admissionColumnIndex])
            ? trim($rowValues[$this->admissionColumnIndex])
            : null;

        if (!$admissionNo) {
            throw new \Exception("Admission number is required.");
        }

        Log::info("Processing: {$admissionNo} at row {$rowNumber}");

        // Find student
        $student = Student::where('admissionNO', $admissionNo)
            ->orWhere('admissionno', $admissionNo)
            ->first();

        if (!$student) {
            throw new \Exception("Student '{$admissionNo}' not found.");
        }

        // Find or create broadsheet record
        $broadsheetRecord = BroadsheetRecord::firstOrCreate(
            [
                'student_id' => $student->id,
                'subject_id' => $this->subjectId,
                'schoolclass_id' => $this->importData['schoolclass_id'],
                'session_id' => $this->importData['session_id'],
            ],
            [
                'term_id' => $this->importData['term_id'],
                'teacher_id' => $this->importData['staff_id'],
            ]
        );

        // Find or create broadsheet
        $broadsheet = Broadsheets::firstOrNew([
            'broadSheet_record_id' => $broadsheetRecord->id,
            'subjectclass_id' => $this->importData['subjectclass_id'],
            'staff_id' => $this->importData['staff_id'],
            'term_id' => $this->importData['term_id'],
        ]);

        // Process assessment scores
        $totalScore = 0;

        foreach ($this->assessments as $assessment) {
            $score = 0;
            $columnIndex = $this->assessmentColumnMap[$assessment->id] ?? null;

            if ($columnIndex !== null && isset($rowValues[$columnIndex])) {
                $scoreValue = $rowValues[$columnIndex];
                if ($scoreValue !== null && $scoreValue !== '') {
                    $score = floatval($scoreValue);
                }
            }

            $maxScore = floatval($assessment->max_score);

            if ($score > $maxScore) {
                throw new \Exception("{$assessment->name}: {$score} > {$maxScore} for {$admissionNo}");
            }

            // Save assessment score
            BroadsheetAssessmentScore::updateOrCreate(
                [
                    'broadsheet_id' => $broadsheet->id ?? 0,
                    'assessment_id' => $assessment->id,
                ],
                ['score' => $score]
            );

            $totalScore += $score;
        }

        // Calculate totals
        $bf = $this->getPreviousTermCum($student->id, $this->subjectId, $this->importData['term_id'], $this->importData['session_id']);
        $cum = $this->importData['term_id'] == 1 ? round($totalScore, 2) : round(($totalScore + $bf) / 2, 2);
        $grade = $this->calculateGrade($cum);
        $remark = $this->getRemark($grade);

        // Save broadsheet
        $broadsheet->fill([
            'total' => round($totalScore, 2),
            'bf' => round($bf, 2),
            'cum' => $cum,
            'grade' => $grade,
            'remark' => $remark,
            'vettedstatus' => 0,
        ]);

        $broadsheet->save();

        $this->successCount++;

        if ($this->successCount % 10 == 0) {
            session(['import_progress' => min(60 + ($this->successCount / 100) * 30, 95)]);
        }
    }

    /**
     * Get previous term cumulative score
     */
    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) return 0;

        try {
            $prev = Broadsheets::where('broadsheet_records.student_id', $studentId)
                ->where('broadsheet_records.subject_id', $subjectId)
                ->where('broadsheets.term_id', $termId - 1)
                ->where('broadsheet_records.session_id', $sessionId)
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->value('broadsheets.cum');

            return $prev ? round(floatval($prev), 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate grade
     */
    protected function calculateGrade($score)
    {
        try {
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
                } else {
                    if ($score >= 70) return 'A';
                    if ($score >= 60) return 'B';
                    if ($score >= 50) return 'C';
                    if ($score >= 40) return 'D';
                    return 'F';
                }
            }
        } catch (\Exception $e) {
            // Fall through to default
        }

        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    /**
     * Get remark
     */
    protected function getRemark($grade)
    {
        $remarks = [
            'A' => 'Excellent', 'A1' => 'Excellent',
            'B' => 'Very Good', 'B2' => 'Very Good', 'B3' => 'Very Good',
            'C' => 'Good', 'C4' => 'Good', 'C5' => 'Good', 'C6' => 'Good',
            'D' => 'Pass', 'D7' => 'Pass', 'E8' => 'Pass',
            'F' => 'Fail', 'F9' => 'Fail',
        ];
        return $remarks[$grade] ?? 'Pass';
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * Validate Excel file
     */
    public function validateExcelFile($file)
    {
        try {
            $rows = $this->toCollection($file);

            if ($rows->isEmpty()) {
                throw new \Exception('The Excel file is empty.');
            }

            // Just check if there's any data
            Log::info('Excel validation passed. Total rows: ' . $rows->count());
            return true;

        } catch (\Exception $e) {
            Log::error('Excel validation failed: ' . $e->getMessage());
            throw new \Exception('Excel validation failed: ' . $e->getMessage());
        }
    }

    public function validateExcelMetadata($filePath)
    {
        return $this->validateExcelFile($filePath);
    }
}
