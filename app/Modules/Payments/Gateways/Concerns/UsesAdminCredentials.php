<?php

namespace App\Modules\Payments\Gateways\Concerns;

use App\Models\PaymentGateway;
use App\Support\PaymentGatewayCatalog;

/**
 * Reads a gateway's keys from Finance > Payment Gateways (payment_gateways row), by mode.
 * The row must be switched on; .env is only a fallback where a gateway defines one.
 */
trait UsesAdminCredentials
{
    private ?PaymentGateway $row = null;
    private bool $rowLoaded = false;

    abstract public function key(): string;

    protected function row(): ?PaymentGateway
    {
        if (! $this->rowLoaded) {
            $this->row = PaymentGateway::where('provider_key', $this->key())->first();
            $this->rowLoaded = true;
        }

        return $this->row;
    }

    /** "test" or "live", the credential set in use. */
    protected function set(): string
    {
        return PaymentGatewayCatalog::set($this->row()?->mode);
    }

    protected function isLive(): bool
    {
        return $this->set() === 'live';
    }

    protected function credential(string $field): ?string
    {
        return $this->row()?->credential($field, $this->set());
    }

    protected function rowActive(): bool
    {
        return (bool) $this->row()?->is_active;
    }

    /** Admin-managed gateways: on, and every required key present. */
    protected function adminReady(): bool
    {
        $r = $this->row();

        return $r && $r->is_active && $r->isConfigured($this->set());
    }
}
