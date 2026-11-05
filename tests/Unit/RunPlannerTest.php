<?php

namespace Tests\Unit;

use App\Modules\Dispatch\RunPlanner;
use PHPUnit\Framework\TestCase;

/** A driver's stops: each parcel picked up before it is dropped, nearest next. */
class RunPlannerTest extends TestCase
{
    public function test_two_parcels_from_one_shop(): void
    {
        $shop = [43.4505, -80.4925];
        $stops = [
            ['key' => 'A-p', 'job' => 'A', 'seq' => 1, 'lat' => $shop[0], 'lng' => $shop[1]],
            ['key' => 'A-d', 'job' => 'A', 'seq' => 2, 'lat' => 43.50, 'lng' => -80.53],   // far
            ['key' => 'B-p', 'job' => 'B', 'seq' => 1, 'lat' => $shop[0], 'lng' => $shop[1]],
            ['key' => 'B-d', 'job' => 'B', 'seq' => 2, 'lat' => 43.46, 'lng' => -80.50],   // near the shop
        ];
        // Both pickups at the shop first, then the nearer drop-off, then the far one.
        $this->assertSame(['A-p', 'B-p', 'B-d', 'A-d'], RunPlanner::order(43.449, -80.49, $stops));
    }

    public function test_a_drop_off_never_comes_before_its_pickup(): void
    {
        $stops = [
            ['key' => 'A-p', 'job' => 'A', 'seq' => 1, 'lat' => 43.60, 'lng' => -80.60],
            ['key' => 'A-d', 'job' => 'A', 'seq' => 2, 'lat' => 43.45, 'lng' => -80.49],   // right where the driver is
        ];
        $this->assertSame(['A-p', 'A-d'], RunPlanner::order(43.45, -80.49, $stops));
    }

    public function test_without_a_position_keeps_the_job_order(): void
    {
        $stops = [
            ['key' => 'A-d', 'job' => 'A', 'seq' => 2, 'lat' => 1.0, 'lng' => 1.0],
            ['key' => 'B-p', 'job' => 'B', 'seq' => 1, 'lat' => 9.0, 'lng' => 9.0],
            ['key' => 'B-d', 'job' => 'B', 'seq' => 2, 'lat' => 9.1, 'lng' => 9.1],
        ];
        $this->assertSame(['A-d', 'B-p', 'B-d'], RunPlanner::order(null, null, $stops));
    }
}
