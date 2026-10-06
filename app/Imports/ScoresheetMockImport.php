<?php

namespace App\Imports;

use App\Models\BroadsheetsMock;
use App\Models\Subjectclass;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;

class ScoresheetMockImport implements ToModel, WithStartRow, WithUpsertColumns, WithUpserts, WithValidation
{
    use Importable;

    public function model(array $row)
    {
        try {
            $subjectclass_id = Session::get('subjectclass_id');
            $staff_id = Session::get('staff_id');
            $term_id = Session::get('term_id');
            $session_id = Session::get('session_id');
            $schoolclass_id = Session::get('schoolclass_id');

            Log::info('ScoresheetMockImport: Processing row', [
                'row' => array_slice($row, 0, 16),
                'session' => compact('subjectclass_id', 'staff_id', 'term_id', 'session_id', 'schoolclass_id'),
            ]);

            // Columns: A=SN, B=Admission No, C=Name, D=Exam, E=Total, F=Grade, G=Position, H=Remark, I=BroadsheetID, J=StaffID, K=SubjectclassID, L=TermID, M=SessionID
            $exam = is_numeric($row[3]) ? floatval($row[3]) : null;
            $broadsheet_id = isset($row[8]) && is_numeric($row[8]) ? intval($row[8]) : null;

            if ($broadsheet_id === null) {
                Log::warning('ScoresheetMockImport: Skipping row with missing broadsheet_id', ['row' => $row]);
                return null;
            }

            $total = $exam ?? 0;

            $broadsheet = BroadsheetsMock::updateOrCreate(
                [
                    'id' => $broadsheet_id,
                    'staff_id' => $staff_id,
                    'subjectclass_id' => $subjectclass_id,
                    'term_id' => $term_id,
                ],
                [
                    'exam' => $exam,
                    'total' => $total,
                    'grade' => $this->grade($total),
                    'remark' => $this->remark($total),
                ]
            );

            $this->subjectscoresheetpos($schoolclass_id, $subjectclass_id, $staff_id, $term_id, $session_id);

            return $broadsheet;
        } catch (\Exception $e) {
            Log::error('ScoresheetMockImport: Error processing row', [
                'error' => $e->getMessage(),
                'row' => $row,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function grade($total)
    {
        if ($total >= 70) return 'A';
        if ($total >= 60) return 'B';
        if ($total >= 40) return 'C';
        if ($total >= 30) return 'D';
        return 'F';
    }

    public function remark($total)
    {
        if ($total >= 70) return 'EXCELLENT';
        if ($total >= 60) return 'VERY GOOD';
        if ($total >= 40) return 'GOOD';
        if ($total >= 30) return 'FAIRLY GOOD';
        return 'POOR';
    }

    public function rules(): array
    {
        $subjectclass_id = Session::get('subjectclass_id');

        return [
            '3' => function ($attribute, $value, $onFailure) use ($subjectclass_id) {
                $examscore = $this->getMaxScore($subjectclass_id, 'examscore');
                if (!is_numeric($value)) {
                    $onFailure('Exam score must be a number.');
                } elseif ($value > $examscore) {
                    $onFailure("Exam score exceeds maximum ($examscore).");
                } elseif (is_null($value) || $value === '') {
                    $onFailure('Exam score cannot be empty.');
                }
            },
            '8' => 'required|integer|exists:broadsheets_mock,id', // Broadsheet ID
        ];
    }

    protected function getMaxScore($subjectclass_id, $score_type)
    {
        return Subjectclass::where('subjectclass.id', $subjectclass_id)
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->value("classcategories.$score_type") ?? 0;
    }

    public function customValidationMessages()
    {
        return [
            '8.required' => 'Broadsheet ID is missing.',
            '8.integer' => 'Broadsheet ID must be an integer.',
            '8.exists' => 'Broadsheet ID does not exist in the database.',
        ];
    }

    public function customValidationAttributes()
    {
        return [
            '3' => 'Exam',
            '8' => 'Broadsheet ID',
        ];
    }

    public function startRow(): int
    {
        return 7;
    }

    public function uniqueBy()
    {
        return 'id';
    }

    public function upsertColumns()
    {
        return ['exam', 'total', 'grade', 'remark'];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            Log::error('ScoresheetMockImport: Validation failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
        throw new \Exception('Validation failed for one or more rows. Check the log for details.');
    }

    public function subjectscoresheetpos($schoolclass_id, $subjectclass_id, $staff_id, $term_id, $session_id)
    {
        try {
            BroadsheetsMock::where('subjectclass_id', $subjectclass_id)
                ->where('staff_id', $staff_id)
                ->where('term_id', $term_id)
                ->whereNull('broadsheet_record_id')
                ->delete();

            $broadsheets = BroadsheetsMock::where('subjectclass_id', $subjectclass_id)
                ->where('staff_id', $staff_id)
                ->where('term_id', $term_id)
                ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheets_mock.broadsheet_record_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records_mock.schoolclass_id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheets_mock.subjectclass_id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets_mock.term_id')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
                ->where('broadsheet_records_mock.session_id', $session_id)
                ->get([
                    'broadsheets_mock.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'subject.subject',
                    'subject.subject_code',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'schoolterm.term',
                    'schoolsession.session',
                    'subjectclass.id as subjectclid',
                    'broadsheets_mock.staff_id',
                    'broadsheets_mock.term_id',
                    'broadsheet_records_mock.session_id as sessionid',
                    'studentpicture.picture',
                    'broadsheets_mock.exam',
                    'broadsheets_mock.total',
                    'broadsheets_mock.grade',
                    'broadsheets_mock.subject_position_class as position',
                    'broadsheets_mock.remark',
                ])->sortBy('admissionno');

            if ($broadsheets->isEmpty()) {
                Log::warning('ScoresheetMockImport: No broadsheets found for position update', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
                return;
            }

            $classmin = $broadsheets->min('total');
            $classmax = $broadsheets->max('total');
            $classavg = ($classmin + $classmax) / 2;

            BroadsheetsMock::where('subjectclass_id', $subjectclass_id)
                ->where('staff_id', $staff_id)
                ->where('term_id', $term_id)
                ->update([
                    'cmin' => $classmin ?? 0,
                    'cmax' => $classmax ?? 0,
                    'avg' => round($classavg, 1),
                ]);

            $rank = 0;
            $last_score = null;
            $rows = 0;

            $classpos = BroadsheetsMock::where('subjectclass_id', $subjectclass_id)
                ->where('staff_id', $staff_id)
                ->where('term_id', $term_id)
                ->orderBy('total', 'DESC')
                ->get();

            foreach ($classpos as $row) {
                $rows++;
                if ($last_score !== $row->total) {
                    $last_score = $row->total;
                    $rank = $rows;
                }
                $position = match ($rank) {
                    1 => 'st',
                    2 => 'nd',
                    3 => 'rd',
                    default => 'th',
                };
                $rank_pos = $rank . $position;

                BroadsheetsMock::where('id', $row->id)->update(['subject_position_class' => $rank_pos]);
            }

            $rank2 = 0;
            $last_score2 = null;
            $rows2 = 0;

            $pos = \App\Models\PromotionStatus::where('schoolclassid', $schoolclass_id)
                ->where('termid', $term_id)
                ->where('sessionid', $session_id)
                ->orderBy('subjectstotalscores', 'DESC')
                ->get();

            foreach ($pos as $row2) {
                $rows2++;
                if ($last_score2 !== $row2->subjectstotalscores) {
                    $last_score2 = $row2->subjectstotalscores;
                    $rank2 = $rows2;
                }
                $position2 = match ($rank2) {
                    1 => 'st',
                    2 => 'nd',
                    3 => 'rd',
                    default => 'th',
                };
                $rank_pos2 = $rank2 . $position2;

                \App\Models\PromotionStatus::where('studentId', $row2->studentId)
                    ->where('schoolclassid', $schoolclass_id)
                    ->where('termid', $term_id)
                    ->where('sessionid', $session_id)
                    ->update(['position' => $rank_pos2]);
            }

            Session::put('subjectclass_id', $subjectclass_id);
            Session::put('staff_id', $staff_id);
            Session::put('term_id', $term_id);
            Session::put('session_id', $session_id);
            Session::put('schoolclass_id', $schoolclass_id);
        } catch (\Exception $e) {
            Log::error('ScoresheetMockImport: Error in subjectscoresheetpos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}