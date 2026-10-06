<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollCalculator;
use PHPUnit\Framework\TestCase;

/** php artisan test --filter=PayrollCalculatorTest */
class PayrollCalculatorTest extends TestCase
{
    protected function rates(bool $old = false): array
    {
        $r = [];
        foreach (PayrollCalculator::defaults() as $d) {
            if ($d['code'] === 'paye' && ($old xor (bool) $d['effective_to'])) continue;
            $r[$d['code']] = $d['config'];
        }
        return $r;
    }

    protected function lines(): array
    {
        return [
            ['code' => 'BASIC', 'label' => 'Basic', 'amount' => 150000, 'taxable' => true, 'pensionable' => true, 'basic' => true],
            ['code' => 'HOUSING', 'label' => 'Housing', 'amount' => 60000, 'taxable' => true, 'pensionable' => true],
            ['code' => 'TRANSPORT', 'label' => 'Transport', 'amount' => 40000, 'taxable' => true, 'pensionable' => true],
            ['code' => 'OTHER', 'label' => 'Other', 'amount' => 50000, 'taxable' => true],
        ];
    }

    public function test_nta2025_full_month(): void
    {
        $r = PayrollCalculator::compute($this->lines(), ['paye' => 1, 'pension' => 1, 'nhf' => 1, 'annual_rent' => 600000], $this->rates());
        $this->assertEquals(300000, $r['gross']);
        $this->assertEquals(20000, $r['employee_pension']);
        $this->assertEquals(25000, $r['employer_pension']);
        $this->assertEquals(3750, $r['nhf']);
        // 3,600,000 − 240,000 pension − 45,000 NHF − 120,000 rent relief = 3,195,000
        $this->assertEquals(3195000, $r['tax']['chargeable']);
        // 0 on 800k + 15% of 2.2m + 18% of 195k = 365,100
        $this->assertEquals(365100, $r['tax']['annual_tax']);
        $this->assertEquals(30425, $r['paye']);
        $this->assertEquals(245825, $r['net']);
    }

    public function test_income_under_800k_is_tax_free(): void
    {
        $r = PayrollCalculator::compute([['code' => 'BASIC', 'label' => 'Basic', 'amount' => 60000, 'basic' => true]], ['paye' => 1], $this->rates());
        $this->assertEquals(0, $r['paye']);
    }

    public function test_rent_relief_is_capped(): void
    {
        $r = PayrollCalculator::compute($this->lines(), ['paye' => 1, 'annual_rent' => 10000000], $this->rates());
        $this->assertEquals(500000, $r['tax']['reliefs']['rent']);
    }

    public function test_part_month_uses_full_month_bands(): void
    {
        $full = PayrollCalculator::compute($this->lines(), ['paye' => 1, 'pension' => 1, 'nhf' => 1, 'annual_rent' => 600000], $this->rates());
        $half = PayrollCalculator::compute($this->lines(), ['paye' => 1, 'pension' => 1, 'nhf' => 1, 'annual_rent' => 600000], $this->rates(), 0.5);
        $this->assertEquals(150000, $half['gross']);
        $this->assertEqualsWithDelta($full['paye'] / 2, $half['paye'], 0.01);
    }

    public function test_deductions_never_push_below_minimum_take_home(): void
    {
        $r = PayrollCalculator::compute([['code' => 'BASIC', 'label' => 'Basic', 'amount' => 100000, 'basic' => true]], ['paye' => 0], $this->rates(), 1,
            [['code' => 'LOAN', 'label' => 'Loan', 'amount' => 90000]]);
        $this->assertEquals(33330, $r['net']);
        $this->assertNotEmpty($r['trimmed']);
    }

    public function test_lines_add_up_to_net(): void
    {
        $r = PayrollCalculator::compute($this->lines(), ['paye' => 1, 'pension' => 1, 'nhf' => 1], $this->rates(), 1,
            [['code' => 'LOAN', 'label' => 'Loan', 'amount' => 12345.67]]);
        $ded = array_sum(array_map(fn ($l) => $l['amount'], array_filter($r['lines'], fn ($l) => $l['type'] === 'deduction')));
        $this->assertEqualsWithDelta($r['gross'] - $ded, $r['net'], 0.001);
    }

    public function test_old_pita_rules_for_history(): void
    {
        $r = PayrollCalculator::compute($this->lines(), ['paye' => 1, 'pension' => 1, 'nhf' => 1], $this->rates(true));
        // CRA = 200,000 + 720,000; chargeable = 3.6m − 920k − 240k − 45k = 2,395,000 → 390,950 a year
        $this->assertEquals(2395000, $r['tax']['chargeable']);
        $this->assertEqualsWithDelta(32579.17, $r['paye'], 0.01);
    }

    public function test_one_off_bonus_taxed_once_not_times_twelve(): void
    {
        $lines = array_merge($this->lines(), [['code' => 'BONUS', 'label' => 'Bonus', 'amount' => 100000, 'taxable' => true, 'one_off' => true]]);
        $r = PayrollCalculator::compute($lines, ['paye' => 1, 'pension' => 1, 'nhf' => 1, 'annual_rent' => 600000], $this->rates());
        // Regular tax 30,425 + the bonus once at the 18% band = 18,000
        $this->assertEquals(18000, $r['tax']['one_off_tax']);
        $this->assertEquals(48425, $r['paye']);
        $this->assertEquals(400000, $r['gross']);
    }

    public function test_one_off_not_prorated(): void
    {
        $lines = array_merge($this->lines(), [['code' => 'ARREARS', 'label' => 'Arrears', 'amount' => 50000, 'taxable' => true, 'one_off' => true]]);
        $r = PayrollCalculator::compute($lines, ['paye' => 0], $this->rates(), 0.5);
        $this->assertEquals(200000, $r['gross']); // 150,000 half month + 50,000 arrears in full
    }
}
