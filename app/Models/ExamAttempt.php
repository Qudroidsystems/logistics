<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 
        'exam_id', 
        'start_time', 
        'end_time', 
        'status', 
        'score',
        'paused_at',    // Timestamp when paused
        'resumed_at',   // Timestamp when resumed
        'pause_duration' // Total seconds of pause time (cumulative)
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'pause_duration' => 'integer',
        'score' => 'decimal:2'
    ];

    /**
     * Relationship to Student/User
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id'); // Assuming User model
    }

    /**
     * Relationship to Exam
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Check if currently paused
     */
    public function isPaused(): bool
    {
        return $this->paused_at !== null && $this->resumed_at === null;
    }

    /**
     * Calculate effective remaining time (accounts for pause duration)
     * Assumes exam duration is in the related Exam model
     */
    public function getEffectiveRemainingTimeAttribute(): int
    {
        if (!$this->start_time) {
            return 0;
        }

        $examDurationSeconds = $this->exam->duration * 60; // Assuming duration in minutes
        $elapsedSeconds = Carbon::now()->diffInSeconds($this->start_time);
        $effectiveElapsed = $elapsedSeconds - $this->pause_duration;
        
        return max(0, $examDurationSeconds - $effectiveElapsed);
    }

    /**
     * Scope for currently paused attempts
     */
    public function scopePaused($query)
    {
        return $query->whereNotNull('paused_at')->whereNull('resumed_at');
    }

    /**
     * Scope for active (non-submitted) attempts
     */
    public function scopeActive($query)
    {
        return $query->whereNull('end_time')->where('status', 'in_progress'); // Adjust status as needed
    }
}