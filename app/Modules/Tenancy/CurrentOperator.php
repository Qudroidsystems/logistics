<?php

namespace App\Modules\Tenancy;

/**
 * Holds the operator the current request or job is acting for.
 *
 * Set by middleware for web/API requests and by queued jobs that carry an operator id.
 * Platform staff run with bypass() so they can see every tenant.
 */
class CurrentOperator
{
    public const PLATFORM_ID = 1;

    private ?int $id = null;
    private bool $bypass = false;

    public function set(?int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function bypass(bool $bypass = true): void
    {
        $this->bypass = $bypass;
    }

    public function bypassed(): bool
    {
        return $this->bypass;
    }

    /** Run a callback with the tenant scope disabled (console, reports, platform tasks). */
    public function withoutScope(callable $callback): mixed
    {
        $previous = $this->bypass;
        $this->bypass = true;
        try {
            return $callback();
        } finally {
            $this->bypass = $previous;
        }
    }
}
