<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BroadsheetRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'schoolclass_id',
        'session_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class);
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class);
    }

    public function broadsheets()
    {
        return $this->hasMany(Broadsheet::class,'broadsheet_record_id', 'id');
    }
   
}
