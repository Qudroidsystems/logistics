<?php

namespace App\Services;

use App\Models\Schoolclass;

/**
 * CSS Kabba — Best Students Explorer.
 *
 * Builds, for any selection of classes (all arms) and/or single arms:
 *   • best students across the whole selection
 *   • per class (selected arms together): best overall + best in EVERY subject
 *   • per arm: best overall + best in EVERY subject
 *
 * Scores come from ClassResultsLoader, which reproduces the CSS Kabba
 * broadsheet formulas exactly:
 *   BF      = previous term's Cum (same session) → stored bf → 0
 *   Cum     = BF + Total (raw running sum)
 *   Cum Ave = Cum ÷ term number
 * Averages count only subjects with a score above 0 — the same rule the
 * broadsheet uses for its Cum Ave — rounded to 1 dp.
 *
 * Ranking: competition ranking (1, 2, 2, 4). Ties share a position; "Top N"
 * includes everyone tied inside the cut-off.
 */
class BestStudentsExplorer
{
    public const BASES = [
        'cum_ave' => 'Cumulative average (BF + Total) ÷ term number',
        'total'   => 'Term total',
        'cum'     => 'Cumulative raw sum (BF + Total)',
    ];

    public const BASES_SHORT = [
        'cum_ave' => 'Cum average',
        'total'   => 'Term total',
        'cum'     => 'Cum (raw sum)',
    ];

    public const MEASURES = [
        'average' => 'Average score per subject',
        'sum'     => 'Total of all subject scores',
    ];

    /** CSS Kabba broadsheet averages count only subjects scored above 0. */
    private const AVERAGE_INCLUDES_ZEROS = false;

    public function __construct(private ClassResultsLoader $loader) {}

    // =========================================================================
    // Options / selection
    // =========================================================================

