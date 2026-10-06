<?php

namespace App\Services\Lms;

use App\Models\LmsCourse;
use App\Models\LmsEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Handles the two enrolment modes for LMS courses:
 *   - auto:   every student in the course's class (+ session/term) is enrolled
 *   - manual: admin/teacher adds or removes individual students
 * A course set to "both" supports both at once. Auto-enrolment is additive and
 * idempotent — it never removes a manual enrolment.
 */
class EnrollmentService
{
    /** Current session id, from schoolsession.status = 'Current'. */
    public static function currentSessionId(): ?int
    {
        try { return (int) (DB::table('schoolsession')->where('status', 'Current')->value('id') ?: 0) ?: null; }
        catch (\Throwable $e) { return null; }
    }

    /** Current term id, from schoolterm.status truthy. */
    public static function currentTermId(): ?int
    {
        try { return (int) (DB::table('schoolterm')->where('status', 1)->value('id') ?: 0) ?: null; }
        catch (\Throwable $e) { return null; }
    }

    /**
     * Student ids that belong to the course's class for its session (and term
     * when set). Falls back to the current session when the course has none.
     */
    public function eligibleStudentIds(LmsCourse $course): array
    {
        if (!$course->schoolclass_id || !Schema::hasTable('studentclass')) return [];

        $sessionId = $course->session_id ?: self::currentSessionId();

        $q = DB::table('studentclass')->where('schoolclassid', $course->schoolclass_id);
        if ($sessionId)          $q->where('sessionid', $sessionId);
        if ($course->term_id)    $q->where('termid', $course->term_id);

        return $q->pluck('studentId')->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
    }

    /**
     * Student ids registered for the course's SUBJECT this session/term, via the
     * existing subject-registration tables (subjectclass → subjectRegistrationStatus).
     */
    public function eligibleBySubject(LmsCourse $course): array
    {
        if (!$course->subject_id || !Schema::hasTable('subjectclass') || !Schema::hasTable('subjectRegistrationStatus')) {
            return [];
        }
        $sessionId = $course->session_id ?: self::currentSessionId();

        $sc = DB::table('subjectclass')->where('subjectid', $course->subject_id);
        if ($course->schoolclass_id) $sc->where('schoolclassid', $course->schoolclass_id);
        if ($sessionId)              $sc->where('sessionid', $sessionId);
        if ($course->term_id)        $sc->where('termid', $course->term_id);
        $scIds = $sc->pluck('id')->all();
        if (!$scIds) return [];

        $q = DB::table('subjectRegistrationStatus')->whereIn('subjectclassid', $scIds);
        if ($sessionId)       $q->where('sessionid', $sessionId);
        if ($course->term_id) $q->where('termid', $course->term_id);

        return $q->pluck('studentid')->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
    }

    /** Enrol the whole eligible class. Returns the number newly enrolled. */
    public function syncAuto(LmsCourse $course): int
    {
        return $this->insertAuto($course, $this->eligibleStudentIds($course));
    }

    /** Enrol everyone registered for the course's subject. */
    public function syncBySubject(LmsCourse $course): int
    {
        return $this->insertAuto($course, $this->eligibleBySubject($course));
    }

    /** Bulk-insert the given student ids as auto enrolments, skipping existing. */
    protected function insertAuto(LmsCourse $course, array $ids): int
    {
        if (!$ids) return 0;

        $existing = LmsEnrollment::where('course_id', $course->id)
            ->whereIn('student_id', $ids)->pluck('student_id')->map(fn ($v) => (int) $v)->all();

        $new = array_values(array_diff($ids, $existing));
        $now = now();
        $rows = array_map(fn ($sid) => [
            'course_id'   => $course->id,
            'student_id'  => $sid,
            'source'      => 'auto',
            'status'      => 'active',
            'enrolled_at' => $now,
            'created_at'  => $now,
            'updated_at'  => $now,
        ], $new);

        foreach (array_chunk($rows, 500) as $chunk) {
            LmsEnrollment::insert($chunk);
        }
        return count($new);
    }

    /** True if the student has any outstanding school-fee balance for the session/term. */
    public static function owesFees(int $studentId, ?int $sessionId = null, ?int $termId = null): bool
    {
        if (!$studentId || !Schema::hasTable('student_bill_payment_book')) return false;
        $sessionId = $sessionId ?: self::currentSessionId();

        $q = DB::table('student_bill_payment_book')
            ->where('student_id', $studentId)->where('amount_owed', '>', 0);
        if ($sessionId) $q->where('session_id', $sessionId);
        if ($termId)    $q->where('term_id', $termId);

        return $q->exists();
    }

    /** Manually enrol specific students. Returns number newly enrolled. */
    public function enroll(LmsCourse $course, array $studentIds, ?int $byUserId = null): int
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if (!$studentIds) return 0;

        $existing = LmsEnrollment::where('course_id', $course->id)
            ->whereIn('student_id', $studentIds)->pluck('student_id')->map(fn ($v) => (int) $v)->all();
        $new = array_values(array_diff($studentIds, $existing));

        $now = now();
        foreach (array_chunk($new, 500) as $chunk) {
            LmsEnrollment::insert(array_map(fn ($sid) => [
                'course_id'   => $course->id,
                'student_id'  => $sid,
                'source'      => 'manual',
                'status'      => 'active',
                'enrolled_by' => $byUserId,
                'enrolled_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], $chunk));
        }
        return count($new);
    }

    /** Remove enrolments (and their progress) for specific students. */
    public function unenroll(LmsCourse $course, array $studentIds): int
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if (!$studentIds) return 0;

        DB::table('lms_lesson_progress')->where('course_id', $course->id)
            ->whereIn('student_id', $studentIds)->delete();

        return LmsEnrollment::where('course_id', $course->id)
            ->whereIn('student_id', $studentIds)->delete();
    }

    /** Student self-enrolment (when the course allows it). */
    public function selfEnroll(LmsCourse $course, int $studentId): bool
    {
        if (!$course->allow_self_enroll || !$studentId) return false;
        if (LmsEnrollment::where('course_id', $course->id)->where('student_id', $studentId)->exists()) return true;

        LmsEnrollment::create([
            'course_id'   => $course->id,
            'student_id'  => $studentId,
            'source'      => 'manual',
            'status'      => 'active',
            'enrolled_at' => now(),
        ]);
        return true;
    }
}
