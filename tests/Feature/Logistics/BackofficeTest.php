<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Backoffice\ReportService;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\Ledger\AccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Manual bookings, job edits, the drag-and-drop board's JSON assign, and the reports, in both platform modes. */
class BackofficeTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private int $city;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // no routing engine or gateway calls leave the test
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');
    }

    private function booking(array $over = []): array
    {
        return array_replace_recursive([
            'customer' => ['name' => 'Ada Phone', 'phone' => '08031234567', 'email' => 'ada@example.test'],
            'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city, 'items' => "Box of shoes\nEnvelope", 'weight_kg' => 3,
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90, 'contact_phone' => '08030000001'],
            'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92, 'contact_name' => 'Bisi', 'contact_phone' => '08030000002'],
            'price_naira' => 2500, 'payment' => 'cash',
        ], $over);
    }

    private function driver(string $name, int $max = 2): User
    {
        $u = User::factory()->create(['name' => $name, 'must_change_password' => false]);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $u->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('driver_profiles')->insert(['public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'operator_id' => $this->op, 'status' => 'active', 'availability' => 'online', 'max_active_jobs' => $max, 'created_at' => now(), 'updated_at' => now()]);

        return $u;
    }

    private function member(string $role): User
    {
        $u = User::factory()->create(['must_change_password' => false]);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $u->id, 'role' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $u->id)->update(['current_operator_id' => $this->op]);

        return $u->fresh();
    }

    private function staff(array $perms): User
    {
        $u = User::factory()->create(['must_change_password' => false]);
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo($perms);

        return $u;
    }

    /** Marks the job delivered and releases the money, as the customer's confirmation does. */
    private function deliver(object $shipment): void
    {
        DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'delivered', 'delivered_at' => now(), 'updated_at' => now()]);
        app(\App\Modules\Partner\ShipmentEvents::class)->record((int) $shipment->id, 'delivered', 'at_dropoff', 'delivered');
        DB::table('agreements')->where('id', DB::table('orders')->where('id', $shipment->order_id)->value('agreement_id'))->update(['status' => 'delivered']);
        app(EscrowService::class)->release((int) DB::table('orders')->where('id', $shipment->order_id)->value('agreement_id'));
    }

    // ---------------------------------------------------------------- manual booking

    public function test_provider_books_a_cash_job_for_a_new_customer_and_the_books_balance_after_delivery(): void
    {
        $this->actingAs($this->owner)->get('/provider/bookings/new')->assertOk()->assertSee('New booking')->assertSee('Testville');

        $res = $this->actingAs($this->owner)->post('/provider/bookings', $this->booking());
        $s = DB::table('shipments')->where('operator_id', $this->op)->first();
        $res->assertRedirect("/provider/jobs/{$s->public_id}")->assertSessionHas('success');

        $customer = DB::table('users')->where('phone_number', '08031234567')->first();
        $this->assertSame('ada@example.test', $customer->email);
        $order = DB::table('orders')->where('id', $s->order_id)->first();
        $this->assertSame(['manual', 'cash', 'paid', 250000], [$order->channel, $order->payment_method, $order->payment_status, (int) $order->total]);
        $this->assertSame((int) $customer->id, (int) $order->customer_id);
        $this->assertContains($s->status, ['awaiting_dispatch', 'offered', 'no_supply', 'unassigned']);
        $this->assertSame(2, DB::table('packages')->where('shipment_id', $s->id)->count());
        $this->assertSame(3000, (int) DB::table('packages')->where('shipment_id', $s->id)->sum('weight_g'));
        $terms = json_decode(DB::table('agreements')->where('id', $order->agreement_id)->value('terms'), true);
        $this->assertSame($this->owner->id, $terms['booked_by']);

        $this->deliver($s);
        $this->assertLedgerBalanced();
        // The provider already has the cash: nothing to withdraw, and it owes the platform its fee.
        $fee = (int) DB::table('agreements')->where('id', $order->agreement_id)->value('platform_fee');
        $this->assertGreaterThan(0, $fee);
        $this->assertSame(0, $this->walletBalance('operator', $this->op));
        $cod = app(AccountResolver::class)->for('operator', $this->op, 'cod_clearing', $this->op);
        $this->assertSame($fee, (int) DB::table('ledger_accounts')->where('id', $cod)->value('balance'));
    }

    public function test_booking_straight_to_a_chosen_driver(): void
    {
        $d = $this->driver('Bola Driver');
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['driver_user_id' => $d->id, 'payment' => 'bank_transfer']))->assertSessionHas('success', 'Booking saved and given to the driver you chose.');
        $s = DB::table('shipments')->where('operator_id', $this->op)->first();
        $this->assertSame('assigned', $s->status);
        $this->assertTrue(DB::table('assignments')->where('shipment_id', $s->id)->where('status', 'accepted')->exists());
    }

    public function test_existing_customer_is_found_and_bad_inputs_are_explained(): void
    {
        $existing = User::factory()->create(['phone_number' => '08031234567', 'email' => 'old@example.test']);

        // A payment link needs an email; a phone-only new customer gets an address that cannot receive mail.
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['customer' => ['phone' => '08099999999', 'email' => '', 'name' => 'Phone Only'], 'payment' => 'link']))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'needs the customer\'s email'));
        $this->assertSame('08099999999@phone.invalid', DB::table('users')->where('phone_number', '08099999999')->value('email'));

        // Phone and email of two different people
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['customer' => ['email' => 'phone-only@example.test']]))->assertSessionHas('success');
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['customer' => ['phone' => '08099999999', 'email' => 'old@example.test']]))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'two different accounts'));

        // The wallet is empty, so a wallet booking is refused and nothing is left half-made.
        $before = DB::table('orders')->count();
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['customer' => ['phone' => '08031234567', 'email' => ''], 'payment' => 'wallet']))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'wallet does not have enough'));
        $this->assertSame($before, DB::table('orders')->count());
        $this->assertSame('cancelled', DB::table('agreements')->where('customer_id', $existing->id)->orderByDesc('id')->value('status'));

        // Missing map pins
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['pickup' => ['lat' => '']]))->assertSessionHasErrors('pickup.lat');
    }

    public function test_wallet_booking_and_link_booking(): void
    {
        $this->fundCustomer(User::factory()->create(['phone_number' => '08031234567'])->id, 1_000_000);
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['payment' => 'wallet', 'customer' => ['email' => '']]))->assertRedirect();
        $this->assertSame('wallet', DB::table('orders')->value('payment_method'));
        $this->assertSame('manual', DB::table('orders')->value('channel'));

        // A link booking waits for payment: the agreement is locked, no order yet.
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['payment' => 'link', 'customer' => ['phone' => '08040000000', 'email' => 'link@example.test', 'name' => 'Link Person']]))
            ->assertRedirect('/provider/bookings/new')->assertSessionHas('booked');
        $linkCustomer = DB::table('users')->where('email', 'link@example.test')->value('id');
        $this->assertSame('locked', DB::table('agreements')->where('customer_id', $linkCustomer)->value('status'));
        $this->assertSame(1, DB::table('orders')->count());
    }

    public function test_price_from_the_price_list(): void
    {
        $body = $this->booking();
        $this->actingAs($this->owner)->postJson('/provider/bookings/price', $body)->assertStatus(422)->assertJsonFragment(['message' => 'There is no price list for this service and city. Type the price instead.']);

        DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'city_id' => $this->city, 'service_type_id' => $this->serviceTypeId(),
            'name' => 'Standard', 'min_fee' => 150000, 'rounding_kobo' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->owner)->postJson('/provider/bookings/price', $body)->assertOk()->assertJsonPath('total', 150000);

        // No price typed: the price list is used.
        unset($body['price_naira']);
        $this->actingAs($this->owner)->post('/provider/bookings', $body)->assertSessionHas('success');
        $this->assertSame(150000, (int) DB::table('orders')->value('total'));
    }

    public function test_only_dispatch_roles_may_book(): void
    {
        $this->actingAs($this->member('finance'))->get('/provider/bookings/new')->assertForbidden();
        $this->actingAs($this->member('dispatcher'))->get('/provider/bookings/new')->assertOk();
    }

    public function test_staff_book_for_a_chosen_provider_in_marketplace_mode(): void
    {
        $staff = $this->staff(['Create delivery', 'View delivery']);
        $public = DB::table('operators')->where('id', $this->op)->value('public_id');
        $this->actingAs($this->staff(['View delivery']))->get('/ops/bookings/new')->assertForbidden();
        $this->actingAs($staff)->get('/ops/bookings/new')->assertOk()->assertSee('Swift Haulage');

        $this->actingAs($staff)->post('/ops/bookings', $this->booking())->assertSessionHas('error', 'Choose the provider that will do this delivery.');
        $res = $this->actingAs($staff)->post('/ops/bookings', $this->booking(['provider' => $public]));
        $order = DB::table('orders')->first();
        $res->assertRedirect("/ops/orders/{$order->public_id}");
        $this->assertSame($this->op, (int) $order->operator_id);
    }

    public function test_company_mode_books_with_the_house_operator(): void
    {
        config(['platform.mode' => 'company', 'platform.house_operator_id' => $this->op]);
        $staff = $this->staff(['Create delivery', 'View delivery', 'View reports', 'Export reports']);
        $this->actingAs($staff)->get('/ops/bookings/new')->assertOk()->assertDontSee('Choose who does the delivery');
        $this->actingAs($staff)->post('/ops/bookings', $this->booking())->assertRedirect();
        $this->assertSame($this->op, (int) DB::table('orders')->value('operator_id'));

        $this->actingAs($staff)->get('/ops/reports/daily')->assertOk()->assertDontSee('All providers');
        $this->actingAs($this->owner)->get('/provider/reports/daily')->assertOk();
    }

    // ---------------------------------------------------------------- editing a job

    public function test_dispatch_corrects_the_drop_off_and_the_driver_is_told(): void
    {
        $d = $this->driver('Bola Driver');
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['driver_user_id' => $d->id]));
        $s = DB::table('shipments')->first();
        $pkg = DB::table('packages')->where('shipment_id', $s->id)->orderBy('id')->first();

        $this->actingAs($this->owner)->get("/provider/jobs/{$s->public_id}")->assertSee('Edit details');
        $this->actingAs($this->owner)->get("/provider/jobs/{$s->public_id}/edit")->assertOk()->assertSee('9 Hill St');
        $this->actingAs($this->owner)->put("/provider/jobs/{$s->public_id}", [
            'dropoff' => ['line1' => '12 New Rd', 'contact_phone' => '08055555555', 'lat' => 7.83, 'lng' => 5.93, 'instructions' => 'Blue gate'],
            'packages' => [$pkg->public_id => ['description' => 'Box of sandals', 'weight_kg' => 2]],
        ])->assertRedirect("/provider/jobs/{$s->public_id}")->assertSessionHas('success', 'Job updated. The driver has been told.');

        $drop = DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->selectRaw('line1, contact_phone, instructions, ST_Y(point::geometry) lat')->first();
        $this->assertSame(['12 New Rd', '08055555555', 'Blue gate'], [$drop->line1, $drop->contact_phone, $drop->instructions]);
        $this->assertEqualsWithDelta(7.83, (float) $drop->lat, 0.00001);
        $this->assertSame('Box of sandals', DB::table('packages')->where('id', $pkg->id)->value('description'));
        $meta = json_decode(DB::table('shipment_events')->where('shipment_id', $s->id)->where('type', 'details_updated')->value('meta'), true);
        $this->assertSame(['9 Hill St', '12 New Rd'], $meta['changes']['dropoff.line1']);
        $this->assertSame(['changed', 'changed'], $meta['changes']['dropoff.contact_phone']); // numbers stay out of the timeline
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $d->id)->where('type', 'delivery.job_updated')->exists());

        // Once collected, the pickup is locked but the drop-off can still change.
        DB::table('shipments')->where('id', $s->id)->update(['status' => 'in_transit']);
        DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'pickup')->update(['status' => 'completed']);
        $this->actingAs($this->owner)->put("/provider/jobs/{$s->public_id}", ['pickup' => ['line1' => 'Elsewhere']])
            ->assertSessionHas('error', 'The pickup can no longer be changed because the goods have been collected.');
        $this->actingAs($this->owner)->put("/provider/jobs/{$s->public_id}", ['dropoff' => ['landmark' => 'Opposite the church']])->assertSessionHas('success');

        // Another provider cannot touch it.
        [, $other] = $this->makeProvider('company', 'Other Co');
        $this->actingAs($other)->get("/provider/jobs/{$s->public_id}/edit")->assertNotFound();
    }

    public function test_staff_edit_needs_permission(): void
    {
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking());
        $order = DB::table('orders')->value('public_id');
        $this->actingAs($this->staff(['View delivery']))->get("/ops/orders/{$order}/edit")->assertForbidden();
        $editor = $this->staff(['View delivery', 'Update delivery']);
        $this->actingAs($editor)->get("/ops/orders/{$order}")->assertOk()->assertSee('Edit details');
        $this->actingAs($editor)->put("/ops/orders/{$order}", ['pickup' => ['instructions' => 'Ask for Tunde']])->assertRedirect("/ops/orders/{$order}");
        $this->assertSame('Ask for Tunde', DB::table('shipment_stops')->where('type', 'pickup')->value('instructions'));
        $this->assertSame('staff', DB::table('shipment_events')->where('type', 'details_updated')->value('actor_type'));
    }

    // ---------------------------------------------------------------- drag and drop

    public function test_board_shows_driver_lanes_and_assigns_over_json(): void
    {
        $a = $this->driver('Bola Driver', 1);
        $b = $this->driver('Chidi Driver', 1);
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking());
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking());
        [$j1, $j2] = DB::table('shipments')->orderBy('id')->pluck('public_id')->all();

        $this->actingAs($this->owner)->get('/provider/dispatch')->assertOk()->assertSee('Bola Driver')->assertSee('Chidi Driver')->assertSee('data-driver="'.$a->id.'"', false);

        $this->actingAs($this->owner)->postJson("/provider/jobs/{$j1}/assign", ['driver_user_id' => $a->id])->assertOk()->assertJsonPath('ok', true);
        $this->actingAs($this->owner)->postJson("/provider/jobs/{$j2}/assign", ['driver_user_id' => $a->id])->assertStatus(422)
            ->assertJsonPath('message', 'That driver already has the most jobs they can take at once.');
        // Moving a job from one driver to another before pickup
        $this->actingAs($this->owner)->postJson("/provider/jobs/{$j1}/assign", ['driver_user_id' => $b->id])->assertOk();
        $this->assertSame('reassigned', DB::table('assignments')->where('driver_profile_id', DB::table('driver_profiles')->where('user_id', $a->id)->value('id'))->value('status'));
    }

    // ---------------------------------------------------------------- reports

    public function test_reports_render_with_real_numbers_and_export(): void
    {
        $d = $this->driver('Bola Driver');
        $this->actingAs($this->owner)->post('/provider/bookings', $this->booking(['driver_user_id' => $d->id]));
        $s = DB::table('shipments')->first();
        app(\App\Modules\Partner\ShipmentEvents::class)->record((int) $s->id, 'picked_up', 'at_pickup', 'in_transit', 'driver', 1);
        DB::table('assignments')->where('shipment_id', $s->id)->update(['status' => 'completed']);
        $this->deliver($s);
        DB::table('ratings')->insert(['shipment_id' => $s->id, 'rater_type' => 'customer', 'rater_id' => 1, 'ratee_type' => 'operator', 'ratee_id' => $this->op, 'score' => 4, 'created_at' => now(), 'updated_at' => now()]);

        $svc = app(ReportService::class);
        $from = now()->subDays(2)->toImmutable();
        $to = now()->addDay()->toImmutable();
        $daily = $svc->daily($this->op, $from, $to);
        $this->assertSame(2500.0, array_sum(array_column($daily['rows'], 5)));
        $this->assertSame(1, array_sum(array_column($daily['rows'], 2)));
        $drivers = $svc->drivers($this->op, $from, $to);
        $this->assertSame(['Bola Driver', 1, 0], array_slice($drivers['rows'][0], 0, 3));
        $this->assertSame(4.0, $drivers['rows'][0][7]);
        $this->assertSame(1, $svc->retention($this->op, $from, $to)['rows'][0][1]);
        $this->assertSame('Near 1 Market Rd', $svc->routes($this->op, $from, $to)['rows'][0][0]);
        $this->assertSame(1, $svc->times($this->op, $from, $to)['summary'][0][1]);
        $this->assertSame(1, $svc->customers(null, $from, $to)['summary'][0][1]);

        foreach (array_keys(ReportService::REPORTS) as $r) {
            $this->actingAs($this->owner)->get("/provider/reports/{$r}")->assertOk()->assertSee(ReportService::REPORTS[$r]);
        }
        $this->actingAs($this->owner)->get('/provider/reports/daily')->assertSee('₦2,500');
        $this->actingAs($this->owner)->get('/provider')->assertOk()->assertSee('Revenue')->assertSee('Drivers this week');

        $x = $this->actingAs($this->owner)->get('/provider/reports/drivers/export.xlsx');
        $x->assertOk();
        $this->assertStringContainsString('spreadsheetml', $x->headers->get('content-type'));
        $p = $this->actingAs($this->owner)->get('/provider/reports/monthly/export.pdf');
        $p->assertOk();
        $this->assertSame('application/pdf', $p->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $p->getContent());
    }

    public function test_report_access_follows_role_and_permission(): void
    {
        $this->actingAs($this->member('dispatcher'))->get('/provider/reports/drivers')->assertOk();
        $this->actingAs($this->member('dispatcher'))->get('/provider/reports/monthly')->assertForbidden();
        $this->actingAs($this->member('finance'))->get('/provider/reports/monthly')->assertOk();
        $this->actingAs($this->member('finance'))->get('/provider/reports/drivers/export.xlsx')->assertForbidden();
        $this->actingAs($this->member('driver'))->get('/provider/reports')->assertForbidden();

        $this->actingAs($this->staff([]))->get('/ops/reports')->assertForbidden();
        $money = $this->staff(['View financial report']);
        $this->actingAs($money)->get('/ops/reports')->assertOk()->assertSee('Daily deliveries and revenue')->assertDontSee('Driver performance');
        $this->actingAs($money)->get('/ops/reports/drivers')->assertForbidden();
        $this->actingAs($money)->get('/ops/reports/daily/export.pdf')->assertForbidden(); // needs Export reports
        $all = $this->staff(['View reports', 'Export reports', 'dashboard']);
        foreach (array_keys(ReportService::REPORTS) as $r) {
            $this->actingAs($all)->get("/ops/reports/{$r}?from=2026-01-01&to=2026-12-31")->assertOk();
        }
        $this->actingAs($all)->get('/ops/reports/routes/export.xlsx')->assertOk();
        $this->actingAs($all)->get('/ops/reports/nope')->assertNotFound();
        $this->actingAs($all)->get('/ops')->assertOk()->assertSee('Revenue');
        $this->actingAs($all)->get('/ops/reports/daily?provider='.DB::table('operators')->where('id', $this->op)->value('public_id'))->assertOk()->assertSee('All providers');
    }
}
