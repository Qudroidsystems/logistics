{{-- resources/views/notices/settings.blade.php — SMS / WhatsApp / email provider settings --}}
@extends('layouts.master')

@section('content')
@php
    $titles = ['sms' => ['SMS', 'ri-message-2-line', 'Termii sends to all Nigerian networks. Register a Sender ID in your Termii dashboard; use the DND route so numbers on Do-Not-Disturb still receive messages.'],
               'whatsapp' => ['WhatsApp', 'ri-whatsapp-line', 'Uses the official WhatsApp Cloud API. Meta only allows pre-approved templates for messages you start — see the template to submit below.'],
               'email' => ['Email', 'ri-mail-line', 'Uses the school mail server configured for the portal (MAIL_* settings).']];
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Notification Settings" icon="ri-settings-3-line"
               subtitle="Providers used to send notices. Keys are encrypted and never shown in full."
               :back="route('notices.index')" back-label="Notices" />

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach

    @foreach($settings as $channel => $s)
        @php [$title, $icon, $help] = $titles[$channel]; @endphp
        <div class="cb-card" id="{{ $channel }}">
            <div class="cb-card-header">
                <h5><i class="{{ $icon }}"></i>{{ $title }}</h5>
                <span class="status-pill {{ !$s->is_active ? 'st-muted' : ($s->isLive() ? 'st-paid' : 'st-warning') }}">{{ !$s->is_active ? 'Off' : ($s->isLive() ? 'On' : ($s->driver === 'log' ? 'Log only' : 'Incomplete')) }}</span>
            </div>
            <div class="cb-card-body">
                <p class="small text-muted">{{ $help }}</p>
                @if($channel === 'sms' && $s->driver === 'termii')
                    <div class="cb-banner {{ $smsBalance ? 'info' : 'warning' }}"><i class="ri-wallet-3-line"></i>
                        <div>@if($smsBalance) Termii balance: <strong>{{ $smsBalance['currency'] }} {{ number_format($smsBalance['balance'], 2) }}</strong>
                            @if($s->value('unit_cost')) (about {{ number_format(floor($smsBalance['balance'] / max(0.01, (float) $s->value('unit_cost')))) }} SMS pages)@endif
                            @else Could not read the Termii balance — check the API key. @endif
                            <a href="{{ route('notices.settings', ['refresh_balance' => 1]) }}#sms" class="ms-2">Refresh</a></div></div>
                @endif

                <form method="POST" action="{{ route('notices.settings.update', $channel) }}" autocomplete="off" class="st-form">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="drv-{{ $channel }}">Provider</label>
                            <select class="form-select st-driver" name="driver" id="drv-{{ $channel }}">
                                @foreach($drivers[$channel] as $key => $d)
                                    <option value="{{ $key }}" @selected($s->driver === $key)>{{ $d['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="on-{{ $channel }}" @checked($s->is_active)>
                                <label class="form-check-label" for="on-{{ $channel }}">Send {{ $title }} notices</label>
                            </div>
                        </div>

                        @foreach($drivers[$channel] as $key => $d)
                            <div class="col-12 st-fields" data-driver="{{ $key }}" @if($s->driver !== $key) hidden @endif>
                                <div class="row g-3">
                                    @foreach($d['fields'] as $f => $def)
                                        @php
                                            $saved = $s->driver === $key ? $s->masked($f) : null;
                                            $id = "f-{$channel}-{$key}-{$f}";
                                        @endphp
                                        <div class="col-md-6">
                                            <label class="form-label" for="{{ $id }}">{{ $def['label'] }}@if(!empty($def['required']))<span class="text-danger">*</span>@endif</label>
                                            @if(!empty($def['options']))
                                                <select class="form-select" name="fields[{{ $f }}]" id="{{ $id }}" @disabled($s->driver !== $key)>
                                                    @foreach($def['options'] as $ov => $ol)<option value="{{ $ov }}" @selected(($s->driver === $key ? $s->value($f) : null) === $ov)>{{ $ol }}</option>@endforeach
                                                </select>
                                            @else
                                                <input class="form-control" id="{{ $id }}" name="fields[{{ $f }}]" type="{{ !empty($def['secret']) ? 'password' : 'text' }}"
                                                       @if(empty($def['secret']) && $saved) value="{{ $saved }}" @endif
                                                       placeholder="{{ !empty($def['secret']) && $saved ? 'Saved: ' . $saved . ' — leave empty to keep' : ($def['default'] ?? '') }}"
                                                       @if(!empty($def['max'])) maxlength="{{ $def['max'] }}" @endif
                                                       autocomplete="new-password" spellcheck="false" @disabled($s->driver !== $key)>
                                                @if(!empty($def['secret']) && $saved)
                                                    <div class="form-check small mt-1"><input class="form-check-input" type="checkbox" name="clear[{{ $f }}]" value="1" id="x-{{ $id }}"><label class="form-check-label text-muted" for="x-{{ $id }}">Remove saved value</label></div>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($channel === 'email')
                                        <div class="col-12"><small class="text-muted">Mailer: <strong>{{ $mailer }}</strong> · From: <strong>{{ $mailFrom ?: 'not set' }}</strong>. Change these in the server's mail settings if emails don't arrive.</small></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($channel === 'whatsapp')
                        <details class="mt-3">
                            <summary class="fw-semibold">Template to submit in WhatsApp Manager</summary>
                            <div class="small mt-2">
                                Create a <strong>Utility</strong> template named <code>school_notice</code> (language English), with this body — exactly two variables:
                                <pre class="nt-pre">Dear @{{1}},

@{{2}}

— Sent via the school portal</pre>
                                Variable 1 is the parent's name and variable 2 is the notice text. Once Meta approves it, enter its name and language above.
                            </div>
                            <div class="small mt-3">
                                <strong>For report cards (optional):</strong> create a second <strong>Utility</strong> template named <code>result_ready</code> with a
                                <strong>Document</strong> header and this body (two variables):
                                <pre class="nt-pre">Dear @{{1}},

@{{2}}</pre>
                                Enter its name in "Document template name" above. Without it, WhatsApp sends the secure download link instead of the PDF.
                            </div>
                        </details>
                    @endif

                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                        <div class="d-flex gap-2 align-items-center st-test">
                            <input class="form-control form-control-sm" style="max-width:220px" placeholder="{{ $channel === 'email' ? 'you@example.com' : '0803 123 4567' }}" aria-label="Send a test to">
                            <button type="button" class="action-btn btn-go" data-channel="{{ $channel }}"><i class="ri-flask-line"></i>Send test</button>
                        </div>
                        <button class="action-btn btn-primary-cb"><i class="ri-save-3-line"></i>Save {{ $title }}</button>
                    </div>
                    <div class="small mt-2 st-result"></div>
                </form>
            </div>
        </div>
    @endforeach

    @php $rc = $receipts->config ?? []; @endphp
    <div class="cb-card" id="receipts">
        <div class="cb-card-header">
            <h5><i class="ri-receipt-line"></i>Payment receipts to parents</h5>
            <span class="status-pill {{ $receipts->is_active ? 'st-paid' : 'st-muted' }}">{{ $receipts->is_active ? 'On' : 'Off' }}</span>
        </div>
        <div class="cb-card-body">
            <p class="small text-muted">When a payment is recorded — at the bursary (single or bulk) or online through Paystack — parents automatically get a receipt with the amount, reference and the term's remaining balance. Each payment is sent once per contact.</p>
            <form method="POST" action="{{ route('notices.settings.receipts') }}">
                @csrf @method('PUT')
                <div class="d-flex flex-wrap gap-4 align-items-center mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="rc-on" @checked($receipts->is_active)>
                        <label class="form-check-label" for="rc-on">Send payment receipts</label>
                    </div>
                    @foreach(['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $c => $l)
                        <label class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="{{ $c }}" @checked(in_array($c, $rc['channels'] ?? []))>
                            <span class="form-check-label">{{ $l }}@if(!$settings[$c]->is_active) <small class="text-muted">(off)</small>@endif</span>
                        </label>
                    @endforeach
                </div>
                <div class="mb-1 d-flex flex-wrap gap-1">
                    @foreach($receiptPlaceholders as $ph => $lbl)<code class="small" title="{{ $lbl }}">{{ $ph }}</code>@endforeach
                </div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="rc-msg">Message (email / WhatsApp)</label>
                        <textarea class="form-control" id="rc-msg" name="message" rows="7" maxlength="2000">{{ $rc['message'] ?? \App\Services\Messaging\PaymentReceiptNotifier::DEFAULT_MESSAGE }}</textarea>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="rc-sms">SMS text</label>
                        <textarea class="form-control" id="rc-sms" name="sms_text" rows="4" maxlength="459">{{ $rc['sms_text'] ?? \App\Services\Messaging\PaymentReceiptNotifier::DEFAULT_SMS }}</textarea>
                        <small class="text-muted">Keep it under 160 characters for a 1-page SMS.</small>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3"><button class="action-btn btn-primary-cb"><i class="ri-save-3-line"></i>Save receipts</button></div>
            </form>

            @if($recentReceipts->isNotEmpty())
                <h6 class="mt-4 mb-2">Recent receipts</h6>
                <div class="table-responsive">
                    <table class="cb-table mb-0">
                        <thead><tr><th>Time</th><th>Reference</th><th>To</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($recentReceipts as $r)
                            <tr>
                                <td><small>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M, g:i a') }}</small></td>
                                <td><code>{{ $r->reference }}</code></td>
                                <td><small>{{ strtoupper($r->channel) === 'WHATSAPP' ? 'WhatsApp' : strtoupper($r->channel) }} · {{ $r->recipient_name }} {{ $r->recipient }}</small></td>
                                <td class="text-end">₦{{ number_format($r->amount, 2) }}</td>
                                <td><span class="status-pill {{ $r->status === 'sent' ? 'st-paid' : ($r->status === 'failed' ? 'st-danger' : 'st-muted') }}">{{ ucfirst($r->status) }}</span>
                                    @if($r->error)<br><small class="text-danger">{{ $r->error }}</small>@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <x-cb.card title="Automatic sending (scheduled notices and reminders)" icon="ri-timer-line">
        <p class="small mb-2">Scheduled notices and reminders are sent by the Laravel scheduler. Add this <strong>one</strong> cron job in cPanel › Cron Jobs, set to run <strong>every minute</strong>:</p>
        <pre class="nt-pre mb-2">* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</pre>
        <p class="small text-muted mb-0">"Send now" works without it. If your host needs the full PHP path, use e.g. <code>/usr/local/bin/php</code> instead of <code>php</code>.</p>
    </x-cb.card>

</div>
</div>
</div>

<style>.nt-pre { background: var(--cb-surface-2); border: 1px solid var(--cb-border); border-radius: 8px; padding: 10px 12px; font-size: 12.5px; white-space: pre-wrap; }</style>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const testUrl = @json(route('notices.settings.test', ['channel' => '__C__']));

    document.querySelectorAll('.st-form').forEach(form => {
        const drv = form.querySelector('.st-driver');
        const sync = () => form.querySelectorAll('.st-fields').forEach(box => {
            const on = box.dataset.driver === drv.value;
            box.hidden = !on;
            box.querySelectorAll('input, select').forEach(i => { i.disabled = !on; });
        });
        drv.addEventListener('change', sync); sync();

        const btn = form.querySelector('.st-test button'), input = form.querySelector('.st-test input'), out = form.querySelector('.st-result');
        btn.addEventListener('click', async () => {
            btn.disabled = true; out.className = 'small mt-2 st-result text-muted'; out.textContent = 'Sending test with the details on screen…';
            const fd = new FormData(form); const fields = {};
            for (const [k, v] of fd.entries()) { const m = k.match(/^fields\[(.+)\]$/); if (m) fields[m[1]] = v; }
            try {
                const r = await fetch(testUrl.replace('__C__', btn.dataset.channel), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ to: input.value, driver: drv.value, fields }),
                });
                const j = await r.json();
                out.className = 'small mt-2 st-result ' + (j.success ? 'text-success' : 'text-danger');
                out.textContent = j.message || (j.success ? 'Sent.' : 'Failed.');
            } catch (e) { out.className = 'small mt-2 st-result text-danger'; out.textContent = 'Test failed.'; }
            btn.disabled = false;
        });
    });
})();
</script>
@endsection
