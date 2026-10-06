<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetableSubjectPriority extends Model
{
    use HasFactory;

    protected $table = 'timetable_subject_priorities';

    protected $fillable = [
        'setting_id',
        'subject_id',
        'priority_level',
        'use_priority',
        'affects_ordering',
        'affects_slot_quality',
        'is_protected',
    ];

    protected $casts = [
        'setting_id'           => 'integer',
        'subject_id'           => 'integer',
        'priority_level'       => 'integer',
        'use_priority'         => 'boolean',
        'affects_ordering'     => 'boolean',
        'affects_slot_quality' => 'boolean',
        'is_protected'         => 'boolean',
    ];

    public const LEVELS = [
        1 => 'Critical',
        2 => 'High',
        3 => 'Normal',
        4 => 'Low',
        5 => 'Minimal',
    ];

    public const DEFAULT_LEVEL = 3;

    public function setting()
    {
        return $this->belongsTo(TimetableSetting::class, 'setting_id', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->priority_level] ?? 'Normal';
    }
}