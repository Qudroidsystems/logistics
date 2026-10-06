<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoopMember extends Model
{
    protected $fillable = ['staff_id', 'monthly_contribution', 'status', 'joined_on', 'left_on', 'note'];
    protected $casts = ['monthly_contribution' => 'float', 'joined_on' => 'date', 'left_on' => 'date'];

    public function staff() { return $this->belongsTo(Staff::class, 'staff_id'); }
    public function transactions() { return $this->hasMany(CoopTransaction::class, 'staff_id', 'staff_id'); }
}
