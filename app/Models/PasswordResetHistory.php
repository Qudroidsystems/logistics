<?php
// app/Models/PasswordResetHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetHistory extends Model
{
    use HasFactory;

    protected $table = 'password_reset_histories';

    protected $fillable = [
        'student_id',
        'action',
        'performed_by',
        'old_password_hash',
        'new_password_hash',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
