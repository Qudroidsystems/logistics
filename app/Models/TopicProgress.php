<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicProgress extends Model
{
    protected $table = 'topic_progress';

    public const STATUS = [
        'pending'   => ['Not taught', 'st-muted'],
        'taught'    => ['Taught', 'st-info'],
        'confirmed' => ['Verified', 'st-paid'],
    ];

    protected $fillable = [
        'subject_topic_id', 'subjectclass_id', 'status', 'planned_week', 'planned_date',
        'taught_on', 'note', 'taught_by', 'verified_by', 'verified_at', 'hod_comment',
        'student_confirmed', 'disputed',
    ];

    protected $casts = [
        'planned_date'      => 'date',
        'taught_on'         => 'date',
        'verified_at'       => 'datetime',
        'student_confirmed' => 'boolean',
        'disputed'          => 'boolean',
    ];

    public function topic() { return $this->belongsTo(SubjectTopic::class, 'subject_topic_id'); }
    public function confirmations() { return $this->hasMany(TopicConfirmation::class, 'topic_progress_id'); }

    public function label(): array
    {
        return self::STATUS[$this->status] ?? [ucfirst((string) $this->status), 'st-muted'];
    }

    public function isTaught(): bool
    {
        return in_array($this->status, ['taught', 'confirmed'], true);
    }

    /** Overdue = planned in the past but still not taught. */
    public function isOverdue(): bool
    {
        return $this->planned_date && $this->planned_date->isPast() && !$this->isTaught();
    }
}
