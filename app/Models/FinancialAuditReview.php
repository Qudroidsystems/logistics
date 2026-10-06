<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAuditReview extends Model
{
    protected $fillable = ['kind', 'ref_key', 'status', 'note', 'reviewed_by', 'reviewed_at'];
    protected $casts = ['reviewed_at' => 'datetime'];

    public const STATUS = [
        'open'    => ['Open', 'st-pending'],
        'cleared' => ['Cleared', 'st-paid'],
        'flagged' => ['Flagged', 'st-danger'],
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
