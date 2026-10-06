<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollStatutoryRate extends Model
{
    protected $fillable = ['code', 'name', 'effective_from', 'effective_to', 'config', 'updated_by'];

    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'config' => 'array'];

    public const CODES = [
        'paye' => 'PAYE tax', 'pension' => 'Pension', 'nhf' => 'National Housing Fund', 'nhia' => 'Health insurance (NHIA)',
        'nsitf' => 'NSITF', 'itf' => 'ITF', 'limits' => 'Payroll limits',
    ];
}
