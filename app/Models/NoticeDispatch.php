<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoticeDispatch extends Model
{
    protected $table = 'notice_dispatches';

    protected $fillable = [
        'school_notice_id', 'kind', 'label', 'run_at', 'status',
        'total', 'sent', 'failed', 'skipped', 'started_at', 'finished_at', 'error',
    ];

    protected $casts = [
        'run_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(SchoolNotice::class, 'school_notice_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NoticeDelivery::class, 'notice_dispatch_id');
    }
}
