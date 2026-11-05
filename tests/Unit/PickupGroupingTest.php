<?php

namespace Tests\Unit;

use App\Modules\Dispatch\ManualAssignmentService as M;
use PHPUnit\Framework\TestCase;

/** Waiting jobs from the same pickup are grouped on the dispatch board. */
class PickupGroupingTest extends TestCase
{
    public function test_nearby_pickups_group_and_far_ones_do_not(): void
    {
        $jobs = [
            ['id' => 1, 'lat' => 43.4505, 'lng' => -80.4925],
            ['id' => 2, 'lat' => 43.4700, 'lng' => -80.5400],   // ~4.5 km away
            ['id' => 3, 'lat' => 43.4510, 'lng' => -80.4920],   // ~70 m from 1
            ['id' => 4, 'lat' => null, 'lng' => null],
            ['id' => 5, 'lat' => null, 'lng' => null],
        ];
        $this->assertSame([[1, 3], [2], [4], [5]], M::groupByPickup($jobs));
        $this->assertSame([[1], [2], [3], [4], [5]], M::groupByPickup($jobs, 10));
    }

    public function test_distance(): void
    {
        $this->assertEqualsWithDelta(111_195, M::metresBetween(0, 0, 1, 0), 50);
        $this->assertSame(0.0, M::metresBetween(43.45, -80.49, 43.45, -80.49));
    }
}
