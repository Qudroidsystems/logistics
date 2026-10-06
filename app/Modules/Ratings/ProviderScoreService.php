<?php

namespace App\Modules\Ratings;

use Illuminate\Support\Facades\DB;

/**
 * Recomputes a provider's public numbers (rating, jobs, completion and dispute rates, response rate), their 0-100 score
 * and their tier. The tier decides search order and the badge customers see, so the rules are plain and stated here.
 */
class ProviderScoreService
{
    /** A provider needs this many ratings before their average counts for the score; until then they get a neutral 0.7. */
    public const MIN_RATINGS = 3;

    public function refresh(int $operatorId): array
    {
        $r = DB::table('ratings')->where(['ratee_type' => 'operator', 'ratee_id' => $operatorId, 'moderation_status' => 'approved'])
            ->selectRaw('COUNT(*) AS n, AVG(score) AS avg')->first();
        $completed = (int) DB::table('agreements')->where('provider_operator_id', $operatorId)->where('status', 'completed')->count();
        $disputed = (int) DB::table('disputes as d')->join('agreements as a', 'a.id', '=', 'd.agreement_id')->where('a.provider_operator_id', $operatorId)->count();
        // Cancellations the provider caused count against completion; the customer's own cancellations do not.
        $providerCancels = (int) DB::table('risk_events')->where(['subject_type' => 'operator', 'subject_id' => $operatorId, 'type' => 'cancel_abuse'])->count();
        $sent = (int) DB::table('request_invitations')->where('operator_id', $operatorId)->where('created_at', '<', now()->subHours(24))->count();
        $answered = (int) DB::table('request_invitations')->where('operator_id', $operatorId)->where('created_at', '<', now()->subHours(24))->whereIn('status', ['countered', 'accepted'])->count();

        $completion = ($completed + $providerCancels) > 0 ? $completed / ($completed + $providerCancels) : null;
        $disputeRate = $completed > 0 ? min(1.0, $disputed / $completed) : null;
        $response = $sent > 0 ? $answered / $sent : null;
        $ratingAvg = (int) $r->n > 0 ? round((float) $r->avg, 2) : null;

        $score = self::score($ratingAvg, (int) $r->n, $completion, $disputeRate, $response);
        $tier = self::tier($completed, $ratingAvg, (int) $r->n, $completion, $disputeRate);

        DB::table('provider_scores')->updateOrInsert(['operator_id' => $operatorId], [
            'completion_rate' => $completion === null ? null : round($completion * 100, 2), 'dispute_rate' => $disputeRate === null ? null : round($disputeRate * 100, 2),
            'response_rate' => $response === null ? null : round($response * 100, 2), 'rating_avg' => $ratingAvg, 'score' => $score, 'computed_at' => now(),
        ]);
        // A provider that is suspended or not yet approved keeps whatever tier staff set.
        $active = DB::table('operators')->where('id', $operatorId)->where('status', 'active')->exists();
        DB::table('provider_profiles')->where('operator_id', $operatorId)->update([
            'rating_avg' => $ratingAvg, 'rating_count' => (int) $r->n, 'jobs_completed' => $completed,
            'completion_rate' => $completion === null ? null : round($completion * 100, 2), 'updated_at' => now(),
        ] + ($active ? ['tier' => $tier] : []));

        return ['score' => $score, 'tier' => $tier, 'rating_avg' => $ratingAvg, 'rating_count' => (int) $r->n, 'jobs_completed' => $completed];
    }

    /** Pure. Rating 40, completion 30, disputes 15, responsiveness 15. Missing data scores a neutral 0.7. */
    public static function score(?float $ratingAvg, int $ratingCount, ?float $completion, ?float $disputeRate, ?float $response): int
    {
        $rating = ($ratingAvg !== null && $ratingCount >= self::MIN_RATINGS) ? $ratingAvg / 5 : 0.7;
        $comp = $completion ?? 0.7;
        $disp = $disputeRate === null ? 0.7 : 1 - $disputeRate;
        $resp = $response ?? 0.7;

        return (int) round(100 * (0.40 * $rating + 0.30 * $comp + 0.15 * $disp + 0.15 * $resp));
    }

    /** Pure. verified is the floor for an approved provider; trusted and preferred are earned and can be lost. */
    public static function tier(int $jobs, ?float $ratingAvg, int $ratingCount, ?float $completion, ?float $disputeRate): string
    {
        $good = $ratingAvg !== null && $ratingCount >= self::MIN_RATINGS;
        $comp = $completion ?? 0.0;
        $disp = $disputeRate ?? 0.0;
        if ($jobs >= 100 && $good && $ratingAvg >= 4.7 && $comp >= 0.95 && $disp <= 0.02) {
            return 'preferred';
        }
        if ($jobs >= 25 && $good && $ratingAvg >= 4.5 && $comp >= 0.90 && $disp <= 0.05) {
            return 'trusted';
        }

        return 'verified';
    }
}
