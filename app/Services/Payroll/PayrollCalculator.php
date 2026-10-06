<?php

namespace App\Services\Payroll;

/**
 * Pure payroll maths — no database, so it can be tested on its own.
 *
 * Money is handled in kobo (integers) throughout and converted back to naira
 * at the end, so totals always add up exactly.
 *
 * PAYE (Nigeria Tax Act 2025, from 1 Jan 2026):
 *   annual chargeable income = annual gross taxable pay
 *                              − employee pension − NHF − NHIA (employee)
 *                              − other allowed reliefs (life assurance, mortgage interest)
 *                              − rent relief (20% of annual rent, capped at ₦500,000)
 *   tax = progressive bands on chargeable income; monthly PAYE = annual tax ÷ 12.
 * Older rule sets (e.g. PITA with CRA) are supported through the rates config
 * so historical periods can be recalculated the way they were.
 */
class PayrollCalculator
{
    /**
     * @param array $lines   earnings for the month: [['code','label','amount'(naira),'taxable'(bool),'pensionable'(bool),'basic'(bool)]]
     * @param array $profile ['paye'=>bool,'pension'=>bool,'nhf'=>bool,'nhia'=>bool,'annual_rent'=>float,'other_reliefs_annual'=>float]
     * @param array $rates   ['paye'=>[...], 'pension'=>[...], 'nhf'=>[...], 'nhia'=>[...], 'nsitf'=>[...], 'itf'=>[...]]
     * @param float $proration fraction of the month worked (1 = full month)
     */
    public static function compute(array $lines, array $profile, array $rates, float $proration = 1.0, array $extraDeductions = []): array
    {
        $k = fn ($naira) => (int) round(((float) $naira) * 100);
        $n = fn (int $kobo) => $kobo / 100;
        $proration = max(0.0, min(1.0, $proration));

        // ── Earnings ────────────────────────────────────────────────────
        $earn = []; $gross = 0; $taxable = 0; $pensionBase = 0; $basic = 0;
        $oneOffTaxable = 0; $oneOffPensionBase = 0;   // bonus, arrears… taxed as a lump sum, not ×12
        foreach ($lines as $l) {
            $oneOff = !empty($l['one_off']);
            $amt = (int) round($k($l['amount'] ?? 0) * ($oneOff || !empty($l['no_proration']) ? 1 : $proration));
            if ($amt === 0) continue;
            $earn[] = ['code' => $l['code'], 'label' => $l['label'], 'type' => 'earning', 'amount' => $amt,
                       'taxable' => $l['taxable'] ?? true, 'pensionable' => $l['pensionable'] ?? false, 'one_off' => $oneOff, 'meta' => $l['meta'] ?? null];
            $gross += $amt;
            if ($l['taxable'] ?? true) { $taxable += $amt; if ($oneOff) $oneOffTaxable += $amt; }
            if ($l['pensionable'] ?? false) { $pensionBase += $amt; if ($oneOff) $oneOffPensionBase += $amt; }
            if ($l['basic'] ?? false) $basic += $amt;
        }

        // ── Statutory deductions (employee) & employer costs ────────────
        $pr = $rates['pension'] ?? [];
        $empPension = !empty($profile['pension']) ? (int) round($pensionBase * (float) ($pr['employee'] ?? 0.08)) : 0;
        $erPension  = !empty($profile['pension']) ? (int) round($pensionBase * (float) ($pr['employer'] ?? 0.10)) : 0;

        $nhfRate = (float) ($rates['nhf']['rate'] ?? 0.025);
        $nhf = !empty($profile['nhf']) ? (int) round($basic * $nhfRate) : 0;

        $nh = $rates['nhia'] ?? [];
        $nhiaBase = ($nh['base'] ?? 'basic') === 'gross' ? $gross : $basic;
        $nhia   = !empty($profile['nhia']) ? (int) round($nhiaBase * (float) ($nh['employee'] ?? 0)) : 0;
        $erNhia = !empty($profile['nhia']) ? (int) round($nhiaBase * (float) ($nh['employer'] ?? 0)) : 0;

        $nsitf = (int) round($gross * (float) ($rates['nsitf']['employer'] ?? 0));
        $itf   = (int) round($gross * (float) ($rates['itf']['employer'] ?? 0));

        // ── PAYE ────────────────────────────────────────────────────────
        $tax = ['monthly' => 0, 'annual_tax' => 0, 'annual_gross' => 0, 'reliefs' => [], 'chargeable' => 0, 'bands' => [], 'one_off_tax' => 0, 'rule' => $rates['paye']['code'] ?? null];
        if (!empty($profile['paye'])) {
            $oneOffPension = !empty($profile['pension']) ? (int) round($oneOffPensionBase * (float) ($pr['employee'] ?? 0.08)) : 0;
            $tax = self::paye($taxable - $oneOffTaxable, $empPension - $oneOffPension, $nhf, $nhia, $profile, $rates['paye'] ?? [], $proration,
                              $oneOffTaxable, $oneOffPension);
        }

        // ── Other deductions (loans, cooperative, …) ────────────────────
        $other = []; $otherTotal = 0;
        foreach ($extraDeductions as $d) {
            $amt = $k($d['amount'] ?? 0);
            if ($amt <= 0) continue;
            $other[] = ['code' => $d['code'], 'label' => $d['label'], 'type' => 'deduction', 'amount' => $amt, 'meta' => $d['meta'] ?? null];
            $otherTotal += $amt;
        }

        $statutory = $tax['monthly'] + $empPension + $nhf + $nhia;
        $net = $gross - $statutory - $otherTotal;

        // Never pay below the configured floor: trim non-statutory deductions from the end.
        $floorPct = (float) ($rates['limits']['min_net_pct'] ?? 0);
        $floor = (int) round($gross * $floorPct);
        $trimmed = [];
        if ($net < $floor) {
            for ($i = count($other) - 1; $i >= 0 && $net < $floor; $i--) {
                $cut = min($other[$i]['amount'], $floor - $net);
                $other[$i]['amount'] -= $cut; $otherTotal -= $cut; $net += $cut;
                $trimmed[] = ['code' => $other[$i]['code'], 'amount' => $n($cut)];
            }
            $other = array_values(array_filter($other, fn ($o) => $o['amount'] > 0));
        }

        $out = [];
        foreach ($earn as $e) $out[] = $e;
        if ($tax['monthly'])  $out[] = ['code' => 'PAYE', 'label' => 'PAYE tax', 'type' => 'deduction', 'amount' => $tax['monthly']];
        if ($empPension)      $out[] = ['code' => 'PENSION_EE', 'label' => 'Pension (employee ' . self::pct($pr['employee'] ?? 0.08) . ')', 'type' => 'deduction', 'amount' => $empPension];
        if ($nhf)             $out[] = ['code' => 'NHF', 'label' => 'National Housing Fund (' . self::pct($nhfRate) . ')', 'type' => 'deduction', 'amount' => $nhf];
        if ($nhia)            $out[] = ['code' => 'NHIA_EE', 'label' => 'Health insurance (NHIA)', 'type' => 'deduction', 'amount' => $nhia];
        foreach ($other as $o) $out[] = $o;
        if ($erPension)       $out[] = ['code' => 'PENSION_ER', 'label' => 'Pension (employer ' . self::pct($pr['employer'] ?? 0.10) . ')', 'type' => 'employer', 'amount' => $erPension];
        if ($erNhia)          $out[] = ['code' => 'NHIA_ER', 'label' => 'Health insurance (employer)', 'type' => 'employer', 'amount' => $erNhia];
        if ($nsitf)           $out[] = ['code' => 'NSITF', 'label' => 'NSITF (employer)', 'type' => 'employer', 'amount' => $nsitf];
        if ($itf)             $out[] = ['code' => 'ITF', 'label' => 'ITF (employer)', 'type' => 'employer', 'amount' => $itf];

        return [
            'lines'            => array_map(fn ($l) => array_merge($l, ['amount' => $n($l['amount'])]), $out),
            'gross'            => $n($gross),
            'taxable_gross'    => $n($taxable),
            'pension_base'     => $n($pensionBase),
            'basic'            => $n($basic),
            'paye'             => $n($tax['monthly']),
            'employee_pension' => $n($empPension),
            'employer_pension' => $n($erPension),
            'nhf'              => $n($nhf),
            'nhia'             => $n($nhia),
            'employer_nhia'    => $n($erNhia),
            'nsitf'            => $n($nsitf),
            'itf'              => $n($itf),
            'other_deductions' => $n($otherTotal),
            'total_deductions' => $n($statutory + $otherTotal),
            'net'              => $n($net),
            'employer_cost'    => $n($gross + $erPension + $erNhia + $nsitf + $itf),
            'proration'        => $proration,
            'trimmed'          => $trimmed,
            'tax'              => [
                'rule'         => $tax['rule'],
                'annual_gross' => $n($tax['annual_gross']),
                'reliefs'      => array_map($n, $tax['reliefs']),
                'chargeable'   => $n($tax['chargeable']),
                'annual_tax'   => $n($tax['annual_tax']),
                'one_off_tax'  => $n($tax['one_off_tax']),
                'bands'        => array_map(fn ($b) => ['from' => $n($b['from']), 'to' => $b['to'] === null ? null : $n($b['to']), 'rate' => $b['rate'], 'amount' => $n($b['amount']), 'tax' => $n($b['tax'])], $tax['bands']),
                'effective_rate' => $taxable > 0 ? round($tax['monthly'] / $taxable * 100, 2) : 0,
            ],
        ];
    }

