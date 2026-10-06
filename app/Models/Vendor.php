<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address', 'bank_name', 'account_number', 'account_name', 'tin', 'category', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function vouchers() { return $this->hasMany(ExpenseVoucher::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
