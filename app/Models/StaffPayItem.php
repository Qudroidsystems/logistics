<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPayItem extends Model
{
    protected $table = 'staff_pay_items';

    protected $fillable = ['staff_id', 'pay_item_id', 'amount', 'rate', 'from_month', 'to_month', 'note', 'review_id', 'created_by'];

    protected $casts = ['from_month' => 'date', 'to_month' => 'date'];
}
