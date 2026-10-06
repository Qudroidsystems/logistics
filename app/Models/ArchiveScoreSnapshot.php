<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveScoreSnapshot extends Model
{
    protected $table = 'archive_score_snapshots';

    protected $fillable = [
        'archive_id',
        'broadsheet_id',
        'student_id',
        'subject_id',
        'schoolclass_id',
        'session_id',
        'term_id',
        'subjectclass_id',
        'staff_id',
        'assessment_id',
        'assessment_name',
        'sub_assessment_id',
        'sub_assessment_name',
        'score',
        'score_type',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    // Score type constants
    const TYPE_ASSESSMENT     = 'assessment';
    const TYPE_SUB_ASSESSMENT = 'sub_assessment';

    // ── Relationships ────────────────────────────────────────────────────────

    public function archive(): BelongsTo
    {
        return $this->belongsTo(SubjectUnregistrationArchive::class, 'archive_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAssessments($query)
    {
        return $query->where('score_type', self::TYPE_ASSESSMENT);
    }

    public function scopeSubAssessments($query)
    {
        return $query->where('score_type', self::TYPE_SUB_ASSESSMENT);
    }

    public function scopeForArchive($query, int $archiveId)
    {
        return $query->where('archive_id', $archiveId);
    }
}
