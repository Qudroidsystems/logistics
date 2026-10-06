<?php
// app/Models/ScoresheetLock.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoresheetLock extends Model
{
    protected $table = 'scoresheet_locks';

    protected $fillable = [
        'subjectclass_id',
        'term_id',
        'session_id',
        'locked_by',
        'locked_at',
        'is_active',
        'reason',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function subjectclass()
    {
        return $this->belongsTo(Subjectclass::class);
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
