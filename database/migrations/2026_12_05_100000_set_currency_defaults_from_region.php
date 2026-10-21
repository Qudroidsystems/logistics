<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money columns default to the install's currency (config/region.php, e.g. CAD with REGION=CA) instead of NGN.
 * On a fresh install that has taken no orders and posted nothing to the ledger yet, the rows the seeders made in NGN
 * (platform ledger accounts, cities, operators, price lists) are switched too. An install with real NGN money is left alone.
 */
return new class extends Migration
{
    private const COLUMNS = [
        ['operators', 'default_currency'], ['cities', 'currency'], ['pricing_plans', 'currency'],
        ['ledger_accounts', 'currency'], ['orders', 'currency'], ['payment_intents', 'currency'],
    ];

    public function up(): void
    {
        $code = strtoupper((string) config('region.currency', 'NGN'));
        if (! preg_match('/^[A-Z]{3}$/', $code)) {
            return;
        }
        foreach (self::COLUMNS as [$table, $column]) {
            if (Schema::hasColumn($table, $column)) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '{$code}'");
            }
        }
        if ($code === 'NGN') {
            return;
        }
        $fresh = ! DB::table('orders')->exists() && ! DB::table('payment_intents')->exists()
            && (! Schema::hasTable('ledger_entries') || ! DB::table('ledger_entries')->exists())
            && (! Schema::hasTable('ledger_transactions') || ! DB::table('ledger_transactions')->exists());
        if ($fresh) {
            foreach (self::COLUMNS as [$table, $column]) {
                if (Schema::hasColumn($table, $column)) {
                    DB::table($table)->where($column, 'NGN')->update([$column => $code]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as [$table, $column]) {
            if (Schema::hasColumn($table, $column)) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT 'NGN'");
            }
        }
    }
};
