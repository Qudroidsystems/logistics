<?php

namespace Tests\Support;

use App\Models\User;
use App\Modules\Marketplace\AgreementService;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Database\Seeders\LogisticsFoundationSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds just enough world for a money-path test: a customer with a funded wallet, a provider operator
 * with an owner, and locked agreements between them. Everything goes through the real services, so a test
 * that passes has exercised the same ledger code production uses.
 */
trait BuildsMarketplace
{
    protected int $platformId = 1;

    protected function seedFoundation(): void
    {
        $this->seed(LogisticsFoundationSeeder::class);
    }

    protected function makeUser(string $name = 'Test User'): User
    {
        return User::factory()->create(['name' => $name]);
    }

    /** @return array{0:int,1:User} operator id and its owner */
    protected function makeProvider(string $type = 'company', string $name = 'Swift Haulage'): array
    {
        $owner = $this->makeUser("{$name} Owner");
        $op = DB::table('operators')->insertGetId([
            'public_id' => (string) Str::ulid(), 'type' => $type, 'legal_name' => $name, 'display_name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)), 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('operator_members')->insert(['operator_id' => $op, 'user_id' => $owner->id, 'role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $owner->id)->update(['current_operator_id' => $op]);

        return [$op, $owner->fresh()];
    }

    /** Puts money in a customer's wallet the same way a confirmed card top-up does. */
    protected function fundCustomer(int $userId, int $kobo): void
    {
        $accounts = app(AccountResolver::class);
        app(LedgerPoster::class)->post("test-fund-{$userId}-".Str::random(6), ['operator_id' => 1, 'kind' => 'topup', 'description' => 'test funding'], [
            ['account_id' => $accounts->platform('gateway_clearing'), 'direction' => 'debit', 'amount' => $kobo],
            ['account_id' => $accounts->wallet('customer', $userId, 1), 'direction' => 'credit', 'amount' => $kobo],
        ]);
    }

    protected function serviceTypeId(): int
    {
        return (int) DB::table('service_types')->orderBy('id')->value('id');
    }

    /** A locked delivery agreement. 12% platform fee comes from the seeded default rule. */
    protected function lockedAgreement(int $customerId, int $providerOp, int $providerUserId, int $price = 300_000, int $goods = 0, int $tip = 0, array $extraTerms = []): int
    {
        $svc = app(AgreementService::class);
        $id = $svc->propose($customerId, $providerOp, [
            'terms' => $extraTerms + [
                'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.8, 'lng' => 5.9],
                'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92],
                'packages' => [['description' => 'Box']],
            ],
            'price' => $price, 'goods_budget' => $goods, 'tip' => $tip, 'distance_m' => 4200, 'service_type_id' => $this->serviceTypeId(),
        ], $customerId);
        $v = (int) DB::table('agreements')->where('id', $id)->value('terms_version');
        $svc->accept($id, 'customer', $v);
        $svc->accept($id, 'provider', $v);

        return $id;
    }

    protected function walletBalance(string $ownerType, int $ownerId): int
    {
        $op = $ownerType === 'operator' ? $ownerId : 1;

        return (int) DB::table('ledger_accounts')->where('id', app(AccountResolver::class)->wallet($ownerType, $ownerId, $op))->value('balance');
    }

    protected function platformBalance(string $purpose): int
    {
        return (int) DB::table('ledger_accounts')->where('id', app(AccountResolver::class)->platform($purpose))->value('balance');
    }

    protected function escrowBalance(int $agreementId): int
    {
        return (int) DB::table('ledger_accounts')->where(['owner_type' => 'agreement', 'owner_id' => $agreementId, 'purpose' => 'order_escrow'])->value('balance');
    }

    /** The books must always balance: every ledger transaction nets to zero. */
    protected function assertLedgerBalanced(): void
    {
        $bad = DB::select("SELECT transaction_id FROM ledger_entries GROUP BY transaction_id HAVING SUM(CASE WHEN direction = 'debit' THEN amount ELSE -amount END) <> 0");
        $this->assertSame([], $bad, 'Unbalanced ledger transaction found.');
    }
}
