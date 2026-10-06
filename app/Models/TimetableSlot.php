<?php
// app/Models/TimetableSlot.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableSlot extends Model
{
    protected $table = 'timetable_slots';

    protected $fillable = [
        'setting_id', 'period_id', 'day', 'subject_id', 'teacher_id',
        'is_double', 'is_free', 'room_id', 'notes'  // Changed 'room' to 'room_id'
    ];

    protected $casts = [
        'is_double' => 'boolean',
        'is_free' => 'boolean'
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(TimetableSetting::class, 'setting_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class, 'period_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Add this relationship
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(TimetableNotification::class, 'slot_id');
    }

    public function getTeacherPictureAttribute(): ?string
    {
        if ($this->teacher && $this->teacher->staffPicture) {
            return asset('storage/staff_avatars/' . $this->teacher->staffPicture->picture);
        }
        return asset('storage/staff_avatars/default.png');
    }
}
