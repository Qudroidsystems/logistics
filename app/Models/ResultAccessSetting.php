<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultAccessSetting extends Model
{
    protected $table = 'result_access_settings';

    protected $fillable = [
        'enabled', 'debt_scope', 'threshold_type', 'threshold_value',
        'apply_to_mock', 'show_amount_owed', 'blocked_message', 'updated_by',
    ];

    protected $casts = [
        'enabled'          => 'boolean',
        'apply_to_mock'    => 'boolean',
        'show_amount_owed' => 'boolean',
        'threshold_value'  => 'float',
    ];

    public const SCOPES = [
        'term'             => 'This term only',
        'term_and_arrears' => 'This term + earlier unpaid terms',
    ];

    public const THRESHOLDS = [
        'any'     => 'Any unpaid balance',
        'amount'  => 'Balance above a set amount (₦)',
        'percent' => 'Balance above a set % of fees',
    ];

    /** The single settings row (created with safe defaults if missing). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'enabled' => false, 'debt_scope' => 'term', 'threshold_type' => 'any', 'threshold_value' => 0,
            'apply_to_mock' => true, 'show_amount_owed' => true,
        ]);
    }
}
