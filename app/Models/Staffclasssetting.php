<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staffclasssetting extends Model
{
    use HasFactory;
    protected $table = "staffclasssettings";
    // protected $primaryKey = "studentid";

     protected $fillable = [
         'noschoolopened',
         'termends',
         'nexttermbegins',
         'staffid',
         'termid',
         'sessionid',
         'vschoolclassid',
     ];
}
