<?php
// app/Models/AttendanceSummary.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSummary extends Model
{
    use HasFactory;

    protected $table = 'attendance_summaries';

    protected $fillable = [
        'student_id',
        'schoolclass_id',
        'term_id',
        'session_id',
        'total_school_days',
        'days_present',
        'days_absent',
        'days_sick_leave',
        'days_excused',
        'days_late',
        'attendance_percentage',
    ];

    protected $casts = [
        'attendance_percentage' => 'float',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclass_id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }
}
