<?php
// app/Models/TimetableSetting.php

namespace App\Models;

use App\Models\TimetableSubjectPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableSetting extends Model
{
    protected $table = 'timetable_settings';

    protected $fillable = [
        'schoolclass_id', 'session_id', 'term_id',
        'school_day_start', 'school_day_end',
        'period_duration_minutes', 'short_break_duration_minutes', 'long_break_duration_minutes',
        'is_active', 'active_days', 'created_by', 'updated_by',
        'is_published', 'published_at', 'published_by',
        'free_periods_per_week', 'max_lessons_per_day',
        'lessons_per_day', 'short_break_after_period', 'long_break_after_period',
        'assembly_day', 'half_days', 'deprioritize_break_adjacent','advanced_rules',
        'is_preview',
        'preview_expires_at',
    ];

    protected $casts = [
        'active_days'                 => 'array',
        'is_active'                   => 'boolean',
        'is_published'                => 'boolean',
        'published_at'                => 'datetime',
        'half_days'                   => 'array',
        'deprioritize_break_adjacent' => 'boolean',
        'advanced_rules'     => 'array',
        'is_preview'         => 'boolean',
        'preview_expires_at' => 'datetime',
    ];

    public function schoolclass(): BelongsTo { return $this->belongsTo(Schoolclass::class, 'schoolclass_id'); }
    public function session(): BelongsTo    { return $this->belongsTo(Schoolsession::class, 'session_id'); }
    public function term(): BelongsTo       { return $this->belongsTo(Schoolterm::class, 'term_id'); }
    public function periods(): HasMany      { return $this->hasMany(TimetablePeriod::class, 'setting_id')->orderBy('order'); }
    public function constraints(): HasMany  { return $this->hasMany(TimetableConstraint::class, 'setting_id'); }
    public function slots(): HasMany        { return $this->hasMany(TimetableSlot::class, 'setting_id'); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function updater()   { return $this->belongsTo(User::class, 'updated_by'); }
    public function publisher() { return $this->belongsTo(User::class, 'published_by'); }
    public function editor()    { return $this->belongsTo(User::class, 'editing_by'); }
    public function subjectPriorities()
    {
        return $this->hasMany(TimetableSubjectPriority::class, 'setting_id', 'id');
    }

    public function scopeReal($query)
    {
        return $query->where('is_preview', false);
    }

    public function scopeExpiredPreviews($query)
    {
        return $query->where('is_preview', true)
            ->where('preview_expires_at', '<', now());
    }
}