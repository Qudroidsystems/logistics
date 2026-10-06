@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

@include('mysubjectvettings.partials.styles')

<style>
.bs-table th.num, .bs-table td.num { text-align: center; }
.bs-table td.num { font-variant-numeric: tabular-nums; }
.bs-table .col-total { font-weight: 700; color: var(--cb-navy); background: #f8fafc; }
.bs-table .col-grade { font-weight: 800; }
.bs-table tr.is-vetted td { background: #f0fdf4; }
.bs-table tr.is-vetted:hover td { background: #dcfce7; }
.bs-table tr.no-score td.col-total { color: #dc2626; }
.stu-cell { display: flex; align-items: center; gap: 10px; min-width: 200px; }
.stu-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,.12); cursor: zoom-in; flex-shrink: 0; }
.stu-name { font-weight: 600; color: var(--cb-navy); line-height: 1.2; }
.stu-adm { font-size: 11px; color: var(--cb-muted); }
.th-sub { display: block; font-size: 10px; font-weight: 500; opacity: .75; }
.gc-a { color: #15803d; } .gc-b { color: #1d4ed8; } .gc-c { color: #0369a1; } .gc-d { color: #d97706; } .gc-f { color: #b91c1c; }

.vet-switch { position: relative; display: inline-block; width: 46px; height: 26px; }
.vet-switch input { opacity: 0; width: 0; height: 0; }
.vet-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 26px; transition: .25s; cursor: pointer; }
.vet-slider::before { content: ''; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .25s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.vet-switch input:checked + .vet-slider { background: var(--cb-green); }
.vet-switch input:checked + .vet-slider::before { transform: translateX(20px); }
.vet-switch input:focus-visible + .vet-slider { outline: 2px solid var(--cb-teal); outline-offset: 2px; }
.vet-switch input:disabled + .vet-slider { opacity: .5; cursor: not-allowed; }
.row-check { width: 16px; height: 16px; cursor: pointer; }

.signoff-bar { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; padding: 14px 24px; background: #f8fafc; border-top: 1px solid var(--cb-border); }
.signoff-progress { flex: 1 1 240px; }
.signoff-progress .progress-track { height: 9px; }
</style>

@php
    $statusMeta = [
        'pending'   => ['Pending',   'ri-time-line'],
        'completed' => ['Completed', 'ri-checkbox-circle-line'],
        'rejected'  => ['Sent back', 'ri-arrow-go-back-line'],
    ];
    $isMock   = ($cfg['mode'] ?? 'terminal') === 'mock';
    $colCount = 5 + ($canUpdate ? 1 : 0) + ($isMock ? 2 : 4 + $assessments->count());
    $status  = $assignment->status ?? 'pending';
    $status  = isset($statusMeta[$status]) ? $status : 'pending';
    $percent = $summary['students'] ? (int) round($summary['vetted'] / $summary['students'] * 100) : 0;
    $gradeClass = function ($g) {
        $u = strtoupper((string) $g);
        if (in_array($u, ['A1', 'A'])) return 'gc-a';
        if (in_array($u, ['B2', 'B3', 'B'])) return 'gc-b';
        if (in_array($u, ['C4', 'C5', 'C6', 'C'])) return 'gc-c';
        if (in_array($u, ['D7', 'E8', 'D', 'E'])) return 'gc-d';
        return $u ? 'gc-f' : '';
    };
    $avatar = fn ($pic) => $pic ? asset('storage/student_avatars/' . basename($pic)) : asset('storage/student_avatars/unnamed.jpg');
    $fmt = fn ($v) => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="cb-hero">
        <a href="{{ route($cfg['routes']['index']) }}" class="cb-back"><i class="ri-arrow-left-line"></i>Back to my vetting assignments</a>
        <h1><i class="ri-shield-check-line me-2"></i>{{ $subjectName }}@if($subjectCode) <small style="font-family:'DM Sans';font-size:15px;opacity:.75">({{ $subjectCode }})</small>@endif</h1>
        <p>Check each student's scores, switch on <strong>Vetted</strong> when they're correct, then sign the broadsheet off.</p>
        <div class="meta-pills">
            <span class="cb-meta-pill"><i class="ri-building-line"></i>{{ $className }}</span>
            <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $schoolterm }} · {{ $schoolsession }}</span>
            <span class="cb-meta-pill"><i class="ri-user-star-line"></i>Teacher: {{ $teacherName }}</span>
            <span class="cb-meta-pill" id="heroStatus"><i class="{{ $statusMeta[$status][1] }}"></i>{{ $statusMeta[$status][0] }}</span>
        </div>
    </div>

    @if(!$assignment)
        <div class="alert alert-info d-flex align-items-center gap-2"><i class="ri-information-line fs-5"></i>
            You're viewing this broadsheet as an administrator. It isn't assigned to you, so vetting controls are read-only.</div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-navy),var(--cb-teal));"></div>
                <div class="stat-ico"><i class="ri-group-line"></i></div>
                <div class="stat-value">{{ $summary['students'] }}</div>
                <div class="stat-label">Students</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-green),#86efac);"></div>
                <div class="stat-ico"><i class="ri-shield-check-line"></i></div>
                <div class="stat-value text-success" id="statVetted">{{ $summary['vetted'] }}</div>
                <div class="stat-label">Vetted</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-amber),#fcd34d);"></div>
                <div class="stat-ico"><i class="ri-hourglass-line"></i></div>
                <div class="stat-value text-warning" id="statRemaining">{{ $summary['students'] - $summary['vetted'] }}</div>
                <div class="stat-label">Still to vet</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-violet),#a78bfa);"></div>
                <div class="stat-ico"><i class="ri-bar-chart-2-line"></i></div>
                <div class="stat-value" style="color:var(--cb-violet)">{{ $summary['average'] }}</div>
                <div class="stat-label">Class average (term total)
                    @if($summary['entered'] < $summary['students'])
                        · <span class="text-danger">{{ $summary['students'] - $summary['entered'] }} blank</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="cb-card mb-4">
        <div class="cb-card-header">
            <h5><i class="ri-table-line" style="color:var(--cb-teal)"></i>Broadsheet
                <span class="cb-count" id="visibleCount">{{ $broadsheets->count() }}</span>
            </h5>
            <small class="text-muted">Scores are read-only here — corrections are made by the subject teacher in score entry.</small>
        </div>

        @if($broadsheets->isNotEmpty())
            <div class="cb-toolbar">
                <div class="cb-search">
                    <i class="ri-search-line"></i>
                    <input type="search" id="stuSearch" placeholder="Search name or admission no…" aria-label="Search students">
                </div>
                <select class="cb-select" id="vetFilter" aria-label="Show">
                    <option value="">All students</option>
                    <option value="unvetted">Not vetted yet</option>
                    <option value="vetted">Vetted</option>
                    <option value="blank">No score entered</option>
                </select>
                @if($canUpdate)
                    <div class="ms-auto d-flex gap-2 flex-wrap">
                        <button type="button" class="action-btn btn-vet-all" onclick="bulkVet(1)" title="Vet the ticked students, or every visible student if none are ticked">
                            <i class="ri-check-double-line"></i><span id="vetAllLabel">Vet all shown</span>
                        </button>
                        <button type="button" class="action-btn btn-unvet-all" onclick="bulkVet(0)">
                            <i class="ri-close-line"></i>Un-vet
                        </button>
                    </div>
                @endif
            </div>

            <div class="table-responsive" style="max-height:70vh">
                <table class="cb-table bs-table" id="bsTable">
                    <thead>
                        <tr>
                            @if($canUpdate)<th style="width:36px"><input type="checkbox" class="row-check" id="checkAll" aria-label="Select all shown"></th>@endif
                            <th class="num" style="width:44px">#</th>
                            <th>Student</th>
                            @if($isMock)
                                <th class="num">Exam</th>
                                <th class="num">Total</th>
                            @else
                                @foreach($assessments as $assessment)
                                    <th class="num">{{ $assessment->name }}<span class="th-sub">/ {{ $fmt($assessment->max_score) }}</span></th>
                                @endforeach
                                <th class="num">Total</th>
                                <th class="num">BF</th>
                                <th class="num">Cum</th>
                                <th class="num">Cum Avg</th>
                            @endif
                            <th class="num">Grade</th>
                            <th class="num">Pos.</th>
                            <th class="num">Vetted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($broadsheets as $i => $b)
                            @php
                                $vetted  = (int) $b->vettedstatus === 1;
                                $noScore = (float) $b->total <= 0;
                                $name    = trim(($b->lname ?? '') . ', ' . ($b->fname ?? '') . ' ' . ($b->mname ?? ''), ', ');
                                $scores  = $b->scores ?? [];
                            @endphp
                            <tr data-id="{{ $b->id }}"
                                data-vetted="{{ $vetted ? 1 : 0 }}"
                                data-blank="{{ $noScore ? 1 : 0 }}"
                                data-search="{{ strtolower($name . ' ' . $b->admissionno) }}"
                                class="{{ $vetted ? 'is-vetted' : '' }} {{ $noScore ? 'no-score' : '' }}">
                                @if($canUpdate)<td><input type="checkbox" class="row-check row-select" value="{{ $b->id }}" aria-label="Select {{ $name }}"></td>@endif
                                <td class="num text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="stu-cell">
                                        <img src="{{ $avatar($b->picture) }}" alt="" class="stu-avatar" loading="lazy"
                                             data-full="{{ $avatar($b->picture) }}" data-name="{{ $name }}"
                                             onerror="this.onerror=null;this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                        <div>
                                            <div class="stu-name">{{ $name ?: '—' }}</div>
                                            <div class="stu-adm">{{ $b->admissionno ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                @if($isMock)
                                    <td class="num">{{ $fmt($b->exam ?? null) }}</td>
                                    <td class="num col-total">{{ $fmt($b->total) }}</td>
                                @else
                                    @foreach($assessments as $assessment)
                                        <td class="num">{{ $fmt($scores[$assessment->id] ?? 0) }}</td>
                                    @endforeach
                                    <td class="num col-total">{{ $fmt($b->total) }}</td>
                                    <td class="num">{{ $fmt($b->bf ?? null) }}</td>
                                    <td class="num">{{ $fmt($b->cum ?? null) }}</td>
                                    <td class="num">{{ $fmt($b->cum_ave ?? null) }}</td>
                                @endif
                                <td class="num col-grade {{ $gradeClass($b->grade) }}">{{ $b->grade ?: '—' }}</td>
                                <td class="num">{{ $b->position ?: '—' }}</td>
                                <td class="num">
                                    <label class="vet-switch" title="{{ $vetted ? 'Vetted' : 'Not vetted' }}">
                                        <input type="checkbox" class="vet-toggle" data-id="{{ $b->id }}" {{ $vetted ? 'checked' : '' }}
                                               {{ $canUpdate ? '' : 'disabled' }} aria-label="Vetted: {{ $name }}">
                                        <span class="vet-slider"></span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="no-match" id="bsNoMatch">
                            <td colspan="{{ $colCount }}">
                                <div class="empty-state" style="padding:28px"><h6>No students match</h6></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Sign-off --}}
            <div class="signoff-bar">
                <div class="signoff-progress">
                    <div class="d-flex justify-content-between small mb-1">
                        <strong style="color:var(--cb-navy)">Vetting progress</strong>
                        <span id="progressText">{{ $summary['vetted'] }}/{{ $summary['students'] }} · {{ $percent }}%</span>
                    </div>
                    <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" id="progressBar">
                        <div class="progress-fill" id="progressFill" style="width:{{ $percent }}%;background:{{ $percent >= 100 ? 'var(--cb-green)' : 'var(--cb-amber)' }}"></div>
                    </div>
                </div>
                <span class="status-pill st-{{ $status }}" id="statusPill"><i class="{{ $statusMeta[$status][1] }}"></i>{{ $statusMeta[$status][0] }}</span>
                @if($canUpdate)
                    <button type="button" class="action-btn btn-reject" onclick="setStatus('rejected')">
                        <i class="ri-arrow-go-back-line"></i>Send back to teacher
                    </button>
                    <button type="button" class="action-btn btn-complete" id="completeBtn" onclick="setStatus('completed')" {{ $percent < 100 ? 'disabled' : '' }}>
                        <i class="ri-checkbox-circle-line"></i>Mark completed
                    </button>
                @endif
            </div>
        @else
            <div class="empty-state">
                <i class="ri-file-list-3-line"></i>
                <h6>No scores yet</h6>
                <p>The subject teacher hasn't entered any scores for this class, term and session.</p>
            </div>
        @endif
    </div>

