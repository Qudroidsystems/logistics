<?php

namespace App\Services;

use App\Models\BroadsheetRankingSetting;

/**
 * Computes the configurable (UNOFFICIAL) best-student ranking for a broadsheet.
 *
 * It operates purely on the already-assembled $studentRows produced by
 * BroadsheetController, and never reads or writes the four official position
 * columns (Arm Pos Total/Cum, Class Pos Total/Cum). Output is a separate panel
 * + optional "Rank" column only.
 */
class BroadsheetRankingService
{
    /**
     * @param  array  $studentRows   assembled rows (each has cum_ave, total_cum,
     *                               total_term, num_subjects, gpa, subjects[], arm, …)
     * @param  array  $settings      resolved BroadsheetRankingSetting (model)
     * @param  string|null $override one-off "Rank by" measure (null = use default)
     * @param  bool   $isSenior      distinction threshold differs by section
     * @param  array  $compulsoryIds subject ids that must be scored (eligibility)
     */
    public function rank(
        array $studentRows,
        BroadsheetRankingSetting $settings,
        ?string $override,
        bool $isSenior,
        array $compulsoryIds = []
    ): array {
        $measure = $override && array_key_exists($override, BroadsheetRankingSetting::MEASURES)
            ? $override
            : $settings->primary_measure;
        if (!array_key_exists($measure, BroadsheetRankingSetting::MEASURES)) {
            $measure = 'cum_ave';
        }

        $isDefault = ($measure === $settings->primary_measure);
        $distinctionCut = $isSenior ? 75 : 70;
        $coreIds = array_map('intval', (array) ($settings->core_subject_ids ?? []));

        // compute measure + eligibility for every row
        $rows = [];
        foreach ($studentRows as $r) {
            $value = $this->measureValue($r, $measure, $distinctionCut, $coreIds);
            [$eligible, $reason] = $this->checkEligibility($r, $settings, $compulsoryIds);
            $rows[] = [
                'row'       => $r,
                'value'     => $value,
                'eligible'  => $eligible,
                'reason'    => $reason,
            ];
        }

        $eligible = array_values(array_filter($rows, fn ($x) => $x['eligible']));
        $ineligible = array_values(array_filter($rows, fn ($x) => !$x['eligible']));

        // sort eligible by measure desc, then tiebreakers
        $tiebreakers = array_values(array_filter(
            (array) ($settings->tiebreakers ?? []),
            fn ($k) => array_key_exists($k, BroadsheetRankingSetting::MEASURES) && $k !== $measure
        ));
        usort($eligible, function ($a, $b) use ($measure, $tiebreakers, $distinctionCut, $coreIds) {
            $cmp = $b['value'] <=> $a['value'];
            if ($cmp !== 0) return $cmp;
            foreach ($tiebreakers as $tb) {
                $av = $this->measureValue($a['row'], $tb, $distinctionCut, $coreIds);
                $bv = $this->measureValue($b['row'], $tb, $distinctionCut, $coreIds);
                $c = $bv <=> $av;
                if ($c !== 0) return $c;
            }
            // final stable tiebreak: name
            return strcmp(
                ($a['row']['lastname'] ?? '').($a['row']['firstname'] ?? ''),
                ($b['row']['lastname'] ?? '').($b['row']['firstname'] ?? '')
            );
        });

        // dense ranking (ties share a rank)
        $ranked = [];
        $prevVal = null; $prevRank = 0; $i = 0;
        foreach ($eligible as $e) {
            $i++;
            if ($prevVal !== null && $this->samePosition($e, $prevVal, $measure, $tiebreakers, $distinctionCut, $coreIds)) {
                $rank = $prevRank;
            } else {
                $rank = $i; $prevRank = $i;
            }
            $prevVal = $e;
            $ranked[] = [
                'rank'    => $rank,
                'id'      => $e['row']['id'] ?? null,
                'name'    => trim(($e['row']['firstname'] ?? '').' '.($e['row']['lastname'] ?? '')),
                'arm'     => $e['row']['arm'] ?? '',
                'value'   => $e['value'],
                'row'     => $e['row'],
            ];
        }

        $topN = max(1, (int) ($settings->top_n ?: 3));

        return [
            'measure_key'   => $measure,
            'measure_label' => BroadsheetRankingSetting::MEASURES[$measure],
            'is_default'    => $isDefault,
            'is_official'   => false,
            'scope'         => $settings->scope,
            'top_n'         => $topN,
            'rank_map'      => $this->rankMap($ranked),          // id => rank
            'ranked'        => $ranked,
            'best'          => $this->bestByScope($ranked, $settings, $topN),
            'ineligible'    => array_map(fn ($x) => [
                'name'   => trim(($x['row']['firstname'] ?? '').' '.($x['row']['lastname'] ?? '')),
                'arm'    => $x['row']['arm'] ?? '',
                'reason' => $x['reason'],
            ], $ineligible),
            'criteria_text' => $this->criteriaText($settings, $measure),
            'show_rank_column' => (bool) $settings->show_rank_column,
        ];
    }

