<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Small key → JSON settings store for finance modules, with defaults. */
class FinanceSettings
{
    public const DEFAULTS = [
        'loans' => [
            'enabled' => true,
            'max_loan_months' => 12,
            'max_loan_multiple' => 3,          // loan ≤ N × monthly gross
            'max_advance_percent' => 50,       // advance ≤ % of monthly net
            'max_advance_months' => 3,
            'max_deduction_percent' => 33,     // all loan deductions ≤ % of monthly gross
            'default_interest_rate' => 0,      // % per year, flat
            'min_service_months' => 6,
            'one_active_per_type' => true,
            'guarantor_above' => 0,            // 0 = never required
            'coop_loan_multiple' => 2,         // cooperative loan ≤ N × savings
        ],
        'attendance_pay' => [
            'enabled' => false,
            'deduct_absence' => true,          // absent on a working day with no approved leave
            'lates_per_day' => 3,              // every N lates = 1 day's pay (0 = off)
            'grace_absences' => 0,             // absences allowed per month before deducting
            'exempt_staff' => [],              // staffbioinfo ids not on the device
            'overtime_taxable' => true,
            'rates' => ['extra_lesson' => 2000, 'overtime_hour' => 1500, 'weekend_duty' => 5000, 'other' => 0],
        ],
        'expenses' => [
            'second_approval_above' => 500000, // needs a second approver above this amount
            'block_over_budget' => false,      // stop, rather than warn, when a budget line is exceeded
            'receipt_required_above' => 20000,
        ],
        'accounting' => [
            'books_closed_until' => null,      // no entries dated on/before this date
            'financial_year_start' => '09-01', // MM-DD (school year)
            'bank_account' => '1020',
            'cash_account' => '1010',
        ],
    ];

    public static function available(): bool
    {
        return Schema::hasTable('finance_settings');
    }

    public static function get(string $key): array
    {
        $defaults = self::DEFAULTS[$key] ?? [];
        if (!self::available()) return $defaults;
        $stored = Cache::remember("finance_settings:$key", 300, function () use ($key) {
            $v = DB::table('finance_settings')->where('key', $key)->value('value');
            return $v ? (json_decode($v, true) ?: []) : [];
        });
        return array_replace($defaults, $stored);
    }

    public static function put(string $key, array $value, ?int $userId = null): void
    {
        $merged = array_replace(self::get($key), $value);
        DB::table('finance_settings')->updateOrInsert(['key' => $key], [
            'value' => json_encode($merged), 'updated_by' => $userId, 'updated_at' => now(), 'created_at' => now(),
        ]);
        Cache::forget("finance_settings:$key");
    }
}
