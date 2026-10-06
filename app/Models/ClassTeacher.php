<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTeacher extends Model
{
    use HasFactory;
    protected $table = "classteacher";

    protected $fillable = ['staffid','schoolclassid','termid','sessionid' ];

    public function user()
    {
        return $this->belongsTo(User::class, 'staffid');
    }
    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid');
    }
    public function schoolterm()
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }
    public function schoolsession()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }
}
