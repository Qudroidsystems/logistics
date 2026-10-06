<?php
// app/Models/TeacherAvailability.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    protected $table = 'teacher_availability';

    protected $fillable = ['teacher_id', 'day', 'start_time', 'end_time', 'is_available'];

    protected $casts = [
        'is_available' => 'boolean'
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
