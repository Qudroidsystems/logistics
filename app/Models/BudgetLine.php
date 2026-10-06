<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    protected $fillable = ['budget_id', 'line_type', 'expense_category_id', 'label', 'amount'];
    protected $casts = ['amount' => 'float'];

    public function budget() { return $this->belongsTo(Budget::class); }
    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function name(): string { return $this->line_type === 'payroll' ? 'Staff salaries (payroll)' : ($this->label ?: ($this->category->name ?? 'Line')); }
}
