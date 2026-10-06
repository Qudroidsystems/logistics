<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadsheetsMock extends Model
{
    use HasFactory;
    protected $table = "broadsheetmock";
    
    protected $fillable = [
        'broadsheet_records_mock_id',
        'subjectclass_id',
        'term_id',
        'staff_id',
        'exam',
        'total',
        'grade',
        'allsubjectstotalscores',
        'subjectpositionclass',
        'cmin',
        'cmax',
        'avg',
        'remark',
        'submiitedby',
        'vettedby',
        'vettedstatus'
    ];

    /**
     * Relationship to BroadsheetRecordMock
     */
    public function broadsheetRecordMock()
    {
        return $this->belongsTo(BroadsheetRecordMock::class, 'broadsheet_records_mock_id', 'id');
    }


    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function subjectclass()
    {
        return $this->belongsTo(Subjectclass::class, 'subjectclass_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
