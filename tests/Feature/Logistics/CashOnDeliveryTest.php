<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Backoffice\ManualBookingService;
use App\Modules\Cod\CodService;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\ApiClientService;
use App\Modules\Verification\DeliveryCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Goods money the rider collects at the door, hands in at the office, and the provider pays to the sender. */
class CashOnDeliveryTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $driver;
    private int $dp;
    private int $city;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');
        $this->driver = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $this->driver->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $this->driver->id)->update(['current_operator_id' => $this->op]);
        $this->dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $this->driver->id, 'status' => 'active', 'availability' => 'busy', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function book(int $cod, array $dropoff = ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92]): object
    {
        $r = app(ManualBookingService::class)->book($this->op, [
            'customer' => ['name' => 'Shoe Shop', 'phone' => '08031112222'], 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => $dropoff + ['contact_name' => 'Bisi', 'contact_phone' => '08030000002'],
            'packages' => [['description' => 'Shoes', 'weight_g' => 800]], 'price' => 300_000, 'payment' => 'cash', 'cod_amount' => $cod,
        ], $this->owner->id);

        return DB::table('shipments')->where('id', $r['shipment_id'])->first();
    }

    private function onTheWay(object $s): int
    {
        DB::table('assignments')->insert(['public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'operator_id' => $this->op, 'driver_profile_id' => $this->dp, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'in_transit']);
        DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'pickup')->update(['status' => 'completed']);

        return (int) DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->value('id');
    }

    private function deliver(object $s, ?int $cash)
    {
        $stop = $this->onTheWay($s);
        $code = app(DeliveryCodes::class)->current($s->id)['code'];
        Sanctum::actingAs($this->driver->fresh());

        return $this->postJson("/api/v1/driver/stops/{$stop}/complete", array_filter(['lat' => 7.82, 'lng' => 5.92, 'otp' => $code, 'cod_collected' => $cash], fn ($v) => $v !== null));
    }

    /** Debits equal credits across every posted entry. */
    private function assertLedgerBalanced(): void
    {
        $sum = fn ($dir) => (int) DB::table('ledger_entries')->where('direction', $dir)->sum('amount');
        $this->assertSame($sum('debit'), $sum('credit'));
    }

    private function balance(string $ownerType, int $ownerId, string $purpose): int
    {
        $id = DB::table('ledger_accounts')->where(['owner_type' => $ownerType, 'owner_id' => $ownerId, 'purpose' => $purpose])->value('id');
        if (! $id) {
            return 0;
        }
        $e = DB::table('ledger_entries')->where('account_id', $id);

        return (int) (clone $e)->where('direction', 'debit')->sum('amount') - (int) (clone $e)->where('direction', 'credit')->sum('amount');
    }

    public function test_the_rider_must_collect_the_exact_amount_and_the_job_shows_it(): void
    {
        $s = $this->book(1_500_000);
        $this->assertSame(1_500_000, (int) $s->cod_amount);
        $this->onTheWay($s);
        Sanctum::actingAs($this->driver->fresh());
        $drop = collect($this->getJson("/api/v1/driver/jobs/{$s->public_id}")->assertOk()->json('stops'))->firstWhere('type', 'dropoff');
        $this->assertSame(1_500_000, $drop['cod_amount']);
        $this->assertContains('cod', $drop['requires']);
        DB::table('assignments')->where('shipment_id', $s->id)->delete();

        $msg = $this->deliver($s, 1_000_000)->assertStatus(422)->json('message');
        $this->assertStringContainsString('15,000', $msg);
        $this->assertSame(0, DB::table('cod_collections')->count());
        $this->assertSame('in_transit', DB::table('shipments')->where('id', $s->id)->value('status'));
    }

    public function test_collect_hand_in_and_pay_out_moves_the_money_and_tells_the_sender(): void
    {
        $s = $this->book(1_500_000);
        $this->deliver($s, 1_500_000)->assertOk();
        $c = DB::table('cod_collections')->where('shipment_id', $s->id)->first();
        $customer = (int) DB::table('orders')->where('id', $s->order_id)->value('customer_id');
        $this->assertSame(['with_rider', 'customer', $customer, 1_500_000], [$c->status, $c->sender_type, (int) $c->sender_id, (int) $c->amount]);
        $this->assertSame(1_500_000, $this->balance('driver_profile', $this->dp, 'cod_with_rider'));
        $this->assertSame(-1_500_000, $this->balance('customer', $customer, 'cod_payable'));
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $customer)->where('type', 'cod.collected')->count());

        Sanctum::actingAs($this->driver->fresh());
        $this->assertSame(1_500_000, $this->getJson('/api/v1/driver/me')->assertOk()->json('cash_held'));

        // Same stop again (an offline retry) does not collect twice.
        app(CodService::class)->collect((int) $s->id, $this->dp, 1_500_000);
        $this->assertSame(1, DB::table('cod_collections')->count());

        $page = $this->actingAs($this->owner)->get('/provider/cash')->assertOk();
        $page->assertSee('Rider')->assertSee('₦15,000');

        // Cash still with the rider cannot be paid out.
        $this->actingAs($this->owner)->post('/provider/cash/pay-out', ['ids' => [$c->id], 'reference' => 'TRF1'])->assertSessionHas('error');
        $this->actingAs($this->owner)->post('/provider/cash/hand-in', ['ids' => [$c->id]])->assertSessionHas('success', '₦15,000 counted in at the office.');
        $this->assertSame(0, $this->balance('driver_profile', $this->dp, 'cod_with_rider'));
        $this->assertSame(1_500_000, $this->balance('operator', $this->op, 'cod_in_office'));

        $this->actingAs($this->owner)->post('/provider/cash/pay-out', ['ids' => [$c->id], 'reference' => 'TRF-889'])->assertSessionHas('success');
        $c = DB::table('cod_collections')->where('id', $c->id)->first();
        $this->assertSame(['paid', 'TRF-889'], [$c->status, $c->paid_reference]);
        $this->assertSame(0, $this->balance('operator', $this->op, 'cod_in_office'));
        $this->assertSame(0, $this->balance('customer', $customer, 'cod_payable'));
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $customer)->where('type', 'cod.paid')->count());
        $this->assertLedgerBalanced();

        $this->assertSame('paid', app(CodService::class)->forShipment((int) $s->id)['status']);
        $this->actingAs($this->owner)->get('/provider/cash')->assertOk()->assertSee('TRF-889');
    }

    public function test_payouts_go_one_sender_at_a_time_and_overdue_cash_is_flagged_once(): void
    {
        $a = $this->book(500_000);
        $this->deliver($a, 500_000)->assertOk();
        DB::table('assignments')->update(['status' => 'completed']);
        $other = $this->makeUser('Other Shop');
        $b = $this->book(200_000);
        DB::table('orders')->where('id', $b->order_id)->update(['customer_id' => $other->id]);
        $this->deliver($b, 200_000)->assertOk();
        $ids = DB::table('cod_collections')->pluck('id')->all();
        app(CodService::class)->handIn($this->op, $ids, $this->owner->id);
        try {
            app(CodService::class)->payOut($this->op, $ids, 'TRF1', $this->owner->id);
            $this->fail('Two senders were paid in one go.');
        } catch (RuntimeException $e) {
            $this->assertSame('Pay one sender at a time.', $e->getMessage());
        }

        $c = $this->book(100_000);
        $this->deliver($c, 100_000)->assertOk();
        DB::table('cod_collections')->where('shipment_id', $c->id)->update(['remit_due_at' => now()->subHour()]);
        $this->artisan('cod:flag-overdue')->assertSuccessful();
        $this->assertSame(1, app(CodService::class)->flagOverdue() + 1);
        $this->assertSame(1, DB::table('notifications')->where('type', 'cod.overdue')->count());
        $this->assertLedgerBalanced();
    }

    public function test_a_spreadsheet_row_carries_cash_on_delivery(): void
    {
        $defaults = ['pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'customer' => ['phone' => '08031112222']];
        $rows = \App\Modules\Backoffice\BulkBookingService::parse("reference,dropoff_address,dropoff_lat,dropoff_lng,cod_naira,pickup_point\nA1,9 Hill St,7.82,5.92,\"12,500\",\nA2,,,,500,HILL1\n");
        $a = \App\Modules\Backoffice\BulkBookingService::row($rows[0]['cells'], $defaults);
        $this->assertSame([[], 1_250_000], [$a['errors'], $a['input']['cod_amount']]);
        $this->assertContains('Cash on delivery cannot go to a pickup point.', \App\Modules\Backoffice\BulkBookingService::row($rows[1]['cells'], $defaults)['errors']);
    }

    public function test_cash_on_delivery_cannot_go_to_a_pickup_point(): void
    {
        $this->actingAs($this->owner)->post('/provider/pickup-points', ['code' => 'HILL1', 'name' => 'Hill Office', 'line1' => '2 Hill St', 'lat' => 7.82, 'lng' => 5.92,
            'opening_hours' => 'Mon to Sat', 'hold_days' => 3])->assertRedirect('/provider/pickup-points');
        $this->expectExceptionMessage('Cash on delivery cannot go to a pickup point.');
        $this->book(100_000, ['pickup_point' => 'HILL1']);
    }

    public function test_a_partner_delivery_carries_cash_and_reports_it(): void
    {
        DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $this->city, 'service_type_id' => $this->serviceTypeId(),
            'name' => 'Standard', 'min_fee' => 150000, 'rounding_kobo' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        [$mOp, $mOwner] = $this->makeProvider('merchant', 'Shoe Shop');
        $this->fundCustomer($mOwner->id, 5_000_000);
        $mid = DB::table('merchants')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $mOp, 'merchant_code' => 'SHOE01', 'display_name' => 'Shoe Shop',
            'status' => 'verified', 'settlement_mode' => 'prepaid_wallet', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $h = ['Authorization' => 'Bearer '.app(ApiClientService::class)->issue($mOp, $mid, 'Shop', 'live')['key']];

        $this->postJson('/api/v1/partner/deliveries', ['external_order_id' => 'S-1', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city, 'cod_amount' => 800_000,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_phone' => '08030000002']], $h)
            ->assertSuccessful();
        $this->assertSame('to_collect', $this->getJson('/api/v1/partner/deliveries/S-1', $h)->assertOk()->json('cod.status'));
        $s = DB::table('shipments')->first();
        $this->assertSame(800_000, (int) $s->cod_amount);

        $this->deliver($s, 800_000)->assertOk();
        $c = DB::table('cod_collections')->first();
        $this->assertSame(['merchant', $mid], [$c->sender_type, (int) $c->sender_id]);
        $this->assertSame('collected', $this->getJson('/api/v1/partner/deliveries/S-1', $h)->json('cod.status'));
        $this->assertLedgerBalanced();
    }
}
