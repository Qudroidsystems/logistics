<?php

namespace App\Services;

/**
 * Best students for CSS Kabba broadsheets — per arm, per subject, and across
 * any selection of classes/arms. Works only on the assembled $studentRows from
 * BroadsheetController (dynamic assessments, cum_ave grading). Read-only:
 * never writes to the database and never touches official positions.
 *
 * Sits alongside the existing BroadsheetRankingService (which keeps driving
 * the overall best-student panel and the Rank column).
 */
class BestStudentsService
{
    public const MEASURES = [
        'cum_ave'      => 'Cumulative average',
        'term_ave'     => 'Term average',
        'gpa'          => 'GPA',
        'total_cum'    => 'Sum of cumulative averages',
        'total_term'   => 'Term total (sum of subjects)',
        'core_ave'     => 'Core-subjects average',
        'distinctions' => 'Number of A1s',
        'lowest_score' => 'Weakest subject score (consistency)',
        'num_subjects' => 'Number of subjects scored',
    ];

    private const FAIL_GRADES = ['F9', 'F'];
    private const DISTINCTION_GRADES = ['A1', 'A'];

    /**
     * @param array $rows        assembled studentRows
     * @param array $opts        measure, tiebreakers[], top_n, subject_top_n, min_subjects,
     *                           min_average, exclude_failed, compulsory[], core_subject_ids[],
     *                           basis ('cum_ave'|'total'), skip_subjects (bool)
     * @param array $subjectsMap [subject_id => ['subject_name' => ...]]
     */
    public function rank(array $rows, array $opts, array $subjectsMap = []): array
    {
        $measure = isset(self::MEASURES[$opts['measure'] ?? '']) ? $opts['measure'] : 'cum_ave';

        $tiebreakers = array_values(array_unique(array_filter(
            (array) ($opts['tiebreakers'] ?? ['distinctions', 'lowest_score']),
            fn ($m) => $m && $m !== $measure && isset(self::MEASURES[$m])
        )));

        $keys     = array_merge([$measure], $tiebreakers);
        $basis    = ($opts['basis'] ?? 'cum_ave') === 'total' ? 'total' : 'cum_ave';
        $coreIds  = array_map('intval', (array) ($opts['core_subject_ids'] ?? []));
        $topN     = max(1, (int) ($opts['top_n'] ?? 3));
        $subTopN  = max(1, (int) ($opts['subject_top_n'] ?? 3));

        $eligible = [];
        $excluded = [];
        foreach ($rows as $row) {
            $sid    = (int) $row['id'];
            $reason = $this->ineligibleReason($row, $opts, $basis);
            if ($reason !== null) {
                $excluded[$sid] = $reason;
                continue;
            }
            $vals = [];
            foreach ($keys as $k) {
                $vals[] = $this->measureValue($row, $k, $coreIds, $basis);
            }
            if ($vals[0] === null) {
                $excluded[$sid] = 'No value for "' . self::MEASURES[$measure] . '"';
                continue;
            }
            $eligible[] = ['row' => $row, 'vals' => $vals];
        }

        $ranked = $this->competitionRank($eligible);

        $byArm  = [];
        $groups = [];
        foreach ($eligible as $item) {
            $groups[$this->groupLabel($item['row'])][] = $item;
        }
        ksort($groups, SORT_NATURAL);
        foreach ($groups as $label => $items) {
            $byArm[$label] = $this->top($this->competitionRank($items), $topN);
        }

        return [
            'measure'        => $measure,
            'measure_label'  => self::MEASURES[$measure],
            'tiebreakers'    => array_map(fn ($k) => self::MEASURES[$k], $tiebreakers),
            'basis'          => $basis,
            'top_n'          => $topN,
            'subject_top_n'  => $subTopN,
            'overall'        => $this->top($ranked, $topN),
            'by_arm'         => $byArm,
            'by_subject'     => empty($opts['skip_subjects'])
                ? $this->rankSubjects($rows, $subjectsMap, $basis, $subTopN)
                : [],
            'eligible_count' => count($eligible),
            'excluded'       => $excluded,
        ];
    }

    // =========================================================================
    // Subjects (no eligibility filter — best in the subject is best in the subject)
    // =========================================================================

    private function rankSubjects(array $rows, array $subjectsMap, string $basis, int $topN): array
    {
        $subjectIds = array_keys($subjectsMap);
        if (empty($subjectIds)) {
            foreach ($rows as $row) {
                $subjectIds = array_merge($subjectIds, array_keys($row['subjects'] ?? []));
            }
            $subjectIds = array_unique($subjectIds);
        }

        $out = [];
        foreach ($subjectIds as $subId) {
            $items = [];
            foreach ($rows as $row) {
                $sd = $row['subjects'][$subId] ?? null;
                if (!$sd || !$this->isSat($sd)) continue;
                $items[] = [
                    'row'   => $row,
                    'vals'  => [(float) ($sd[$basis] ?? 0), (float) ($sd['total'] ?? 0)],
                    'grade' => $this->basisGrade($sd, $basis),
                ];
            }
            if (empty($items)) continue;

            $out[(int) $subId] = [
                'subject' => $subjectsMap[$subId]['subject_name'] ?? ('Subject #' . $subId),
                'count'   => count($items),
                'top'     => $this->top($this->competitionRank($items), $topN),
            ];
        }

        uasort($out, fn ($a, $b) => strcasecmp($a['subject'], $b['subject']));
        return $out;
    }

    // =========================================================================
    // Measures & eligibility
    // =========================================================================

