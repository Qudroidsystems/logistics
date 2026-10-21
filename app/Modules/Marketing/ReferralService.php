<?php

namespace App\Modules\Marketing;

use App\Modules\Notifications\NotificationService;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Invite a friend. The friend signs up with the customer's code and gets money off their first delivery; once that
 * delivery is finished and paid out, the customer who invited them gets a wallet credit. The platform pays for both
 * (promo_expense). Off until staff switch it on and set the amounts on the Promo codes page.
 */
class ReferralService
{
    public const KEY = 'referral.settings';

    public const DEFAULTS = [
        'enabled' => false,
        // kobo
        'reward' => 50_000, 'friend_discount' => 50_000, 'min_price' => 100_000,
        // Credits one customer can earn in a calendar month.
        'monthly_cap' => 20,
    ];

    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts, private NotificationService $notify) {}

    public static function settings(): array
    {
        $v = DB::table('platform_settings')->whereNull('operator_id')->where('scope', 'global')->where('key', self::KEY)->value('value');

        return array_merge(self::DEFAULTS, $v ? (array) json_decode($v, true) : []);
    }

    public static function save(array $s, int $userId): void
    {
        DB::table('platform_settings')->updateOrInsert(
            ['operator_id' => null, 'scope' => 'global', 'scope_id' => null, 'key' => self::KEY],
            ['value' => json_encode(array_intersect_key($s, self::DEFAULTS) + self::settings()), 'updated_by' => $userId, 'updated_at' => now()]
        );
    }

    /** At sign-up: link the new customer to whoever invited them. Returns false when the code is unknown or off. */
    public function attach(int $friendId, ?string $code): bool
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '' || ! self::settings()['enabled']) {
            return false;
        }
        $referrer = DB::table('customer_profiles')->where('referral_code', $code)->value('user_id');
        if (! $referrer || (int) $referrer === $friendId || DB::table('referrals')->where('friend_id', $friendId)->exists()) {
            return false;
        }
        DB::table('customer_profiles')->where('user_id', $friendId)->whereNull('referred_by')->update(['referred_by' => $referrer, 'updated_at' => now()]);
        DB::table('referrals')->insert(['referrer_id' => $referrer, 'friend_id' => $friendId, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        return true;
    }

    /** Money off an invited customer's first delivery; 0 once they have paid for one, or when referrals are off. */
    public function friendDiscount(int $customerId, int $price): int
    {
        $s = self::settings();
        if (! $s['enabled'] || $price <= 0 || ! DB::table('referrals')->where('friend_id', $customerId)->where('status', 'pending')->exists()) {
            return 0;
        }
        if (DB::table('orders')->where('customer_id', $customerId)->where('payment_status', 'paid')->exists()) {
            return 0;
        }

        return min((int) $s['friend_discount'], $price);
    }

    /**
     * Called when a job's money is paid out. If it was an invited customer's first finished delivery, credit the
     * customer who invited them. Best effort: a failure here never stops the payout.
     */
    public function rewardFor(int $agreementId): void
    {
        try {
            DB::transaction(function () use ($agreementId) {
                $a = DB::table('agreements')->where('id', $agreementId)->first(['id', 'customer_id', 'price', 'number', 'is_test']);
                $r = $a ? DB::table('referrals')->where('friend_id', $a->customer_id)->where('status', 'pending')->lockForUpdate()->first() : null;
                if (! $r || $a->is_test) {
                    return;
                }
                $s = self::settings();
                if ((int) $a->price < (int) $s['min_price']) {
                    return; // a small first job does not count; the next one might
                }
                $month = DB::table('referrals')->where('referrer_id', $r->referrer_id)->where('status', 'rewarded')
                    ->where('rewarded_at', '>=', now()->startOfMonth())->count();
                if (! $s['enabled'] || $month >= (int) $s['monthly_cap'] || (int) $s['reward'] <= 0) {
                    DB::table('referrals')->where('id', $r->id)->update([
                        'status' => 'void', 'agreement_id' => $a->id, 'updated_at' => now(),
                        'note' => $s['enabled'] ? 'Monthly limit reached' : 'Referrals were off', ]);

                    return;
                }
                $reward = (int) $s['reward'];
                $this->ledger->post("referral-reward-{$r->id}", [
                    'operator_id' => 1, 'kind' => 'referral_reward', 'reference_type' => 'referral', 'reference_id' => $r->id,
                    'description' => "Referral credit for agreement {$a->number}",
                ], [
                    ['account_id' => $this->accounts->platform('promo_expense'), 'direction' => 'debit', 'amount' => $reward],
                    ['account_id' => $this->accounts->wallet('customer', (int) $r->referrer_id, 1), 'direction' => 'credit', 'amount' => $reward],
                ]);
                DB::table('referrals')->where('id', $r->id)->update(['status' => 'rewarded', 'reward' => $reward, 'agreement_id' => $a->id, 'rewarded_at' => now(), 'updated_at' => now()]);
                $friend = (string) Str::of((string) DB::table('users')->where('id', $a->customer_id)->value('name'))->trim()->explode(' ')->first();
                $this->notify->notify((int) $r->referrer_id, 'referral.rewarded', ['amount' => \App\Support\Money::symbol().number_format($reward / 100), 'friend' => $friend ?: 'Your friend'], '/wallet');
            });
        } catch (Throwable $e) {
            Log::warning("Referral reward for agreement {$agreementId} failed: {$e->getMessage()}");
        }
    }

    /** The customer's invite screen: their code, what friends get, and how their invites went. */
    public function forCustomer(int $userId): array
    {
        $s = self::settings();
        $code = DB::table('customer_profiles')->where('user_id', $userId)->value('referral_code');
        if (! $code) {
            // Customers added by staff or before invites existed get their code the first time they look.
            $code = strtoupper(Str::random(8));
            DB::table('customer_profiles')->where('user_id', $userId)->exists()
                ? DB::table('customer_profiles')->where('user_id', $userId)->update(['referral_code' => $code, 'updated_at' => now()])
                : DB::table('customer_profiles')->insert(['public_id' => (string) Str::ulid(), 'user_id' => $userId, 'referral_code' => $code, 'created_at' => now(), 'updated_at' => now()]);
        }
        $rows = DB::table('referrals as r')->join('users as u', 'u.id', '=', 'r.friend_id')->where('r.referrer_id', $userId)
            ->orderByDesc('r.id')->limit(50)->get(['u.name', 'r.status', 'r.reward', 'r.created_at']);
        $naira = fn ($k) => \App\Support\Money::symbol().number_format(((int) $k) / 100);

        return [
            'enabled' => (bool) $s['enabled'], 'code' => $code,
            'reward' => (int) $s['reward'], 'friend_discount' => (int) $s['friend_discount'],
            'how' => $s['enabled']
                ? "Your friend gets {$naira($s['friend_discount'])} off their first delivery. You get {$naira($s['reward'])} in your wallet when it is done."
                : 'Invites are not running right now.',
            'share' => $s['enabled'] && $code ? "Use my code {$code} when you sign up and get {$naira($s['friend_discount'])} off your first delivery." : null,
            'earned' => (int) $rows->where('status', 'rewarded')->sum('reward'),
            'friends' => $rows->map(fn ($r) => [
                'name' => (string) Str::of((string) $r->name)->trim()->explode(' ')->first(), 'status' => $r->status,
                'label' => match ($r->status) { 'rewarded' => 'You earned '.$naira($r->reward), 'void' => 'No credit', default => 'Waiting for their first delivery' },
                'joined' => $r->created_at,
            ])->all(),
        ];
    }
}
