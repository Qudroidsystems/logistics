<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportTeam extends Model
{
    protected $table = 'sport_teams';
    
    protected $fillable = [
        'sportid',
        'team_name',
        'captainid',
        'description'
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sportid');
    }

    public function captain()
    {
        return $this->belongsTo(User::class, 'captainid');
    }
}