    private function measureValue(array $row, string $key, array $coreIds, string $basis): ?float
    {
        $sat = $this->satScores($row, $basis);
        if (empty($sat) && $key !== 'num_subjects') return null;

        switch ($key) {
            case 'cum_ave':      return (float) ($row['cum_ave'] ?? 0);
            case 'gpa':          return (float) ($row['gpa'] ?? 0);
            case 'total_cum':    return (float) ($row['total_cum'] ?? 0);
            case 'total_term':   return (float) ($row['total_term'] ?? 0);
            case 'num_subjects': return (float) count($sat);
            case 'lowest_score': return (float) min($sat);

            case 'term_ave':
                $totals = $this->satScores($row, 'total');
                return empty($totals) ? null : round(array_sum($totals) / count($totals), 2);

            case 'distinctions':
                $n = 0;
                foreach ($row['subjects'] ?? [] as $sd) {
                    if ($this->isSat($sd) && in_array($this->basisGrade($sd, $basis), self::DISTINCTION_GRADES, true)) $n++;
                }
                return (float) $n;

            case 'core_ave':
                $core = array_intersect_key($sat, array_flip($coreIds));
                return empty($core) ? null : round(array_sum($core) / count($core), 2);
        }
        return null;
    }

    private function ineligibleReason(array $row, array $opts, string $basis): ?string
    {
        $sat = $this->satScores($row, $basis);
        $n   = count($sat);
        if ($n === 0) return 'No scores recorded';

        $min = (int) ($opts['min_subjects'] ?? 0);
        if ($min > 0 && $n < $min) return "Only {$n} subject(s) scored (minimum {$min})";

        $minAvg = $opts['min_average'] ?? null;
        if ($minAvg !== null && $minAvg !== '') {
            $avg = array_sum($sat) / $n;
            if ($avg < (float) $minAvg) {
                return 'Average ' . number_format($avg, 1) . ' is below ' . number_format((float) $minAvg, 1);
            }
        }

        if (!empty($opts['exclude_failed'])) {
            foreach ($row['subjects'] ?? [] as $sd) {
                if (!$this->isSat($sd)) continue;
                if ((float) ($sd[$basis] ?? 0) < 40 || in_array($this->basisGrade($sd, $basis), self::FAIL_GRADES, true)) {
                    return 'Has a failed subject';
                }
            }
        }

        $compulsory = array_map('intval', (array) ($opts['compulsory'] ?? []));
        if (!empty($compulsory)) {
            $missing = array_diff($compulsory, array_keys($sat));
            if (!empty($missing)) return count($missing) . ' compulsory subject(s) not scored';
        }

        return null;
    }

    private function satScores(array $row, string $basis): array
    {
        $out = [];
        foreach ($row['subjects'] ?? [] as $subId => $sd) {
            if ($this->isSat($sd)) $out[(int) $subId] = (float) ($sd[$basis] ?? 0);
        }
        return $out;
    }

    private function isSat(array $sd): bool
    {
        return (float) ($sd['total'] ?? 0) > 0 || (float) ($sd['cum'] ?? 0) > 0;
    }

    private function basisGrade(array $sd, string $basis): ?string
    {
        $g = $basis === 'total' ? ($sd['total_grade'] ?? null) : ($sd['cum_grade'] ?? null);
        return $g ?: ($sd['grade'] ?? null);
    }

    // =========================================================================
    // Ranking helpers
    // =========================================================================

    /** Competition rank (1, 2, 2, 4) on vals[] descending. */
    private function competitionRank(array $items): array
    {
        usort($items, function ($a, $b) {
            foreach ($a['vals'] as $i => $v) {
                $cmp = ($b['vals'][$i] ?? -INF) <=> ($v ?? -INF);
                if ($cmp !== 0) return $cmp;
            }
            return strcasecmp($this->fullName($a['row']), $this->fullName($b['row']));
        });

        $prevSig = null;
        $rank    = 0;
        foreach ($items as $i => &$item) {
            $sig = json_encode(array_map(fn ($v) => $v === null ? null : round($v, 4), $item['vals']));
            if ($sig !== $prevSig) {
                $rank    = $i + 1;
                $prevSig = $sig;
            }
            $item['rank'] = $rank;
        }
        unset($item);
        return $items;
    }

    /** Everyone ranked within top N (ties included). */
    private function top(array $ranked, int $n): array
    {
        $out = [];
        foreach ($ranked as $item) {
            if ($item['rank'] > $n) break;
            $row   = $item['row'];
            $out[] = [
                'id'          => (int) $row['id'],
                'name'        => $this->fullName($row),
                'admissionno' => $row['admissionno'] ?? '',
                'arm'         => $this->groupLabel($row),
                'rank'        => $item['rank'],
                'value'       => round((float) $item['vals'][0], 2),
                'grade'       => $item['grade'] ?? null,
                // extra context for dashboards / reports
                'firstname'     => $row['firstname'] ?? '',
                'lastname'      => $row['lastname'] ?? '',
                'class_name'    => $row['class_name'] ?? '',
                'arm_name'      => $row['arm'] ?? '',
                'schoolclassid' => (int) ($row['schoolclassid'] ?? 0),
                'picture'       => $row['picture'] ?? null,
                'cum_ave'       => (float) ($row['cum_ave'] ?? 0),
                'total_cum'     => (float) ($row['total_cum'] ?? 0),
                'num_subjects'  => (int) ($row['num_subjects'] ?? 0),
                'gpa'           => (float) ($row['gpa'] ?? 0),
            ];
        }
        return $out;
    }

    private function groupLabel(array $row): string
    {
        $label = trim((string) ($row['class_label'] ?? ''));
        if ($label === '') $label = trim((string) ($row['arm'] ?? ''));
        return $label !== '' ? $label : '—';
    }

    private function fullName(array $row): string
    {
        return trim(strtoupper($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? ''), ', ');
    }
}
