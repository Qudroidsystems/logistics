<?php

namespace App\Modules\Verification;

use RuntimeException;

/** The driver typed a code that does not match. Carries the stop so the failed try can be counted after a rollback. */
class WrongDeliveryCode extends RuntimeException
{
    public function __construct(public readonly int $stopId, public readonly int $attemptsLeft)
    {
        parent::__construct($attemptsLeft > 0
            ? "Delivery code does not match. {$attemptsLeft} ".($attemptsLeft === 1 ? 'try' : 'tries').' left.'
            : 'Delivery code does not match. The code is now locked for '.DeliveryCodes::LOCK_MINUTES.' minutes.');
    }
}
