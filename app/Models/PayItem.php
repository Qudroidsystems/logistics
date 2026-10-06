<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayItem extends Model
{
    protected $table = 'pay_items';

    protected $fillable = ['code', 'name', 'type', 'calc', 'default_amount', 'default_rate', 'taxable', 'pensionable', 'one_off', 'is_system', 'is_active', 'account_id', 'description'];

    public const CALCS = ['fixed' => 'Fixed amount', 'percent_basic' => '% of basic', 'percent_gross' => '% of gross'];

    protected $casts = ['taxable' => 'boolean', 'pensionable' => 'boolean', 'one_off' => 'boolean', 'is_system' => 'boolean', 'is_active' => 'boolean'];
}
