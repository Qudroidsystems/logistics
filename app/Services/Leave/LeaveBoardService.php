<?php

namespace App\Services\Leave;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A single "who's away" view over both staff and student leave. Everything is
 * normalised to a small event shape so the board and calendar can treat staff
 * and students the same way:
 *   { kind: staff|student, name, sub, type, color, start (Carbon), end (Carbon) }
 */
class LeaveBoardService
{
    /** Approved leave overlapping [$from,$to], for staff and/or students. */
    public function events($from, $to, bool $staff = true, bool $students = true): Collection
    {
        $from = Carbon::parse($from)->toDateString();
        $to   = Carbon::parse($to)->toDateString();
        $out  = collect();

        if ($staff && Schema::hasTable('leave_requests')) {
            $rows = DB::table('leave_requests as r')
                ->join('leave_types as t', 't.id', '=', 'r.leave_type_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->where('r.status', 'approved')
                ->where('r.start_date', '<=', $to)->where('r.end_date', '>=', $from)
                ->orderBy('r.start_date')
                ->get(['r.id', 'r.start_date', 'r.end_date', 'r.half_day', 'u.name', 't.name as type', 't.color']);
            foreach ($rows as $r) {
                $out->push((object) [
                    'kind' => 'staff', 'id' => $r->id, 'name' => $r->name, 'sub' => 'Staff',
                    'type' => $r->type . ($r->half_day ? ' (½ day)' : ''), 'color' => $r->color ?: '#0f766e',
                    'start' => Carbon::parse($r->start_date), 'end' => Carbon::parse($r->end_date),
                ]);
            }
        }

        if ($students && Schema::hasTable('student_leave_requests')) {
            $rows = DB::table('student_leave_requests as r')
                ->join('studentRegistration as s', 's.id', '=', 'r.student_id')
                ->leftJoin('schoolclass as c', 'c.id', '=', 'r.class_id')
                ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
                ->where('r.status', 'approved')
                ->where('r.start_date', '<=', $to)->where('r.end_date', '>=', $from)
                ->orderBy('r.start_date')
                ->get(['r.id', 'r.start_date', 'r.end_date', 'r.reason_type', 's.firstname', 's.lastname',
                    DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''),' ',COALESCE(a.arm,''))) as class_name")]);
            foreach ($rows as $r) {
                $out->push((object) [
                    'kind' => 'student', 'id' => $r->id,
                    'name' => trim($r->firstname . ' ' . $r->lastname),
                    'sub' => $r->class_name ?: 'Student',
                    'type' => \App\Models\StudentLeaveRequest::REASONS[$r->reason_type] ?? ucfirst((string) $r->reason_type),
                    'color' => '#2563eb',
                    'start' => Carbon::parse($r->start_date), 'end' => Carbon::parse($r->end_date),
                ]);
            }
        }

        return $out->sortBy([['start', 'asc'], ['name', 'asc']])->values();
    }

    /** Events active on a single day. */
    public function onDay($date, bool $staff = true, bool $students = true): Collection
    {
        $d = Carbon::parse($date);
        return $this->events($d, $d, $staff, $students);
    }

    /**
     * Build a calendar month: array of weeks, each a list of day cells
     * { date (Carbon), inMonth (bool), events (Collection) }. Weeks start Monday.
     */
    public function month(Carbon $anchor, bool $staff = true, bool $students = true): array
    {
        $first = $anchor->copy()->startOfMonth();
        $last  = $anchor->copy()->endOfMonth();
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd   = $last->copy()->endOfWeek(Carbon::SUNDAY);

        $events = $this->events($gridStart, $gridEnd, $staff, $students);

        $weeks = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $day = $cursor->copy();
                $dayEvents = $events->filter(fn ($e) => $day->between($e->start, $e->end))->values();
                $week[] = (object) ['date' => $day, 'inMonth' => $day->month === $anchor->month, 'events' => $dayEvents];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }
        return $weeks;
    }
}
