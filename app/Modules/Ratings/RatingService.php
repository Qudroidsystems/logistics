<?php

namespace App\Modules\Ratings;

use App\Modules\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * After a delivery both sides can rate each other once: the customer rates the provider (public, shown in search and
 * on the provider's page), the provider rates the customer (private, used only for risk and dispatch decisions).
 */
class RatingService
{
    /** Ratings can be left this long after delivery. */
    public const WINDOW_DAYS = 14;

    public const TAGS = ['on_time', 'careful', 'polite', 'good_communication', 'fair_price', 'late', 'rude', 'damaged_goods', 'poor_communication', 'wrong_item'];

    public function __construct(private ProviderScoreService $scores, private NotificationService $notify)
    {
    }

    /**
     * @param 'customer'|'provider' $side which side is rating
     * @return int the new rating id
     */
    public function rate(int $shipmentId, int $userId, string $side, int $score, array $tags = [], ?string $comment = null): int
    {
        if ($score < 1 || $score > 5) {
            throw new RuntimeException('A rating is between 1 and 5.');
        }
        $tags = array_values(array_intersect(array_unique($tags), self::TAGS));
        if (count($tags) > 5) {
            throw new RuntimeException('Pick up to 5 tags.');
        }

        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->join('agreements as a', 'a.id', '=', 'o.agreement_id')
            ->where('s.id', $shipmentId)->first(['s.id', 's.delivered_at', 'o.order_number', 'a.id as agreement_id', 'a.customer_id', 'a.provider_operator_id', 'a.status as agreement_status']);
        if (! $s || ! $s->delivered_at || ! in_array($s->agreement_status, ['delivered', 'completed'], true)) {
            throw new RuntimeException('You can rate once the delivery is done.');
        }
        if (now()->diffInDays($s->delivered_at, true) > self::WINDOW_DAYS) {
            throw new RuntimeException('The time to rate this delivery has passed.');
        }

        if ($side === 'customer') {
            if ((int) $s->customer_id !== $userId) {
                throw new RuntimeException('This is not your delivery.');
            }
            $row = ['rater_type' => 'customer', 'rater_id' => $userId, 'ratee_type' => 'operator', 'ratee_id' => (int) $s->provider_operator_id, 'is_public' => true];
        } else {
            $member = DB::table('operator_members')->where(['operator_id' => $s->provider_operator_id, 'user_id' => $userId, 'status' => 'active'])->whereIn('role', ['owner', 'admin', 'dispatcher'])->exists();
            if (! $member) {
                throw new RuntimeException('You cannot rate this delivery.');
            }
            $row = ['rater_type' => 'operator', 'rater_id' => (int) $s->provider_operator_id, 'ratee_type' => 'customer', 'ratee_id' => (int) $s->customer_id, 'is_public' => false];
        }

        $id = DB::transaction(function () use ($s, $row, $score, $tags, $comment, $shipmentId) {
            $exists = DB::table('ratings')->where(['shipment_id' => $shipmentId, 'rater_type' => $row['rater_type'], 'rater_id' => $row['rater_id'], 'ratee_type' => $row['ratee_type'], 'ratee_id' => $row['ratee_id']])->exists();
            if ($exists) {
                throw new RuntimeException('You have already rated this delivery.');
            }

            return DB::table('ratings')->insertGetId($row + [
                'shipment_id' => $shipmentId, 'agreement_id' => $s->agreement_id, 'score' => $score, 'tags' => json_encode($tags),
                'comment' => $comment ? mb_substr(trim($comment), 0, 500) : null, 'moderation_status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $this->afterRating($row, $s->order_number, $score);

        return $id;
    }

    /** Best effort: the rating is saved even if the score refresh or the notification fails. */
    private function afterRating(array $row, string $order, int $score): void
    {
        try {
            DB::transaction(function () use ($row, $order, $score) {
                if ($row['ratee_type'] === 'operator') {
                    $this->scores->refresh($row['ratee_id']);
                    $this->notify->notifyOperator($row['ratee_id'], 'rating.received', ['score' => $score, 'order' => $order]);
                } else {
                    $r = DB::table('ratings')->where(['ratee_type' => 'customer', 'ratee_id' => $row['ratee_id'], 'moderation_status' => 'approved'])->selectRaw('COUNT(*) n, AVG(score) a')->first();
                    DB::table('customer_profiles')->where('user_id', $row['ratee_id'])->update(['rating_avg' => round((float) $r->a, 2), 'rating_count' => (int) $r->n, 'updated_at' => now()]);
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Rating follow-up failed: {$e->getMessage()}");
        }
    }

    /** Public reviews for a provider's page. Ratings staff removed are excluded. */
    public function publicReviews(int $operatorId, int $limit = 20)
    {
        return DB::table('ratings as r')->leftJoin('users as u', function ($j) {
            $j->on('u.id', '=', 'r.rater_id');
        })->where(['r.ratee_type' => 'operator', 'r.ratee_id' => $operatorId, 'r.is_public' => true, 'r.moderation_status' => 'approved'])
            ->orderByDesc('r.id')->limit($limit)
            ->get(['r.score', 'r.tags', 'r.comment', 'r.created_at', 'u.name as reviewer'])
            ->map(function ($x) {
                // First name only: reviews are public.
                $x->reviewer = $x->reviewer ? explode(' ', trim($x->reviewer))[0] : 'Customer';
                $x->tags = json_decode($x->tags ?? '[]', true);

                return $x;
            });
    }
}
