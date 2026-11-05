<?php

namespace Tests\Unit;

use App\Modules\System\ErrorReports;
use RuntimeException;
use Tests\TestCase;

/** Error tracking groups the same failure at the same place, whatever the message says. */
class ErrorReportsTest extends TestCase
{
    private function boom(string $m): RuntimeException
    {
        return new RuntimeException($m);
    }

    public function test_same_place_same_fingerprint(): void
    {
        $a = $this->boom('order 1 failed');
        $b = $this->boom('order 2 failed');
        $c = new \LogicException('x');
        $this->assertSame(ErrorReports::fingerprint($a), ErrorReports::fingerprint($b));
        $this->assertNotSame(ErrorReports::fingerprint($a), ErrorReports::fingerprint($c));
        $this->assertSame('tests/Unit/ErrorReportsTest.php', ErrorReports::relative($a->getFile()));
        $this->assertStringContainsString('tests/Unit/ErrorReportsTest.php', ErrorReports::trace($a));
        $this->assertStringContainsString('…vendor', ErrorReports::trace($a));
    }
}
