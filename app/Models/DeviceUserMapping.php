<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceUserMapping extends Model
{
    use HasFactory;

    protected $table = 'device_user_mappings';

    protected $fillable = [
        'device_serial',
        'device_pin',
        'person_type', // 'student' | 'staff'
        'person_id',
        'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'device_pin' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function student()
    {
        return $this->belongsTo(Student::class, 'person_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'person_id');
    }

    /**
     * Convenience resolver returning the actual Student or Staff model
     * based on person_type, without needing two separate relation calls.
     */
    public function resolvePerson()
    {
        return $this->person_type === 'student'
            ? Student::find($this->person_id)
            : Staff::find($this->person_id);
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForDevice($query, string $deviceSerial)
    {
        return $query->where('device_serial', $deviceSerial);
    }

    public function scopeStudents($query)
    {
        return $query->where('person_type', 'student');
    }

    public function scopeStaff($query)
    {
        return $query->where('person_type', 'staff');
    }
}
