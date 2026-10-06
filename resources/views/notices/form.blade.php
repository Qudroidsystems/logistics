{{-- resources/views/notices/form.blade.php — create / edit a notice --}}
@extends('layouts.master')

@section('content')
@php
    $aud       = $notice->audience ?? ['scope' => 'school'];
    $scope     = old('scope', $aud['scope'] ?? 'school');
    $selCls    = array_map('intval', old('class_ids', $aud['class_ids'] ?? []));
    $selCat    = array_map('intval', old('category_ids', $aud['category_ids'] ?? []));
    $selCh     = old('channels', $notice->channels ?? []);
    $remSaved  = collect($notice->reminders ?? [])->keyBy('days');
    $remRows   = [7 => '1 week before', 3 => '3 days before', 1 => 'The day before', 0 => 'On the day'];
    $isNew     = !$notice->exists;
    $action    = $isNew ? route('notices.store') : route('notices.update', $notice);
    $scheduled = $notice->status === 'scheduled';
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero :title="$isNew ? 'New Notice' : 'Edit Notice'" icon="ri-megaphone-line"
               subtitle="Write once — it's personalised for each parent and sent on every channel you choose."
               :back="route('notices.index')" back-label="All notices" />

    @if($errors->any())
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ implode(' ', $errors->all()) }}</div></div>
    @endif
    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach
    @if($scheduled)
        <div class="cb-banner info"><i class="ri-time-line"></i><div>This notice is scheduled for {{ $notice->send_at?->format('D j M Y, g:i a') }}. Saving as draft cancels the schedule; you can reschedule below.</div></div>
    @endif

    <form method="POST" action="{{ $action }}" id="noticeForm" novalidate>
        @csrf
        @unless($isNew) @method('PUT') @endunless

        <div class="row g-4">
            <div class="col-xl-8">

                {{-- 1. WHAT --}}
                <x-cb.card title="1. What is it about?" icon="ri-edit-box-line">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="type">Type</label>
                            <select class="form-select" name="type" id="type">
                                @foreach(\App\Models\SchoolNotice::TYPES as $k => $t)
                                    <option value="{{ $k }}" @selected(old('type', $notice->type) === $k)>{{ $t['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                            <input class="form-control" name="title" id="title" maxlength="150" required value="{{ old('title', $notice->title) }}" placeholder="e.g. First Term CA Test">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="event_date">Date</label>
                            <input type="date" class="form-control" name="event_date" id="event_date" value="{{ old('event_date', $notice->event_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="event_end_date">End date <small class="text-muted">(optional)</small></label>
                            <input type="date" class="form-control" name="event_end_date" id="event_end_date" value="{{ old('event_end_date', $notice->event_end_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="event_time">Time <small class="text-muted">(optional)</small></label>
                            <input class="form-control" name="event_time" id="event_time" maxlength="20" value="{{ old('event_time', $notice->event_time) }}" placeholder="e.g. 10:00 am">
                        </div>
                    </div>
                </x-cb.card>

                {{-- 2. MESSAGE --}}
                <x-cb.card title="2. Message" icon="ri-chat-3-line">
                    <div class="mb-2 d-flex flex-wrap gap-1 align-items-center">
                        <small class="text-muted me-1">Insert:</small>
                        @foreach($placeholders as $ph => $label)
                            <button type="button" class="nt-chip" data-insert="{{ $ph }}" title="{{ $label }}">{{ $ph }}</button>
                        @endforeach
                        <button type="button" class="nt-chip nt-chip-alt ms-auto" id="useTemplate"><i class="ri-magic-line"></i> Use standard wording</button>
                    </div>
                    <label class="form-label" for="message">Full message <small class="text-muted">(email and WhatsApp)</small> <span class="text-danger">*</span></label>
                    <textarea class="form-control nt-text" name="message" id="message" rows="8" maxlength="5000" required>{{ old('message', $notice->message) }}</textarea>

                    <div class="d-flex justify-content-between align-items-end mt-3">
                        <label class="form-label mb-1" for="sms_text">Short SMS version <small class="text-muted">(optional — the full message is used if empty)</small></label>
                        <small id="smsCounter" class="text-muted"></small>
                    </div>
                    <textarea class="form-control nt-text" name="sms_text" id="sms_text" rows="3" maxlength="918">{{ old('sms_text', $notice->sms_text) }}</textarea>
                    <small class="text-muted">Placeholders are replaced per parent, so the final length varies slightly. Each SMS page is 160 characters (70 if you use emoji or special symbols).</small>
                </x-cb.card>

                {{-- 3. WHO --}}
                <x-cb.card title="3. Who should receive it?" icon="ri-group-line">
                    <div class="nt-scopes mb-3">
                        @foreach(['school' => ['Whole school', 'All parents of active students', 'ri-building-4-line'],
                                  'categories' => ['Class categories', 'e.g. Junior or Senior only', 'ri-stack-line'],
                                  'classes' => ['Specific classes', 'Pick classes and arms', 'ri-door-open-line'],
                                  'students' => ['Selected students', 'Parents of chosen students', 'ri-user-search-line'],
                                  'none' => ['No parents', 'Staff only', 'ri-user-unfollow-line']] as $k => [$l, $d, $i])
                            <label class="nt-scope">
                                <input type="radio" name="scope" value="{{ $k }}" @checked($scope === $k)>
                                <span><i class="{{ $i }}"></i><strong>{{ $l }}</strong><small>{{ $d }}</small></span>
                            </label>
                        @endforeach
                    </div>

                    <div class="nt-panel" data-scope="categories">
                        <div class="d-flex flex-wrap gap-2">
                            @forelse($categories as $c)
                                <label class="nt-check"><input type="checkbox" name="category_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $selCat))> {{ $c->name }}</label>
                            @empty
                                <small class="text-muted">No class categories found.</small>
                            @endforelse
                        </div>
                    </div>

                    <div class="nt-panel" data-scope="classes">
                        <div class="d-flex gap-2 mb-2">
                            <input type="search" class="form-control form-control-sm" id="classFilter" placeholder="Filter classes…" style="max-width:240px">
                            <button type="button" class="action-btn btn-open" id="clsAll">Select shown</button>
                            <button type="button" class="action-btn btn-open" id="clsNone">Clear</button>
                        </div>
                        <div class="nt-class-grid">
                            @foreach($classes as $c)
                                <label class="nt-check" data-name="{{ strtolower($c->name) }}"><input type="checkbox" name="class_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $selCls))> {{ $c->name }}</label>
                            @endforeach
                        </div>
                    </div>

                    <div class="nt-panel" data-scope="students">
                        <input type="search" class="form-control" id="studentSearch" placeholder="Type a name or admission number" autocomplete="off">
                        <div id="studentResults" class="list-group mt-1"></div>
                        <div id="studentChips" class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($selectedStudents as $s)
                                <span class="nt-chip nt-chip-sel">{{ trim($s->lastname . ' ' . $s->firstname) }} ({{ $s->admissionNo }})<input type="hidden" name="student_ids[]" value="{{ $s->id }}"><button type="button" aria-label="Remove">×</button></span>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="include_staff" value="1" id="include_staff" @checked(old('include_staff', $aud['include_staff'] ?? false))>
                        <label class="form-check-label" for="include_staff">Also send to staff</label>
                    </div>
                </x-cb.card>

                {{-- 4. HOW --}}
                <x-cb.card title="4. Channels" icon="ri-send-plane-line">
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($channels as $k => $c)
                            <label class="nt-channel {{ $c['enabled'] ? '' : 'is-off' }}">
                                <input type="checkbox" name="channels[]" value="{{ $k }}" @checked(in_array($k, $selCh)) @disabled(!$c['enabled'])>
                                <span><strong>{{ $c['label'] }}</strong>
                                    <small>{{ !$c['enabled'] ? 'Switched off' : ($c['live'] ? 'Ready' : 'Log only (test)') }}</small></span>
                            </label>
                        @endforeach
                    </div>
                    @can('Manage notification settings')
                        <small class="d-block mt-2"><a href="{{ route('notices.settings') }}">Notification settings</a> — turn channels on and enter provider keys.</small>
                    @endcan
                </x-cb.card>

                {{-- 5. WHEN --}}
                <x-cb.card title="5. When to send" icon="ri-calendar-schedule-line">
                    <div class="d-flex flex-wrap gap-4 mb-3">
                        <label class="nt-check"><input type="radio" name="when" value="now" @checked(!$scheduled)> Send now</label>
                        <label class="nt-check"><input type="radio" name="when" value="later" @checked($scheduled)> Schedule for</label>
                        <input type="datetime-local" class="form-control form-control-sm" name="send_at" id="send_at" style="max-width:230px"
                               value="{{ old('send_at', $notice->send_at?->format('Y-m-d\TH:i')) }}" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                    </div>

                    <label class="form-label">Automatic reminders <small class="text-muted">(need a date above)</small></label>
                    <div class="row g-2" id="reminderRows">
                        @foreach($remRows as $days => $label)
                            @php $r = $remSaved->get($days); @endphp
                            <div class="col-md-6">
                                <div class="nt-reminder">
                                    <label class="nt-check mb-0"><input type="checkbox" name="reminders[{{ $days }}][on]" value="1" @checked($r)> {{ $label }}</label>
                                    <input type="hidden" name="reminders[{{ $days }}][days]" value="{{ $days }}">
                                    <input type="time" class="form-control form-control-sm" name="reminders[{{ $days }}][time]" value="{{ $r['time'] ?? ($days === 0 ? '07:00' : '08:00') }}" aria-label="Time for {{ $label }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Reminders go to the same audience, using the same message.</small>
                </x-cb.card>
            </div>

            {{-- PREVIEW / ACTIONS --}}
            <div class="col-xl-4">
                <div class="nt-side">
                    <div class="cb-card">
                        <div class="cb-card-header"><h5><i class="ri-eye-line"></i>Preview</h5>
                            <button type="button" class="action-btn btn-open" id="refreshPreview"><i class="ri-refresh-line"></i></button></div>
                        <div class="cb-card-body" id="preview"><p class="text-muted small mb-0">Fill in the message and audience to see who will receive it.</p></div>
                    </div>
                    <div class="cb-card">
                        <div class="cb-card-body d-grid gap-2">
                            <button type="submit" name="action" value="send" class="action-btn btn-primary-cb justify-content-center py-2" id="sendBtn"><i class="ri-send-plane-2-line"></i><span>Send now</span></button>
                            <button type="button" class="action-btn btn-go justify-content-center" id="testBtn"><i class="ri-user-received-2-line"></i>Send test to me</button>
                            <button type="submit" name="action" value="draft" class="action-btn btn-open justify-content-center" formnovalidate><i class="ri-save-3-line"></i>Save draft</button>
                            <div id="testResult" class="small"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
</div>

<style>
.nt-chip { border: 1px solid var(--cb-border); background: var(--cb-surface-2); border-radius: 20px; padding: 2px 10px; font-size: 12px; color: var(--cb-heading); }
.nt-chip:hover { border-color: var(--cb-teal); }
.nt-chip-alt { background: rgba(13,148,136,.08); color: var(--cb-teal); }
.nt-chip-sel { display: inline-flex; align-items: center; gap: 6px; }
.nt-chip-sel button { border: 0; background: none; color: #b91c1c; font-size: 15px; line-height: 1; padding: 0; }
.nt-text { font-size: 14px; line-height: 1.5; }
.nt-scopes { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; }
.nt-scope input { position: absolute; opacity: 0; }
.nt-scope span { display: flex; flex-direction: column; gap: 2px; border: 1.5px solid var(--cb-border); border-radius: var(--cb-radius-sm); padding: 10px 12px; cursor: pointer; height: 100%; }
.nt-scope span i { font-size: 18px; color: var(--cb-teal); }
.nt-scope small { color: var(--cb-muted); font-size: 11.5px; }
.nt-scope input:checked + span { border-color: var(--cb-teal); background: rgba(13,148,136,.06); }
.nt-scope input:focus-visible + span { box-shadow: 0 0 0 3px rgba(13,148,136,.25); }
.nt-panel { display: none; border: 1px dashed var(--cb-border); border-radius: var(--cb-radius-sm); padding: 12px; }
.nt-panel.show { display: block; }
.nt-class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 6px; max-height: 260px; overflow: auto; }
.nt-check { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; }
.nt-channel { display: flex; gap: 10px; align-items: center; border: 1.5px solid var(--cb-border); border-radius: var(--cb-radius-sm); padding: 10px 14px; cursor: pointer; }
.nt-channel span { display: flex; flex-direction: column; } .nt-channel small { color: var(--cb-muted); font-size: 11.5px; }
.nt-channel.is-off { opacity: .55; cursor: not-allowed; }
.nt-reminder { display: flex; justify-content: space-between; align-items: center; gap: 10px; border: 1px solid var(--cb-border); border-radius: var(--cb-radius-sm); padding: 8px 12px; }
.nt-reminder input[type=time] { max-width: 120px; }
.nt-side { position: sticky; top: 90px; }
.nt-prev-ch { border-bottom: 1px dashed var(--cb-border); padding: 10px 0; }
.nt-prev-ch:last-child { border-bottom: 0; }
.nt-sample { white-space: pre-wrap; background: var(--cb-surface-2); border-radius: 8px; padding: 8px 10px; font-size: 12.5px; margin-top: 6px; max-height: 180px; overflow: auto; }
</style>

<script>
(function () {
    const form = document.getElementById('noticeForm');
    const TPL = @json($templates);
    const URLS = { preview: @json(route('notices.preview')), test: @json(route('notices.test')), students: @json(route('notices.students')) };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = id => document.getElementById(id);
    const msg = $('message'), sms = $('sms_text');
    let lastField = msg;
    [msg, sms].forEach(t => t.addEventListener('focus', () => { lastField = t; }));

    // Placeholder chips insert at the cursor
    document.querySelectorAll('[data-insert]').forEach(b => b.addEventListener('click', () => {
        const t = lastField, v = b.dataset.insert, s = t.selectionStart ?? t.value.length, e = t.selectionEnd ?? s;
        t.value = t.value.slice(0, s) + v + t.value.slice(e);
        t.focus(); t.selectionStart = t.selectionEnd = s + v.length;
        t.dispatchEvent(new Event('input'));
    }));

    function applyTemplate(force) {
        const t = TPL[$('type').value]; if (!t) return;
        const empty = !msg.value.trim() || Object.values(TPL).some(x => x.message === msg.value);
        if (!force && !empty) return;
        if (force && msg.value.trim() && !empty && !confirm('Replace the current message with the standard wording?')) return;
        msg.value = t.message; sms.value = t.sms;
        if (!$('title').value.trim() && t.title) $('title').value = t.title;
        smsCount(); schedulePreview();
    }
    $('useTemplate').addEventListener('click', () => applyTemplate(true));
    $('type').addEventListener('change', () => applyTemplate(false));
    if (!msg.value.trim()) applyTemplate(false);

    // SMS counter (GSM-7 vs unicode)
    const GSM = /^[@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&'()*+,\-./0-9:;<=>?¡A-ZÄÖÑÜ§¿a-zäöñüà^{}\\\[~\]|€]*$/;
    function smsCount() {
        const text = sms.value.trim() || msg.value.trim();
        const uni = !GSM.test(text);
        const len = uni ? [...text].length : text.length + (text.match(/[\^{}\\\[~\]|€]/g) || []).length;
        const pages = len === 0 ? 0 : (uni ? (len <= 70 ? 1 : Math.ceil(len / 67)) : (len <= 160 ? 1 : Math.ceil(len / 153)));
        $('smsCounter').textContent = len + ' chars · ' + pages + ' SMS page' + (pages === 1 ? '' : 's') + (uni ? ' (special characters)' : '') + (sms.value.trim() ? '' : ' — using full message');
        $('smsCounter').className = pages > 3 ? 'text-danger fw-semibold' : 'text-muted';
    }
    [msg, sms].forEach(t => t.addEventListener('input', smsCount)); smsCount();

    // Audience panels
    function showScope() {
        const v = form.querySelector('input[name="scope"]:checked')?.value;
        document.querySelectorAll('.nt-panel').forEach(p => p.classList.toggle('show', p.dataset.scope === v));
    }
    form.querySelectorAll('input[name="scope"]').forEach(r => r.addEventListener('change', () => { showScope(); schedulePreview(); }));
    showScope();

    $('classFilter')?.addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('.nt-class-grid .nt-check').forEach(l => { l.style.display = l.dataset.name.includes(q) ? '' : 'none'; });
    });
    $('clsAll')?.addEventListener('click', () => { document.querySelectorAll('.nt-class-grid .nt-check').forEach(l => { if (l.style.display !== 'none') l.querySelector('input').checked = true; }); schedulePreview(); });
    $('clsNone')?.addEventListener('click', () => { document.querySelectorAll('.nt-class-grid input').forEach(i => { i.checked = false; }); schedulePreview(); });

    // Student search
    const chips = $('studentChips'), results = $('studentResults');
    function addStudent(id, label) {
        if (chips.querySelector('input[value="' + id + '"]')) return;
        const s = document.createElement('span'); s.className = 'nt-chip nt-chip-sel';
        s.append(document.createTextNode(label));
        const h = document.createElement('input'); h.type = 'hidden'; h.name = 'student_ids[]'; h.value = id; s.append(h);
        const b = document.createElement('button'); b.type = 'button'; b.textContent = '×'; b.setAttribute('aria-label', 'Remove'); s.append(b);
        chips.append(s); schedulePreview();
    }
    chips.addEventListener('click', e => { if (e.target.tagName === 'BUTTON') { e.target.parentElement.remove(); schedulePreview(); } });
    let st;
    $('studentSearch')?.addEventListener('input', e => {
        clearTimeout(st); const q = e.target.value.trim();
        if (q.length < 2) { results.innerHTML = ''; return; }
        st = setTimeout(async () => {
            const rows = await fetch(URLS.students + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }).then(r => r.json()).catch(() => []);
            results.innerHTML = '';
            rows.forEach(r => {
                const a = document.createElement('button'); a.type = 'button'; a.className = 'list-group-item list-group-item-action py-1';
                a.textContent = r.label; a.addEventListener('click', () => { addStudent(r.id, r.label); results.innerHTML = ''; e.target.value = ''; });
                results.append(a);
            });
        }, 300);
    });

    // When: button label + schedule
    function syncWhen() {
        const later = form.querySelector('input[name="when"]:checked')?.value === 'later';
        $('send_at').disabled = !later;
        const b = $('sendBtn'); b.value = later ? 'schedule' : 'send';
        b.querySelector('span').textContent = later ? 'Schedule' : 'Send now';
        b.querySelector('i').className = later ? 'ri-calendar-check-line' : 'ri-send-plane-2-line';
    }
    form.querySelectorAll('input[name="when"]').forEach(r => r.addEventListener('change', syncWhen)); syncWhen();

    // Preview
    let pt, lastPreview = null;
    function schedulePreview() { clearTimeout(pt); pt = setTimeout(loadPreview, 600); }
    form.addEventListener('change', e => { if (!['send_at'].includes(e.target.name)) schedulePreview(); });
    [msg, sms, $('title')].forEach(t => t.addEventListener('input', schedulePreview));
    $('refreshPreview').addEventListener('click', loadPreview);

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const NAMES = { sms: 'SMS', whatsapp: 'WhatsApp', email: 'Email', portal: 'In-portal' };

    async function loadPreview() {
        const box = $('preview');
        const fd = new FormData(form); fd.delete('_method');
        box.innerHTML = '<p class="small text-muted mb-0"><span class="spinner-border spinner-border-sm"></span> Counting recipients…</p>';
        try {
            const res = await fetch(URLS.preview, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
            const p = await res.json(); lastPreview = p;
            let html = '<p class="small mb-2"><strong>' + p.students + '</strong> student(s)' + (p.staff ? ' and <strong>' + p.staff + '</strong> staff' : '') + ' in this audience.</p>';
            const chs = Object.keys(p.channels || {});
            if (!chs.length) html += '<p class="small text-muted mb-0">Select at least one channel.</p>';
            chs.forEach(c => {
                const x = p.channels[c];
                html += '<div class="nt-prev-ch"><div class="d-flex justify-content-between"><strong>' + NAMES[c] + '</strong><span>' + x.recipients + ' recipient' + (x.recipients === 1 ? '' : 's') + '</span></div>';
                if (!x.enabled) html += '<div class="small text-danger">Switched off in settings — nothing will be sent.</div>';
                else if (!x.live) html += '<div class="small text-warning">Log only — messages are recorded, not delivered.</div>';
                if (x.sms) html += '<div class="small text-muted">' + x.sms.pages + ' page(s) each · about ' + x.sms_total + ' SMS units in total</div>';
                if (x.estimate) {
                    const e = x.estimate, fmt = v => '₦' + Number(v).toLocaleString('en-NG', { minimumFractionDigits: 2 });
                    if (e.cost !== null) html += '<div class="small ' + (e.enough === false ? 'text-danger fw-semibold' : 'text-muted') + '">Estimated cost ' + fmt(e.cost) + (e.balance !== null ? ' · balance ' + fmt(e.balance) : '') + (e.enough === false ? ' — not enough SMS credit' : '') + '</div>';
                    else if (e.balance !== null) html += '<div class="small text-muted">SMS balance ' + fmt(e.balance) + '</div>';
                }
                if (c === 'portal') { html += (x.missing ? '<div class="small text-muted">' + x.missing + ' student(s) have no portal account.</div>' : '') + '<div class="nt-sample">' + esc(x.sample) + '</div></div>'; return; }
                if (x.missing) {
                    html += '<details class="small mt-1"><summary class="text-danger">' + x.missing + ' student(s) have no ' + (c === 'email' ? 'parent email' : 'parent phone') + '</summary><ul class="mb-0 ps-3">' +
                        x.missing_list.map(m => '<li>' + esc(m.name) + ' (' + esc(m.adm) + ')' + (m.class ? ' — ' + esc(m.class) : '') + '</li>').join('') +
                        (x.missing > x.missing_list.length ? '<li>…and ' + (x.missing - x.missing_list.length) + ' more</li>' : '') + '</ul></details>';
                }
                html += '<div class="nt-sample">' + esc(x.sample) + '</div></div>';
            });
            box.innerHTML = html;
        } catch (e) {
            box.innerHTML = '<p class="small text-danger mb-0">Could not load the preview.</p>';
        }
    }
    loadPreview();

    // Test to me
    $('testBtn').addEventListener('click', async () => {
        const out = $('testResult'); out.textContent = 'Sending test…';
        const fd = new FormData(form); fd.delete('_method');
        try {
            const j = await fetch(URLS.test, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(r => r.json());
            out.innerHTML = Object.entries(j.results || {}).map(([c, r]) =>
                '<div class="' + (r.status === 'sent' ? 'text-success' : 'text-danger') + '">' + NAMES[c] + ': ' + (r.status === 'sent' ? 'sent to ' + esc(r.to) : esc(r.error || r.status)) + '</div>').join('') || 'Select a channel first.';
        } catch (e) { out.textContent = 'Test failed.'; }
    });

    // Confirm before sending
    form.addEventListener('submit', e => {
        const btn = e.submitter;
        if (!btn || btn.value === 'draft') return;
        const p = lastPreview?.channels || {};
        const parts = Object.keys(p).map(c => p[c].recipients + ' by ' + NAMES[c]);
        const verb = btn.value === 'schedule' ? 'Schedule this notice' : 'Send this notice now';
        if (!confirm(verb + (parts.length ? ' to ' + parts.join(', ') : '') + '?')) e.preventDefault();
    });
})();
</script>
@endsection
