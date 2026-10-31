<?php

namespace Tests\Unit;

use App\Modules\Booking\ReferencePrice;
use PHPUnit\Framework\TestCase;

/** How a provider's offer reads next to the instant (road-based) price. */
class ReferencePriceTest extends TestCase
{
    public function test_compare(): void
    {
        $this->assertSame('About the instant price', ReferencePrice::compare(2_020, 2_000));
        $this->assertSame('10% below the instant price', ReferencePrice::compare(1_800, 2_000));
        $this->assertSame('25% above the instant price', ReferencePrice::compare(2_500, 2_000));
        $this->assertNull(ReferencePrice::compare(null, 2_000));
        $this->assertNull(ReferencePrice::compare(1_800, null));
    }
}
