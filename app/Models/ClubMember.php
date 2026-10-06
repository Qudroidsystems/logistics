<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubMember extends Model
{
    protected $table = 'club_members';
    
    protected $fillable = [
        'clubid',
        'studentid',
        'role',
        'joined_at'
    ];

    public function club()
    {
        return $this->belongsTo(Club::class, 'clubid');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'studentid');
    }
}