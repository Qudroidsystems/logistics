<?php
// app/Models/TimetableConstraint.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableConstraint extends Model
{
    protected $table = 'timetable_constraints';

    protected $fillable = [
        'setting_id', 'subject_id', 'periods_per_week',
        'allow_double_period', 'max_double_periods_per_week',
        'preferred_days', 'avoid_days', 'preferred_periods', 'is_compulsory',
        'avoid_consecutive_double_days',
    ];

    protected $casts = [
        'preferred_days'                => 'array',
        'avoid_days'                    => 'array',
        'preferred_periods'             => 'array',
        'allow_double_period'           => 'boolean',
        'is_compulsory'                 => 'boolean',
        'avoid_consecutive_double_days' => 'boolean',
    ];

    public function setting(): BelongsTo { return $this->belongsTo(TimetableSetting::class, 'setting_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class, 'subject_id'); }
}