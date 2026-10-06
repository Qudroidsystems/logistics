<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultSendItem extends Model
{
    protected $table = 'result_send_items';

    protected $fillable = [
        'result_send_id', 'student_id', 'class_id', 'status', 'skip_reason', 'pdf_path',
        'token', 'link_expires_at', 'downloads', 'last_downloaded_at', 'wa_media_id', 'error',
    ];

    protected $casts = ['link_expires_at' => 'datetime', 'last_downloaded_at' => 'datetime'];

    public function send(): BelongsTo
    {
        return $this->belongsTo(ResultSend::class, 'result_send_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ResultSendDelivery::class, 'result_send_item_id');
    }
}
