<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Payments\PaymentService;
use App\Modules\Tracking\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Actions saved on the driver's phone while offline are applied once each, in order, with reasons for any rejection. */
class DriverSyncTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private User $driver;
    private object $shipment;
    private array $stops;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$op, $owner] = $this->makeProvider();
        $customer = $this->makeUser('Ada');
        $this->fundCustomer($customer->id, 1_000_000);
        $agreement = $this->lockedAgreement($customer->id, $op, $owner->id, 300_000, 0, 0, ['packages' => [['description' => 'Box'], ['description' => 'Bag']]]);
        app(PaymentService::class)->payWithWallet($agreement, $customer->id);
        $this->shipment = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.*']);
        $this->stops = DB::table('shipment_stops')->where('shipment_id', $this->shipment->id)->pluck('id', 'type')->all();

        $this->driver = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $op, 'user_id' => $this->driver->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $this->driver->id)->update(['current_operator_id' => $op]);
        $dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $op, 'user_id' => $this->driver->id, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $this->shipment->id, 'operator_id' => $op, 'driver_profile_id' => $dp, 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $this->shipment->id)->update(['status' => 'assigned']);
        Sanctum::actingAs($this->driver->fresh());
    }

    private function sync(array $events)
    {
        return $this->postJson('/api/v1/driver/sync', ['events' => $events])->assertOk();
    }

    public function test_a_whole_offline_trip_syncs_once(): void
    {
        $pkgs = DB::table('packages')->where('shipment_id', $this->shipment->id)->orderBy('seq')->get();
        $code = app(TrackingService::class)->deliveryCode($this->shipment->public_id);
        $t = now()->subMinutes(30);
        $events = [
            // Uploaded out of order: the server sorts them by when they happened on the phone.
            ['id' => 'e3', 'type' => 'stop.complete', 'at' => $t->copy()->addMinutes(5)->toIso8601String(), 'data' => ['stop_id' => $this->stops['pickup'], 'proof_type' => 'qr_scan', 'lat' => 7.8, 'lng' => 5.9]],
            ['id' => 'e1', 'type' => 'job.start', 'at' => $t->toIso8601String(), 'data' => ['shipment' => $this->shipment->public_id]],
            ['id' => 'e2', 'type' => 'package.scan', 'at' => $t->copy()->addMinutes(4)->toIso8601String(), 'data' => ['code' => $pkgs[0]->qr_payload, 'stage' => 'pickup', 'lat' => 7.8, 'lng' => 5.9]],
            ['id' => 'e4', 'type' => 'stop.complete', 'at' => $t->copy()->addMinutes(20)->toIso8601String(), 'data' => ['stop_id' => $this->stops['dropoff'], 'proof_type' => 'otp', 'otp' => $code, 'lat' => 7.82, 'lng' => 5.92]],
        ];

        $r = $this->sync($events)->json('results');
        $this->assertSame(['e3', 'e1', 'e2', 'e4'], array_column($r, 'id'));
        $this->assertSame(['accepted'], array_values(array_unique(array_column($r, 'status'))), json_encode($r));
        $this->assertSame('delivered', DB::table('shipments')->where('id', $this->shipment->id)->value('status'));
        $this->assertSame(1, $r[2]['result']['scanned']);
        $this->assertSame(2, $r[2]['result']['of']);
        $this->assertSame('picked_up', DB::table('packages')->where('id', $pkgs[0]->id)->value('status'));

        // The phone retries the same batch after a timeout: nothing happens twice.
        $again = $this->sync($events)->json('results');
        $this->assertTrue(collect($again)->every(fn ($x) => $x['duplicate'] && $x['status'] === 'accepted'));
        $this->assertSame(2, DB::table('proofs')->where('shipment_id', $this->shipment->id)->count());
        $this->assertSame(1, DB::table('package_scans')->count());
        $this->assertSame('***', json_decode(DB::table('sync_events')->where('client_event_id', 'e4')->value('payload'), true)['otp']);
    }

    public function test_conflicts_wrong_codes_and_unknown_actions_are_rejected_with_reasons(): void
    {
        $r = $this->sync([
            ['id' => 'a', 'type' => 'job.start', 'data' => ['shipment' => $this->shipment->public_id]],
            ['id' => 'b', 'type' => 'job.start', 'data' => ['shipment' => $this->shipment->public_id]],
            ['id' => 'c', 'type' => 'teleport', 'data' => []],
            ['id' => 'd', 'type' => 'package.scan', 'data' => ['code' => 'PK00000000X']],
        ])->json('results');

        $this->assertSame(['accepted', 'rejected', 'rejected', 'rejected'], array_column($r, 'status'));
        $this->assertSame('This job is not ready to start.', $r[1]['message']);
        $this->assertStringContainsString('Update the app', $r[2]['message']);

        // A wrong delivery code entered offline is rejected, and the stop stays open.
        $this->sync([['id' => 'p', 'type' => 'stop.complete', 'data' => ['stop_id' => $this->stops['pickup'], 'proof_type' => 'qr_scan', 'lat' => 7.8, 'lng' => 5.9]]]);
        $bad = $this->sync([['id' => 'x', 'type' => 'stop.complete', 'data' => ['stop_id' => $this->stops['dropoff'], 'proof_type' => 'otp', 'otp' => '0000', 'lat' => 7.82, 'lng' => 5.92]]])->json('results.0');
        if (app(TrackingService::class)->deliveryCode($this->shipment->public_id) !== '0000') {
            $this->assertSame('rejected', $bad['status']);
            $this->assertSame('Delivery code does not match.', $bad['message']);
            $this->assertNotSame('completed', DB::table('shipment_stops')->where('id', $this->stops['dropoff'])->value('status'));
        }
    }

    public function test_a_wrong_phone_clock_is_flagged_not_trusted(): void
    {
        $r = $this->sync([['id' => 'z', 'type' => 'job.start', 'at' => now()->addDays(3)->toIso8601String(), 'data' => ['shipment' => $this->shipment->public_id]]])->json('results.0');

        $this->assertSame('accepted', $r['status']);
        $this->assertTrue($r['clock_suspect']);
        $row = DB::table('sync_events')->where('client_event_id', 'z')->first();
        $this->assertTrue((bool) $row->clock_suspect);
        $this->assertLessThan(-200_000, $row->clock_skew_s);

        $old = $this->sync([['id' => 'old', 'type' => 'job.issue', 'at' => now()->subHours(73)->toIso8601String(), 'data' => ['shipment' => $this->shipment->public_id, 'reason' => 'traffic']]])->json('results.0');
        $this->assertSame('rejected', $old['status']);
        $this->assertStringContainsString('too old', $old['message']);
    }

    public function test_photo_proof_upload_is_idempotent(): void
    {
        \Illuminate\Support\Facades\Storage::fake();
        $this->sync([['id' => 's', 'type' => 'job.start', 'data' => ['shipment' => $this->shipment->public_id]]]);
        $body = ['proof_type' => 'qr_scan', 'lat' => 7.8, 'lng' => 5.9, 'client_event_id' => 'photo-1'];

        $this->postJson("/api/v1/driver/stops/{$this->stops['pickup']}/complete", $body)->assertOk()->assertJsonPath('duplicate', false);
        $this->postJson("/api/v1/driver/stops/{$this->stops['pickup']}/complete", $body)->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame(1, DB::table('proofs')->where('stop_id', $this->stops['pickup'])->count());
    }
}
