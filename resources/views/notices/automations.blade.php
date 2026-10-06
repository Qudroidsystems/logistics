{{-- resources/views/notices/automations.blade.php — absence alerts, fee reminders, birthday wishes --}}
@extends('layouts.master')

@section('content')
@php
    $meta = [
        'absence'  => ['Absence alerts', 'ri-user-unfollow-line', 'Every school day, after attendance is taken, parents of students marked absent get a message. Works with teacher-marked and device attendance.'],
        'fees'     => ['Fee reminders', 'ri-money-dollar-circle-line', 'On the dates you set, parents of students who still owe for the current term (optionally plus arrears) get their balance and the Pay online link.'],
        'birthday' => ['Birthday wishes', 'ri-cake-2-line', 'On each student\'s birthday (from date of birth), a short greeting goes to the parents and/or the student.'],
    ];
    $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
    $statusLabels = ['absent' => 'Absent', 'late' => 'Late', 'sick_leave' => 'Sick leave', 'excused' => 'Excused'];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Automatic Messages" icon="ri-robot-2-line"
               subtitle="Set once — the portal sends them on schedule. Nothing is ever sent twice to the same contact."
               :back="route('notices.index')" back-label="Notices">
        <x-slot:actions>
            <a class="cb-hero-btn" href="{{ route('notices.settings') }}"><i class="ri-settings-3-line"></i>Channel settings</a>
        </x-slot:actions>
    </x-cb.hero>

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ implode(' ', $errors->all()) }}</div></div>@endif

    @foreach($settings as $type => $s)
        @php [$title, $icon, $help] = $meta[$type]; $c = $s->config; $st = $stats[$type] ?? collect(); @endphp
        <div class="cb-card" id="{{ $type }}">
            <div class="cb-card-header">
                <h5><i class="{{ $icon }}"></i>{{ $title }}</h5>
                <div class="d-flex gap-2 align-items-center">
                    <small class="text-muted">This month: {{ $st['sent'] ?? 0 }} sent{{ ($st['failed'] ?? 0) ? ', ' . $st['failed'] . ' failed' : '' }}</small>
                    <span class="status-pill {{ $s->is_active ? 'st-paid' : 'st-muted' }}">{{ $s->is_active ? 'On' : 'Off' }}</span>
                </div>
            </div>
            <div class="cb-card-body">
                <p class="small text-muted">{{ $help }}</p>
                <form method="POST" action="{{ route('notices.automations.update', $type) }}">
                    @csrf @method('PUT')
                    <div class="d-flex flex-wrap gap-4 align-items-center mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="on-{{ $type }}" @checked($s->is_active)>
                            <label class="form-check-label" for="on-{{ $type }}">Send automatically</label>
                        </div>
                        @foreach($channels as $ck => $ch)
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="{{ $ck }}" @checked(in_array($ck, $c['channels'] ?? []))>
                                <span class="form-check-label">{{ $ch['label'] }}@unless($ch['enabled']) <small class="text-muted">(off)</small>@endunless</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="row g-3 mb-3">
                        @if($type === 'absence')
                            <div class="col-md-2">
                                <label class="form-label" for="t-absence">Send at</label>
                                <input type="time" class="form-control" id="t-absence" name="time" value="{{ $c['time'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">School days</label>
                                @foreach($days as $n => $l)
                                    <label class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="days[]" value="{{ $n }}" @checked(in_array($n, $c['days'] ?? []))><span class="form-check-label">{{ $l }}</span></label>
                                @endforeach
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Alert for</label>
                                @foreach($statusLabels as $k => $l)
                                    <label class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="statuses[]" value="{{ $k }}" @checked(in_array($k, $c['statuses'] ?? []))><span class="form-check-label">{{ $l }}</span></label>
                                @endforeach
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="p-absence">Register</label>
                                <select class="form-select" id="p-absence" name="period">
                                    <option value="morning" @selected(($c['period'] ?? '') === 'morning')>Morning only</option>
                                    <option value="any" @selected(($c['period'] ?? '') === 'any')>Morning or afternoon</option>
                                </select>
                            </div>
                        @elseif($type === 'fees')
                            <div class="col-md-8">
                                <label class="form-label d-flex justify-content-between">Send on these dates <button type="button" class="action-btn btn-open am-add-date"><i class="ri-add-line"></i>Add date</button></label>
                                <div class="am-dates d-flex flex-column gap-2">
                                    @foreach(array_values($c['dates'] ?? []) as $i => $d)
                                        <div class="d-flex gap-2 am-date">
                                            <input type="date" class="form-control form-control-sm" name="dates[{{ $i }}][date]" value="{{ $d['date'] }}" style="max-width:170px">
                                            <input type="time" class="form-control form-control-sm" name="dates[{{ $i }}][time]" value="{{ $d['time'] ?? '08:00' }}" style="max-width:120px">
                                            @if(in_array(($d['date'] ?? '') . ' ' . ($d['time'] ?? '08:00'), $c['done'] ?? []))<span class="status-pill st-paid align-self-center">Sent</span>@endif
                                            <button type="button" class="btn btn-sm btn-light am-del" aria-label="Remove date">×</button>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">e.g. 3 weeks and 1 week before exams. Uses the current session and each student's current term.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="mb-fees">Only if owing at least (₦)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="mb-fees" name="min_balance" value="{{ $c['min_balance'] ?? 0 }}">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="include_arrears" value="1" id="ar-fees" @checked(!empty($c['include_arrears']))>
                                    <label class="form-check-label" for="ar-fees">Include arrears from earlier terms</label>
                                </div>
                            </div>
                        @else
                            <div class="col-md-2">
                                <label class="form-label" for="t-birthday">Send at</label>
                                <input type="time" class="form-control" id="t-birthday" name="time" value="{{ $c['time'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="a-birthday">Send to</label>
                                <select class="form-select" id="a-birthday" name="audience">
                                    <option value="parents" @selected(($c['audience'] ?? '') === 'parents')>Parents</option>
                                    <option value="students" @selected(($c['audience'] ?? '') === 'students')>Students (their own phone/email)</option>
                                    <option value="both" @selected(($c['audience'] ?? '') === 'both')>Parents and students</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="mb-1 d-flex flex-wrap gap-1">@foreach($placeholders[$type] as $ph)<code class="small">{{ $ph }}</code>@endforeach</div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="m-{{ $type }}">Message (email / WhatsApp)</label>
                            <textarea class="form-control" id="m-{{ $type }}" name="message" rows="6" maxlength="2000">{{ $c['message'] }}</textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="s-{{ $type }}">SMS text</label>
                            <textarea class="form-control" id="s-{{ $type }}" name="sms_text" rows="4" maxlength="459">{{ $c['sms_text'] }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="action-btn btn-open am-preview" data-type="{{ $type }}"><i class="ri-eye-line"></i>Who gets it today?</button>
                            <button type="submit" formaction="{{ route('notices.automations.run', $type) }}" formmethod="POST" class="action-btn btn-go"
                                    onclick="return confirm('Send {{ strtolower($title) }} now to everyone who qualifies today? Anyone already messaged is skipped.')"><i class="ri-send-plane-line"></i>Send now</button>
                        </div>
                        <button class="action-btn btn-primary-cb"><i class="ri-save-3-line"></i>Save</button>
                    </div>
                    <div class="am-preview-box small mt-3" hidden></div>
                </form>
            </div>
        </div>
    @endforeach

    <x-cb.card title="Recent automatic messages" icon="ri-history-line" :flush="true">
        @if($recent->isEmpty())
            <div class="empty-state"><i class="ri-inbox-line"></i><h6>Nothing sent yet</h6></div>
        @else
            <div class="table-responsive">
                <table class="cb-table mb-0">
                    <thead><tr><th>Time</th><th>Type</th><th>To</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($recent as $r)
                        <tr>
                            <td><small>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M, g:i a') }}</small></td>
                            <td>{{ $meta[$r->type][0] ?? $r->type }}</td>
                            <td><small>{{ $r->channel === 'whatsapp' ? 'WhatsApp' : strtoupper($r->channel) }} · {{ $r->recipient_name }} {{ $r->recipient }}</small></td>
                            <td><span class="status-pill {{ $r->status === 'sent' ? 'st-paid' : ($r->status === 'failed' ? 'st-danger' : 'st-muted') }}">{{ ucfirst($r->status) }}</span>
                                @if($r->error)<br><small class="text-danger">{{ $r->error }}</small>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-cb.card>
</div>
</div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const urlTpl = @json(route('notices.automations.preview', ['type' => '__T__']));
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const money = v => '₦' + Number(v).toLocaleString('en-NG', { minimumFractionDigits: 2 });

    document.querySelectorAll('.am-preview').forEach(btn => btn.addEventListener('click', async () => {
        const box = btn.closest('form').querySelector('.am-preview-box');
        box.hidden = false; box.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking… (save first if you changed the settings)';
        try {
            const j = await fetch(urlTpl.replace('__T__', btn.dataset.type), { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
            let h = '<div class="cb-banner info mb-2"><i class="ri-information-line"></i><div><strong>' + j.students + '</strong> student(s) qualify today · messages: ' +
                (Object.entries(j.messages || {}).map(([c, n]) => n + ' ' + (c === 'whatsapp' ? 'WhatsApp' : c.toUpperCase())).join(', ') || 'none (no contacts or channels)');
            if (j.sms) h += ' · ' + j.sms.pages + ' SMS pages' + (j.sms.cost !== null ? ' (≈ ' + money(j.sms.cost) + ')' : '') + (j.sms.balance !== null ? ', balance ' + money(j.sms.balance) : '') + (j.sms.enough === false ? ' — <strong class="text-danger">not enough credit</strong>' : '');
            h += '</div></div>';
            if (j.list && j.list.length) h += '<div class="mb-2">' + j.list.map(esc).join(' · ') + (j.students > j.list.length ? ' …' : '') + '</div>';
            if (j.sample) h += '<pre class="p-2 rounded" style="white-space:pre-wrap;background:var(--cb-surface-2)">' + esc(j.sample) + '</pre>';
            box.innerHTML = h;
        } catch (e) { box.innerHTML = '<span class="text-danger">Could not load the preview.</span>'; }
    }));

    document.querySelectorAll('.am-add-date').forEach(b => b.addEventListener('click', () => {
        const wrap = b.closest('.col-md-8').querySelector('.am-dates');
        const i = Date.now();
        const div = document.createElement('div'); div.className = 'd-flex gap-2 am-date';
        div.innerHTML = '<input type="date" class="form-control form-control-sm" name="dates[' + i + '][date]" style="max-width:170px">' +
            '<input type="time" class="form-control form-control-sm" name="dates[' + i + '][time]" value="08:00" style="max-width:120px">' +
            '<button type="button" class="btn btn-sm btn-light am-del" aria-label="Remove date">×</button>';
        wrap.appendChild(div);
    }));
    document.addEventListener('click', e => { if (e.target.classList.contains('am-del')) e.target.closest('.am-date').remove(); });
})();
</script>
@endsection
