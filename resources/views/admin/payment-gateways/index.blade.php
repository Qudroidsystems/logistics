{{-- resources/views/admin/payment-gateways/index.blade.php — admins enter gateway keys here --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Payment Gateways" icon="ri-bank-card-line"
               subtitle="Enter each gateway's test and live keys. Keys are encrypted and never shown again in full.">
        @if(Route::has('online-fees.index'))
            <x-slot:actions>
                <a class="cb-hero-btn" href="{{ route('online-fees.index') }}"><i class="ri-secure-payment-line"></i>Online payments</a>
            </x-slot:actions>
        @endif
    </x-cb.hero>

    <div id="pgFlash"></div>
    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach

    <div class="cb-banner info">
        <i class="ri-shield-keyhole-line"></i>
        <div><strong>Sandbox</strong> uses the test keys and moves no real money. Switch a gateway to <strong>Live</strong> only after its live keys pass the test.
            Leave a key box empty to keep the saved key.</div>
    </div>

    @foreach($gateways as $g)
        @php
            $def    = $g->catalog();
            $fields = $def['fields'] ?? [];
            $sets   = ['test' => 'Test keys (Sandbox)', 'live' => 'Live keys'];
        @endphp
        <div class="cb-card pg-card" id="pg-{{ $g->id }}" data-id="{{ $g->id }}">
            <div class="cb-card-header">
                <h5><i class="ri-bank-card-2-line"></i>{{ $def['name'] ?? $g->name }}
                    @if(!empty($def['supported']))
                        <span class="status-pill st-info ms-2">{{ $def['used_for'] ?? 'In use' }}</span>
                    @else
                        <span class="status-pill st-muted ms-2">Not used for payments yet</span>
                    @endif
                </h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="status-pill pg-state {{ $g->is_active ? 'st-paid' : 'st-muted' }}">{{ $g->is_active ? 'On' : 'Off' }}</span>
                    <span class="status-pill pg-mode {{ $g->mode === 'live' ? 'st-danger' : 'st-warning' }}">{{ $g->mode === 'live' ? 'Live' : 'Sandbox' }}</span>
                    @if(!empty($def['dashboard']))
                        <a class="action-btn btn-open" href="{{ $def['dashboard'] }}" target="_blank" rel="noopener noreferrer"><i class="ri-external-link-line"></i>Get keys</a>
                    @endif
                </div>
            </div>

            <form class="cb-card-body pg-form" action="{{ route('admin.payment-gateways.update', $g->id) }}" method="POST" autocomplete="off">
                @csrf @method('PUT')

                <div class="d-flex flex-wrap gap-4 align-items-center mb-3">
                    <div>
                        <label class="form-label d-block mb-1">Mode</label>
                        <div class="btn-group" role="group" aria-label="Mode">
                            <input type="radio" class="btn-check" name="mode" value="sandbox" id="m-s-{{ $g->id }}" @checked($g->mode !== 'live')>
                            <label class="btn btn-outline-warning btn-sm" for="m-s-{{ $g->id }}">Sandbox (test)</label>
                            <input type="radio" class="btn-check" name="mode" value="live" id="m-l-{{ $g->id }}" @checked($g->mode === 'live')>
                            <label class="btn btn-outline-danger btn-sm" for="m-l-{{ $g->id }}">Live</label>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="a-{{ $g->id }}" @checked($g->is_active)>
                        <label class="form-check-label" for="a-{{ $g->id }}">Accept payments through {{ $def['name'] ?? $g->name }}</label>
                    </div>
                </div>

                <div class="row g-4">
                    @foreach($sets as $set => $title)
                        <div class="col-lg-6">
                            <div class="pg-set">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">{{ $title }}</h6>
                                    <span class="status-pill pg-conf-{{ $set }} {{ $g->isConfigured($set) ? 'st-paid' : 'st-muted' }}">{{ $g->isConfigured($set) ? 'Complete' : 'Not set' }}</span>
                                </div>
                                @foreach($fields as $field => $f)
                                    @php
                                        $saved = $g->maskedCredential($field, $set);
                                        $ph = $saved ? 'Saved: ' . $saved . ' — leave empty to keep' : (isset($f['prefix'][$set]) ? $f['prefix'][$set] . '…' : 'Not set');
                                        $id = "c-{$g->id}-{$set}-{$field}";
                                    @endphp
                                    <div class="mb-2">
                                        <label class="form-label mb-1" for="{{ $id }}">{{ $f['label'] }}@if(!empty($f['required']))<span class="text-danger">*</span>@endif</label>
                                        <div class="input-group input-group-sm">
                                            <input type="{{ !empty($f['secret']) ? 'password' : 'text' }}" class="form-control pg-input" id="{{ $id }}"
                                                   name="credentials[{{ $set }}][{{ $field }}]" placeholder="{{ $ph }}" data-field="{{ $field }}" data-set="{{ $set }}"
                                                   autocomplete="new-password" spellcheck="false">
                                            @if(!empty($f['secret']))
                                                <button class="btn btn-outline-secondary pg-eye" type="button" aria-label="Show what you typed"><i class="ri-eye-line"></i></button>
                                            @endif
                                        </div>
                                        @if($saved)
                                            <div class="form-check form-check-inline small mt-1">
                                                <input class="form-check-input" type="checkbox" name="clear[{{ $set }}][{{ $field }}]" value="1" id="x-{{ $id }}">
                                                <label class="form-check-label text-muted" for="x-{{ $id }}">Remove saved {{ strtolower($f['label']) }}</label>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                <button type="button" class="action-btn btn-go mt-2 pg-test" data-set="{{ $set }}"><i class="ri-flask-line"></i>Test {{ $set === 'live' ? 'live' : 'test' }} keys</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($g->provider_key === 'opay')
                    <div class="pg-urls mt-4">
                        <h6 class="mb-2"><i class="ri-links-line me-1"></i>OPay callback URL</h6>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text" style="min-width:110px">Callback URL</span>
                            <input type="text" class="form-control" value="{{ $urls['opay_webhook'] }}" readonly>
                            <button class="btn btn-outline-secondary pg-copy" type="button" data-copy="{{ $urls['opay_webhook'] }}"><i class="ri-file-copy-line"></i> Copy</button>
                        </div>
                        <div class="small text-muted">The portal sends this URL with every payment, so you don't have to set it in the OPay dashboard. Use the keys from OPay Merchant › Developer (test keys while in Sandbox).</div>
                    </div>
                @endif

                @if($g->provider_key === 'paystack')
                    <div class="pg-urls mt-4">
                        <h6 class="mb-2"><i class="ri-links-line me-1"></i>Paste these into your Paystack dashboard (Settings › API Keys &amp; Webhooks)</h6>
                        @foreach(['Webhook URL' => $urls['paystack_webhook'], 'Callback URL' => $urls['paystack_callback']] as $lbl => $u)
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text" style="min-width:110px">{{ $lbl }}</span>
                                <input type="text" class="form-control" value="{{ $u }}" readonly>
                                <button class="btn btn-outline-secondary pg-copy" type="button" data-copy="{{ $u }}"><i class="ri-file-copy-line"></i> Copy</button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="action-btn btn-primary-cb"><i class="ri-save-3-line"></i><span>Save {{ $def['name'] ?? $g->name }}</span></button>
                </div>
            </form>
        </div>
    @endforeach

</div>
</div>
</div>

<style>
.pg-set { border: 1px solid var(--cb-border); border-radius: var(--cb-radius-sm); padding: 14px 16px; background: var(--cb-surface-2); height: 100%; }
.pg-set h6 { font-weight: 700; color: var(--cb-heading); }
.pg-input { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; }
.pg-urls { border-top: 1px dashed var(--cb-border); padding-top: 14px; }
.pg-urls input { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
</style>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const testUrl = @json(route('admin.payment-gateways.test', ['gateway' => '__ID__']));
    const flash = document.getElementById('pgFlash');

    function say(ok, msg) {
        flash.innerHTML = '';
        const d = document.createElement('div');
        d.className = 'cb-banner ' + (ok ? 'info' : 'warning');
        d.innerHTML = '<i class="' + (ok ? 'ri-checkbox-circle-line' : 'ri-error-warning-line') + '"></i><div></div>';
        d.querySelector('div').textContent = msg;
        flash.appendChild(d);
        flash.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function paint(card, g) {
        if (!g) return;
        const st = card.querySelector('.pg-state');
        st.textContent = g.is_active ? 'On' : 'Off';
        st.className = 'status-pill pg-state ' + (g.is_active ? 'st-paid' : 'st-muted');
        const md = card.querySelector('.pg-mode');
        md.textContent = g.mode === 'live' ? 'Live' : 'Sandbox';
        md.className = 'status-pill pg-mode ' + (g.mode === 'live' ? 'st-danger' : 'st-warning');
        ['test', 'live'].forEach(set => {
            const p = card.querySelector('.pg-conf-' + set);
            const ok = g[set + '_configured'];
            p.textContent = ok ? 'Complete' : 'Not set';
            p.className = 'status-pill pg-conf-' + set + ' ' + (ok ? 'st-paid' : 'st-muted');
        });
        card.querySelectorAll('.pg-input').forEach(i => {
            const m = g.masked?.[i.dataset.set]?.[i.dataset.field];
            i.value = '';
            if (m) i.placeholder = 'Saved: ' + m + ' — leave empty to keep';
        });
        card.querySelectorAll('input[name^="clear["]').forEach(c => { c.checked = false; });
    }

    document.querySelectorAll('.pg-form').forEach(form => {
        const card = form.closest('.pg-card');
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const j = await res.json().catch(() => ({}));
                const msg = j.message || (j.errors ? Object.values(j.errors).flat().join(' ') : 'Could not save.');
                say(res.ok && j.success, msg);
                if (res.ok && j.success) paint(card, j.gateway);
            } catch (err) {
                say(false, 'Could not save. Check your connection.');
            } finally {
                btn.disabled = false;
            }
        });

        form.querySelectorAll('.pg-test').forEach(b => b.addEventListener('click', async () => {
            b.disabled = true;
            const old = b.innerHTML;
            b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing…';
            try {
                const res = await fetch(testUrl.replace('__ID__', card.dataset.id), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ set: b.dataset.set }),
                });
                const j = await res.json().catch(() => ({}));
                say(!!j.success, j.message || 'Test failed.');
            } catch (err) {
                say(false, 'Test failed. Check your connection.');
            } finally {
                b.disabled = false; b.innerHTML = old;
            }
        }));

        form.querySelectorAll('.pg-eye').forEach(eye => eye.addEventListener('click', () => {
            const i = eye.parentElement.querySelector('input');
            i.type = i.type === 'password' ? 'text' : 'password';
        }));
    });

    document.querySelectorAll('.pg-copy').forEach(b => b.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(b.dataset.copy); b.innerHTML = '<i class="ri-check-line"></i> Copied'; }
        catch (e) { b.previousElementSibling.select(); }
        setTimeout(() => { b.innerHTML = '<i class="ri-file-copy-line"></i> Copy'; }, 1500);
    }));
})();
</script>
@endsection
