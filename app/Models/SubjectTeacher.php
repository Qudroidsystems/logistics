<?php

namespace App\Models;

use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subjectclass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectTeacher extends Model
{
    use HasFactory;
    protected $table = "subjectteacher";
    //protected $primaryKey = "userid";

    protected $fillable = [
        'userid',
        'staffid',
        'subjectid',
        'termid',
        'sessionid'

    ];

    public function schoolsession() {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }


    public function schoolterm()
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'staffid');
    }

    // Add this missing relationship
    public function subjectclass()
    {
        return $this->hasOne(Subjectclass::class, 'subjectteacherid','id');
    }

    // Other existing relationships
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subjectid');
    }

   public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'staffid');
    }

}
