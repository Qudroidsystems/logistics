@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
/* Same design language as My Classes (myclass/index.blade.php) */
:root {
    --cb-navy:   #0f2342;
    --cb-teal:   #0d9488;
    --cb-sky:    #0ea5e9;
    --cb-amber:  #f59e0b;
    --cb-rose:   #f43f5e;
    --cb-green:  #22c55e;
    --cb-violet: #7c3aed;
    --cb-muted:  #64748b;
    --cb-border: #e2e8f0;
    --cb-surface:#f8fafc;
    --cb-radius: 14px;
    --cb-shadow: 0 4px 16px rgba(15,35,66,.10);
}
body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; }

.cb-hero { background: linear-gradient(135deg, var(--cb-navy) 0%, #1e4a7e 55%, #0d9488 100%); border-radius: var(--cb-radius); padding: 32px 36px; margin-bottom: 28px; position: relative; overflow: hidden; }
.cb-hero::before { content: ''; position: absolute; top: -80px; right: -80px; width: 280px; height: 280px; background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%); border-radius: 50%; }
.cb-hero h1 { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; color: #fff; margin: 0 0 8px; }
.cb-hero p { font-size: 13px; color: rgba(255,255,255,.72); margin: 0; }
.cb-hero .meta-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
.cb-meta-pill { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 20px; padding: 4px 14px; font-size: 12px; font-weight: 600; color: #fff; display: inline-flex; align-items: center; gap: 5px; }

.cb-stat { background: #fff; border: 1px solid var(--cb-border); border-radius: var(--cb-radius); padding: 20px 22px; position: relative; overflow: hidden; transition: transform .15s, box-shadow .15s; height: 100%; }
.cb-stat:hover { transform: translateY(-2px); box-shadow: var(--cb-shadow); }
.cb-stat .stat-accent { position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.cb-stat .stat-value { font-size: 30px; font-weight: 700; color: var(--cb-navy); line-height: 1; margin-top: 8px; }
.cb-stat .stat-label { font-size: 12px; color: var(--cb-muted); margin-top: 5px; font-weight: 500; }
.cb-stat .stat-ico { font-size: 36px; opacity: .08; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); }

.cb-card { background: #fff; border: 1px solid var(--cb-border); border-radius: var(--cb-radius); box-shadow: var(--cb-shadow); overflow: hidden; animation: fadeInUp .4s ease; }
.cb-card-header { padding: 18px 24px; border-bottom: 1px solid var(--cb-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: linear-gradient(to right, #f8fafc, #f0fdf9); }
.cb-card-header h5 { font-size: 15px; font-weight: 700; color: var(--cb-navy); margin: 0; display: flex; align-items: center; gap: 8px; }
.cb-count { background: var(--cb-teal); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }

.cb-toolbar { padding: 14px 24px; border-bottom: 1px solid var(--cb-border); display: flex; gap: 10px; flex-wrap: wrap; align-items: center; background: #fff; }
.cb-search { position: relative; flex: 1 1 220px; max-width: 320px; }
.cb-search input { width: 100%; border: 1.5px solid var(--cb-border); border-radius: 10px; padding: 8px 12px 8px 34px; font-size: 13px; }
.cb-search input:focus { outline: none; border-color: var(--cb-teal); box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
.cb-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--cb-muted); }
.cb-select { border: 1.5px solid var(--cb-border); border-radius: 10px; padding: 7px 10px; font-size: 13px; background: #fff; }
.term-chips { display: flex; gap: 6px; flex-wrap: wrap; }
.term-chip { border: 1.5px solid var(--cb-border); background: #fff; color: #475569; border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; }
.term-chip:hover { border-color: var(--cb-teal); color: var(--cb-teal); }
.term-chip.active { background: var(--cb-teal); border-color: var(--cb-teal); color: #fff; }

.cb-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cb-table thead th { background: linear-gradient(135deg, var(--cb-navy), #1e4a7e); color: #fff; padding: 13px 16px; font-weight: 600; font-size: 12px; white-space: nowrap; text-align: left; letter-spacing: .3px; }
.cb-table tbody td { padding: 13px 16px; vertical-align: middle; border-bottom: 1px solid var(--cb-border); color: #334155; }
.cb-table tbody tr:hover td { background: #f0fdf9; }
.cb-table tbody tr:last-child td { border-bottom: none; }
.history-table tbody td { background: #fcfdfe; }

.subject-name { font-weight: 700; color: var(--cb-navy); }
.subject-code { font-family: ui-monospace, monospace; font-size: 11px; color: var(--cb-muted); background: #f1f5f9; border-radius: 6px; padding: 1px 6px; margin-left: 6px; }
.class-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 10px; font-size: 12px; font-weight: 700; background: linear-gradient(135deg, #f8fafc, #f1f5f9); color: var(--cb-navy); border: 1px solid var(--cb-border); }
.class-badge i { color: var(--cb-teal); }
.arm-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 700; background: var(--cb-teal); color: #fff; text-transform: uppercase; margin-left: 4px; }
.term-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569; }
.term-1 { background: #dbeafe; color: #1e40af; }
.term-2 { background: #dcfce7; color: #166534; }
.term-3 { background: #fef3c7; color: #92400e; }
.session-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; background: #e0e7ff; color: #3730a3; }

.progress-cell { min-width: 170px; }
.progress-track { height: 7px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; transition: width .6s cubic-bezier(.25,.8,.25,1); }
.progress-meta { display: flex; justify-content: space-between; font-size: 11px; color: var(--cb-muted); margin-top: 4px; }
.status-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.st-complete    { background: #dcfce7; color: #15803d; }
.st-in_progress { background: #fef3c7; color: #92400e; }
.st-not_started { background: #fee2e2; color: #b91c1c; }
.st-no_students { background: #f1f5f9; color: #475569; }
.st-unlinked    { background: #ede9fe; color: #6d28d9; }

.action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 600; text-decoration: none; transition: all .25s ease; border: 1px solid transparent; white-space: nowrap; }
.btn-scoresheet { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; border-color: #86efac; }
.btn-scoresheet:hover { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border-color: #22c55e; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(34,197,94,.25); }
.btn-disabled { background: #f1f5f9; color: #94a3b8; border-color: var(--cb-border); cursor: not-allowed; }

.empty-state { text-align: center; padding: 56px 24px; }
.empty-state i { font-size: 56px; color: #cbd5e1; display: block; margin-bottom: 14px; }
.empty-state h6 { font-size: 17px; color: var(--cb-navy); margin-bottom: 6px; }
.empty-state p { color: var(--cb-muted); font-size: 13px; margin: 0; }
.no-match { display: none; }

.collapse-toggle { background: none; border: none; color: var(--cb-teal); font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.collapse-toggle i { transition: transform .2s; }
.collapse-toggle[aria-expanded="false"] i { transform: rotate(-90deg); }

@keyframes fadeInUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
    .cb-hero { padding: 24px 20px; }
    .cb-hero h1 { font-size: 22px; }
    .cb-toolbar, .cb-card-header { padding: 12px 16px; }
    .cb-table thead { display: none; }
    .cb-table tbody tr { display: block; border-bottom: 1px solid var(--cb-border); padding: 6px 0; }
    .cb-table tbody td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border: none; padding: 7px 16px; text-align: right; }
    .cb-table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--cb-navy); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; text-align: left; }
    .progress-cell { min-width: 0; }
    .progress-cell > div { flex: 1; max-width: 60%; }
}
@media (prefers-reduced-motion: reduce) { .cb-card, .progress-fill, .cb-stat { animation: none !important; transition: none !important; } }
</style>

@php
    $statusLabels = [
        'complete'    => ['Complete',          'ri-checkbox-circle-line'],
        'in_progress' => ['In progress',       'ri-loader-4-line'],
        'not_started' => ['Not started',       'ri-time-line'],
        'no_students' => ['No students yet',   'ri-user-unfollow-line'],
        'unlinked'    => ['No class linked',   'ri-link-unlink'],
    ];
    $termClass = fn ($id) => in_array((int) $id, [1, 2, 3]) ? 'term-' . (int) $id : '';
    $barColor  = fn ($p) => $p >= 100 ? 'var(--cb-green)' : ($p >= 50 ? 'var(--cb-amber)' : ($p > 0 ? '#fb923c' : '#cbd5e1'));
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="cb-hero">
        <h1><i class="ri-book-open-line me-2"></i>My Subjects</h1>
        <p>The subjects and classes you teach, with score-entry progress and a shortcut into each score sheet.</p>
        <div class="meta-pills">
            <span class="cb-meta-pill"><i class="ri-user-line"></i>{{ Auth::user()->name }}</span>
            @if($currentSession)
                <span class="cb-meta-pill"><i class="ri-calendar-event-line"></i>{{ $currentSession->session }} session</span>
            @endif
            <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ now()->format('F j, Y') }}</span>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-lg col-md-4 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-navy),var(--cb-teal));"></div>
                <div class="stat-ico"><i class="ri-book-2-line"></i></div>
                <div class="stat-value">{{ $stats['subjects'] }}</div>
                <div class="stat-label">Subjects this session</div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-sky),#38bdf8);"></div>
                <div class="stat-ico"><i class="ri-building-line"></i></div>
                <div class="stat-value text-info">{{ $stats['classes'] }}</div>
                <div class="stat-label">Classes taught</div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-violet),#a78bfa);"></div>
                <div class="stat-ico"><i class="ri-group-line"></i></div>
                <div class="stat-value" style="color:var(--cb-violet)">{{ number_format($stats['students']) }}</div>
                <div class="stat-label">Student enrolments</div>
            </div>
        </div>
        <div class="col-lg col-md-6 col-6">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-green),#86efac);"></div>
                <div class="stat-ico"><i class="ri-edit-2-line"></i></div>
                <div class="stat-value text-success">{{ $stats['progress'] }}%</div>
                <div class="stat-label">Average scores entered</div>
            </div>
        </div>
        <div class="col-lg col-md-6 col-12">
            <div class="cb-stat">
                <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-amber),#fcd34d);"></div>
                <div class="stat-ico"><i class="ri-history-line"></i></div>
                <div class="stat-value text-warning">{{ $stats['history'] }}</div>
                <div class="stat-label">Previous assignments</div>
            </div>
        </div>
    </div>

    {{-- Current subjects --}}
    <div class="cb-card mb-4">
        <div class="cb-card-header">
            <h5><i class="ri-book-open-line" style="color:var(--cb-teal)"></i>Current Subjects
                <span class="cb-count" id="currentCount">{{ $current->count() }}</span>
            </h5>
            <small class="text-muted">
                {{ $currentSession ? $currentSession->session . ' session' : 'No session is marked Current' }}
            </small>
        </div>

        @if($current->isNotEmpty())
            <div class="cb-toolbar">
                <div class="cb-search">
                    <i class="ri-search-line"></i>
                    <input type="search" id="currentSearch" placeholder="Search subject, code or class…" aria-label="Search current subjects">
                </div>
                @if(count($termOptions) > 1)
                    <div class="term-chips" id="termChips" role="group" aria-label="Filter by term">
                        <button type="button" class="term-chip active" data-term="">All terms</button>
                        @foreach($termOptions as $tid => $tname)
                            <button type="button" class="term-chip" data-term="{{ $tid }}">{{ $tname }}</button>
                        @endforeach
                    </div>
                @endif
                @if(count($classOptions) > 1)
                    <select class="cb-select" id="classFilter" aria-label="Filter by class">
                        <option value="">All classes</option>
                        @foreach($classOptions as $cid => $cname)
                            <option value="{{ $cid }}">{{ $cname }}</option>
                        @endforeach
                    </select>
                @endif
                <select class="cb-select" id="statusFilter" aria-label="Filter by score entry status">
                    <option value="">Any status</option>
                    @foreach($statusLabels as $key => $meta)
                        <option value="{{ $key }}">{{ $meta[0] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="table-responsive">
                <table class="cb-table" id="currentTable">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th class="text-center">Students</th>
                            <th>Scores entered</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($current as $row)
                            @php $stLabel = $statusLabels[$row->status][0]; $stIcon = $statusLabels[$row->status][1]; @endphp
                            <tr data-term="{{ $row->termid }}"
                                data-class="{{ $row->schoolclass_id }}"
                                data-status="{{ $row->status }}"
                                data-search="{{ strtolower($row->subject . ' ' . $row->subject_code . ' ' . $row->schoolclass . ' ' . $row->arm) }}">
                                <td data-label="Subject">
                                    <span class="subject-name">{{ $row->subject }}</span>
                                    @if($row->subject_code)<span class="subject-code">{{ $row->subject_code }}</span>@endif
                                </td>
                                <td data-label="Class">
                                    @if($row->schoolclass_id)
                                        <span class="class-badge"><i class="ri-building-line"></i>{{ $row->schoolclass }}</span>
                                        @if($row->arm)<span class="arm-badge">{{ $row->arm }}</span>@endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Term"><span class="term-badge {{ $termClass($row->termid) }}"><i class="ri-calendar-line"></i>{{ $row->term ?? '—' }}</span></td>
                                <td data-label="Students" class="text-center fw-semibold">{{ $row->students ?: '—' }}</td>
                                <td data-label="Scores entered" class="progress-cell">
                                    @if($row->students > 0)
                                        <div>
                                            <div class="progress-track" role="progressbar" aria-valuenow="{{ $row->progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Scores entered">
                                                <div class="progress-fill" style="width:0%;background:{{ $barColor($row->progress) }}" data-width="{{ $row->progress }}"></div>
                                            </div>
                                            <div class="progress-meta">
                                                <span>{{ $row->entered }}/{{ $row->students }} · {{ $row->progress }}%</span>
                                                @if($row->vetted)<span title="Rows vetted"><i class="ri-shield-check-line"></i> {{ $row->vetted }} vetted</span>@endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td data-label="Status"><span class="status-pill st-{{ $row->status }}"><i class="{{ $stIcon }}"></i>{{ $stLabel }}</span></td>
                                <td data-label="Action" class="text-end">
                                    @if($row->subjectclass_id && $row->schoolclass_id)
                                        <a class="action-btn btn-scoresheet"
                                           href="{{ route('subjectscoresheet', [
                                               'schoolclassid'  => $row->schoolclass_id,
                                               'subjectclassid' => $row->subjectclass_id,
                                               'staffid'        => Auth::id(),
                                               'termid'         => $row->termid,
                                               'sessionid'      => $row->sessionid,
                                           ]) }}">
                                            <i class="ri-file-list-3-line"></i>Score sheet
                                        </a>
                                    @else
                                        <span class="action-btn btn-disabled" title="Ask an administrator to link this subject to a class">
                                            <i class="ri-link-unlink"></i>Not linked
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="no-match" id="currentNoMatch">
                            <td colspan="7">
                                <div class="empty-state" style="padding:32px">
                                    <i class="ri-search-eye-line" style="font-size:40px"></i>
                                    <h6>No subjects match these filters</h6>
                                    <p><a href="#" onclick="resetCurrentFilters(); return false;">Clear filters</a></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="ri-book-mark-line"></i>
                <h6>No subjects assigned {{ $currentSession ? 'for ' . $currentSession->session : 'yet' }}</h6>
                <p>Subject assignments are made by an administrator. If you should be teaching a subject, ask them to add it under Subject Teacher and Subject Class.</p>
            </div>
        @endif
    </div>

    {{-- History --}}
    <div class="cb-card mb-4">
        <div class="cb-card-header">
            <h5><i class="ri-history-line" style="color:var(--cb-amber)"></i>Subject History
                <span class="cb-count" style="background:var(--cb-amber)" id="historyCount">{{ $history->count() }}</span>
            </h5>
            <div class="d-flex align-items-center gap-2">
                @if(count($historySessions) > 1)
                    <select class="cb-select" id="historySession" aria-label="Filter history by session">
                        <option value="">All sessions</option>
                        @foreach($historySessions as $sid => $sname)
                            <option value="{{ $sid }}">{{ $sname }}</option>
                        @endforeach
                    </select>
                @endif
                @if($history->isNotEmpty())
                    <button type="button" class="collapse-toggle" data-bs-toggle="collapse" data-bs-target="#historyBody" aria-expanded="true" aria-controls="historyBody">
                        <i class="ri-arrow-down-s-line"></i>Show / hide
                    </button>
                @endif
            </div>
        </div>

        @if($history->isNotEmpty())
            <div class="collapse show" id="historyBody">
                <div class="table-responsive">
                    <table class="cb-table history-table" id="historyTable">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Term</th>
                                <th>Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $row)
                                <tr data-session="{{ $row->sessionid }}">
                                    <td data-label="Subject">
                                        <span class="subject-name">{{ $row->subject }}</span>
                                        @if($row->subject_code)<span class="subject-code">{{ $row->subject_code }}</span>@endif
                                    </td>
                                    <td data-label="Class">
                                        @if($row->schoolclass_id)
                                            <span class="class-badge"><i class="ri-building-line"></i>{{ $row->schoolclass }}</span>
                                            @if($row->arm)<span class="arm-badge" style="background:#94a3b8">{{ $row->arm }}</span>@endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td data-label="Term"><span class="term-badge {{ $termClass($row->termid) }}">{{ $row->term ?? '—' }}</span></td>
                                    <td data-label="Session"><span class="session-badge"><i class="ri-calendar-line"></i>{{ $row->session ?? '—' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="empty-state" style="padding:36px">
                <i class="ri-archive-line" style="font-size:44px"></i>
                <h6>No previous assignments</h6>
                <p>Subjects from earlier sessions will appear here.</p>
            </div>
        @endif
    </div>

</div>
</div>
</div>

<script>
(function () {
    const table = document.getElementById('currentTable');

    // Animate progress bars in.
    requestAnimationFrame(() => {
        document.querySelectorAll('.progress-fill[data-width]').forEach(el => { el.style.width = el.dataset.width + '%'; });
    });

    if (table) {
        const rows     = [...table.querySelectorAll('tbody tr[data-term]')];
        const search   = document.getElementById('currentSearch');
        const classSel = document.getElementById('classFilter');
        const statSel  = document.getElementById('statusFilter');
        const chips    = document.getElementById('termChips');
        let term = '';

        function apply() {
            const q = (search?.value || '').trim().toLowerCase();
            const cls = classSel?.value || '';
            const st  = statSel?.value || '';
            let shown = 0;
            rows.forEach(tr => {
                const ok = (!q || tr.dataset.search.includes(q))
                    && (!term || tr.dataset.term === term)
                    && (!cls || tr.dataset.class === cls)
                    && (!st || tr.dataset.status === st);
                tr.style.display = ok ? '' : 'none';
                if (ok) shown++;
            });
            document.getElementById('currentCount').textContent = shown;
            document.getElementById('currentNoMatch').style.display = shown ? 'none' : 'table-row';
        }

        search?.addEventListener('input', apply);
        classSel?.addEventListener('change', apply);
        statSel?.addEventListener('change', apply);
        chips?.addEventListener('click', e => {
            const chip = e.target.closest('.term-chip');
            if (!chip) return;
            term = chip.dataset.term;
            chips.querySelectorAll('.term-chip').forEach(c => c.classList.toggle('active', c === chip));
            apply();
        });

        window.resetCurrentFilters = function () {
            if (search) search.value = '';
            if (classSel) classSel.value = '';
            if (statSel) statSel.value = '';
            term = '';
            chips?.querySelectorAll('.term-chip').forEach(c => c.classList.toggle('active', c.dataset.term === ''));
            apply();
        };
    }

    const histSel = document.getElementById('historySession');
    histSel?.addEventListener('change', () => {
        let shown = 0;
        document.querySelectorAll('#historyTable tbody tr').forEach(tr => {
            const ok = !histSel.value || tr.dataset.session === histSel.value;
            tr.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        document.getElementById('historyCount').textContent = shown;
    });
})();
</script>
@endsection
