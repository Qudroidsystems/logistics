<?php

namespace Tests\Unit;

use App\Modules\Pricing\CostCalculator;
use App\Modules\Tracking\ParcelCodes;
use PHPUnit\Framework\TestCase;

/** The provider's job costing is pure arithmetic: the same profile and route always give the same price. */
class CostCalculatorTest extends TestCase
{
    private function profile(array $over = []): array
    {
        return $over + [
            'pricing_method' => 'higher_of', 'base_fee' => 50_000, 'rate_per_km' => 15_000, 'min_fee' => 150_000,
            'fuel_price_per_litre' => 88_000, 'fuel_l_per_100km' => '10.00', 'labour_per_job' => 80_000, 'labour_per_hour' => 0,
            'maintenance_per_km' => 2_000, 'other_per_job' => 27_000, 'loading_fee' => 0, 'waiting_per_minute' => 0, 'fragile_surcharge' => 0,
            'insurance_bp' => 0, 'return_trip_bp' => 0, 'markup_bp' => 2_000, 'rounding_kobo' => 100,
        ];
    }

    public function test_the_worked_example_from_the_spec(): void
    {
        // 18 km at 10 L/100 km and ₦880/L is ₦1,584 of fuel.
        $r = (new CostCalculator())->calculate($this->profile(), ['distance_m' => 18_000, 'duration_s' => 2_400]);

        $this->assertSame(158_400, $r['costs']['fuel']);
        $this->assertSame(36_000, $r['costs']['maintenance']);
        $this->assertSame(158_400 + 36_000 + 80_000 + 27_000, $r['operating_cost']);       // ₦3,014
        $this->assertSame(50_000 + 270_000, $r['per_km_price']);                            // ₦3,200
        $this->assertSame(301_400 + 60_280, $r['cost_plus_price']);                         // ₦3,616.80
        $this->assertSame(361_700, $r['suggested_price']);                                  // higher of the two, rounded up to ₦1
        $this->assertNull($r['warning']);
    }

    public function test_methods_are_alternatives_not_added_together(): void
    {
        $calc = new CostCalculator();
        $job = ['distance_m' => 18_000, 'duration_s' => 2_400];

        $this->assertSame(320_000, $calc->calculate($this->profile(['pricing_method' => 'per_km']), $job)['suggested_price']);
        $this->assertSame(361_700, $calc->calculate($this->profile(['pricing_method' => 'cost_plus']), $job)['suggested_price']);
    }

    public function test_minimum_fee_and_rounding(): void
    {
        $r = (new CostCalculator())->calculate($this->profile(['rounding_kobo' => 5_000]), ['distance_m' => 1_000, 'duration_s' => 120]);

        $this->assertTrue($r['min_fee_applied']);
        $this->assertSame(150_000, $r['suggested_price']);
    }

    public function test_surcharges_go_on_top_and_expenses_count_as_cost(): void
    {
        $p = $this->profile(['pricing_method' => 'per_km', 'loading_fee' => 20_000, 'waiting_per_minute' => 1_000, 'fragile_surcharge' => 30_000, 'insurance_bp' => 100]);
        $r = (new CostCalculator())->calculate($p, ['distance_m' => 18_000, 'duration_s' => 2_400, 'loading' => true, 'wait_minutes' => 10,
            'fragile' => true, 'declared_value' => 5_000_000, 'job_expenses' => 40_000]);

        $this->assertSame(['loading' => 20_000, 'waiting' => 10_000, 'fragile' => 30_000, 'insurance' => 50_000], $r['surcharges']);
        $this->assertSame(40_000, $r['costs']['job_expenses']);
        $this->assertSame(320_000 + 40_000 + 110_000, $r['per_km_price']);
    }

    public function test_empty_return_and_platform_fee_and_loss_warning(): void
    {
        $p = $this->profile(['pricing_method' => 'per_km', 'rate_per_km' => 2_000, 'base_fee' => 0, 'min_fee' => 0, 'return_trip_bp' => 10_000]);
        $r = (new CostCalculator())->calculate($p, ['distance_m' => 18_000, 'duration_s' => 2_400], fn (int $price) => intdiv($price * 1_200, 10_000));

        $this->assertSame(158_400 + 36_000, $r['costs']['return_trip']);
        $this->assertSame(intdiv($r['suggested_price'] * 1_200, 10_000), $r['platform_fee']);
        $this->assertSame($r['suggested_price'] - $r['platform_fee'], $r['provider_net']);
        $this->assertSame('loss', $r['warning']);
    }

    public function test_parcel_codes_carry_a_check_character(): void
    {
        $code = ParcelCodes::make();
        $this->assertMatchesRegularExpression('/^PK[0-9A-HJKMNP-TV-Z]{9}$/', $code);
        $this->assertTrue(ParcelCodes::valid($code));
        $this->assertTrue(ParcelCodes::valid(strtolower(substr($code, 0, 5)).'-'.substr($code, 5)));

        $wrong = substr($code, 0, 4).($code[4] === '7' ? '8' : '7').substr($code, 5);
        $this->assertFalse(ParcelCodes::valid($wrong));
    }
}