    // ── measures ────────────────────────────────────────────────────────────────
    protected function measureValue(array $r, string $measure, int $distinctionCut, array $coreIds): float
    {
        $num = max(1, (int) ($r['num_subjects'] ?? 0) ?: 1);
        switch ($measure) {
            case 'term_total': return (float) ($r['total_term'] ?? 0);
            case 'cum_total':  return (float) ($r['total_cum'] ?? 0);
            case 'term_ave':   return round(((float) ($r['total_term'] ?? 0)) / $num, 2);
            case 'gpa':        return (float) ($r['gpa'] ?? $r['cgpa'] ?? 0);
            case 'distinctions': return (float) $this->countDistinctions($r, $distinctionCut);
            case 'core_avg':   return $this->coreAverage($r, $coreIds);
            case 'cum_ave':
            default:           return (float) ($r['cum_ave'] ?? 0);
        }
    }

    protected function countDistinctions(array $r, int $cut): int
    {
        $n = 0;
        foreach (($r['subjects'] ?? []) as $s) {
            $score = (float) ($s['cum_ave'] ?? 0);
            if ($score >= $cut) $n++;
        }
        return $n;
    }

    protected function coreAverage(array $r, array $coreIds): float
    {
        if (empty($coreIds)) return 0.0;
        $vals = [];
        foreach ($coreIds as $sid) {
            $s = $r['subjects'][$sid] ?? null;
            if ($s && ($s['cum_ave'] ?? 0) > 0) $vals[] = (float) $s['cum_ave'];
        }
        return $vals ? round(array_sum($vals) / count($vals), 2) : 0.0;
    }

    // ── eligibility ───────────────────────────────────────────────────────────────
    protected function checkEligibility(array $r, BroadsheetRankingSetting $s, array $compulsoryIds): array
    {
        if ($s->min_subjects && (int) ($r['num_subjects'] ?? 0) < (int) $s->min_subjects) {
            return [false, 'Fewer than '.$s->min_subjects.' subjects'];
        }
        if ($s->min_average !== null && (float) ($r['cum_ave'] ?? 0) < (float) $s->min_average) {
            return [false, 'Average below '.$s->min_average];
        }
        if ($s->exclude_failed) {
            foreach (($r['subjects'] ?? []) as $sub) {
                if ((float) ($sub['cum_ave'] ?? 0) > 0 && (float) ($sub['cum_ave'] ?? 0) < 40) {
                    return [false, 'Has a failed subject'];
                }
            }
        }
        if ($s->require_all_compulsory && !empty($compulsoryIds)) {
            foreach ($compulsoryIds as $cid) {
                $sub = $r['subjects'][$cid] ?? null;
                if (!$sub || (float) ($sub['cum_ave'] ?? 0) <= 0) {
                    return [false, 'Missing a compulsory subject'];
                }
            }
        }
        return [true, null];
    }

    // ── helpers ────────────────────────────────────────────────────────────────
    protected function samePosition(array $e, array $prev, string $measure, array $tiebreakers, int $cut, array $coreIds): bool
    {
        if ($e['value'] !== $prev['value']) return false;
        foreach ($tiebreakers as $tb) {
            if ($this->measureValue($e['row'], $tb, $cut, $coreIds)
                !== $this->measureValue($prev['row'], $tb, $cut, $coreIds)) {
                return false;
            }
        }
        return true;
    }

    protected function rankMap(array $ranked): array
    {
        $m = [];
        foreach ($ranked as $r) {
            if ($r['id'] !== null) $m[(int) $r['id']] = $r['rank'];
        }
        return $m;
    }

    protected function bestByScope(array $ranked, BroadsheetRankingSetting $s, int $topN): array
    {
        if ($s->scope === 'per_arm') {
            $byArm = [];
            foreach ($ranked as $r) {
                $arm = $r['arm'] ?: '—';
                if (count($byArm[$arm] ?? []) < $topN) $byArm[$arm][] = $r;
            }
            return ['type' => 'per_arm', 'groups' => $byArm];
        }
        if ($s->scope === 'per_subject') {
            // handled in the view from subjectStats; here just overall top for the panel
            return ['type' => 'overall', 'list' => array_slice($ranked, 0, $topN)];
        }
        return ['type' => 'overall', 'list' => array_slice($ranked, 0, $topN)];
    }

    protected function criteriaText(BroadsheetRankingSetting $s, string $measure): string
    {
        $parts = ['Ranked by '.BroadsheetRankingSetting::MEASURES[$measure]];
        $tb = array_values(array_filter((array) ($s->tiebreakers ?? []),
            fn ($k) => array_key_exists($k, BroadsheetRankingSetting::MEASURES) && $k !== $measure));
        if ($tb) {
            $parts[] = 'tie-breakers: '.implode(' → ', array_map(fn ($k) => BroadsheetRankingSetting::MEASURES[$k], $tb));
        }
        $elig = [];
        if ($s->min_subjects) $elig[] = 'min '.$s->min_subjects.' subjects';
        if ($s->require_all_compulsory) $elig[] = 'all compulsory scored';
        if ($s->exclude_failed) $elig[] = 'no failed subject';
        if ($s->min_average !== null) $elig[] = 'avg ≥ '.$s->min_average;
        if ($elig) $parts[] = 'eligibility: '.implode(', ', $elig);
        return implode(' · ', $parts);
    }
}
