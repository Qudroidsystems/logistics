<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceOutageDate extends Model
{
    use HasFactory;

    protected $table = 'device_outage_dates';

    protected $fillable = [
        'outage_date',
        'reason',
        'marked_by',
    ];

    protected $casts = [
        'outage_date' => 'date',
    ];

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('outage_date', [$from, $to]);
    }
}
