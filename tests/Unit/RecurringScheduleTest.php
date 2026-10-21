<?php

namespace Tests\Unit;

use App\Modules\Recurring\RecurringDeliveryService as R;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** When the next trip of a recurring delivery is. 2026-10-21 is a Wednesday. */
class RecurringScheduleTest extends TestCase
{
    private string $tz = 'America/Toronto';

    private function at(string $s): CarbonImmutable
    {
        return CarbonImmutable::parse($s, $this->tz);
    }

    public function test_daily_and_weekdays(): void
    {
        $this->assertSame('2026-10-21 15:00', R::nextOccurrence('daily', [], '15:00', $this->at('2026-10-21 09:00'), '2026-10-01', null, $this->tz)->format('Y-m-d H:i'));
        $this->assertSame('2026-10-22 08:00', R::nextOccurrence('daily', [], '08:00', $this->at('2026-10-21 09:00'), '2026-10-01', null, $this->tz)->format('Y-m-d H:i'));
        // Friday after the time → Monday.
        $this->assertSame('2026-10-26 10:00', R::nextOccurrence('weekdays', [], '10:00', $this->at('2026-10-23 11:00'), '2026-10-01', null, $this->tz)->format('Y-m-d H:i'));
    }

    public function test_weekly_days_start_and_end(): void
    {
        // Mondays and Thursdays from Wednesday → Thursday.
        $this->assertSame('2026-10-22 15:30', R::nextOccurrence('weekly', [1, 4], '15:30', $this->at('2026-10-21 09:00'), '2026-10-01', null, $this->tz)->format('Y-m-d H:i'));
        // Not before the start date.
        $this->assertSame('2026-11-02 15:30', R::nextOccurrence('weekly', [1, 4], '15:30', $this->at('2026-10-21 09:00'), '2026-11-01', null, $this->tz)->format('Y-m-d H:i'));
        // Nothing left after the end date.
        $this->assertNull(R::nextOccurrence('weekly', [1], '15:30', $this->at('2026-10-21 09:00'), '2026-10-01', '2026-10-25', $this->tz));
        // Strictly after: the same moment moves on to the next one.
        $this->assertSame('2026-10-26 15:30', R::nextOccurrence('weekly', [1, 4], '15:30', $this->at('2026-10-22 15:30'), '2026-10-01', null, $this->tz)->format('Y-m-d H:i'));
    }

    public function test_describe(): void
    {
        $this->assertSame('Weekdays at 10:00 am', R::describe('weekdays', [], '10:00'));
        $this->assertSame('Mon, Thu at 3:30 pm', R::describe('weekly', [1, 4], '15:30'));
    }
}
