<?php
// app/Models/RoomBooking.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomBooking extends Model
{
    use HasFactory;

    protected $table = 'room_bookings';

    protected $fillable = [
        'room_id',
        'date',
        'start_time',
        'end_time',
        'purpose',
        'recurring_type',
        'booked_by',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }
}
