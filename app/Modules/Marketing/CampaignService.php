<?php

namespace App\Modules\Marketing;

use App\Jobs\SendPlainEmail;
use App\Modules\Notifications\Sms\SmsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Email and SMS campaigns. Only customers who said yes to offers (customer_profiles.marketing_opt_in) receive them,
 * and every message says how to stop. The platform writes to all of them; a provider writes only to customers who
 * have used it. Sending runs in batches from `campaigns:send`, so a big list never holds up a page.
 */
class CampaignService
{
    public const AUDIENCES = [
        'all' => 'Everyone who agreed to offers',
        'recent' => 'Booked in the last 30 days',
        'lapsed' => 'Booked before, but not in the last 60 days',
        'new' => 'Signed up but never booked',
    ];

    /** Recipients sent per run of the command. */
    public const BATCH = 300;

    /** One SMS page; longer messages cost more pages. */
    public const SMS_PAGE = 160;

    /** Customers a campaign would reach now. */
    public function audience(?int $operatorId, string $audience, string $channel): Builder
    {
        $paid = fn ($q) => $q->select(DB::raw(1))->from('orders as o')->whereColumn('o.customer_id', 'u.id')->where('o.payment_status', 'paid')
            ->when($operatorId, fn ($q) => $q->where('o.operator_id', $operatorId));

        return DB::table('users as u')->join('customer_profiles as cp', 'cp.user_id', '=', 'u.id')
            ->where('cp.marketing_opt_in', true)->where('u.is_disabled', false)->where('u.status', 'active')
            ->when($channel === 'email', fn ($q) => $q->whereNotNull('u.email')->where('u.email', 'not like', '%@phone.invalid'))
            ->when($channel === 'sms', fn ($q) => $q->whereNotNull('u.phone_number'))
            // A provider reaches only its own customers.
            ->when($operatorId, fn ($q) => $q->whereExists($paid))
            ->when($audience === 'recent', fn ($q) => $q->whereExists(fn ($q) => $paid($q)->where('o.created_at', '>=', now()->subDays(30))))
            ->when($audience === 'lapsed', fn ($q) => $q->whereExists($paid)->whereNotExists(fn ($q) => $paid($q)->where('o.created_at', '>=', now()->subDays(60))))
            ->when($audience === 'new', fn ($q) => $q->whereNotExists($paid));
    }

    public function count(?int $operatorId, string $audience, string $channel): int
    {
        return $this->audience($operatorId, $audience, $channel)->count();
    }

    public function save(?int $operatorId, array $d, int $userId, ?int $id = null): int
    {
        if (! isset(self::AUDIENCES[$d['audience']])) {
            throw new RuntimeException('Choose who the campaign goes to.');
        }
        if ($d['channel'] === 'email' && trim((string) ($d['subject'] ?? '')) === '') {
            throw new RuntimeException('An email needs a subject.');
        }
        $promo = null;
        if (! empty($d['promo_code'])) {
            $promo = DB::table('promo_codes')->where('code', strtoupper(trim($d['promo_code'])))->where('active', true)
                ->when($operatorId, fn ($q) => $q->where('operator_id', $operatorId))->value('id');
            if (! $promo) {
                throw new RuntimeException($operatorId ? 'That is not one of your live promo codes.' : 'That promo code does not exist or is paused.');
            }
        }
        $row = [
            'name' => mb_substr(trim($d['name']), 0, 120), 'channel' => $d['channel'], 'subject' => $d['channel'] === 'email' ? mb_substr(trim((string) $d['subject']), 0, 150) : null,
            'body' => trim($d['body']), 'audience' => $d['audience'], 'promo_code_id' => $promo, 'updated_at' => now(),
        ];
        if ($id) {
            $c = $this->find($operatorId, $id);
            if ($c->status !== 'draft') {
                throw new RuntimeException('Only a draft can be changed.');
            }
            DB::table('marketing_campaigns')->where('id', $id)->update($row);

            return $id;
        }

        return (int) DB::table('marketing_campaigns')->insertGetId($row + [
            'public_id' => (string) Str::ulid(), 'operator_id' => $operatorId, 'status' => 'draft', 'created_by' => $userId, 'created_at' => now(),
        ]);
    }

