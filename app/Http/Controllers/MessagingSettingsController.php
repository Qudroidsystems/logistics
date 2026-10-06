<?php

namespace App\Http\Controllers;

use App\Models\MessagingSetting;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\PaymentReceiptNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

/**
 * Notices › Settings: SMS (Termii), WhatsApp (Meta Cloud API) and email.
 * Keys are entered here (encrypted at rest), not in .env.
 */
class MessagingSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage notification settings');
    }

    public function index()
    {
        $settings = collect(['sms', 'whatsapp', 'email'])->mapWithKeys(fn ($c) => [$c => MessagingSetting::for($c)]);

        return view('notices.settings', [
            'pagetitle' => 'Notification Settings',
            'settings'  => $settings,
            'drivers'   => MessagingSetting::DRIVERS,
            'mailFrom'  => config('mail.from.address'),
            'mailer'    => config('mail.default'),
            'receipts'  => PaymentReceiptNotifier::settings(),
            'smsBalance' => app(MessagingService::class)->smsBalance(request()->boolean('refresh_balance')),
            'receiptPlaceholders' => PaymentReceiptNotifier::PLACEHOLDERS,
            'recentReceipts' => Schema::hasTable('payment_receipt_messages')
                ? DB::table('payment_receipt_messages')->orderByDesc('id')->limit(10)->get()
                : collect(),
        ]);
    }

    public function update(Request $request, string $channel)
    {
        abort_unless(isset(MessagingSetting::DRIVERS[$channel]), 404);
        $s = MessagingSetting::for($channel);

        $data = $request->validate([
            'driver'    => 'required|in:' . implode(',', array_keys(MessagingSetting::DRIVERS[$channel])),
            'is_active' => 'nullable|boolean',
            'fields'    => 'nullable|array',
            'fields.*'  => 'nullable|string|max:2000',
            'clear'     => 'nullable|array',
        ]);

        $s->driver = $data['driver'];
        foreach ($s->fields() as $f => $def) {
            if (!empty($data['clear'][$f])) { $s->put($f, null); continue; }
            if (!array_key_exists($f, $data['fields'] ?? [])) continue;
            $v = trim((string) ($data['fields'][$f] ?? ''));
            if ($v === '' && !empty($def['secret'])) continue; // empty secret box = keep saved key
            if (!empty($def['max']) && mb_strlen($v) > $def['max']) {
                return back()->with('error', "{$def['label']} must be at most {$def['max']} characters.")->withFragment($channel);
            }
            if (!empty($def['options']) && !isset($def['options'][$v])) continue;
            $s->put($f, $v);
        }
        $s->is_active  = (bool) ($data['is_active'] ?? false);
        $s->updated_by = auth()->id();

        if ($s->is_active && !$s->isConfigured()) {
            return back()->with('error', 'Fill in all required ' . strtoupper($channel) . ' fields before switching it on.')->withFragment($channel);
        }
        $s->save();

        return back()->with('success', ucfirst($channel) . ' settings saved.')->withFragment($channel);
    }

    /** Automatic payment receipts to parents. */
    public function receipts(Request $request)
    {
        $d = $request->validate([
            'is_active'  => 'nullable|boolean',
            'channels'   => 'nullable|array',
            'channels.*' => 'in:sms,whatsapp,email',
            'message'    => 'required|string|max:2000',
            'sms_text'   => 'nullable|string|max:459',
        ]);
        $s = PaymentReceiptNotifier::settings();
        $s->config = [
            'channels' => array_values(array_unique($d['channels'] ?? [])),
            'message'  => $d['message'],
            'sms_text' => $d['sms_text'] ?? null,
        ];
        $s->is_active  = $request->boolean('is_active');
        $s->updated_by = auth()->id();

        if ($s->is_active && empty($s->config['channels'])) {
            return back()->with('error', 'Pick at least one channel for payment receipts.')->withFragment('receipts');
        }
        $s->save();

        return back()->with('success', 'Payment receipt settings saved.')->withFragment('receipts');
    }

    /** Send a real test message to the given phone/email with the saved settings. */
    public function test(Request $request, string $channel, MessagingService $messaging)
    {
        abort_unless(isset(MessagingSetting::DRIVERS[$channel]), 404);
        $to = trim((string) $request->input('to'));

        if ($channel === 'email') {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return response()->json(['success' => false, 'message' => 'Enter a valid email address.']);
        } else {
            $to = MessagingService::normalizePhone($to);
            if (!$to) return response()->json(['success' => false, 'message' => 'Enter a valid phone number, e.g. 08031234567.']);
        }

        // Test with what's on screen (even if not saved yet): an unsaved copy of the settings.
        if ($request->filled('driver') && isset(MessagingSetting::DRIVERS[$channel][$request->input('driver')])) {
            $tmp = MessagingSetting::for($channel)->replicate();
            $tmp->driver = $request->input('driver');
            foreach ($tmp->fields() as $f => $def) {
                $v = trim((string) data_get($request->input('fields', []), $f, ''));
                if ($v !== '') $tmp->put($f, $v);          // blank secret box = keep the saved key
            }
            $tmp->is_active = true;                         // a test always tries to send
            $messaging->useSetting($channel, $tmp);
            if ($why = $messaging->whyNotReady($channel)) return response()->json(['success' => false, 'message' => $why]);
        }

        $res = $messaging->send($channel, $to, 'This is a test message from the school portal. If you received it, ' . strtoupper($channel) . ' notifications are working.', [
            'name' => $request->user()->name, 'subject' => 'Test notification',
        ]);

        $driver = $messaging->setting($channel)->driver;
        $saved = MessagingSetting::for($channel);
        $note = !$saved->is_active ? ' Note: it is still switched OFF — turn on the switch and click Save so the portal uses it.' : '';
        return response()->json([
            'success' => $res['status'] === 'sent',
            'message' => match ($res['status']) {
                'sent'    => ($driver === 'log' ? 'Logged only (provider is "Log only") — check storage/logs/laravel.log.' : 'Sent to ' . $to . '. Check the device/inbox.') . $note,
                'skipped' => $res['error'],
                default   => $res['error'] ?: 'Failed.',
            },
        ]);
    }
}
