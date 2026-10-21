<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\RouteEstimator;
use App\Modules\Recurring\RecurringDeliveryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Recurring deliveries: set a schedule, each trip is booked ahead and paid from the wallet; short wallets and failures. */
class RecurringDeliveriesTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $biz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $this->mock(RouteEstimator::class, fn ($m) => $m->shouldReceive('estimate')->andReturn(['distance_m' => 5_000, 'duration_s' => 600]));
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Waterloo', 'waterloo', ST_SetSRID(ST_MakePoint(-80.52, 43.46), 4326)::geography, 'live', now(), now())");
        $city = (int) DB::table('cities')->value('id');
        [$this->op] = $this->makeProvider('company', 'CityHop');
        $plan = DB::table('pricing_plans')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $city,
            'service_type_id' => (int) DB::table('service_types')->where('code', 'scheduled')->value('id'), 'name' => 'Scheduled', 'min_fee' => 0, 'rounding_kobo' => 1,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pricing_rules')->insert(['plan_id' => $plan, 'kind' => 'base', 'amount' => 1_500, 'condition' => null, 'priority' => 0, 'created_at' => now(), 'updated_at' => now()]);
        config(['booking.operator_id' => $this->op, 'booking.vehicles' => []]);
        $this->biz = $this->makeUser('Bakery');
    }

    private function schedule(array $o = []): void
    {
        $this->actingAs($this->biz)->post('/account/recurring', $o + [
            'name' => 'Daily bread', 'from' => 'address',
            'pickup_lat' => 43.45, 'pickup_lng' => -80.49, 'pickup_line1' => '1 King St', 'pickup_name' => 'Bakery', 'pickup_phone' => '2265550123',
            'dropoff_lat' => 43.47, 'dropoff_lng' => -80.54, 'dropoff_line1' => '200 University Ave', 'dropoff_name' => 'Cafe', 'dropoff_phone' => '5195550199',
            'size' => 'medium', 'description' => 'Bread', 'pattern' => 'daily', 'time' => '10:00', 'starts_on' => now()->toDateString(),
        ])->assertRedirect(route('account.recurring'));
    }

    public function test_trips_are_booked_ahead_and_paid(): void
    {
        $this->actingAs($this->biz)->get('/account/book')->assertOk()->assertSee('Get my price');
        $this->schedule();
        $r = DB::table('recurring_deliveries')->first();
        $this->assertSame('active', $r->status);
        $this->fundCustomer($this->biz->id, 10_000);

        $svc = app(RecurringDeliveryService::class);
        $first = CarbonImmutable::parse($r->next_run_at);
        $svc->runDue($first->subHours(10));
        $run = DB::table('recurring_delivery_runs')->first();
        $this->assertSame('paid', $run->status);
        $this->assertSame(1_500, (int) $run->amount);
        $this->assertNotNull($run->order_id);
        $this->assertTrue(CarbonImmutable::parse(DB::table('recurring_deliveries')->value('next_run_at'))->equalTo($first->addDay()));

        // Running again books nothing twice.
        $svc->runDue($first->subHours(10));
        $this->assertSame(1, DB::table('recurring_delivery_runs')->count());
        $this->actingAs($this->biz)->get('/account/recurring')->assertOk()->assertSee('Daily bread');
    }

    public function test_short_wallet_asks_for_payment_and_failures_pause(): void
    {
        $this->schedule();
        $svc = app(RecurringDeliveryService::class);
        $first = CarbonImmutable::parse(DB::table('recurring_deliveries')->value('next_run_at'));
        $svc->runDue($first->subHours(10));
        $this->assertSame('needs_payment', DB::table('recurring_delivery_runs')->value('status'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->biz->id, 'type' => 'recurring.needs_payment'])->exists());
        $this->assertSame('locked', DB::table('agreements')->where('customer_id', $this->biz->id)->value('status'));

        // No price any more: three failures in a row pause the schedule.
        config(['booking.operator_id' => null]);
        for ($i = 1; $i <= 3; $i++) {
            $svc->runDue($first->addDays($i)->subHours(10));
        }
        $this->assertSame(3, DB::table('recurring_delivery_runs')->where('status', 'failed')->count());
        $this->assertSame('paused', DB::table('recurring_deliveries')->value('status'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->biz->id, 'type' => 'recurring.paused'])->exists());

        // Delete.
        $pid = DB::table('recurring_deliveries')->value('public_id');
        $this->actingAs($this->biz)->post("/account/recurring/{$pid}/status", ['status' => 'ended'])->assertSessionHas('success');
        $this->assertSame('ended', DB::table('recurring_deliveries')->value('status'));
    }

    public function test_weekly_needs_days_and_addresses(): void
    {
        $this->actingAs($this->biz)->post('/account/recurring', ['name' => 'x', 'from' => 'address', 'dropoff_lat' => 43.47, 'dropoff_lng' => -80.54, 'dropoff_line1' => 'a',
            'dropoff_name' => 'a', 'dropoff_phone' => '5195550199', 'size' => 'small', 'description' => 'x', 'pattern' => 'weekly', 'time' => '10:00', 'starts_on' => now()->toDateString()])
            ->assertSessionHasErrors(['days', 'pickup_lat']);
    }
}
