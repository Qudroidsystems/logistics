<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionActionLog extends Model
{
    protected $table = 'promotion_action_logs';

    protected $fillable = [
        'batch_id', 'action', 'student_id', 'schoolclass_id', 'session_id', 'term_id',
        'description', 'before_state', 'after_state', 'performed_by', 'reverted_at', 'reverted_by',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
        'reverted_at'  => 'datetime',
    ];
}