    /**
     * PAYE for one month (all kobo). Regular pay is annualised (×12 on a
     * full-month basis); one-off pay (bonus, arrears) is added once to the
     * year, and only the extra tax it causes is charged this month.
     */
    protected static function paye(int $taxableMonthly, int $pension, int $nhf, int $nhia, array $profile, array $cfg, float $proration,
                                   int $oneOffTaxable = 0, int $oneOffPension = 0): array
    {
        $factor = $proration > 0 ? 1 / $proration : 1;
        $annualGross = (int) round($taxableMonthly * $factor * 12);
        $reliefs = [
            'pension' => (int) round($pension * $factor * 12),
            'nhf'     => (int) round($nhf * $factor * 12),
            'nhia'    => (int) round($nhia * $factor * 12),
        ];

        $old = ($cfg['method'] ?? 'nta2025') === 'pita_cra';
        if ($old) {
            // Old rule: CRA = max(₦200k, 1% of gross) + 20% of gross.
            $reliefs['cra'] = max((int) round(($cfg['cra_min'] ?? 200000) * 100), (int) round($annualGross * ($cfg['cra_pct_floor'] ?? 0.01)))
                            + (int) round($annualGross * ($cfg['cra_pct'] ?? 0.20));
        } else {
            $rent = (int) round(((float) ($profile['annual_rent'] ?? 0)) * 100);
            $reliefs['rent'] = min((int) round($rent * (float) ($cfg['rent_relief_rate'] ?? 0.20)), (int) round(((float) ($cfg['rent_relief_cap'] ?? 500000)) * 100));
            $reliefs['other'] = (int) round(((float) ($profile['other_reliefs_annual'] ?? 0)) * 100);
        }
        $reliefs = array_filter($reliefs);

        $chargeableRegular = max(0, $annualGross - array_sum($reliefs));
        [$taxRegular, $bandsRegular] = self::bands($chargeableRegular, $cfg);
        if ($old && !empty($cfg['minimum_tax_pct'])) $taxRegular = max($taxRegular, (int) round($annualGross * (float) $cfg['minimum_tax_pct']));

        $monthly = (int) round($taxRegular / 12 * ($proration > 0 ? $proration : 1));
        $taxAll = $taxRegular; $bands = $bandsRegular; $chargeable = $chargeableRegular;

        if ($oneOffTaxable > 0) {
            if ($oneOffPension) $reliefs['pension'] = ($reliefs['pension'] ?? 0) + $oneOffPension;
            $chargeable = max(0, $chargeableRegular + $oneOffTaxable - $oneOffPension);
            [$taxAll, $bands] = self::bands($chargeable, $cfg);
            $taxAll = max($taxAll, $taxRegular);
            $monthly += $taxAll - $taxRegular;
        }

        return ['monthly' => $monthly, 'annual_tax' => $taxAll, 'annual_gross' => $annualGross + $oneOffTaxable, 'reliefs' => $reliefs,
                'chargeable' => $chargeable, 'bands' => $bands, 'one_off_tax' => $taxAll - $taxRegular, 'rule' => $cfg['code'] ?? null];
    }

