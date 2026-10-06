<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolNotice extends Model
{
    protected $table = 'school_notices';

    protected $fillable = [
        'type', 'title', 'message', 'sms_text', 'event_date', 'event_end_date', 'event_time',
        'audience', 'channels', 'reminders', 'status', 'send_at', 'sent_at', 'created_by',
    ];

    protected $casts = [
        'audience'       => 'array',
        'channels'       => 'array',
        'reminders'      => 'array',
        'event_date'     => 'date',
        'event_end_date' => 'date',
        'send_at'        => 'datetime',
        'sent_at'        => 'datetime',
    ];

    public const TYPES = [
        'ca_test'    => ['label' => 'CA test',            'icon' => 'ri-edit-2-line'],
        'exam'       => ['label' => 'Examination',        'icon' => 'ri-file-list-3-line'],
        'mock'       => ['label' => 'Mock examination',   'icon' => 'ri-draft-line'],
        'midterm'    => ['label' => 'Midterm break',      'icon' => 'ri-cup-line'],
        'holiday'    => ['label' => 'Public holiday',     'icon' => 'ri-sun-line'],
        'resumption' => ['label' => 'Resumption',         'icon' => 'ri-school-line'],
        'vacation'   => ['label' => 'End of term / vacation', 'icon' => 'ri-plane-line'],
        'meeting'    => ['label' => 'PTA / meeting',      'icon' => 'ri-group-line'],
        'fees'       => ['label' => 'Fees',               'icon' => 'ri-money-dollar-circle-line'],
        'event'      => ['label' => 'School event',       'icon' => 'ri-calendar-event-line'],
        'general'    => ['label' => 'General notice',     'icon' => 'ri-megaphone-line'],
    ];

    public const STATUS = [
        'draft'     => ['label' => 'Draft',     'pill' => 'st-muted'],
        'scheduled' => ['label' => 'Scheduled', 'pill' => 'st-info'],
        'sending'   => ['label' => 'Sending',   'pill' => 'st-pending'],
        'sent'      => ['label' => 'Sent',      'pill' => 'st-paid'],
        'cancelled' => ['label' => 'Cancelled', 'pill' => 'st-danger'],
    ];

    public function dispatches(): HasMany
    {
        return $this->hasMany(NoticeDispatch::class, 'school_notice_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NoticeDelivery::class, 'school_notice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type]['label'] ?? ucfirst($this->type);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }
}
