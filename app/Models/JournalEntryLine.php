<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    protected $table = 'journal_entry_lines';

    protected $fillable = ['journal_entry_id', 'account_id', 'debit', 'credit', 'narration', 'student_id', 'staff_id', 'expense_id'];

    protected $casts = ['debit' => 'float', 'credit' => 'float'];

    public function entry() { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account() { return $this->belongsTo(ChartOfAccount::class, 'account_id'); }
}
