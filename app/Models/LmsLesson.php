<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LmsLesson extends Model
{
    protected $table = 'lms_lessons';

    public const TYPES = [
        'text'         => 'Text / reading',
        'file'         => 'File / document',
        'video_embed'  => 'Embedded video (link)',
        'video_upload' => 'Uploaded video',
        'cbt'          => 'Graded test (CBT)',
        'live'         => 'Live class',
    ];

    protected $fillable = [
        'course_id', 'section_id', 'title', 'type', 'content', 'video_url',
        'attachment_path', 'attachment_name', 'exam_id', 'duration_minutes',
        'position', 'is_preview', 'is_published',
    ];

    protected $casts = [
        'is_preview'   => 'boolean',
        'is_published' => 'boolean',
    ];

    public function course()  { return $this->belongsTo(LmsCourse::class, 'course_id'); }
    public function section() { return $this->belongsTo(LmsSection::class, 'section_id'); }
    public function quizzes() { return $this->hasMany(LmsQuiz::class, 'lesson_id'); }
    public function progress(){ return $this->hasMany(LmsLessonProgress::class, 'lesson_id'); }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? asset('storage/' . ltrim($this->attachment_path, '/')) : null;
    }

    /** For video_embed: normalise common share links into an embeddable URL. */
    public function embedUrl(): ?string
    {
        $u = trim((string) $this->video_url);
        if ($u === '') return null;

        // YouTube
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/shorts/)([A-Za-z0-9_-]{6,})~', $u, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        // Vimeo
        if (preg_match('~vimeo\.com/(\d+)~', $u, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return $u; // already an embed / other provider
    }

    public function completedBy(?int $studentId): bool
    {
        if (!$studentId) return false;
        return $this->progress()->where('student_id', $studentId)->where('completed', true)->exists();
    }
}
