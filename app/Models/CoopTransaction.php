<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoopTransaction extends Model
{
    protected $fillable = ['staff_id', 'type', 'amount', 'payroll_period_id', 'txn_date', 'reference', 'note', 'recorded_by'];
    protected $casts = ['amount' => 'float', 'txn_date' => 'date'];

    public const TYPES = ['contribution' => 'Contribution', 'withdrawal' => 'Withdrawal', 'dividend' => 'Dividend / interest', 'adjustment' => 'Adjustment'];

    public function period() { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
}
