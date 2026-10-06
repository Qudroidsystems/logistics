<?php

namespace App\Services\Messaging;

use App\Mail\NoticeMail;
use App\Models\MessagingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one message on one channel using the settings the admin saved in
 * Notices › Settings. Every method returns
 *   ['status' => sent|failed|skipped, 'error' => ?string, 'id' => ?string, 'retryable' => bool]
 */
class MessagingService
{
    protected array $settings = [];

    public function setting(string $channel): MessagingSetting
    {
        return $this->settings[$channel] ??= MessagingSetting::for($channel);
    }

    /** Use an unsaved copy of a channel's settings (for "Send test" with what's on screen). */
    public function useSetting(string $channel, MessagingSetting $s): void
    {
        $this->settings[$channel] = $s;
    }

    /** Why a channel can't send right now (null = ready). */
    public function whyNotReady(string $channel): ?string
    {
        if ($channel === 'portal') return null;
        $s = $this->setting($channel);
        $name = $channel === 'sms' ? 'SMS' : ucfirst($channel);
        if (!$s->is_active) return "{$name} is switched off. In Notification Settings, turn on the \"On\" switch in the {$name} card and click Save.";
        $missing = []; $unreadable = [];
        foreach ($s->fields() as $f => $def) {
            if (empty($def['required']) || $s->value($f)) continue;
            $raw = $s->config[$f] ?? null;
            if (is_string($raw) && str_starts_with($raw, 'enc:')) $unreadable[] = $def['label'];
            else $missing[] = $def['label'];
        }
        if ($unreadable) return "The saved " . implode(', ', $unreadable) . " can't be read (the app key changed since it was saved). Type it in again and save.";
        if ($missing) return "{$name} is on but these are missing: " . implode(', ', $missing) . '. Fill them in and save.';
        return null;
    }

    /** Channels switched on (log driver counts: it records instead of sending). */
    public function enabled(string $channel): bool
    {
        if ($channel === 'portal') {
            return PortalNotifier::available(); // in-portal bell: free, always on
        }
        $s = $this->setting($channel);
        return $s->is_active && $s->isConfigured();
    }

    public function send(string $channel, string $to, string $body, array $context = []): array
    {
        if (!$this->enabled($channel)) {
            return $this->result('skipped', $this->whyNotReady($channel) ?? (ucfirst($channel) . ' is not ready.'));
        }

        try {
            return match ($channel) {
                'sms'      => $this->sms($to, $body),
                'whatsapp' => $this->whatsapp($to, $body, $context),
                'email'    => $this->email($to, $body, $context),
                default    => $this->result('skipped', 'Unknown channel'),
            };
        } catch (\Throwable $e) {
            Log::error("Notice {$channel} send error", ['to' => $to, 'error' => $e->getMessage()]);
            return $this->result('failed', 'Could not reach the ' . $channel . ' provider: ' . mb_substr($e->getMessage(), 0, 200), null, true);
        }
    }

    // ── SMS (Termii) ────────────────────────────────────────────────────
    protected function sms(string $phone, string $text): array
    {
        $s = $this->setting('sms');

        if ($s->driver === 'log') {
            Log::info('[Notice SMS – log driver]', ['to' => $phone, 'text' => $text]);
            return $this->result('sent', null, 'log');
        }

        $res = Http::acceptJson()->timeout(30)->post(rtrim($s->value('base_url') ?: 'https://api.ng.termii.com', '/') . '/api/sms/send', [
            'api_key' => $s->value('api_key'),
            'to'      => $phone,
            'from'    => $s->value('sender_id'),
            'sms'     => $text,
            'type'    => 'plain',
            'channel' => $s->value('route') === 'dnd' ? 'dnd' : 'generic',
        ]);
        $body = $res->json() ?? [];

        if ($res->successful() && (strtolower((string) ($body['code'] ?? '')) === 'ok' || !empty($body['message_id']))) {
            return $this->result('sent', null, (string) ($body['message_id'] ?? ''));
        }

        $msg = $body['message'] ?? ('HTTP ' . $res->status());
        return $this->result('failed', 'Termii: ' . (is_string($msg) ? $msg : json_encode($msg)), null, $res->status() >= 500 || $res->status() === 429);
    }

