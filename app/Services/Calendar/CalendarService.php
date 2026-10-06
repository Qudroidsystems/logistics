<?php

namespace App\Services\Calendar;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads the calendar: expands recurring events, overlays holidays, decides what
 * a given viewer may see, builds a month grid, and renders an iCal feed.
 * Normalised occurrence shape (stdClass):
 *   { id, event_id, title, description, color, category, location, all_day,
 *     start (Carbon), end (Carbon), start_time, end_time, is_public,
 *     kind: event|holiday, rsvp_enabled, source }
 */
class CalendarService
{
    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('calendar_events');
    }

    public function categories(bool $activeOnly = true): Collection
    {
        if (!Schema::hasTable('calendar_categories')) return collect();
        return CalendarCategory::query()->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort')->orderBy('name')->get();
    }

    /**
     * Occurrences overlapping [$from,$to].
     * $filter: audiences[] (viewer groups; null = all), category_id, session_id,
     *          term_id, public_only (bool), with_holidays (bool).
     */
    public function occurrences($from, $to, array $filter = []): Collection
    {
        if (!self::available()) return collect();
        $from = Carbon::parse($from)->startOfDay();
        $to   = Carbon::parse($to)->endOfDay();

        $q = CalendarEvent::with('category')->where('is_active', true)
            // window prefilter: base start must be <= window end; recurring events
            // may extend past their base end, so we don't prefilter on end here.
            ->where('start_date', '<=', $to->toDateString())
            ->when(!empty($filter['category_id']), fn ($x) => $x->where('category_id', $filter['category_id']))
            ->when(!empty($filter['session_id']), fn ($x) => $x->where('session_id', $filter['session_id']))
            ->when(!empty($filter['term_id']), fn ($x) => $x->where('term_id', $filter['term_id']))
            ->when(!empty($filter['public_only']), fn ($x) => $x->where('is_public', true));

        $events = $q->orderBy('start_date')->get();

        $viewerGroups = $filter['audiences'] ?? null; // null = see everything
        $out = collect();

        foreach ($events as $e) {
            if ($viewerGroups !== null && !array_intersect($viewerGroups, $e->audienceList())) continue;
            $len = $e->lengthInDays() - 1;
            foreach ($e->occurrenceStarts($from, $to) as $start) {
                $end = $start->copy()->addDays($len);
                if ($start->gt($to) || $end->lt($from)) continue;
                $out->push((object) [
                    'id' => $e->id, 'event_id' => $e->id, 'title' => $e->title, 'description' => $e->description,
                    'color' => $e->displayColor(), 'category' => $e->category->name ?? null, 'location' => $e->location,
                    'all_day' => (bool) $e->all_day, 'start' => $start, 'end' => $end,
                    'start_time' => $e->start_time, 'end_time' => $e->end_time,
                    'is_public' => (bool) $e->is_public, 'kind' => 'event',
                    'rsvp_enabled' => (bool) $e->rsvp_enabled, 'source' => $e->source,
                ]);
            }
        }

        if (!empty($filter['with_holidays'])) {
            $out = $out->merge($this->holidays($from, $to));
        }

        return $out->sortBy([['start', 'asc'], ['title', 'asc']])->values();
    }

    /** Holidays overlay (read-only), handling the table's mixed legacy schema. */
    public function holidays(Carbon $from, Carbon $to): Collection
    {
        if (!Schema::hasTable('holidays')) return collect();
        $hasDate = Schema::hasColumn('holidays', 'date');
        $hasRange = Schema::hasColumn('holidays', 'start_date');
        $rows = DB::table('holidays')->get();
        $out = collect();
        foreach ($rows as $h) {
            $s = null; $e = null;
            if ($hasDate && !empty($h->date)) { $s = Carbon::parse($h->date); $e = $s->copy(); }
            elseif ($hasRange && !empty($h->start_date)) { $s = Carbon::parse($h->start_date); $e = Carbon::parse($h->end_date ?: $h->start_date); }
            if (!$s) continue;
            if ($s->gt($to) || $e->lt($from)) continue;
            $title = $h->title ?? $h->name ?? 'Holiday';
            $out->push((object) [
                'id' => 'h' . $h->id, 'event_id' => null, 'title' => $title, 'description' => null,
                'color' => '#dc2626', 'category' => 'Holiday', 'location' => null,
                'all_day' => true, 'start' => $s->copy()->startOfDay(), 'end' => $e->copy()->startOfDay(),
                'start_time' => null, 'end_time' => null, 'is_public' => true, 'kind' => 'holiday',
                'rsvp_enabled' => false, 'source' => 'holiday',
            ]);
        }
        return $out;
    }

    /** Which audience groups this user belongs to (for filtering what they see). */
    public function viewerGroups($user): array
    {
        if (!$user) return ['public'];
        $groups = [];
        try {
            if ($user->hasRole('Parent')) $groups[] = 'parents';
        } catch (\Throwable $e) {}
        if (!empty($user->student_id)) $groups[] = 'students';
        $isStaff = DB::table('staffbioinfo')->where('userid', $user->id)->exists();
        if ($isStaff) $groups[] = 'staff';
        // Admins / calendar managers see everything.
        try {
            if ($user->can('Manage school calendar') || $user->can('View school calendar')) {
                return ['staff', 'parents', 'students', 'public'];
            }
        } catch (\Throwable $e) {}
        if (!$groups) $groups[] = 'public';
        return array_values(array_unique($groups));
    }

    /**
     * Build a month grid (weeks of day cells). Weeks start Monday.
     * @return array<int, array<int, object>>  weeks -> cells { date, inMonth, events }
     */
    public function monthGrid(Carbon $anchor, array $filter = []): array
    {
        $first = $anchor->copy()->startOfMonth();
        $last  = $anchor->copy()->endOfMonth();
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd   = $last->copy()->endOfWeek(Carbon::SUNDAY);

        $occ = $this->occurrences($gridStart, $gridEnd, $filter);

        $weeks = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $day = $cursor->copy();
                $dayEvents = $occ->filter(fn ($e) => $day->between($e->start, $e->end))->values();
                $week[] = (object) ['date' => $day, 'inMonth' => $day->month === $anchor->month, 'events' => $dayEvents];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }
        return $weeks;
    }

    /** Minimal, well-formed iCal feed for a set of occurrences. */
    public function ical(Collection $occurrences, string $calName = 'School Calendar'): string
    {
        $fold = fn ($s) => trim(chunk_split($s, 73, "\r\n ")); // simple line folding
        $esc = fn ($s) => addcslashes(str_replace(["\r\n", "\n", "\r"], '\\n', (string) $s), ",;\\");
        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//CSS Kabba//School Calendar//EN',
            'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:' . $esc($calName),
        ];
        $stamp = Carbon::now('UTC')->format('Ymd\THis\Z');
        foreach ($occurrences as $o) {
            $uid = ($o->event_id ?: $o->id) . '-' . $o->start->format('Ymd') . '@csskabba';
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTAMP:' . $stamp;
            if ($o->all_day || !$o->start_time) {
                $lines[] = 'DTSTART;VALUE=DATE:' . $o->start->format('Ymd');
                $lines[] = 'DTEND;VALUE=DATE:' . $o->end->copy()->addDay()->format('Ymd');
            } else {
                $lines[] = 'DTSTART:' . $o->start->copy()->setTimeFromTimeString((string) $o->start_time)->format('Ymd\THis');
                $endT = $o->end_time ?: $o->start_time;
                $lines[] = 'DTEND:' . $o->end->copy()->setTimeFromTimeString((string) $endT)->format('Ymd\THis');
            }
            $lines[] = $fold('SUMMARY:' . $esc($o->title));
            if ($o->description) $lines[] = $fold('DESCRIPTION:' . $esc($o->description));
            if ($o->location) $lines[] = $fold('LOCATION:' . $esc($o->location));
            if ($o->category) $lines[] = $fold('CATEGORIES:' . $esc($o->category));
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines) . "\r\n";
    }
}
