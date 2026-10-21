<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\RouteEstimator;
use App\Modules\Partner\ShipmentEvents;
use App\Modules\Payments\PaymentService;
use App\Modules\Payments\WalletService;
use App\Modules\Subscriptions\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Business plans: the fee from the wallet, included deliveries, the plan rate after that, priority dispatch, renewal and past due. */
class BusinessPlansTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $biz;
    private SubscriptionService $plans;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $this->mock(RouteEstimator::class, fn ($m) => $m->shouldReceive('estimate')->andReturn(['distance_m' => 7_000, 'duration_s' => 800]));
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Waterloo', 'waterloo', ST_SetSRID(ST_MakePoint(-80.52, 43.46), 4326)::geography, 'live', now(), now())");
        $city = (int) DB::table('cities')->value('id');
        [$this->op] = $this->makeProvider('company', 'CityHop');
        $plan = DB::table('pricing_plans')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $city,
            'service_type_id' => (int) DB::table('service_types')->where('code', 'same_day')->value('id'), 'name' => 'Same day', 'min_fee' => 0, 'rounding_kobo' => 1,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pricing_rules')->insert(['plan_id' => $plan, 'kind' => 'base', 'amount' => 2_000, 'condition' => null, 'priority' => 0, 'created_at' => now(), 'updated_at' => now()]);
        config(['booking.operator_id' => $this->op, 'booking.vehicles' => []]);
        $this->biz = $this->makeUser('Shop');
        $this->plans = app(SubscriptionService::class);
    }

    /** Books and pays one delivery for $user; returns [agreement row, made]. */
    private function deliver(User $user, bool $checkout = true): array
    {
        $q = $this->actingAs($user, 'sanctum')->postJson('/api/v1/public/quote', ['pickup' => ['lat' => 43.45, 'lng' => -80.49, 'line1' => 'A'], 'dropoff' => ['lat' => 43.47, 'lng' => -80.54, 'line1' => 'B'],
            'service' => 'same_day', 'size' => 'small'])->assertOk()->json('quote');
        $pub = $this->actingAs($user, 'sanctum')->postJson('/api/v1/customer/instant-bookings', ['quote' => $q, 'pickup_name' => 'Me', 'pickup_phone' => '2265550123',
            'dropoff_name' => 'You', 'dropoff_phone' => '5195550199', 'description' => 'Box'])->assertCreated()->json('agreement');
        $id = (int) DB::table('agreements')->where('public_id', $pub)->value('id');
        $made = $checkout ? app(PaymentService::class)->initiateForAgreement($id, $user->id) : app(PaymentService::class)->payWithWallet($id, $user->id);

        return [DB::table('agreements')->where('id', $id)->first(), $made];
    }

    public function test_the_fee_comes_from_the_wallet_and_deliveries_are_included(): void
    {
        $this->get('/business')->assertOk()->assertSee('Starter')->assertSee('Talk to us');
        try {
            $this->plans->subscribe($this->biz->id, 'starter');
            $this->fail('Subscribed with an empty wallet');
        } catch (RuntimeException) {
            $this->assertNull($this->plans->live($this->biz->id));
        }
        $this->fundCustomer($this->biz->id, 25_000);
        $this->actingAs($this->biz)->post('/account/plan', ['plan' => 'starter'])->assertRedirect(route('account.plan'));
        $this->assertSame(5_100, app(WalletService::class)->balance($this->biz->id));
        $this->actingAs($this->biz)->post('/account/plan', ['plan' => 'growth'])->assertSessionHas('error');

        // An included delivery: nothing to pay, no checkout, the order is made.
        [$a, $made] = $this->deliver($this->biz);
        $this->assertSame('none', $made['gateway']);
        $this->assertSame((int) $a->price, (int) $a->discount);
        $this->assertSame(1, (int) DB::table('subscriptions')->where('user_id', $this->biz->id)->value('used'));
        $this->assertSame(0, (int) DB::table('shipments')->where('id', $made['shipment_id'])->value('priority'));
        $this->assertSame(5_100, app(WalletService::class)->balance($this->biz->id));

        // A cancelled delivery gives it back.
        app(ShipmentEvents::class)->record($made['shipment_id'], 'cancelled', 'created', 'cancelled');
        $this->assertSame(0, (int) DB::table('subscriptions')->where('user_id', $this->biz->id)->value('used'));

        // After the allowance, extras are priced at the plan rate (11.00 instead of 20.00).
        DB::table('subscriptions')->where('user_id', $this->biz->id)->update(['used' => 20]);
        $this->actingAs($this->biz)->get('/account/plan')->assertOk()->assertSee('20');
        [$a2] = $this->deliver($this->biz, false);
        $this->assertSame(900, (int) $a2->discount);
        $this->assertSame('extra', DB::table('subscription_uses')->where('agreement_id', $a2->id)->value('kind'));
        $this->assertSame(4_000, app(WalletService::class)->balance($this->biz->id));
    }

    public function test_growth_gets_priority_dispatch(): void
    {
        $this->fundCustomer($this->biz->id, 60_000);
        $this->plans->subscribe($this->biz->id, 'growth');
        [, $made] = $this->deliver($this->biz);
        $this->assertSame(1, (int) DB::table('shipments')->where('id', $made['shipment_id'])->value('priority'));
    }

    public function test_renewal_past_due_pay_now_and_cancel(): void
    {
        $start = CarbonImmutable::parse('2026-10-01 10:00');
        $this->fundCustomer($this->biz->id, 20_000);
        $this->plans->subscribe($this->biz->id, 'starter', $start);
        DB::table('subscriptions')->update(['used' => 7]);

        // Month ends with 1.00 in the wallet: past due, deliveries are not covered.
        $this->plans->renewDue($start->addMonthNoOverflow()->addMinute());
        $s = $this->plans->live($this->biz->id);
        $this->assertSame('past_due', $s->status);
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->biz->id, 'type' => 'subscription.past_due'])->exists());
        $this->assertSame(1, DB::table('subscription_charges')->where('status', 'failed')->count());
        $this->plans->renewDue($start->addMonthNoOverflow()->addHours(2));
        $this->assertSame(1, DB::table('subscription_charges')->where('status', 'failed')->count());

        // Topped up: pay now starts a fresh month.
        $this->fundCustomer($this->biz->id, 20_000);
        $this->actingAs($this->biz)->post('/account/plan/pay')->assertSessionHas('success');
        $s = $this->plans->live($this->biz->id);
        $this->assertSame('active', $s->status);
        $this->assertSame(0, (int) $s->used);

        // Cancel: runs to the month end, then stops.
        $this->actingAs($this->biz)->post('/account/plan/cancel')->assertSessionHas('success');
        $this->plans->renewDue(CarbonImmutable::parse($s->current_period_end)->addMinute());
        $this->assertNull($this->plans->live($this->biz->id));
    }

    public function test_staff_edit_plans(): void
    {
        $staff = $this->makeUser('Ops');
        \Spatie\Permission\Models\Permission::findOrCreate('Manage business plans', 'web');
        $this->actingAs($this->biz)->get('/ops/plans')->assertForbidden();
        $staff->givePermissionTo('Manage business plans');
        $this->actingAs($staff)->get('/ops/plans')->assertOk()->assertSee('Growth');
        $id = DB::table('subscription_plans')->where('code', 'starter')->value('id');
        $this->actingAs($staff)->post("/ops/plans/{$id}", ['name' => 'Starter', 'price' => '149', 'included_deliveries' => 15, 'overage_price' => '', 'active' => 1, 'features' => "One\nTwo"])
            ->assertSessionHas('success');
        $p = DB::table('subscription_plans')->where('id', $id)->first();
        $this->assertSame(14_900, (int) $p->price);
        $this->assertNull($p->overage_price);
    }
}
