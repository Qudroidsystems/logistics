<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Payments\PaymentService;
use App\Modules\Tracking\ParcelCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Cost profiles, server-side estimates that stay frozen, and parcel codes on new shipments. */
class ProviderPricingTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
    }

    private function profile(array $over = []): array
    {
        return $over + [
            'name' => 'Motorbike', 'pricing_method' => 'higher_of', 'base_fee' => 50_000, 'rate_per_km' => 15_000, 'min_fee' => 150_000,
            'fuel_price_per_litre' => 88_000, 'fuel_l_per_100km' => 10, 'labour_per_job' => 80_000, 'maintenance_per_km' => 2_000,
            'other_per_job' => 27_000, 'markup_bp' => 2_000, 'rounding_kobo' => 100,
        ];
    }

    public function test_a_provider_sets_a_profile_and_gets_a_frozen_estimate(): void
    {
        Sanctum::actingAs($this->owner);
        $p = $this->postJson('/api/v1/provider/cost-profiles', $this->profile())->assertCreated()->json();
        $this->assertTrue((bool) $p['is_default']);

        $e = $this->postJson('/api/v1/provider/estimates', ['distance_km' => 18, 'duration_min' => 40])->assertCreated()->json();
        $this->assertSame(361_700, $e['suggested_price']);
        $this->assertSame(301_400, $e['operating_cost']);
        $this->assertSame('manual', $e['route']['provider']);
        $this->assertGreaterThan(0, $e['platform_fee']); // seeded 12% marketplace fee

        // Fuel goes up: new estimates change, the saved one does not.
        $this->putJson("/api/v1/provider/cost-profiles/{$p['public_id']}", $this->profile(['fuel_price_per_litre' => 120_000]))->assertOk()->assertJsonPath('version', 2);
        $again = $this->postJson('/api/v1/provider/estimates', ['distance_km' => 18, 'duration_min' => 40])->assertCreated()->json();
        $this->assertGreaterThan($e['operating_cost'], $again['operating_cost']);
        $this->getJson("/api/v1/provider/estimates/{$e['estimate']}")->assertOk()
            ->assertJsonPath('suggested_price', 361_700)->assertJsonPath('profile_version', 1);
    }

    public function test_estimates_route_over_stops(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/provider/cost-profiles', $this->profile())->assertCreated();

        $one = $this->postJson('/api/v1/provider/estimates', ['stops' => [['lat' => 6.45, 'lng' => 3.39], ['lat' => 6.52, 'lng' => 3.37]]])->assertCreated()->json();
        $round = $this->postJson('/api/v1/provider/estimates', ['stops' => [['lat' => 6.45, 'lng' => 3.39], ['lat' => 6.52, 'lng' => 3.37]], 'round_trip' => true])->assertCreated()->json();

        $this->assertGreaterThan(5_000, $one['distance_m']);
        $this->assertCount(2, $round['route']['legs']);
        $this->assertEqualsWithDelta($one['distance_m'] * 2, $round['distance_m'], 2);
    }

    public function test_no_profile_and_other_operators_are_refused(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/provider/estimates', ['distance_km' => 5])->assertStatus(422);

        [, $other] = $this->makeProvider('company', 'Other Co');
        $p = $this->postJson('/api/v1/provider/cost-profiles', $this->profile())->json();
        Sanctum::actingAs($other);
        $this->putJson("/api/v1/provider/cost-profiles/{$p['public_id']}", $this->profile())->assertStatus(422);
        $this->postJson('/api/v1/provider/estimates', ['distance_km' => 5, 'profile' => $p['public_id']])->assertStatus(422);
    }

    public function test_pricing_page_and_web_estimate(): void
    {
        $this->actingAs($this->owner)->get('/provider/pricing')->assertOk()->assertSee('Add a cost profile first');
        $this->post('/provider/pricing/profiles', ['name' => 'Van', 'pricing_method' => 'per_km', 'base_fee' => 500, 'rate_per_km' => 150, 'min_fee' => 1500, 'markup_bp' => 20, 'rounding_kobo' => 1])
            ->assertRedirect('/provider/pricing');
        $this->get('/provider/pricing')->assertOk()->assertSee('Van')->assertSee('Calculate');
        $this->postJson('/provider/pricing/estimate', ['distance_km' => 18])->assertOk()->assertJsonPath('suggested_price', 320_000);
    }

    public function test_paid_jobs_get_parcel_codes_and_drivers_can_look_them_up(): void
    {
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $customer = $this->makeUser('Ada');
        $this->fundCustomer($customer->id, 1_000_000);
        $agreement = $this->lockedAgreement($customer->id, $this->op, $this->owner->id, 300_000, 0, 0, [
            'packages' => [['description' => 'Box of books'], ['description' => 'Laptop', 'fragile' => true, 'declared_value' => 50_000_000]],
        ]);
        app(PaymentService::class)->payWithWallet($agreement, $customer->id);
        $shipment = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.*']);

        $pkgs = DB::table('packages')->where('shipment_id', $shipment->id)->orderBy('seq')->get();
        $this->assertCount(2, $pkgs);
        $this->assertTrue((bool) $pkgs[1]->fragile);
        foreach ($pkgs as $p) {
            $this->assertTrue(ParcelCodes::valid($p->barcode));
            $this->assertStringStartsWith(ParcelCodes::QR_PREFIX, $p->qr_payload);
        }
        $this->assertNotSame($pkgs[0]->barcode, $pkgs[1]->barcode);
        $this->assertMatchesRegularExpression('/^QD[0-9A-HJKMNP-TV-Z]{10}$/', $shipment->tracking_code);

        // A driver on the job finds the parcel by its QR or by the typed code; a stranger finds nothing.
        $driverUser = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $driverUser->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $driverUser->id)->update(['current_operator_id' => $this->op]);
        $dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $driverUser->id, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $shipment->id, 'operator_id' => $this->op, 'driver_profile_id' => $dp, 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);

        Sanctum::actingAs($driverUser->fresh());
        $this->getJson('/api/v1/driver/packages/lookup?code='.urlencode($pkgs[1]->qr_payload))->assertOk()->assertJsonPath('package.seq', 2)->assertJsonPath('package.of', 2);
        $this->getJson('/api/v1/driver/packages/lookup?code='.strtolower($pkgs[0]->barcode))->assertOk()->assertJsonPath('shipment', $shipment->public_id);
        $this->getJson("/api/v1/driver/jobs/{$shipment->public_id}")->assertOk()->assertJsonCount(2, 'packages');

        Sanctum::actingAs($this->makeUser('Stranger'));
        $this->getJson('/api/v1/driver/packages/lookup?code='.$pkgs[0]->barcode)->assertStatus(403);

        $this->actingAs($this->owner)->get("/provider/jobs/{$shipment->public_id}/labels")->assertOk()->assertSee($pkgs[0]->barcode)->assertSee('<svg', false);
    }

    public function test_a_request_is_priced_over_its_own_route(): void
    {
        $customer = User::factory()->create(['must_change_password' => false]);
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $id = app(\App\Modules\Marketplace\NegotiationService::class)->createRequest($customer->id, [
            'type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => (int) DB::table('cities')->value('id'),
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92],
            'packages' => [['description' => 'Vase', 'fragile' => true]], 'visibility' => 'direct', 'operator_ids' => [$this->op],
        ]);
        $public = DB::table('service_requests')->where('id', $id)->value('public_id');

        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/provider/cost-profiles', $this->profile(['fragile_surcharge' => 30_000]))->assertCreated();
        $e = $this->postJson("/api/v1/provider/requests/{$public}/estimate")->assertCreated()->json();
        $this->assertSame(30_000, $e['surcharges']['fragile']);
        $this->assertSame((int) DB::table('service_requests')->where('id', $id)->value('distance_m'), $e['distance_m']);
        $this->assertSame($id, (int) DB::table('provider_estimates')->value('request_id'));

        $this->actingAs($this->owner)->get("/provider/requests/{$public}")->assertOk()->assertSee('What should I charge?');

        [, $other] = $this->makeProvider('company', 'Uninvited');
        Sanctum::actingAs($other);
        $this->postJson("/api/v1/provider/requests/{$public}/estimate")->assertStatus(422);
    }
}
