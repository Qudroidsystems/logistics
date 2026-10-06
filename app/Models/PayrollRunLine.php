<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Permanent copy of one payslip line (kept as it was when calculated/locked). */
class PayrollRunLine extends Model
{
    protected $fillable = ['payroll_run_id', 'payroll_period_id', 'staff_id', 'code', 'label', 'type', 'amount', 'taxable', 'pensionable', 'sort', 'meta'];

    protected $casts = ['amount' => 'float', 'taxable' => 'boolean', 'pensionable' => 'boolean', 'meta' => 'array'];
}
