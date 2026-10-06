<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = [
        'title', 'description', 'category_id', 'color', 'session_id', 'term_id', 'location',
        'start_date', 'end_date', 'start_time', 'end_time', 'all_day', 'audiences', 'is_public',
        'recurrence', 'reminders', 'rsvp_enabled', 'source', 'source_ref', 'is_active', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date', 'end_date' => 'date',
        'all_day' => 'boolean', 'is_public' => 'boolean', 'rsvp_enabled' => 'boolean', 'is_active' => 'boolean',
        'audiences' => 'array', 'recurrence' => 'array', 'reminders' => 'array',
    ];

    public const AUDIENCES = [
        'staff' => 'Staff', 'parents' => 'Parents', 'students' => 'Students', 'public' => 'Public',
    ];

    public function category() { return $this->belongsTo(CalendarCategory::class, 'category_id'); }
    public function attachments() { return $this->hasMany(CalendarAttachment::class, 'event_id'); }
    public function rsvps() { return $this->hasMany(CalendarRsvp::class, 'event_id'); }

    public function displayColor(): string
    {
        return $this->color ?: ($this->category->color ?? '#0f766e');
    }

    public function audienceList(): array
    {
        return is_array($this->audiences) ? $this->audiences : [];
    }

    public function isFor(string $group): bool
    {
        return in_array($group, $this->audienceList(), true);
    }

    /** Duration of the base event in days (inclusive). */
    public function lengthInDays(): int
    {
        return max(1, Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1);
    }

    /**
     * Occurrence start-dates that intersect [$from,$to], expanding recurrence.
     * Each occurrence keeps the base event's length.
     *
     * @return array<int, \Carbon\Carbon>  occurrence start dates
     */
    public function occurrenceStarts(Carbon $from, Carbon $to): array
    {
        $start = Carbon::parse($this->start_date)->startOfDay();
        $len   = $this->lengthInDays() - 1;
        $rec   = $this->recurrence;

        // Non-recurring: single occurrence if it overlaps the window.
        if (!is_array($rec) || empty($rec['freq']) || $rec['freq'] === 'none') {
            $end = $start->copy()->addDays($len);
            return ($start->lte($to) && $end->gte($from)) ? [$start->copy()] : [];
        }

        $freq     = $rec['freq'];
        $interval = max(1, (int) ($rec['interval'] ?? 1));
        $until    = !empty($rec['until']) ? Carbon::parse($rec['until'])->endOfDay() : $to->copy();
        $hardEnd  = $until->lt($to) ? $until : $to->copy();
        $byday    = array_map('intval', (array) ($rec['byday'] ?? [])); // 0=Sun..6=Sat

        $out = [];
        $guard = 0;

        if ($freq === 'weekly' && $byday) {
            // Walk each day from the anchor week; include matching weekdays on active weeks.
            $anchorWeek = $start->copy()->startOfWeek(Carbon::SUNDAY);
            $cursor = $start->copy();
            while ($cursor->lte($hardEnd) && $guard++ < 1500) {
                $weeksSince = intdiv($anchorWeek->diffInDays($cursor->copy()->startOfWeek(Carbon::SUNDAY)), 7);
                if ($weeksSince % $interval === 0 && in_array($cursor->dayOfWeek, $byday, true)) {
                    $occEnd = $cursor->copy()->addDays($len);
                    if ($cursor->gte($start) && $occEnd->gte($from) && $cursor->lte($hardEnd)) $out[] = $cursor->copy();
                }
                $cursor->addDay();
            }
            return $out;
        }

        $cursor = $start->copy();
        while ($cursor->lte($hardEnd) && $guard++ < 2000) {
            $occEnd = $cursor->copy()->addDays($len);
            if ($occEnd->gte($from)) $out[] = $cursor->copy();
            match ($freq) {
                'daily'   => $cursor->addDays($interval),
                'weekly'  => $cursor->addWeeks($interval),
                'monthly' => $cursor->addMonthsNoOverflow($interval),
                'yearly'  => $cursor->addYearsNoOverflow($interval),
                default   => $cursor->addDays(100000), // stop
            };
        }
        return $out;
    }
}
