<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    protected $table = 'sports';
    
    protected $fillable = [
        'sport',
        'description',
        'coachid',
        'termid',
        'sessionid'
    ];

    /**
     * Get the coach (user) of the sport
     */
    public function coach()
    {
        return $this->belongsTo(User::class, 'coachid');
    }

    /**
     * Get the term of the sport
     */
    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }

    /**
     * Get the session of the sport
     */
    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }

    /**
     * Get the teams of the sport
     */
    public function teams()
    {
        return $this->hasMany(SportTeam::class, 'sportid');
    }

    /**
     * Get the players of the sport
     */
    public function players()
    {
        return $this->hasMany(SportPlayer::class, 'sportid');
    }
}