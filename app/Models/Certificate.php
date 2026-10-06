<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'template_id', 'student_id', 'serial', 'verify_token', 'title', 'status',
        'class_id', 'term_id', 'session_id', 'data_snapshot', 'design_snapshot', 'rendered_path',
        'generation_count', 'last_generated_at', 'approved_at', 'approved_by',
        'issued_at', 'issued_by', 'revoked_at', 'revoked_by', 'revoke_reason', 'created_by',
    ];

    protected $casts = [
        'generation_count' => 'integer',
        'last_generated_at' => 'datetime', 'approved_at' => 'datetime',
        'issued_at' => 'datetime', 'revoked_at' => 'datetime',
    ];

    public const STATUS = [
        'draft'    => ['Draft', 'st-muted'],
        'approved' => ['Approved', 'st-info'],
        'issued'   => ['Issued', 'st-paid'],
        'revoked'  => ['Revoked', 'st-danger'],
    ];

    public function template() { return $this->belongsTo(CertificateTemplate::class, 'template_id'); }
    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
    public function logs() { return $this->hasMany(CertificateLog::class, 'certificate_id'); }

    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst((string) $this->status), 'st-muted']; }

    public function dataSnapshot(): array
    {
        $d = json_decode((string) $this->data_snapshot, true);
        return is_array($d) ? $d : [];
    }

    public function isRevoked(): bool { return $this->status === 'revoked'; }
    public function isIssued(): bool { return $this->status === 'issued'; }

    /** Serial made safe for use as a filename. */
    public function serial_safe(): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $this->serial);
    }
}
