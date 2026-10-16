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

/** Chat between the customer and the rider on a job, and calls through the shared number. */
class RiderChatTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;
    private User $rider;
    private int $dp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = $this->makeUser('Ada Obi');
        $this->fundCustomer($this->customer->id, 5_000_000);
        $this->rider = $this->makeUser('Musa Bello');
        DB::table('users')->where('id', $this->customer->id)->update(['phone_number' => '08030000001']);
        DB::table('users')->where('id', $this->rider->id)->update(['phone_number' => '08030000002', 'current_operator_id' => $this->op]);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $this->rider->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->dp = $this->driverProfile($this->rider->id);
    }

    private function driverProfile(int $userId): int
    {
        return DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $userId, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
    }

    /** A paid job; with $rider, a rider has taken it. */
    private function job(bool $rider = true): object
    {
        $agreement = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000, 0, 0, ['packages' => [['description' => 'Box']]]);
        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.*']);
        DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->update(['contact_phone' => '08031234567']);
        if ($rider) {
            DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('shipments')->where('id', $s->id)->update(['status' => 'in_transit']);
        }

        return $s;
    }

    public function test_the_customer_and_rider_chat_and_the_chat_closes_when_the_job_ends(): void
    {
        $s = $this->job();

        Sanctum::actingAs($this->customer);
        $this->getJson("/api/v1/customer/orders/{$s->public_id}")->assertOk()->assertJsonPath('chat.open', true)->assertJsonPath('chat.rider', 'Musa');
        $this->postJson("/api/v1/customer/shipments/{$s->public_id}/chat", ['body' => '  Please   leave it with security '])->assertOk();

        Sanctum::actingAs($this->rider);
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertOk()->assertJsonPath('chat_unread', 1);
        $chat = $this->getJson("/api/v1/driver/jobs/{$s->public_id}/chat")->assertOk()
            ->assertJsonPath('me', 'driver')->assertJsonPath('other', 'Ada')->assertJsonPath('messages.0.body', 'Please leave it with security')->assertJsonPath('messages.0.mine', false)->json();
        $this->assertContains("I've arrived", $chat['quick']);
        $this->postJson("/api/v1/driver/jobs/{$s->public_id}/chat", ['body' => 'Okay, call me on 0803 000 0002'])->assertOk();
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertJsonPath('chat_unread', 0);

        Sanctum::actingAs($this->customer);
        $mine = $this->getJson("/api/v1/customer/shipments/{$s->public_id}/chat?after={$chat['messages'][0]['id']}")->assertOk()->json('messages');
        $this->assertCount(1, $mine);
        $this->assertSame('Okay, call me on 0803 000 0002', $mine[0]['body']);
        $this->assertTrue(DB::table('messages')->where('body', 'like', 'Okay%')->value('moderation_flag'));
        $this->assertTrue(DB::table('messages')->where('body', 'like', 'Please%')->whereNotNull('read_at')->exists(), 'the rider read it');

        // Someone else can't read it.
        Sanctum::actingAs($this->makeUser('Stranger'));
        $this->getJson("/api/v1/customer/shipments/{$s->public_id}/chat")->assertNotFound();

        // The job ends: the chat stays readable but takes no new messages, and the rider is no longer on it.
        DB::table('assignments')->where('shipment_id', $s->id)->update(['status' => 'completed']);
        Sanctum::actingAs($this->customer);
        $this->getJson("/api/v1/customer/shipments/{$s->public_id}/chat")->assertOk()->assertJsonPath('open', false)->assertJsonCount(2, 'messages');
        $this->postJson("/api/v1/customer/shipments/{$s->public_id}/chat", ['body' => 'Thanks'])->assertStatus(422)
            ->assertJsonPath('message', 'You can chat once a rider takes your job.');
        Sanctum::actingAs($this->rider);
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}/chat")->assertNotFound();

        // The provider's team sees the whole chat on the job page.
        $this->actingAs($this->owner)->get("/provider/jobs/{$s->public_id}")->assertOk()->assertSee('Rider and customer chat')->assertSee('Please leave it with security')->assertSee('number shared');
    }

    public function test_a_new_rider_takes_over_the_chat(): void
    {
        $s = $this->job();
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/v1/customer/shipments/{$s->public_id}/chat", ['body' => 'Gate 2'])->assertOk();

        $next = $this->makeUser('Tunde Ade');
        DB::table('assignments')->where('shipment_id', $s->id)->update(['status' => 'reassigned']);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->driverProfile($next->id), 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);

        Sanctum::actingAs($this->rider);
        $this->postJson("/api/v1/driver/jobs/{$s->public_id}/chat", ['body' => 'Hello'])->assertStatus(422);
        Sanctum::actingAs($next);
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}/chat")->assertOk()->assertJsonPath('messages.0.body', 'Gate 2');
        Sanctum::actingAs($this->customer);
        $this->getJson("/api/v1/customer/orders/{$s->public_id}")->assertJsonPath('chat.rider', 'Tunde');
    }

    public function test_no_chat_before_a_rider_takes_the_job(): void
    {
        $s = $this->job(rider: false);
        Sanctum::actingAs($this->customer);
        $this->getJson("/api/v1/customer/shipments/{$s->public_id}/chat")->assertOk()->assertJsonPath('open', false)->assertJsonPath('quick', []);
        $this->postJson("/api/v1/customer/shipments/{$s->public_id}/chat", ['body' => 'Hi'])->assertStatus(422);
    }

    public function test_with_calls_off_the_rider_still_sees_the_number_and_the_app_cannot_call(): void
    {
        config(['services.calls.driver' => 'off']);
        $s = $this->job();
        Sanctum::actingAs($this->rider);
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertJsonPath('stops.1.contact_phone', '08031234567')->assertJsonPath('stops.1.masked_call', false);
        $this->postJson("/api/v1/driver/jobs/{$s->public_id}/call", ['who' => 'dropoff'])->assertStatus(422)->assertJsonPath('message', 'Calls through the app are not set up.');
    }

    public function test_calls_go_through_the_shared_number_both_ways(): void
    {
        config(['services.calls.driver' => 'log', 'services.calls.number' => '+2342017000000', 'services.calls.callback_secret' => 'sekret']);
        $s = $this->job();

        // The rider sees only the last digits and calls the receiver through the app.
        Sanctum::actingAs($this->rider);
        $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertJsonPath('stops.1.contact_phone', '•••• 4567')->assertJsonPath('stops.1.masked_call', true);
        $this->postJson("/api/v1/driver/jobs/{$s->public_id}/call", ['who' => 'dropoff'])->assertOk()->assertJsonPath('ok', true);
        $call = DB::table('call_logs')->first();
        $this->assertSame(['dropoff', '2348030000002', '2348031234567', 'ringing'], [$call->purpose, $call->from_number, $call->to_number, $call->outcome]);

        // The rider answers our ring: we connect them to the receiver, showing the shared number.
        $this->post('/api/v1/voice/wrong', ['sessionId' => $call->provider_ref, 'isActive' => '1'])->assertNotFound();
        $xml = $this->post('/api/v1/voice/sekret', ['sessionId' => $call->provider_ref, 'isActive' => '1', 'direction' => 'Outbound'])->assertOk()->getContent();
        $this->assertStringContainsString('<Dial phoneNumbers="+2348031234567" callerId="+2342017000000" />', $xml);
        $this->post('/api/v1/voice/sekret', ['sessionId' => $call->provider_ref, 'isActive' => '0', 'durationInSeconds' => '42', 'status' => 'Success']);
        $this->assertSame([42, 'success'], [(int) DB::table('call_logs')->value('duration_s'), DB::table('call_logs')->value('outcome')]);

        // The receiver rings the shared number back and reaches the rider.
        $xml = $this->post('/api/v1/voice/sekret', ['sessionId' => 'in-1', 'isActive' => '1', 'direction' => 'Inbound', 'callerNumber' => '+2348031234567'])->getContent();
        $this->assertStringContainsString('phoneNumbers="+2348030000002"', $xml);
        // A stranger gets told what the number is for.
        $xml = $this->post('/api/v1/voice/sekret', ['sessionId' => 'in-2', 'isActive' => '1', 'direction' => 'Inbound', 'callerNumber' => '+2348099999999'])->getContent();
        $this->assertStringContainsString('<Say>', $xml);

        // The customer can only call their rider.
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/v1/customer/shipments/{$s->public_id}/call", ['who' => 'dropoff'])->assertStatus(422);
        $this->postJson("/api/v1/customer/shipments/{$s->public_id}/call")->assertOk();
        $this->assertSame('2348030000002', DB::table('call_logs')->where('caller_id', $this->customer->id)->value('to_number'));
        $this->getJson("/api/v1/customer/orders/{$s->public_id}")->assertJsonPath('chat.calls', true);

        // Once the job ends, calling the shared number back no longer connects.
        DB::table('assignments')->where('shipment_id', $s->id)->update(['status' => 'completed']);
        $xml = $this->post('/api/v1/voice/sekret', ['sessionId' => 'in-3', 'isActive' => '1', 'direction' => 'Inbound', 'callerNumber' => '+2348031234567'])->getContent();
        $this->assertStringContainsString('<Say>', $xml);
    }
}
