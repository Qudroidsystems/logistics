<?php

namespace App\Services\Lms;

use App\Models\CalendarEvent;
use App\Models\LmsAssignment;
use App\Models\LmsCourse;
use App\Models\LmsLiveClass;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors LMS assignment due-dates and live classes into the school calendar as
 * events, keyed by source_ref so they update/remove cleanly. Every method is a
 * no-op (never throws) when the calendar module is absent, so it is safe to call
 * inline from controllers.
 */
class CalendarSync
{
    public static function available(): bool
    {
        try { return Schema::hasTable('calendar_events'); } catch (\Throwable $e) { return false; }
    }

    /** Push (or refresh) an assignment's due date. Removes the event if no due date. */
    public static function pushAssignment(LmsCourse $course, LmsAssignment $a): void
    {
        if (!self::available()) return;
        $ref = 'lms-assignment-' . $a->id;
        if (!$a->due_at || !$a->is_published) { self::remove($ref); return; }

        self::upsert($course, $ref, [
            'title'       => 'Assignment due: ' . $a->title,
            'description' => 'Course: ' . $course->title,
            'color'       => '#c8a24a',
            'start_date'  => $a->due_at->toDateString(),
            'end_date'    => $a->due_at->toDateString(),
            'start_time'  => $a->due_at->format('H:i:s'),
            'end_time'    => $a->due_at->format('H:i:s'),
            'all_day'     => false,
        ]);
    }

    /** Push (or refresh) a live class. Removes it if cancelled or unscheduled. */
    public static function pushLiveClass(LmsCourse $course, LmsLiveClass $lc): void
    {
        if (!self::available()) return;
        $ref = 'lms-live-' . $lc->id;
        if (!$lc->scheduled_at || $lc->status === 'cancelled') { self::remove($ref); return; }

        $end = $lc->scheduled_at->copy()->addMinutes($lc->duration_minutes ?: 60);
        self::upsert($course, $ref, [
            'title'       => 'Live class: ' . $lc->title,
            'description' => 'Course: ' . $course->title . ($lc->join_url ? "\nJoin: " . $lc->join_url : ''),
            'location'    => $lc->join_url,
            'color'       => '#1e3a5f',
            'start_date'  => $lc->scheduled_at->toDateString(),
            'end_date'    => $end->toDateString(),
            'start_time'  => $lc->scheduled_at->format('H:i:s'),
            'end_time'    => $end->format('H:i:s'),
            'all_day'     => false,
        ]);
    }

    /** Re-sync every assignment and live class of a course. Returns count synced. */
    public static function syncCourse(LmsCourse $course): int
    {
        if (!self::available()) return 0;
        $n = 0;
        foreach ($course->assignments()->get() as $a) { self::pushAssignment($course, $a); if ($a->due_at && $a->is_published) $n++; }
        foreach ($course->liveClasses()->get() as $lc) { self::pushLiveClass($course, $lc); if ($lc->scheduled_at && $lc->status !== 'cancelled') $n++; }
        return $n;
    }

    public static function remove(string $ref): void
    {
        if (!self::available()) return;
        try { CalendarEvent::where('source', 'lms')->where('source_ref', $ref)->delete(); } catch (\Throwable $e) {}
    }

    protected static function upsert(LmsCourse $course, string $ref, array $attrs): void
    {
        try {
            CalendarEvent::updateOrCreate(
                ['source' => 'lms', 'source_ref' => $ref],
                array_merge([
                    'session_id'   => $course->session_id,
                    'term_id'      => $course->term_id,
                    'audiences'    => ['students'],
                    'is_public'    => false,
                    'is_active'    => true,
                    'rsvp_enabled' => false,
                    'created_by'   => $course->teacher_id ?: $course->created_by,
                ], $attrs)
            );
        } catch (\Throwable $e) {}
    }
}
