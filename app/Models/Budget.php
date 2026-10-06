<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'status', 'notes', 'created_by', 'approved_by', 'approved_at'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'approved_at' => 'datetime'];

    public function lines() { return $this->hasMany(BudgetLine::class); }
    public function scopeActive($q) { return $q->where('status', 'active'); }
}
