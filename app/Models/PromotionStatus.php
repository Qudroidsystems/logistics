<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionStatus extends Model
{
    use HasFactory;

    protected $table = 'promotionStatus';

    protected $primaryKey = 'studentId';

    protected $fillable = [
        'studentId',
        'schoolclassid',
        'position',
        'termid',
        'sessionid',
        'promotionStatus',
        'classstatus'

    ];
}
