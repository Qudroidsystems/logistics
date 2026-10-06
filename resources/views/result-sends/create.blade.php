{{-- resources/views/result-sends/create.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Send Results to Parents" icon="ri-mail-send-line"
               subtitle="Report cards go by email (PDF attached), WhatsApp and SMS (secure download link)."
               :back="route('result-sends.index')" back-label="History" />

    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ implode(' ', $errors->all()) }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <form method="POST" action="{{ route('result-sends.store') }}" id="rsForm">
        @csrf
        <div class="row g-4">
            <div class="col-xl-8">
                <x-cb.card title="1. Which results?" icon="ri-file-list-3-line">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="session_id">Session</label>
                            <select class="form-select" name="session_id" id="session_id">
                                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('session_id', $currentSessionId) == $s->id)>{{ $s->session }}{{ $s->status === 'Current' ? ' (current)' : '' }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="term_id">Term</label>
                            <select class="form-select" name="term_id" id="term_id">
                                @foreach($terms as $t)<option value="{{ $t->id }}" @selected(old('term_id') == $t->id)>{{ $t->term }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Report</label>
                            <input class="form-control" value="Terminal report card" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between">Classes
                                <span><button type="button" class="action-btn btn-open" id="clsAll">All</button> <button type="button" class="action-btn btn-open" id="clsNone">None</button></span></label>
                            <div class="rs-class-grid">
                                @foreach($classes as $c)
                                    <label class="rs-check"><input type="checkbox" name="class_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, old('class_ids', [])))> {{ $c->name }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="require_vetted" value="1" id="require_vetted" @checked(old('require_vetted', true))>
                                <label class="form-check-label" for="require_vetted">Only send results where every subject is vetted</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="include_owing" value="1" id="include_owing" @checked(old('include_owing'))>
                                <label class="form-check-label" for="include_owing">Also send to students whose results are on hold for fees</label>
                            </div>
                        </div>
                    </div>
                </x-cb.card>

                <x-cb.card title="2. Students" icon="ri-group-line" :flush="true">
                    <x-slot:tools>
                        <span class="small text-muted" id="rsCount"></span>
                        <button type="button" class="action-btn btn-open" id="selReady">Select all ready</button>
                    </x-slot:tools>
                    <div id="rsTable"><div class="empty-state"><i class="ri-checkbox-multiple-line"></i><h6>Pick classes</h6><p>Students with results for the chosen term will be listed here with their readiness.</p></div></div>
                </x-cb.card>
            </div>

            <div class="col-xl-4">
                <div class="rs-side">
                    <x-cb.card title="3. Channels & message" icon="ri-send-plane-line">
                        <div class="d-flex flex-column gap-2 mb-3">
                            @foreach($channels as $k => $c)
                                <label class="rs-channel {{ $c['enabled'] ? '' : 'is-off' }}">
                                    <input type="checkbox" name="channels[]" value="{{ $k }}" @checked(in_array($k, old('channels', ['email']))) @disabled(!$c['enabled'])>
                                    <span><strong>{{ $c['label'] }}</strong>
                                        <small>
                                            @if(!$c['enabled']) Switched off
                                            @elseif(!$c['live']) Log only (test)
                                            @elseif($k === 'email') PDF attached
                                            @elseif($k === 'whatsapp') {{ $c['doc'] ? 'PDF as a document' : 'Secure link (no document template set)' }}
                                            @else Secure download link @endif
                                        </small></span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mb-1 d-flex flex-wrap gap-1">
                            @foreach($placeholders as $ph => $l)<button type="button" class="rs-chip" data-insert="{{ $ph }}" title="{{ $l }}">{{ $ph }}</button>@endforeach
                        </div>
                        <label class="form-label" for="message">Message <small class="text-muted">(email / WhatsApp)</small></label>
                        <textarea class="form-control mb-2" name="message" id="message" rows="7" maxlength="3000" required>{{ old('message', $defaultMsg) }}</textarea>
                        <label class="form-label" for="sms_text">SMS text <small id="smsLen" class="text-muted"></small></label>
                        <textarea class="form-control mb-2" name="sms_text" id="sms_text" rows="3" maxlength="612">{{ old('sms_text', $defaultSms) }}</textarea>
                        <label class="form-label" for="link_days">Download link works for</label>
                        <div class="input-group input-group-sm mb-3" style="max-width:180px">
                            <input type="number" class="form-control" name="link_days" id="link_days" min="1" max="90" value="{{ old('link_days', 14) }}">
                            <span class="input-group-text">days</span>
                        </div>
                        <button type="submit" class="action-btn btn-primary-cb w-100 justify-content-center py-2" id="sendBtn" disabled><i class="ri-mail-send-line"></i><span>Send results</span></button>
                        <small class="text-muted d-block mt-2">Report cards are generated and sent in the background. You can leave the page.</small>
                    </x-cb.card>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
</div>

<style>
.rs-class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 6px; max-height: 200px; overflow: auto; border: 1px dashed var(--cb-border); border-radius: var(--cb-radius-sm); padding: 10px; }
.rs-check { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; }
.rs-channel { display: flex; gap: 10px; align-items: center; border: 1.5px solid var(--cb-border); border-radius: var(--cb-radius-sm); padding: 8px 12px; cursor: pointer; }
.rs-channel span { display: flex; flex-direction: column; } .rs-channel small { color: var(--cb-muted); font-size: 11.5px; }
.rs-channel.is-off { opacity: .55; cursor: not-allowed; }
.rs-chip { border: 1px solid var(--cb-border); background: var(--cb-surface-2); border-radius: 20px; padding: 1px 8px; font-size: 11.5px; }
.rs-side { position: sticky; top: 90px; }
.rs-row-off td { opacity: .6; }
</style>

<script>
(function () {
    const form = document.getElementById('rsForm');
    const URL_C = @json(route('result-sends.candidates'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const box = document.getElementById('rsTable'), countEl = document.getElementById('rsCount'), sendBtn = document.getElementById('sendBtn');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let rows = [];
    let lastField = document.getElementById('message');
    ['message', 'sms_text'].forEach(id => document.getElementById(id).addEventListener('focus', e => { lastField = e.target; }));
    document.querySelectorAll('[data-insert]').forEach(b => b.addEventListener('click', () => {
        const t = lastField, s = t.selectionStart, v = b.dataset.insert;
        t.value = t.value.slice(0, s) + v + t.value.slice(t.selectionEnd); t.focus(); t.selectionStart = t.selectionEnd = s + v.length; smsLen();
    }));
    function smsLen() {
        const t = document.getElementById('sms_text').value; const n = t.length + (t.includes('{link}') ? 40 : 0);
        document.getElementById('smsLen').textContent = '≈ ' + n + ' chars · ' + (n <= 160 ? 1 : Math.ceil(n / 153)) + ' page(s) incl. link';
    }
    document.getElementById('sms_text').addEventListener('input', smsLen); smsLen();

    const owingOk = () => document.getElementById('include_owing').checked;
    function reasonOf(r) {
        if (!r.complete) return r.subjects === 0 ? 'No results' : 'Not all vetted (' + r.vetted + '/' + r.subjects + ')';
        if (r.blocked && (r.block_reason === 'manual_block' || !owingOk())) return r.block_reason === 'manual_block' ? 'Blocked by school' : 'On hold: fees owed';
        if (!(r.sms + r.whatsapp + r.email)) return 'No parent contact';
        return null;
    }

    function render() {
        if (!rows.length) { box.innerHTML = '<div class="empty-state"><i class="ri-file-search-line"></i><h6>No results found</h6><p>No student has results for this term in the chosen classes.</p></div>'; update(); return; }
        let h = '<div class="table-responsive" style="max-height:520px;overflow:auto"><table class="cb-table mb-0"><thead><tr><th style="width:36px"><input type="checkbox" id="chkAll" class="form-check-input" aria-label="Select all"></th><th>Student</th><th>Class</th><th>Results</th><th>Parent contacts</th><th>Status</th></tr></thead><tbody>';
        rows.forEach((r, i) => {
            const why = reasonOf(r);
            h += '<tr class="' + (why ? 'rs-row-off' : '') + '"><td><input type="checkbox" class="form-check-input rs-st" name="student_ids[]" value="' + r.student_id + '"' + (why ? '' : ' checked') + ' aria-label="Send to ' + esc(r.name) + '"></td>' +
                '<td><div class="fw-semibold">' + esc(r.name) + '</div><small class="text-muted">' + esc(r.adm) + '</small></td>' +
                '<td>' + esc(r.class) + '</td>' +
                '<td>' + r.subjects + ' subj.' + (r.vetted < r.subjects ? '<br><small class="text-warning">' + r.vetted + ' vetted</small>' : '') + '</td>' +
                '<td><small>' + (r.email ? '✉ ' + r.email + ' ' : '') + (r.whatsapp ? 'WA ' + r.whatsapp + ' ' : '') + (r.sms ? 'SMS ' + r.sms : '') + (!(r.email + r.whatsapp + r.sms) ? '<span class="text-danger">none</span>' : '') + '</small></td>' +
                '<td>' + (why ? '<span class="status-pill ' + (why.startsWith('On hold') ? 'st-warning' : 'st-muted') + '">' + esc(why) + '</span>' : '<span class="status-pill st-paid">Ready</span>') +
                (r.blocked && r.owed ? '<br><small class="text-muted">owes ₦' + Number(r.owed).toLocaleString('en-NG', { minimumFractionDigits: 2 }) + '</small>' : '') + '</td></tr>';
        });
        box.innerHTML = h + '</tbody></table></div>';
        box.querySelector('#chkAll').addEventListener('change', e => { box.querySelectorAll('.rs-st').forEach(c => { c.checked = e.target.checked; }); update(); });
        box.querySelectorAll('.rs-st').forEach(c => c.addEventListener('change', update));
        update();
    }
    function update() {
        const n = box.querySelectorAll('.rs-st:checked').length;
        const ready = rows.filter(r => !reasonOf(r)).length;
        countEl.textContent = rows.length ? n + ' selected · ' + ready + ' ready of ' + rows.length : '';
        const ch = form.querySelectorAll('input[name="channels[]"]:checked').length;
        sendBtn.disabled = !n || !ch;
        sendBtn.querySelector('span').textContent = n ? 'Send ' + n + ' report card' + (n === 1 ? '' : 's') : 'Send results';
    }
    document.getElementById('selReady').addEventListener('click', () => { box.querySelectorAll('.rs-st').forEach((c, i) => { c.checked = !reasonOf(rows[i]); }); update(); });
    form.querySelectorAll('input[name="channels[]"]').forEach(c => c.addEventListener('change', update));
    document.getElementById('include_owing').addEventListener('change', render);

    let t;
    async function load() {
        const fd = new FormData(); fd.append('_token', csrf);
        fd.append('session_id', document.getElementById('session_id').value);
        fd.append('term_id', document.getElementById('term_id').value);
        fd.append('require_vetted', document.getElementById('require_vetted').checked ? 1 : 0);
        const cls = [...form.querySelectorAll('input[name="class_ids[]"]:checked')].map(c => c.value);
        if (!cls.length) { rows = []; box.innerHTML = '<div class="empty-state"><i class="ri-checkbox-multiple-line"></i><h6>Pick classes</h6><p>Students with results will be listed here.</p></div>'; update(); return; }
        cls.forEach(c => fd.append('class_ids[]', c));
        box.innerHTML = '<p class="p-3 small text-muted mb-0"><span class="spinner-border spinner-border-sm"></span> Checking results, fees and contacts…</p>';
        try {
            const j = await fetch(URL_C, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json());
            rows = j.students || []; render();
        } catch (e) { box.innerHTML = '<p class="p-3 small text-danger mb-0">Could not load students.</p>'; }
    }
    const later = () => { clearTimeout(t); t = setTimeout(load, 400); };
    ['session_id', 'term_id', 'require_vetted'].forEach(id => document.getElementById(id).addEventListener('change', later));
    form.querySelectorAll('input[name="class_ids[]"]').forEach(c => c.addEventListener('change', later));
    document.getElementById('clsAll').addEventListener('click', () => { form.querySelectorAll('input[name="class_ids[]"]').forEach(c => { c.checked = true; }); later(); });
    document.getElementById('clsNone').addEventListener('click', () => { form.querySelectorAll('input[name="class_ids[]"]').forEach(c => { c.checked = false; }); later(); });
    load();

    form.addEventListener('submit', e => {
        const n = box.querySelectorAll('.rs-st:checked').length;
        const ch = [...form.querySelectorAll('input[name="channels[]"]:checked')].map(c => c.parentElement.querySelector('strong').textContent);
        if (!confirm('Send ' + n + ' report card(s) to parents by ' + ch.join(', ') + '?')) e.preventDefault();
    });
})();
</script>
@endsection
