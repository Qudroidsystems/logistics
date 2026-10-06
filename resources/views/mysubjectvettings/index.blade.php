@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

@include('mysubjectvettings.partials.styles')

@php
    $statusMeta = [
        'pending'   => ['Pending',       'ri-time-line'],
        'completed' => ['Completed',     'ri-checkbox-circle-line'],
        'rejected'  => ['Sent back',     'ri-arrow-go-back-line'],
    ];
    $termClass = fn ($id) => in_array((int) $id, [1, 2, 3]) ? 'term-' . (int) $id : '';
    $barColor  = fn ($p) => $p >= 100 ? 'var(--cb-green)' : ($p >= 50 ? 'var(--cb-amber)' : ($p > 0 ? '#fb923c' : '#cbd5e1'));
    $defaultSession = isset($sessionOptions[$currentSessionId]) ? $currentSessionId : '';
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="cb-hero">
        <h1><i class="ri-shield-check-line me-2"></i>{{ $cfg['labels']['hero'] }}</h1>
        <p>Broadsheets assigned to you for checking. Open one, confirm each student's scores, and sign it off.</p>
        <div class="meta-pills">
            <span class="cb-meta-pill"><i class="ri-user-line"></i>{{ Auth::user()->name }}</span>
            <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ now()->format('F j, Y') }}</span>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-lg col-md-4 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-navy),var(--cb-teal));"></div>
                <div class="stat-ico"><i class="ri-stack-line"></i></div>
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Assignments</div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-amber),#fcd34d);"></div>
                <div class="stat-ico"><i class="ri-time-line"></i></div>
                <div class="stat-value text-warning" id="statPending">{{ $stats['pending'] }}</div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-green),#86efac);"></div>
                <div class="stat-ico"><i class="ri-checkbox-circle-line"></i></div>
                <div class="stat-value text-success" id="statCompleted">{{ $stats['completed'] }}</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-lg col-md-6 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-rose),#fda4af);"></div>
                <div class="stat-ico"><i class="ri-arrow-go-back-line"></i></div>
                <div class="stat-value text-danger" id="statRejected">{{ $stats['rejected'] }}</div>
                <div class="stat-label">Sent back to teacher</div>
            </div>
        </div>
        <div class="col-lg col-md-6 col-12">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-violet),#a78bfa);"></div>
                <div class="stat-ico"><i class="ri-shield-check-line"></i></div>
                <div class="stat-value" style="color:var(--cb-violet)">{{ $stats['percent'] }}%</div>
                <div class="stat-label">{{ number_format($stats['vetted']) }} of {{ number_format($stats['rows']) }} student scores vetted</div>
            </div>
        </div>
    </div>

    <div class="cb-card mb-4">
        <div class="cb-card-header">
            <h5><i class="ri-list-check-2" style="color:var(--cb-teal)"></i>Vetting Assignments
                <span class="cb-count" id="visibleCount">{{ $assignments->count() }}</span>
            </h5>
            <small class="text-muted">Status updates automatically when every student is vetted.</small>
        </div>

        @if($assignments->isNotEmpty())
            <div class="cb-toolbar">
                <div class="cb-search">
                    <i class="ri-search-line"></i>
                    <input type="search" id="vetSearch" placeholder="Search subject, teacher or class…" aria-label="Search assignments">
                </div>
                @if(count($sessionOptions) > 1)
                    <select class="cb-select" id="sessionFilter" aria-label="Filter by session">
                        <option value="">All sessions</option>
                        @foreach($sessionOptions as $sid => $sname)
                            <option value="{{ $sid }}" @selected((string) $sid === (string) $defaultSession)>{{ $sname }}</option>
                        @endforeach
                    </select>
                @endif
                @if(count($termOptions) > 1)
                    <div class="term-chips" id="termChips" role="group" aria-label="Filter by term">
                        <button type="button" class="term-chip active" data-term="">All terms</button>
                        @foreach($termOptions as $tid => $tname)
                            <button type="button" class="term-chip" data-term="{{ $tid }}">{{ $tname }}</button>
                        @endforeach
                    </div>
                @endif
                <select class="cb-select" id="statusFilter" aria-label="Filter by status">
                    <option value="">Any status</option>
                    @foreach($statusMeta as $key => $meta)
                        <option value="{{ $key }}">{{ $meta[0] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="table-responsive">
                <table class="cb-table stack" id="vetTable">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Term / Session</th>
                            <th>Vetted</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $a)
                            <tr data-id="{{ $a->svid }}"
                                data-term="{{ $a->termid }}"
                                data-session="{{ $a->sessionid }}"
                                data-status="{{ $a->status }}"
                                data-search="{{ strtolower($a->subjectname . ' ' . $a->subjectcode . ' ' . $a->teachername . ' ' . $a->sclass . ' ' . $a->schoolarm) }}">
                                <td data-label="Subject">
                                    <span class="subject-name">{{ $a->subjectname ?? '—' }}</span>
                                    @if($a->subjectcode)<span class="subject-code">{{ $a->subjectcode }}</span>@endif
                                </td>
                                <td data-label="Teacher">{{ $a->teachername ?? '—' }}</td>
                                <td data-label="Class">
                                    @if($a->schoolclassid)
                                        <span class="class-badge"><i class="ri-building-line"></i>{{ $a->sclass }}</span>
                                        @if($a->schoolarm)<span class="arm-badge">{{ $a->schoolarm }}</span>@endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Term / Session">
                                    <span class="term-badge {{ $termClass($a->termid) }}">{{ $a->termname ?? '—' }}</span>
                                    <span class="session-badge ms-1">{{ $a->sessionname ?? '—' }}</span>
                                </td>
                                <td data-label="Vetted" class="progress-cell">
                                    @if($a->students > 0)
                                        <div>
                                            <div class="progress-track" role="progressbar" aria-valuenow="{{ $a->percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Vetted">
                                                <div class="progress-fill" style="width:0%;background:{{ $barColor($a->percent) }}" data-width="{{ $a->percent }}"></div>
                                            </div>
                                            <div class="progress-meta"><span>{{ $a->vetted }}/{{ $a->students }} · {{ $a->percent }}%</span>
                                                @if($a->entered < $a->students)<span title="Students with no score yet" class="text-warning"><i class="ri-error-warning-line"></i> {{ $a->students - $a->entered }} blank</span>@endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">No scores entered yet</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="status-pill st-{{ $a->status }}" data-status-pill>
                                        <i class="{{ $statusMeta[$a->status][1] }}"></i>{{ $statusMeta[$a->status][0] }}
                                    </span>
                                </td>
                                <td data-label="Actions" class="text-end">
                                    <div class="d-inline-flex gap-2 align-items-center">
                                        @if($a->subjectclassid && $a->schoolclassid)
                                            <a class="action-btn btn-open"
                                               href="{{ route($cfg['routes']['broadsheet'], [$a->schoolclassid, $a->subjectclassid, $a->staffid ?? 0, $a->termid, $a->sessionid]) }}">
                                                <i class="ri-eye-line"></i>{{ $a->status === 'completed' ? 'Review' : 'Vet now' }}
                                            </a>
                                        @else
                                            <span class="action-btn btn-disabled" title="The subject-class for this assignment no longer exists"><i class="ri-link-unlink"></i>Unavailable</span>
                                        @endif
                                        @if($a->subjectclassid)
                                            <div class="dropdown">
                                                <button class="action-btn btn-more" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Change status">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><h6 class="dropdown-header">Set status</h6></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setStatus({{ $a->svid }}, 'completed'); return false;"><i class="ri-checkbox-circle-line text-success me-2"></i>Mark completed</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setStatus({{ $a->svid }}, 'pending'); return false;"><i class="ri-time-line text-warning me-2"></i>Reopen (pending)</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setStatus({{ $a->svid }}, 'rejected'); return false;"><i class="ri-arrow-go-back-line text-danger me-2"></i>Send back to teacher</a></li>
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="no-match" id="vetNoMatch">
                            <td colspan="7">
                                <div class="empty-state" style="padding:32px">
                                    <i class="ri-search-eye-line" style="font-size:40px"></i>
                                    <h6>No assignments match these filters</h6>
                                    <p><a href="#" onclick="resetFilters(); return false;">Clear filters</a></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="ri-shield-check-line"></i>
                <h6>No vetting assignments yet</h6>
                <p>When an administrator assigns you a subject to vet, it will appear here.</p>
            </div>
        @endif
    </div>

</div>
</div>
</div>

@include('mysubjectvettings.partials.toast')

<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STATUS_META = @json($statusMeta);
    const STATUS_URL  = @json(route($cfg['routes']['status'], ['id' => 0]));

    requestAnimationFrame(() => {
        document.querySelectorAll('.progress-fill[data-width]').forEach(el => { el.style.width = el.dataset.width + '%'; });
    });

    const table = document.getElementById('vetTable');
    if (!table) return;

    const rows      = [...table.querySelectorAll('tbody tr[data-id]')];
    const search    = document.getElementById('vetSearch');
    const sessSel   = document.getElementById('sessionFilter');
    const statSel   = document.getElementById('statusFilter');
    const chips     = document.getElementById('termChips');
    let term = '';

    function apply() {
        const q  = (search?.value || '').trim().toLowerCase();
        const ss = sessSel?.value || '';
        const st = statSel?.value || '';
        let shown = 0;
        rows.forEach(tr => {
            const ok = (!q || tr.dataset.search.includes(q))
                && (!ss || tr.dataset.session === ss)
                && (!term || tr.dataset.term === term)
                && (!st || tr.dataset.status === st);
            tr.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        document.getElementById('visibleCount').textContent = shown;
        document.getElementById('vetNoMatch').style.display = shown ? 'none' : 'table-row';
    }

    search?.addEventListener('input', apply);
    sessSel?.addEventListener('change', apply);
    statSel?.addEventListener('change', apply);
    chips?.addEventListener('click', e => {
        const chip = e.target.closest('.term-chip');
        if (!chip) return;
        term = chip.dataset.term;
        chips.querySelectorAll('.term-chip').forEach(c => c.classList.toggle('active', c === chip));
        apply();
    });
    window.resetFilters = function () {
        if (search) search.value = '';
        if (sessSel) sessSel.value = '';
        if (statSel) statSel.value = '';
        term = '';
        chips?.querySelectorAll('.term-chip').forEach(c => c.classList.toggle('active', c.dataset.term === ''));
        apply();
    };
    apply();

    function recount() {
        const count = s => rows.filter(r => r.dataset.status === s).length;
        document.getElementById('statPending').textContent   = count('pending');
        document.getElementById('statCompleted').textContent = count('completed');
        document.getElementById('statRejected').textContent  = count('rejected');
    }

    window.setStatus = async function (id, status) {
        if (status === 'rejected' && !confirm('Send this broadsheet back to the subject teacher for corrections?')) return;
        try {
            const res = await fetch(STATUS_URL.replace(/\/0\/status$/, `/${id}/status`), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ status }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'Could not update the status.');

            const tr = rows.find(r => r.dataset.id === String(id));
            if (tr) {
                tr.dataset.status = data.status;
                const pill = tr.querySelector('[data-status-pill]');
                const meta = STATUS_META[data.status];
                pill.className = `status-pill st-${data.status}`;
                pill.innerHTML = `<i class="${meta[1]}"></i>${meta[0]}`;
            }
            recount();
            apply();
            vetToast(data.message, 'success');
        } catch (err) {
            vetToast(err.message, 'danger');
        }
    };
})();
</script>
@endsection