    /** Progressive bands. @return array{0:int,1:array} annual tax (kobo), band breakdown */
    protected static function bands(int $chargeable, array $cfg): array
    {
        $bands = []; $total = 0; $from = 0;
        foreach ($cfg['bands'] ?? [] as $b) {
            $to = $b['to'] === null ? null : (int) round($b['to'] * 100);
            if ($chargeable <= $from) break;
            $slice = ($to === null ? $chargeable : min($chargeable, $to)) - $from;
            $t = (int) round($slice * (float) $b['rate']);
            $bands[] = ['from' => $from, 'to' => $to, 'rate' => (float) $b['rate'], 'amount' => $slice, 'tax' => $t];
            $total += $t;
            if ($to === null) break;
            $from = $to;
        }
        return [$total, $bands];
    }

    protected static function pct($r): string
    {
        return rtrim(rtrim(number_format((float) $r * 100, 2), '0'), '.') . '%';
    }

    /** Default rule sets, used to seed payroll_statutory_rates. */
    public static function defaults(): array
    {
        return [
            ['code' => 'paye', 'name' => 'PAYE — Nigeria Tax Act 2025', 'effective_from' => '2026-01-01', 'effective_to' => null, 'config' => [
                'code' => 'NTA2025', 'method' => 'nta2025', 'rent_relief_rate' => 0.20, 'rent_relief_cap' => 500000,
                'bands' => [
                    ['to' => 800000, 'rate' => 0], ['to' => 3000000, 'rate' => 0.15], ['to' => 12000000, 'rate' => 0.18],
                    ['to' => 25000000, 'rate' => 0.21], ['to' => 50000000, 'rate' => 0.23], ['to' => null, 'rate' => 0.25],
                ]]],
            ['code' => 'paye', 'name' => 'PAYE — PITA (old rules, before 2026)', 'effective_from' => '2011-01-01', 'effective_to' => '2025-12-31', 'config' => [
                'code' => 'PITA2011', 'method' => 'pita_cra', 'cra_min' => 200000, 'cra_pct_floor' => 0.01, 'cra_pct' => 0.20, 'minimum_tax_pct' => 0.01,
                'bands' => [
                    ['to' => 300000, 'rate' => 0.07], ['to' => 600000, 'rate' => 0.11], ['to' => 1100000, 'rate' => 0.15],
                    ['to' => 1600000, 'rate' => 0.19], ['to' => 3200000, 'rate' => 0.21], ['to' => null, 'rate' => 0.24],
                ]]],
            ['code' => 'pension', 'name' => 'Pension (PRA 2014)', 'effective_from' => '2014-07-01', 'effective_to' => null,
             'config' => ['employee' => 0.08, 'employer' => 0.10, 'base' => 'basic + housing + transport']],
            ['code' => 'nhf', 'name' => 'National Housing Fund', 'effective_from' => '1992-01-01', 'effective_to' => null,
             'config' => ['rate' => 0.025, 'base' => 'basic']],
            ['code' => 'nhia', 'name' => 'Health insurance (NHIA) — set if the school participates', 'effective_from' => '2022-01-01', 'effective_to' => null,
             'config' => ['employee' => 0, 'employer' => 0, 'base' => 'basic']],
            ['code' => 'nsitf', 'name' => 'NSITF (employer)', 'effective_from' => '2011-01-01', 'effective_to' => null, 'config' => ['employer' => 0.01]],
            ['code' => 'itf', 'name' => 'ITF (employer) — set to 1% if the school qualifies', 'effective_from' => '2011-01-01', 'effective_to' => null, 'config' => ['employer' => 0]],
            ['code' => 'limits', 'name' => 'Payroll limits', 'effective_from' => '2000-01-01', 'effective_to' => null,
             'config' => ['min_net_pct' => 0.3333, 'minimum_wage_monthly' => 70000, 'require_different_approver' => false]],
        ];
    }
}
