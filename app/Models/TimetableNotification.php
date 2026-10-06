<?php
// app/Models/TimetableNotification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableNotification extends Model
{
    protected $table = 'timetable_notifications';

    protected $fillable = [
        'teacher_id', 'slot_id', 'type', 'email',
        'scheduled_at', 'sent_at', 'status', 'payload'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime'
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class, 'slot_id');
    }
}
