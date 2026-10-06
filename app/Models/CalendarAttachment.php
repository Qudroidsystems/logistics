<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarAttachment extends Model
{
    protected $fillable = ['event_id', 'path', 'name', 'mime', 'size'];
    protected $casts = ['size' => 'integer'];

    public function event() { return $this->belongsTo(CalendarEvent::class, 'event_id'); }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
