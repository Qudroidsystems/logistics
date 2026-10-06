<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceAttendanceLog extends Model
{
    use HasFactory;

    protected $table = 'device_attendance_logs';

    protected $fillable = [
        'device_serial',
        'device_pin',
        'punch_time',
        'verify_mode',
        'status_code',
        'processing_status', // pending | processed | unmapped | error
        'process_note',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'device_pin' => 'integer',
    ];

    // ── Scopes ────────────────────────────────────────────────
    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeUnmapped($query)
    {
        return $query->where('processing_status', 'unmapped');
    }

    public function scopeProcessed($query)
    {
        return $query->where('processing_status', 'processed');
    }

    public function scopeForPin($query, string $deviceSerial, int $pin)
    {
        return $query->where('device_serial', $deviceSerial)->where('device_pin', $pin);
    }
}
