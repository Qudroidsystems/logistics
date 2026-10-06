<?php
// app/Models/ScholarshipApplication.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipApplication extends Model
{
    use HasFactory;

    protected $table = 'scholarship_applications';

    protected $fillable = [
        'scholarship_id', 'student_id', 'status', 'motivation_letter',
        'documents', 'admin_notes', 'rejection_reason', 'submitted_at',
        'reviewed_at', 'reviewed_by'
    ];

    protected $casts = [
        'documents' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function scholarship()
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
