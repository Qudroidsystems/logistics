<?php

namespace App\Modules\Marketing;

use App\Modules\Notifications\NotificationService;
use App\Modules\Notifications\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asking customers for a Google review after a finished delivery. Each provider sets its own Google review link.
 * Every customer is asked, whatever they thought of the job (Google does not allow asking only happy customers),
 * at most once in REPEAT_DAYS per provider. Off until the provider switches it on.
 */
class ReviewRequestService
{
    public const KEY = 'reviews.google';

    public const DEFAULTS = ['enabled' => false, 'url' => '', 'delay_hours' => 2, 'sms' => false];

    public const REPEAT_DAYS = 90;

    public function __construct(private NotificationService $notify) {}

    public static function settings(int $operatorId): array
    {
        $v = DB::table('platform_settings')->where('operator_id', $operatorId)->where('scope', 'operator')->where('key', self::KEY)->value('value');

        return array_merge(self::DEFAULTS, $v ? (array) json_decode($v, true) : []);
    }

    public static function save(int $operatorId, array $s, int $userId): void
    {
        $where = ['operator_id' => $operatorId, 'scope' => 'operator', 'scope_id' => $operatorId, 'key' => self::KEY];
        $exists = DB::table('platform_settings')->where($where)->exists();
        DB::table('platform_settings')->updateOrInsert($where, ['value' => json_encode(array_intersect_key($s, self::DEFAULTS) + self::settings($operatorId)), 'updated_by' => $userId, 'updated_at' => now()] + ($exists ? [] : ['created_at' => now()]));
    }

    /** A job paid out: queue the request for later. Best effort, never stops the payout. */
    public function queueFor(int $agreementId): void
    {
        try {
            $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('o.agreement_id', $agreementId)
                ->first(['s.id', 's.operator_id', 'o.customer_id', 'o.merchant_id', 's.is_test']);
            if (! $s || $s->merchant_id || $s->is_test || ! $s->operator_id) {
                return;
            }
            $cfg = self::settings((int) $s->operator_id);
            if (! $cfg['enabled'] || ! $cfg['url']) {
                return;
            }
            DB::table('review_requests')->insertOrIgnore([
                'operator_id' => $s->operator_id, 'customer_id' => $s->customer_id, 'shipment_id' => $s->id,
                'send_after' => now()->addHours((int) $cfg['delay_hours']), 'status' => 'waiting', 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning("Review request for agreement {$agreementId} failed: {$e->getMessage()}");
        }
    }

    /** Send requests that are due. Run every ten minutes. */
    public function run(): int
    {
        $sent = 0;
        $due = DB::table('review_requests')->where('status', 'waiting')->where('send_after', '<=', now())->orderBy('id')->limit(500)->get();
        foreach ($due as $r) {
            $cfg = self::settings((int) $r->operator_id);
            $recent = DB::table('review_requests')->where('operator_id', $r->operator_id)->where('customer_id', $r->customer_id)
                ->where('status', 'sent')->where('sent_at', '>=', now()->subDays(self::REPEAT_DAYS))->exists();
            $skip = ! $cfg['enabled'] || ! $cfg['url'] ? 'Review requests were switched off' : ($recent ? 'Asked recently' : null);
            if ($skip) {
                DB::table('review_requests')->where('id', $r->id)->update(['status' => 'skipped', 'note' => $skip, 'updated_at' => now()]);

                continue;
            }
            $provider = (string) DB::table('operators')->where('id', $r->operator_id)->value('display_name');
            $vars = ['provider' => $provider, 'link' => $cfg['url']];
            $this->notify->notify((int) $r->customer_id, 'review.request', $vars, $cfg['url']);
            if ($cfg['sms']) {
                app(SmsService::class)->toUser((int) $r->customer_id, "Thanks for using {$provider}. Would you leave us a quick Google review? {$cfg['url']}", 'review.request');
            }
            DB::table('review_requests')->where('id', $r->id)->update(['status' => 'sent', 'sent_at' => now(), 'updated_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    public function stats(int $operatorId): object
    {
        return DB::table('review_requests')->where('operator_id', $operatorId)
            ->selectRaw("count(*) filter (where status = 'sent') as sent, count(*) filter (where status = 'waiting') as waiting, count(*) filter (where status = 'skipped') as skipped")->first();
    }
}
