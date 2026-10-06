<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Principalscomment extends Model
{
    use HasFactory;

    protected $table = 'principalscomments';

    protected $fillable = [
        'staffId',
        'schoolclassid',
        'sessionid',
        'termid',
    ];

    // Relationships
    public function staff()
    {
        return $this->belongsTo(User::class, 'staffId');
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }
}