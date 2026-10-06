<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatutoryRemittance extends Model
{
    protected $fillable = ['type', 'payroll_period_id', 'authority', 'staff_count', 'employee_amount', 'employer_amount', 'amount_due', 'amount_paid',
        'due_date', 'status', 'paid_at', 'reference', 'payment_method', 'evidence', 'notes', 'needs_review', 'recorded_by'];

    protected $casts = ['due_date' => 'date', 'paid_at' => 'date', 'needs_review' => 'boolean', 'amount_due' => 'float', 'amount_paid' => 'float',
        'employee_amount' => 'float', 'employer_amount' => 'float'];

    public const TYPES = [
        'paye'    => ['PAYE tax', 'ri-government-line', 'State Internal Revenue Service'],
        'pension' => ['Pension', 'ri-shield-user-line', 'Pension Fund Administrator'],
        'nhf'     => ['NHF', 'ri-home-4-line', 'Federal Mortgage Bank (NHF)'],
        'nhia'    => ['Health insurance', 'ri-heart-pulse-line', 'NHIA / HMO'],
        'nsitf'   => ['NSITF', 'ri-first-aid-kit-line', 'NSITF'],
        'itf'     => ['ITF', 'ri-graduation-cap-line', 'Industrial Training Fund'],
    ];

    public function period() { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast() && !$this->due_date->isToday();
    }

    public function balance(): float
    {
        return round(max(0, $this->amount_due - $this->amount_paid), 2);
    }
}
