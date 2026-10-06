<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studentclass extends Model
{
    use HasFactory;
    protected $table = "studentclass";
    protected $primaryKey= "studentId";

    protected $fillable = [
        'studentId',
        'schoolclassid',
        'termid',
        'sessionid',

    ];


// In Studentclass model
public function schoolclass()
{
    return $this->belongsTo(Schoolclass::class, 'schoolclassid', 'id');
}

// In Schoolclass model
public function armRelation()
{
    return $this->belongsTo(Schoolarm::class, 'arm', 'id'); // Adjust based on your actual relationship
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
