<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $fillable = ['loan_id', 'amount', 'source', 'payroll_period_id', 'reference', 'paid_on', 'note', 'recorded_by'];
    protected $casts = ['amount' => 'float', 'paid_on' => 'date'];

    public const SOURCES = ['payroll' => 'Salary deduction', 'cash' => 'Cash', 'transfer' => 'Bank transfer', 'waiver' => 'Waived / written off'];

    public function loan() { return $this->belongsTo(LoanAdvance::class, 'loan_id'); }
    public function period() { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
