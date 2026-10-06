<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsAssignment extends Model
{
    protected $table = 'lms_assignments';

    protected $fillable = [
        'course_id', 'lesson_id', 'title', 'instructions', 'max_score', 'rubric', 'due_at',
        'allow_file', 'allow_text', 'is_published', 'created_by',
    ];

    protected $casts = [
        'due_at'       => 'datetime',
        'allow_file'   => 'boolean',
        'allow_text'   => 'boolean',
        'is_published' => 'boolean',
        'max_score'    => 'decimal:2',
        'rubric'       => 'array',
    ];

    public function hasRubric(): bool
    {
        return is_array($this->rubric) && count($this->rubric) > 0;
    }

    public function course()      { return $this->belongsTo(LmsCourse::class, 'course_id'); }
    public function lesson()      { return $this->belongsTo(LmsLesson::class, 'lesson_id'); }
    public function submissions() { return $this->hasMany(LmsAssignmentSubmission::class, 'assignment_id'); }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast();
    }

    public function submissionFor(?int $studentId): ?LmsAssignmentSubmission
    {
        if (!$studentId) return null;
        return $this->submissions()->where('student_id', $studentId)->first();
    }
}
