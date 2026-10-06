<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportApproval extends Model
{
    protected $fillable = [
        'schoolclass_id', 'term_id', 'session_id', 'status',
        'submitted_by', 'submitted_at', 'submit_note',
        'reviewed_by', 'reviewed_at', 'review_note', 'snapshot', 'history',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'snapshot'     => 'array',
        'history'      => 'array',
    ];

    public const STATUSES = [
        'pending'   => ['Not submitted', 'st-muted'],
        'submitted' => ['Awaiting approval', 'st-pending'],
        'returned'  => ['Returned', 'st-danger'],
        'approved'  => ['Approved & released', 'st-paid'],
    ];

    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function reviewer()  { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function log(string $action, ?int $by, ?string $note = null): void
    {
        $h = $this->history ?? [];
        $h[] = ['at' => now()->toDateTimeString(), 'by' => $by, 'action' => $action, 'note' => $note];
        $this->history = array_slice($h, -30);
    }
}