    /** Send now, or at a later time. */
    public function schedule(?int $operatorId, int $id, ?string $at): void
    {
        $c = $this->find($operatorId, $id);
        if ($c->status !== 'draft') {
            throw new RuntimeException('This campaign has already been scheduled or sent.');
        }
        if ($this->count($operatorId, $c->audience, $c->channel) === 0) {
            throw new RuntimeException('Nobody in this audience has agreed to receive offers yet.');
        }
        DB::table('marketing_campaigns')->where('id', $id)->update(['status' => 'scheduled', 'scheduled_at' => $at ?: now(), 'updated_at' => now()]);
    }

    /** Stop a campaign that has not finished. Messages already sent stay sent. */
    public function cancel(?int $operatorId, int $id): void
    {
        $c = $this->find($operatorId, $id);
        if (! in_array($c->status, ['draft', 'scheduled', 'sending'], true)) {
            throw new RuntimeException('This campaign has finished.');
        }
        DB::table('marketing_campaigns')->where('id', $id)->update(['status' => 'cancelled', 'finished_at' => now(), 'updated_at' => now()]);
        DB::table('campaign_recipients')->where('campaign_id', $id)->where('status', 'queued')->delete();
    }

    /** Send the message to the person writing it, to see how it looks. */
    public function test(?int $operatorId, int $id, object $user): string
    {
        $c = $this->find($operatorId, $id);
        $to = $c->channel === 'email' ? $user->email : $user->phone_number;
        if (! $to || str_ends_with((string) $to, '@phone.invalid')) {
            throw new RuntimeException($c->channel === 'email' ? 'Your account has no email address.' : 'Your account has no phone number.');
        }
        $this->deliver($c, $user, $to, true);

        return $to;
    }

    /** Called every minute: start campaigns that are due and send the next batch of each. Returns messages sent. */
    public function run(): int
    {
        foreach (DB::table('marketing_campaigns')->where('status', 'scheduled')->where('scheduled_at', '<=', now())->pluck('id') as $id) {
            $this->start((int) $id);
        }
        $sent = 0;
        foreach (DB::table('marketing_campaigns')->where('status', 'sending')->get() as $c) {
            $sent += $this->batch($c);
        }

        return $sent;
    }

    /** The recipient list is fixed when sending starts, so later sign-ups don't join halfway. */
    private function start(int $id): void
    {
        DB::transaction(function () use ($id) {
            $c = DB::table('marketing_campaigns')->where('id', $id)->lockForUpdate()->first();
            if ($c->status !== 'scheduled') {
                return;
            }
            $dest = $c->channel === 'email' ? 'u.email' : 'u.phone_number';
            $this->audience($c->operator_id ? (int) $c->operator_id : null, $c->audience, $c->channel)->orderBy('u.id')
                ->select('u.id', DB::raw("{$dest} as destination"))
                ->chunk(1000, function ($rows) use ($id) {
                    DB::table('campaign_recipients')->insertOrIgnore($rows->map(fn ($r) => ['campaign_id' => $id, 'user_id' => $r->id, 'destination' => $r->destination])->all());
                });
            $n = DB::table('campaign_recipients')->where('campaign_id', $id)->count();
            DB::table('marketing_campaigns')->where('id', $id)->update(['status' => 'sending', 'started_at' => now(), 'recipients' => $n, 'updated_at' => now()]);
        });
    }

