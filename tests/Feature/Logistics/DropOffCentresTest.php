<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\RouteEstimator;
use App\Modules\Partner\ShipmentEvents;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Partner stores: apply, approve, book a delivery that starts at the store, receive it at the counter, hand it to the driver, pay the store. */
class DropOffCentresTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $customer;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        Notification::fake();
        $this->seedFoundation();
        $this->mock(RouteEstimator::class, fn ($m) => $m->shouldReceive('estimate')->andReturn(['distance_m' => 6_000, 'duration_s' => 700]));
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Kitchener', 'kitchener', ST_SetSRID(ST_MakePoint(-80.49, 43.45), 4326)::geography, 'live', now(), now())");
        $city = (int) DB::table('cities')->value('id');
        [$this->op] = $this->makeProvider('company', 'CityHop');
        $plan = DB::table('pricing_plans')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $city,
            'service_type_id' => (int) DB::table('service_types')->where('code', 'same_day')->value('id'), 'name' => 'Same day', 'min_fee' => 0, 'rounding_kobo' => 1,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pricing_rules')->insert([
            ['plan_id' => $plan, 'kind' => 'base', 'amount' => 2_000, 'condition' => null, 'priority' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['plan_id' => $plan, 'kind' => 'store_dropoff', 'amount' => -300, 'condition' => null, 'priority' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        config(['booking.operator_id' => $this->op, 'booking.vehicles' => []]);
        $this->customer = $this->makeUser('Ada');
        $this->staff = $this->makeUser('Ops');
        Permission::findOrCreate('Manage drop-off centres', 'web');
        $this->staff->givePermissionTo('Manage drop-off centres');
    }

    private function approvedStore(): array
    {
        $this->get('/partners')->assertOk()->assertSee('Become a drop-off partner');
        $this->post('/partners', ['business_name' => 'Corner Mart', 'contact_name' => 'Sam', 'email' => 'sam@cornermart.test', 'phone' => '2265550100',
            'line1' => '10 King St W', 'city' => 'Kitchener', 'lat' => 43.4505, 'lng' => -80.4925, 'consent' => '1'])->assertRedirect('/partners');
        $app = DB::table('partner_applications')->first();
        $this->assertSame('new', $app->status);

        $this->actingAs($this->staff)->get('/ops/dropoff-centres')->assertOk()->assertSee('Corner Mart');
        $this->actingAs($this->staff)->post("/ops/dropoff-centres/applications/{$app->id}/approve", ['lat' => 43.4505, 'lng' => -80.4925, 'commission_flat' => '1.50', 'commission_pct' => '0'])
            ->assertSessionHas('success');
        $centre = DB::table('dropoff_centres')->first();
        $owner = User::where('email', 'sam@cornermart.test')->first();
        $this->assertNotNull($owner);
        $this->assertSame(150, (int) $centre->commission_flat);

        return [$centre, $owner];
    }

    public function test_the_whole_store_journey(): void
    {
        [$centre, $owner] = $this->approvedStore();
        $dispatch = $this->mock(DispatchService::class);
        $dispatch->shouldReceive('start')->andReturn(1);

        // The price is cheaper from the store, and the trip starts there.
        $q = $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/public/quote', [
            'dropoff_centre' => $centre->public_id, 'dropoff' => ['lat' => 43.47, 'lng' => -80.54, 'line1' => '200 University Ave W'],
            'service' => 'same_day', 'size' => 'small',
        ])->assertOk()->json();
        $this->assertSame(1_700, $q['subtotal']);
        $this->assertSame('Corner Mart', $q['centre']['name']);

        $agreement = $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/instant-bookings', ['quote' => $q['quote'],
            'dropoff_name' => 'Bo', 'dropoff_phone' => '5195550199', 'description' => 'Books'])->assertCreated()->json('agreement');
        $agreementId = (int) DB::table('agreements')->where('public_id', $agreement)->value('id');
        $this->fundCustomer($this->customer->id, 10_000);
        $made = app(PaymentService::class)->payWithWallet($agreementId, $this->customer->id);

        $parcel = DB::table('dropoff_parcels')->where('shipment_id', $made['shipment_id'])->first();
        $this->assertSame('expected', $parcel->status);
        $this->assertSame(150, (int) $parcel->commission);
        $pickup = DB::table('shipment_stops')->where('shipment_id', $made['shipment_id'])->where('type', 'pickup')->first();
        $this->assertSame('Corner Mart', $pickup->contact_name);
        $this->assertTrue(\App\Modules\DropOff\DropOffService::waitingForDropOff($made['shipment_id']));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->customer->id, 'type' => 'dropoff.bring_parcel'])->exists());

        // Counter: the wrong code is refused; the right one is received.
        $code = DB::table('shipments')->where('id', $made['shipment_id'])->value('tracking_code');
        $this->actingAs($owner)->get('/counter')->assertOk()->assertSee($code);
        $this->actingAs($owner)->post('/counter/receive', ['code' => 'NOPE'])->assertSessionHas('error');
        $this->actingAs($owner)->post('/counter/collect', ['code' => $code])->assertSessionHas('error');
        $this->actingAs($owner)->post('/counter/receive', ['code' => strtolower($code)])->assertSessionHas('success');
        $this->assertSame('received', DB::table('dropoff_parcels')->where('id', $parcel->id)->value('status'));
        $this->assertFalse(\App\Modules\DropOff\DropOffService::waitingForDropOff($made['shipment_id']));

        // The driver picks it up in the app: the store has earned its commission.
        app(ShipmentEvents::class)->record($made['shipment_id'], 'picked_up', 'assigned', 'picked_up', 'driver', 1);
        $row = DB::table('dropoff_parcels')->where('id', $parcel->id)->first();
        $this->assertSame('collected', $row->status);
        $this->assertSame('earned', $row->commission_status);
        $this->actingAs($owner)->get('/counter')->assertOk()->assertSee('1.50');

        // Staff pay the store.
        $this->actingAs($this->staff)->post("/ops/dropoff-centres/{$centre->id}/paid", ['reference' => 'ET-123'])->assertSessionHas('success');
        $this->assertSame('paid', DB::table('dropoff_parcels')->where('id', $parcel->id)->value('commission_status'));
        $this->actingAs($this->staff)->post("/ops/dropoff-centres/{$centre->id}/paid", ['reference' => 'ET-124'])->assertSessionHas('error');
    }

    public function test_other_stores_and_strangers_cannot_use_the_counter(): void
    {
        [$centre] = $this->approvedStore();
        $this->actingAs($this->customer)->get('/counter')->assertForbidden();
        $this->actingAs($this->customer)->get('/ops/dropoff-centres')->assertForbidden();

        DB::table('dropoff_centres')->where('id', $centre->id)->update(['status' => 'paused']);
        $this->postJson('/api/v1/public/quote', ['dropoff_centre' => $centre->public_id, 'dropoff' => ['lat' => 43.47, 'lng' => -80.54],
            'service' => 'same_day', 'size' => 'small'])->assertStatus(422);
        $this->getJson('/api/v1/public/dropoff-centres')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_decided_application_cannot_be_decided_again(): void
    {
        $this->approvedStore();
        $id = DB::table('partner_applications')->value('id');
        $this->actingAs($this->staff)->post("/ops/dropoff-centres/applications/{$id}/decline", ['note' => 'dupe'])->assertSessionHas('error');
    }
}
