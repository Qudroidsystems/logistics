<?php

namespace Tests\Unit;

use App\Modules\Payments\ETransferService;
use PHPUnit\Framework\TestCase;

/** What the customer's e-Transfer page says, from the payment's state. */
class ETransferStateTest extends TestCase
{
    private function i(array $o = []): object
    {
        return (object) ($o + ['status' => 'pending', 'confirmed_at' => null, 'rejected_at' => null, 'customer_sent_at' => null]);
    }

    public function test_states(): void
    {
        $this->assertSame('waiting', ETransferService::state($this->i()));
        $this->assertSame('sent', ETransferService::state($this->i(['customer_sent_at' => '2026-10-21 10:00'])));
        $this->assertSame('received', ETransferService::state($this->i(['status' => 'succeeded', 'confirmed_at' => '2026-10-21 11:00'])));
        $this->assertSame('rejected', ETransferService::state($this->i(['status' => 'failed', 'rejected_at' => '2026-10-21 11:00'])));
        $this->assertSame('expired', ETransferService::state($this->i(['status' => 'abandoned', 'customer_sent_at' => '2026-10-21 10:00'])));
        $this->assertSame('received', ETransferService::state($this->i(['status' => 'succeeded', 'confirmed_at' => 'x', 'customer_sent_at' => 'y'])), 'late money still shows as received');
    }
}
