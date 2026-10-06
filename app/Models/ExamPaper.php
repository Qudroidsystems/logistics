<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamPaper extends Model
{
    protected $table = 'exam_papers';

    public const STATUS = [
        'draft'              => ['Draft', 'st-muted'],
        'submitted'          => ['Awaiting vetting', 'st-info'],
        'changes_requested'  => ['Changes requested', 'st-pending'],
        'approved'           => ['Approved', 'st-paid'],
        'locked'             => ['Locked', 'st-danger'],
    ];

    public const TYPES = [
        'exam'    => 'Terminal exam',
        'test'    => 'Class test / CA',
        'midterm' => 'Mid-term',
        'mock'    => 'Mock',
    ];

    protected $fillable = [
        'subjectclass_id', 'subject_id', 'class_level', 'term_id', 'session_id',
        'teacher_id', 'title', 'exam_type', 'total_marks', 'duration_minutes',
        'instructions', 'status', 'vetted_by', 'vetted_at', 'locked_by',
        'locked_at', 'vet_summary',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'vetted_at'   => 'datetime',
        'locked_at'   => 'datetime',
    ];

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class, 'exam_paper_id')
            ->orderBy('position')->orderBy('id');
    }

    public function comments()
    {
        return $this->hasMany(ExamVetComment::class, 'exam_paper_id')->latest();
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function label(): array
    {
        return self::STATUS[$this->status] ?? [ucfirst((string) $this->status), 'st-muted'];
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->exam_type] ?? ucfirst((string) $this->exam_type);
    }

    /** A paper can be edited by the teacher only while draft or when changes were requested. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'changes_requested'], true);
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /** Sum the marks of all questions and persist on total_marks. */
    public function recomputeTotal(): void
    {
        $this->total_marks = (float) $this->questions()->sum('marks');
        $this->saveQuietly();
    }
}