</div>
</div>
</div>

{{-- Photo preview --}}
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:16px;overflow:hidden">
            <div class="modal-header py-2"><h6 class="modal-title" id="photoName"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body text-center p-2"><img src="" id="photoImg" class="img-fluid rounded" alt=""></div>
        </div>
    </div>
</div>

@include('mysubjectvettings.partials.toast')

<script>
(function () {
    const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ctx    = { subjectclass_id: {{ $subjectclassid }}, term_id: {{ $termid }}, session_id: {{ $sessionid }} };
    const svid   = {{ $assignment->id ?? 'null' }};
    const META   = @json($statusMeta);
    const STATUS_URL = @json(route($cfg['routes']['status'], ['id' => 0]));
    const table  = document.getElementById('bsTable');
    if (!table) return;

    const rows   = [...table.querySelectorAll('tbody tr[data-id]')];
    const search = document.getElementById('stuSearch');
    const filter = document.getElementById('vetFilter');
    const total  = rows.length;

    async function post(url, body, method = 'POST') {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed');
        return data;
    }

    // ── filtering ──
    function apply() {
        const q = (search?.value || '').trim().toLowerCase();
        const f = filter?.value || '';
        let shown = 0;
        rows.forEach(tr => {
            const ok = (!q || tr.dataset.search.includes(q))
                && (!f || (f === 'vetted' && tr.dataset.vetted === '1')
                       || (f === 'unvetted' && tr.dataset.vetted === '0')
                       || (f === 'blank' && tr.dataset.blank === '1'));
            tr.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        document.getElementById('visibleCount').textContent = shown;
        document.getElementById('bsNoMatch').style.display = shown ? 'none' : 'table-row';
        const all = document.getElementById('checkAll');
        if (all) all.checked = false;
        updateBulkLabel();
    }
    search?.addEventListener('input', apply);
    filter?.addEventListener('change', apply);

    // ── selection ──
    const selected = () => rows.filter(r => r.style.display !== 'none' && r.querySelector('.row-select')?.checked);
    function updateBulkLabel() {
        const lbl = document.getElementById('vetAllLabel');
        if (!lbl) return;
        const n = selected().length;
        lbl.textContent = n ? `Vet ${n} selected` : 'Vet all shown';
    }
    document.getElementById('checkAll')?.addEventListener('change', e => {
        rows.forEach(r => { if (r.style.display !== 'none') { const c = r.querySelector('.row-select'); if (c) c.checked = e.target.checked; } });
        updateBulkLabel();
    });
    table.addEventListener('change', e => { if (e.target.classList.contains('row-select')) updateBulkLabel(); });

    // ── progress / status UI ──
    function paintRow(tr, vetted) {
        tr.dataset.vetted = vetted ? '1' : '0';
        tr.classList.toggle('is-vetted', vetted);
        const t = tr.querySelector('.vet-toggle');
        if (t) t.checked = vetted;
    }
    function paintProgress(data) {
        const vetted = data.vetted ?? rows.filter(r => r.dataset.vetted === '1').length;
        const students = data.students ?? total;
        const pct = students ? Math.round(vetted / students * 100) : 0;
        document.getElementById('statVetted').textContent = vetted;
        document.getElementById('statRemaining').textContent = students - vetted;
        document.getElementById('progressText').textContent = `${vetted}/${students} · ${pct}%`;
        const fill = document.getElementById('progressFill');
        fill.style.width = pct + '%';
        fill.style.background = pct >= 100 ? 'var(--cb-green)' : 'var(--cb-amber)';
        document.getElementById('progressBar').setAttribute('aria-valuenow', pct);
        const btn = document.getElementById('completeBtn');
        if (btn) btn.disabled = pct < 100;
        if (data.status) paintStatus(data.status);
    }
    function paintStatus(status) {
        const m = META[status] || META.pending;
        const pill = document.getElementById('statusPill');
        pill.className = `status-pill st-${status}`;
        pill.innerHTML = `<i class="${m[1]}"></i>${m[0]}`;
        document.getElementById('heroStatus').innerHTML = `<i class="${m[1]}"></i>${m[0]}`;
    }

    // ── single toggle ──
    table.addEventListener('change', async e => {
        const t = e.target;
        if (!t.classList.contains('vet-toggle')) return;
        const tr = t.closest('tr');
        const vetted = t.checked;
        paintRow(tr, vetted);
        t.disabled = true;
        try {
            const data = await post(@json(route($cfg['routes']['toggle'])), { broadsheet_id: t.dataset.id, vettedstatus: vetted ? 1 : 0 });
            paintProgress(data);
            if (data.status === 'completed' && data.percent === 100) vetToast('Every student is vetted — broadsheet marked completed.', 'success');
        } catch (err) {
            paintRow(tr, !vetted);
            vetToast(err.message, 'danger');
        } finally {
            t.disabled = false;
        }
    });

    // ── bulk ──
    window.bulkVet = async function (value) {
        const picked = selected();
        const target = picked.length ? picked : rows.filter(r => r.style.display !== 'none');
        if (!target.length) return vetToast('No students to update.', 'warning');
        const verb = value ? 'Vet' : 'Un-vet';
        if (!confirm(`${verb} ${target.length} student(s)?`)) return;
        try {
            const data = await post(@json(route($cfg['routes']['bulk'])), {
                ...ctx, vettedstatus: value, broadsheet_ids: target.map(r => Number(r.dataset.id)),
            });
            const done = new Set((data.ids || []).map(String));
            rows.forEach(r => { if (done.has(r.dataset.id)) paintRow(r, !!value); });
            rows.forEach(r => { const c = r.querySelector('.row-select'); if (c) c.checked = false; });
            const all = document.getElementById('checkAll'); if (all) all.checked = false;
            paintProgress(data);
            updateBulkLabel();
            vetToast(`${data.updated} student(s) ${value ? 'vetted' : 'un-vetted'}.`, 'success');
        } catch (err) {
            vetToast(err.message, 'danger');
        }
    };

    // ── assignment status ──
    window.setStatus = async function (status) {
        if (!svid) return;
        if (status === 'rejected' && !confirm('Send this broadsheet back to the subject teacher for corrections?')) return;
        try {
            const data = await post(STATUS_URL.replace(/\/0\/status$/, `/${svid}/status`), { status }, 'PUT');
            paintStatus(data.status);
            vetToast(data.message, 'success');
        } catch (err) {
            vetToast(err.message, 'danger');
        }
    };

    // ── photo preview ──
    table.addEventListener('click', e => {
        const img = e.target.closest('.stu-avatar');
        if (!img || typeof bootstrap === 'undefined') return;
        document.getElementById('photoImg').src = img.dataset.full;
        document.getElementById('photoName').textContent = img.dataset.name;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('photoModal')).show();
    });

    apply();
})();
</script>
@endsection
