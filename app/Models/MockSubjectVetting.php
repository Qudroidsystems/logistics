<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockSubjectVetting extends Model
{
    protected $table = 'mock_subject_vettings';
    protected $primaryKey = 'id';

    protected $fillable = [
        'userid',
        'subjectclassid',
        'termid',
        'sessionid',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }

    public function subjectClass()
    {
        return $this->belongsTo(Subjectclass::class, 'subjectclassid', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }
}