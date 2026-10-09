<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Tenancy\Models\Operator;
use App\Modules\Tenancy\Models\OperatorCapability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starter data every environment needs: the platform operator (id 1), reference types and defaults.
 * Safe to run more than once.
 */
class LogisticsFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->platformOperator();
        $this->vehicleTypes();
        $this->serviceTypes();
        $this->packageCategories();
        $this->commissionRule();
        $this->platformLedgerAccounts();
        $this->settings();
    }

    private function platformOperator(): void
    {
        $existing = DB::table('operators')->where('id', 1)->first();
        if (! $existing) {
            DB::table('operators')->insert([
                'id' => 1,
                'public_id' => (string) Str::ulid(),
                'type' => Operator::TYPE_PLATFORM,
                'legal_name' => config('app.name', 'Platform'),
                'display_name' => config('app.name', 'Platform'),
                'slug' => 'platform',
                'status' => 'active',
                'commission_bp' => 0,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Keep the sequence ahead of the explicit id.
            DB::statement("SELECT setval(pg_get_serial_sequence('operators', 'id'), GREATEST((SELECT MAX(id) FROM operators), 1))");
        }

        foreach (OperatorCapability::defaultsFor(Operator::TYPE_PLATFORM) as $capability) {
            DB::table('operator_capabilities')->updateOrInsert(
                ['operator_id' => 1, 'capability' => $capability],
                ['enabled' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Make the first Super Admin a member and set their working operator.
        $admin = User::query()->orderBy('id')->first();
        if ($admin) {
            DB::table('operator_members')->updateOrInsert(
                ['operator_id' => 1, 'user_id' => $admin->id],
                ['role' => 'owner', 'status' => 'active', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('users')->where('id', $admin->id)->whereNull('current_operator_id')->update(['current_operator_id' => 1]);
        }

        // Backfill ULIDs on users created before the column existed.
        DB::table('users')->whereNull('public_id')->orderBy('id')->each(function ($u) {
            DB::table('users')->where('id', $u->id)->update(['public_id' => (string) Str::ulid()]);
        });
    }

    private function vehicleTypes(): void
    {
        $rows = [
            ['bicycle', 'Bicycle', 8_000, false, 8000],
            ['motorbike', 'Motorbike', 25_000, true, 10000],
            ['tricycle', 'Tricycle (Keke)', 150_000, true, 13000],
            ['car', 'Car', 60_000, true, 15000],
            ['van', 'Van', 800_000, true, 22000],
            ['truck', 'Truck', 5_000_000, true, 40000],
        ];
        foreach ($rows as [$code, $name, $weight, $licence, $mult]) {
            DB::table('vehicle_types')->updateOrInsert(['code' => $code], [
                'name' => $name, 'max_weight_g' => $weight, 'requires_licence' => $licence,
                'price_multiplier_bp' => $mult, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function serviceTypes(): void
    {
        $rows = [
            ['instant', 'Instant delivery', 90, true, false],
            ['same_day', 'Same-day delivery', 480, true, true],
            ['next_day', 'Next-day delivery', 1440, true, true],
            ['scheduled', 'Scheduled pickup', null, true, true],
            ['express_intercity', 'Express inter-city', 2880, false, true],
            ['economy', 'Economy', 2880, false, true],
            ['freight', 'Freight and haulage', null, false, true],
            ['errand', 'Errand', 180, false, false],
            ['shopping_run', 'Shopping run (market shopper)', 240, false, true],
        ];
        foreach ($rows as [$code, $name, $sla, $cod, $multi]) {
            DB::table('service_types')->updateOrInsert(['code' => $code], [
                'name' => $name, 'sla_minutes' => $sla, 'allows_cod' => $cod, 'allows_multi_stop' => $multi,
                'active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function packageCategories(): void
    {
        $rows = [
            ['documents', 'Documents', false, false],
            ['parcel', 'General parcel', false, false],
            ['food', 'Food and drink', false, false],
            ['electronics', 'Electronics', false, true],
            ['fragile', 'Fragile items', false, false],
            ['groceries', 'Groceries', false, false],
            ['medication', 'Medication', true, true],
            ['alcohol', 'Alcohol', true, true],
            ['valuables', 'High-value items', true, true],
        ];
        foreach ($rows as [$code, $name, $restricted, $signature]) {
            DB::table('package_categories')->updateOrInsert(['code' => $code], [
                'name' => $name, 'restricted' => $restricted, 'requires_signature' => $signature,
                'active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function commissionRule(): void
    {
        if (DB::table('commission_rules')->whereNull('operator_id')->whereNull('operator_type')->exists()) {
            return;
        }
        // Default marketplace fee: 12% of the delivery price, minimum ₦100. Tune per provider type later.
        DB::table('commission_rules')->insert([
            'basis' => 'percent_of_delivery_fee', 'rate_bp' => 1200, 'min_fee' => 10_000,
            'priority' => 0, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function platformLedgerAccounts(): void
    {
        $accounts = [
            ['platform_commission', 'revenue'],
            ['platform_revenue', 'revenue'],
            ['gateway_clearing', 'asset'],
            ['cod_clearing', 'asset'],
            ['promo_expense', 'expense'],
            ['refund_reserve', 'liability'],
            ['tax_payable', 'liability'],
            ['payout_in_transit', 'liability'],
        ];
        foreach ($accounts as [$purpose, $type]) {
            DB::table('ledger_accounts')->updateOrInsert(
                ['owner_type' => 'platform', 'owner_id' => 1, 'purpose' => $purpose, 'currency' => 'NGN'],
                ['operator_id' => 1, 'type' => $type, 'code' => $purpose, 'allow_negative' => in_array($type, ['asset', 'expense'], true), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function settings(): void
    {
        $defaults = [
            'platform.modes' => ['company' => true, 'marketplace' => \App\Support\Platform::marketplace()],
            'dispatch.offer_timeout_seconds' => 30,
            'dispatch.max_offer_attempts' => 5,
            'escrow.default_confirmation_window_hours' => 24,
            'escrow.auto_release' => true,
            'cod.remit_deadline_hours' => 24,
            'tracking.location_interval_seconds' => 8,
        ];
        foreach ($defaults as $key => $value) {
            DB::table('platform_settings')->updateOrInsert(
                ['operator_id' => null, 'scope' => 'global', 'scope_id' => null, 'key' => $key],
                ['value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
