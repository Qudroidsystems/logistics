<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeDelivery extends Model
{
    protected $table = 'notice_deliveries';

    protected $fillable = [
        'notice_dispatch_id', 'school_notice_id', 'channel', 'recipient', 'recipient_name', 'audience_type',
        'student_ids', 'body', 'status', 'error', 'provider_message_id', 'attempts', 'sent_at',
    ];

    protected $casts = ['student_ids' => 'array', 'sent_at' => 'datetime'];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(NoticeDispatch::class, 'notice_dispatch_id');
    }
}
