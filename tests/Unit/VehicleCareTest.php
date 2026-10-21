<?php

namespace Tests\Unit;

use App\Modules\Fleet\VehicleCareService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** When fleet reminders go out and how due items read. Pure rules, no database. */
class VehicleCareTest extends TestCase
{
    private function day(string $d): CarbonImmutable
    {
        return CarbonImmutable::parse($d);
    }

    public function test_reminder_stages(): void
    {
        $today = $this->day('2026-10-21');
        $this->assertNull(VehicleCareService::stage($this->day('2026-12-01'), $today), '41 days out: nothing yet');
        $this->assertSame('30', VehicleCareService::stage($this->day('2026-11-20'), $today));
        $this->assertSame('30', VehicleCareService::stage($this->day('2026-11-05'), $today), '15 days out is still the 30-day reminder');
        $this->assertSame('7', VehicleCareService::stage($this->day('2026-10-28'), $today));
        $this->assertSame('7', VehicleCareService::stage($this->day('2026-10-22'), $today));
        $this->assertSame('0', VehicleCareService::stage($this->day('2026-10-21'), $today));
        $this->assertSame('overdue', VehicleCareService::stage($this->day('2026-10-20'), $today));
    }

    public function test_due_badges(): void
    {
        $today = $this->day('2026-10-21');
        $this->assertNull(VehicleCareService::dateDue('Insurance', null, $today));
        $this->assertNull(VehicleCareService::dateDue('Insurance', '2027-01-01', $today));
        $this->assertSame(['label' => 'Insurance expires today', 'level' => 'warning'], VehicleCareService::dateDue('Insurance', '2026-10-21', $today));
        $this->assertSame(['label' => 'Insurance expired 3 days ago', 'level' => 'danger'], VehicleCareService::dateDue('Insurance', '2026-10-18', $today));
        $this->assertSame('info', VehicleCareService::dateDue('Roadworthiness', '2026-11-10', $today)['level']);
        $this->assertSame('Service was due 1 day ago', VehicleCareService::dateDue('Service', '2026-10-20', $today)['label']);
    }

    public function test_service_by_mileage(): void
    {
        $this->assertNull(VehicleCareService::kmDue(null, 10_000));
        $this->assertNull(VehicleCareService::kmDue(9_000, 10_000), '1,000 km to go is not due yet');
        $this->assertSame(['label' => 'Service due in 300 km', 'level' => 'warning'], VehicleCareService::kmDue(9_700, 10_000));
        $this->assertSame(['label' => 'Service overdue by 1,250 km', 'level' => 'danger'], VehicleCareService::kmDue(11_250, 10_000));
    }

    public function test_the_next_service_follows_the_last_one(): void
    {
        [$on, $km] = VehicleCareService::nextService($this->day('2026-10-21'), 42_000, 90, 5_000);
        $this->assertSame('2027-01-19', $on->toDateString());
        $this->assertSame(47_000, $km);

        [$on, $km] = VehicleCareService::nextService($this->day('2026-10-21'), null, null, 5_000);
        $this->assertNull($on);
        $this->assertNull($km, 'no mileage target without an odometer reading');
    }
}
