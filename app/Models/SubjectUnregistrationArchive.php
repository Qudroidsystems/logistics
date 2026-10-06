<?php

namespace App\Models;

use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subjectclass;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectUnregistrationArchive extends Model
{
    protected $table = 'subject_unregistration_archive';

    protected $fillable = [
        'studentid',
        'subjectclassid',
        'staffid',
        'termid',
        'sessionid',
        'subjectid',
        'schoolclassid',
        'broadsheet_record_id',
        'unregistered_by',
        'snapshot_name',
        'snapshot_notes',
        'status',
        'unregistered_at',
        'actioned_at',
    ];

    protected $casts = [
        'unregistered_at' => 'datetime',
        'actioned_at'     => 'datetime',
    ];

    // ── Status constants ─────────────────────────────────────────────────────
    const STATUS_ARCHIVED            = 'archived';
    const STATUS_RESTORED            = 'restored';
    const STATUS_PERMANENTLY_DELETED = 'permanently_deleted';

    // ── Relationships ────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'studentid');
    }

    public function subjectclass(): BelongsTo
    {
        return $this->belongsTo(Subjectclass::class, 'subjectclassid');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staffid');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }

    public function unregisteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unregistered_by');
    }

    /**
     * Score snapshots captured at unregistration time.
     */
    public function scoreSnapshots(): HasMany
    {
        return $this->hasMany(ArchiveScoreSnapshot::class, 'archive_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    // ── Alias (kept for backwards compat) ────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staffid');
    }
}
