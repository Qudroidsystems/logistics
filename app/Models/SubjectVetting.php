<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectVetting extends Model
{
    protected $table = 'subject_vettings'; // Match table name used in controller
    protected $primaryKey = 'id';
    protected $fillable = ['userid', 'subjectclassId', 'termid', 'sessionid', 'status'];

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
?>