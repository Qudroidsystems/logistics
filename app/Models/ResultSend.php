<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultSend extends Model
{
    protected $table = 'result_sends';

    protected $fillable = [
        'session_id', 'term_id', 'report_type', 'class_ids', 'channels', 'message', 'sms_text',
        'include_owing', 'require_vetted', 'link_days', 'status', 'students', 'skipped',
        'messages_sent', 'messages_failed', 'created_by', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'class_ids' => 'array', 'channels' => 'array',
        'include_owing' => 'boolean', 'require_vetted' => 'boolean',
        'started_at' => 'datetime', 'finished_at' => 'datetime',
    ];

    public const STATUS = [
        'queued'    => ['label' => 'Queued',    'pill' => 'st-info'],
        'sending'   => ['label' => 'Sending',   'pill' => 'st-pending'],
        'sent'      => ['label' => 'Sent',      'pill' => 'st-paid'],
        'cancelled' => ['label' => 'Cancelled', 'pill' => 'st-danger'],
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ResultSendItem::class, 'result_send_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ResultSendDelivery::class, 'result_send_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }
}
