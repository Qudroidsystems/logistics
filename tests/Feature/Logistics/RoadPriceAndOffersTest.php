<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\RouteEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Both ways to price a trip: the instant road-based price, or offers from providers with that price as the going rate. */
class RoadPriceAndOffersTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private User $customer;
    private int $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        $this->seedFoundation();
        config(['platform.mode' => 'both']);
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $this->mock(RouteEstimator::class, fn ($m) => $m->shouldReceive('estimate')->andReturn(['distance_m' => 9_000, 'duration_s' => 1_080]));
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Waterloo', 'waterloo', ST_SetSRID(ST_MakePoint(-80.52, 43.46), 4326)::geography, 'live', now(), now())");
        $this->city = (int) DB::table('cities')->value('id');
        [$op] = $this->makeProvider('company', 'CityHop');
        $plan = DB::table('pricing_plans')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $op, 'city_id' => $this->city,
            'service_type_id' => (int) DB::table('service_types')->where('code', 'same_day')->value('id'), 'name' => 'Same day', 'min_fee' => 0, 'rounding_kobo' => 1,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pricing_rules')->insert([
            ['plan_id' => $plan, 'kind' => 'base', 'amount' => 500, 'condition' => null, 'priority' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['plan_id' => $plan, 'kind' => 'per_km', 'amount' => 150, 'condition' => null, 'priority' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        config(['booking.operator_id' => $op, 'booking.vehicles' => []]);
        $this->customer = $this->makeUser('Ada');
    }

    public function test_instant_price_can_go_to_providers_with_the_going_rate(): void
    {
        $q = $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/public/quote', ['pickup' => ['lat' => 43.45, 'lng' => -80.49, 'line1' => 'A'],
            'dropoff' => ['lat' => 43.47, 'lng' => -80.54, 'line1' => 'B'], 'service' => 'same_day', 'size' => 'small'])->assertOk()->json();
        $this->assertSame(500 + 9 * 150, $q['subtotal']);
        $this->getJson('/api/v1/public/quote-options')->assertJsonPath('offers', true);

        $details = ['pickup_name' => 'Ada', 'pickup_phone' => '2265550123', 'dropoff_name' => 'Bo', 'dropoff_phone' => '5195550199', 'description' => 'Books'];
        $res = $this->actingAs($this->customer)->post("/book/{$q['quote']}", $details + ['mode' => 'offers']);
        $req = DB::table('service_requests')->where('customer_id', $this->customer->id)->first();
        $res->assertRedirect(route('account.request', $req->public_id));
        $this->assertSame('instant', $req->origin);
        $this->assertSame(9_000, (int) $req->distance_m);
        $this->assertSame(1_080, (int) $req->route_duration_s);
        $this->assertSame((int) $q['total'], (int) $req->reference_price);
        $this->assertSame(0, DB::table('agreements')->where('customer_id', $this->customer->id)->count());
        $this->actingAs($this->customer)->get("/account/requests/{$req->public_id}")->assertOk()->assertSee('Instant price')->assertSee('about 18 min drive');

        // The app's way.
        $q2 = $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/public/quote', ['pickup' => ['lat' => 43.45, 'lng' => -80.49], 'dropoff' => ['lat' => 43.47, 'lng' => -80.54],
            'service' => 'same_day', 'size' => 'small'])->json('quote');
        $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/instant-bookings/offers', ['quote' => $q2] + $details)->assertCreated()->assertJsonStructure(['request']);
    }

    public function test_company_mode_keeps_the_instant_price_only(): void
    {
        config(['platform.mode' => 'company', 'platform.house_operator_id' => (int) config('booking.operator_id')]);
        $q = $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/public/quote', ['pickup' => ['lat' => 43.45, 'lng' => -80.49], 'dropoff' => ['lat' => 43.47, 'lng' => -80.54],
            'service' => 'same_day', 'size' => 'small'])->json('quote');
        $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/instant-bookings/offers', ['quote' => $q, 'pickup_name' => 'Ada', 'pickup_phone' => '2265550123',
            'dropoff_name' => 'Bo', 'dropoff_phone' => '5195550199', 'description' => 'Books'])->assertStatus(422);
    }
}
