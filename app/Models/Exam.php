<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'staffId',
        'title',
        'description',
        'duration',
        'start_time',
        'end_time',
        'termid',
        'session',
        'subject_id',
        'schoolclass_id',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclass_id');
    }

    // Add this relationship to Subject
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    // Relationship to SubjectTeacher (through subject_id)
    public function subjectTeacher()
    {
        return $this->belongsTo(SubjectTeacher::class, 'subject_id', 'id');
    }

    // Accessor for formatted start time
    public function getFormattedStartTimeAttribute()
    {
        return $this->start_time ? $this->start_time->format('M d, Y h:i A') : null;
    }

    // Accessor for formatted end time
    public function getFormattedEndTimeAttribute()
    {
        return $this->end_time ? $this->end_time->format('M d, Y h:i A') : null;
    }

    // Accessor for display time range
    public function getTimeRangeAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->format('M d, h:i A') . ' - ' . $this->end_time->format('h:i A');
        }
        return null;
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    // In App\Models\Exam.php
public function termRelation()
{
    return $this->belongsTo(Schoolterm::class, 'termid');
}

public function sessionRelation()
{
    return $this->belongsTo(Schoolsession::class, 'session');
}
}
