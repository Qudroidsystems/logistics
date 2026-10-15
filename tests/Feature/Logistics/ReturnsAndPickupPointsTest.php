<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Backoffice\BulkBookingService;
use App\Modules\Backoffice\ManualBookingService;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\ApiClientService;
use App\Modules\Returns\ReturnService;
use App\Modules\Verification\DeliveryCodes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Parcels left at a provider's pickup point for the receiver to collect, and returns after a delivery. */
class ReturnsAndPickupPointsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $driver;
    private int $dp;
    private int $city;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');
        $this->driver = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $this->driver->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $this->driver->id)->update(['current_operator_id' => $this->op]);
        $this->dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $this->driver->id, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function addPoint(string $code = 'HILL1', float $lat = 7.82, float $lng = 5.92): void
    {
        $this->actingAs($this->owner)->post('/provider/pickup-points', ['code' => $code, 'name' => 'Hill Office', 'line1' => '2 Hill St', 'lat' => $lat, 'lng' => $lng,
            'opening_hours' => 'Mon to Sat, 8am to 6pm', 'hold_days' => 3])->assertRedirect('/provider/pickup-points');
    }

    /** A cash booking taken by staff; the drop-off is the receiver's door or a pickup point code. */
    private function book(array $dropoff = ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92]): object
    {
        $r = app(ManualBookingService::class)->book($this->op, [
            'customer' => ['name' => 'Shoe Shop', 'phone' => '08031112222'], 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => $dropoff + ['contact_name' => 'Bisi', 'contact_phone' => '08030000002'],
            'packages' => [['description' => 'Shoes', 'weight_g' => 800]], 'price' => 300_000, 'payment' => 'cash',
        ], $this->owner->id);

        return DB::table('shipments')->where('id', $r['shipment_id'])->first();
    }

    /** The rider has the parcel and is at the drop-off. */
    private function onTheWay(object $s): int
    {
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'in_transit']);
        DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'pickup')->update(['status' => 'completed']);

        return (int) DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->value('id');
    }

    private function leaveAtPoint(object $s): void
    {
        $stop = $this->onTheWay($s);
        Sanctum::actingAs($this->driver->fresh());
        $job = $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertOk()->json();
        $drop = collect($job['stops'] ?? $job['data']['stops'] ?? [])->firstWhere('type', 'dropoff');
        $this->assertSame([true, false, ['photo']], [$drop['at_pickup_point'], $drop['needs_code'], $drop['requires']]);
        // No code from the receiver: the rider proves the hand-over to the point with a photo.
        $this->post("/api/v1/driver/stops/{$stop}/complete", ['lat' => 7.82, 'lng' => 5.92, 'photo' => UploadedFile::fake()->image('shelf.jpg')], ['Accept' => 'application/json'])->assertOk();
    }

    public function test_only_finished_deliveries_go_back_and_only_for_thirty_days(): void
    {
        $now = CarbonImmutable::parse('2026-11-30 12:00');
        $this->assertNull(ReturnService::window('delivered', '2026-11-20 10:00', $now));
        $this->assertNull(ReturnService::window('completed', '2026-11-01 10:00', $now));
        $this->assertSame('Returns can be booked up to 30 days after delivery.', ReturnService::window('completed', '2026-10-30 10:00', $now));
        $this->assertNull(ReturnService::window('failed_attempt', null, $now));
        $this->assertSame('A return can be booked once the delivery is finished.', ReturnService::window('in_transit', null, $now));
    }

    public function test_a_parcel_left_at_a_pickup_point_is_delivered_when_the_receiver_collects_it(): void
    {
        $this->addPoint();
        $s = $this->book(['pickup_point' => 'hill1']);
        $drop = DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->first();
        $this->assertSame('Hill Office, 2 Hill St', $drop->line1);
        $this->assertSame(['Bisi', '08030000002'], [$drop->contact_name, $drop->contact_phone]);   // the receiver stays the contact
        $this->assertNotNull($drop->pickup_point_id);

        $this->leaveAtPoint($s);
        $s = DB::table('shipments')->where('id', $s->id)->first();
        $this->assertSame('at_pickup_point', $s->status);
        $this->assertSame('completed', DB::table('assignments')->where('shipment_id', $s->id)->value('status'));
        $this->assertSame('online', DB::table('driver_profiles')->where('id', $this->dp)->value('availability'));
        $this->assertSame(0, DB::table('delivery_confirmations')->where('shipment_id', $s->id)->count());   // not delivered yet
        // The receiver's code lasts as long as the parcel is kept, and they were texted.
        $live = DB::table('delivery_codes')->where('shipment_id', $s->id)->whereNull('revoked_at')->first();
        $this->assertSame(CarbonImmutable::parse($s->hold_until)->timestamp, CarbonImmutable::parse($live->expires_at)->timestamp);
        $this->assertTrue(CarbonImmutable::parse($s->hold_until)->isAfter(now()->addDays(2)));
        $this->assertSame(1, DB::table('sms_log')->where('template_key', 'delivery.at_pickup_point')->count());

        $code = app(DeliveryCodes::class)->current($s->id)['code'];
        $page = $this->actingAs($this->owner)->get('/provider/pickup-points')->assertOk();
        $page->assertSee(DB::table('orders')->where('id', $s->order_id)->value('order_number'))->assertSee('Hill Office');

        $wrong = $code === '000000' ? '111111' : '000000';
        $this->actingAs($this->owner)->post("/provider/pickup-points/parcels/{$s->public_id}/collect", ['code' => $wrong])
            ->assertSessionHas('error', 'That code is not right. 4 more tries before it locks.');
        $this->assertSame(1, (int) DB::table('delivery_codes')->where('id', $live->id)->value('attempts'));

        $this->actingAs($this->owner)->post("/provider/pickup-points/parcels/{$s->public_id}/collect", ['code' => $code, 'receiver_name' => 'Bisi'])
            ->assertSessionHas('success', 'Handed over. The delivery is complete.');
        $this->assertSame('delivered', DB::table('shipments')->where('id', $s->id)->value('status'));
        $this->assertSame(1, DB::table('delivery_confirmations')->where('shipment_id', $s->id)->count());
        $this->assertSame('staff', DB::table('proofs')->where('shipment_id', $s->id)->orderByDesc('id')->value('verified_by'));
        $this->actingAs($this->owner)->post("/provider/pickup-points/parcels/{$s->public_id}/collect", ['code' => $code])
            ->assertSessionHas('error', 'That parcel is not waiting at a pickup point.');
    }

    public function test_an_uncollected_parcel_is_closed_under_the_agreement_and_booked_back(): void
    {
        $this->addPoint();
        $s = $this->book(['pickup_point' => 'HILL1']);
        $this->leaveAtPoint($s);

        $this->actingAs($this->owner)->post("/provider/pickup-points/parcels/{$s->public_id}/not-collected")
            ->assertSessionHas('error', 'The parcel is still within its collection time.');
        DB::table('shipments')->where('id', $s->id)->update(['hold_until' => now()->subHour()]);
        $this->actingAs($this->owner)->get('/provider/pickup-points')->assertSee('Nobody came');

        $this->actingAs($this->owner)->post("/provider/pickup-points/parcels/{$s->public_id}/not-collected")->assertRedirect("/provider/jobs/{$s->public_id}");
        $this->assertSame('failed_attempt', DB::table('shipments')->where('id', $s->id)->value('status'));
        $this->assertSame('failed', DB::table('orders')->where('id', $s->order_id)->value('status'));
        $this->assertSame('not_collected', json_decode(DB::table('shipment_events')->where('shipment_id', $s->id)->where('type', 'delivery_failed')->value('meta'), true)['reason']);

        // Now the parcel goes back from the point to the sender, as a job of its own.
        $this->actingAs($this->owner)->get("/provider/jobs/{$s->public_id}")->assertOk()->assertSee('Book a return');
        $this->actingAs($this->owner)->post("/provider/jobs/{$s->public_id}/return", ['reason' => 'not_collected', 'payment' => 'cash', 'price_naira' => 1500])
            ->assertSessionHas('success');
        $ret = DB::table('shipments')->where('parent_shipment_id', $s->id)->first();
        $this->assertSame(['return', 'not_collected', 'awaiting_dispatch'], [$ret->kind, $ret->return_reason, $ret->status]);
        $stops = DB::table('shipment_stops')->where('shipment_id', $ret->id)->orderBy('seq')->get(['type', 'line1', 'contact_name']);
        $this->assertSame(['Hill Office, 2 Hill St', '1 Market Rd'], $stops->pluck('line1')->all());
        $this->assertSame('Shoes', DB::table('packages')->where('shipment_id', $ret->id)->value('description'));
        $this->assertSame(150_000, (int) DB::table('orders')->where('id', $ret->order_id)->value('total'));

        $this->actingAs($this->owner)->get("/provider/jobs/{$s->public_id}")->assertSee('Return booked as order');
        $this->actingAs($this->owner)->get("/provider/jobs/{$ret->public_id}")->assertSee('This is a return');
        $this->actingAs($this->owner)->post("/provider/jobs/{$s->public_id}/return", ['reason' => 'other', 'payment' => 'cash', 'price_naira' => 1500])
            ->assertSessionHas('error', 'A return has already been booked for this delivery.');
    }

    public function test_a_delivered_parcel_can_be_returned_to_a_pickup_point_within_the_window(): void
    {
        $s = $this->book();
        $this->actingAs($this->owner)->post("/provider/jobs/{$s->public_id}/return", ['reason' => 'wrong_item', 'payment' => 'cash', 'price_naira' => 1500])
            ->assertSessionHas('error', 'A return can be booked once the delivery is finished.');

        $this->addPoint('DEPOT', 7.81, 5.91);
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'completed', 'delivered_at' => now()->subDays(31)]);
        $this->actingAs($this->owner)->post("/provider/jobs/{$s->public_id}/return", ['reason' => 'wrong_item', 'payment' => 'cash', 'price_naira' => 1500])
            ->assertSessionHas('error', 'Returns can be booked up to 30 days after delivery.');

        DB::table('shipments')->where('id', $s->id)->update(['delivered_at' => now()->subDays(3)]);
        $this->actingAs($this->owner)->post("/provider/jobs/{$s->public_id}/return", ['reason' => 'wrong_item', 'payment' => 'cash', 'price_naira' => 1500, 'to_point' => 'DEPOT'])
            ->assertSessionHas('success');
        $ret = DB::table('shipments')->where('parent_shipment_id', $s->id)->first();
        $drops = DB::table('shipment_stops')->where('shipment_id', $ret->id)->orderBy('seq')->get(['type', 'line1', 'contact_name', 'pickup_point_id']);
        // Picked up from the receiver; left at the depot for the sender to collect.
        $this->assertSame(['9 Hill St', 'Hill Office, 2 Hill St'], $drops->pluck('line1')->all());
        $this->assertSame('Bisi', $drops[0]->contact_name);
        $this->assertNotNull($drops[1]->pickup_point_id);
    }

    public function test_points_belong_to_their_provider_and_a_paused_point_takes_no_parcels(): void
    {
        $this->addPoint();
        $this->actingAs($this->owner)->post('/provider/pickup-points', ['code' => 'hill-1', 'name' => 'Again', 'line1' => 'x', 'lat' => 7.8, 'lng' => 5.9])
            ->assertSessionHas('error', 'Another of your pickup points already uses the code HILL1.');
        $point = DB::table('pickup_points')->first();
        $this->actingAs($this->owner)->post("/provider/pickup-points/{$point->public_id}/status", ['active' => 0]);
        $this->expectExceptionMessage('The pickup point Hill Office is paused and is not taking parcels.');
        try {
            $this->book(['pickup_point' => 'HILL1']);
        } finally {
            [$other] = $this->makeProvider('company', 'Other Haulage');
            $this->assertThrows(fn () => app(\App\Modules\Returns\PickupPointService::class)->resolve($other, 'HILL1'), \RuntimeException::class, 'There is no pickup point with the code HILL1.');
        }
    }

    public function test_a_bulk_sheet_can_send_rows_to_a_pickup_point(): void
    {
        $this->addPoint();
        $defaults = ['service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city, 'payment' => 'cash',
            'customer' => ['name' => 'Shoe Shop', 'phone' => '08031112222'], 'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90]];
        $csv = "reference,recipient_name,recipient_phone,pickup_point,price_naira\nA1,Bisi,08030000002,hill1,3000\nA2,Tunde,08030000003,NOPE,3000\n";
        $svc = app(BulkBookingService::class);
        $batch = $svc->check($this->op, $this->owner->id, $csv, $defaults);
        $this->assertSame(['ready', 'invalid'], DB::table('bulk_batch_rows')->orderBy('line')->pluck('status')->all());
        $this->assertStringContainsString('There is no active pickup point with the code NOPE.', DB::table('bulk_batch_rows')->where('line', 3)->value('errors'));
        $this->assertSame(['booked' => 1, 'failed' => 0], $svc->book($this->op, $batch, $this->owner->id));
        $this->assertNotNull(DB::table('shipment_stops')->where('type', 'dropoff')->value('pickup_point_id'));
    }

    // ---------------------------------------------------------------- partner API

    public function test_a_merchant_books_a_return_over_the_api(): void
    {
        DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $this->city, 'service_type_id' => $this->serviceTypeId(),
            'name' => 'Standard', 'min_fee' => 150000, 'rounding_kobo' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        [$mOp, $mOwner] = $this->makeProvider('merchant', 'Shoe Shop');
        $this->fundCustomer($mOwner->id, 5_000_000);
        $mid = DB::table('merchants')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $mOp, 'merchant_code' => 'SHOE01', 'display_name' => 'Shoe Shop',
            'status' => 'verified', 'settlement_mode' => 'prepaid_wallet', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $h = ['Authorization' => 'Bearer '.app(ApiClientService::class)->issue($mOp, $mid, 'Shop', 'live')['key']];

        $this->postJson('/api/v1/partner/deliveries', ['external_order_id' => 'S-9', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_phone' => '08030000002']], $h)->assertCreated();
        $this->postJson('/api/v1/partner/deliveries/S-9/return', ['reason' => 'changed_mind'], $h)->assertStatus(422)->assertJsonPath('message', 'A return can be booked once the delivery is finished.');
        $this->postJson('/api/v1/partner/deliveries/NOPE/return', ['reason' => 'changed_mind'], $h)->assertNotFound();

        $orig = DB::table('shipments')->first();
        DB::table('shipments')->where('id', $orig->id)->update(['status' => 'delivered', 'delivered_at' => now()]);
        $r = $this->postJson('/api/v1/partner/deliveries/S-9/return', ['reason' => 'changed_mind'], $h)->assertCreated();
        $this->assertSame('S-9-return', $r->json('external_order_id'));
        $ret = DB::table('shipments')->where('parent_shipment_id', $orig->id)->first();
        $this->assertSame(['return', 'changed_mind'], [$ret->kind, $ret->return_reason]);
        $this->assertSame('9 Hill St', DB::table('shipment_stops')->where('shipment_id', $ret->id)->where('type', 'pickup')->value('line1'));

        // Sent again after a timeout: the same return, not a second one.
        $this->postJson('/api/v1/partner/deliveries/S-9/return', ['reason' => 'changed_mind'], $h)->assertCreated()->assertJsonPath('external_order_id', 'S-9-return');
        $this->assertSame(1, DB::table('shipments')->where('parent_shipment_id', $orig->id)->count());
    }
}
