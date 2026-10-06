<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_code',
        'room_name',
        'type',
        'capacity',
        'facilities',
        'building',
        'floor',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'facilities' => 'array',
        'is_active'  => 'boolean',
        'capacity'   => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function bookings()
    {
        return $this->hasMany(RoomBooking::class);
    }

    /**
     * Room-to-class(-to-subject) mappings. A row with subject_id = NULL
     * means the room is generically available to that class.
     */
    public function classSubjectMappings()
    {
        return $this->hasMany(RoomClassSubject::class);
    }

    /**
     * Every distinct class this room is mapped to, regardless of subject
     * or session.
     */
    public function mappedClasses()
    {
        return $this->belongsToMany(
            Schoolclass::class,
            'room_class_subject',
            'room_id',
            'schoolclass_id'
        )->distinct();
    }

    public function getFacilitiesAttribute($value)
    {
        if (is_null($value)) return [];
        if (is_array($value)) return $value;
        return json_decode($value, true) ?? [];
    }

    public function setFacilitiesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['facilities'] = json_encode($value);
        } else {
            $this->attributes['facilities'] = $value;
        }
    }
}