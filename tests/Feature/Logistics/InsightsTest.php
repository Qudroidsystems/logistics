<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Backoffice\ReportService;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Eta\EtaService;
use App\Modules\Insights\InsightsService;
use App\Modules\Payments\PaymentService;
use App\Modules\Pricing\ProviderCostingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Profit per job, rider reliability and fleet use on the provider's Insights page. */
class InsightsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;
    private User $rider;
    private int $dp;
    private int $vehicle;

    /** ₦1,000 a litre at 10 L per 100 km, ₦20 a km upkeep, ₦50 other, half the fuel and upkeep again for the empty trip back, ₦500 labour. */
    private const PROFILE = [
        'name' => 'Bike', 'pricing_method' => 'higher_of', 'fuel_price_per_litre' => 100_000, 'fuel_l_per_100km' => 10, 'maintenance_per_km' => 2_000,
        'other_per_job' => 5_000, 'return_trip_bp' => 5_000, 'labour_per_job' => 50_000, 'rate_per_km' => 10_000, 'rounding_kobo' => 1,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = $this->makeUser('Ada');
        $this->fundCustomer($this->customer->id, 5_000_000);
        $this->rider = $this->member('Rider Bayo', 'driver');
        $this->vehicle = DB::table('vehicles')->insertGetId([
            'public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'vehicle_type_id' => DB::table('vehicle_types')->where('code', 'motorbike')->value('id'),
            'plate' => 'KGL111AA', 'make' => 'Bajaj', 'model' => 'Boxer', 'ownership' => 'operator_owned', 'status' => 'active',
            'insurance_expires_on' => now()->addDays(10)->toDateString(), 'roadworthy_expires_on' => now()->addYear()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $this->rider->id,
            'status' => 'active', 'availability' => 'online', 'current_vehicle_id' => $this->vehicle, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function member(string $name, string $role): User
    {
        $u = $this->makeUser($name);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $u->id, 'role' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $u->id)->update(['current_operator_id' => $this->op]);

        return $u->fresh();
    }

    /** A job the rider finished: delivered (10 km, paid ₦800 to the rider) or a failed attempt. */
    private function finished(string $status = 'delivered', ?int $lateBy = null): object
    {
        $agreement = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000, 0, 0, ['packages' => [['description' => 'Box']]]);
        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.*']);
        $done = now()->subDay();
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->dp,
            'vehicle_id' => $this->vehicle, 'status' => 'completed', 'payout_amount' => 80_000, 'completed_at' => $done->toIso8601String(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $s->id)->update(['status' => $status, 'distance_m' => 10_000, 'duration_s' => 1_800,
            'delivered_at' => $status === 'delivered' ? $done->toIso8601String() : null]);
        $drop = DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->first();
        if ($status === 'delivered') {
            DB::table('shipment_stops')->where('id', $drop->id)->update(['status' => 'completed', 'completed_at' => $done->toIso8601String()]);
            if ($lateBy !== null) {
                // The receiver was first told the rider would come $lateBy minutes before they did.
                DB::table('stop_etas')->insert(['stop_id' => $drop->id, 'shipment_id' => $s->id, 'duration_s' => 600, 'distance_m' => 3000, 'provider' => 'osrm',
                    'from_lat' => 7.8, 'from_lng' => 5.9, 'computed_at' => $done->toIso8601String(), 'due_at' => $done->toIso8601String(),
                    'promised_at' => $done->subMinutes($lateBy)->toIso8601String()]);
            }
        }

        return $s;
    }

    public function test_profit_counts_rider_pay_instead_of_labour_and_flags_losses(): void
    {
        $i = app(InsightsService::class);
        $p = self::PROFILE + array_fill_keys(ProviderCostingService::FIELDS, 0);
        $p = array_merge($p, self::PROFILE);

        // 10 km: fuel ₦1,000, upkeep ₦200, other ₦50, return trip ₦600 = ₦1,850 running; rider paid ₦800.
        $j = $i->jobProfit(300_000, 80_000, $p, 10_000, 1_800);
        $this->assertSame(['earned' => 300_000, 'rider_pay' => 80_000, 'running_cost' => 185_000, 'cost' => 265_000, 'profit' => 35_000, 'margin_bp' => 1_166, 'flag' => null, 'costed' => true], $j);
        // Without the rider's pay the profile's ₦500 labour stands in.
        $this->assertSame(65_000, $i->jobProfit(300_000, null, $p, 10_000, 1_800)['profit']);
        $this->assertSame('loss', $i->jobProfit(200_000, 80_000, $p, 10_000, 1_800)['flag']);
        $this->assertSame('thin_margin', $i->jobProfit(280_000, 80_000, $p, 10_000, 1_800)['flag']);
        // No cost profile: only the rider's pay is a cost.
        $this->assertSame(['cost' => 80_000, 'profit' => 220_000, 'costed' => false], array_intersect_key($i->jobProfit(300_000, 80_000, null, 10_000, 1_800), array_flip(['cost', 'profit', 'costed'])));
    }

    public function test_reliability_score_and_paper_warnings(): void
    {
        $m = ['offers' => 10, 'offers_taken' => 5, 'delivered' => 9, 'failed' => 1, 'dropped' => 0, 'timed' => 10, 'on_time' => 8];
        $this->assertSame(['score' => 78, 'enough' => true, 'on_time_rate' => 80.0, 'finish_rate' => 90.0, 'accept_rate' => 50.0], InsightsService::reliabilityScore($m));
        $none = array_fill_keys(array_keys($m), 0);
        $this->assertSame(['score' => 70, 'enough' => false], array_intersect_key(InsightsService::reliabilityScore($none), array_flip(['score', 'enough'])));

        $today = CarbonImmutable::parse('2026-10-15');
        $this->assertSame(['label' => 'Insurance expired 3 days ago', 'level' => 'danger'], InsightsService::paper('Insurance', '2026-10-12', $today));
        $this->assertSame(['label' => 'Insurance expires in 1 day', 'level' => 'warning'], InsightsService::paper('Insurance', '2026-10-16', $today));
        $this->assertNull(InsightsService::paper('Insurance', '2027-01-01', $today));
        $this->assertSame('info', InsightsService::paper('Insurance', null, $today)['level']);
    }

    public function test_the_first_arrival_time_given_is_kept_as_the_promise(): void
    {
        $s = $this->finished('in_transit');
        $stop = DB::selectOne("SELECT id, type, ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng FROM shipment_stops WHERE shipment_id = ? AND type = 'dropoff'", [$s->id]);
        $eta = app(EtaService::class);

        $eta->forStop($s->id, $stop, 7.80, 5.92, now()->toIso8601String(), 'motorbike', false);
        $this->assertNull(DB::table('stop_etas')->where('stop_id', $stop->id)->value('promised_at'));
        $first = $eta->forStop($s->id, $stop, 7.80, 5.92, now()->toIso8601String(), 'motorbike', true);
        DB::table('stop_etas')->where('stop_id', $stop->id)->update(['computed_at' => now()->subMinutes(2)->toIso8601String()]);
        $eta->rebase($stop->id, CarbonImmutable::now()->addHour());
        $eta->forStop($s->id, $stop, 7.81, 5.92, now()->toIso8601String(), 'motorbike', true);

        $row = DB::table('stop_etas')->where('stop_id', $stop->id)->first();
        $this->assertNotNull($row->promised_at);
        $this->assertNotEquals(CarbonImmutable::parse($row->due_at)->getTimestamp(), CarbonImmutable::parse($row->promised_at)->getTimestamp());
        $this->assertEqualsWithDelta(CarbonImmutable::parse($first['due_at'])->getTimestamp(), CarbonImmutable::parse($row->promised_at)->getTimestamp(), 1);
    }

    public function test_reports_show_profit_rider_reliability_and_fleet_by_role(): void
    {
        app(ProviderCostingService::class)->saveProfile($this->op, self::PROFILE);
        $on = $this->finished('delivered', 5);      // 5 minutes after the promise: on time with the grace
        $this->finished('delivered', 30);           // half an hour late
        $this->finished('failed_attempt');
        $net = (int) DB::table('agreements')->join('orders', 'orders.agreement_id', '=', 'agreements.id')->join('shipments', 'shipments.order_id', '=', 'orders.id')
            ->where('shipments.id', $on->id)->value('agreements.provider_net');

        $to = CarbonImmutable::now()->endOfDay();
        $from = $to->subDays(29)->startOfDay();
        $profit = app(InsightsService::class)->profit($this->op, $from, $to);
        $this->assertSame(2, $profit['totals']['jobs']);
        $this->assertSame(2 * ($net - 265_000), $profit['totals']['profit']);
        $this->assertSame('Bike', $profit['jobs'][0]['profile']);
        $this->assertSame([$this->dp => ['timed' => 2, 'on_time' => 1]], app(InsightsService::class)->onTime($this->op, $from, $to));

        $fleet = app(InsightsService::class)->fleet($this->op, $from, $to);
        $this->assertEquals(['jobs' => 3, 'km' => 30.0, 'days_used' => 1, 'rider' => 'Rider Bayo'], array_intersect_key($fleet['vehicles'][0], array_flip(['jobs', 'km', 'days_used', 'rider'])));
        $this->assertSame('Insurance expires in 10 days', $fleet['vehicles'][0]['papers'][0]['label']);

        $reports = app(ReportService::class);
        $table = $reports->profit($this->op, $from, $to);
        $this->assertSame(['Date', 'Order', 'Rider', 'Distance', 'Earned', 'Rider pay', 'Running cost', 'Profit', 'Margin', 'Flag', 'Cost profile'], $table['headings']);
        $r = $table['rows'][0];
        $this->assertSame([10.0, round($net / 100, 2), 800.0, 1850.0, round(($net - 265_000) / 100, 2)], [$r[3], (float) $r[4], $r[5], $r[6], $r[7]]);
        $drivers = $reports->drivers($this->op, $from, $to);
        $row = $drivers['rows'][0];
        $this->assertSame(50.0, $row[array_search('On time', $drivers['headings'], true)]);
        $this->assertNull($row[array_search('Reliability', $drivers['headings'], true)]);   // under 5 jobs: not settled yet

        $this->actingAs($this->owner)->get('/provider/reports/profit')->assertOk()->assertSee('Profit per job')->assertSee('Bike');
        $this->actingAs($this->owner)->get('/provider/reports/fleet')->assertOk()->assertSee('KGL111AA')->assertSee('Insurance expires in 10 days');
        $this->actingAs($this->owner)->get('/provider/reports/drivers')->assertOk()->assertSee('Reliability');
        $this->actingAs($this->owner)->get('/provider/reports/profit/export.xlsx')->assertOk();

        // Finance sees profit but not the fleet; a dispatcher the fleet but not profit.
        $finance = $this->member('Fola Finance', 'finance');
        $this->actingAs($finance)->get('/provider/reports/profit')->assertOk();
        $this->actingAs($finance)->get('/provider/reports/fleet')->assertForbidden();
        $dispatch = $this->member('Dee Dispatch', 'dispatcher');
        $this->actingAs($dispatch)->get('/provider/reports/fleet')->assertOk();
        $this->actingAs($dispatch)->get('/provider/reports/profit')->assertForbidden();
    }

    public function test_without_a_cost_profile_the_profit_report_says_so(): void
    {
        $this->finished();
        $this->actingAs($this->owner)->get('/provider/reports/profit')->assertOk()->assertSee('No cost profile yet');
    }
}
