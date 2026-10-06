<?php
// app/Models/PaymentGateway.php

namespace App\Models;

use App\Support\PaymentGatewayCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Credentials are entered by an admin (Finance › Payment Gateways) and kept in
 * config['credentials'][test|live][field]. Secret fields are encrypted with
 * the app key; use credential() to read them.
 *
 * Older rows kept live keys in the secret_key/public_key columns and test
 * keys in config['test_secret_key'] etc.; credential() still reads those
 * until the admin saves the gateway once.
 */
class PaymentGateway extends Model
{
    use HasFactory;

    protected $table = 'payment_gateways';

    protected $fillable = [
        'name', 'provider_key', 'secret_key', 'public_key', 'mode',
        'fee_percentage', 'fee_fixed', 'config', 'is_active'
    ];

    protected $hidden = ['secret_key', 'config'];

    protected $casts = [
        'config' => 'array',
        'fee_percentage' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected const ENC = 'enc:';

    // ── Scopes ──────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider_key', $provider);
    }

    // ── Accessors ───────────────────────────────────────────────────────
    public function getFormattedFeeAttribute()
    {
        if ($this->fee_percentage > 0) {
            return $this->fee_percentage . '% + ₦' . number_format($this->fee_fixed, 2);
        }
        return '₦' . number_format($this->fee_fixed, 2);
    }

    public function getIsLiveAttribute()
    {
        return $this->mode === 'live';
    }

    public function getIsSandboxAttribute()
    {
        return $this->mode === 'sandbox';
    }

    public function catalog(): array
    {
        return PaymentGatewayCatalog::get($this->provider_key) ?? ['name' => $this->name, 'supported' => false, 'fields' => []];
    }

    // ── Credentials ─────────────────────────────────────────────────────

    /** Plain value of a credential for a set ("test"/"live"; default = current mode). */
    public function credential(string $field, ?string $set = null): ?string
    {
        $set    = $set ?? PaymentGatewayCatalog::set($this->mode);
        $config = $this->config ?? [];
        $stored = $config['credentials'][$set][$field] ?? null;

        if ($stored !== null && $stored !== '') {
            if (str_starts_with($stored, self::ENC)) {
                try {
                    $stored = Crypt::decryptString(substr($stored, strlen(self::ENC)));
                } catch (\Throwable $e) {
                    return null; // APP_KEY changed — admin must re-enter the key
                }
            }
            return $this->real($stored);
        }

        // Legacy locations
        $legacy = [
            $config[$set . '_' . $field] ?? null,                                     // test_secret_key, live_merchant_id…
            $set === 'live' && in_array($field, ['secret_key', 'public_key'], true)
                ? ($this->attributes[$field] ?? null) : null,                          // live keys in columns
            $set === 'test' && in_array($field, ['secret_key', 'public_key'], true)
                ? ($this->attributes[$field] ?? null) : null,                          // some rows put test keys in columns
        ];
        foreach ($legacy as $v) {
            if ($v = $this->real($v)) {
                $prefix = $this->catalog()['fields'][$field]['prefix'][$set] ?? null;
                if (!$prefix || str_starts_with($v, $prefix)) {
                    return $v;
                }
            }
        }
        return null;
    }

    /** Store (or clear with null) a credential. Secret fields are encrypted. */
    public function putCredential(string $field, string $set, ?string $value): void
    {
        $config = $this->config ?? [];
        $value  = $value === null ? null : trim($value);

        if ($value === null || $value === '') {
            unset($config['credentials'][$set][$field]);
        } else {
            $secret = $this->catalog()['fields'][$field]['secret'] ?? true;
            $config['credentials'][$set][$field] = $secret ? self::ENC . Crypt::encryptString($value) : $value;
        }

        // Drop the legacy copy so an old placeholder can never win again.
        unset($config[$set . '_' . $field]);
        $this->config = $config;
    }

    /** "••••b654" for secrets, the value itself otherwise; null when unset. */
    public function maskedCredential(string $field, string $set): ?string
    {
        $v = $this->credential($field, $set);
        if ($v === null) return null;

        $secret = $this->catalog()['fields'][$field]['secret'] ?? true;
        return $secret ? '••••••' . substr($v, -4) : $v;
    }

    /** Every required credential present for a set? */
    public function isConfigured(?string $set = null): bool
    {
        $set = $set ?? PaymentGatewayCatalog::set($this->mode);
        foreach ($this->catalog()['fields'] as $field => $def) {
            if (!empty($def['required']) && !$this->credential($field, $set)) {
                return false;
            }
        }
        return !empty($this->catalog()['fields']);
    }

    /** Ignore empty values and seeder placeholders such as "sk_test_xxxxxxxx". */
    protected function real($v): ?string
    {
        $v = trim((string) $v);
        return ($v === '' || str_contains(strtolower($v), 'xxxx')) ? null : $v;
    }
}
