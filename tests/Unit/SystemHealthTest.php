<?php

namespace Tests\Unit;

use App\Modules\System\SystemHealth as H;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** Whether a scheduled task is on time, late, failed or running. */
class SystemHealthTest extends TestCase
{
    public function test_states(): void
    {
        $now = CarbonImmutable::parse('2026-10-31 12:07:00');
        $at = fn (string $s) => CarbonImmutable::parse($s);
        // Hourly task: due at 12:00.
        $this->assertSame('ok', H::state('0 * * * *', $at('2026-10-31 12:00:20'), 'ok', $at('2026-10-31 12:00:01'), $now));
        // Due 7 minutes ago and not run yet: still within the grace time.
        $this->assertSame('ok', H::state('0 * * * *', $at('2026-10-31 11:00:20'), 'ok', null, $now));
        $this->assertSame('late', H::state('0 * * * *', $at('2026-10-31 11:00:20'), 'ok', null, $now->addMinutes(20)));
        $this->assertSame('failed', H::state('0 * * * *', $at('2026-10-31 12:00:20'), 'failed', null, $now));
        $this->assertSame('running', H::state('0 * * * *', null, 'running', $at('2026-10-31 12:00:01'), $now));
        $this->assertSame('never', H::state('0 * * * *', null, null, null, $now->addMinutes(20)));
    }
}
