<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentRegistration extends Model
{
    use HasFactory;

    protected $table = 'parentRegistration';
    protected $primaryKey = 'studentId';
    
    // Prevent auto-incrementing as studentId is likely a foreign key
    public $incrementing = false;

    protected $fillable = [
        'studentId',
        'father_title',
        'mother_title',
        'father',
        'mother',
        'father_phone',
        'mother_phone',
        'parent_address',
        'office_address',
        'father_occupation',
        'father_city',
        'parent_email',
    ];

    /**
     * Relationship to Student model
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentId', 'id');
    }
}