    // ── WhatsApp (Meta Cloud API, approved template) ────────────────────
    protected function whatsapp(string $phone, string $text, array $context): array
    {
        $s = $this->setting('whatsapp');

        if ($s->driver === 'log') {
            Log::info('[Notice WhatsApp – log driver]', ['to' => $phone, 'text' => $text]);
            return $this->result('sent', null, 'log');
        }

        // Template body params may not contain new lines, tabs or 4+ spaces.
        $clean = fn ($v) => trim(preg_replace('/\s{2,}/', ' ', str_replace(["\r", "\n", "\t"], ' ', (string) $v)));

        // Document (e.g. a report card PDF) through the approved document template.
        $docTemplate = trim((string) $s->value('document_template_name'));
        $att = $context['attachment'] ?? null;
        if ($docTemplate !== '' && $att && (!empty($context['wa_media_id']) || is_file($att['path'] ?? ''))) {
            $mediaId = $context['wa_media_id'] ?? null;
            if (!$mediaId) {
                $up = Http::withToken($s->value('access_token'))->acceptJson()->timeout(60)
                    ->attach('file', file_get_contents($att['path']), $att['filename'] ?? 'document.pdf', ['Content-Type' => 'application/pdf'])
                    ->post('https://graph.facebook.com/' . $s->value('api_version') . '/' . $s->value('phone_number_id') . '/media', [
                        'messaging_product' => 'whatsapp',
                        'type'              => 'application/pdf',
                    ]);
                if (!$up->successful() || !$up->json('id')) {
                    return $this->result('failed', 'WhatsApp: could not upload the PDF (' . ($up->json('error.message') ?: 'HTTP ' . $up->status()) . ')', null, $up->status() >= 500);
                }
                $mediaId = (string) $up->json('id');
            }

            $res = Http::withToken($s->value('access_token'))->acceptJson()->timeout(30)
                ->post('https://graph.facebook.com/' . $s->value('api_version') . '/' . $s->value('phone_number_id') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to'       => $phone,
                    'type'     => 'template',
                    'template' => [
                        'name'     => $docTemplate,
                        'language' => ['code' => $s->value('template_lang')],
                        'components' => [
                            ['type' => 'header', 'parameters' => [[
                                'type' => 'document',
                                'document' => ['id' => $mediaId, 'filename' => $att['filename'] ?? 'document.pdf'],
                            ]]],
                            ['type' => 'body', 'parameters' => [
                                ['type' => 'text', 'text' => mb_substr($clean($context['name'] ?? 'Parent'), 0, 60)],
                                ['type' => 'text', 'text' => mb_substr($clean($text), 0, 1000)],
                            ]],
                        ],
                    ],
                ]);
            if ($res->successful()) {
                return $this->result('sent', null, (string) $res->json('messages.0.id')) + ['media_id' => $mediaId];
            }
            $err = $res->json('error') ?? [];
            return $this->result('failed', 'WhatsApp: ' . (($err['error_user_msg'] ?? null) ?: ($err['message'] ?? 'HTTP ' . $res->status()))
                . ' (check the document template: document header + 2 body variables)', null, $res->status() >= 500) + ['media_id' => $mediaId];
        }

