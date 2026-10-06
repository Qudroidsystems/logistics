<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsAssignmentSubmission extends Model
{
    protected $table = 'lms_assignment_submissions';

    protected $fillable = [
        'assignment_id', 'student_id', 'body', 'file_path', 'file_name', 'status',
        'score', 'rubric_scores', 'feedback', 'graded_by', 'submitted_at', 'graded_at',
    ];

    protected $casts = [
        'submitted_at'  => 'datetime',
        'graded_at'     => 'datetime',
        'score'         => 'decimal:2',
        'rubric_scores' => 'array',
    ];

    public function assignment() { return $this->belongsTo(LmsAssignment::class, 'assignment_id'); }
    public function student()    { return $this->belongsTo(Student::class, 'student_id'); }

    public function fileUrl(): ?string
    {
        return $this->file_path ? asset('storage/' . ltrim($this->file_path, '/')) : null;
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded' && $this->score !== null;
    }
}
