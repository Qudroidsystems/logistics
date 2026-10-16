<?php

namespace App\Modules\Accounting;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends a provider's money to its own QuickBooks or Xero, one balanced journal per day.
 *
 * The provider's wallet on the platform is treated as a bank account in their books. Each day, every movement on
 * that wallet is grouped by what it was: jobs paid out (income, with the platform's fee shown as an expense), rider
 * pay, payouts to their bank, and cash they collected themselves. Test jobs are left out. Days already sent are
 * never sent twice, so running it again is safe.
 */
class AccountingSync
{
    /** Account mapping: key => what it is, shown on the settings page. */
    public const ACCOUNTS = [
        'wallet' => 'Your app wallet (set up as a bank account)',
        'income' => 'Delivery income',
        'fees' => 'Platform fees (expense)',
        'rider_pay' => 'Rider pay (expense)',
        'bank' => 'Your bank account (payouts arrive here)',
        'cash' => 'Cash and transfers paid to you directly',
        'other' => 'Other adjustments',
    ];

    /** Ledger transaction kind => the account the other side of a wallet movement goes to. */
    private const KINDS = ['escrow_release' => 'income', 'driver_pay' => 'rider_pay', 'payout' => 'bank', 'offline_settlement' => 'cash'];

    /** Days sent in one run, so a long-unused link catches up over a few nights. */
    public const MAX_DAYS = 31;

    public const SERVICES = ['quickbooks' => 'QuickBooks', 'xero' => 'Xero'];

    public function gateway(string $service): AccountingGateway
    {
        return match ($service) {
            'quickbooks' => app(QuickBooksGateway::class),
            'xero' => app(XeroGateway::class),
            default => throw new RuntimeException('Unknown accounting service.'),
        };
    }

    public static function redirectUrl(string $service): string
    {
        return route('provider.accounting.callback', $service);
    }

    public function connectUrl(int $operatorId, int $userId, string $service): string
    {
        $g = $this->gateway($service);
        if (! $g->configured()) {
            throw new RuntimeException(self::SERVICES[$service].' is not set up on this platform yet. Ask the platform team to add its app keys.');
        }
        $state = Crypt::encryptString(json_encode(['op' => $operatorId, 'user' => $userId, 'service' => $service, 'exp' => now()->addMinutes(15)->timestamp]));

        return $g->authorizeUrl($state, self::redirectUrl($service));
    }

    /** Back from the service with an approval code. */
    public function finish(int $operatorId, int $userId, string $service, string $code, string $state, array $query): object
    {
        try {
            $s = json_decode(Crypt::decryptString($state), true);
        } catch (Throwable) {
            $s = null;
        }
        if (! $s || $s['op'] !== $operatorId || $s['user'] !== $userId || $s['service'] !== $service || $s['exp'] < now()->timestamp) {
            throw new RuntimeException('That link has expired. Start connecting again.');
        }
        $t = $this->gateway($service)->connect($code, self::redirectUrl($service), $query);
        $existing = DB::table('accounting_connections')->where('operator_id', $operatorId)->first();
        $row = [
            'service' => $service, 'tenant_id' => $t['tenant_id'], 'tenant_name' => $t['tenant_name'] ?? null,
            'access_token' => Crypt::encryptString($t['access_token']), 'refresh_token' => Crypt::encryptString($t['refresh_token']),
            'expires_at' => now()->addSeconds($t['expires_in']), 'status' => 'active', 'error' => null, 'connected_by' => $userId, 'updated_at' => now(),
        ];
        // A different company or service starts with a fresh mapping, from today.
        $same = $existing && $existing->service === $service && $existing->tenant_id === $t['tenant_id'];
        if ($existing) {
            DB::table('accounting_connections')->where('id', $existing->id)->update($row + ($same ? [] : ['accounts' => null, 'sync_from' => today(), 'synced_through' => null]));
            if (! $same) {
                DB::table('accounting_syncs')->where('connection_id', $existing->id)->delete();
            }
        } else {
            DB::table('accounting_connections')->insert($row + ['operator_id' => $operatorId, 'sync_from' => today(), 'created_at' => now()]);
        }

        return DB::table('accounting_connections')->where('operator_id', $operatorId)->first();
    }

    public function disconnect(int $operatorId): void
    {
        DB::table('accounting_connections')->where('operator_id', $operatorId)->update([
            'status' => 'disconnected', 'access_token' => '', 'refresh_token' => '', 'updated_at' => now(),
        ]);
    }

    /** The accounts in their books, for the mapping form. */
    public function accounts(object $c): array
    {
        return $this->withToken($c, fn (string $token) => $this->gateway($c->service)->accounts($c, $token));
    }

    public function saveSettings(object $c, array $accounts, ?string $from): void
    {
        $from = $from ? CarbonImmutable::parse($from)->toDateString() : $c->sync_from;
        DB::table('accounting_connections')->where('id', $c->id)->update([
            'accounts' => json_encode(array_intersect_key($accounts, self::ACCOUNTS)), 'sync_from' => $from,
            // Moving the start earlier sends those days too.
            'synced_through' => $c->synced_through && $from <= $c->synced_through ? min($c->synced_through, CarbonImmutable::parse($from)->subDay()->toDateString()) : $c->synced_through,
            'updated_at' => now(),
        ]);
    }

    public static function mapped(object $c): bool
    {
        $a = $c->accounts ? json_decode($c->accounts, true) : [];

        return count(array_filter(array_intersect_key($a, self::ACCOUNTS))) === count(self::ACCOUNTS);
    }

    /**
     * The journal for one provider and one day (Lagos time).
     *
     * @return list<array{account:string, debit:int, credit:int, description:string}> keyed by mapping name, not yet account ids
     */
    public function dayLines(int $operatorId, string $day): array
    {
        $wallet = DB::table('ledger_accounts')->where(['owner_type' => 'operator', 'owner_id' => $operatorId, 'purpose' => 'operator_wallet'])->value('id');
        if (! $wallet) {
            return [];
        }
        $start = CarbonImmutable::parse($day, 'Africa/Lagos')->startOfDay();
        $moves = DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('e.account_id', $wallet)->where('t.is_test', false)
            ->where('t.posted_at', '>=', $start)->where('t.posted_at', '<', $start->addDay())
            ->groupBy('t.kind')->selectRaw("t.kind, sum(case when e.direction = 'credit' then e.amount else -e.amount end) as delta")->get();

        $lines = [];
        $add = function (string $key, int $debit, int $credit, string $what) use (&$lines) {
            if ($debit > 0 || $credit > 0) {
                $lines[] = ['account' => $key, 'debit' => $debit, 'credit' => $credit, 'description' => $what];
            }
        };
        $labels = ['income' => 'Jobs paid out', 'rider_pay' => 'Rider pay', 'bank' => 'Payouts to your bank', 'cash' => 'Cash and transfers you collected', 'other' => 'Other wallet changes'];
        foreach ($moves as $m) {
            $d = (int) $m->delta;
            $other = self::KINDS[$m->kind] ?? 'other';
            if ($d > 0) {
                $add('wallet', $d, 0, $labels[$other]);
                $add($other, 0, $d, $labels[$other]);
            } elseif ($d < 0) {
                $add($other, -$d, 0, $labels[$other]);
                $add('wallet', 0, -$d, $labels[$other]);
            }
        }
        // The platform kept its fee before paying the provider: income is the full price, the fee an expense.
        $fee = (int) DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->where('t.operator_id', $operatorId)->where('t.kind', 'escrow_release')->where('t.is_test', false)
            ->where('a.purpose', 'platform_commission')->where('t.posted_at', '>=', $start)->where('t.posted_at', '<', $start->addDay())
            ->selectRaw("coalesce(sum(case when e.direction = 'credit' then e.amount else -e.amount end), 0) as fee")->value('fee');
        if ($fee > 0) {
            $add('fees', $fee, 0, 'Platform fees');
            $add('income', 0, $fee, 'Platform fees taken from jobs');
        }

        return $lines;
    }

    /** Send every day not yet sent, up to yesterday. Returns days sent (with money in them). */
    public function run(object $c): int
    {
        if ($c->status !== 'active' || ! self::mapped($c)) {
            return 0;
        }
        $day = CarbonImmutable::parse($c->synced_through ? CarbonImmutable::parse($c->synced_through)->addDay() : $c->sync_from);
        $last = CarbonImmutable::now('Africa/Lagos')->subDay()->startOfDay();
        $sent = 0;
        for ($n = 0; $day->lte($last) && $n < self::MAX_DAYS; $n++, $day = $day->addDay()) {
            $date = $day->toDateString();
            try {
                $sent += $this->sendDay($c, $date) ? 1 : 0;
            } catch (Throwable $e) {
                DB::table('accounting_syncs')->updateOrInsert(['connection_id' => $c->id, 'day' => $date], ['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now(), 'created_at' => now()]);
                DB::table('accounting_connections')->where('id', $c->id)->update(['status' => 'error', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
                Log::warning("Accounting sync for operator {$c->operator_id} on {$date} failed: {$e->getMessage()}");

                return $sent;
            }
            DB::table('accounting_connections')->where('id', $c->id)->update(['synced_through' => $date, 'error' => null, 'updated_at' => now()]);
        }

        return $sent;
    }

    /** Every active link, nightly. */
    public function runAll(): int
    {
        $n = 0;
        foreach (DB::table('accounting_connections')->where('status', 'active')->get() as $c) {
            $n += $this->run($c);
        }

        return $n;
    }

    private function sendDay(object $c, string $date): bool
    {
        $done = DB::table('accounting_syncs')->where('connection_id', $c->id)->where('day', $date)->whereIn('status', ['sent', 'empty'])->exists();
        if ($done) {
            return false;
        }
        $lines = $this->dayLines((int) $c->operator_id, $date);
        if (! $lines) {
            DB::table('accounting_syncs')->updateOrInsert(['connection_id' => $c->id, 'day' => $date], ['status' => 'empty', 'error' => null, 'updated_at' => now(), 'created_at' => now()]);

            return false;
        }
        $map = json_decode($c->accounts, true);
        $lines = array_map(fn ($l) => ['account' => (string) $map[$l['account']]] + $l, $lines);
        $memo = config('app.name')." wallet, {$date}";
        $id = $this->withToken($c, fn (string $token) => $this->gateway($c->service)->journal($c, $token, $date, $memo, $lines));
        DB::table('accounting_syncs')->updateOrInsert(['connection_id' => $c->id, 'day' => $date], [
            'status' => 'sent', 'external_id' => $id, 'total' => array_sum(array_column($lines, 'debit')), 'error' => null, 'updated_at' => now(), 'created_at' => now(),
        ]);

        return true;
    }

    /** Runs $fn with a live access token, refreshing it when it has expired or is refused. */
    private function withToken(object $c, callable $fn)
    {
        $fresh = DB::table('accounting_connections')->where('id', $c->id)->first();
        $token = $fresh->expires_at <= now()->addMinutes(2) ? $this->refresh($fresh) : Crypt::decryptString($fresh->access_token);
        try {
            return $fn($token);
        } catch (TokenExpired) {
            return $fn($this->refresh($fresh));
        }
    }

    private function refresh(object $c): string
    {
        if ($c->refresh_token === '') {
            throw new RuntimeException('The link was disconnected. Connect again.');
        }
        $t = $this->gateway($c->service)->refresh(Crypt::decryptString($c->refresh_token));
        DB::table('accounting_connections')->where('id', $c->id)->update([
            'access_token' => Crypt::encryptString($t['access_token']), 'refresh_token' => Crypt::encryptString($t['refresh_token']),
            'expires_at' => now()->addSeconds($t['expires_in']), 'updated_at' => now(),
        ]);

        return $t['access_token'];
    }
}
