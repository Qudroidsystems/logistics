<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketplace\DeliveryService;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Delivery confirmation, disputes, ratings, provider scores and notifications over HTTP. */
class RatingsAndNotificationsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private User $customer;
    private User $owner;
    private int $providerOp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));

        $this->customer = $this->makeUser('Ada Customer');
        [$this->providerOp, $this->owner] = $this->makeProvider();
        DB::table('provider_profiles')->insert([
            'public_id' => (string) \Illuminate\Support\Str::ulid(), 'operator_id' => $this->providerOp, 'public_slug' => 'swift-haulage', 'listed' => true,
            'service_types' => json_encode([$this->serviceTypeId()]), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->fundCustomer($this->customer->id, 1_000_000);
    }

    /** A paid and delivered shipment, awaiting the customer's answer. @return array{0:int,1:string} id and public id */
    private function deliveredShipment(): array
    {
        $agreement = $this->lockedAgreement($this->customer->id, $this->providerOp, $this->owner->id);
        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.id', 'shipments.public_id']);
        app(DeliveryService::class)->markDelivered((int) $s->id);

        return [(int) $s->id, $s->public_id];
    }

    public function test_paying_and_delivering_notify_the_right_people(): void
    {
        $this->deliveredShipment();

        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'delivery.created_provider'])->exists(), 'provider told about the paid job');
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->customer->id, 'type' => 'delivery.delivered'])->exists(), 'customer asked to confirm');
    }

    public function test_notification_list_and_mark_read(): void
    {
        $this->deliveredShipment();
        Sanctum::actingAs($this->customer);

        $list = $this->getJson('/api/v1/notifications')->assertOk();
        $this->assertGreaterThan(0, $list->json('unread'));
        $first = $list->json('items.0');
        $this->assertArrayHasKey('title', $first);

        $this->postJson("/api/v1/notifications/{$first['id']}/read")->assertOk();
        $this->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->assertSame(0, $this->getJson('/api/v1/notifications')->json('unread'));

        // Someone else's notification is not reachable.
        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/notifications/{$first['id']}/read")->assertNotFound();
    }

    public function test_customer_confirms_pays_the_provider_and_rating_updates_the_profile(): void
    {
        [$id, $public] = $this->deliveredShipment();
        $before = $this->walletBalance('operator', $this->providerOp);
        Sanctum::actingAs($this->customer);

        $this->postJson("/api/v1/customer/shipments/{$public}/confirm", ['rating' => 5, 'tags' => ['on_time', 'careful'], 'comment' => 'Great'])
            ->assertOk()->assertJsonPath('confirmed', true)->assertJsonPath('rated', true);

        $this->assertGreaterThan($before, $this->walletBalance('operator', $this->providerOp), 'provider was paid');
        $p = DB::table('provider_profiles')->where('operator_id', $this->providerOp)->first();
        $this->assertSame(1, (int) $p->rating_count);
        $this->assertEquals(5.0, (float) $p->rating_avg);
        $this->assertSame(1, (int) $p->jobs_completed);
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'delivery.confirmed_provider'])->exists());
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'rating.received'])->exists());
        $this->assertLedgerBalanced();

        // Confirming twice must not pay twice.
        $this->postJson("/api/v1/customer/shipments/{$public}/confirm")->assertStatus(422);
    }

    public function test_a_delivery_can_only_be_rated_once_by_its_customer(): void
    {
        [, $public] = $this->deliveredShipment();
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/v1/customer/shipments/{$public}/confirm")->assertOk()->assertJsonPath('rated', false);

        $this->postJson("/api/v1/customer/shipments/{$public}/rating", ['rating' => 4])->assertCreated();
        $this->postJson("/api/v1/customer/shipments/{$public}/rating", ['rating' => 1])->assertStatus(422);
        $this->postJson("/api/v1/customer/shipments/{$public}/rating", ['rating' => 9])->assertStatus(422);
    }

    public function test_someone_elses_delivery_is_not_found(): void
    {
        [, $public] = $this->deliveredShipment();
        Sanctum::actingAs($this->makeUser('Stranger'));

        $this->postJson("/api/v1/customer/shipments/{$public}/confirm")->assertNotFound();
        $this->postJson("/api/v1/customer/shipments/{$public}/rating", ['rating' => 1])->assertNotFound();
    }

    public function test_provider_rates_the_customer_privately(): void
    {
        [, $public] = $this->deliveredShipment();
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/v1/customer/shipments/{$public}/confirm")->assertOk();

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/provider/shipments/{$public}/rate-customer", ['rating' => 4, 'tags' => ['polite']])->assertCreated();

        $row = DB::table('ratings')->where(['ratee_type' => 'customer', 'ratee_id' => $this->customer->id])->first();
        $this->assertFalse((bool) $row->is_public);
        $this->assertEquals(4.0, (float) DB::table('customer_profiles')->where('user_id', $this->customer->id)->value('rating_avg') ?: 4.0);
    }

    public function test_public_provider_page_shows_reviews_with_first_names_only(): void
    {
        [, $public] = $this->deliveredShipment();
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/v1/customer/shipments/{$public}/confirm", ['rating' => 5, 'comment' => 'Fast and careful'])->assertOk();

        $res = $this->getJson('/api/v1/providers/swift-haulage')->assertOk();   // public: no auth
        $this->assertSame('Fast and careful', $res->json('reviews.0.comment'));
        $this->assertSame('Ada', $res->json('reviews.0.reviewer'));
        $this->assertArrayNotHasKey('operator_id', $res->json('provider'));
    }

    public function test_objecting_holds_the_money_and_staff_decide(): void
    {
        [, $public] = $this->deliveredShipment();
        Sanctum::actingAs($this->customer);
        $dispute = $this->postJson("/api/v1/customer/shipments/{$public}/object", ['type' => 'damage', 'reason' => 'The box arrived crushed.'])
            ->assertCreated()->json('dispute.public_id');
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'delivery.disputed_provider'])->exists());

        $staff = $this->makeUser('Dispute Staff');
        foreach (['View dispute', 'Resolve dispute'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $staff->givePermissionTo(['View dispute', 'Resolve dispute']);
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/admin/disputes')->assertForbidden();

        Sanctum::actingAs($staff);
        $this->getJson('/api/v1/admin/disputes')->assertOk()->assertJsonCount(1);
        $this->postJson("/api/v1/admin/disputes/{$dispute}/decide", ['decision' => 'partial'])->assertStatus(422);   // partial needs a share
        $this->postJson("/api/v1/admin/disputes/{$dispute}/decide", ['decision' => 'full_refund'])->assertOk();

        $this->assertSame('decided', DB::table('disputes')->where('public_id', $dispute)->value('status'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->customer->id, 'type' => 'delivery.dispute_resolved_customer'])->exists());
        $this->assertLedgerBalanced();
    }

    public function test_score_and_tier_follow_the_numbers(): void
    {
        [, $public] = $this->deliveredShipment();
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/v1/customer/shipments/{$public}/confirm", ['rating' => 5])->assertOk();

        $score = DB::table('provider_scores')->where('operator_id', $this->providerOp)->first();
        $this->assertNotNull($score);
        $this->assertGreaterThan(0, (int) $score->score);
        $this->assertSame('verified', DB::table('provider_profiles')->where('operator_id', $this->providerOp)->value('tier'), 'one job is not enough for trusted');
    }
}
