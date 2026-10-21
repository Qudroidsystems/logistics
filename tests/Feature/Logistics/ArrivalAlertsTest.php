<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Eta\ArrivalAlerts;
use App\Modules\Eta\EtaService;
use App\Modules\Payments\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Arrival estimates as a range, and "the rider is close" alerts sent once per stop. Routing is the straight-line estimate in tests. */
class ArrivalAlertsTest extends TestCase
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
        $this->customer = $this->makeUser('Ada');
        $this->fundCustomer($this->customer->id, 5_000_000);
        $this->driver = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $this->driver->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $this->driver->id)->update(['current_operator_id' => $this->op]);
        $this->dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $this->driver->id, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
    }

    /** A paid job with the parcel picked up and the rider heading to the drop-off at 7.82, 5.92. */
    private function job(): object
    {
        $agreement = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000, 0, 0, ['packages' => [['description' => 'Box']]]);
        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.*']);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'in_transit']);
        DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'pickup')->update(['status' => 'completed']);
        DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->update(['contact_phone' => '08031234567']);

        return $s;
    }

    private function ping(float $lat, float $lng = 5.92)
    {
        Sanctum::actingAs($this->driver->fresh());

        return $this->postJson('/api/v1/driver/location', ['pings' => [['lat' => $lat, 'lng' => $lng, 'at' => now()->toIso8601String(), 'accuracy' => 10]]])->assertOk();
    }

    private function alerts(int $shipmentId): array
    {
        return DB::table('stop_alerts')->where('shipment_id', $shipmentId)->orderBy('id')->pluck('status', 'kind')->all();
    }

    /** Lets the next ping recalculate even when the rider has barely moved. */
    private function ageEstimate(int $shipmentId): void
    {
        DB::table('stop_etas')->where('shipment_id', $shipmentId)->update(['computed_at' => now()->subMinutes(2)->toIso8601String()]);
    }

    public function test_the_estimate_is_a_range_that_widens_when_the_position_is_old(): void
    {
        $this->assertSame(['minutes' => 10, 'low' => 8, 'high' => 12, 'label' => '8 to 12 min', 'stale' => false], EtaService::range(600, 30, 'osrm'));
        $this->assertSame('8 to 17 min', EtaService::range(600, 300, 'osrm')['label']);
        $this->assertTrue(EtaService::range(600, 300, 'osrm')['stale']);
        // A straight-line guess is wider, and long ranges round to fives.
        $this->assertSame('25 to 55 min', EtaService::range(2400, null, 'straight')['label']);
        $this->assertSame('about 1 min', EtaService::range(30, null, 'osrm')['label']);
    }

    public function test_only_the_closest_due_alert_is_sent_and_nothing_twice(): void
    {
        $this->assertSame(['send' => 'eta_5', 'skip' => ['eta_15']], ArrivalAlerts::due([15, 5], 500, 3, 2000, []));
        $this->assertSame(['send' => 'near', 'skip' => ['eta_5', 'eta_15']], ArrivalAlerts::due([15, 5], 500, 3, 400, []));
        $this->assertSame(['send' => null, 'skip' => []], ArrivalAlerts::due([15, 5], 500, 3, 2000, ['eta_5', 'eta_15']));
        $this->assertSame(['send' => null, 'skip' => []], ArrivalAlerts::due([15, 5], null, 20, 9000, []));
        $now = CarbonImmutable::parse('2026-10-15 12:00');
        $this->assertTrue(ArrivalAlerts::delayed($now, $now->addMinutes(10), 10));
        $this->assertFalse(ArrivalAlerts::delayed($now, $now->addMinutes(9), 10));
    }

    public function test_the_receiver_and_job_giver_hear_once_at_each_point_as_the_rider_closes_in(): void
    {
        $s = $this->job();

        $this->ping(7.80);       // about 2.2 km out: roughly 7 minutes
        $this->assertSame(['eta_15' => 'sent'], $this->alerts($s->id));
        $this->ping(7.80);       // the same place again: nothing new
        $this->assertCount(1, $this->alerts($s->id));

        $this->ping(7.815);      // about 550 m: under 5 minutes
        $this->assertSame(['eta_15' => 'sent', 'eta_5' => 'sent'], $this->alerts($s->id));

        $this->ping(7.818);      // about 220 m by air, under 500 m by road
        $this->assertSame('sent', $this->alerts($s->id)['near']);

        $this->ping(7.8199);     // inside the arrival radius
        $this->assertSame('sent', $this->alerts($s->id)['arrived']);

        $texts = DB::table('sms_log')->where('phone', 'like', '%8031234567')->pluck('template_key')->all();
        $this->assertSame(['delivery.approaching_recipient', 'delivery.approaching_recipient', 'delivery.approaching_recipient', 'delivery.recipient_arrived'], $texts);
        $this->assertSame(3, DB::table('notifications')->where('notifiable_id', $this->customer->id)->where('type', 'delivery.approaching')->count());
        $this->assertSame(['push', 'sms'], json_decode(DB::table('stop_alerts')->where('kind', 'eta_5')->value('channels'), true));
    }

    public function test_tracking_shows_the_range_and_a_rider_far_away_with_old_gps_is_marked_stale(): void
    {
        $s = $this->job();
        $this->ping(7.80);
        $token = DB::table('tracking_links')->where('shipment_id', $s->id)->where('audience', 'recipient')->value('token');

        $eta = $this->getJson("/api/v1/track/{$token}")->assertOk()->json('driver.eta');
        $this->assertSame('dropoff', $eta['to']);
        $this->assertFalse($eta['stale']);
        $this->assertMatchesRegularExpression('/^\d+ to \d+ min$/', $eta['label']);

        DB::table('driver_profiles')->where('id', $this->dp)->update(['last_seen_at' => now()->subMinutes(6)->toIso8601String()]);
        $old = $this->getJson("/api/v1/track/{$token}")->json('driver');
        $this->assertTrue($old['eta']['stale']);
        $this->assertGreaterThanOrEqual(359, $old['seconds_since_update']); // a positive age, so the page can say the location is old
    }

    public function test_a_late_rider_sends_one_delay_notice_and_texts_can_be_turned_off(): void
    {
        $s = $this->job();
        $this->actingAs($this->owner)->post('/provider/alerts', ['eta_minutes' => [5], 'delay' => '1', 'delay_minutes' => 10])->assertRedirect();
        $this->assertSame(['eta_minutes' => [5], 'near_m' => null, 'arrived' => false, 'delay_minutes' => 10, 'sms' => false, 'saved' => true], app(ArrivalAlerts::class)->settings($this->op));

        $this->ping(7.70);       // about 13 km: no 5-minute alert yet
        $this->assertSame([], $this->alerts($s->id));

        // We had promised an earlier arrival than the rider can now make.
        DB::table('stop_etas')->where('shipment_id', $s->id)->update(['due_at' => now()->subMinutes(20)->toIso8601String()]);
        $this->ageEstimate($s->id);
        $this->ping(7.70);
        $this->assertSame(['delay_1' => 'sent'], $this->alerts($s->id));
        $this->ageEstimate($s->id);
        $this->ping(7.70);       // the new expected time was taken as the baseline: no repeat
        $this->assertCount(1, $this->alerts($s->id));

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->customer->id)->where('type', 'delivery.delayed')->count());
        $this->assertSame(0, DB::table('sms_log')->count());
    }

    public function test_the_receiver_tells_the_rider_they_need_more_time(): void
    {
        $s = $this->job();
        $token = DB::table('tracking_links')->where('shipment_id', $s->id)->where('audience', 'recipient')->value('token');

        $this->postJson("/api/v1/track/{$token}/reply", ['reply' => 'teleport'])->assertStatus(422);
        $this->postJson("/api/v1/track/{$token}/reply", ['reply' => 'need_time', 'note' => '  At the  gate  '])->assertOk()->assertJsonPath('label', 'I need 10 more minutes');

        Sanctum::actingAs($this->driver->fresh());
        $stops = $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertOk()->json('stops');
        $drop = collect($stops)->firstWhere('type', 'dropoff');
        $this->assertSame(['reply' => 'need_time', 'label' => 'I need 10 more minutes', 'note' => 'At the gate'], array_intersect_key($drop['receiver_reply'], array_flip(['reply', 'label', 'note'])));
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->driver->id)->where('type', 'driver.receiver_replied')->count());
        $this->assertSame('I need 10 more minutes', $this->getJson("/api/v1/track/{$token}")->json('reply.label'));

        // From the web page too, and not once the parcel is delivered.
        $this->post("/track/{$token}/reply", ['reply' => 'ready'])->assertRedirect("/track/{$token}");
        $this->assertSame('ready', DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->value('readiness'));
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'delivered']);
        $this->postJson("/api/v1/track/{$token}/reply", ['reply' => 'ready'])->assertStatus(422);
    }

    public function test_the_pages_show_the_estimate_alerts_and_reply(): void
    {
        $s = $this->job();
        $this->ping(7.815);
        $token = DB::table('tracking_links')->where('shipment_id', $s->id)->where('audience', 'recipient')->value('token');
        $this->postJson("/api/v1/track/{$token}/reply", ['reply' => 'call_me'])->assertOk();

        $this->actingAs($this->owner)->get('/provider/alerts')->assertOk()->assertSee('Minutes before the rider arrives');
        $this->actingAs($this->owner)->get("/provider/jobs/{$s->public_id}")->assertOk()
            ->assertSee('About 5 min away')->assertSee('skipped, a closer alert went out')->assertSee('Call me when you arrive');
        $this->get("/track/{$token}")->assertOk()->assertSee('Tell the rider')->assertSee('I need 10 more minutes');
    }
}
