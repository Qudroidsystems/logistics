<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** The rider's chat with dispatch (separate from the customer chat) and searching the provider's jobs. */
class DispatchChatAndJobSearchTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;
    private User $driver;
    private int $dp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = $this->makeUser('Ada Customer');
        $this->fundCustomer($this->customer->id, 5_000_000);
        $this->driver = $this->makeUser('Rider Musa');
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $this->driver->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $this->driver->id)->update(['current_operator_id' => $this->op]);
        $this->dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $this->driver->id, 'status' => 'active', 'availability' => 'on_job', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function job(): object
    {
        $agreement = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000, 0, 0, ['packages' => [['description' => 'Box']]]);
        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.*', 'orders.order_number']);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->update(['contact_name' => 'Bisi Receiver', 'contact_phone' => '08031234567']);

        return $s;
    }

    public function test_rider_and_dispatch_talk_and_the_customer_cannot_see_it(): void
    {
        $s = $this->job();

        Sanctum::actingAs($this->driver->fresh());
        $this->postJson("/api/v1/driver/jobs/{$s->public_id}/dispatch-chat", ['body' => 'Customer is not answering'])->assertOk();
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'delivery.dispatch_message'])->exists(), 'the desk is told');

        $thread = $this->actingAs($this->owner)->getJson("/provider/jobs/{$s->public_id}/dispatch-chat")->assertOk();
        $this->assertSame('dispatch', $thread->json('me'));
        $this->assertSame('Customer is not answering', $thread->json('messages.0.body'));
        $this->actingAs($this->owner)->postJson("/provider/jobs/{$s->public_id}/dispatch-chat", ['body' => 'Wait 10 more minutes'])->assertOk();

        Sanctum::actingAs($this->driver->fresh());
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertOk()->assertJsonPath('dispatch_unread', 1);
        $mine = $this->getJson("/api/v1/driver/jobs/{$s->public_id}/dispatch-chat")->assertOk();
        $this->assertSame('Dispatch', $mine->json('other'));
        $this->assertCount(2, $mine->json('messages'));
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertOk()->assertJsonPath('dispatch_unread', 0);

        // The customer's chat is a different conversation.
        Sanctum::actingAs($this->customer);
        $this->assertSame([], $this->getJson("/api/v1/customer/shipments/{$s->public_id}/chat")->assertOk()->json('messages'));
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}/dispatch-chat")->assertNotFound();
    }

    public function test_the_chat_closes_when_no_rider_holds_the_job(): void
    {
        $s = $this->job();
        DB::table('assignments')->where('shipment_id', $s->id)->update(['status' => 'released']);

        $this->actingAs($this->owner)->postJson("/provider/jobs/{$s->public_id}/dispatch-chat", ['body' => 'Hello?'])->assertStatus(422);
        $this->assertFalse($this->actingAs($this->owner)->getJson("/provider/jobs/{$s->public_id}/dispatch-chat")->assertOk()->json('open'));
    }

    public function test_jobs_can_be_searched_by_order_customer_address_or_phone(): void
    {
        $s = $this->job();
        $this->job(); // another job that should not match a phone search

        foreach ([$s->order_number, 'ada cust', 'Bisi', '0803 123'] as $q) {
            $this->actingAs($this->owner)->get('/provider/jobs?status=all&q='.urlencode($q))->assertOk()->assertSee($s->order_number, false);
        }
        $this->actingAs($this->owner)->get('/provider/jobs?status=all&q=nothing-like-this')->assertOk()->assertSee('No jobs match');
    }
}