    public function normaliseOptions(array $in): array
    {
        $basis   = isset(self::BASES[$in['basis'] ?? '']) ? $in['basis'] : 'cum_ave';
        $measure = isset(self::MEASURES[$in['measure'] ?? '']) ? $in['measure'] : 'average';

        return [
            'basis'          => $basis,
            'measure'        => $measure,
            'top_n'          => max(1, min(50, (int) ($in['top_n'] ?? 5))),
            'subject_top_n'  => max(1, min(10, (int) ($in['subject_top_n'] ?? 3))),
            'min_subjects'   => max(0, min(40, (int) ($in['min_subjects'] ?? 0))),
            'exclude_failed' => filter_var($in['exclude_failed'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /** Whole classes (by name, all arms) + single arms (by id) → class ids. */
    public function resolveClassIds(array $groups, array $ids): array
    {
        $groups = array_values(array_filter(array_map('strval', $groups)));
        $ids    = array_values(array_filter(array_map('intval', $ids)));
        if (empty($groups) && empty($ids)) return [];

        return Schoolclass::query()
            ->where(function ($q) use ($groups, $ids) {
                if ($groups) $q->orWhereIn('schoolclass', $groups);
                if ($ids)    $q->orWhereIn('id', $ids);
            })
            ->pluck('id')->map(fn ($v) => (int) $v)->unique()->values()->all();
    }

    // =========================================================================
    // Build
    // =========================================================================

    public function build(int $termId, int $sessionId, array $classIds, array $opts): array
    {
        $opts  = $this->normaliseOptions($opts);
        $basis = $opts['basis'];

        $data     = $this->loader->load($termId, $sessionId, $classIds);
        $subjects = $data['subjects'];
        $rows     = array_map(fn ($r) => $this->computeRow($r, $basis), $data['rows']);

        $report = [
            'options'       => $opts,
            'basis_label'   => self::BASES[$basis],
            'basis_short'   => self::BASES_SHORT[$basis],
            'measure_label' => self::MEASURES[$opts['measure']],
            'selection'     => $this->selectionLabels($data['classes']),
            'overall'       => [],
            'classes'       => [],
            'students'      => 0,
            'ranked'        => 0,
            'subject_count' => count($subjects),
        ];

        if (empty($rows)) return $report;

        // ── Whole selection (a student in two arms appears once) ──
        $unique = [];
        foreach ($rows as $row) {
            $sid = $row['id'];
            if (!isset($unique[$sid]) || $row['_x']['n_scored'] > $unique[$sid]['_x']['n_scored']) {
                $unique[$sid] = $row;
            }
        }
        $whole = $this->rankGroup(array_values($unique), $opts);
        $report['overall']  = $whole['top'];
        $report['students'] = count($unique);
        $report['ranked']   = $whole['ranked'];
        $report['mean']     = $whole['mean'];
        $report['excluded'] = $whole['excluded'];

        // ── Per class, then per arm ──
        $byClass = [];
        foreach ($rows as $row) {
            $byClass[$row['class_name']][$row['class_label']][] = $row;
        }
        uksort($byClass, 'strnatcasecmp');

        foreach ($byClass as $className => $arms) {
            uksort($arms, 'strnatcasecmp');
            $classRows = array_merge(...array_values($arms));

            $classRank = $this->rankGroup($classRows, $opts);
            $entry = [
                'students' => count($classRows),
                'ranked'   => $classRank['ranked'],
                'mean'     => $classRank['mean'],
                'top'      => $classRank['top'],
                'subjects' => $this->subjectToppers($classRows, $subjects, $opts),
                'arms'     => [],
            ];

            foreach ($arms as $armLabel => $armRows) {
                $armRank = $this->rankGroup($armRows, $opts);
                $entry['arms'][$armLabel] = [
                    'students' => count($armRows),
                    'ranked'   => $armRank['ranked'],
                    'mean'     => $armRank['mean'],
                    'top'      => $armRank['top'],
                    'subjects' => $this->subjectToppers($armRows, $subjects, $opts),
                ];
            }

            $report['classes'][$className] = $entry;
        }

        return $report;
    }

    // =========================================================================
    // Internals
    // =========================================================================

    private function selectionLabels(iterable $classes): array
    {
        $labels = [];
        foreach ($classes as $c) {
            $labels[] = trim($c->schoolclass . ' ' . ($c->arm_name ?? ''));
        }
        natcasesort($labels);
        return array_values($labels);
    }

    private function computeRow(array $row, string $basis): array
    {
        $scores = [];   // subjects actually scored → score on basis
        $forAvg = [];
        foreach ($row['subjects'] ?? [] as $subId => $sd) {
            $v   = (float) ($sd[$basis] ?? 0);
            $sat = (float) ($sd['total'] ?? 0) > 0 || (float) ($sd['cum'] ?? 0) > 0;
            if ($sat) $scores[(int) $subId] = $v;
            if (self::AVERAGE_INCLUDES_ZEROS || $v > 0) $forAvg[] = $v;
        }

        $sum = array_sum($forAvg);
        $n   = count($forAvg);

        $row['_x'] = [
            'scores'   => $scores,
            'sum'      => round($sum, 1),
            'avg'      => $n > 0 ? round($sum / $n, 1) : 0.0,
            'n_scored' => count($scores),
        ];
        return $row;
    }

    private function rankGroup(array $rows, array $opts): array
    {
        $items = [];
        $excluded = 0;
        foreach ($rows as $row) {
            $x = $row['_x'];
            if ($x['n_scored'] === 0 || $x['n_scored'] < $opts['min_subjects']) { $excluded++; continue; }
            if ($opts['exclude_failed'] && !empty(array_filter($x['scores'], fn ($v) => $v < 40))) { $excluded++; continue; }

            $value   = $opts['measure'] === 'sum' ? $x['sum'] : $x['avg'];
            $items[] = ['row' => $row, 'value' => $value, 'tie' => $x['avg']];
        }

        $ranked = $this->competitionRank($items);
        $mean   = count($items) ? round(array_sum(array_column($items, 'value')) / count($items), 1) : 0;

        return [
            'ranked'   => count($items),
            'excluded' => $excluded,
            'mean'     => $mean,
            'top'      => $this->top($ranked, $opts['top_n'], fn ($it) => $this->studentEntry($it)),
        ];
    }

    private function subjectToppers(array $rows, array $subjectsMap, array $opts): array
    {
        $basis = $opts['basis'];
        $out   = [];

        foreach ($subjectsMap as $subId => $info) {
            $items = [];
            foreach ($rows as $row) {
                if (!array_key_exists($subId, $row['_x']['scores'])) continue;
                $sd = $row['subjects'][$subId];
                $items[] = [
                    'row'   => $row,
                    'value' => (float) $sd[$basis],
                    'tie'   => (float) ($sd['total'] ?? 0),
                    'grade' => $this->subjectGrade($sd, $basis, !empty($row['is_senior'])),
                ];
            }
            if (empty($items)) continue;

            $values = array_column($items, 'value');
            $out[$subId] = [
                'subject' => $info['subject_name'],
                'code'    => $info['subject_code'] ?? '',
                'count'   => count($items),
                'mean'    => round(array_sum($values) / count($values), 1),
                'top'     => $this->top($this->competitionRank($items), $opts['subject_top_n'], fn ($it) => $this->subjectEntry($it)),
            ];
        }

        uasort($out, fn ($a, $b) => strcasecmp($a['subject'], $b['subject']));
        return $out;
    }

    /** Competition rank on value desc; equal values share a rank (tie field only orders the display). */
    private function competitionRank(array $items): array
    {
        usort($items, function ($a, $b) {
            return [$b['value'], $b['tie'], $this->name($a['row'])] <=> [$a['value'], $a['tie'], $this->name($b['row'])];
        });

        $prev = null; $rank = 0;
        foreach ($items as $i => &$it) {
            $v = round($it['value'], 2);
            if ($v !== $prev) { $rank = $i + 1; $prev = $v; }
            $it['rank'] = $rank;
        }
        unset($it);
        return $items;
    }

    private function top(array $ranked, int $n, callable $map): array
    {
        $out = [];
        foreach ($ranked as $it) {
            if ($it['rank'] > $n) break;
            $out[] = $map($it);
        }
        return $out;
    }

    private function studentEntry(array $it): array
    {
        $row = $it['row'];
        $x   = $row['_x'];
        return [
            'rank'        => $it['rank'],
            'id'          => $row['id'],
            'name'        => $this->name($row),
            'admissionno' => $row['admissionno'] ?? '',
            'class'       => $row['class_name'],
            'arm'         => $row['arm'],
            'class_label' => $row['class_label'],
            'picture'     => $row['picture'] ?? null,
            'gender'      => $row['gender'] ?? '',
            'value'       => $it['value'],
            'avg'         => $x['avg'],
            'sum'         => $x['sum'],
            'subjects'    => $x['n_scored'],
            'grade'       => $this->scaleGrade($x['avg'], !empty($row['is_senior'])),
        ];
    }

    private function subjectEntry(array $it): array
    {
        $row = $it['row'];
        return [
            'rank'        => $it['rank'],
            'id'          => $row['id'],
            'name'        => $this->name($row),
            'admissionno' => $row['admissionno'] ?? '',
            'class_label' => $row['class_label'],
            'arm'         => $row['arm'],
            'value'       => round($it['value'], 2),
            'grade'       => $it['grade'],
        ];
    }

    private function name(array $row): string
    {
        return trim(strtoupper($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? ''), ', ');
    }

    // =========================================================================
    // Grades (CSS Kabba: A1–F9 from the class category, as on the broadsheet)
    // =========================================================================

    private function subjectGrade(array $sd, string $basis, bool $isSenior): string
    {
        // Same grades the broadsheet shows: term-total grade or cum-average grade
        $g = $basis === 'total' ? ($sd['total_grade'] ?? '') : ($sd['cum_grade'] ?? '');
        $g = (string) ($g ?: ($sd['grade'] ?? ''));
        return ($g !== '' && $g !== '-') ? $g : $this->scaleGrade((float) ($sd['cum_ave'] ?? 0), $isSenior);
    }

    public function scaleGrade(float $score, bool $isSenior): string
    {
        return match (true) {
            $score >= 75 => 'A1', $score >= 70 => 'B2', $score >= 65 => 'B3',
            $score >= 60 => 'C4', $score >= 55 => 'C5', $score >= 50 => 'C6',
            $score >= 45 => 'D7', $score >= 40 => 'E8', default => 'F9',
        };
    }
}
