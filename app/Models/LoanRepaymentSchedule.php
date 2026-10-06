<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepaymentSchedule extends Model
{
    protected $table = 'loan_repayment_schedule';

    protected $fillable = [
        'loan_id', 'installment_no', 'due_date', 'amount', 'principal',
        'interest', 'paid_amount', 'status', 'paid_date', 'transaction_reference',
    ];

    protected $casts = [
        'amount' => 'float', 'principal' => 'float', 'interest' => 'float', 'paid_amount' => 'float',
        'due_date' => 'date', 'paid_date' => 'date',
    ];

    public function loan() { return $this->belongsTo(LoanAdvance::class, 'loan_id'); }
}
