<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsEnrollment extends Model
{
    protected $table = 'lms_enrollments';

    protected $fillable = [
        'course_id', 'student_id', 'source', 'status', 'progress_percent',
        'enrolled_by', 'enrolled_at', 'completed_at',
    ];

    protected $casts = [
        'enrolled_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function course()  { return $this->belongsTo(LmsCourse::class, 'course_id'); }
    public function student() { return $this->belongsTo(Student::class, 'student_id'); }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
