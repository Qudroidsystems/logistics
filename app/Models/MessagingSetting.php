<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Provider settings per channel (sms / whatsapp / email), entered by an admin
 * in Notices › Settings. Secret values are encrypted with the app key.
 */
class MessagingSetting extends Model
{
    protected $table = 'messaging_settings';
    protected $fillable = ['channel', 'driver', 'is_active', 'config', 'updated_by'];
    protected $hidden = ['config'];
    protected $casts = ['is_active' => 'boolean', 'config' => 'array'];

    protected const ENC = 'enc:';

    /** Field definitions per channel/driver; 'secret' fields are encrypted. */
    public const DRIVERS = [
        'sms' => [
            'log'    => ['label' => 'Log only (testing — nothing is sent)', 'fields' => []],
            'termii' => ['label' => 'Termii', 'fields' => [
                'api_key'   => ['label' => 'API key', 'secret' => true, 'required' => true],
                'sender_id' => ['label' => 'Sender ID (approved by Termii)', 'secret' => false, 'required' => true, 'max' => 11],
                'route'     => ['label' => 'Route', 'secret' => false, 'required' => true, 'options' => ['generic' => 'Generic', 'dnd' => 'DND (delivers to DND numbers; sender ID must be approved for DND)']],
                'base_url'  => ['label' => 'API base URL', 'secret' => false, 'required' => false, 'default' => 'https://api.ng.termii.com'],
                'unit_cost' => ['label' => 'Your cost per SMS page in ₦ (for estimates)', 'secret' => false, 'required' => false],
            ]],
        ],
        'whatsapp' => [
            'log'  => ['label' => 'Log only (testing — nothing is sent)', 'fields' => []],
            'meta' => ['label' => 'WhatsApp Cloud API (Meta)', 'fields' => [
                'access_token'    => ['label' => 'Permanent access token', 'secret' => true, 'required' => true],
                'phone_number_id' => ['label' => 'Phone number ID', 'secret' => false, 'required' => true],
                'template_name'   => ['label' => 'Approved template name', 'secret' => false, 'required' => true, 'default' => 'school_notice'],
                'template_lang'   => ['label' => 'Template language code', 'secret' => false, 'required' => true, 'default' => 'en'],
                'api_version'     => ['label' => 'Graph API version', 'secret' => false, 'required' => true, 'default' => 'v21.0'],
                'document_template_name' => ['label' => 'Document template name (for report cards, optional)', 'secret' => false, 'required' => false, 'default' => ''],
            ]],
        ],
        'email' => [
            'mail' => ['label' => 'School mail server (MAIL_* settings)', 'fields' => [
                'from_name'  => ['label' => 'From name', 'secret' => false, 'required' => false],
                'reply_to'   => ['label' => 'Reply-to address', 'secret' => false, 'required' => false],
            ]],
        ],
    ];

    public static function for(string $channel): self
    {
        return static::firstOrCreate(
            ['channel' => $channel],
            ['driver' => $channel === 'email' ? 'mail' : 'log', 'is_active' => $channel === 'email', 'config' => []]
        );
    }

    public function fields(): array
    {
        return self::DRIVERS[$this->channel][$this->driver]['fields'] ?? [];
    }

    public function value(string $field): ?string
    {
        $v = $this->config[$field] ?? null;
        if ($v === null || $v === '') {
            return self::DRIVERS[$this->channel][$this->driver]['fields'][$field]['default'] ?? null;
        }
        if (is_string($v) && str_starts_with($v, self::ENC)) {
            try {
                return Crypt::decryptString(substr($v, strlen(self::ENC)));
            } catch (\Throwable $e) {
                return null;
            }
        }
        return (string) $v;
    }

    public function put(string $field, ?string $value): void
    {
        $config = $this->config ?? [];
        $value  = $value === null ? null : trim($value);
        $secret = $this->fields()[$field]['secret'] ?? false;

        if ($value === null || $value === '') {
            unset($config[$field]);
        } else {
            $config[$field] = $secret ? self::ENC . Crypt::encryptString($value) : $value;
        }
        $this->config = $config;
    }

    public function masked(string $field): ?string
    {
        $v = $this->value($field);
        if ($v === null) return null;
        return ($this->fields()[$field]['secret'] ?? false) ? '••••••' . substr($v, -4) : $v;
    }

    /** Ready to send real messages? ('log' counts as ready — it just logs.) */
    public function isConfigured(): bool
    {
        foreach ($this->fields() as $f => $def) {
            if (!empty($def['required']) && !$this->value($f)) return false;
        }
        return true;
    }

    public function isLive(): bool
    {
        return $this->is_active && $this->driver !== 'log' && $this->isConfigured();
    }
}
