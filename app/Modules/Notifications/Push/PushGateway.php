<?php

namespace App\Modules\Notifications\Push;

interface PushGateway
{
    /**
     * @param array<string,scalar|null> $data
     * @return array{ok:bool,invalid:bool,error:?string} invalid=true means the token is dead and should be forgotten
     */
    public function send(string $token, string $title, string $body, array $data = []): array;
}
