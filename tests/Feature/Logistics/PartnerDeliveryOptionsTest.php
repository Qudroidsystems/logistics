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
 * A merchant's shop (e.g. an e-commerce app) shows its customer every provider with a price, the customer picks one,
 * and the shop books that provider — paid by the shop, or by the receiver in cash at the door.
 */
class PartnerDeliveryOptionsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $city;
    private int $cheap;
    private int $fancy;
    private int $merchantId;
    private array $h;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');

        [$this->cheap] = $this->makeProvider('company', 'Budget Bikes');
        [$this->fancy] = $this->makeProvider('company', 'Swift Haulage');
        foreach ([[$this->cheap, 150000], [$this->fancy, 250000]] as [$op, $fee]) {
            DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $op, 'city_id' => $this->city, 'service_type_id' => $this->serviceTypeId(),
                'name' => 'Standard', 'min_fee' => $fee, 'rounding_kobo' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('provider_profiles')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $this->fancy, 'public_slug' => 'swift', 'rating_avg' => 4.8, 'rating_count' => 120,
            'on_time_rate' => 97.5, 'jobs_completed' => 900, 'tier' => 'trusted', 'created_at' => now(), 'updated_at' => now()]);

        [$mOp, $mOwner] = $this->makeProvider('merchant', 'GozakMart');
        $this->fundCustomer($mOwner->id, 5_000_000);
        $this->merchantId = DB::table('merchants')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $mOp, 'merchant_code' => 'GOZAK1', 'display_name' => 'GozakMart',
            'status' => 'verified', 'settlement_mode' => 'prepaid_wallet', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('merchant_branding')->insert(['merchant_id' => $this->merchantId, 'marketplace_display_name' => 'MoveIt Marketplace', 'badge_text' => 'Delivered by MoveIt', 'created_at' => now(), 'updated_at' => now()]);
        $this->h = ['Authorization' => 'Bearer '.app(ApiClientService::class)->issue($mOp, $this->merchantId, 'GozakMart', 'live')['key']];
    }

    private function body(array $over = []): array
    {
        // No city_id and no service_type_id: both are worked out.
        return array_replace_recursive(['pickup' => ['line1' => 'GozakMart, Ikeja', 'lat' => 7.80, 'lng' => 5.90],
            'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_name' => 'Ada', 'contact_phone' => '08030000002'], 'weight_g' => 2000], $over);
    }

    public function test_the_customer_sees_every_provider_with_its_price_and_the_marketplace_name(): void
    {
        $r = $this->postJson('/api/v1/partner/quotes/options', $this->body(), $this->h)->assertOk();

        $this->assertSame('MoveIt Marketplace', $r->json('marketplace.name'));
        $this->assertSame($this->city, $r->json('city_id'));
        $this->assertSame(['Budget Bikes', 'Swift Haulage'], array_column(array_column($r->json('options'), 'provider'), 'name'));
        $this->assertSame([150000, 250000], array_column($r->json('options'), 'price'));
        $this->assertContains('cheapest', $r->json('options.0.tags'));
        $this->assertSame(4.8, $r->json('options.1.provider.rating'));
        $this->assertNotEmpty($r->json('options.0.eta.label'));
        $this->assertSame(26, strlen($r->json('options.0.option_id')));

        // A merchant that hides provider names shows a neutral label.
        DB::table('merchant_branding')->update(['show_provider_name' => false]);
        $this->assertSame('Delivery partner', $this->postJson('/api/v1/partner/quotes/options', $this->body(), $this->h)->json('options.0.provider.name'));

        // Somewhere no city covers.
        $this->postJson('/api/v1/partner/quotes/options', $this->body(['dropoff' => ['lat' => 4.0, 'lng' => 9.0]]), $this->h)
            ->assertStatus(422)->assertJson(['message' => 'We do not deliver to that location yet.']);
        $this->postJson('/api/v1/partner/quotes/options', $this->body())->assertStatus(401);
    }

    public function test_the_chosen_provider_is_booked_at_the_quoted_price(): void
    {
        $opts = $this->postJson('/api/v1/partner/quotes/options', $this->body(), $this->h)->json('options');
        $swift = collect($opts)->firstWhere('provider.name', 'Swift Haulage');

        $r = $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-1001', 'option_id' => $swift['option_id']]), $this->h)->assertCreated();
        $this->assertSame('Swift Haulage', $r->json('provider.name'));
        $this->assertSame(250000, $r->json('price'));
        $this->assertSame('merchant', $r->json('fee_payer'));
        $this->assertSame($this->fancy, (int) DB::table('agreements')->value('provider_operator_id'));

        // Same order again: the same delivery comes back.
        $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-1001', 'option_id' => $swift['option_id']]), $this->h)->assertCreated();
        $this->assertSame(1, DB::table('orders')->count());
        $this->assertSame('Swift Haulage', $this->getJson('/api/v1/partner/deliveries/GZ-1001', $this->h)->json('provider.name'));
    }

    public function test_an_expired_option_is_priced_again_with_the_same_provider_and_capped_by_max_price(): void
    {
        $opt = $this->postJson('/api/v1/partner/quotes/options', $this->body(), $this->h)->json('options.0');
        DB::table('quotes')->where('public_id', $opt['option_id'])->update(['expires_at' => now()->subMinute()]);
        DB::table('pricing_plans')->where('operator_id', $this->cheap)->update(['min_fee' => 180000]);

        $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-2', 'option_id' => $opt['option_id'], 'max_price' => 150000]), $this->h)
            ->assertStatus(422)->assertJsonFragment(['error' => 'cannot_create']);
        $ok = $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-2', 'option_id' => $opt['option_id'], 'max_price' => 200000]), $this->h)->assertCreated();
        $this->assertSame(180000, $ok->json('price'));
        $this->assertSame('Budget Bikes', $ok->json('provider.name'));

        // Another merchant's option is refused.
        DB::table('quotes')->where('public_id', $opt['option_id'])->update(['request' => json_encode(['merchant_id' => 999])]);
        $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-3', 'option_id' => $opt['option_id']]), $this->h)
            ->assertStatus(422)->assertJson(['message' => 'Unknown option_id. Ask for delivery options again.']);
    }

    public function test_the_receiver_can_pay_the_fee_to_the_rider(): void
    {
        $opt = $this->postJson('/api/v1/partner/quotes/options', $this->body(), $this->h)->json('options.0');
        $r = $this->postJson('/api/v1/partner/deliveries', $this->body(['external_order_id' => 'GZ-9', 'option_id' => $opt['option_id'], 'fee_payer' => 'receiver']), $this->h)->assertCreated();

        $this->assertSame(['receiver', 150000], [$r->json('fee_payer'), $r->json('fee_on_delivery')]);
        $this->assertNull($r->json('payment_url'));
        $order = DB::table('orders')->first();
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame('awaiting_dispatch', DB::table('shipments')->value('status'));
        $this->assertSame(5_000_000, $this->walletBalance('customer', (int) DB::table('operator_members')->where('operator_id', DB::table('merchants')->value('operator_id'))->value('user_id')));
        $this->assertLedgerBalanced();

        // The rider sees the fee at the drop-off only.
        $driver = $this->makeUser('Rider');
        $dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->cheap, 'user_id' => $driver->id, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
        $s = DB::table('shipments')->first();
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->cheap, 'driver_profile_id' => $dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $job = app(\App\Modules\Dispatch\DriverService::class)->job($dp, $s->public_id);
        $this->assertSame(['pickup' => 0, 'dropoff' => 150000], collect($job['stops'])->mapWithKeys(fn ($st) => [$st['type'] => $st['fee_on_delivery']])->all());
    }

    public function test_the_catalog_lists_services_and_cities(): void
    {
        $r = $this->getJson('/api/v1/partner/catalog', $this->h)->assertOk();
        $this->assertContains('same_day', array_column($r->json('service_types'), 'code'));
        $this->assertSame([['id' => $this->city, 'name' => 'Testville']], array_map(fn ($c) => array_intersect_key($c, array_flip(['id', 'name'])), $r->json('cities')));
        $this->assertSame(['merchant', 'receiver'], $r->json('fee_payers'));
    }

    public function test_the_merchant_portal_explains_the_checkout_integration(): void
    {
        $owner = \App\Models\User::find(DB::table('operator_members')->where('operator_id', DB::table('merchants')->value('operator_id'))->value('user_id'));
        $this->actingAs($owner)->get('/merchant/api')->assertOk()->assertSee("Connect your shop's checkout")->assertSee('/quotes/options');
    }
}
