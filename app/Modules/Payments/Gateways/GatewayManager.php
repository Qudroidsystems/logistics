<?php

namespace App\Modules\Payments\Gateways;

use RuntimeException;

/** Picks the gateway for a payment. Only gateways that are switched on and fully configured are offered. */
class GatewayManager
{
    /** @var array<string, class-string<CollectionGateway>> */
    private const MAP = [
        'paystack' => PaystackGateway::class,
        'opay' => OpayGateway::class,
        'monnify' => MonnifyGateway::class,
        'stripe' => StripeGateway::class,
    ];

    public function get(string $key): CollectionGateway
    {
        if (! isset(self::MAP[$key])) {
            throw new RuntimeException('Unknown payment method.');
        }

        return app(self::MAP[$key]);
    }

    /** @return array<int, CollectionGateway> */
    public function ready(): array
    {
        $out = [];
        foreach (array_keys(self::MAP) as $key) {
            $g = $this->get($key);
            if ($g->isReady()) {
                $out[] = $g;
            }
        }

        return $out;
    }

    /** The customer's choice if it is available, otherwise the configured default, otherwise the first ready one. */
    public function choose(?string $key): CollectionGateway
    {
        $ready = collect($this->ready())->keyBy(fn (CollectionGateway $g) => $g->key());
        if ($ready->isEmpty()) {
            throw new RuntimeException('Online payment is not available right now. Pay from your wallet or try again later.');
        }
        if ($key && ! $ready->has($key)) {
            throw new RuntimeException('That payment method is not available right now.');
        }

        return $ready->get($key ?: (string) config('payments.default', 'paystack')) ?? $ready->first();
    }

    /** For the apps: the methods to show on the pay screen. */
    public function options(): array
    {
        $default = $this->choose(null)->key();

        return collect($this->ready())->map(fn (CollectionGateway $g) => ['key' => $g->key(), 'name' => $g->label(), 'default' => $g->key() === $default])->values()->all();
    }
}
