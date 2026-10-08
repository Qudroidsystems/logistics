<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Marketplace\NegotiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** The customer's browser area: ask, compare, negotiate, pay, follow the order. */
class CustomerAccountTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['must_change_password' => false]);
    }

    private function city(): int
    {
        if ($id = DB::table('cities')->value('id')) {
            return (int) $id;
        }
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");

        return (int) DB::table('cities')->value('id');
    }

    public function test_guests_are_sent_to_login_and_customers_see_their_home(): void
    {
        $this->seedFoundation();
        $this->get('/account')->assertRedirect();
        $this->actingAs($this->customer())->get('/account')->assertOk()->assertSee('Ask for a delivery');
        $this->actingAs($this->customer())->get('/account/requests/zzz')->assertNotFound();
        $this->actingAs($this->customer())->get('/account/requests/new')->assertOk()->assertSee('Where');
    }

    public function test_a_request_needs_both_map_pins(): void
    {
        $this->seedFoundation();
        $c = $this->customer();
        $city = $this->city();

        $this->actingAs($c)->post('/account/requests', [
            'type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $city, 'items' => "Box of books",
            'pickup' => ['line1' => '1 Market Rd'], 'dropoff' => ['line1' => '9 Hill St'],
        ])->assertSessionHasErrors();
        $this->assertSame(0, DB::table('service_requests')->count());
    }

    public function test_the_whole_journey_from_request_to_paid_order(): void
    {
        $this->seedFoundation();
        $c = $this->customer();
        $this->fundCustomer($c->id, 5_000_000);
        [$op, $owner] = $this->makeProvider();
        DB::table('provider_profiles')->insert([
            'public_id' => (string) \Illuminate\Support\Str::ulid(), 'operator_id' => $op, 'public_slug' => 'swift', 'listed' => true,
            'service_types' => json_encode([$this->serviceTypeId()]), 'vehicle_types' => json_encode([]), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->actingAs($c)->post('/account/requests', [
            'type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city(), 'items' => "Box of books\nShoes",
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92],
            'provider' => $op,
        ]);
        $res->assertRedirect();
        $request = DB::table('service_requests')->first();
        $this->assertSame('direct', $request->visibility);
        $this->actingAs($c)->get('/account/requests/'.$request->public_id)->assertOk();

        // The provider replies with an offer.
        $threadId = app(NegotiationService::class)->providerOffer($request->id, $op, $owner->id, ['price' => 300_000], 'Can do today');
        $thread = DB::table('negotiation_threads')->where('id', $threadId)->value('public_id');

        $this->actingAs($c)->get("/account/negotiations/{$thread}")->assertOk()->assertSee('₦3,000')->assertSee('Accept this offer');
        $offerId = DB::table('negotiation_messages')->where(['thread_id' => $threadId, 'kind' => 'counter_offer'])->value('id');

        $accept = $this->actingAs($c)->post("/account/negotiations/{$thread}/accept", ['offer_id' => $offerId]);
        $agreement = DB::table('agreements')->first();
        $accept->assertRedirect(route('account.pay', $agreement->public_id));
        $this->actingAs($c)->get("/account/pay/{$agreement->public_id}")->assertOk()->assertSee('Pay');

        $pay = $this->actingAs($c)->post("/account/pay/{$agreement->public_id}", ['method' => 'wallet']);
        $shipment = DB::table('shipments')->value('public_id');
        $pay->assertRedirect(route('account.order', $shipment));
        $this->actingAs($c)->get("/account/orders/{$shipment}")->assertOk()->assertSee('Progress');
        $this->actingAs($c)->get('/account/orders')->assertOk();

        // Nobody else can open this customer's order, agreement or negotiation.
        $other = $this->customer();
        $this->actingAs($other)->get("/account/orders/{$shipment}")->assertNotFound();
        $this->actingAs($other)->get("/account/pay/{$agreement->public_id}")->assertNotFound();
        $this->actingAs($other)->get("/account/negotiations/{$thread}")->assertNotFound();
    }

    public function test_wallet_page_and_topup_validation(): void
    {
        $this->seedFoundation();
        $c = $this->customer();
        $this->actingAs($c)->get('/account/wallet')->assertOk()->assertSee('Add money');
        $this->actingAs($c)->post('/account/wallet/top-up', ['amount_naira' => 5])->assertSessionHasErrors('amount_naira');
    }
}
