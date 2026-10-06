<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/** Single-row config for talking to the remote control portal. */
class FeatureSync extends Model
{
    protected $table = 'feature_sync';

    protected $fillable = ['api_key', 'remote_url', 'remote_key', 'auto_pull', 'last_pulled_at', 'last_pull_status', 'last_pushed_at'];

    protected $casts = [
        'auto_pull' => 'boolean',
        'last_pulled_at' => 'datetime',
        'last_pushed_at' => 'datetime',
    ];

    protected $hidden = ['api_key', 'remote_key'];

    public static function current(): self
    {
        if (!Schema::hasTable('feature_sync')) {
            return new self();
        }
        return self::query()->firstOrCreate(['id' => 1]);
    }

    // Secrets are stored encrypted.
    public function apiKey(): ?string { return $this->decrypt($this->attributes['api_key'] ?? null); }
    public function remoteKey(): ?string { return $this->decrypt($this->attributes['remote_key'] ?? null); }

    public function setApiKey(?string $v): void { $this->attributes['api_key'] = $this->encrypt($v); }
    public function setRemoteKey(?string $v): void { $this->attributes['remote_key'] = $this->encrypt($v); }

    protected function encrypt(?string $v): ?string
    {
        $v = $v !== null ? trim($v) : null;
        return ($v === null || $v === '') ? null : Crypt::encryptString($v);
    }

    protected function decrypt(?string $v): ?string
    {
        if (!$v) return null;
        try { return Crypt::decryptString($v); } catch (\Throwable $e) { return null; }
    }

    public function maskedApiKey(): ?string
    {
        $k = $this->apiKey();
        return $k ? str_repeat('•', max(0, strlen($k) - 4)) . substr($k, -4) : null;
    }
}
