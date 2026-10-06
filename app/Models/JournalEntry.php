<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'entry_no', 'entry_date', 'entry_type', 'description', 'status', 'reference_id', 'reference_type',
        'created_by', 'approved_by', 'approved_at', 'reversal_reason', 'reversed_by', 'reversed_at',
    ];

    protected $casts = ['entry_date' => 'date', 'approved_at' => 'datetime', 'reversed_at' => 'datetime'];

    public const TYPES = [
        'journal' => 'General journal', 'payroll' => 'Payroll', 'payment' => 'Payment', 'receipt' => 'Receipt', 'contra' => 'Bank / cash transfer',
        'adjustment' => 'Adjustment', 'reversal' => 'Reversal', 'petty_cash' => 'Petty cash', 'depreciation' => 'Depreciation', 'opening' => 'Opening balance',
    ];

    public function lines() { return $this->hasMany(JournalEntryLine::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function scopePosted($q) { return $q->where('status', 'posted'); }

    public function total(): float { return round((float) $this->lines->sum('debit'), 2); }
    public function typeLabel(): string { return self::TYPES[$this->entry_type] ?? ucfirst((string) $this->entry_type); }
}