    private function batch(object $c): int
    {
        $rows = DB::table('campaign_recipients as r')->join('users as u', 'u.id', '=', 'r.user_id')->where('r.campaign_id', $c->id)->where('r.status', 'queued')
            ->orderBy('r.id')->limit(self::BATCH)->get(['r.id', 'r.destination', 'u.id as user_id', 'u.name', 'u.email', 'u.phone_number']);
        $sent = 0;
        foreach ($rows as $r) {
            // Someone who said stop after the list was made is skipped.
            $still = DB::table('customer_profiles')->where('user_id', $r->user_id)->value('marketing_opt_in');
            $ok = $still && $this->deliver($c, (object) ['id' => $r->user_id, 'name' => $r->name, 'email' => $r->email, 'phone_number' => $r->phone_number], $r->destination);
            DB::table('campaign_recipients')->where('id', $r->id)->update(['status' => $ok ? 'sent' : 'failed', 'sent_at' => $ok ? now() : null]);
            $sent += $ok ? 1 : 0;
        }
        $counts = DB::table('campaign_recipients')->where('campaign_id', $c->id)
            ->selectRaw("count(*) filter (where status = 'sent') as sent, count(*) filter (where status = 'failed') as failed, count(*) filter (where status = 'queued') as queued")->first();
        DB::table('marketing_campaigns')->where('id', $c->id)->update([
            'sent' => $counts->sent, 'failed' => $counts->failed, 'updated_at' => now(),
        ] + ((int) $counts->queued === 0 ? ['status' => 'sent', 'finished_at' => now()] : []));

        return $sent;
    }

    private function deliver(object $c, object $user, string $to, bool $test = false): bool
    {
        $text = $this->render($c, $user);
        if ($c->channel === 'sms') {
            return app(SmsService::class)->toNumber($to, $text, $test ? null : (int) $user->id, 'campaign');
        }
        SendPlainEmail::dispatch($to, ($test ? '[Test] ' : '').$c->subject, $text);

        return true;
    }

    /** {first_name} and {code} are filled in; the way to stop is always added. */
    public function render(object $c, object $user): string
    {
        $first = (string) Str::of((string) ($user->name ?? ''))->trim()->explode(' ')->first();
        $code = $c->promo_code_id ? (string) DB::table('promo_codes')->where('id', $c->promo_code_id)->value('code') : '';
        $body = strtr($c->body, ['{first_name}' => $first ?: 'there', '{name}' => $first ?: 'there', '{code}' => $code]);
        $from = $c->operator_id ? (string) DB::table('operators')->where('id', $c->operator_id)->value('display_name') : config('app.name');

        return $c->channel === 'sms'
            ? "{$from}: {$body} Stop offers in the app settings."
            : $body."\n\nYou get these emails because you agreed to offers from {$from}. Stop them: ".self::unsubscribeUrl((int) $user->id);
    }

    public static function unsubscribeUrl(int $userId): string
    {
        return URL::signedRoute('marketing.unsubscribe', ['user' => $userId]);
    }

    public static function smsPages(string $body): int
    {
        return max(1, (int) ceil(mb_strlen($body) / self::SMS_PAGE));
    }

    /** Yes or no to offers, from the app, the account page or an unsubscribe link. */
    public static function setOptIn(int $userId, bool $yes): void
    {
        $row = ['marketing_opt_in' => $yes, 'marketing_changed_at' => now(), 'updated_at' => now()];
        DB::table('customer_profiles')->where('user_id', $userId)->exists()
            ? DB::table('customer_profiles')->where('user_id', $userId)->update($row)
            : DB::table('customer_profiles')->insert($row + ['public_id' => (string) Str::ulid(), 'user_id' => $userId, 'created_at' => now()]);
    }

    public function list(?int $operatorId): array
    {
        return DB::table('marketing_campaigns as c')->leftJoin('promo_codes as p', 'p.id', '=', 'c.promo_code_id')
            ->when($operatorId, fn ($q) => $q->where('c.operator_id', $operatorId), fn ($q) => $q->whereNull('c.operator_id'))
            ->orderByDesc('c.id')->limit(100)->get(['c.*', 'p.code as promo_code'])->all();
    }

    public function find(?int $operatorId, int $id): object
    {
        $c = DB::table('marketing_campaigns')->where('id', $id)
            ->when($operatorId, fn ($q) => $q->where('operator_id', $operatorId), fn ($q) => $q->whereNull('operator_id'))->first();
        if (! $c) {
            throw new RuntimeException('Campaign not found.');
        }

        return $c;
    }
}
