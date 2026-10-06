<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDutyClaim extends Model
{
    protected $fillable = [
        'staff_id', 'work_date', 'type', 'quantity', 'rate', 'amount', 'description', 'status',
        'submitted_by', 'approved_by', 'approved_at', 'rejection_reason', 'payroll_period_id',
    ];

    protected $casts = ['work_date' => 'date', 'approved_at' => 'datetime', 'quantity' => 'float', 'rate' => 'float', 'amount' => 'float'];

    public const TYPES = [
        'extra_lesson' => ['Extra lesson', 'lesson(s)'], 'overtime_hour' => ['Overtime', 'hour(s)'],
        'weekend_duty' => ['Weekend / holiday duty', 'day(s)'], 'other' => ['Other duty', 'unit(s)'],
    ];

    public const STATUS = ['pending' => ['Pending', 'st-pending'], 'approved' => ['Approved', 'st-info'], 'rejected' => ['Declined', 'st-danger'], 'paid' => ['Paid', 'st-paid']];

    public function staff() { return $this->belongsTo(Staff::class, 'staff_id'); }
    public function period() { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }
    public function typeLabel(): string { return self::TYPES[$this->type][0] ?? ucfirst($this->type); }
}
