<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AttendanceTermSetting extends Model
{
    use HasFactory;

    protected $table = 'attendance_term_settings';

    protected $fillable = [
        'term_id',
        'session_id',
        'resumption_date',
        'vacation_date',
        'resumption_time',
        'closing_time',
        'morning_end_time',
        'late_grace_minutes',
        'track_morning',
        'track_afternoon',
        'allow_backdating',
        'backdate_days',
        'created_by',
    ];

    protected $casts = [
        'resumption_date'    => 'date',
        'vacation_date'      => 'date',
        'track_morning'      => 'boolean',
        'track_afternoon'    => 'boolean',
        'allow_backdating'   => 'boolean',
        'backdate_days'      => 'integer',
        'late_grace_minutes' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Time helpers ──────────────────────────────────────────

    /**
     * The instant after which a morning punch is considered late:
     * resumption_time + late_grace_minutes, on the given date.
     */
    public function morningCutoffFor(Carbon $date): Carbon
    {
        return $date->copy()
            ->setTimeFromTimeString($this->resumption_time ?: '08:00:00')
            ->addMinutes($this->late_grace_minutes ?? 0);
    }

    /**
     * The instant at/after which a punch is treated as afternoon.
     */
    public function afternoonStartFor(Carbon $date): Carbon
    {
        return $date->copy()
            ->setTimeFromTimeString($this->morning_end_time ?: '12:00:00');
    }

    /**
     * The instant after which the school day is considered closed.
     */
    public function closingAtFor(Carbon $date): Carbon
    {
        return $date->copy()
            ->setTimeFromTimeString($this->closing_time ?: '14:00:00');
    }

    /**
     * Resumption + closing for a given day, formatted for display.
     */
    public function schoolHoursLabel(): string
    {
        $in  = $this->resumption_time
            ? Carbon::parse($this->resumption_time)->format('g:i A')
            : '8:00 AM';
        $out = $this->closing_time
            ? Carbon::parse($this->closing_time)->format('g:i A')
            : '2:00 PM';

        return "{$in} – {$out}";
    }

    // ── Cached lookup for the processor ───────────────────────

    /**
     * The active term setting — "current session + current term".
     * Cached briefly because DeviceAttendanceProcessor hits this on
     * every single punch. Call ::forget() whenever settings change.
     *
     * NOTE: Schoolterm.status is cast to boolean on the model, so we
     * MUST query with where('status', true) — NOT where('status', 'Current').
     * The string 'Current' coerces to 0 in MySQL against a boolean-cast
     * column, which silently returns the *inactive* terms instead.
     *
     * Schoolsession.status is a plain string → where('status', 'Current').
     */
    public static function current(): ?self
    {
        return cache()->remember('attendance_term_settings.current', 120, function () {
            $term    = Schoolterm::where('status', true)->first();
            $session = Schoolsession::where('status', 'Current')->first();

            if (!$term || !$session) {
                return null;
            }

            return static::where('term_id', $term->id)
                ->where('session_id', $session->id)
                ->first();
        });
    }

    public static function forget(): void
    {
        cache()->forget('attendance_term_settings.current');
    }

    // ── Calendar helpers ──────────────────────────────────────

    /** Total weekdays in the term, minus holidays. */
    public function totalSchoolDays(): int
    {
        $holidayDates = $this->getHolidayDates();

        $count   = 0;
        $current = $this->resumption_date->copy();
        while ($current->lte($this->vacation_date)) {
            if (!$current->isWeekend() && !$holidayDates->contains($current->toDateString())) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /** Collect all individual holiday dates for this term. */
    public function getHolidayDates(): Collection
    {
        $holidays = AttendanceHoliday::forTerm($this->term_id, $this->session_id)->get();

        $dates = collect();
        foreach ($holidays as $h) {
            foreach ($h->allDates() as $d) {
                $dates->push($d);
            }
        }
        return $dates->unique();
    }

    /** Number of periods tracked per day. */
    public function periodsPerDay(): int
    {
        return ($this->track_morning ? 1 : 0) + ($this->track_afternoon ? 1 : 0);
    }

    /** Check whether this setting covers a given date. */
    public function coversDate(string $date): bool
    {
        $d = Carbon::parse($date);
        return $d->between($this->resumption_date, $this->vacation_date);
    }
}