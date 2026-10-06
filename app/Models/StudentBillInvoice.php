<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentBillInvoice extends Model
{
    use HasFactory;

    protected $table = 'student_bill_invoice';

    protected $fillable = [
        'invoice_no',
        'student_id',
        'school_bill_id',
        'status',
        'payment_method',
        'class_id',
        'termid_id',
        'session_id',
        'generated_by',
    ];
}
