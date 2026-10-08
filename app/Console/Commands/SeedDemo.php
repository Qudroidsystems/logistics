<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Sets up a small demo world so the whole flow can be tried by hand: a live city with a service zone, a customer with
 * money in their wallet, an approved delivery company with an owner, and an active driver. Safe to run again.
 */
class SeedDemo extends Command
{
    protected $signature = 'logistics:demo {--password=Demo1234! : Password for the three demo logins} {--fund=5000000 : Wallet top-up for the customer, in kobo}';

    protected $description = 'Create a demo city, customer, delivery company and driver (not for production)';

    public function handle(AccountResolver $accounts, LedgerPoster $ledger): int
    {
        if (app()->environment('production')) {
            $this->error('This is for local and staging use only.');

            return self::FAILURE;
        }
        $password = (string) $this->option('password');

        DB::transaction(function () use ($password, $accounts, $ledger) {
            $cityId = $this->city();
            $customer = $this->user('Demo Customer', 'demo.customer@example.test', $password);
            $ownerUser = $this->user('Demo Owner', 'demo.provider@example.test', $password);
            $driverUser = $this->user('Demo Driver', 'demo.driver@example.test', $password);

            $op = $this->company($ownerUser, $cityId);
            $this->driver($op, $driverUser);
            $this->fund($customer, (int) $this->option('fund'), $accounts, $ledger);
        });

        $this->info('Demo data is ready. All three logins use the password you gave (default Demo1234!).');
        $this->table(['Role', 'Email', 'Opens'], [
            ['Customer (wallet funded)', 'demo.customer@example.test', '/account'],
            ['Delivery company owner', 'demo.provider@example.test', '/provider'],
            ['Driver', 'demo.driver@example.test', '/driver'],
        ]);

        return self::SUCCESS;
    }

    private function city(): int
    {
        $id = DB::table('cities')->where('slug', 'demo-lokoja')->value('id');
        if ($id) {
            return (int) $id;
        }
        $id = DB::table('cities')->insertGetId([
            'name' => 'Lokoja (demo)', 'region' => 'Kogi', 'slug' => 'demo-lokoja', 'launch_status' => 'live',
            'centre' => DB::raw('ST_SetSRID(ST_MakePoint(6.7400, 7.8000), 4326)::geography'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('zones')->insert([
            'city_id' => $id, 'name' => 'Lokoja (demo, whole city)', 'type' => 'service',
            'boundary' => DB::raw('ST_Multi(ST_Buffer(ST_SetSRID(ST_MakePoint(6.7400, 7.8000), 4326)::geography, 15000)::geometry)::geography'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return (int) $id;
    }

    private function user(string $name, string $email, string $password): User
    {
        $u = User::query()->where('email', $email)->first() ?? new User();
        $u->forceFill(['name' => $name, 'email' => $email, 'password' => Hash::make($password), 'email_verified_at' => now(), 'must_change_password' => false])->save();

        return $u;
    }

    private function company(User $owner, int $cityId): int
    {
        $op = DB::table('operators')->where('slug', 'demo-haulage')->value('id');
        if (! $op) {
            $op = DB::table('operators')->insertGetId([
                'public_id' => (string) Str::ulid(), 'type' => 'company', 'legal_name' => 'Demo Haulage Ltd', 'display_name' => 'Demo Haulage',
                'slug' => 'demo-haulage', 'status' => 'active', 'home_city_id' => $cityId, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('operator_members')->updateOrInsert(
            ['operator_id' => $op, 'user_id' => $owner->id],
            ['role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('users')->where('id', $owner->id)->update(['current_operator_id' => $op]);

        $serviceTypes = DB::table('service_types')->pluck('id')->map(fn ($i) => (int) $i)->all();
        DB::table('provider_profiles')->updateOrInsert(['operator_id' => $op], [
            'public_id' => DB::table('provider_profiles')->where('operator_id', $op)->value('public_id') ?? (string) Str::ulid(),
            'public_slug' => 'demo-haulage', 'headline' => 'Fast, careful deliveries around Lokoja', 'listed' => true,
            'service_types' => json_encode($serviceTypes), 'vehicle_types' => json_encode([]),
            'availability_status' => 'accepting', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $zone = DB::table('zones')->where('city_id', $cityId)->whereNull('operator_id')->value('id');
        if ($zone) {
            DB::table('operator_service_areas')->updateOrInsert(['operator_id' => $op, 'zone_id' => $zone, 'service_type_id' => null], ['active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        return (int) $op;
    }

    private function driver(int $op, User $driver): void
    {
        DB::table('operator_members')->updateOrInsert(
            ['operator_id' => $op, 'user_id' => $driver->id],
            ['role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('users')->where('id', $driver->id)->update(['current_operator_id' => $op]);
        $exists = DB::table('driver_profiles')->where(['user_id' => $driver->id, 'operator_id' => $op])->exists();
        if ($exists) {
            DB::table('driver_profiles')->where(['user_id' => $driver->id, 'operator_id' => $op])->update(['status' => 'active', 'kyc_status' => 'company_verified', 'updated_at' => now()]);

            return;
        }
        DB::table('driver_profiles')->insert([
            'public_id' => (string) Str::ulid(), 'user_id' => $driver->id, 'operator_id' => $op, 'status' => 'active', 'availability' => 'offline',
            'kyc_status' => 'company_verified', 'max_active_jobs' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Tops the wallet up to the target the same way a confirmed card top-up does; running again does not stack. */
    private function fund(User $customer, int $kobo, AccountResolver $accounts, LedgerPoster $ledger): void
    {
        if ($kobo <= 0) {
            return;
        }
        $wallet = $accounts->wallet('customer', $customer->id, 1);
        $have = (int) DB::table('ledger_accounts')->where('id', $wallet)->value('balance');
        if ($have >= $kobo) {
            return;
        }
        $add = $kobo - $have;
        $ledger->post('demo-fund-'.$customer->id.'-'.Str::random(6), ['operator_id' => 1, 'kind' => 'topup', 'description' => 'demo funding'], [
            ['account_id' => $accounts->platform('gateway_clearing'), 'direction' => 'debit', 'amount' => $add],
            ['account_id' => $wallet, 'direction' => 'credit', 'amount' => $add],
        ]);
    }
}
