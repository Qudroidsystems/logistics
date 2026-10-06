<?php
// app/Models/StudentAttendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table = 'student_attendance';

    protected $fillable = [
        'student_id',
        'schoolclass_id',
        'term_id',
        'session_id',
        'attendance_date',
        'period',
        'status',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // ── Helper ────────────────────────────────────────────────
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'present'    => 'Present',
            'absent'     => 'Absent',
            'sick_leave' => 'Sick Leave',
            'excused'    => 'Excused',
            'late'       => 'Late',
            default      => ucfirst($status),
        };
    }
}
