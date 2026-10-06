<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = ['late_time', 'close_time', 'grace_minutes'];

    /**
     * There should only ever be one row. Cached because
     * DeviceAttendanceProcessor::process() calls this on every single
     * incoming punch — potentially dozens per sync cycle, every 5s.
     */
    public static function current(): self
    {
        return cache()->remember('attendance_settings.current', 300, function () {
            return static::first() ?? static::create([
                'late_time'     => '08:00:00',
                'grace_minutes' => 0,
            ]);
        });
    }

    public static function forget(): void
    {
        cache()->forget('attendance_settings.current');
    }

    /**
     * The actual cutoff instant for a given day: late_time + grace_minutes,
     * on that specific date.
     *
     * Usage in DeviceAttendanceProcessor:
     *   $settings = AttendanceSetting::current();
     *   $cutoff   = $settings->cutoffFor($record->punch_time->copy()->startOfDay());
     *   $isLate   = $record->punch_time->gt($cutoff);
     */
    public function cutoffFor(Carbon $date): Carbon
    {
        return $date->copy()
            ->setTimeFromTimeString($this->late_time)
            ->addMinutes($this->grace_minutes);
    }
}