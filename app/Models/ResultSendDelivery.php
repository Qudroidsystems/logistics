<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultSendDelivery extends Model
{
    protected $table = 'result_send_deliveries';

    protected $fillable = [
        'result_send_item_id', 'result_send_id', 'channel', 'recipient', 'recipient_name',
        'status', 'error', 'provider_message_id', 'attempts', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ResultSendItem::class, 'result_send_item_id');
    }
}