        $res = Http::withToken($s->value('access_token'))->acceptJson()->timeout(30)
            ->post('https://graph.facebook.com/' . $s->value('api_version') . '/' . $s->value('phone_number_id') . '/messages', [
                'messaging_product' => 'whatsapp',
                'to'       => $phone,
                'type'     => 'template',
                'template' => [
                    'name'     => $s->value('template_name'),
                    'language' => ['code' => $s->value('template_lang')],
                    'components' => [[
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => mb_substr($clean($context['name'] ?? 'Parent'), 0, 60)],
                            ['type' => 'text', 'text' => mb_substr($clean($text), 0, 1000)],
                        ],
                    ]],
                ],
            ]);

        if ($res->successful()) {
            return $this->result('sent', null, (string) $res->json('messages.0.id'));
        }

        $err  = $res->json('error') ?? [];
        $code = (int) ($err['code'] ?? 0);
        $msg  = ($err['error_user_msg'] ?? null) ?: ($err['message'] ?? ('HTTP ' . $res->status()));
        $hint = match (true) {
            $code === 190                  => ' (access token expired or invalid)',
            in_array($code, [132000, 132001], true) => ' (template name/language not approved or parameter count wrong — the template needs exactly 2 variables)',
            $code === 131026               => ' (number is not on WhatsApp)',
            $code === 131047               => ' (re-engagement window closed — template required)',
            in_array($code, [4, 80007, 130429], true) => ' (rate limited, will retry)',
            default                        => '',
        };
        return $this->result('failed', 'WhatsApp: ' . $msg . $hint, null, in_array($code, [4, 80007, 130429], true) || $res->status() >= 500);
    }

    // ── Email ───────────────────────────────────────────────────────────
    protected function email(string $email, string $text, array $context): array
    {
        $s = $this->setting('email');
        Mail::to($email)->send(new NoticeMail(
            $context['subject'] ?? 'School notice',
            $text,
            $context['name'] ?? null,
            $s->value('from_name'),
            $s->value('reply_to'),
            $context['attachment'] ?? null,
        ));
        return $this->result('sent');
    }

    // ── SMS credit ──────────────────────────────────────────────────────

    /** Termii wallet balance, cached for 5 minutes. null when unknown. */
    public function smsBalance(bool $fresh = false): ?array
    {
        $s = $this->setting('sms');
        if ($s->driver !== 'termii' || !$s->value('api_key')) return null;

        $key = 'termii_balance_' . md5((string) $s->value('api_key'));
        if ($fresh) \Illuminate\Support\Facades\Cache::forget($key);

        return \Illuminate\Support\Facades\Cache::remember($key, 300, function () use ($s) {
            try {
                $res = Http::acceptJson()->timeout(15)->get(rtrim($s->value('base_url') ?: 'https://api.ng.termii.com', '/') . '/api/get-balance', [
                    'api_key' => $s->value('api_key'),
                ]);
                if ($res->successful() && $res->json('balance') !== null) {
                    return ['balance' => (float) $res->json('balance'), 'currency' => (string) ($res->json('currency') ?: 'NGN')];
                }
            } catch (\Throwable $e) {
                Log::warning('Termii balance check failed', ['error' => $e->getMessage()]);
            }
            return null;
        });
    }

    /** Estimated cost of $pages SMS pages, and whether the balance covers it. */
    public function smsEstimate(int $pages): array
    {
        $unit = (float) ($this->setting('sms')->value('unit_cost') ?: 0);
        $bal  = $this->smsBalance();
        $cost = $unit > 0 ? round($unit * $pages, 2) : null;

        return [
            'pages'     => $pages,
            'unit_cost' => $unit ?: null,
            'cost'      => $cost,
            'balance'   => $bal['balance'] ?? null,
            'currency'  => $bal['currency'] ?? 'NGN',
            'enough'    => ($cost === null || $bal === null) ? null : $bal['balance'] >= $cost,
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /** Nigeria-friendly: 0803… / 803… / +234803… → 234803…; null when not a phone. */
    public static function normalizePhone(?string $phone): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        if ($d === '' || $d === null) return null;
        if (str_starts_with($d, '234') && strlen($d) === 13) return $d;
        if (str_starts_with($d, '0') && strlen($d) === 11) return '234' . substr($d, 1);
        if (strlen($d) === 10 && in_array($d[0], ['7', '8', '9'], true)) return '234' . $d;
        return strlen($d) >= 11 && strlen($d) <= 15 ? $d : null; // other countries, already international
    }

    /** SMS pages: GSM-7 = 160 / 153 per page; any other character = 70 / 67. */
    public static function smsPages(string $text): array
    {
        $gsm = '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r" . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
        $ext = '^{}\\[~]|€';
        $unicode = false; $len = 0;
        foreach (mb_str_split($text) as $ch) {
            if (mb_strpos($gsm, $ch) !== false) { $len++; }
            elseif (mb_strpos($ext, $ch) !== false) { $len += 2; }
            else { $unicode = true; break; }
        }
        if ($unicode) {
            $len = mb_strlen($text);
            return ['chars' => $len, 'pages' => $len <= 70 ? 1 : (int) ceil($len / 67), 'unicode' => true];
        }
        return ['chars' => $len, 'pages' => $len <= 160 ? 1 : (int) ceil($len / 153), 'unicode' => false];
    }

    protected function result(string $status, ?string $error = null, ?string $id = null, bool $retryable = false): array
    {
        return ['status' => $status, 'error' => $error, 'id' => $id, 'retryable' => $retryable];
    }
}
