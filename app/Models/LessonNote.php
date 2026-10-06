<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonNote extends Model
{
    protected $table = 'lesson_notes';

    public const STATUS = [
        'draft'     => ['Draft', 'st-muted'],
        'submitted' => ['Submitted', 'st-info'],
        'approved'  => ['Approved', 'st-paid'],
        'returned'  => ['Returned', 'st-danger'],
        'delivered' => ['Delivered', 'st-paid'],
    ];

    protected $fillable = [
        'subjectclass_id', 'subject_id', 'class_level', 'term_id', 'session_id', 'week_no',
        'title', 'objectives', 'content', 'materials', 'methods', 'status', 'teacher_id',
        'reviewed_by', 'reviewed_at', 'review_comment', 'delivered_on',
    ];

    protected $casts = [
        'methods'      => 'array',
        'reviewed_at'  => 'datetime',
        'delivered_on' => 'date',
    ];

    public function subject() { return $this->belongsTo(Subject::class, 'subject_id'); }
    public function topics()  { return $this->belongsToMany(SubjectTopic::class, 'lesson_note_topic', 'lesson_note_id', 'subject_topic_id'); }

    public function label(): array
    {
        return self::STATUS[$this->status] ?? [ucfirst((string) $this->status), 'st-muted'];
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }
}
