<?php

namespace App\Services;

use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Studentclass;

/**
 * Loads student result rows for many classes at once, using EXACTLY the same
 * maths as BroadsheetController::buildBroadsheetData() + assembleStudentRows():
 *
 *   BF      = previous term's cum (same session) → else stored bf → else 0
 *   Cum     = round(BF + Total, 2)
 *   Cum Ave = round(Cum / term_id, 2)
 *   Grade   = class category calculateGrade() on Total / Cum Ave
 *   Student cum_ave = average of subject Cum Aves > 0 (1 dp)
 *
 * Rows have the same shape as the broadsheet's $studentRows, so they can be
 * passed straight to BestStudentsService. A student is matched to a class the
 * same way the single-class broadsheet does it: studentclass row for the
 * session + broadsheet_records.schoolclass_id = that class.
 *
 * Bulk: ~5 queries in total regardless of how many classes are loaded.
 * If you change the maths in BroadsheetController, change it here too.
 */
class ClassResultsLoader
{
    public function load(int $termId, int $sessionId, ?array $classIds = null, string $basis = 'cum_ave'): array
    {
        $classes = Schoolclass::with('classcategories')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->when($classIds, fn ($q) => $q->whereIn('schoolclass.id', $classIds))
            ->get()
            ->keyBy('id');

        if ($classes->isEmpty()) {
            return ['rows' => [], 'subjects' => [], 'classes' => $classes];
        }

        // ── Enrolment (student ↔ class for the session) ─────────────
        $enrolment = Studentclass::whereIn('studentclass.schoolclassid', $classes->keys())
            ->where('studentclass.sessionid', $sessionId)
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'studentclass.schoolclassid',
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.gender',
                'studentpicture.picture',
            ])
            ->get();

        $pairs = [];        // "sid:cid" => student info
        $studentIds = [];
        foreach ($enrolment as $e) {
            $key = (int) $e->id . ':' . (int) $e->schoolclassid;
            if (!isset($pairs[$key])) {
                $pairs[$key]   = $e;
                $studentIds[]  = (int) $e->id;
            }
        }
        $studentIds = array_values(array_unique($studentIds));

        if (empty($studentIds)) {
            return ['rows' => [], 'subjects' => [], 'classes' => $classes];
        }

        $prevCumMap = $this->previousTermCums($studentIds, $sessionId, $termId);

        // ── Current-term broadsheets for every class at once ────────
        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $termId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->whereIn('broadsheet_records.schoolclass_id', $classes->keys())
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->select([
                'broadsheet_records.student_id',
                'broadsheet_records.subject_id',
                'broadsheet_records.schoolclass_id',
                'subject.subject as subject_name',
                'subject.subject_code',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.grade',
            ])
            ->get();

        $subjectsMap = [];
        $scores      = [];   // "sid:cid" => [subject_id => subject data]

        foreach ($broadsheets as $row) {
            $sid = (int) $row->student_id;
            $cid = (int) $row->schoolclass_id;
            $sub = (int) $row->subject_id;
            $key = $sid . ':' . $cid;

            // Same scoping as the single-class broadsheet: the record must
            // belong to the class the student is enrolled in this session.
            if (!isset($pairs[$key])) continue;

            $subjectsMap[$sub] = $subjectsMap[$sub] ?? [
                'subject_id'   => $sub,
                'subject_name' => $row->subject_name,
                'subject_code' => $row->subject_code ?? '',
            ];

            $gradeCategory = optional($classes->get($cid))->classcategories?->first();

            $rawTotal = (float) ($row->total ?? 0);

            $prevCum = $prevCumMap[$sid][$sub] ?? null;
            if ($prevCum !== null && $prevCum > 0) {
                $bf = $prevCum;
            } elseif (!empty($row->bf) && (float) $row->bf > 0) {
                $bf = (float) $row->bf;
            } else {
                $bf = 0.0;
            }

            $cum    = round($bf + $rawTotal, 2);
            $cumAve = $termId > 0 ? round($cum / $termId, 2) : $cum;

            $totalGrade = $row->grade ?? ($gradeCategory ? $gradeCategory->calculateGrade($rawTotal) : '-');
            $cumGrade   = $gradeCategory ? $gradeCategory->calculateGrade($cumAve) : $totalGrade;

            $scores[$key][$sub] = [
                'total'       => $rawTotal,
                'bf'          => $bf,
                'cum'         => $cum,
                'cum_ave'     => $cumAve,
                'grade'       => $basis === 'total' ? $totalGrade : $cumGrade,
                'total_grade' => $totalGrade,
                'cum_grade'   => $cumGrade,
            ];
        }

        // ── Assemble rows (same aggregates as assembleStudentRows) ──
        $rows = [];
        foreach ($pairs as $key => $stu) {
            $cid       = (int) $stu->schoolclassid;
            $class     = $classes->get($cid);
            $subScores = $scores[$key] ?? [];

            $cumValues = []; $totalValues = []; $gradePoints = [];
            foreach ($subScores as $sd) {
                if ($sd['cum_ave'] > 0) $cumValues[]   = $sd['cum_ave'];
                if ($sd['total'] > 0)   $totalValues[] = $sd['total'];
                $gpaSource = $basis === 'total' ? $sd['total'] : $sd['cum_ave'];
                if ($gpaSource > 0) $gradePoints[] = $this->gradePoint($gpaSource);
            }

            $numSubjects = count($cumValues);
            $totalCum    = array_sum($cumValues);
            $totalTerm   = array_sum($totalValues);
            $className   = (string) ($class->schoolclass ?? '');
            $armName     = (string) ($class->arm_name ?? '');

            $rows[] = [
                'id'            => (int) $stu->id,
                'admissionno'   => $stu->admissionno,
                'firstname'     => $stu->firstname,
                'lastname'      => $stu->lastname,
                'gender'        => $stu->gender,
                'picture'       => $stu->picture,
                'arm'           => $armName,
                'class_name'    => $className,
                'class_label'   => trim($className . ' ' . $armName),
                'schoolclassid' => $cid,
                'is_senior'     => (bool) optional(optional($class)->classcategories?->first())->is_senior,
                'subjects'      => $subScores,
                'total_cum'     => round($totalCum, 1),
                'total_term'    => round($totalTerm, 1),
                'cum_ave'       => $numSubjects > 0 ? round($totalCum / $numSubjects, 1) : 0,
                'term_ave'      => count($totalValues) > 0 ? round($totalTerm / count($totalValues), 1) : 0,
                'num_subjects'  => $numSubjects,
                'gpa'           => count($gradePoints) > 0 ? round(array_sum($gradePoints) / count($gradePoints), 2) : 0.0,
            ];
        }

        return ['rows' => $rows, 'subjects' => $subjectsMap, 'classes' => $classes];
    }

    /** Same lookup as BroadsheetController::fetchPreviousTermCums (student + session scope). */
    private function previousTermCums(array $studentIds, int $sessionId, int $termId): array
    {
        $prevTerm = Schoolterm::where('id', '<', $termId)->orderByDesc('id')->first();
        if (!$prevTerm) return [];

        $rows = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $prevTerm->id)
            ->where('broadsheet_records.session_id', $sessionId)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->select(['broadsheet_records.student_id', 'broadsheet_records.subject_id', 'broadsheets.cum'])
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->student_id][(int) $r->subject_id] = (float) $r->cum;
        }
        return $map;
    }

    /** Same scale as BroadsheetController::getGradePoint. */
    private function gradePoint(float $score): float
    {
        if ($score >= 75) return 5.0;
        if ($score >= 70) return 4.5;
        if ($score >= 65) return 4.0;
        if ($score >= 60) return 3.5;
        if ($score >= 55) return 3.0;
        if ($score >= 50) return 2.5;
        if ($score >= 45) return 2.0;
        if ($score >= 40) return 1.0;
        return 0.0;
    }
}
