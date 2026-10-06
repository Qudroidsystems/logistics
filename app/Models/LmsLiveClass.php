<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsLiveClass extends Model
{
    protected $table = 'lms_live_classes';

    protected $fillable = [
        'course_id', 'title', 'description', 'provider', 'join_url',
        'scheduled_at', 'duration_minutes', 'status', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function course() { return $this->belongsTo(LmsCourse::class, 'course_id'); }

    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    /** Joinable in a window from 15 min before start until end. */
    public function isJoinable(): bool
    {
        if (!$this->join_url || in_array($this->status, ['ended', 'cancelled'], true)) return false;
        if (!$this->scheduled_at) return true;
        $end = $this->scheduled_at->copy()->addMinutes(($this->duration_minutes ?: 60));
        return now()->gte($this->scheduled_at->copy()->subMinutes(15)) && now()->lte($end);
    }
}
