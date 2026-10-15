<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Backoffice\ManualBookingService;
use App\Modules\Billing\BusinessAccountService;
use App\Modules\Billing\InvoiceService;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Partner\ApiClientService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Business accounts that book on credit, and the monthly invoices that bill them. */
class BusinessInvoicesTest extends TestCase
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
        Mail::fake();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');
    }

    private function addAccount(array $extra = []): string
    {
        $this->actingAs($this->owner)->post('/provider/business-accounts', $extra + ['name' => 'Acme Stores', 'billing_email' => 'accounts@acme.test',
            'billing_phone' => '08035550000', 'credit_limit_naira' => 10000, 'terms_days' => 14])->assertRedirect();

        return (string) DB::table('business_accounts')->where('name', $extra['name'] ?? 'Acme Stores')->value('public_id');
    }

    private function book(string $account, int $price = 300_000, array $customer = []): array
    {
        return app(ManualBookingService::class)->book($this->op, [
            'customer' => $customer, 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_phone' => '08030000002'],
            'packages' => [['description' => 'Box']], 'price' => $price, 'payment' => 'account', 'business_account' => $account,
        ], $this->owner->id);
    }

    public function test_deliveries_on_account_go_to_dispatch_and_respect_the_credit_limit(): void
    {
        $acc = $this->addAccount();
        $this->actingAs($this->owner)->get('/provider/business-accounts')->assertOk()->assertSee('Acme Stores')->assertSee('₦10,000.00');
        $this->actingAs($this->owner)->get('/provider/bookings/new')->assertOk()->assertSee('On account (monthly invoice)')->assertSee($acc);
        $this->actingAs($this->owner)->get('/provider/bookings/bulk')->assertOk()->assertSee($acc);
        $r = $this->book($acc);
        $o = DB::table('orders')->find($r['order_id']);
        $a = DB::table('business_accounts')->where('public_id', $acc)->first();
        $this->assertSame(['account', (int) $a->id, null], [$o->payment_method, (int) $o->business_account_id, $o->invoice_id]);
        $this->assertSame('awaiting_dispatch', DB::table('shipments')->where('id', $r['shipment_id'])->value('status'));
        // No customer was given: the business's billing contact is the customer.
        $this->assertSame('Acme Stores', DB::table('users')->where('id', $o->customer_id)->value('name'));
        $this->assertSame(300_000, app(BusinessAccountService::class)->owed((int) $a->id)['unbilled']);

        // ₦10,000 limit: ₦3,000 used, so ₦8,000 more is refused and nothing is left behind.
        $agreements = DB::table('agreements')->count();
        try {
            $this->book($acc, 800_000);
            $this->fail('Booking over the limit should be refused.');
        } catch (RuntimeException $e) {
            $this->assertSame('This booking would take Acme Stores over its credit limit. Credit left: ₦7,000.00.', $e->getMessage());
        }
        $this->assertSame($agreements, DB::table('agreements')->count());
        $this->book($acc, 700_000);

        $this->actingAs($this->owner)->post("/provider/business-accounts/{$acc}/status", ['status' => 'on_hold']);
        $this->expectExceptionMessage('The account Acme Stores is on hold. Nothing new can be booked on it.');
        $this->book($acc, 100);
    }

    public function test_the_month_is_billed_once_with_only_finished_deliveries(): void
    {
        $acc = $this->addAccount(['credit_limit_naira' => null]);
        $a = DB::table('business_accounts')->where('public_id', $acc)->first();
        $done = $this->book($acc, 300_000);
        $road = $this->book($acc, 200_000);
        $gone = $this->book($acc, 150_000);
        DB::table('shipments')->where('id', $done['shipment_id'])->update(['status' => 'delivered', 'delivered_at' => now()]);
        DB::table('shipments')->where('id', $gone['shipment_id'])->update(['status' => 'cancelled']);
        DB::table('escrow_holds')->where('agreement_id', $gone['agreement_id'])->update(['refunded_amount' => 150_000, 'status' => 'refunded']);
        DB::table('orders')->update(['created_at' => '2026-10-20 10:00:00']);

        $n = app(InvoiceService::class)->monthly(CarbonImmutable::parse('2026-11-01 06:00'));
        $this->assertSame(1, $n);
        $inv = DB::table('invoices')->where('business_account_id', $a->id)->first();
        $this->assertSame(['INV-'.now()->format('ym').'-0001', 300_000, 'issued', '2026-10-01', '2026-10-31'],
            [$inv->number, (int) $inv->total, $inv->status, $inv->period_start, $inv->period_end]);
        $this->assertSame(now()->addDays(14)->toDateString(), $inv->due_on);
        $lines = json_decode($inv->lines, true);
        $this->assertCount(1, $lines);
        $this->assertSame(['1 Market Rd', '9 Hill St', 300_000], [$lines[0]['from'], $lines[0]['to'], $lines[0]['amount']]);
        // The cancelled, fully refunded job is settled by this invoice; the one on the road waits.
        $this->assertSame($inv->id, DB::table('orders')->where('id', $gone['order_id'])->value('invoice_id'));
        $this->assertNull(DB::table('orders')->where('id', $road['order_id'])->value('invoice_id'));
        $this->assertSame(0, app(InvoiceService::class)->monthly(CarbonImmutable::parse('2026-11-01 06:00')));

        $this->assertSame(['unbilled' => 200_000, 'invoiced' => 300_000, 'total' => 500_000, 'overdue' => 0], app(BusinessAccountService::class)->owed((int) $a->id));
        $page = $this->actingAs($this->owner)->get("/provider/business-accounts/{$acc}")->assertOk();
        $page->assertSee($inv->number)->assertSee('₦3,000.00')->assertSee('(not finished)', false);

        $pdf = $this->actingAs($this->owner)->get("/provider/invoices/{$inv->public_id}.pdf")->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->actingAs($this->owner)->post("/provider/invoices/{$inv->public_id}/send")->assertSessionHas('success', "Invoice {$inv->number} emailed.");
        Mail::assertSent(\App\Mail\NoticeMail::class, fn ($m) => $m->hasTo('accounts@acme.test'));
        $this->assertNotNull(DB::table('invoices')->where('id', $inv->id)->value('sent_at'));

        // Overdue: no more bookings until it is paid.
        DB::table('invoices')->where('id', $inv->id)->update(['due_on' => now()->subDay()->toDateString()]);
        try {
            $this->book($acc, 100);
            $this->fail('An overdue invoice should block bookings.');
        } catch (RuntimeException $e) {
            $this->assertSame('Acme Stores has an overdue invoice. Record the payment before booking more on account.', $e->getMessage());
        }
        $this->actingAs($this->owner)->post("/provider/invoices/{$inv->public_id}/paid", ['reference' => 'TRF-889'])->assertSessionHas('success', 'Payment recorded.');
        $this->assertSame(['paid', 'TRF-889'], [DB::table('invoices')->where('id', $inv->id)->value('status'), DB::table('invoices')->where('id', $inv->id)->value('paid_reference')]);
        $this->actingAs($this->owner)->post("/provider/invoices/{$inv->public_id}/paid")->assertSessionHas('error', "Invoice {$inv->number} is already paid.");
        $this->book($acc, 100);
    }

    public function test_staff_bill_an_account_now_and_numbers_run_on(): void
    {
        $acc = $this->addAccount();
        $this->actingAs($this->owner)->post("/provider/business-accounts/{$acc}/invoice")->assertSessionHas('error', 'There are no finished deliveries to bill yet.');
        $r = $this->book($acc);
        DB::table('shipments')->where('id', $r['shipment_id'])->update(['status' => 'delivered']);
        $this->actingAs($this->owner)->post("/provider/business-accounts/{$acc}/invoice")->assertSessionHas('success', 'Invoice INV-'.now()->format('ym').'-0001 issued for ₦3,000.00.');
        $r = $this->book($acc);
        DB::table('shipments')->where('id', $r['shipment_id'])->update(['status' => 'delivered']);
        $this->actingAs($this->owner)->post("/provider/business-accounts/{$acc}/invoice")->assertSessionHas('success', 'Invoice INV-'.now()->format('ym').'-0002 issued for ₦3,000.00.');

        // Another provider cannot see the account or its invoices.
        [, $other] = $this->makeProvider('company', 'Other Co');
        $this->actingAs($other)->get("/provider/business-accounts/{$acc}")->assertNotFound();
        $inv = DB::table('invoices')->whereNotNull('business_account_id')->value('public_id');
        $this->actingAs($other)->get("/provider/invoices/{$inv}.pdf")->assertNotFound();
        // The customer's receipt is the invoice, not a per-job receipt.
        $order = DB::table('orders')->orderBy('id')->first();
        $this->expectExceptionMessage('This delivery is billed on the monthly invoice.');
        app(\App\Modules\Booking\Receipts::class)->data((int) $order->id, (int) $order->customer_id);
    }

    public function test_a_bulk_upload_can_go_on_account(): void
    {
        $acc = $this->addAccount(['credit_limit_naira' => null]);
        $csv = "reference,dropoff_address,dropoff_lat,dropoff_lng,recipient_phone,price_naira\nA1,9 Hill St,7.82,5.92,08030000002,1500\nA2,10 Hill St,7.83,5.93,08030000003,1500\n";
        $file = UploadedFile::fake()->createWithContent('sheet.csv', $csv);
        $res = $this->actingAs($this->owner)->post('/provider/bookings/bulk', ['file' => $file, 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'payment' => 'account', 'business_account' => $acc, 'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90]]);
        $batch = DB::table('bulk_batches')->value('public_id');
        $res->assertRedirect("/provider/bookings/bulk/{$batch}");
        $this->actingAs($this->owner)->post("/provider/bookings/bulk/{$batch}/book")->assertSessionHas('success', '2 deliveries booked.');
        $a = DB::table('business_accounts')->where('public_id', $acc)->first();
        $this->assertSame(2, DB::table('orders')->where('business_account_id', $a->id)->where('payment_method', 'account')->count());
    }

    public function test_a_partner_on_monthly_invoicing_books_on_account(): void
    {
        DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $this->city, 'service_type_id' => $this->serviceTypeId(),
            'name' => 'Standard', 'min_fee' => 150000, 'rounding_kobo' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        [$mOp] = $this->makeProvider('merchant', 'Shoe Shop');
        $mid = DB::table('merchants')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $mOp, 'merchant_code' => 'SHOE01', 'display_name' => 'Shoe Shop',
            'status' => 'verified', 'settlement_mode' => 'monthly_invoice', 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $h = ['Authorization' => 'Bearer '.app(ApiClientService::class)->issue($mOp, $mid, 'Shop', 'live')['key']];
        $this->actingAs($this->owner)->post('/provider/business-accounts', ['name' => 'Shoe Shop', 'merchant_code' => 'shoe01', 'credit_limit_naira' => 2000])->assertRedirect();
        $this->actingAs($this->owner)->post('/provider/business-accounts', ['name' => 'Again', 'merchant_code' => 'SHOE01'])->assertSessionHas('error', 'That partner business already bills to the account Shoe Shop.');

        $body = fn ($ref) => ['external_order_id' => $ref, 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_phone' => '08030000002']];
        $r = $this->postJson('/api/v1/partner/deliveries', $body('S-1'), $h)->assertCreated();
        $this->assertNull($r->json('payment_url'));
        $o = DB::table('orders')->first();
        $this->assertSame(['account', $mid], [$o->payment_method, (int) $o->merchant_id]);
        $this->assertNotNull($o->business_account_id);
        // ₦2,000 limit and ₦1,500 used: the next one does not fit.
        $this->postJson('/api/v1/partner/deliveries', $body('S-2'), $h)->assertStatus(422);
        $this->assertSame(1, DB::table('agreements')->count());
    }
}
