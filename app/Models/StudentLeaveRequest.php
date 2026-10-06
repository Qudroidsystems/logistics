<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLeaveRequest extends Model
{
    protected $fillable = [
        'student_id', 'requested_by', 'requester_type', 'class_id', 'term_id', 'session_id',
        'reason_type', 'reason', 'start_date', 'end_date', 'days', 'attachment', 'contact_phone',
        'status', 'teacher_id', 'teacher_at', 'teacher_note', 'approver_id', 'approved_at', 'approver_note', 'resumed_at',
    ];

    protected $casts = [
        'start_date' => 'date', 'end_date' => 'date', 'days' => 'integer',
        'teacher_at' => 'datetime', 'approved_at' => 'datetime', 'resumed_at' => 'datetime',
    ];

    public const STATUS = [
        'pending_teacher'   => ['Waiting for class teacher', 'st-pending'],
        'pending_principal' => ['Waiting for principal', 'st-info'],
        'approved'          => ['Approved', 'st-paid'],
        'rejected'          => ['Not approved', 'st-danger'],
        'cancelled'         => ['Cancelled', 'st-muted'],
    ];

    public const REASONS = [
        'sick' => 'Illness / medical', 'family' => 'Family reasons', 'travel' => 'Travel',
        'religious' => 'Religious', 'bereavement' => 'Bereavement', 'other' => 'Other',
    ];

    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }
    public function reasonLabel(): string { return self::REASONS[$this->reason_type] ?? ucfirst((string) $this->reason_type); }
    public function isOpen(): bool { return in_array($this->status, ['pending_teacher', 'pending_principal'], true); }
}
