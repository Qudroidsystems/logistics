<?php
// app/Models/TimetablePeriod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetablePeriod extends Model
{
    protected $table = 'timetable_periods';

    protected $fillable = [
        'setting_id', 'order', 'name', 'type',
        'start_time', 'end_time', 'duration_minutes', 'is_break'
    ];

    protected $casts = ['is_break' => 'boolean'];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(TimetableSetting::class, 'setting_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class, 'period_id');
    }

    public function isLesson(): bool
    {
        return $this->type === 'lesson';
    }

    public function isBreak(): bool
    {
        return $this->is_break;
    }

    public function getDurationLabel(): string
    {
        return $this->start_time . ' – ' . $this->end_time . ' (' . $this->duration_minutes . ' min)';
    }
}
