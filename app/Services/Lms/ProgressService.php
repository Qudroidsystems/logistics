<?php

namespace App\Services\Lms;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LmsCourse;
use App\Models\LmsEnrollment;
use App\Models\LmsLesson;
use App\Models\LmsLessonProgress;
use App\Models\LmsQuiz;
use App\Models\LmsAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tracks per-student lesson completion, keeps the enrolment progress bar in sync,
 * marks a course complete at 100%, drafts a completion certificate when one is
 * configured, and builds the teacher gradebook.
 */
class ProgressService
{
    /** Mark (or unmark) a lesson complete for a student and recompute progress. */
    public function markLesson(LmsCourse $course, LmsLesson $lesson, int $studentId, bool $completed = true): void
    {
        LmsLessonProgress::updateOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $studentId],
            [
                'course_id'    => $course->id,
                'completed'    => $completed,
                'completed_at' => $completed ? now() : null,
            ]
        );
        $this->recompute($course, $studentId);
    }

    /** Recompute the enrolment progress percentage and completion state. */
    public function recompute(LmsCourse $course, int $studentId): int
    {
        $total = (int) $course->lessons()->where('is_published', true)->count();
        $done  = (int) LmsLessonProgress::where('course_id', $course->id)
            ->where('student_id', $studentId)->where('completed', true)
            ->whereIn('lesson_id', $course->lessons()->where('is_published', true)->pluck('id'))
            ->count();

        $percent = $total > 0 ? (int) round($done / $total * 100) : 0;

        $enrol = LmsEnrollment::where('course_id', $course->id)->where('student_id', $studentId)->first();
        if (!$enrol) return $percent;

        $enrol->progress_percent = $percent;
        if ($percent >= 100 && $enrol->status !== 'completed') {
            $enrol->status = 'completed';
            $enrol->completed_at = now();
            $enrol->save();
            $this->issueCompletionCertificate($course, $studentId);
        } elseif ($percent < 100 && $enrol->status === 'completed') {
            // reopened
            $enrol->status = 'active';
            $enrol->completed_at = null;
            $enrol->save();
        } else {
            $enrol->save();
        }

        return $percent;
    }

    /**
     * Draft a completion certificate through the existing certificate module so
     * it flows through the same approval / audit / QR pipeline. Idempotent: the
     * certificates table is unique on (template_id, student_id).
     */
    protected function issueCompletionCertificate(LmsCourse $course, int $studentId): void
    {
        $templateId = $course->completion_cert_template_id;
        if (!$templateId) return;

        try {
            $template = CertificateTemplate::find($templateId);
            if (!$template) return;

            $exists = Certificate::where('template_id', $templateId)->where('student_id', $studentId)->exists();
            if ($exists) return;

            Certificate::create([
                'template_id' => $templateId,
                'student_id'  => $studentId,
                'serial'      => $this->nextSerial($template),
                'verify_token'=> $this->uniqueToken(),
                'title'       => 'Course Completion — ' . $course->title,
                'status'      => 'draft',
                'class_id'    => $course->schoolclass_id,
                'term_id'     => $course->term_id,
                'session_id'  => $course->session_id,
            ]);
        } catch (\Throwable $e) {
            // never let certificate drafting block course completion
        }
    }

    protected function nextSerial(CertificateTemplate $template): string
    {
        $prefix = $template->serial_prefix ?: 'CERT';
        $year = date('Y');
        do {
            $n = Certificate::where('serial', 'like', "{$prefix}/{$year}/%")->count() + 1;
            $serial = sprintf('%s/%s/%05d', $prefix, $year, $n);
            if (Certificate::where('serial', $serial)->exists()) {
                $serial = sprintf('%s/%s/%05d-%s', $prefix, $year, $n, Str::upper(Str::random(3)));
            }
        } while (Certificate::where('serial', $serial)->exists());
        return $serial;
    }

    protected function uniqueToken(): string
    {
        do { $t = Str::random(48); } while (Certificate::where('verify_token', $t)->exists());
        return $t;
    }

    /**
     * Teacher gradebook for a course: per enrolled student — progress, quiz
     * average (best attempt per quiz) and assignment average (graded only).
     *
     * @return array<int, array{student_id:int,name:string,admissionNo:?string,progress:int,quiz_avg:?float,assignment_avg:?float,status:string}>
     */
    public function gradebook(LmsCourse $course): array
    {
        $enrols = LmsEnrollment::where('course_id', $course->id)->get();
        if ($enrols->isEmpty()) return [];

        $studentIds = $enrols->pluck('student_id')->all();

        $students = DB::table('studentRegistration')->whereIn('id', $studentIds)
            ->get(['id', 'firstname', 'lastname', 'admissionNo'])->keyBy('id');

        $quizIds = $course->quizzes()->pluck('id')->all();
        $assignmentIds = $course->assignments()->pluck('id')->all();

        // Best quiz percent per student per quiz → averaged.
        $quizAvg = [];
        if ($quizIds) {
            $best = DB::table('lms_quiz_attempts')
                ->whereIn('quiz_id', $quizIds)->whereIn('student_id', $studentIds)
                ->select('student_id', 'quiz_id', DB::raw('MAX(percent) as best'))
                ->groupBy('student_id', 'quiz_id')->get();
            foreach ($best->groupBy('student_id') as $sid => $rows) {
                $quizAvg[(int) $sid] = round($rows->avg('best'), 1);
            }
        }

        // Graded assignment score as percent of max_score → averaged.
        $assignAvg = [];
        if ($assignmentIds) {
            $subs = DB::table('lms_assignment_submissions as s')
                ->join('lms_assignments as a', 'a.id', '=', 's.assignment_id')
                ->whereIn('s.assignment_id', $assignmentIds)
                ->whereIn('s.student_id', $studentIds)
                ->whereNotNull('s.score')
                ->select('s.student_id', DB::raw('s.score as score'), DB::raw('a.max_score as max_score'))
                ->get();
            foreach ($subs->groupBy('student_id') as $sid => $rows) {
                $pcts = $rows->map(fn ($r) => $r->max_score > 0 ? ($r->score / $r->max_score * 100) : 0);
                $assignAvg[(int) $sid] = round($pcts->avg(), 1);
            }
        }

        $weights = $course->gradeWeights(); // ['quiz'=>x,'assignment'=>y] summing to 100

        $out = [];
        foreach ($enrols as $e) {
            $sid = (int) $e->student_id;
            $s = $students[$sid] ?? null;
            $q = $quizAvg[$sid] ?? null;
            $a = $assignAvg[$sid] ?? null;
            $out[] = [
                'student_id'     => $sid,
                'name'           => $s ? trim(($s->firstname ?? '') . ' ' . ($s->lastname ?? '')) : ('Student #' . $sid),
                'admissionNo'    => $s->admissionNo ?? null,
                'progress'       => (int) $e->progress_percent,
                'quiz_avg'       => $q,
                'assignment_avg' => $a,
                'overall'        => $this->weightedOverall($q, $a, $weights),
                'status'         => $e->status,
            ];
        }
        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $out;
    }

    /**
     * Weighted overall grade. When only one component has data, it takes the
     * full weight; when neither does, returns null.
     */
    protected function weightedOverall(?float $quiz, ?float $assign, array $weights): ?float
    {
        $qw = $weights['quiz'] ?? 50;
        $aw = $weights['assignment'] ?? 50;
        $parts = [];
        if ($quiz !== null)   $parts[] = ['v' => $quiz,   'w' => $qw];
        if ($assign !== null) $parts[] = ['v' => $assign, 'w' => $aw];
        if (!$parts) return null;
        $wsum = array_sum(array_column($parts, 'w'));
        if ($wsum <= 0) return null;
        $total = 0.0;
        foreach ($parts as $p) $total += $p['v'] * $p['w'];
        return round($total / $wsum, 1);
    }
}
