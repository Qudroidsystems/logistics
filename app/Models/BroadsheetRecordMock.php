<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadsheetRecordMock extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'schoolclass_id',
        'session_id',
    ];

    protected $table = "broadsheet_records_mock";
    
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

    public function broadsheetsMock()
    {
        return $this->hasMany(BroadsheetsMock::class, 'broadsheet_records_mock_id', 'id');
    }
}
