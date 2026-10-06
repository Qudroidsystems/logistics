<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsQuizAttempt extends Model
{
    protected $table = 'lms_quiz_attempts';

    protected $fillable = [
        'quiz_id', 'student_id', 'answers', 'marks', 'score', 'max_score', 'percent',
        'passed', 'needs_review', 'graded_by', 'graded_at', 'attempt_no',
        'started_at', 'submitted_at',
    ];

    protected $casts = [
        'answers'      => 'array',
        'marks'        => 'array',
        'passed'       => 'boolean',
        'needs_review' => 'boolean',
        'score'        => 'decimal:2',
        'max_score'    => 'decimal:2',
        'started_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
    ];

    public function quiz()    { return $this->belongsTo(LmsQuiz::class, 'quiz_id'); }
    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
}
