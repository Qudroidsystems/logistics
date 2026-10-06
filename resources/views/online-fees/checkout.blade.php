{{-- resources/views/online-fees/checkout.blade.php
     Pay school fees online (Paystack). Used by the student portal (mode=student)
     and by the bursary paying on a student's behalf (mode=staff). --}}
@extends('layouts.master')

@section('content')
@php
    $k2n      = fn ($k) => '₦' . number_format(((int) $k) / 100, 2);
    $k2v      = fn ($k) => number_format(((int) $k) / 100, 2, '.', '');
    $arrears  = $quote['arrears'];
    $current  = $quote['current'];
    $openCur  = collect($current)->where('balance_kobo', '>', 0);
    $hasArr   = !empty($arrears);
    $fullName = trim(($student->lastname ?? '') . ' ' . ($student->firstname ?? '') . ' ' . ($student->othername ?? ''));
    $backUrl  = match ($mode) {
        'student' => route('student.payments', ['term_id' => $selectedTermId, 'session_id' => $selectedSessionId]),
        'parent'  => route('parent.fees', ['student' => $student->id, 'term_id' => $selectedTermId, 'session_id' => $selectedSessionId]),
        default   => route('online-fees.index'),
    };
    $pageRoute = fn ($params) => match ($mode) {
        'student' => route('student.fees.pay', $params),
        'parent'  => route('parent.pay', ['student' => $student->id] + $params),
        default   => route('online-fees.pay-for', ['student' => $student->id] + $params),
    };
    $statusPill = ['success' => 'st-paid', 'pending' => 'st-pending', 'amount_mismatch' => 'st-warning'];
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero :title="$mode === 'staff' ? 'Online Payment' : 'Pay School Fees'" icon="ri-secure-payment-line"
               subtitle="Choose the bills and amounts to pay, then complete the payment securely with {{ implode(' or ', $gateways ?: ['Paystack']) }}."
               :back="$backUrl" :back-label="['student' => 'My Payments', 'parent' => 'Fees'][$mode] ?? 'Online payments'">
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-user-line"></i>{{ $fullName }}</span>
            <span class="cb-meta-pill"><i class="ri-hashtag"></i>{{ $student->admissionNo ?? '—' }}</span>
            @if($quote['class'])<span class="cb-meta-pill"><i class="ri-building-line"></i>{{ $quote['class']->schoolclass }}</span>@endif
            @if($quote['term'] && $quote['session'])<span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $quote['term']->term }} · {{ $quote['session']->session }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @if(!$gatewayReady)
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i>
            <div>Online payment is not switched on yet. {{ $mode !== 'staff' ? 'Please contact the school bursary.' : 'Add the Paystack or OPay keys and switch the gateway on under Finance › Payment Gateways.' }}</div>
        </div>
    @endif

    @include('instalments.partials.schedule', ['studentId' => $student->id, 'termId' => $selectedTermId, 'sessionId' => $selectedSessionId,
        'payable' => ($quote['totals']['current_payable_kobo'] ?? 0) / 100, 'paid' => ($quote['totals']['current_paid_kobo'] ?? 0) / 100])

    {{-- PERIOD PICKER --}}
    <div class="cb-card">
        <form method="GET" action="{{ $pageRoute([]) }}" class="cb-toolbar" style="border-bottom:none">
            <select name="session_id" class="cb-select" aria-label="Session" onchange="this.form.submit()">
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" @selected($selectedSessionId == $s->id)>{{ $s->session }}{{ $s->status === 'Current' ? ' (current)' : '' }}</option>
                @endforeach
            </select>
            <div class="term-chips" role="group" aria-label="Term">
                @foreach($terms as $t)
                    <a class="term-chip {{ $selectedTermId == $t->id ? 'active' : '' }}" href="{{ $pageRoute(['session_id' => $selectedSessionId, 'term_id' => $t->id]) }}">{{ $t->term }}</a>
                @endforeach
            </div>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">

            {{-- ARREARS --}}
            @if($hasArr)
                <div class="cb-banner warning">
                    <i class="ri-alarm-warning-line"></i>
                    <div>
                        <strong>{{ $k2n($quote['totals']['arrears_kobo']) }} is outstanding from earlier terms.</strong>
                        This term's fees can only be paid once these arrears are cleared. You can clear them in this same payment:
                        selecting a bill below adds every arrear at its full balance.
                    </div>
                </div>

                <x-cb.card title="Arrears from earlier terms" icon="ri-history-line" :count="count($arrears)" :flush="true">
                    <div class="table-responsive">
                        <table class="cb-table mb-0 of-table">
                            <thead><tr>
                                <th style="width:40px"><input type="checkbox" class="form-check-input" id="allArrears" aria-label="Select all arrears" @disabled(!$gatewayReady)></th>
                                <th>Bill</th><th>Term</th>
                                <th class="text-end">Outstanding</th>
                                <th class="text-end" style="width:170px">Amount to pay</th>
                            </tr></thead>
                            <tbody>
                            @foreach($arrears as $a)
                                <tr class="of-row" data-key="{{ $a['key'] }}" data-arrear="1" data-balance="{{ $a['balance_kobo'] }}">
                                    <td><input type="checkbox" class="form-check-input of-check" aria-label="Pay {{ $a['title'] }}" @disabled(!$gatewayReady)></td>
                                    <td><div class="fw-semibold">{{ $a['title'] }}</div><small class="text-muted">{{ $a['class_name'] }}</small></td>
                                    <td>{{ $a['term_name'] }} · {{ $a['session_name'] }}</td>
                                    <td class="text-end fw-semibold" style="color:#b91c1c">{{ $k2n($a['balance_kobo']) }}</td>
                                    <td class="text-end">
                                        <div class="of-amount"><span>₦</span>
                                            <input type="text" inputmode="decimal" class="form-control form-control-sm of-input" value="{{ $k2v($a['balance_kobo']) }}" disabled aria-label="Amount for {{ $a['title'] }}">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-cb.card>
            @endif

            {{-- THIS TERM --}}
            <x-cb.card :title="'This term' . ($quote['term'] ? ' — ' . $quote['term']->term . ', ' . $quote['session']->session : '')" icon="ri-bill-line" :count="count($current)" :flush="true">
                @if($quote['statementError'] && empty($current))
                    <div class="empty-state"><i class="ri-file-list-3-line"></i><h6>No bills</h6><p>{{ $quote['statementError'] }}</p></div>
                @elseif(empty($current))
                    <div class="empty-state"><i class="ri-file-list-3-line"></i><h6>No bills</h6><p>No fees have been set for this class and term yet.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="cb-table mb-0 of-table">
                            <thead><tr>
                                <th style="width:40px"><input type="checkbox" class="form-check-input" id="allCurrent" aria-label="Select all bills" @disabled(!$gatewayReady || $openCur->isEmpty())></th>
                                <th>Bill</th>
                                <th class="text-end">Payable</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Outstanding</th>
                                <th class="text-end" style="width:170px">Amount to pay</th>
                            </tr></thead>
                            <tbody>
                            @foreach($current as $c)
                                @php $open = $c['balance_kobo'] > 0; @endphp
                                <tr class="of-row {{ $open ? '' : 'is-settled' }}" data-key="{{ $c['key'] }}" data-arrear="0" data-balance="{{ $c['balance_kobo'] }}">
                                    <td>
                                        @if($open)
                                            <input type="checkbox" class="form-check-input of-check" aria-label="Pay {{ $c['title'] }}" @disabled(!$gatewayReady)>
                                        @else
                                            <i class="ri-checkbox-circle-fill" style="color:var(--cb-teal)"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $c['title'] }}</div>
                                        @if($c['savings_kobo'] > 0)<small class="text-success">Includes {{ $k2n($c['savings_kobo']) }} scholarship/discount</small>@endif
                                    </td>
                                    <td class="text-end">{{ $k2n($c['payable_kobo']) }}</td>
                                    <td class="text-end">{{ $k2n($c['paid_kobo']) }}</td>
                                    <td class="text-end fw-semibold">
                                        @if($open){{ $k2n($c['balance_kobo']) }}@else<span class="status-pill st-paid">Paid</span>@endif
                                    </td>
                                    <td class="text-end">
                                        @if($open)
                                            <div class="of-amount"><span>₦</span>
                                                <input type="text" inputmode="decimal" class="form-control form-control-sm of-input" value="{{ $k2v($c['balance_kobo']) }}" disabled aria-label="Amount for {{ $c['title'] }}">
                                            </div>
                                        @else — @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot><tr>
                                <th></th><th>Total</th>
                                <th class="text-end">{{ $k2n($quote['totals']['current_payable_kobo']) }}</th>
                                <th class="text-end">{{ $k2n($quote['totals']['current_paid_kobo']) }}</th>
                                <th class="text-end">{{ $k2n($quote['totals']['current_kobo']) }}</th>
                                <th></th>
                            </tr></tfoot>
                        </table>
                    </div>
                @endif
            </x-cb.card>

            @if($history->isNotEmpty())
                <x-cb.card title="Recent online payments" icon="ri-time-line" :flush="true">
                    <div class="table-responsive">
                        <table class="cb-table mb-0">
                            <thead><tr><th>Date</th><th>Reference</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            @foreach($history as $h)
                                <tr>
                                    <td>{{ ($h->paid_at ?? $h->created_at)->format('d M Y, g:i a') }}</td>
                                    <td><code>{{ $h->reference }}</code></td>
                                    <td class="text-end">{{ $k2n($h->amount_kobo) }}</td>
                                    <td><span class="status-pill {{ $statusPill[$h->status] ?? 'st-muted' }}">{{ $h->statusLabel() }}</span></td>
                                    <td class="text-end"><a class="action-btn btn-open" href="{{ route('online-fees.show', $h->reference) }}"><i class="ri-eye-line"></i>{{ $h->status === 'success' ? 'Receipt' : 'View' }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-cb.card>
            @endif
        </div>

        {{-- SUMMARY --}}
        <div class="col-xl-4">
            <div class="cb-card of-summary">
                <div class="cb-card-header"><h5><i class="ri-shopping-bag-3-line"></i>Payment summary</h5></div>
                <div class="cb-card-body">
                    <div class="of-sum-row"><span>Arrears</span><strong id="sumArrears">₦0.00</strong></div>
                    <div class="of-sum-row"><span>This term</span><strong id="sumCurrent">₦0.00</strong></div>
                    <div class="of-sum-row of-sum-total"><span>Total to pay</span><strong id="sumTotal">₦0.00</strong></div>
                    <div id="ofErrors" class="cb-banner warning d-none mt-3 mb-0"><i class="ri-error-warning-line"></i><div></div></div>
                    @if(count($gateways) > 1)
                        <div class="mt-3">
                            <div class="small fw-semibold mb-1">Pay with</div>
                            <div class="d-flex gap-2">
                                @foreach($gateways as $gk => $gl)
                                    <label class="of-gw flex-fill"><input type="radio" name="of_gateway" value="{{ $gk }}" @checked($loop->first)> <span><i class="{{ $gk === 'opay' ? 'ri-smartphone-line' : 'ri-bank-card-line' }}"></i> {{ $gl }}</span>
                                        <small class="d-block text-muted">{{ $gk === 'opay' ? 'OPay wallet, card, bank transfer, USSD' : 'Card, bank transfer, USSD' }}</small></label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <button type="button" class="action-btn btn-primary-cb w-100 justify-content-center mt-3 py-2" id="ofPayBtn" disabled>
                        <i class="ri-lock-2-line"></i><span>Select bills to pay</span>
                    </button>
                    <ul class="of-notes">
                        <li><i class="ri-shield-check-line"></i>Processed by {{ implode(' or ', $gateways ?: ['Paystack']) }}. Card and wallet details never touch this portal.</li>
                        <li><i class="ri-price-tag-3-line"></i>No extra charge: you pay exactly the total shown.</li>
                        <li><i class="ri-file-list-3-line"></i>Your bills update automatically once the payment is confirmed.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- CONFIRM MODAL --}}
