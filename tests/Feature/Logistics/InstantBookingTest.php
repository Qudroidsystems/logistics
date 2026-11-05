<?php

namespace Tests\Feature\Logistics;

use App\Modules\Partner\RouteEstimator;
use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** The homepage price form: price without signing in, sign up, book, pay; and "track your order" by code. */
class InstantBookingTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        $this->mock(RouteEstimator::class, fn ($m) => $m->shouldReceive('estimate')->andReturn(['distance_m' => 8_000, 'duration_s' => 900]));
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Waterloo', 'waterloo', ST_SetSRID(ST_MakePoint(-80.52, 43.46), 4326)::geography, 'live', now(), now())");
        $city = (int) DB::table('cities')->value('id');
        [$this->op] = $this->makeProvider('company', 'CityHop');
        $sameDay = (int) DB::table('service_types')->where('code', 'same_day')->value('id');
        $plan = DB::table('pricing_plans')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $city, 'service_type_id' => $sameDay,
            'name' => 'Same day', 'min_fee' => 1_500, 'rounding_kobo' => 25, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pricing_rules')->insert([
            ['plan_id' => $plan, 'kind' => 'base', 'amount' => 1_000, 'condition' => null, 'priority' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['plan_id' => $plan, 'kind' => 'item_size', 'amount' => 800, 'condition' => json_encode(['sizes' => ['large']]), 'priority' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        config(['booking.operator_id' => $this->op, 'booking.vehicles' => []]);
        $this->customer = $this->makeUser('Ada');
    }

    private function body(array $o = []): array
    {
        return $o + ['pickup' => ['lat' => 43.45, 'lng' => -80.49, 'line1' => '1 King St W, Kitchener'], 'dropoff' => ['lat' => 43.47, 'lng' => -80.54, 'line1' => '200 University Ave W, Waterloo'],
            'service' => 'same_day', 'size' => 'small', 'fragile' => false];
    }

    public function test_anyone_can_get_a_price_and_bigger_items_cost_more(): void
    {
        $this->get('/')->assertOk()->assertSee('Get my price');
        $this->getJson('/api/v1/public/quote-options')->assertOk()->assertJsonPath('enabled', true)->assertJsonPath('services.0.code', 'same_day');

        $small = $this->postJson('/api/v1/public/quote', $this->body())->assertOk()->json();
        $large = $this->postJson('/api/v1/public/quote', $this->body(['size' => 'large']))->assertOk()->json();
        $this->assertSame(1_500, $small['subtotal']);
        $this->assertSame(1_800, $large['subtotal']);
        $this->assertSame(8.0, (float) $small['distance_km']);

        $this->postJson('/api/v1/public/quote', $this->body(['pickup' => ['lat' => 6.5, 'lng' => 3.4]]))->assertStatus(422);
        $this->postJson('/api/v1/public/quote', $this->body(['service' => 'scheduled', 'pickup_at' => now()->addMinutes(10)->toIso8601String()]))->assertStatus(422);
    }

    public function test_a_guest_signs_up_then_books_and_lands_on_the_pay_page(): void
    {
        $q = $this->postJson('/api/v1/public/quote', $this->body())->json('quote');
        $this->get("/book/$q")->assertOk()->assertSee('About you');

        $this->actingAs($this->customer)->get("/book/$q")->assertOk()->assertSee('Continue to payment');
        $details = ['pickup_name' => 'Ada', 'pickup_phone' => '(226) 555-0123', 'dropoff_name' => 'Bo', 'dropoff_phone' => '519 555 0199', 'description' => 'A box of books'];
        $res = $this->actingAs($this->customer)->post("/book/$q", $details);
        $agreement = DB::table('agreements')->where('customer_id', $this->customer->id)->first();
        $res->assertRedirect(route('account.pay', $agreement->public_id));
        $this->assertSame('locked', $agreement->status);
        $terms = json_decode($agreement->terms, true);
        $this->assertSame('instant', $terms['channel']);
        $this->assertSame('A box of books', $terms['packages'][0]['description']);

        // Pressing the button twice books once.
        $this->actingAs($this->customer)->post("/book/$q", $details)->assertRedirect(route('account.pay', $agreement->public_id));
        $this->assertSame(1, DB::table('agreements')->where('customer_id', $this->customer->id)->count());

        // Someone else cannot book this customer's price.
        $this->actingAs($this->makeUser('Eve'))->post("/book/$q", $details)->assertSessionHas('error');
    }

    public function test_a_guest_books_without_signing_up_first(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendPlainEmail::class]);
        $q = $this->postJson('/api/v1/public/quote', $this->body())->json('quote');
        $details = ['dropoff_name' => 'Bo', 'dropoff_phone' => '519 555 0199', 'description' => 'A box of books'];

        // An email that already has an account must sign in.
        $this->post("/book/$q", $details + ['guest_name' => 'Ada', 'guest_email' => $this->customer->email, 'guest_phone' => '2265550123', 'terms' => '1'])
            ->assertSessionHas('error')->assertSessionHas('sign_in');
        $this->assertGuest();

        $res = $this->post("/book/$q", $details + ['guest_name' => 'Gina Guest', 'guest_email' => 'gina@example.test', 'guest_phone' => '(226) 555-0177', 'terms' => '1']);
        $gina = User::where('email', 'gina@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($gina);
        $agreement = DB::table('agreements')->where('customer_id', $gina->id)->first();
        $res->assertRedirect(route('account.pay', $agreement->public_id));
        $this->assertSame('Gina Guest', json_decode($agreement->terms, true)['pickup']['contact_name']);
        $this->assertTrue(DB::table('customer_profiles')->where('user_id', $gina->id)->exists());
        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendPlainEmail::class, fn ($j) => $j->to === 'gina@example.test' && str_contains($j->body, '/password/reset/'));
    }

    public function test_the_app_books_through_the_api(): void
    {
        $q = $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/public/quote', $this->body())->json('quote');
        $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/instant-bookings', ['quote' => $q, 'pickup_name' => 'Ada', 'pickup_phone' => '2265550123',
            'dropoff_name' => 'Bo', 'dropoff_phone' => '5195550199', 'description' => 'Keys'])->assertCreated()->assertJsonStructure(['agreement']);
    }

    public function test_track_by_code(): void
    {
        $this->get('/track')->assertOk()->assertSee('Track a delivery');
        $this->get('/track?code=NOPE123')->assertOk()->assertSee('couldn');
    }
}
