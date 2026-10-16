<?php

namespace App\Modules\Messaging;

interface CallGateway
{
    /**
     * Ring $phone (international digits) from the shared number. When they pick up, the provider asks our
     * callback who to connect them to.
     *
     * @return array{ok: bool, ref: ?string, error: ?string, provider: string}
     */
    public function call(string $phone): array;
}
