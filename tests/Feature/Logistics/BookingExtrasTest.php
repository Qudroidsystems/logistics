<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\PaymentService;
use App\Modules\Pricing\PriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Customer booking extras: pickup time, several stops, saved addresses, files, promo codes, corporate discounts, receipts. */
class BookingExtrasTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;
    private int $city;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = $this->makeUser('Ada');
        $this->fundCustomer($this->customer->id, 5_000_000);
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');
    }

    private function api(?User $as = null): self
    {
        Sanctum::actingAs(($as ?? $this->customer)->fresh());

        return $this;
    }

    private function requestBody(array $extra = []): array
    {
        return $extra + [
            'type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92],
            'visibility' => 'direct', 'operator_ids' => [$this->op],
        ];
    }

    /** Request -> offer -> accept through the real API. Returns [request row, agreement id]. */
    private function agreeOn(array $body, int $price = 300_000): array
    {
        $public = $this->api()->postJson('/api/v1/customer/requests', $body)->assertCreated()->json('request_id');
        $req = DB::table('service_requests')->where('public_id', $public)->first();
        $thread = $this->api($this->owner)->postJson("/api/v1/provider/requests/{$public}/offer", ['price' => $price])->assertCreated()->json('thread');
        $offer = (int) DB::table('negotiation_messages')->where('kind', 'counter_offer')->orderByDesc('id')->value('id');
        $this->api()->postJson("/api/v1/customer/negotiations/{$thread}/accept", ['offer_id' => $offer])->assertCreated();

        return [$req, (int) DB::table('agreements')->where('request_id', $req->id)->value('id')];
    }

    private function shipmentFor(int $agreementId): object
    {
        return DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreementId)->first(['shipments.*']);
    }

    // ------------------------------------------------------------------ scheduled pickup and several stops

    public function test_a_multi_stop_booking_for_later_waits_for_its_pickup_time(): void
    {
        $at = now()->addDays(2)->setTime(10, 0);
        [$req, $agreement] = $this->agreeOn($this->requestBody([
            'pickup_at' => $at->toIso8601String(), 'rush' => true,
            'stops' => [
                ['type' => 'pickup', 'line1' => '4 Second Pickup', 'lat' => 7.805, 'lng' => 5.905],
                ['type' => 'dropoff', 'line1' => '2 First Drop', 'lat' => 7.81, 'lng' => 5.91, 'contact_name' => 'Bola'],
            ],
            'packages' => [['description' => 'For Bola', 'dropoff' => 1], ['description' => 'For Hill St']],
        ]));
        $this->assertTrue((bool) $req->rush);
        $this->assertSame(2, count(json_decode($req->stops, true)['extra']));

        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = $this->shipmentFor($agreement);
        $stops = DB::table('shipment_stops')->where('shipment_id', $s->id)->orderBy('seq')->get();
        $this->assertSame(['pickup', 'pickup', 'dropoff', 'dropoff'], $stops->pluck('type')->all());
        $this->assertSame(['1 Market Rd', '4 Second Pickup', '2 First Drop', '9 Hill St'], $stops->pluck('line1')->all());
        $this->assertSame([$stops[2]->id, $stops[3]->id], DB::table('packages')->where('shipment_id', $s->id)->orderBy('seq')->pluck('dropoff_stop_id')->map(fn ($i) => (int) $i)->all());

        // Paid two days ahead: nobody is offered the job yet.
        $this->assertSame('awaiting_dispatch', $s->status);
        $this->assertSame(0, DB::table('dispatch_runs')->where('shipment_id', $s->id)->count());
        $this->artisan('dispatch:scheduled')->assertSuccessful();
        $this->assertSame(0, DB::table('dispatch_runs')->where('shipment_id', $s->id)->count());

        // Shortly before the pickup time the search starts.
        $this->travelTo($at->copy()->subMinutes(30));
        $this->artisan('dispatch:scheduled')->assertSuccessful();
        $this->assertSame(1, DB::table('dispatch_runs')->where('shipment_id', $s->id)->count());
    }

    public function test_the_driver_works_through_every_stop_before_the_job_is_delivered(): void
    {
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [, $agreement] = $this->agreeOn($this->requestBody([
            'stops' => [['type' => 'dropoff', 'line1' => '2 First Drop', 'lat' => 7.81, 'lng' => 5.91]],
        ]));
        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = $this->shipmentFor($agreement);
        $driver = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $driver->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $driver->id)->update(['current_operator_id' => $this->op]);
        $dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $driver->id, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $dp, 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);
        $stops = DB::table('shipment_stops')->where('shipment_id', $s->id)->orderBy('seq')->get();

        $at = fn ($stop) => ['lat' => (float) DB::selectOne('SELECT ST_Y(point::geometry) y FROM shipment_stops WHERE id = ?', [$stop->id])->y, 'lng' => (float) DB::selectOne('SELECT ST_X(point::geometry) x FROM shipment_stops WHERE id = ?', [$stop->id])->x];
        $this->api($driver)->post("/api/v1/driver/stops/{$stops[0]->id}/complete", $at($stops[0]), ['Accept' => 'application/json'])->assertOk();
        // A drop-off before the last one takes a photo; the delivery code is kept for the final hand-over.
        $this->api($driver)->post("/api/v1/driver/stops/{$stops[1]->id}/complete", $at($stops[1]), ['Accept' => 'application/json'])->assertStatus(422);
        $this->api($driver)->post("/api/v1/driver/stops/{$stops[1]->id}/complete", $at($stops[1]) + ['photo' => UploadedFile::fake()->image('door.jpg')], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame('in_transit', DB::table('shipments')->where('id', $s->id)->value('status'));

        $code = app(\App\Modules\Verification\DeliveryCodes::class)->current($s->id)['code'] ?? null;
        $last = $at($stops[2]) + ['photo' => UploadedFile::fake()->image('door2.jpg')] + ($code ? ['otp' => $code] : []);
        $this->api($driver)->post("/api/v1/driver/stops/{$stops[2]->id}/complete", $last, ['Accept' => 'application/json'])->assertOk();
        $this->assertSame('delivered', DB::table('shipments')->where('id', $s->id)->value('status'));
    }

    public function test_bad_stops_and_pickup_times_are_refused(): void
    {
        $this->api()->postJson('/api/v1/customer/requests', $this->requestBody(['pickup_at' => now()->subHour()->toIso8601String()]))->assertStatus(422);
        $this->api()->postJson('/api/v1/customer/requests', $this->requestBody(['pickup_at' => now()->addDays(40)->toIso8601String()]))->assertStatus(422);
        $this->api()->postJson('/api/v1/customer/requests', $this->requestBody(['stops' => [['type' => 'waypoint', 'line1' => 'x', 'lat' => 7.8, 'lng' => 5.9]]]))->assertStatus(422);
        $tooMany = array_fill(0, 9, ['type' => 'dropoff', 'line1' => 'x', 'lat' => 7.8, 'lng' => 5.9]);
        $this->api()->postJson('/api/v1/customer/requests', $this->requestBody(['stops' => $tooMany]))->assertStatus(422);
    }

    public function test_company_mode_takes_several_stops_and_a_pickup_time_too(): void
    {
        config(['platform.mode' => 'company', 'platform.house_operator_id' => $this->op]);
        $body = $this->requestBody(['pickup_at' => now()->addHours(5)->toIso8601String(), 'stops' => [['type' => 'dropoff', 'line1' => '2 First Drop', 'lat' => 7.81, 'lng' => 5.91]]]);
        unset($body['visibility'], $body['operator_ids']);
        $public = $this->api()->postJson('/api/v1/customer/requests', $body)->assertCreated()->json('request_id');
        $req = DB::table('service_requests')->where('public_id', $public)->first();
        $this->assertSame('direct', $req->visibility);
        $this->assertSame($this->op, (int) DB::table('request_invitations')->where('request_id', $req->id)->value('operator_id'));
        $inbox = $this->api($this->owner)->getJson('/api/v1/provider/requests')->assertOk()->assertJsonPath('0.public_id', $public)->assertJsonPath('0.rush', false)->json('0');
        $this->assertSame('2 First Drop', json_decode($inbox['stops'], true)['extra'][0]['line1']);
        $this->assertNotNull($inbox['pickup_at']);
        // The request closes before the pickup time.
        $this->assertSame($req->pickup_at, $req->expires_at);
    }

    // ------------------------------------------------------------------ saved addresses

    public function test_customers_keep_their_own_saved_addresses(): void
    {
        $id = $this->api()->postJson('/api/v1/customer/addresses', ['label' => 'Home', 'line1' => '5 Palm Close', 'lat' => 7.81, 'lng' => 5.91, 'is_default' => true, 'instructions' => 'Blue gate'])
            ->assertCreated()->assertJsonPath('instructions', 'Blue gate')->json('id');
        $this->api()->postJson('/api/v1/customer/addresses', ['label' => 'Office', 'line1' => '12 Broad St', 'lat' => 7.79, 'lng' => 5.89, 'is_default' => true])->assertCreated();
        $list = $this->api()->getJson('/api/v1/customer/addresses')->assertOk()->json();
        $this->assertSame(['Office', 'Home'], array_column($list, 'label'));
        $this->assertFalse($list[1]['is_default']);

        $this->api()->putJson("/api/v1/customer/addresses/{$id}", ['label' => 'Home', 'line1' => '5 Palm Close, Flat 2', 'lat' => 7.81, 'lng' => 5.91])->assertOk()->assertJsonPath('line1', '5 Palm Close, Flat 2');

        $stranger = $this->makeUser('Eve');
        $this->api($stranger)->getJson('/api/v1/customer/addresses')->assertOk()->assertJsonCount(0);
        $this->api($stranger)->deleteJson("/api/v1/customer/addresses/{$id}")->assertStatus(422);

        // Booking from a saved place marks it as recently used.
        $this->api()->postJson('/api/v1/customer/requests', $this->requestBody(['pickup' => ['line1' => '5 Palm Close', 'lat' => 7.81, 'lng' => 5.91]]))->assertCreated();
        $this->assertNotNull(DB::table('addresses')->where('public_id', $id)->value('last_used_at'));

        $this->api()->deleteJson("/api/v1/customer/addresses/{$id}")->assertOk();
        $this->api()->getJson('/api/v1/customer/addresses')->assertJsonCount(1);
    }

    // ------------------------------------------------------------------ photos and documents

    public function test_customer_files_reach_the_invited_provider_and_no_one_else(): void
    {
        $public = $this->api()->postJson('/api/v1/customer/requests', $this->requestBody())->assertCreated()->json('request_id');
        $file = $this->api()->post("/api/v1/customer/requests/{$public}/attachments", ['file' => UploadedFile::fake()->image('sofa.jpg'), 'caption' => 'The sofa'], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('kind', 'photo')->json('id');
        $this->api()->post("/api/v1/customer/requests/{$public}/attachments", ['file' => UploadedFile::fake()->create('waybill.pdf', 50, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('kind', 'document');
        $this->api()->post("/api/v1/customer/requests/{$public}/attachments", ['file' => UploadedFile::fake()->create('virus.exe', 5)], ['Accept' => 'application/json'])->assertStatus(422);
        $this->api()->getJson("/api/v1/customer/requests/{$public}/attachments")->assertOk()->assertJsonCount(2);

        $this->api($this->owner)->get("/api/v1/attachments/{$file}")->assertOk();
        $this->actingAs($this->owner)->get('/provider/requests/'.$public)->assertOk()->assertSee('The sofa');
        [, $rivalOwner] = $this->makeProvider('company', 'Rival');
        $this->api($rivalOwner)->get("/api/v1/attachments/{$file}")->assertNotFound();

        $this->api()->deleteJson("/api/v1/customer/attachments/{$file}")->assertOk();
        $this->api()->getJson("/api/v1/customer/requests/{$public}/attachments")->assertJsonCount(1);
    }

    // ------------------------------------------------------------------ promo codes and corporate discounts

    private function promo(array $over = []): int
    {
        return DB::table('promo_codes')->insertGetId($over + [
            'code' => 'WELCOME10', 'type' => 'percent', 'value' => 1000, 'cap' => 50_000, 'per_user_limit' => 1, 'funded_by' => 'platform', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_a_promo_code_lowers_what_the_customer_pays_but_not_what_the_provider_gets(): void
    {
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $this->promo();
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        $pub = DB::table('agreements')->where('id', $a)->value('public_id');

        $this->api()->postJson("/api/v1/customer/agreements/{$pub}/promo", ['code' => 'nope'])->assertStatus(422);
        $this->api()->postJson("/api/v1/customer/agreements/{$pub}/promo", ['code' => 'welcome10'])->assertOk()
            ->assertJsonPath('discount', 30_000)->assertJsonPath('payable', 270_000)->assertJsonPath('promo_code', 'WELCOME10');

        $before = $this->walletBalance('customer', $this->customer->id);
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);
        $this->assertSame(270_000, $before - $this->walletBalance('customer', $this->customer->id));
        $this->assertSame(300_000, $this->escrowBalance($a));
        $order = DB::table('orders')->where('agreement_id', $a)->first();
        $this->assertSame([30_000, 270_000], [(int) $order->discount, (int) $order->total]);
        $this->assertSame(1, DB::table('promo_redemptions')->where('order_id', $order->id)->count());

        // The provider is paid on the full agreed price.
        $net = (int) DB::table('agreements')->where('id', $a)->value('provider_net');
        $provider = $this->walletBalance('operator', $this->op);
        app(EscrowService::class)->release($a);
        $this->assertSame($net, $this->walletBalance('operator', $this->op) - $provider);
        $this->assertLedgerBalanced();

        // Once per customer.
        $b = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        $this->api()->postJson('/api/v1/customer/agreements/'.DB::table('agreements')->where('id', $b)->value('public_id').'/promo', ['code' => 'WELCOME10'])
            ->assertStatus(422)->assertJsonPath('message', 'You have already used this promo code.');
    }

    public function test_cancelling_a_discounted_job_returns_the_platform_its_share(): void
    {
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $this->promo(['code' => 'FLAT500', 'type' => 'fixed', 'value' => 50_000, 'cap' => null]);
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        $this->api()->postJson('/api/v1/customer/agreements/'.DB::table('agreements')->where('id', $a)->value('public_id').'/promo', ['code' => 'FLAT500'])->assertOk();
        $before = $this->walletBalance('customer', $this->customer->id);
        $promoBefore = $this->platformBalance('promo_expense');
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);

        app(EscrowService::class)->refund($a);
        // The customer gets back exactly what they paid; the platform gets its ₦500 back.
        $this->assertSame($before, $this->walletBalance('customer', $this->customer->id));
        $this->assertSame($promoBefore, $this->platformBalance('promo_expense'));
        $this->assertSame(0, $this->escrowBalance($a));
        $this->assertLedgerBalanced();
    }

    public function test_a_corporate_customer_gets_their_discount_automatically(): void
    {
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        DB::table('corporate_discounts')->insert(['user_id' => $this->customer->id, 'name' => 'Acme Ltd account', 'rate_bp' => 1500, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 200_000);
        $pub = DB::table('agreements')->where('id', $a)->value('public_id');
        $this->api()->getJson("/api/v1/customer/agreements/{$pub}/payment")->assertOk()
            ->assertJsonPath('discount', 30_000)->assertJsonPath('payable', 170_000)->assertJsonPath('discount_lines.0.label', 'Acme Ltd account');

        $before = $this->walletBalance('customer', $this->customer->id);
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);
        $this->assertSame(170_000, $before - $this->walletBalance('customer', $this->customer->id));
        $this->assertLedgerBalanced();
    }

    public function test_a_card_checkout_asks_for_the_discounted_amount(): void
    {
        $this->promo();
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        $pub = DB::table('agreements')->where('id', $a)->value('public_id');
        config(['services.paystack.mode' => 'sandbox', 'services.paystack.test_secret_key' => 'sk_test_unit']);
        \Illuminate\Support\Facades\Http::fake(['api.paystack.co/transaction/initialize' => \Illuminate\Support\Facades\Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.test/abc', 'access_code' => 'x', 'reference' => 'r']])]);

        $first = app(PaymentService::class)->initiateForAgreement($a, $this->customer->id);
        $this->assertSame(300_000, $first['amount']);
        $this->api()->postJson("/api/v1/customer/agreements/{$pub}/promo", ['code' => 'WELCOME10'])->assertOk();
        $second = app(PaymentService::class)->initiateForAgreement($a, $this->customer->id);
        $this->assertSame(270_000, $second['amount']);
        $this->assertNotSame($first['reference'], $second['reference']);
        $this->assertSame('abandoned', DB::table('payment_intents')->where('reference', $first['reference'])->value('status'));
    }

    // ------------------------------------------------------------------ surcharges

    public function test_weekend_rush_and_fuel_surcharges_show_as_their_own_lines(): void
    {
        $calc = new PriceCalculator();
        $plan = ['min_fee' => 0, 'max_fee' => null, 'rounding_kobo' => 1];
        $rules = [
            ['kind' => 'base', 'amount' => 100_000],
            ['kind' => 'weekend', 'rate_bp' => 1000, 'priority' => 1],
            ['kind' => 'rush', 'rate_bp' => 2000, 'amount' => 5_000, 'priority' => 2],
            ['kind' => 'fuel_surcharge', 'rate_bp' => 500, 'priority' => 3],
        ];
        $in = ['distance_m' => 0, 'duration_s' => 0, 'weight_g' => 0, 'extra_stops' => 0, 'wait_minutes' => 0, 'hour' => 12, 'fragile' => false, 'declared_value' => 0,
            'cod_amount' => 0, 'dropoff_zone_ids' => [], 'vehicle_multiplier_bp' => 10_000, 'surge_multiplier_bp' => 10_000, 'tax_rate_bp' => 0, 'discount' => 0];

        $weekday = $calc->calculate($plan, $rules, $in + ['weekday' => 3, 'rush' => false]);
        $this->assertSame([['label' => 'base', 'amount' => 100_000], ['label' => 'fuel_surcharge', 'amount' => 5_000]], $weekday['lines']);

        $saturdayRush = $calc->calculate($plan, $rules, $in + ['weekday' => 6, 'rush' => true]);
        // 100,000 + 10% weekend = 110,000; + 20% rush + 5,000 flat = 137,000; + 5% fuel = 143,850
        $this->assertSame(['base', 'weekend', 'rush', 'fuel_surcharge'], array_column($saturdayRush['lines'], 'label'));
        $this->assertSame(143_850, $saturdayRush['total']);
    }

    // ------------------------------------------------------------------ receipts

    public function test_a_paid_order_has_a_downloadable_receipt_with_a_stable_number(): void
    {
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);
        $s = $this->shipmentFor($a);

        $res = $this->api()->get("/api/v1/customer/orders/{$s->public_id}/receipt");
        $res->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $number = DB::table('invoices')->where('order_id', $s->order_id)->value('number');
        $this->api()->get("/api/v1/customer/orders/{$s->public_id}/receipt")->assertOk();
        $this->assertSame(1, DB::table('invoices')->where('order_id', $s->order_id)->count());
        $this->assertSame($number, DB::table('invoices')->where('order_id', $s->order_id)->value('number'));

        $this->api($this->makeUser('Eve'))->get("/api/v1/customer/orders/{$s->public_id}/receipt")->assertNotFound();
        $this->actingAs($this->customer)->get("/account/orders/{$s->public_id}/receipt")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    // ------------------------------------------------------------------ web pages

    private function staff(array $perms): User
    {
        $u = $this->makeUser('Staff');
        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo($perms);

        return $u;
    }

    public function test_staff_create_promo_codes_corporate_discounts_and_surcharge_rules(): void
    {
        $u = $this->staff(['View promo code', 'Create promo code', 'Update promo code', 'Manage pricing rules']);
        $this->actingAs($u)->get('/ops/promos')->assertOk()->assertSee('No promo codes yet');
        $this->actingAs($u)->post('/ops/promos', ['code' => 'save20', 'type' => 'percent', 'value' => 20, 'cap_naira' => 1000])->assertRedirect();
        $this->assertSame(['value' => 2000, 'cap' => 100_000], array_map('intval', (array) DB::table('promo_codes')->where('code', 'SAVE20')->first(['value', 'cap'])));
        $this->actingAs($u)->post('/ops/promos', ['code' => 'SAVE20', 'type' => 'fixed', 'value' => 5])->assertSessionHas('error');
        $this->actingAs($u)->post('/ops/corporate-discounts', ['name' => 'Acme', 'rate' => 7.5, 'user_email' => $this->customer->email])->assertSessionHas('success');
        $this->assertSame(750, (int) DB::table('corporate_discounts')->value('rate_bp'));
        $this->actingAs($u)->get('/ops/promos')->assertOk()->assertSee('SAVE20')->assertSee('Acme');

        $this->actingAs($u)->post('/ops/price-lists', ['name' => 'Bikes', 'operator_id' => $this->op, 'service_type_id' => $this->serviceTypeId(), 'min_fee_naira' => 1000])->assertRedirect();
        $plan = (int) DB::table('pricing_plans')->value('id');
        $this->actingAs($u)->post("/ops/price-lists/{$plan}/rules", ['kind' => 'base', 'amount_naira' => 1500])->assertSessionHas('success');
        $this->actingAs($u)->post("/ops/price-lists/{$plan}/rules", ['kind' => 'weekend', 'rate' => 10, 'days' => [6, 7]])->assertSessionHas('success');
        $this->actingAs($u)->post("/ops/price-lists/{$plan}/rules", ['kind' => 'fuel_surcharge'])->assertSessionHas('error');
        $this->actingAs($u)->post("/ops/price-lists/{$plan}/status", ['status' => 'active'])->assertSessionHas('success');
        $this->actingAs($u)->get("/ops/price-lists/{$plan}")->assertOk()->assertSee('Weekend surcharge')->assertSee('Sat, Sun');
        $this->assertSame(3, (int) DB::table('pricing_plans')->where('id', $plan)->value('version'));

        $this->actingAs($this->makeUser('Nobody'))->get('/ops/promos')->assertForbidden();
    }

    public function test_the_web_portal_books_with_stops_files_and_a_later_pickup_then_pays_with_a_promo(): void
    {
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $this->actingAs($this->customer)->post('/account/addresses', ['label' => 'Home', 'line1' => '5 Palm Close', 'lat' => 7.81, 'lng' => 5.91])->assertSessionHas('success');
        $this->actingAs($this->customer)->get('/account/addresses')->assertOk()->assertSee('5 Palm Close');
        $this->actingAs($this->customer)->get('/account/requests/new')->assertOk()->assertSee('Choose a saved place')->assertSee('Schedule for later');

        $this->actingAs($this->customer)->post('/account/requests', [
            'type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city, 'items' => 'Box', 'provider' => $this->op,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92],
            'when' => 'later', 'pickup_at' => now()->addDay()->format('Y-m-d\\TH:i'), 'rush' => '1',
            'stops' => [['type' => 'dropoff', 'line1' => '2 First Drop', 'lat' => '7.81', 'lng' => '5.91'], ['type' => 'dropoff', 'line1' => 'no pin']],
            'files' => [UploadedFile::fake()->image('item.jpg')],
        ])->assertRedirect()->assertSessionHas('success');
        $req = DB::table('service_requests')->first();
        $this->assertNotNull($req->pickup_at);
        $this->assertTrue((bool) $req->rush);
        $this->assertCount(1, json_decode($req->stops, true)['extra']);
        $this->assertSame(1, DB::table('request_attachments')->where('request_id', $req->id)->count());
        $this->actingAs($this->customer)->get('/account/requests/'.$req->public_id)->assertOk()->assertSee('2 First Drop')->assertSee('Rush');

        $this->promo();
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        $pub = DB::table('agreements')->where('id', $a)->value('public_id');
        $this->actingAs($this->customer)->post("/account/pay/{$pub}/promo", ['code' => 'WELCOME10'])->assertSessionHas('success');
        $this->actingAs($this->customer)->get("/account/pay/{$pub}")->assertOk()->assertSee('Promo WELCOME10')->assertSee('₦2,700');
    }
}
