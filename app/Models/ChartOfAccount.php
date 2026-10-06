<?php
// app/Models/ChartOfAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'account_code', 'account_name', 'account_type', 'normal_balance',
        'parent_id', 'school_bill_id', 'is_active', 'is_bank_account',
        'bank_name', 'bank_account_no', 'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_bank_account' => 'boolean',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function schoolBill()
    {
        return $this->belongsTo(SchoolBillModel::class, 'school_bill_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('is_bank_account', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }
}
