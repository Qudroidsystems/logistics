{{-- resources/views/dashboards/partials/best-students-explorer.blade.php
     Best Students Explorer — admin picks classes/arms + criteria.
     Shows best overall, per class (all selected arms) and per arm, plus the
     best in every subject at class and arm level. Same figures as the
     broadsheet. Uses the dashboard's existing styles (.section, .dt, .rank, .sp). --}}
@php
    $bx      = $best_explorer;
    $opts    = $bx['opts'];
    $report  = $bx['report'];
    $selG    = $bx['groups'];
    $selI    = $bx['ids'];
    $bxFmt   = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $printQs = array_merge(request()->query(), [
        'term_id'    => $selectedTerm?->id,
        'session_id' => $selectedSession?->id,
        'bx'         => 1,
    ]);
@endphp

<style>
.bx-classes { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:8px; }
.bx-group { border:1px solid var(--c-border); border-radius:10px; padding:8px 10px; background:#fafbfe; }
.bx-group-all { font-weight:600; font-size:12.5px; color:var(--c-text); display:flex; align-items:center; gap:6px; cursor:pointer; }
.bx-arms { display:flex; flex-wrap:wrap; gap:2px 12px; margin-top:5px; padding-top:5px; border-top:1px dashed var(--c-border); }
.bx-arms label { font-size:12px; color:var(--c-sub); display:flex; align-items:center; gap:4px; cursor:pointer; }
.bx-arms input:disabled + span { color:#cbd5e1; }
.bx-classes input[type=checkbox], .bx-opts input[type=checkbox] { accent-color:var(--c-indigo); }
.bx-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--c-muted); display:block; margin-bottom:4px; }
.bx-input { width:100%; padding:7px 10px; border:1px solid var(--c-border); border-radius:var(--r-sm); font-size:13px; font-family:'Outfit',sans-serif; color:var(--c-text); background:#fff; }
.bx-input:focus, .bx-btn:focus-visible, .bx-tab:focus-visible { outline:2px solid var(--c-indigo); outline-offset:2px; }
.bx-btn { padding:8px 16px; border-radius:var(--r-sm); font-size:13px; font-weight:600; border:1px solid transparent; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.bx-btn-primary { background:var(--c-indigo); color:#fff; }
.bx-btn-primary:hover { background:#3b4de8; color:#fff; }
.bx-btn-ghost { background:#fff; color:var(--c-text); border-color:var(--c-border); }
.bx-btn-ghost:hover { background:#f8fafc; color:var(--c-text); }
.bx-pills { display:flex; flex-wrap:wrap; gap:6px; }
.bx-pill { background:#f1f5f9; color:var(--c-sub); border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
.bx-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px; }
.bx-sub { font-family:'Syne',sans-serif; font-weight:700; font-size:13.5px; color:var(--c-text); margin:0 0 8px; }
.bx-arm { border:1px solid var(--c-border); border-radius:12px; margin-top:12px; }
.bx-arm > summary { padding:10px 14px; cursor:pointer; font-weight:600; color:var(--c-text); display:flex; justify-content:space-between; gap:10px; list-style:none; }
.bx-arm > summary::-webkit-details-marker { display:none; }
.bx-arm > summary::after { content:'▾'; color:var(--c-muted); }
.bx-arm[open] > summary::after { content:'▴'; }
.bx-arm-body { padding:4px 14px 14px; }
</style>

<div class="row g-3 mb-4" id="best-explorer">
<div class="col-12">
<div class="section" style="animation-delay:.28s;">
    <div class="section-hd">
        <div>
            <div class="section-title">🔎 Best Students Explorer</div>
            <div class="section-sub">Pick classes or arms and the criteria. Shows the best students overall, in each class and arm, and in every subject — using the same figures as the broadsheet.</div>
        </div>
        @if($report)
            <a class="bx-btn bx-btn-ghost" target="_blank" rel="noopener"
               href="{{ route('dashboard.best-students.print', $printQs) }}">
                <i class="bi bi-printer"></i> Print report
            </a>
        @endif
    </div>

    <div class="section-bd">
        {{-- ── Criteria ── --}}
        <form method="GET" action="{{ url()->current() }}#best-explorer" id="bxForm">
            <input type="hidden" name="bx" value="1">
            <input type="hidden" name="term_id" value="{{ $selectedTerm?->id }}">
            <input type="hidden" name="session_id" value="{{ $selectedSession?->id }}">

            <span class="bx-label">Classes and arms</span>
            <div class="bx-classes mb-3">
                @foreach($bx['classes'] as $groupName => $arms)
                    @php $gid = 'bxg_' . md5($groupName); $groupOn = in_array($groupName, $selG, true); @endphp
                    <div class="bx-group">
                        <label class="bx-group-all">
                            <input type="checkbox" name="bx_groups[]" value="{{ $groupName }}" class="bx-group-cb" data-group="{{ $gid }}" @checked($groupOn)>
                            {{ $groupName }}
                            <span style="font-weight:400;font-size:11px;color:var(--c-muted);">all {{ $arms->count() }}</span>
                        </label>
                        <div class="bx-arms">
                            @foreach($arms as $c)
                                <label>
                                    <input type="checkbox" name="bx_ids[]" value="{{ $c->id }}" data-arm-of="{{ $gid }}"
                                           @checked(in_array((int) $c->id, $selI, true)) @disabled($groupOn)>
                                    <span>{{ $c->arm ?: 'No arm' }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-2 align-items-end bx-opts">
                <div class="col-lg-3 col-md-6">
                    <label class="bx-label" for="bxBasis">Score basis</label>
                    <select id="bxBasis" name="bx_basis" class="bx-input">
                        @foreach($bx['bases'] as $k => $lbl)<option value="{{ $k }}" @selected($opts['basis'] === $k)>{{ $lbl }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="bx-label" for="bxMeasure">Rank students by</label>
                    <select id="bxMeasure" name="bx_measure" class="bx-input">
                        @foreach($bx['measures'] as $k => $lbl)<option value="{{ $k }}" @selected($opts['measure'] === $k)>{{ $lbl }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-4">
                    <label class="bx-label" for="bxTop">Top</label>
                    <input id="bxTop" type="number" min="1" max="50" name="bx_top" value="{{ $opts['top_n'] }}" class="bx-input">
                </div>
                <div class="col-lg-1 col-4">
                    <label class="bx-label" for="bxSubTop">Per subject</label>
                    <input id="bxSubTop" type="number" min="1" max="10" name="bx_subject_top" value="{{ $opts['subject_top_n'] }}" class="bx-input">
                </div>
                <div class="col-lg-1 col-4">
                    <label class="bx-label" for="bxMin">Min. subj.</label>
                    <input id="bxMin" type="number" min="0" max="40" name="bx_min" value="{{ $opts['min_subjects'] }}" class="bx-input">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label style="font-size:12px;color:var(--c-sub);display:flex;gap:6px;align-items:center;cursor:pointer;">
                        <input type="checkbox" name="bx_fail" value="1" @checked($opts['exclude_failed'])>
                        Leave out anyone who failed a subject (below 40)
                    </label>
                </div>
                <div class="col-lg-2 col-md-6 d-flex gap-2">
                    <button type="submit" class="bx-btn bx-btn-primary"><i class="bi bi-search"></i> Show</button>
                    @if($report)
                        <a href="{{ url()->current() }}?term_id={{ $selectedTerm?->id }}&session_id={{ $selectedSession?->id }}#best-explorer" class="bx-btn bx-btn-ghost">Clear</a>
                    @endif
                </div>
            </div>
        </form>

        @if($bx['error'])
            <div class="alert alert-warning mt-3 mb-0" style="font-size:12.5px;">{{ $bx['error'] }}</div>
        @endif

        {{-- ── Results ── --}}
        @if($report)
            <hr style="border-color:var(--c-border);margin:20px 0;">

            <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                <div style="font-size:12px;color:var(--c-sub);">
                    <strong style="color:var(--c-text);">{{ $selectedTerm?->term }} · {{ $selectedSession?->session }}</strong> —
                    basis: {{ $report['basis_short'] }} · ranked by {{ strtolower($report['measure_label']) }}
                    @if($opts['min_subjects'] > 0) · at least {{ $opts['min_subjects'] }} subjects @endif
                    @if($opts['exclude_failed']) · no failed subject @endif
                </div>
                <div class="bx-pills">
                    @foreach($report['selection'] as $lbl)<span class="bx-pill">{{ $lbl }}</span>@endforeach
                </div>
            </div>

            <div class="bx-kpis mb-3">
                <div class="kpi"><div class="kpi-l">Students</div><div class="kpi-v">{{ number_format($report['students']) }}</div></div>
                <div class="kpi"><div class="kpi-l">Ranked</div><div class="kpi-v">{{ number_format($report['ranked']) }}</div></div>
                <div class="kpi"><div class="kpi-l">Subjects</div><div class="kpi-v">{{ $report['subject_count'] }}</div></div>
                <div class="kpi"><div class="kpi-l">{{ $opts['measure'] === 'sum' ? 'Mean total' : 'Mean average' }}</div><div class="kpi-v">{{ $bxFmt($report['mean'] ?? 0) }}</div></div>
            </div>

            @if(empty($report['classes']))
                <div class="text-center py-3" style="color:var(--c-muted);font-size:12.5px;">No student results found for the selected classes this term.</div>
            @else
                <h3 class="bx-sub">Best across the whole selection — top {{ $opts['top_n'] }}</h3>
                @include('dashboards.partials.bx-student-table', ['entries' => $report['overall'], 'report' => $report, 'showClass' => true])

                {{-- Class tabs --}}
                <div class="d-flex gap-2 flex-wrap mt-4 mb-3" role="tablist" aria-label="Classes">
                    @foreach($report['classes'] as $className => $c)
                        <button type="button" class="pill-btn bx-tab {{ $loop->first ? 'on' : '' }}" role="tab"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-pane="bxp_{{ md5($className) }}">
                            {{ $className }}
                        </button>
                    @endforeach
                </div>

                @foreach($report['classes'] as $className => $c)
                    <div class="bx-pane" id="bxp_{{ md5($className) }}" role="tabpanel" @if(!$loop->first) hidden @endif>
                        <div style="font-size:12px;color:var(--c-muted);margin-bottom:10px;">
                            {{ $className }} · {{ count($c['arms']) }} arm{{ count($c['arms']) > 1 ? 's' : '' }} · {{ $c['students'] }} students · {{ $c['ranked'] }} ranked ·
                            {{ $opts['measure'] === 'sum' ? 'mean total' : 'mean average' }} {{ $bxFmt($c['mean']) }}
                        </div>

                        <div class="row g-3">
                            <div class="col-xl-5">
                                <h3 class="bx-sub">{{ $className }} — best overall (all selected arms)</h3>
                                @include('dashboards.partials.bx-student-table', ['entries' => $c['top'], 'report' => $report, 'showClass' => count($c['arms']) > 1])
                            </div>
                            <div class="col-xl-7">
                                <h3 class="bx-sub">{{ $className }} — best in each subject (class-wide)</h3>
                                @include('dashboards.partials.bx-subject-table', ['subjects' => $c['subjects'], 'showArm' => count($c['arms']) > 1])
                            </div>
                        </div>

                        @foreach($c['arms'] as $armLabel => $a)
                            <details class="bx-arm" @if(count($c['arms']) === 1) open @endif>
                                <summary>
                                    <span>{{ $armLabel }}</span>
                                    <span style="font-weight:400;font-size:11.5px;color:var(--c-muted);">{{ $a['students'] }} students · {{ $a['ranked'] }} ranked</span>
                                </summary>
                                <div class="bx-arm-body">
                                    <div class="row g-3">
                                        <div class="col-xl-5">
                                            <h3 class="bx-sub">{{ $armLabel }} — best overall</h3>
                                            @include('dashboards.partials.bx-student-table', ['entries' => $a['top'], 'report' => $report, 'showClass' => false])
                                        </div>
                                        <div class="col-xl-7">
                                            <h3 class="bx-sub">{{ $armLabel }} — best in each subject</h3>
                                            @include('dashboards.partials.bx-subject-table', ['subjects' => $a['subjects'], 'showArm' => false])
                                        </div>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endforeach
            @endif
        @endif
    </div>
</div>
</div>
</div>

<script>
(function () {
    // "All arms" covers the class, so its single-arm boxes are disabled (and not submitted).
    document.querySelectorAll('.bx-group-cb').forEach(function (g) {
        g.addEventListener('change', function () {
            document.querySelectorAll('[data-arm-of="' + g.dataset.group + '"]').forEach(function (a) {
                a.disabled = g.checked;
                if (g.checked) a.checked = false;
            });
        });
    });
    var f = document.getElementById('bxForm');
    if (f) f.addEventListener('submit', function (e) {
        if (!f.querySelector('input[name="bx_groups[]"]:checked, input[name="bx_ids[]"]:checked')) {
            e.preventDefault();
            alert('Select at least one class or arm.');
        }
    });
    document.querySelectorAll('.bx-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.bx-tab').forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('on', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            document.querySelectorAll('.bx-pane').forEach(function (p) { p.hidden = p.id !== btn.dataset.pane; });
        });
    });
})();
</script>
