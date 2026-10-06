<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $table = 'expense_categories';
    protected $fillable = ['code', 'name', 'description', 'account_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function account() { return $this->belongsTo(ChartOfAccount::class, 'account_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
