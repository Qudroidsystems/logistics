{{-- resources/views/result-access/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $canUpdate = auth()->user()->can('Update result-access');
@endphp

<style>
.ra-option { display: flex; gap: 12px; align-items: flex-start; border: 1.5px solid var(--cb-border); border-radius: var(--cb-radius-sm); padding: 12px 14px; cursor: pointer; transition: border-color .15s, background .15s; height: 100%; }
.ra-option:hover { border-color: var(--cb-teal); }
.ra-option input { margin-top: 3px; }
.ra-option.checked { border-color: var(--cb-teal); background: var(--cb-hover); }
.ra-option strong { color: var(--cb-heading); font-size: 13px; display: block; }
.ra-option small { color: var(--cb-muted); font-size: 12px; }
.ra-section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--cb-muted); margin: 18px 0 8px; }
.ra-switch-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--cb-border); }
.ra-switch-row:last-child { border-bottom: none; }
.ra-switch-row strong { color: var(--cb-heading); font-size: 13px; }
.ra-switch-row small { color: var(--cb-muted); display: block; font-size: 12px; }
.ra-master { display: flex; align-items: center; gap: 16px; padding: 16px 18px; border-radius: var(--cb-radius-sm); border: 1.5px solid var(--cb-border); }
.ra-master.on  { background: #fef2f2; border-color: #fecaca; }
.ra-master.off { background: var(--cb-surface-2); }
[data-bs-theme="dark"] .ra-master.on { background: #3b1d22; border-color: #7f1d1d; }
.ra-master .form-switch .form-check-input { width: 3em; height: 1.5em; cursor: pointer; }
.ra-bulkbar { display: none; align-items: center; gap: 8px; flex-wrap: wrap; padding: 10px 22px; background: #fff7ed; border-bottom: 1px solid #fed7aa; }
.ra-bulkbar.visible { display: flex; }
[data-bs-theme="dark"] .ra-bulkbar { background: #3a2a14; border-color: #78350f; }
.ra-money { font-variant-numeric: tabular-nums; font-weight: 700; }
.ra-exc-note { font-size: 11px; color: var(--cb-muted); margin-top: 3px; }
.row-check { width: 16px; height: 16px; cursor: pointer; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Result Access Control" icon="ri-lock-password-line"
               subtitle="Decide whether students who owe fees can see their results, and let specific students through when you choose.">
        <x-slot:pills>
            <span class="cb-meta-pill" id="heroStatus">
                <i class="{{ $settings->enabled ? 'ri-lock-line' : 'ri-lock-unlock-line' }}"></i>
                Fee blocking {{ $settings->enabled ? 'ON' : 'OFF' }}
            </span>
            @if($currentTerm && $currentSession)
                <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $currentTerm->term }} · {{ $currentSession->session }}</span>
            @endif
        </x-slot:pills>
    </x-cb.hero>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><x-cb.stat label="Fee blocking" :value="$settings->enabled ? 'On' : 'Off'" icon="ri-shield-keyhole-line" :accent="$settings->enabled ? 'rose' : 'teal'" id="statEnabled" /></div>
        <div class="col-md-4"><x-cb.stat label="Active exceptions (all classes)" :value="$stats['active_exceptions']" icon="ri-key-2-line" accent="green" /></div>
        <div class="col-md-4"><x-cb.stat label="Manually blocked students" :value="$stats['manual_blocks']" icon="ri-forbid-line" accent="amber" /></div>
    </div>

    {{-- ─────────────── SETTINGS ─────────────── --}}
    <x-cb.card title="Blocking rules" icon="ri-settings-3-line">
        <form id="settingsForm" onsubmit="saveSettings(event)">
            <div class="ra-master {{ $settings->enabled ? 'on' : 'off' }}" id="masterBox">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" {{ $settings->enabled ? 'checked' : '' }} {{ $canUpdate ? '' : 'disabled' }}>
                </div>
                <div>
                    <strong style="color:var(--cb-heading)">Block results for students who owe fees</strong>
                    <small class="d-block text-muted">When off, every student can see their results. When on, the rules below decide who is blocked; you can still let individual students through.</small>
                </div>
            </div>

            <div class="ra-section-label">Which debt counts</div>
            <div class="row g-2">
                @foreach(\App\Models\ResultAccessSetting::SCOPES as $key => $label)
                    <div class="col-md-6">
                        <label class="ra-option {{ $settings->debt_scope === $key ? 'checked' : '' }}">
                            <input type="radio" name="debt_scope" value="{{ $key }}" {{ $settings->debt_scope === $key ? 'checked' : '' }} {{ $canUpdate ? '' : 'disabled' }}>
                            <span><strong>{{ $label }}</strong>
                                <small>{{ $key === 'term' ? 'Only the unpaid balance for the term whose results they are opening.' : 'Also counts unpaid balances carried from earlier terms and sessions.' }}</small></span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="ra-section-label">How much debt blocks results</div>
            <div class="row g-2">
                @foreach(\App\Models\ResultAccessSetting::THRESHOLDS as $key => $label)
                    <div class="col-md-4">
                        <label class="ra-option {{ $settings->threshold_type === $key ? 'checked' : '' }}">
                            <input type="radio" name="threshold_type" value="{{ $key }}" {{ $settings->threshold_type === $key ? 'checked' : '' }} {{ $canUpdate ? '' : 'disabled' }}>
                            <span><strong>{{ $label }}</strong>
                                <small>{{ ['any' => 'Owing even ₦1 blocks results.', 'amount' => 'Small balances are ignored — e.g. only block above ₦10,000.', 'percent' => 'e.g. block only if more than 30% of fees is unpaid.'][$key] }}</small></span>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="row g-2 mt-1" id="thresholdValueWrap" style="{{ $settings->threshold_type === 'any' ? 'display:none' : '' }}">
                <div class="col-md-4">
                    <label class="form-label" for="threshold_value" id="thresholdLabel">{{ $settings->threshold_type === 'percent' ? 'Block when more than this % is unpaid' : 'Block when balance is above (₦)' }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="threshold_value" name="threshold_value" value="{{ $settings->threshold_value }}" {{ $canUpdate ? '' : 'disabled' }}>
                </div>
            </div>

            <div class="ra-section-label">Other options</div>
            <div class="ra-switch-row">
                <div><strong>Also block the mock exam report</strong><small>Turn off to let owing students still see mock results.</small></div>
                <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" name="apply_to_mock" {{ $settings->apply_to_mock ? 'checked' : '' }} {{ $canUpdate ? '' : 'disabled' }}></div>
            </div>
            <div class="ra-switch-row">
                <div><strong>Show the student how much they owe</strong><small>Shows the balance and a link to My Payments on the blocked screen.</small></div>
                <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" name="show_amount_owed" {{ $settings->show_amount_owed ? 'checked' : '' }} {{ $canUpdate ? '' : 'disabled' }}></div>
            </div>

            <div class="ra-section-label">Message shown to blocked students (optional)</div>
            <textarea class="form-control" name="blocked_message" rows="2" maxlength="1000" {{ $canUpdate ? '' : 'disabled' }}
                placeholder="Default: Your results for this term are on hold because there is an outstanding fee balance. Please clear the balance or speak to the school bursary.">{{ $settings->blocked_message }}</textarea>

            @if($canUpdate)
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="action-btn btn-primary-cb" id="saveBtn"><i class="ri-save-line"></i>Save rules</button>
                </div>
            @endif
        </form>
    </x-cb.card>

    {{-- ─────────────── STUDENTS ─────────────── --}}
    <x-cb.card title="Students" icon="ri-group-line" :flush="true">
        <x-slot:tools>
            <small class="text-muted" id="studentsSummary">Pick a class to see who is blocked.</small>
        </x-slot:tools>

        <div class="cb-toolbar">
            <select class="cb-select" id="fClass" aria-label="Class">
                <option value="">Select class…</option>
                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
            <select class="cb-select" id="fSession" aria-label="Session">
                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($currentSession && $currentSession->id === $s->id)>{{ $s->session }}</option>@endforeach
            </select>
            <select class="cb-select" id="fTerm" aria-label="Term">
                @foreach($terms as $t)<option value="{{ $t->id }}" @selected($currentTerm && $currentTerm->id === $t->id)>{{ $t->term }}</option>@endforeach
            </select>
            <button type="button" class="action-btn btn-open" onclick="loadStudents()"><i class="ri-refresh-line"></i>Load</button>
            <div class="cb-search ms-lg-auto">
                <i class="ri-search-line"></i>
                <input type="search" id="fSearch" placeholder="Search name or admission no…" aria-label="Search students">
            </div>
            <select class="cb-select" id="fStatus" aria-label="Show">
                <option value="">Everyone</option>
                <option value="owing" selected>Owing only</option>
                <option value="blocked">Blocked</option>
                <option value="exception">Has exception</option>
                <option value="manual_block">Manually blocked</option>
            </select>
        </div>

        @if($canUpdate)
            <div class="ra-bulkbar" id="bulkBar">
                <strong><span id="selCount">0</span> selected</strong>
                <button type="button" class="action-btn btn-go" onclick="openGrant()"><i class="ri-key-2-line"></i>Allow results</button>
                <button type="button" class="action-btn btn-danger-soft" onclick="revokeSelected()"><i class="ri-close-circle-line"></i>Remove exception</button>
                <button type="button" class="action-btn btn-more" onclick="manualSelected(false)"><i class="ri-forbid-line"></i>Block regardless</button>
                <button type="button" class="action-btn btn-more" onclick="manualSelected(true)"><i class="ri-checkbox-circle-line"></i>Remove manual block</button>
                <button type="button" class="action-btn btn-more ms-auto" onclick="clearSel()">Clear</button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="cb-table stack" id="stuTable">
                <thead>
                    <tr>
                        @if($canUpdate)<th style="width:36px"><input type="checkbox" class="row-check" id="checkAll" aria-label="Select all shown"></th>@endif
                        <th>Student</th>
                        <th class="num">Owed (this term)</th>
                        <th class="num">Arrears</th>
                        <th class="num">% unpaid</th>
                        <th>Result access</th>
                        @if($canUpdate)<th class="text-end">Actions</th>@endif
                    </tr>
                </thead>
                <tbody id="stuBody">
                    <tr><td colspan="7"><div class="empty-state" style="padding:36px"><i class="ri-building-line" style="font-size:44px"></i><h6>Select a class</h6><p>Choose a class, session and term, then Load.</p></div></td></tr>
                </tbody>
            </table>
        </div>
    </x-cb.card>

    {{-- ─────────────── HISTORY ─────────────── --}}
    <x-cb.card title="Exception history" icon="ri-history-line" :flush="true">
        <x-slot:tools>
            <div class="cb-search" style="max-width:240px"><i class="ri-search-line"></i><input type="search" id="hSearch" placeholder="Search student…" aria-label="Search history"></div>
        </x-slot:tools>
        <div class="table-responsive">
            <table class="cb-table stack">
                <thead><tr><th>Student</th><th>Covers</th><th>Expires</th><th>Reason</th><th>Granted</th><th>Status</th></tr></thead>
                <tbody id="histBody"><tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr></tbody>
            </table>
        </div>
    </x-cb.card>

</div>
</div>
</div>

{{-- Grant modal --}}
<div class="modal fade" id="grantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-key-2-line me-2"></i>Allow results for <span id="grantCount">0</span> student(s)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="grantNames" class="small text-muted mb-3"></div>
                <label class="form-label">Covers</label>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="ra-option checked"><input type="radio" name="gScope" value="term" checked><span><strong>This term only</strong><small id="gTermLabel"></small></span></label></div>
                    <div class="col-6"><label class="ra-option"><input type="radio" name="gScope" value="all"><span><strong>All terms</strong><small>Until you remove it</small></span></label></div>
                </div>
                <label class="form-label" for="gExpires">Expires on (optional)</label>
                <input type="date" class="form-control mb-1" id="gExpires" min="{{ now()->toDateString() }}">
                <small class="text-muted d-block mb-3">Leave empty for no expiry. After this date the student is blocked again if they still owe.</small>
                <label class="form-label" for="gReason">Reason (optional)</label>
                <input type="text" class="form-control" id="gReason" maxlength="500" placeholder="e.g. Parent promised to pay by Friday">
            </div>
            <div class="modal-footer">
                <button type="button" class="action-btn btn-more" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="action-btn btn-go" onclick="submitGrant()"><i class="ri-check-line"></i>Allow results</button>
            </div>
        </div>
    </div>
</div>

<div id="raToastHost" aria-live="polite" style="position:fixed;bottom:20px;right:20px;z-index:1090;display:flex;flex-direction:column;gap:8px;"></div>

<script>
(function () {
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CAN       = @json($canUpdate);
    const URLS      = {
        settings: @json(route('result-access.settings')),
        students: @json(route('result-access.students')),
        grant:    @json(route('result-access.grant')),
        revoke:   @json(route('result-access.revoke')),
        manual:   @json(route('result-access.manual')),
        history:  @json(route('result-access.history')),
    };
    const STATUS = {
        blocked:         ['Blocked',              'st-danger',  'ri-lock-line'],
        would_block:     ['Would be blocked',     'st-warning', 'ri-lock-line'],
        exception:       ['Allowed (exception)',  'st-success', 'ri-key-2-line'],
        under_threshold: ['Allowed (below limit)','st-info',    'ri-checkbox-circle-line'],
        clear:           ['Allowed',              'st-muted',   'ri-checkbox-circle-line'],
        manual_block:    ['Manually blocked',     'st-violet',  'ri-forbid-line'],
    };
    let rows = [];

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const naira = v => '₦' + Number(v || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function toast(msg, type) {
        const colors = { success: '#16a34a', danger: '#dc2626', warning: '#d97706', info: '#0d9488' };
        const el = document.createElement('div');
        el.setAttribute('role', 'status');
        el.style.cssText = `background:var(--cb-surface);color:var(--cb-text);border-left:4px solid ${colors[type] || colors.info};padding:11px 16px;border-radius:10px;box-shadow:0 8px 24px rgba(15,35,66,.18);max-width:380px;font-size:13px;`;
        el.textContent = msg;
        document.getElementById('raToastHost').appendChild(el);
        setTimeout(() => el.remove(), 4200);
    }

    async function call(url, method, body) {
        const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } };
        if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const res  = await fetch(url, opts);
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            const firstErr = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            throw new Error(firstErr || data.message || 'Request failed');
        }
        return data;
    }

    // ── settings ─────────────────────────────────────────────
    const form = document.getElementById('settingsForm');
    form.addEventListener('change', e => {
        if (e.target.type === 'radio') {
            form.querySelectorAll(`input[name="${e.target.name}"]`).forEach(r => r.closest('.ra-option')?.classList.toggle('checked', r.checked));
        }
        if (e.target.name === 'threshold_type') {
            document.getElementById('thresholdValueWrap').style.display = e.target.value === 'any' ? 'none' : '';
            document.getElementById('thresholdLabel').textContent = e.target.value === 'percent' ? 'Block when more than this % is unpaid' : 'Block when balance is above (₦)';
        }
        if (e.target.id === 'enabled') {
            document.getElementById('masterBox').className = 'ra-master ' + (e.target.checked ? 'on' : 'off');
        }
    });

    window.saveSettings = async function (e) {
        e.preventDefault();
        const fd = new FormData(form);
        const body = {
            enabled:          form.enabled.checked,
            debt_scope:       fd.get('debt_scope'),
            threshold_type:   fd.get('threshold_type'),
            threshold_value:  fd.get('threshold_value') || 0,
            apply_to_mock:    form.apply_to_mock.checked,
            show_amount_owed: form.show_amount_owed.checked,
            blocked_message:  fd.get('blocked_message') || null,
        };
        if (body.enabled && !confirm('Turn ON fee blocking? Students who owe (per these rules) will lose access to their results until they pay or you grant an exception.')) return;
        try {
            const d = await call(URLS.settings, 'POST', body);
            toast(d.message, 'success');
            document.getElementById('heroStatus').innerHTML = `<i class="${body.enabled ? 'ri-lock-line' : 'ri-lock-unlock-line'}"></i>Fee blocking ${body.enabled ? 'ON' : 'OFF'}`;
            const stat = document.querySelector('#statEnabled .stat-value'); if (stat) stat.textContent = body.enabled ? 'On' : 'Off';
            if (rows.length) loadStudents();
        } catch (err) { toast(err.message, 'danger'); }
    };

    // ── students ─────────────────────────────────────────────
    const body = document.getElementById('stuBody');
    const period = () => ({
        class_id: document.getElementById('fClass').value,
        session_id: document.getElementById('fSession').value,
        term_id: document.getElementById('fTerm').value,
    });

    window.loadStudents = async function () {
        const p = period();
        if (!p.class_id) { toast('Choose a class first.', 'warning'); return; }
        body.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Working out balances…</td></tr>`;
        try {
            const d = await call(URLS.students + '?' + new URLSearchParams(p), 'GET');
            rows = d.students || [];
            const s = d.summary;
            document.getElementById('studentsSummary').textContent =
                `${s.total} students · ${s.owing} owing (${naira(s.owed)}) · ${s.blocked} blocked · ${s.exception} with exception · ${s.manual} manually blocked`;
            render();
        } catch (err) {
            body.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${esc(err.message)}</td></tr>`;
        }
    };
    ['fClass', 'fSession', 'fTerm'].forEach(id => document.getElementById(id).addEventListener('change', () => { if (document.getElementById('fClass').value) loadStudents(); }));

    function visibleRows() {
        const q  = document.getElementById('fSearch').value.trim().toLowerCase();
        const st = document.getElementById('fStatus').value;
        return rows.filter(r => {
            if (q && !(r.name.toLowerCase().includes(q) || String(r.admissionno || '').toLowerCase().includes(q))) return false;
            if (st === 'owing') return r.owed > 0.009;
            if (st === 'blocked') return r.status === 'blocked' || r.status === 'would_block';
            if (st) return r.status === st;
            return true;
        });
    }

    function render() {
        const list = visibleRows();
        if (!list.length) {
            body.innerHTML = `<tr><td colspan="7"><div class="empty-state" style="padding:32px"><i class="ri-emotion-happy-line" style="font-size:40px"></i><h6>No students match</h6><p>${rows.length ? 'Try “Everyone” in the Show filter.' : 'No students found for this class and term.'}</p></div></td></tr>`;
            updateBulk();
            return;
        }
        body.innerHTML = list.map(r => {
            const [label, cls, icon] = STATUS[r.status] || STATUS.clear;
            const ex = r.exception;
            const exNote = ex ? `<div class="ra-exc-note">${ex.all_terms ? 'All terms' : 'This term'}${ex.expires_on ? ' · until ' + esc(ex.expires_on) : ''} · by ${esc(ex.granted_by)}${ex.reason ? ' — “' + esc(ex.reason) + '”' : ''}</div>` : '';
            let actions = '';
            if (CAN) {
                if (r.status === 'manual_block') {
                    actions = `<button class="action-btn btn-more" onclick="manualOne(${r.id}, true)"><i class="ri-checkbox-circle-line"></i>Unblock</button>`;
                } else if (ex) {
                    actions = `<button class="action-btn btn-danger-soft" onclick="revokeOne(${ex.id})"><i class="ri-close-circle-line"></i>Remove exception</button>`;
                } else if (r.status === 'blocked' || r.status === 'would_block') {
                    actions = `<button class="action-btn btn-go" onclick="openGrant([${r.id}])"><i class="ri-key-2-line"></i>Allow results</button>`;
                }
                actions += ` <div class="dropdown d-inline-block"><button class="action-btn btn-more" data-bs-toggle="dropdown" aria-label="More"><i class="ri-more-2-fill"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="openGrant([${r.id}]);return false;"><i class="ri-key-2-line me-2 text-success"></i>Allow results…</a></li>
                        ${r.manual
                            ? `<li><a class="dropdown-item" href="#" onclick="manualOne(${r.id}, true);return false;"><i class="ri-checkbox-circle-line me-2"></i>Remove manual block</a></li>`
                            : `<li><a class="dropdown-item" href="#" onclick="manualOne(${r.id}, false);return false;"><i class="ri-forbid-line me-2 text-danger"></i>Block regardless of fees</a></li>`}
                    </ul></div>`;
            }
            return `<tr data-id="${r.id}">
                ${CAN ? `<td><input type="checkbox" class="row-check row-select" value="${r.id}" aria-label="Select ${esc(r.name)}"></td>` : ''}
                <td data-label="Student"><span class="subject-name">${esc(r.name)}</span><div class="small text-muted">${esc(r.admissionno || '—')}</div></td>
                <td data-label="Owed" class="num ra-money ${r.term_owed > 0 ? 'text-danger' : 'text-success'}">${naira(r.term_owed)}</td>
                <td data-label="Arrears" class="num">${r.arrears > 0 ? naira(r.arrears) : '<span class="text-muted">—</span>'}</td>
                <td data-label="% unpaid" class="num">${r.payable > 0 ? r.percent_owed + '%' : '—'}</td>
                <td data-label="Access"><span class="status-pill ${cls}"><i class="${icon}"></i>${label}</span>${exNote}</td>
                ${CAN ? `<td data-label="Actions" class="text-end" style="white-space:nowrap">${actions}</td>` : ''}
            </tr>`;
        }).join('');
        updateBulk();
    }
    document.getElementById('fSearch').addEventListener('input', render);
    document.getElementById('fStatus').addEventListener('change', render);

    // ── selection ────────────────────────────────────────────
    const selected = () => [...document.querySelectorAll('.row-select:checked')].map(c => Number(c.value));
    function updateBulk() {
        const bar = document.getElementById('bulkBar'); if (!bar) return;
        const n = selected().length;
        document.getElementById('selCount').textContent = n;
        bar.classList.toggle('visible', n > 0);
    }
    document.getElementById('stuTable').addEventListener('change', e => { if (e.target.classList.contains('row-select')) updateBulk(); });
    document.getElementById('checkAll')?.addEventListener('change', e => {
        document.querySelectorAll('.row-select').forEach(c => c.checked = e.target.checked);
        updateBulk();
    });
    window.clearSel = () => { document.querySelectorAll('.row-select, #checkAll').forEach(c => c.checked = false); updateBulk(); };

    // ── grant ────────────────────────────────────────────────
    let grantIds = [];
    window.openGrant = function (ids) {
        grantIds = ids || selected();
        if (!grantIds.length) return toast('Select at least one student.', 'warning');
        const names = rows.filter(r => grantIds.includes(r.id)).map(r => r.name);
        document.getElementById('grantCount').textContent = grantIds.length;
        document.getElementById('grantNames').textContent = names.slice(0, 6).join(' · ') + (names.length > 6 ? ` and ${names.length - 6} more` : '');
        const t = document.getElementById('fTerm'), s = document.getElementById('fSession');
        document.getElementById('gTermLabel').textContent = `${t.options[t.selectedIndex].text} · ${s.options[s.selectedIndex].text}`;
        document.getElementById('gExpires').value = '';
        document.getElementById('gReason').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('grantModal')).show();
    };
    document.getElementById('grantModal').addEventListener('change', e => {
        if (e.target.name === 'gScope') document.querySelectorAll('input[name="gScope"]').forEach(r => r.closest('.ra-option').classList.toggle('checked', r.checked));
    });
    window.submitGrant = async function () {
        const p = period();
        try {
            const d = await call(URLS.grant, 'POST', {
                student_ids: grantIds,
                scope: document.querySelector('input[name="gScope"]:checked').value,
                term_id: p.term_id, session_id: p.session_id,
                expires_on: document.getElementById('gExpires').value || null,
                reason: document.getElementById('gReason').value || null,
            });
            bootstrap.Modal.getInstance(document.getElementById('grantModal'))?.hide();
            toast(d.message, 'success');
            clearSel(); loadStudents(); loadHistory();
        } catch (err) { toast(err.message, 'danger'); }
    };

    // ── revoke / manual ──────────────────────────────────────
    window.revokeOne = async function (exceptionId) {
        if (!confirm('Remove this exception? The student will be blocked again if they still owe.')) return;
        try { const d = await call(URLS.revoke, 'POST', { exception_id: exceptionId }); toast(d.message, 'success'); loadStudents(); loadHistory(); }
        catch (err) { toast(err.message, 'danger'); }
    };
    window.revokeSelected = async function () {
        const ids = selected(); if (!ids.length) return;
        if (!confirm(`Remove exceptions for ${ids.length} student(s)?`)) return;
        const p = period();
        try { const d = await call(URLS.revoke, 'POST', { student_ids: ids, term_id: p.term_id, session_id: p.session_id }); toast(d.message, 'success'); clearSel(); loadStudents(); loadHistory(); }
        catch (err) { toast(err.message, 'danger'); }
    };
    async function manual(ids, canView) {
        if (!canView && !confirm(`Block ${ids.length} student(s) from their results regardless of fees? Exceptions won't override this.`)) return;
        try { const d = await call(URLS.manual, 'POST', { student_ids: ids, can_view: canView }); toast(d.message, 'success'); clearSel(); loadStudents(); }
        catch (err) { toast(err.message, 'danger'); }
    }
    window.manualOne = (id, canView) => manual([id], canView);
    window.manualSelected = canView => { const ids = selected(); if (ids.length) manual(ids, canView); };

    // ── history ──────────────────────────────────────────────
    const HSTATE = { active: ['Active', 'st-success'], expired: ['Expired', 'st-muted'], revoked: ['Removed', 'st-danger'] };
    let hTimer;
    async function loadHistory() {
        const q = document.getElementById('hSearch').value.trim();
        try {
            const d = await call(URLS.history + (q ? '?q=' + encodeURIComponent(q) : ''), 'GET');
            const h = d.history || [];
            document.getElementById('histBody').innerHTML = h.length ? h.map(e => {
                const [l, c] = HSTATE[e.state];
                return `<tr>
                    <td data-label="Student"><span class="subject-name">${esc(e.student)}</span><div class="small text-muted">${esc(e.admissionno || '')}</div></td>
                    <td data-label="Covers">${esc(e.covers)}</td>
                    <td data-label="Expires">${esc(e.expires_on || '—')}</td>
                    <td data-label="Reason" class="small">${esc(e.reason || '—')}</td>
                    <td data-label="Granted" class="small">${esc(e.granted_by)}<div class="text-muted">${esc(e.granted_at)}</div></td>
                    <td data-label="Status"><span class="status-pill ${c}">${l}</span>${e.revoked_at ? `<div class="small text-muted">${esc(e.revoked_by || '')} · ${esc(e.revoked_at)}</div>` : ''}</td>
                </tr>`;
            }).join('') : `<tr><td colspan="6" class="text-center text-muted py-4">No exceptions granted yet.</td></tr>`;
        } catch (err) {
            document.getElementById('histBody').innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${esc(err.message)}</td></tr>`;
        }
    }
    window.loadHistory = loadHistory;
    document.getElementById('hSearch').addEventListener('input', () => { clearTimeout(hTimer); hTimer = setTimeout(loadHistory, 350); });
    loadHistory();
})();
</script>
@endsection
