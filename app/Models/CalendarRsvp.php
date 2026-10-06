<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarRsvp extends Model
{
    protected $fillable = ['event_id', 'occurrence_date', 'user_id', 'student_id', 'response', 'note'];
    protected $casts = ['occurrence_date' => 'date'];

    public const RESPONSES = ['going' => 'Going', 'maybe' => 'Maybe', 'no' => 'Not going'];

    public function event() { return $this->belongsTo(CalendarEvent::class, 'event_id'); }
}
