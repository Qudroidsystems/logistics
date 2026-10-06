<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_attendance';

    protected $fillable = [
        'staff_id',
        'attendance_date',
        'time_in',
        'time_out',
        'status',  // present | late | excused
        'source',  // device | manual (kept for schema flexibility; device is the only active path)
        'marked_by',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function markedByUser()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('attendance_date', [$from, $to]);
    }

    public function scopeForStaff($query, int $staffId)
    {
        return $query->where('staff_id', $staffId);
    }
}
