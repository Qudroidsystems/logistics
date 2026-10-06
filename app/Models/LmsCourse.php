<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LmsCourse extends Model
{
    protected $table = 'lms_courses';

    protected $fillable = [
        'title', 'slug', 'code', 'description', 'subject_id', 'schoolclass_id',
        'session_id', 'term_id', 'teacher_id', 'cover_path', 'enrollment_mode',
        'allow_self_enroll', 'completion_cert_template_id', 'is_published',
        'settings', 'created_by',
    ];

    protected $casts = [
        'allow_self_enroll' => 'boolean',
        'is_published'      => 'boolean',
        'settings'          => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            if (empty($c->slug)) {
                $c->slug = Str::slug($c->title) . '-' . Str::lower(Str::random(5));
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function sections()      { return $this->hasMany(LmsSection::class, 'course_id')->orderBy('position'); }
    public function lessons()       { return $this->hasMany(LmsLesson::class, 'course_id')->orderBy('position'); }
    public function enrollments()   { return $this->hasMany(LmsEnrollment::class, 'course_id'); }
    public function assignments()   { return $this->hasMany(LmsAssignment::class, 'course_id'); }
    public function quizzes()       { return $this->hasMany(LmsQuiz::class, 'course_id'); }
    public function discussions()   { return $this->hasMany(LmsDiscussion::class, 'course_id'); }
    public function liveClasses()   { return $this->hasMany(LmsLiveClass::class, 'course_id'); }
    public function announcements() { return $this->hasMany(LmsAnnouncement::class, 'course_id')->latest(); }

    public function subject()     { return $this->belongsTo(Subject::class, 'subject_id'); }
    public function schoolclass() { return $this->belongsTo(Schoolclass::class, 'schoolclass_id'); }
    public function teacher()     { return $this->belongsTo(User::class, 'teacher_id'); }

    // ── Helpers ───────────────────────────────────────────────────────────
    public function publishedLessons()
    {
        return $this->lessons()->where('is_published', true);
    }

    public function lessonCount(): int
    {
        return (int) $this->lessons()->where('is_published', true)->count();
    }

    public function isEnrolled(?int $studentId): bool
    {
        if (!$studentId) return false;
        return $this->enrollments()->where('student_id', $studentId)->exists();
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? asset('storage/' . ltrim($this->cover_path, '/')) : null;
    }

    /**
     * Overall-grade weighting (percent) for quizzes vs assignments, stored in
     * settings. Defaults to 50/50; normalised so the two always sum to 100.
     *
     * @return array{quiz:int,assignment:int}
     */
    /** Whether access is blocked for students who owe school fees. */
    public function feesGateOn(): bool
    {
        return (bool) ($this->settings['fees_gate'] ?? false);
    }

    public function gradeWeights(): array
    {
        $s = $this->settings ?? [];
        $q = (int) ($s['quiz_weight'] ?? 50);
        $a = (int) ($s['assignment_weight'] ?? 50);
        $q = max(0, min(100, $q));
        $a = max(0, min(100, $a));
        if ($q + $a === 0) { $q = 50; $a = 50; }
        // normalise to 100
        $sum = $q + $a;
        return ['quiz' => (int) round($q / $sum * 100), 'assignment' => (int) round($a / $sum * 100)];
    }
}
