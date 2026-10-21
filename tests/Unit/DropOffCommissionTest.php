<?php

namespace Tests\Unit;

use App\Modules\DropOff\DropOffService;
use Tests\TestCase;

/** What a partner store earns per parcel. */
class DropOffCommissionTest extends TestCase
{
    public function test_flat_plus_share_of_the_price(): void
    {
        $this->assertSame(150, DropOffService::commission(150, 0, 2_500));
        $this->assertSame(125, DropOffService::commission(0, 500, 2_500));
        $this->assertSame(275, DropOffService::commission(150, 500, 2_500));
        $this->assertSame(0, DropOffService::commission(-10, -5, 2_500));
    }

    public function test_description(): void
    {
        $this->assertSame('No commission', DropOffService::describeCommission(0, 0));
        $this->assertStringContainsString('5% of the delivery price', DropOffService::describeCommission(0, 500));
        $this->assertStringContainsString('2.5%', DropOffService::describeCommission(100, 250));
    }
}
