<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Payments\PaymentService;
use App\Modules\Verification\CustodyLog;
use App\Modules\Verification\DeliveryCodes;
use App\Modules\Verification\VerificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Proof of delivery agreed between the job giver and the provider, enforced at every stop. */
class DeliveryVerificationTest extends TestCase
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
        Storage::fake();
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

    /** A paid, assigned, started job with the given agreed proofs. Returns [shipment row, stop ids by type]. */
    private function job(?array $verification, array $packages = [['description' => 'Box']]): array
    {
        $extra = ['packages' => $packages] + ($verification ? ['verification' => $verification] : []);
        $agreement = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000, 0, 0, $extra);
        app(PaymentService::class)->payWithWallet($agreement, $this->customer->id);
        $s = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreement)->first(['shipments.*']);
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'in_transit']);
        $stops = DB::table('shipment_stops')->where('shipment_id', $s->id)->pluck('id', 'type')->all();
        DB::table('shipment_stops')->where('id', $stops['pickup'])->update(['status' => 'completed']);

        return [$s, $stops];
    }

    private function complete(int $stop, array $fields = [])
    {
        Sanctum::actingAs($this->driver->fresh());

        return $this->post("/api/v1/driver/stops/{$stop}/complete", $fields + ['lat' => 7.82, 'lng' => 5.92], ['Accept' => 'application/json']);
    }

    public function test_the_agreement_keeps_both_sides_requirements(): void
    {
        $p = VerificationPolicy::agree(['dropoff' => ['signature', 'teleport'], 'authorised_receivers' => ['  Ada   Obi ']], ['pickup' => ['scan'], 'dropoff' => ['code', 'photo']]);

        $this->assertSame(['scan'], $p['pickup']);
        $this->assertSame(['code', 'photo', 'signature', 'receiver_name'], $p['dropoff']);
        $this->assertSame(['Ada Obi'], $p['authorised_receivers']);
    }

    public function test_shipments_freeze_the_agreed_proofs_and_old_agreements_get_the_provider_standard(): void
    {
        app(VerificationPolicy::class)->save($this->op, null, ['scan'], ['code', 'photo'], $this->owner->id);
        [$agreed] = $this->job(['pickup' => [], 'dropoff' => ['signature']]);
        [$standard] = $this->job(null);

        $this->assertSame(['signature'], app(VerificationPolicy::class)->forShipment($agreed->id)['dropoff']);
        $this->assertSame(['code', 'photo'], app(VerificationPolicy::class)->forShipment($standard->id)['dropoff']);

        // Changing the standard later does not touch jobs already agreed.
        app(VerificationPolicy::class)->save($this->op, null, [], ['receiver_name'], $this->owner->id);
        $this->assertSame(['code', 'photo'], app(VerificationPolicy::class)->forShipment($standard->id)['dropoff']);
        // Only jobs needing a code get one.
        $this->assertSame(0, DB::table('delivery_codes')->where('shipment_id', $agreed->id)->count());
        $this->assertSame(1, DB::table('delivery_codes')->where('shipment_id', $standard->id)->count());
    }

    public function test_delivery_codes_are_one_time_expiring_and_lock_after_five_wrong_tries(): void
    {
        [$s, $stops] = $this->job(['dropoff' => ['code']]);
        $code = app(DeliveryCodes::class)->current($s->id)['code'];
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 4; $i >= 1; $i--) {
            $this->complete($stops['dropoff'], ['otp' => $wrong])->assertStatus(422)->assertJsonPath('message', "Delivery code does not match. {$i} ".($i === 1 ? 'try' : 'tries').' left.');
        }
        $this->complete($stops['dropoff'], ['otp' => $wrong])->assertStatus(422);
        // Locked now: even the right code waits.
        $this->complete($stops['dropoff'], ['otp' => $code])->assertStatus(422)->assertJson(fn ($j) => $j->where('message', fn ($m) => str_starts_with($m, 'Too many wrong codes'))->etc());

        DB::table('delivery_codes')->where('stop_id', $stops['dropoff'])->update(['locked_until' => now()->subMinute()->toIso8601String()]);
        $this->complete($stops['dropoff'], ['otp' => $code])->assertOk();
        $this->assertNotNull(DB::table('delivery_codes')->where('stop_id', $stops['dropoff'])->value('used_at'));
        $this->assertSame('delivered', DB::table('shipments')->where('id', $s->id)->value('status'));
    }

    public function test_an_expired_code_is_replaced_and_the_customer_can_ask_for_a_new_one(): void
    {
        [$s, $stops] = $this->job(['dropoff' => ['code']]);
        $first = app(DeliveryCodes::class)->current($s->id)['code'];
        DB::table('delivery_codes')->where('shipment_id', $s->id)->update(['expires_at' => now()->subMinute()->toIso8601String()]);
        $this->complete($stops['dropoff'], ['otp' => $first])->assertStatus(422)->assertJsonPath('message', 'This delivery code has expired. Ask the receiver to open their app for a new one.');

        Sanctum::actingAs($this->customer->fresh());
        $shown = $this->getJson("/api/v1/customer/shipments/{$s->public_id}/delivery-code")->assertOk()->json();
        $this->assertNotNull($shown['expires_at']);
        $this->assertTrue(now()->lt($shown['expires_at']));

        $new = $this->postJson("/api/v1/customer/shipments/{$s->public_id}/delivery-code")->assertOk()->json('code');
        $this->assertSame(1, DB::table('delivery_codes')->where('shipment_id', $s->id)->whereNull('revoked_at')->count());
        $this->postJson("/api/v1/customer/shipments/{$s->public_id}/delivery-code")->assertStatus(429);
        $this->complete($stops['dropoff'], ['otp' => $new])->assertOk();
    }

    public function test_every_parcel_must_be_scanned_and_the_receiver_must_be_authorised(): void
    {
        [$s, $stops] = $this->job(['dropoff' => ['scan', 'receiver_name', 'photo'], 'authorised_receivers' => ['Ada Obi']], [['description' => 'Box'], ['description' => 'Bag']]);
        $photo = fn () => UploadedFile::fake()->image('p.jpg');

        $this->complete($stops['dropoff'], ['photo' => $photo()])->assertStatus(422)
            ->assertJsonPath('message', "Still needed: scan 2 more parcels (0 of 2 scanned); the receiver's name.");

        $codes = DB::table('packages')->where('shipment_id', $s->id)->orderBy('seq')->pluck('barcode');
        $r = $this->postJson('/api/v1/driver/sync', ['events' => [['id' => 's1', 'type' => 'package.scan', 'data' => ['code' => $codes[0], 'stage' => 'dropoff']]]])->json('results.0');
        $this->assertSame([1, 2], [$r['result']['scanned'], $r['result']['of']]);
        $this->complete($stops['dropoff'], ['photo' => $photo(), 'recipient_name' => 'Bola'])->assertStatus(422)
            ->assertJsonPath('message', 'Still needed: scan 1 more parcel (1 of 2 scanned); a receiver named in the agreement (Ada Obi).');

        $this->postJson('/api/v1/driver/sync', ['events' => [['id' => 's2', 'type' => 'package.scan', 'data' => ['code' => $codes[1], 'stage' => 'dropoff']]]]);
        $this->complete($stops['dropoff'], ['photo' => $photo(), 'recipient_name' => 'ada  obi'])->assertOk();

        $proof = DB::table('proofs')->where('stop_id', $stops['dropoff'])->first();
        $this->assertSame(['scan', 'photo', 'receiver_name'], json_decode($proof->checks, true)['satisfied']);
        $this->assertSame(64, strlen($proof->file_sha256));
    }

    public function test_a_missing_proof_needs_an_approved_exception_and_the_custody_trail_detects_edits(): void
    {
        [$s, $stops] = $this->job(['dropoff' => ['signature', 'receiver_id']]);
        Sanctum::actingAs($this->driver->fresh());

        $this->postJson("/api/v1/driver/stops/{$stops['dropoff']}/exceptions", ['method' => 'signature', 'reason' => 'short'])->assertStatus(422);
        $this->postJson("/api/v1/driver/stops/{$stops['dropoff']}/exceptions", ['method' => 'photo', 'reason' => 'No camera on this phone'])->assertStatus(422)
            ->assertJsonPath('message', 'That proof is not required at this stop.');
        $ex = $this->postJson("/api/v1/driver/stops/{$stops['dropoff']}/exceptions", ['method' => 'signature', 'reason' => 'Receiver has a broken arm, cannot sign'])
            ->assertOk()->json('exception');
        $this->assertSame('pending', $ex['status']);

        $this->complete($stops['dropoff'], ['id_type' => 'nin', 'id_last4' => '1234'])->assertStatus(422)->assertJsonPath('message', "Still needed: the receiver's signature.");

        // A dispatcher at the provider approves it from the job page.
        $this->actingAs($this->owner->fresh())->post("/provider/jobs/{$s->public_id}/exceptions/{$ex['id']}", ['decision' => 'approve', 'note' => 'OK, take ID'])->assertSessionHas('success');

        $this->complete($stops['dropoff'], ['id_type' => 'nin', 'id_last4' => '1234'])->assertOk();
        $checks = json_decode(DB::table('proofs')->where('stop_id', $stops['dropoff'])->value('checks'), true);
        $this->assertSame(['signature'], $checks['waived']);
        $this->assertSame(['receiver_id'], $checks['satisfied']);

        $custody = app(CustodyLog::class);
        $this->assertSame(['ok' => true, 'events' => 3, 'broken_at' => null], $custody->verify($s->id));
        DB::table('custody_events')->where('shipment_id', $s->id)->where('seq', 2)->update(['payload' => json_encode(['status' => 'approved', 'by' => 'someone else'])]);
        $this->assertSame(['ok' => false, 'events' => 2, 'broken_at' => 2], $custody->verify($s->id));
    }

    public function test_the_driver_sees_what_each_stop_needs(): void
    {
        [$s, $stops] = $this->job(['dropoff' => ['code', 'signature']]);
        Sanctum::actingAs($this->driver->fresh());
        $job = $this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertOk()->json();
        $drop = collect($job['stops'])->firstWhere('type', 'dropoff');

        $this->assertSame(['code', 'signature'], $drop['requires']);
        $this->assertTrue($drop['needs_code']);
        $this->assertFalse($job['packages'][0]['scanned_dropoff']);
        $this->assertArrayNotHasKey('code', $drop);
    }

    public function test_the_web_pages_show_and_save_the_checks(): void
    {
        DB::table('users')->where('id', $this->owner->id)->update(['current_operator_id' => $this->op]);
        $this->actingAs($this->owner->fresh())->get('/provider/verification')->assertOk()->assertSee('Delivery checks');
        $this->post('/provider/verification', ['dropoff' => ['code', 'signature'], 'pickup' => ['scan']])->assertRedirect('/provider/verification');
        $this->assertSame(['code', 'signature'], app(VerificationPolicy::class)->forProvider($this->op)['dropoff']);
        $this->post('/provider/verification', ['dropoff' => []])->assertSessionHas('error');

        [$s, $stops] = $this->job(null);
        $this->actingAs($this->owner->fresh())->get("/provider/jobs/{$s->public_id}")->assertOk()->assertSee('How the hand-over is confirmed')->assertSee('Custody trail');
        $this->actingAs($this->driver->fresh())->get("/driver/jobs/{$s->public_id}")->assertOk()->assertSee('Receiver signs here');
        $this->actingAs($this->customer->fresh())->get("/account/orders/{$s->public_id}")->assertOk()->assertSee('How the hand-over is confirmed');
    }
}
