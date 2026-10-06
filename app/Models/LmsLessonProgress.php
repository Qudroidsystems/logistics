<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsLessonProgress extends Model
{
    protected $table = 'lms_lesson_progress';

    protected $fillable = [
        'lesson_id', 'course_id', 'student_id', 'completed', 'seconds_spent', 'completed_at',
    ];

    protected $casts = [
        'completed'    => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function lesson()  { return $this->belongsTo(LmsLesson::class, 'lesson_id'); }
    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
}
