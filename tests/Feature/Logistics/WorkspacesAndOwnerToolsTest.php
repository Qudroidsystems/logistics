<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketplace\DeliveryService;
use App\Modules\Payments\PaymentService;
use App\Modules\Settlements\PayoutService;
use App\Modules\Settlements\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/**
 * The platform owner and the providers each get their own dashboard and tools; one login that is both
 * (or also runs a shop) switches between them.
 */
class WorkspacesAndOwnerToolsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $providerOp;
    private User $owner;
    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.mode' => 'sandbox', 'services.paystack.test_secret_key' => 'sk_test_unit_secret']);
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        Http::fake(['api.paystack.co/*' => Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_test', 'status' => 'pending']])]);
        [$this->providerOp, $this->owner] = $this->makeProvider('company', 'Swift Haulage');
        DB::table('users')->where('id', $this->owner->id)->update(['must_change_password' => false]);
        $this->owner->refresh();
        $this->customerId = $this->makeUser('Ada Customer')->id;
    }

    private function staff(array $perms): User
    {
        $u = User::factory()->create(['must_change_password' => false]);
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo($perms);

        return $u;
    }

    /** One completed, confirmed job: provider wallet 264,000 kobo, platform fee 36,000. */
    private function earnOneJob(): int
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->owner->id);
        app(PaymentService::class)->payWithWallet($id, $this->customerId);
        $shipmentId = (int) DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $id)->value('shipments.id');
        app(DeliveryService::class)->markDelivered($shipmentId);
        app(DeliveryService::class)->confirm($shipmentId, $this->customerId);

        return $shipmentId;
    }

    private function bank(): int
    {
        return DB::table('bank_accounts')->insertGetId([
            'public_id' => (string) Str::ulid(), 'owner_type' => 'operator', 'owner_id' => $this->providerOp, 'bank_code' => '058', 'bank_name' => 'GTBank',
            'account_number' => Crypt::encryptString('0123456789'), 'account_number_last4' => '6789', 'account_name' => 'SWIFT HAULAGE LTD',
            'verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_owner_has_a_platform_dashboard_and_the_provider_its_own(): void
    {
        $this->earnOneJob();
        $boss = $this->staff(['dashboard', 'View delivery', 'View vendor', 'Approve withdrawal', 'View settlement']);

        $this->actingAs($boss)->get('/dashboard')->assertOk()
            ->assertSee('Platform overview')->assertSee('Commission today')->assertSee('Providers live')->assertSee('Needs your attention')
            ->assertSee('Top providers this month')->assertSee('Swift Haulage')->assertSee('₦360'); // the platform's fee on that job
        $this->actingAs($boss)->get('/ops')->assertRedirect(route('dashboard'));

        // The provider sees its own business, not the platform.
        $this->actingAs($this->owner)->get('/dashboard')->assertForbidden();
        $this->actingAs($this->owner)->get('/provider')->assertOk()
            ->assertSee('Waiting for a rider')->assertSee('Latest jobs')->assertSee('Quick actions')->assertSee('Platform fee')
            ->assertDontSee('Top providers this month');
    }

    public function test_someone_who_also_runs_a_shop_still_reaches_their_provider_workspace_and_can_switch(): void
    {
        [$shopOp] = $this->makeProvider('merchant', 'GozakMart');
        $merchant = DB::table('merchants')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $shopOp, 'merchant_code' => 'GZ1', 'display_name' => 'GozakMart',
            'status' => 'verified', 'settlement_mode' => 'pay_at_checkout', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('operator_members')->insert(['operator_id' => $shopOp, 'user_id' => $this->owner->id, 'role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        // Being made owner of the shop moved their current account to it.
        DB::table('users')->where('id', $this->owner->id)->update(['current_operator_id' => $shopOp]);
        $this->owner->refresh();

        $this->actingAs($this->owner)->get('/provider')->assertOk()->assertSee('Swift Haulage');
        $this->assertSame($this->providerOp, (int) DB::table('users')->where('id', $this->owner->id)->value('current_operator_id'));

        // The switcher lists both businesses and their own account.
        $page = $this->actingAs($this->owner->fresh())->get('/provider')->assertSee('Switch workspace');
        $page->assertSee('GozakMart')->assertSee('Delivery provider')->assertSee('Shop (merchant)')->assertSee('My account');

        $this->actingAs($this->owner->fresh())->post('/workspace', ['key' => "shop:{$shopOp}"])->assertRedirect(route('merchant.home'));
        $this->assertSame($shopOp, (int) DB::table('users')->where('id', $this->owner->id)->value('current_operator_id'));
        $this->actingAs($this->owner->fresh())->post('/workspace', ['key' => "op:{$this->providerOp}"])->assertRedirect(route('provider.dashboard'));

        // Nobody can switch into a business they are not part of, or into the back office without the right.
        [$other] = $this->makeProvider('company', 'Someone Else');
        $this->actingAs($this->owner->fresh())->post('/workspace', ['key' => "op:{$other}"])->assertForbidden();
        $this->actingAs($this->owner->fresh())->post('/workspace', ['key' => 'platform'])->assertForbidden();
    }

    public function test_staff_approve_and_reject_payouts_from_the_back_office(): void
    {
        Queue::fake();
        DB::table('platform_settings')->updateOrInsert(['operator_id' => null, 'scope' => 'global', 'scope_id' => null, 'key' => 'payout.auto_approve_max_kobo'], ['value' => json_encode(1), 'created_at' => now(), 'updated_at' => now()]);
        $this->earnOneJob();
        $svc = app(PayoutService::class);
        $a = $svc->request($this->providerOp, $this->owner->id, 120_000, $this->bank());
        $b = $svc->request($this->providerOp, $this->owner->id, 100_000, $this->bank());

        $fin = $this->staff(['dashboard', 'Approve withdrawal']);
        $this->actingAs($fin)->get('/dashboard')->assertSee('Payout requests to approve');
        $this->actingAs($fin)->get('/ops/payouts')->assertOk()->assertSee('Swift Haulage')->assertSee('₦1,200')->assertSee('····6789');

        $this->actingAs($fin)->post("/ops/payouts/{$a}/approve")->assertSessionHas('success');
        $this->assertSame('approved', DB::table('payout_requests')->find($a)->status);
        $this->actingAs($fin)->post("/ops/payouts/{$b}/reject", ['reason' => 'Name mismatch'])->assertSessionHas('success');
        $this->assertSame('failed', DB::table('payout_requests')->find($b)->status);
        $this->assertSame(144_000, $this->walletBalance('operator', $this->providerOp)); // 264,000 − the approved 120,000

        // Without the right, nothing.
        $this->actingAs($this->staff(['dashboard']))->get('/ops/payouts')->assertForbidden();
    }

    public function test_staff_set_a_special_fee_for_one_provider(): void
    {
        $boss = $this->staff(['dashboard', 'Manage commission', 'View vendor']);
        $this->actingAs($boss)->get('/ops/settings')->assertOk()->assertSee('Special fees')->assertSee('Swift Haulage');
        $this->actingAs($boss)->post('/ops/settings/commission', ['applies_to' => 'provider', 'operator_id' => $this->providerOp, 'percent' => 5, 'min_naira' => 100])
            ->assertSessionHas('success');
        $rule = DB::table('commission_rules')->where('operator_id', $this->providerOp)->first();
        $this->assertSame([500, 10000, true], [(int) $rule->rate_bp, (int) $rule->min_fee, (bool) $rule->active]);
        $this->assertSame(15_000, app(\App\Modules\Marketplace\CommissionResolver::class)->resolve($this->providerOp, 'company', $this->serviceTypeId(), null, 300_000)['fee']);

        // Saving again replaces it; switching off falls back to the platform fee.
        $this->actingAs($boss)->post('/ops/settings/commission', ['applies_to' => 'provider', 'operator_id' => $this->providerOp, 'percent' => 7]);
        $this->assertSame(1, DB::table('commission_rules')->where('operator_id', $this->providerOp)->count());
        $this->actingAs($boss)->post("/ops/settings/commission/{$rule->id}/toggle")->assertSessionHas('success');
        $this->assertFalse((bool) DB::table('commission_rules')->find($rule->id)->active);

        $this->actingAs($boss)->get('/ops/providers/'.DB::table('operators')->where('id', $this->providerOp)->value('public_id'))->assertOk()
            ->assertSee('Swift Haulage')->assertSee('Commission')->assertSee('Riders')->assertSee('Recent jobs');
        $this->actingAs($this->staff(['dashboard', 'View vendor']))->post('/ops/settings/commission', ['applies_to' => 'type', 'operator_type' => 'company', 'percent' => 3])->assertForbidden();
    }

    public function test_staff_cancel_a_delivery_from_the_order_page(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->owner->id);
        app(PaymentService::class)->payWithWallet($id, $this->customerId);
        $order = DB::table('orders')->where('agreement_id', $id)->first();

        $ops = $this->staff(['dashboard', 'View delivery', 'Cancel delivery']);
        $this->actingAs($ops)->get("/ops/orders/{$order->public_id}")->assertOk()->assertSee('Cancel this delivery');
        $this->actingAs($ops)->post("/ops/orders/{$order->public_id}/cancel", ['reason' => 'fraud'])->assertSessionHas('success');
        $this->assertSame('cancelled', DB::table('orders')->find($order->id)->status);
        $this->assertSame(1_000_000, $this->walletBalance('customer', $this->customerId));
        $this->actingAs($ops)->get("/ops/orders/{$order->public_id}")->assertDontSee('Cancel this delivery');
        $this->assertLedgerBalanced();
    }

    public function test_providers_see_their_statements_and_reviews(): void
    {
        $shipment = $this->earnOneJob();
        $day = now()->toDateString();
        app(SettlementService::class)->generate($this->providerOp, $day, $day);
        DB::table('ratings')->insert(['shipment_id' => $shipment, 'rater_type' => 'customer', 'rater_id' => $this->customerId, 'ratee_type' => 'operator', 'ratee_id' => $this->providerOp,
            'score' => 5, 'comment' => 'Fast and careful', 'is_public' => true, 'moderation_status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->owner)->get('/provider/statements')->assertOk()
            ->assertSee('Weekly statements')->assertSee('₦3,000')->assertSee('₦360')->assertSee('₦2,640');
        $this->actingAs($this->owner)->get('/provider/reviews')->assertOk()->assertSee('Fast and careful')->assertSee('5.0');

        // A driver on the team cannot see the money.
        $driver = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $this->providerOp, 'user_id' => $driver->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $driver->id)->update(['current_operator_id' => $this->providerOp]);
        $this->actingAs($driver->fresh())->get('/provider/statements')->assertForbidden();
    }
}
