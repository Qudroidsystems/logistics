<?php

namespace App\Modules\Booking;

use RuntimeException;

/** The quote ran out (or the pickup time changed) and the new price differs: show the customer the new one. */
class PriceChanged extends RuntimeException
{
    public function __construct(public array $quote)
    {
        parent::__construct('The price changed to '.$quote['total_text'].'. Check it and book again.');
    }
}
