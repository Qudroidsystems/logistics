<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessagingSetting;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin > Messaging: which provider sends SMS (Termii, Twilio), WhatsApp (Meta Cloud API) and email notices, their
 * credentials (secrets are stored encrypted and shown masked), whether each channel is on, and a test send.
 * Delivery texts follow services.sms (SMS_DRIVER); WhatsApp delivery notices use the WhatsApp settings here.
 */
class MessagingSettingsController extends Controller
{
    public const CHANNELS = ['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'];

    public function __construct()
    {
        $this->middleware('permission:Manage notifications settings');
    }

    public function index(MessagingService $messaging)
    {
        $channels = [];
        foreach (array_keys(self::CHANNELS) as $c) {
            $s = MessagingSetting::for($c);
            $channels[$c] = ['setting' => $s, 'drivers' => MessagingSetting::DRIVERS[$c], 'problem' => $messaging->whyNotReady($c)];
        }

        return view('admin.messaging.index', [
            'channels' => $channels, 'labels' => self::CHANNELS,
            'smsDriver' => config('services.sms.driver'), 'whatsappMode' => config('services.sms.whatsapp'),
            'pagetitle' => 'Messaging',
        ]);
    }

    public function save(Request $request, string $channel)
    {
        abort_unless(isset(self::CHANNELS[$channel]), 404);
        $drivers = MessagingSetting::DRIVERS[$channel];
        $d = $request->validate(['driver' => ['required', Rule::in(array_keys($drivers))], 'is_active' => 'nullable|boolean', 'config' => 'nullable|array']);

        $s = MessagingSetting::for($channel);
        $s->driver = $d['driver'];
        $s->is_active = $request->boolean('is_active');
        foreach ($s->fields() as $field => $def) {
            $value = $d['config'][$field] ?? null;
            // A secret left blank keeps the saved one, so the form never has to show it.
            if (($def['secret'] ?? false) && ($value === null || $value === '')) {
                continue;
            }
            $s->put($field, $value);
        }
        $s->updated_by = $request->user()->id;
        $s->save();

        return redirect()->route('admin.messaging.index')->with('success', self::CHANNELS[$channel].' settings saved.');
    }

    public function test(Request $request, string $channel, MessagingService $messaging)
    {
        abort_unless(isset(self::CHANNELS[$channel]), 404);
        $d = $request->validate(['to' => ['required', 'string', 'max:190']]);
        $to = $channel === 'email' ? $d['to'] : MessagingService::normalizePhone($d['to']);
        if (! $to) {
            return back()->with('error', 'That does not look like a phone number. Try '.config('region.phone_example').'.');
        }
        $r = $messaging->send($channel, $to, 'Test message from '.config('app.name').'. If you can read this, '.self::CHANNELS[$channel].' is working.',
            ['name' => $request->user()->name, 'subject' => 'Test message']);

        return back()->with(($r['status'] ?? '') === 'sent' ? 'success' : 'error', ($r['status'] ?? '') === 'sent'
            ? 'Sent. Check '.($channel === 'email' ? 'the inbox' : 'the phone').'.'
            : 'Not sent: '.($r['error'] ?? 'unknown error'));
    }
}
