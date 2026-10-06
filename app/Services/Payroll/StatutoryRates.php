<?php

namespace App\Services\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Rates in force on a date (falls back to the built-in defaults). */
class StatutoryRates
{
    protected static array $cache = [];

    public static function on($date): array
    {
        $d = \Carbon\Carbon::parse($date)->toDateString();
        if (isset(self::$cache[$d])) return self::$cache[$d];

        $rates = [];
        foreach (PayrollCalculator::defaults() as $x) {
            if ($x['effective_from'] <= $d && ($x['effective_to'] === null || $x['effective_to'] >= $d)) $rates[$x['code']] = $x['config'];
        }
        if (Schema::hasTable('payroll_statutory_rates')) {
            $rows = DB::table('payroll_statutory_rates')->where('effective_from', '<=', $d)
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $d))
                ->orderBy('effective_from')->orderBy('id')->get();
            foreach ($rows as $r) $rates[$r->code] = json_decode($r->config, true) ?: [];
        }
        return self::$cache[$d] = $rates;
    }
}
