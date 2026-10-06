<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    protected $table = 'clubs';
    
    protected $fillable = [
        'club',
        'description',
        'patronid',
        'termid',
        'sessionid'
    ];

    /**
     * Get the patron (user) of the club
     */
    public function patron()
    {
        return $this->belongsTo(User::class, 'patronid');
    }

    /**
     * Get the term of the club
     */
    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }

    /**
     * Get the session of the club
     */
    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }

    /**
     * Get the members of the club
     */
    public function members()
    {
        return $this->hasMany(ClubMember::class, 'clubid');
    }

    /**
     * Get the activities of the club
     */
    public function activities()
    {
        return $this->hasMany(ClubActivity::class, 'clubid');
    }
}