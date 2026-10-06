<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsQuiz extends Model
{
    protected $table = 'lms_quizzes';

    protected $fillable = [
        'course_id', 'lesson_id', 'title', 'description', 'pass_mark',
        'max_attempts', 'time_limit_minutes', 'available_from', 'available_until',
        'shuffle', 'allow_partial', 'is_published',
    ];

    protected $casts = [
        'shuffle'         => 'boolean',
        'allow_partial'   => 'boolean',
        'is_published'    => 'boolean',
        'available_from'  => 'datetime',
        'available_until' => 'datetime',
    ];

    /** Whether the quiz is open right now (published + within any window). */
    public function isOpen(): bool
    {
        if (!$this->is_published) return false;
        if ($this->available_from && now()->lt($this->available_from)) return false;
        if ($this->available_until && now()->gt($this->available_until)) return false;
        return true;
    }

    public function availabilityNote(): ?string
    {
        if ($this->available_from && now()->lt($this->available_from)) {
            return 'Opens ' . $this->available_from->format('d M Y H:i');
        }
        if ($this->available_until && now()->gt($this->available_until)) {
            return 'Closed on ' . $this->available_until->format('d M Y H:i');
        }
        if ($this->available_until) {
            return 'Closes ' . $this->available_until->format('d M Y H:i');
        }
        return null;
    }

    public function course()   { return $this->belongsTo(LmsCourse::class, 'course_id'); }
    public function lesson()   { return $this->belongsTo(LmsLesson::class, 'lesson_id'); }
    public function questions(){ return $this->hasMany(LmsQuizQuestion::class, 'quiz_id')->orderBy('position'); }
    public function attempts() { return $this->hasMany(LmsQuizAttempt::class, 'quiz_id'); }

    public function totalPoints(): int
    {
        return (int) $this->questions()->sum('points');
    }

    public function attemptsUsed(?int $studentId): int
    {
        if (!$studentId) return 0;
        return (int) $this->attempts()->where('student_id', $studentId)->count();
    }

    public function attemptsLeft(?int $studentId): ?int
    {
        if ($this->max_attempts <= 0) return null; // unlimited
        return max(0, $this->max_attempts - $this->attemptsUsed($studentId));
    }

    public function bestAttempt(?int $studentId): ?LmsQuizAttempt
    {
        if (!$studentId) return null;
        return $this->attempts()->where('student_id', $studentId)->orderByDesc('percent')->first();
    }
}
