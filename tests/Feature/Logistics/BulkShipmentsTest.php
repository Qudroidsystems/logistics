<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Backoffice\BulkBookingService;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Many deliveries at once: a provider's spreadsheet upload, and a merchant's batch over the partner API. */
class BulkShipmentsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private int $city;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');
    }

    private function priceList(): void
    {
        DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $this->city, 'service_type_id' => $this->serviceTypeId(),
            'name' => 'Standard', 'min_fee' => 150000, 'rounding_kobo' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function csv(array $rows): UploadedFile
    {
        $f = fopen('php://temp', 'w+');
        fputcsv($f, ['Reference', 'Recipient name', 'recipient_phone', 'dropoff_address', 'dropoff_lat', 'dropoff_lng', 'items', 'weight_kg', 'price_naira']);
        foreach ($rows as $r) {
            fputcsv($f, $r);
        }
        rewind($f);

        return UploadedFile::fake()->createWithContent('orders.csv', stream_get_contents($f));
    }

    private function form(array $over = []): array
    {
        return array_replace_recursive([
            'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city, 'payment' => 'cash',
            'customer' => ['name' => 'Shoe Shop', 'phone' => '08031112222', 'email' => 'shop@example.test'],
            'pickup' => ['line1' => '1 Market Rd', 'lat' => '7.80', 'lng' => '5.90', 'contact_phone' => '08030000001'],
        ], $over);
    }

    public function test_rows_are_read_and_each_problem_is_named(): void
    {
        $rows = BulkBookingService::parse("\xEF\xBB\xBFReference,Dropoff Address,dropoff-lat,dropoff_lng\nA1,9 Hill St,7.82,5.92\n,,,\nA2,,abc,5.9\n");
        $this->assertSame([['line' => 2, 'cells' => ['reference' => 'A1', 'dropoff_address' => '9 Hill St', 'dropoff_lat' => '7.82', 'dropoff_lng' => '5.92']]], array_slice($rows, 0, 1));
        $this->assertSame(4, $rows[1]['line']);   // the blank line 3 is skipped, but line numbers stay the spreadsheet's

        $defaults = ['pickup' => ['line1' => '1 Market Rd', 'lat' => 7.8, 'lng' => 5.9], 'customer' => ['phone' => '0803']];
        $this->assertSame([], BulkBookingService::row($rows[0]['cells'], $defaults)['errors']);
        $this->assertSame(['Drop-off latitude is not a valid number.', 'The drop-off address is missing.', 'The drop-off position (latitude and longitude) is missing.'],
            BulkBookingService::row($rows[1]['cells'], $defaults)['errors']);
        $this->assertContains('There is no pickup: give one on the form or in the row.', BulkBookingService::row($rows[0]['cells'], ['customer' => ['phone' => '0803']])['errors']);

        // Semicolon sheets, items split on ;, weight spread over the parcels, price in kobo.
        $r = BulkBookingService::row(BulkBookingService::parse("dropoff_address;dropoff_lat;dropoff_lng;items;weight_kg;price_naira\n9 Hill;7.8;5.9;\"Shoes, size 9\";3;\"2,500\"")[0]['cells'], $defaults);
        $this->assertSame([[ 'description' => 'Shoes, size 9', 'weight_g' => 3000]], $r['input']['packages']);
        $this->assertSame(250_000, $r['input']['price']);
        $this->assertStringContainsString('dropoff_address', BulkBookingService::template());
    }

    public function test_a_spreadsheet_is_checked_then_booked_once(): void
    {
        $this->priceList();
        $file = $this->csv([
            ['ORD-1', 'Bisi', '08030000002', '9 Hill St', '7.82', '5.92', 'Shoes; Belt', '2', '2500'],
            ['ORD-2', 'Tunde', '08030000003', '4 Lake Rd', '7.81', '5.93', '', '', ''],      // no price: the price list is used
            ['ORD-1', 'Again', '', '9 Hill St', '7.82', '5.92', '', '', ''],                 // same reference twice
            ['ORD-3', 'Kemi', '', '', '', '', '', '', ''],                                   // no address or position
        ]);
        $res = $this->actingAs($this->owner)->post('/provider/bookings/bulk', $this->form() + ['file' => $file]);
        $batch = DB::table('bulk_batches')->first();
        $res->assertRedirect("/provider/bookings/bulk/{$batch->public_id}");
        $this->assertSame([4, 2], [(int) $batch->row_count, (int) $batch->ok_count]);
        $this->assertSame(0, DB::table('orders')->count());   // nothing is booked by the check
        $this->actingAs($this->owner)->get("/provider/bookings/bulk/{$batch->public_id}")->assertOk()
            ->assertSee('2 of 4 rows are ready to book')->assertSee('Reference ORD-1 is already on line 2.')->assertSee('The drop-off address is missing.');

        $this->actingAs($this->owner)->post("/provider/bookings/bulk/{$batch->public_id}/book")->assertSessionHas('success', '2 deliveries booked.');
        $this->assertSame(2, DB::table('orders')->where('channel', 'manual')->count());
        $this->assertSame([250_000, 150_000], DB::table('orders')->orderBy('id')->pluck('total')->map(fn ($v) => (int) $v)->all());
        $shop = DB::table('users')->where('phone_number', 'like', '%8031112222')->value('id');
        $this->assertSame(2, DB::table('orders')->where('customer_id', $shop)->count());
        $this->assertSame('Bisi', DB::table('shipment_stops')->where('type', 'dropoff')->orderBy('id')->value('contact_name'));
        $this->assertSame(2, DB::table('packages')->join('shipments', 'shipments.id', '=', 'packages.shipment_id')->join('orders', 'orders.id', '=', 'shipments.order_id')
            ->where('orders.total', 250_000)->count());

        // Pressing again books nothing more, and the same sheet sent again is caught by its references.
        $this->actingAs($this->owner)->post("/provider/bookings/bulk/{$batch->public_id}/book")->assertSessionHas('error', 'That upload has already been booked.');
        $this->actingAs($this->owner)->post('/provider/bookings/bulk', $this->form() + ['file' => $this->csv([['ORD-2', 'Tunde', '', '4 Lake Rd', '7.81', '5.93', '', '', '']])]);
        $again = DB::table('bulk_batches')->orderByDesc('id')->first();
        $this->assertSame(0, (int) $again->ok_count);
        $this->actingAs($this->owner)->get("/provider/bookings/bulk/{$again->public_id}")->assertSee('Reference ORD-2 was booked before.');
        $this->assertSame(2, DB::table('orders')->count());
    }

    public function test_a_row_that_cannot_be_booked_does_not_stop_the_rest(): void
    {
        // No price list: rows without a price fail, rows with one go through.
        $file = $this->csv([['A', 'Bisi', '', '9 Hill St', '7.82', '5.92', '', '', '3000'], ['B', 'Tunde', '', '4 Lake Rd', '7.81', '5.93', '', '', '']]);
        $this->actingAs($this->owner)->post('/provider/bookings/bulk', $this->form() + ['file' => $file]);
        $batch = DB::table('bulk_batches')->first();
        $this->actingAs($this->owner)->post("/provider/bookings/bulk/{$batch->public_id}/book")
            ->assertSessionHas('error', '1 delivery booked. 1 could not be booked; the reason is shown on each.');
        $this->assertSame(['booked', 'failed'], DB::table('bulk_batch_rows')->orderBy('line')->pluck('status')->all());
        $this->actingAs($this->owner)->get("/provider/bookings/bulk/{$batch->public_id}")->assertSee('There is no price list for this service and city');
    }

    public function test_only_dispatch_roles_upload_and_a_checked_sheet_can_be_discarded(): void
    {
        $finance = User::factory()->create(['must_change_password' => false]);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $finance->id, 'role' => 'finance', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $finance->id)->update(['current_operator_id' => $this->op]);
        $this->actingAs($finance->fresh())->get('/provider/bookings/bulk')->assertForbidden();

        $this->actingAs($this->owner)->get('/provider/bookings/bulk')->assertOk()->assertSee('Download the template');
        $this->actingAs($this->owner)->get('/provider/bookings/bulk/template.csv')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->actingAs($this->owner)->post('/provider/bookings/bulk', $this->form() + ['file' => UploadedFile::fake()->createWithContent('e.csv', "reference\n")])
            ->assertSessionHas('error');
        $this->actingAs($this->owner)->post('/provider/bookings/bulk', $this->form() + ['file' => $this->csv([['A', 'Bisi', '', '9 Hill St', '7.82', '5.92', '', '', '3000']])]);
        $batch = DB::table('bulk_batches')->first();
        $this->actingAs($this->owner)->post("/provider/bookings/bulk/{$batch->public_id}/discard")->assertRedirect('/provider/bookings/bulk');
        $this->actingAs($this->owner)->post("/provider/bookings/bulk/{$batch->public_id}/book")->assertSessionHas('error', 'That upload was discarded.');
        $this->assertSame(0, DB::table('orders')->count());
    }

    // ---------------------------------------------------------------- partner API

    private function merchantKey(): string
    {
        $this->priceList();
        [$mOp, $mOwner] = $this->makeProvider('merchant', 'Shoe Shop');
        $this->fundCustomer($mOwner->id, 5_000_000);
        $mid = DB::table('merchants')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $mOp, 'merchant_code' => 'SHOE01', 'display_name' => 'Shoe Shop',
            'status' => 'verified', 'settlement_mode' => 'prepaid_wallet', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return app(ApiClientService::class)->issue($mOp, $mid, 'Shop', 'live')['key'];
    }

    private function delivery(string $ref, array $over = []): array
    {
        return array_replace_recursive(['external_order_id' => $ref, 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_phone' => '08030000002']], $over);
    }

    public function test_a_merchant_sends_a_batch_and_reads_back_each_delivery(): void
    {
        $key = $this->merchantKey();
        $h = ['Authorization' => "Bearer {$key}"];
        $batch = ['deliveries' => [$this->delivery('S-1'), $this->delivery('S-2'), $this->delivery('S-3', ['dropoff' => ['lat' => 200]]), $this->delivery('S-1')]];

        $r = $this->postJson('/api/v1/partner/deliveries/batch', $batch, $h)->assertOk()->assertJson(['created' => 2, 'existing' => 0, 'failed' => 2]);
        $this->assertSame(['created', 'created', 'invalid', 'invalid'], array_column($r->json('results'), 'result'));
        $this->assertStringContainsString('appears twice', $r->json('results.3.errors.0'));
        $this->assertSame(2, DB::table('orders')->count());

        // Sending the same batch again after a timeout does not double anything.
        $again = $this->postJson('/api/v1/partner/deliveries/batch', ['deliveries' => [$this->delivery('S-1'), $this->delivery('S-2')]], $h)->assertOk();
        $this->assertSame(['existing', 'existing'], array_column($again->json('results'), 'result'));
        $this->assertSame(2, DB::table('orders')->count());

        $one = $this->getJson('/api/v1/partner/deliveries/S-2', $h)->assertOk();
        $this->assertSame(['external_order_id' => 'S-2', 'delivery_status' => 'awaiting_dispatch'], array_intersect_key($one->json(), array_flip(['external_order_id', 'delivery_status'])));
        $this->assertNotNull($one->json('tracking_code'));
        $this->getJson('/api/v1/partner/deliveries/NOPE', $h)->assertNotFound();

        $list = $this->getJson('/api/v1/partner/deliveries?status=awaiting_dispatch', $h)->assertOk();
        $this->assertSame(['S-2', 'S-1'], array_column($list->json('data'), 'external_order_id'));
        $this->assertFalse($list->json('has_more'));
        $this->postJson('/api/v1/partner/deliveries/batch', ['deliveries' => array_fill(0, 51, $this->delivery('X'))], $h)->assertStatus(422);
        $this->getJson('/api/v1/partner/deliveries')->assertStatus(401);
    }
}
