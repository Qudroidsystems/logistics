<?php

namespace App\Http\Controllers\Lms\Concerns;

use App\Models\LmsCourse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Shared authorisation & context helpers for the LMS controllers.
 */
trait InteractsWithLms
{
    protected function me()
    {
        return Auth::user();
    }

    /** studentRegistration.id for the logged-in student, or null. */
    protected function currentStudentId(): ?int
    {
        $u = Auth::user();
        return $u && $u->student_id ? (int) $u->student_id : null;
    }

    /** Full management (course + content + enrolment) rights over a course. */
    protected function canManage(LmsCourse $course): bool
    {
        $u = Auth::user();
        if (!$u) return false;
        if ($u->can('Manage courses')) return true;
        return (int) $course->teacher_id === (int) $u->id && $u->can('Grade coursework');
    }

    /** Grading + gradebook rights over a course. */
    protected function canGrade(LmsCourse $course): bool
    {
        $u = Auth::user();
        if (!$u) return false;
        if ($u->can('Manage courses') || $u->can('Grade coursework')) {
            if ($u->can('Manage courses')) return true;
            return (int) $course->teacher_id === (int) $u->id;
        }
        return false;
    }

    protected function authorizeManage(LmsCourse $course): void
    {
        if (!$this->canManage($course)) {
            throw new HttpException(403, 'You do not have permission to manage this course.');
        }
    }

    protected function authorizeGrade(LmsCourse $course): void
    {
        if (!$this->canGrade($course)) {
            throw new HttpException(403, 'You do not have permission to grade this course.');
        }
    }

    /** Ensure the logged-in student is enrolled (or the lesson is preview). */
    protected function ensureEnrolled(LmsCourse $course): int
    {
        $sid = $this->currentStudentId();
        if (!$sid || !$course->isEnrolled($sid)) {
            throw new HttpException(403, 'You are not enrolled in this course.');
        }
        return $sid;
    }
}
