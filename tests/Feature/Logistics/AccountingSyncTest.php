<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Accounting\AccountingSync;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** A provider links QuickBooks or Xero and gets one balanced journal a day of their wallet money. */
class AccountingSyncTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.quickbooks.client_id' => 'qb-id', 'services.quickbooks.client_secret' => 'qb-secret', 'services.xero.client_id' => 'x-id', 'services.xero.client_secret' => 'x-secret']);
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
    }

    /** One paid-out delivery, $days days ago. */
    private function deliveryTwoDaysAgo(int $days = 2, int $price = 300_000): void
    {
        $this->travelTo(now('Africa/Lagos')->subDays($days)->setTime(11, 0));
        $c = $this->makeUser('Ada');
        $this->fundCustomer($c->id, 5_000_000);
        $a = $this->lockedAgreement($c->id, $this->op, $this->owner->id, $price);
        app(PaymentService::class)->payWithWallet($a, $c->id);
        app(EscrowService::class)->release($a);
        $this->travelBack();
    }

    /** Goes through the sign-in round trip and returns the state the service sent back. */
    private function link(string $service, array $query = []): void
    {
        $r = $this->actingAs($this->owner)->post("/provider/accounting/connect/{$service}");
        parse_str(parse_url($r->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->assertSame(route('provider.accounting.callback', $service), $q['redirect_uri']);
        $this->actingAs($this->owner)->get("/provider/accounting/callback/{$service}?".http_build_query(['code' => 'abc', 'state' => $q['state']] + $query))
            ->assertRedirect('/provider/accounting')->assertSessionHas('success');
    }

    private function mapAll(): void
    {
        $this->actingAs($this->owner)->post('/provider/accounting', ['accounts' => array_combine(array_keys(AccountingSync::ACCOUNTS), ['10', '40', '60', '61', '11', '12', '90'])])
            ->assertSessionHas('success');
    }

    public function test_quickbooks_gets_one_balanced_journal_per_day_and_never_twice(): void
    {
        Http::fake([
            'oauth.platform.intuit.com/*' => Http::response(['access_token' => 'qb-access', 'refresh_token' => 'qb-refresh', 'expires_in' => 3600]),
            '*/companyinfo/*' => Http::response(['CompanyInfo' => ['CompanyName' => 'Swift Books Ltd']]),
            '*/query*' => Http::response(['QueryResponse' => ['Account' => [['Id' => '10', 'Name' => 'App wallet', 'AccountType' => 'Bank'], ['Id' => '40', 'Name' => 'Delivery income', 'AccountType' => 'Income']]]]),
            '*/journalentry*' => Http::response(['JournalEntry' => ['Id' => '777']]),
        ]);
        $this->deliveryTwoDaysAgo();
        $this->actingAs($this->owner)->get('/provider/accounting')->assertOk()->assertSee('Link QuickBooks')->assertSee('Link Xero');
        $this->link('quickbooks', ['realmId' => '9130']);
        $conn = DB::table('accounting_connections')->first();
        $this->assertSame(['quickbooks', '9130', 'Swift Books Ltd'], [$conn->service, $conn->tenant_id, $conn->tenant_name]);
        $this->assertNotSame('qb-access', $conn->access_token);   // stored encrypted
        $this->actingAs($this->owner)->get('/provider/accounting')->assertOk()->assertSee('Swift Books Ltd')->assertSee('App wallet (Bank)');

        $this->actingAs($this->owner)->post('/provider/accounting', ['accounts' => ['wallet' => '10']])->assertSessionHasErrors();
        $this->mapAll();
        // Start a week back so the delivery day is included.
        $this->actingAs($this->owner)->post('/provider/accounting', ['accounts' => array_combine(array_keys(AccountingSync::ACCOUNTS), ['10', '40', '60', '61', '11', '12', '90']), 'sync_from' => today()->subDays(7)->toDateString()]);

        $this->actingAs($this->owner)->post('/provider/accounting/sync')->assertSessionHas('success', '1 day(s) sent.');
        $sent = Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/journalentry'))->values();
        $this->assertCount(1, $sent);
        $body = $sent[0][0]->data();
        $this->assertSame(now('Africa/Lagos')->subDays(2)->toDateString(), $body['TxnDate']);
        $debits = collect($body['Line'])->where('JournalEntryLineDetail.PostingType', 'Debit')->sum('Amount');
        $credits = collect($body['Line'])->where('JournalEntryLineDetail.PostingType', 'Credit')->sum('Amount');
        $this->assertEqualsWithDelta($debits, $credits, 0.001);
        // Income is the full delivery price; the fee and the wallet share make it up.
        $this->assertEqualsWithDelta(3000.0, collect($body['Line'])->where('JournalEntryLineDetail.AccountRef.value', '40')->sum('Amount'), 0.001);
        $wallet = collect($body['Line'])->firstWhere('JournalEntryLineDetail.AccountRef.value', '10')['Amount'];
        $fee = collect($body['Line'])->firstWhere('JournalEntryLineDetail.AccountRef.value', '60')['Amount'] ?? 0;
        $this->assertEqualsWithDelta(3000.0, $wallet + $fee, 0.001);
        $this->assertSame((string) now('Africa/Lagos')->subDay()->toDateString(), (string) DB::table('accounting_connections')->value('synced_through'));
        $this->assertSame(['sent', '777'], [DB::table('accounting_syncs')->where('status', 'sent')->value('status'), DB::table('accounting_syncs')->where('status', 'sent')->value('external_id')]);

        // Again, and nightly: nothing new.
        $this->actingAs($this->owner)->post('/provider/accounting/sync')->assertSessionHas('success', 'Up to date. Nothing new to send.');
        $this->artisan('accounting:sync')->assertSuccessful();
        $this->assertCount(1, Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/journalentry')));
        $this->actingAs($this->owner)->get('/provider/accounting')->assertOk()->assertSee('no money moved')->assertSee('sent');
    }

    public function test_xero_refreshes_an_expired_token_and_a_refused_journal_stops_and_shows(): void
    {
        $journals = 0;
        Http::fake(function (HttpRequest $r) use (&$journals) {
            $u = $r->url();
            if (str_contains($u, 'identity.xero.com')) {
                return Http::response(['access_token' => $r['grant_type'] === 'refresh_token' ? 'x-new' : 'x-old', 'refresh_token' => 'x-r2', 'expires_in' => 1800]);
            }
            if (str_contains($u, '/connections')) {
                return Http::response([['tenantId' => 't-1', 'tenantName' => 'Swift Xero', 'tenantType' => 'ORGANISATION']]);
            }
            if (str_contains($u, '/Accounts')) {
                return Http::response(['Accounts' => [['Code' => '090', 'Name' => 'Wallet', 'Type' => 'BANK']]]);
            }
            if (str_contains($u, '/ManualJournals')) {
                $journals++;
                if ($r->header('Authorization')[0] === 'Bearer x-old') {
                    return Http::response([], 401);
                }

                return $journals > 2 ? Http::response(['Elements' => [['ValidationErrors' => [['Message' => 'Account code 12 is not valid']]]]], 400)
                    : Http::response(['ManualJournals' => [['ManualJournalID' => 'mj-1']]]);
            }

            return Http::response([], 404);
        });
        $this->deliveryTwoDaysAgo(3);
        $this->link('xero');
        $this->assertSame(['t-1', 'Swift Xero'], [DB::table('accounting_connections')->value('tenant_id'), DB::table('accounting_connections')->value('tenant_name')]);
        $this->mapAll();
        DB::table('accounting_connections')->update(['sync_from' => today()->subDays(7)]);

        $this->travelTo(now()->subDays(2));   // so yesterday is the delivery day
        $this->assertSame(1, app(AccountingSync::class)->run(DB::table('accounting_connections')->first()));
        $this->travelBack();
        $this->assertSame(2, $journals);   // refused with the old token, then sent with the new one
        $sent = Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/ManualJournals'))->values()[1][0];
        $this->assertSame('t-1', $sent->header('xero-tenant-id')[0]);
        $this->assertSame(0.0, round(collect($sent['ManualJournals'][0]['JournalLines'])->sum('LineAmount'), 2));

        // A later day Xero refuses: sending stops, the reason shows, nothing is skipped.
        $this->deliveryTwoDaysAgo(1);
        $this->artisan('accounting:sync')->assertSuccessful();
        $c = DB::table('accounting_connections')->first();
        $this->assertSame('error', $c->status);
        $this->assertStringContainsString('Account code 12 is not valid', $c->error);
        $this->assertSame(1, DB::table('accounting_syncs')->where('status', 'failed')->count());
        $this->actingAs($this->owner)->get('/provider/accounting')->assertOk()->assertSee('Sending stopped');
    }

    public function test_a_forged_or_stale_link_is_refused_and_riders_cannot_see_the_page(): void
    {
        Http::fake();
        $this->actingAs($this->owner)->get('/provider/accounting/callback/quickbooks?code=x&state=forged&realmId=1')->assertSessionHas('error', 'That link has expired. Start connecting again.');
        $this->actingAs($this->owner)->get('/provider/accounting/callback/xero?error=access_denied')->assertSessionHas('error', 'The link was not approved.');
        $this->assertSame(0, DB::table('accounting_connections')->count());

        config(['services.xero.client_id' => null]);
        $this->actingAs($this->owner)->post('/provider/accounting/connect/xero')->assertSessionHas('error');

        $rider = $this->makeUser('Rider');
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $rider->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $rider->id)->update(['current_operator_id' => $this->op]);
        $this->actingAs($rider->fresh())->get('/provider/accounting')->assertForbidden();
    }
}
