<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** A provider sees invited requests, makes an offer, counters, and an owner accepts. */
class ProviderRequestsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;
    private string $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = User::factory()->create(['must_change_password' => false]);
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        DB::table('provider_profiles')->insert([
            'public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'public_slug' => 'swift', 'listed' => true,
            'service_types' => json_encode([$this->serviceTypeId()]), 'vehicle_types' => json_encode([]), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($this->customer)->post('/account/requests', [
            'type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => (int) DB::table('cities')->value('id'), 'items' => 'Box of books',
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92],
            'provider' => $this->op,
        ])->assertRedirect();
        $this->request = (string) DB::table('service_requests')->value('public_id');
        auth()->logout();
    }

    public function test_invited_provider_sees_the_request_and_others_do_not(): void
    {
        $this->actingAs($this->owner)->get('/provider/requests')->assertOk()->assertSee('1 Market Rd');
        $this->actingAs($this->owner)->get("/provider/requests/{$this->request}")->assertOk()->assertSee('Make an offer')->assertSee('Box of books');
        $this->assertSame('viewed', DB::table('request_invitations')->where('operator_id', $this->op)->value('status'));

        [, $other] = $this->makeProvider('company', 'Rival Haulage');
        $this->actingAs($other)->get("/provider/requests/{$this->request}")->assertNotFound();
    }

    public function test_offer_counter_and_accept(): void
    {
        $res = $this->actingAs($this->owner)->post("/provider/requests/{$this->request}/offer", ['price_naira' => 3000, 'message' => 'Can do today']);
        $thread = (string) DB::table('negotiation_threads')->value('public_id');
        $res->assertRedirect(route('provider.thread', $thread));
        $this->assertSame(300_000, (int) json_decode(DB::table('negotiation_messages')->where('kind', 'counter_offer')->value('terms'), true)['price']);

        $this->actingAs($this->owner)->get("/provider/negotiations/{$thread}")->assertOk()->assertSee('₦3,000');
        $this->actingAs($this->owner)->get("/provider/requests/{$this->request}")->assertOk()->assertSee('Open the conversation');

        // The customer counters; now the provider can accept it.
        $this->actingAs($this->customer)->post("/account/negotiations/{$thread}/counter", ['price_naira' => 2500]);
        $this->actingAs($this->owner)->get("/provider/negotiations/{$thread}")->assertOk()->assertSee("Accept the customer's offer");

        $this->actingAs($this->owner)->post("/provider/negotiations/{$thread}/say", ['text' => 'Fine, thanks'])->assertSessionHasNoErrors();
        $latest = DB::table('negotiation_messages')->where('kind', 'counter_offer')->orderByDesc('id')->value('id');
        $this->actingAs($this->owner)->post("/provider/negotiations/{$thread}/accept", ['offer_id' => $latest])->assertRedirect(route('provider.requests'));
        $this->assertSame(1, DB::table('agreements')->count());
        $this->assertSame(250_000, (int) DB::table('agreements')->value('price'));
    }

    public function test_other_providers_cannot_open_the_thread_and_bad_price_is_rejected(): void
    {
        $this->actingAs($this->owner)->post("/provider/requests/{$this->request}/offer", ['price_naira' => 0])->assertSessionHasErrors('price_naira');
        $this->actingAs($this->owner)->post("/provider/requests/{$this->request}/offer", ['price_naira' => 3000]);
        $thread = (string) DB::table('negotiation_threads')->value('public_id');

        [, $other] = $this->makeProvider('company', 'Rival Haulage');
        $this->actingAs($other)->get("/provider/negotiations/{$thread}")->assertNotFound();
    }
}