<div class="modal fade" id="ofConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="ri-secure-payment-line me-2"></i>Confirm payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <p class="mb-2">You're paying for <strong>{{ $fullName }}</strong> ({{ $student->admissionNo }}):</p>
                <table class="table table-sm mb-2" id="ofConfirmTable"><tbody></tbody></table>
                <p class="small text-muted mb-0">You'll be taken to <span class="of-gw-name">{{ array_values($gateways)[0] ?? 'Paystack' }}</span> to complete the payment, then brought back here for your receipt.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="ofGo"><i class="ri-arrow-right-line me-1"></i><span>Continue to {{ array_values($gateways)[0] ?? 'Paystack' }}</span></button>
            </div>
        </div>
    </div>
</div>

<style>
.of-gw { border: 1px solid var(--bs-border-color, #e2e8f0); border-radius: 10px; padding: 8px 10px; cursor: pointer; font-size: .9rem; }
.of-gw:has(input:checked) { border-color: #0d9488; background: rgba(13,148,136,.06); }
.of-gw input { margin-right: 4px; }
.of-table td, .of-table th { vertical-align: middle; }
.of-row.is-settled { opacity: .6; }
.of-row.is-locked td { background: rgba(245,158,11,.05); }
.of-amount { display: flex; align-items: center; gap: 4px; justify-content: flex-end; }
.of-amount span { color: var(--cb-muted); font-weight: 600; }
.of-amount input { max-width: 130px; text-align: right; font-variant-numeric: tabular-nums; }
.of-amount input.is-invalid { border-color: #dc2626; }
.of-summary { position: sticky; top: 90px; }
.of-sum-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--cb-border); font-size: 14px; }
.of-sum-row strong { font-variant-numeric: tabular-nums; }
.of-sum-total { border-bottom: none; font-size: 17px; padding-top: 12px; }
.of-sum-total strong { color: var(--cb-heading); font-size: 20px; }
.of-notes { list-style: none; padding: 0; margin: 16px 0 0; font-size: 12px; color: var(--cb-muted); }
.of-notes li { display: flex; gap: 8px; margin-bottom: 8px; }
.of-notes i { color: var(--cb-teal); font-size: 15px; }
</style>

<script>
(function () {
    const CFG = {
        checkoutUrl: @json(route('online-fees.checkout')),
        studentId:   @json((int) $student->id),
        termId:      @json((int) $selectedTermId),
        sessionId:   @json((int) $selectedSessionId),
        minKobo:     @json((int) $minTotalKobo),
        ready:       @json((bool) $gatewayReady),
        gateways:    @json($gateways),
        csrf:        document.querySelector('meta[name="csrf-token"]')?.content || '',
    };
    const rows      = Array.from(document.querySelectorAll('.of-row[data-key]')).filter(r => r.querySelector('.of-check'));
    const arrRows   = rows.filter(r => r.dataset.arrear === '1');
    const curRows   = rows.filter(r => r.dataset.arrear === '0');
    const payBtn    = document.getElementById('ofPayBtn');
    const errBox    = document.getElementById('ofErrors');
    const naira     = k => '₦' + (k / 100).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // "1,234.5" -> 123450 kobo; null when invalid
    function toKobo(v) {
        v = String(v || '').replace(/[,\s₦]/g, '');
        if (!/^\d+(\.\d{1,2})?$/.test(v)) return null;
        const [n, d = ''] = v.split('.');
        return parseInt(n, 10) * 100 + parseInt((d + '00').slice(0, 2), 10);
    }
    const chk = r => r.querySelector('.of-check');
    const inp = r => r.querySelector('.of-input');
    const bal = r => parseInt(r.dataset.balance, 10);
    const anyCurrent = () => curRows.some(r => chk(r).checked);

    function setRow(r, on, full) {
        chk(r).checked = on;
        inp(r).disabled = !on;
        if (full) inp(r).value = (bal(r) / 100).toFixed(2);
    }

    // Arrears must be paid in full whenever a current bill is selected.
    function enforceArrears() {
        const lock = arrRows.length > 0 && anyCurrent();
        arrRows.forEach(r => {
            if (lock) { setRow(r, true, true); inp(r).readOnly = true; chk(r).disabled = true; r.classList.add('is-locked'); }
            else { inp(r).readOnly = false; chk(r).disabled = !CFG.ready; r.classList.remove('is-locked'); }
        });
    }

    function collect() {
        const items = {}; let arr = 0, cur = 0; const errors = [];
        rows.forEach(r => {
            const i = inp(r);
            i.classList.remove('is-invalid');
            if (!chk(r).checked) return;
            const k = toKobo(i.value);
            if (k === null || k <= 0) { i.classList.add('is-invalid'); errors.push('Enter a valid amount for every selected bill.'); return; }
            if (k > bal(r)) { i.classList.add('is-invalid'); errors.push('An amount is more than the bill\'s outstanding balance.'); return; }
            items[r.dataset.key] = (k / 100).toFixed(2);
            r.dataset.arrear === '1' ? arr += k : cur += k;
        });
        const total = arr + cur;
        if (!errors.length && total > 0 && total < CFG.minKobo) errors.push('The minimum online payment is ' + naira(CFG.minKobo) + '.');
        return { items, arr, cur, total, errors: [...new Set(errors)] };
    }

    function refresh() {
        const s = collect();
        document.getElementById('sumArrears').textContent = naira(s.arr);
        document.getElementById('sumCurrent').textContent = naira(s.cur);
        document.getElementById('sumTotal').textContent   = naira(s.total);
        errBox.classList.toggle('d-none', !s.errors.length);
        errBox.querySelector('div').textContent = s.errors.join(' ');
        const ok = CFG.ready && s.total > 0 && !s.errors.length;
        payBtn.disabled = !ok;
        payBtn.querySelector('span').textContent = s.total > 0 ? 'Pay ' + naira(s.total) : 'Select bills to pay';
        const all = document.getElementById('allCurrent');
        if (all) all.checked = curRows.length > 0 && curRows.every(r => chk(r).checked);
        const allA = document.getElementById('allArrears');
        if (allA) { allA.checked = arrRows.length > 0 && arrRows.every(r => chk(r).checked); allA.disabled = !CFG.ready || anyCurrent(); }
        return s;
    }

    rows.forEach(r => {
        chk(r).addEventListener('change', () => { setRow(r, chk(r).checked, true); enforceArrears(); refresh(); });
        inp(r).addEventListener('input', refresh);
        inp(r).addEventListener('blur', () => { const k = toKobo(inp(r).value); if (k !== null) inp(r).value = (k / 100).toFixed(2); refresh(); });
    });
    document.getElementById('allCurrent')?.addEventListener('change', e => { curRows.forEach(r => setRow(r, e.target.checked, true)); enforceArrears(); refresh(); });
    document.getElementById('allArrears')?.addEventListener('change', e => { arrRows.forEach(r => setRow(r, e.target.checked, true)); refresh(); });

    const modalEl = document.getElementById('ofConfirm');
    payBtn.addEventListener('click', () => {
        const s = refresh();
        if (payBtn.disabled) return;
        const body = document.querySelector('#ofConfirmTable tbody');
        body.innerHTML = '';
        rows.filter(r => chk(r).checked).forEach(r => {
            const tr = document.createElement('tr');
            const title = r.querySelector('td:nth-child(2) .fw-semibold').textContent;
            tr.innerHTML = '<td></td><td class="text-end"></td>';
            tr.children[0].textContent = title + (r.dataset.arrear === '1' ? ' (arrear)' : '');
            tr.children[1].textContent = naira(toKobo(inp(r).value));
            body.appendChild(tr);
        });
        const tot = document.createElement('tr');
        tot.innerHTML = '<th>Total</th><th class="text-end"></th>';
        tot.children[1].textContent = naira(s.total);
        body.appendChild(tot);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    const gwKey  = () => (document.querySelector('input[name="of_gateway"]:checked') || {}).value || Object.keys(CFG.gateways)[0] || 'paystack';
    const gwName = () => CFG.gateways[gwKey()] || 'Paystack';
    const syncGw = () => {
        document.querySelectorAll('.of-gw-name').forEach(e => e.textContent = gwName());
        document.querySelector('#ofGo span').textContent = 'Continue to ' + gwName();
    };
    document.querySelectorAll('input[name="of_gateway"]').forEach(r => r.addEventListener('change', syncGw));
    syncGw();

    document.getElementById('ofGo').addEventListener('click', async function () {
        const s = collect();
        const btn = this; btn.disabled = true; btn.querySelector('span').textContent = 'Connecting to ' + gwName() + '…';
        try {
            const res = await fetch(CFG.checkoutUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CFG.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ student_id: CFG.studentId, term_id: CFG.termId, session_id: CFG.sessionId, items: s.items, gateway: gwKey() }),
            });
            const json = await res.json().catch(() => ({}));
            if (res.ok && json.success && json.authorization_url) {
                window.location.href = json.authorization_url;
                return;
            }
            throw new Error((json.errors && json.errors.join(' ')) || json.message || 'Could not start the payment.');
        } catch (e) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            errBox.classList.remove('d-none');
            errBox.querySelector('div').textContent = e.message;
            btn.disabled = false; btn.querySelector('span').textContent = 'Continue to ' + gwName();
        }
    });

    enforceArrears();
    refresh();
})();
</script>
@endsection
