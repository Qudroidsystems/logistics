<?php
// ============================================================
//  File: app/Models/AttendanceHoliday.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AttendanceHoliday extends Model
{
    use HasFactory;

    protected $table = 'attendance_holidays';

    protected $fillable = [
        'term_id', 'session_id', 'holiday_date', 'holiday_end_date',
        'holiday_name', 'holiday_type', 'notes', 'created_by',
    ];

    protected $casts = [
        'holiday_date'     => 'date',
        'holiday_end_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function term()    { return $this->belongsTo(Schoolterm::class,   'term_id'); }
    public function session() { return $this->belongsTo(Schoolsession::class,'session_id'); }
    public function creator() { return $this->belongsTo(User::class,         'created_by'); }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeForTerm(Builder $q, int $termId, int $sessionId): Builder
    {
        return $q->where('term_id', $termId)->where('session_id', $sessionId);
    }

    public function scopeOnDate(Builder $q, string $date): Builder
    {
        return $q->where(function ($inner) use ($date) {
            $inner->where(function ($s) use ($date) {
                $s->whereNull('holiday_end_date')->where('holiday_date', $date);
            })->orWhere(function ($s) use ($date) {
                $s->where('holiday_date', '<=', $date)
                  ->where('holiday_end_date', '>=', $date);
            });
        });
    }

    // ── Helpers ───────────────────────────────────────────────
    /** Return all calendar dates covered by this holiday entry. */
    public function allDates(): array
    {
        $dates   = [];
        $current = $this->holiday_date->copy();
        $end     = $this->holiday_end_date ?? $this->holiday_date;
        while ($current->lte($end)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }
        return $dates;
    }

    /** Human-readable type label. */
    public function typeLabel(): string
    {
        return match ($this->holiday_type) {
            'public'       => 'Public Holiday',
            'midterm'      => 'Mid-Term Break',
            'school_event' => 'School Event',
            default        => 'Other',
        };
    }
}
