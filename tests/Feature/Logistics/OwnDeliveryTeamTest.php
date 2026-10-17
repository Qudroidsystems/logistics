<?php

namespace Tests\Feature\Logistics;

use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/**
 * A shop that runs its own riders as a provider on the marketplace sends its own-delivery orders straight to that
 * team ("provider": "own"). The team's staff assign a rider in their dispatch, and the shop's app shows its customer
 * the rider live through GET /deliveries/{id}/tracking.
 */
class OwnDeliveryTeamTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $city;
    private int $team;
    private object $teamOwner;
    private int $other;
    private int $merchantId;
    private object $owner;
    private array $h;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');

        [$mOp, $this->owner] = $this->makeProvider('merchant', 'GozakMart');
        $this->merchantId = DB::table('merchants')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $mOp, 'merchant_code' => 'GOZAK1', 'display_name' => 'GozakMart',
            'status' => 'verified', 'settlement_mode' => 'pay_at_checkout', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('merchant_branding')->insert(['merchant_id' => $this->merchantId, 'marketplace_display_name' => 'MoveIt Marketplace', 'created_at' => now(), 'updated_at' => now()]);
        $this->h = ['Authorization' => 'Bearer '.app(ApiClientService::class)->issue($mOp, $this->merchantId, 'GozakMart', 'live')['key']];

        // GozakMart's riders, registered as a provider by the same person.
        [$this->team, $this->teamOwner] = $this->makeProvider('company', 'GozakMart Logistics');
        DB::table('operator_members')->insert(['operator_id' => $this->team, 'user_id' => $this->owner->id, 'role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        [$this->other] = $this->makeProvider('company', 'Someone Else Ltd');
    }

    private function body(array $over = []): array
    {
        return array_replace_recursive(['pickup' => ['line1' => 'GozakMart, Ikeja', 'lat' => 7.80, 'lng' => 5.90],
            'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_name' => 'Ada', 'contact_phone' => '08030000002'], 'weight_g' => 2000], $over);
    }

    private function link(): void
    {
        $this->actingAs($this->owner)->post('/merchant/api/own-team', ['operator_id' => $this->team])->assertSessionHas('success');
    }

    public function test_the_shop_links_a_team_it_runs_and_not_someone_elses(): void
    {
        $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-1', 'provider' => 'own', 'price' => 150000]), $this->h)
            ->assertStatus(422)->assertJsonFragment(['message' => 'No own delivery team is linked to this merchant. Link your provider account on the API page first.']);

        $this->actingAs($this->owner)->get('/merchant/api')->assertOk()->assertSee('Your own delivery team')->assertSee('GozakMart Logistics')->assertDontSee('Someone Else Ltd');
        $this->actingAs($this->owner)->post('/merchant/api/own-team', ['operator_id' => $this->other])->assertSessionHas('error');
        $this->assertNull(DB::table('merchants')->value('own_provider_operator_id'));

        $this->link();
        $this->assertSame($this->team, (int) DB::table('merchants')->value('own_provider_operator_id'));
        $this->assertSame('GozakMart Logistics', $this->getJson('/api/v1/partner/me', $this->h)->json('own_provider.name'));
    }

    public function test_an_own_delivery_goes_straight_to_the_team_with_nothing_to_pay(): void
    {
        $this->link();
        $r = $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-2', 'provider' => 'own', 'price' => 150000]), $this->h)->assertCreated();

        $this->assertSame('GozakMart Logistics', $r->json('provider.name'));
        $this->assertSame(150000, $r->json('price'));
        $this->assertNull($r->json('payment_url'));
        $this->assertSame($this->team, (int) DB::table('agreements')->value('provider_operator_id'));
        $this->assertSame('own_delivery', DB::table('orders')->value('payment_method'));
        $this->assertSame('awaiting_dispatch', DB::table('shipments')->value('status'));
        $this->assertSame($this->team, (int) DB::table('shipments')->value('operator_id'));
        $this->assertTrue((bool) json_decode(DB::table('agreements')->value('terms'), true)['own_delivery']);
        $this->assertLedgerBalanced();

        // The team's staff see the job on their dispatch board, ready to assign a rider.
        $this->actingAs($this->teamOwner)->get('/provider/dispatch')->assertOk()->assertSee(DB::table('orders')->value('order_number'));

        // Sent again (a retry): the same delivery.
        $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-2', 'provider' => 'own', 'price' => 150000]), $this->h)->assertCreated();
        $this->assertSame(1, DB::table('orders')->count());
    }

    public function test_without_a_price_the_teams_price_list_is_used(): void
    {
        $this->link();
        $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-3', 'provider' => 'own']), $this->h)
            ->assertStatus(422)->assertJsonFragment(['message' => 'Your delivery team has no price list for this delivery. Send a price, or add a price list for the team.']);

        DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $this->team, 'city_id' => $this->city, 'service_type_id' => $this->serviceTypeId(),
            'name' => 'Standard', 'min_fee' => 90000, 'rounding_kobo' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $r = $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-3', 'provider' => 'own', 'price' => 0]), $this->h)->assertCreated();
        $this->assertSame('GozakMart Logistics', $r->json('provider.name'));
        $this->assertGreaterThanOrEqual(90000, $r->json('price'));
    }

    public function test_the_shop_app_follows_the_rider_and_the_customer_can_reply(): void
    {
        $this->link();
        $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-4', 'provider' => 'own', 'price' => 150000]), $this->h)->assertCreated();
        $s = DB::table('shipments')->first();
        app(\App\Modules\Tracking\TrackingService::class)->arm((int) $s->id);

        $t = $this->getJson('/api/v1/partner/deliveries/GZ-4/tracking', $this->h)->assertOk();
        $this->assertSame('awaiting_dispatch', $t->json('delivery_status'));
        $this->assertSame('GozakMart Logistics', $t->json('provider.name'));
        $this->assertNull($t->json('driver'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $t->json('delivery_code'));
        $this->assertCount(2, $t->json('stops'));
        $this->assertFalse($t->json('can_reply'));
        $this->postJson('/api/v1/partner/deliveries/GZ-4/reply', ['reply' => 'ready'], $this->h)->assertStatus(422);

        // The team's staff assign one of their riders; the rider is on the way.
        $rider = $this->makeUser('Musa Bello');
        $dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->team, 'user_id' => $rider->id, 'status' => 'active', 'availability' => 'busy',
            'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::statement('UPDATE driver_profiles SET last_point = ST_SetSRID(ST_MakePoint(5.91, 7.81), 4326)::geography WHERE id = ?', [$dp]);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->team, 'driver_profile_id' => $dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'in_transit']);

        $t = $this->getJson('/api/v1/partner/deliveries/GZ-4/tracking', $this->h)->assertOk();
        $this->assertSame('in_transit', $t->json('delivery_status'));
        $this->assertSame('Musa', $t->json('driver.first_name'));
        $this->assertEqualsWithDelta(7.81, $t->json('driver.lat'), 0.0001);
        $this->assertTrue($t->json('can_reply'));
        $this->assertArrayHasKey('ready', $t->json('reply_options'));

        $this->postJson('/api/v1/partner/deliveries/GZ-4/reply', ['reply' => 'call_me', 'note' => 'Blue gate'], $this->h)->assertOk();
        $this->assertSame(['call_me', 'Blue gate'], [DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->value('readiness'),
            DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->value('readiness_note')]);
        $this->assertSame('call_me', $this->getJson('/api/v1/partner/deliveries/GZ-4/tracking', $this->h)->json('reply.reply'));

        // Delivered: no code any more.
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'delivered', 'delivered_at' => now()]);
        $this->assertNull($this->getJson('/api/v1/partner/deliveries/GZ-4/tracking', $this->h)->json('delivery_code'));

        // Another shop cannot look.
        $this->getJson('/api/v1/partner/deliveries/NOPE/tracking', $this->h)->assertNotFound();
        $this->getJson('/api/v1/partner/deliveries/GZ-4/tracking')->assertStatus(401);
    }

    public function test_staff_can_link_the_team_from_ops(): void
    {
        $staff = \App\Models\User::factory()->create(['must_change_password' => false]);
        foreach (['View vendor', 'Update vendor'] as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $staff->givePermissionTo(['View vendor', 'Update vendor']);
        $this->actingAs($staff)->get("/ops/merchants/{$this->merchantId}")->assertOk()->assertSee('Own delivery team');
        $this->actingAs($staff)->post("/ops/merchants/{$this->merchantId}/own-team", ['operator_id' => $this->other])->assertSessionHas('success');
        $this->assertSame($this->other, (int) DB::table('merchants')->value('own_provider_operator_id'));
        $this->actingAs($staff)->post("/ops/merchants/{$this->merchantId}/own-team", ['operator_id' => null])->assertSessionHas('success');
        $this->assertNull(DB::table('merchants')->value('own_provider_operator_id'));
    }
}
