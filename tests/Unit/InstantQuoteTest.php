<?php

namespace Tests\Unit;

use App\Modules\Booking\InstantQuoteService;
use App\Modules\Pricing\PriceCalculator;
use Carbon\CarbonImmutable;
use RuntimeException;
use Tests\TestCase;

/** The instant price form: pickup times by service, and the size and vehicle price rules. */
class InstantQuoteTest extends TestCase
{
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = CarbonImmutable::parse('2026-10-21 14:30', 'America/Toronto');
        config(['booking.next_day_hour' => 9]);
    }

    public function test_same_day_is_now_and_next_day_is_tomorrow_morning(): void
    {
        $this->assertNull(InstantQuoteService::pickupAt('same_day', null, $this->now));
        $this->assertSame('2026-10-22 09:00', InstantQuoteService::pickupAt('next_day', null, $this->now)->format('Y-m-d H:i'));
    }

    public function test_scheduled_needs_a_time_between_an_hour_and_30_days_ahead(): void
    {
        $this->assertSame('2026-10-23 11:00', InstantQuoteService::pickupAt('scheduled', '2026-10-23T11:00', $this->now)->format('Y-m-d H:i'));
        foreach ([null, '2026-10-21T15:00', '2026-12-01T10:00'] as $bad) {
            try {
                InstantQuoteService::pickupAt('scheduled', $bad, $this->now);
                $this->fail("Accepted $bad");
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function price(array $rules, array $in): array
    {
        $input = $in + ['distance_m' => 5_000, 'duration_s' => 600, 'weight_g' => 1_000, 'extra_stops' => 0, 'wait_minutes' => 0, 'hour' => 12,
            'fragile' => false, 'declared_value' => 0, 'cod_amount' => 0, 'dropoff_zone_ids' => [], 'vehicle_multiplier_bp' => 10_000,
            'surge_multiplier_bp' => 10_000, 'tax_rate_bp' => 0, 'discount' => 0];

        return (new PriceCalculator())->calculate(['min_fee' => 0, 'max_fee' => null, 'rounding_kobo' => 1], $rules, $input);
    }

    public function test_size_and_vehicle_rules_only_apply_when_they_match(): void
    {
        $rules = [
            ['kind' => 'base', 'amount' => 1_000],
            ['kind' => 'item_size', 'amount' => 500, 'condition' => ['sizes' => ['large']]],
            ['kind' => 'vehicle', 'amount' => 700, 'condition' => ['vehicles' => ['suv', 'van']]],
            ['kind' => 'fragile', 'amount' => 300],
        ];
        $this->assertSame(1_000, $this->price($rules, ['size' => 'small', 'vehicle_code' => 'sedan'])['total']);
        $this->assertSame(1_500, $this->price($rules, ['size' => 'large', 'vehicle_code' => 'sedan'])['total']);
        $this->assertSame(2_500, $this->price($rules, ['size' => 'large', 'vehicle_code' => 'van', 'fragile' => true])['total']);
        $this->assertSame(1_000, $this->price($rules, [])['total']);
    }
}
