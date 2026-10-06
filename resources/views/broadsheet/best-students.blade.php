{{-- resources/views/broadsheet/best-students.blade.php (CSS Kabba) --}}
@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

@php
    $val      = fn ($k, $d = null) => old($k, $input[$k] ?? $d);
    $selGroups = (array) $val('class_groups', []);
    $selIds    = array_map('intval', (array) $val('class_ids', []));
    $fmt      = fn ($v) => is_numeric($v) ? rtrim(rtrim(number_format((float) $v, 2), '0'), '.') : '—';
    $badge    = fn ($r) => $r <= 3 ? 'bs-r' . $r : 'bs-rx';
@endphp

<style>
:root {
    --bs-navy:#0f2342; --bs-teal:#0d9488; --bs-gold:#b7791f; --bs-gold-bg:#fdf6e3;
    --bs-muted:#64748b; --bs-border:#e2e8f0; --bs-surface:#f8fafc;
}
.bs-wrap { font-family:'DM Sans',sans-serif; }
.bs-hero { background:linear-gradient(135deg,var(--bs-navy) 0%,#1e4a7e 60%,var(--bs-teal) 100%); border-radius:14px; padding:26px 30px; margin-bottom:22px; color:#fff; }
.bs-hero h1 { font-family:'Playfair Display',serif; font-size:24px; margin:0 0 6px; color:#fff; }
.bs-hero p { font-size:13px; margin:0; color:rgba(255,255,255,.78); max-width:72ch; }

.bs-card { background:#fff; border:1px solid var(--bs-border); border-radius:14px; box-shadow:0 4px 16px rgba(15,35,66,.07); margin-bottom:20px; }
.bs-card > summary, .bs-card-head { padding:14px 20px; font-weight:700; color:var(--bs-navy); cursor:pointer; list-style:none; display:flex; align-items:center; gap:8px; }
.bs-card > summary::-webkit-details-marker { display:none; }
.bs-card-body { padding:4px 20px 20px; }
.bs-label { font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; display:block; }
.bs-hint { font-size:11px; color:var(--bs-muted); }

.bs-classes { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:10px; }
.bs-group { border:1px solid var(--bs-border); border-radius:10px; padding:10px 12px; background:var(--bs-surface); }
.bs-group-all { font-weight:700; color:var(--bs-navy); font-size:13px; }
.bs-arms { display:flex; flex-wrap:wrap; gap:4px 12px; margin-top:6px; padding-top:6px; border-top:1px dashed var(--bs-border); }
.bs-arms label { font-size:12px; color:#374151; }
.bs-arms input:disabled + span { color:#cbd5e1; }
.bs-classes input[type=checkbox] { accent-color:var(--bs-teal); }

.bs-btn { background:var(--bs-teal); color:#fff; border:none; border-radius:10px; padding:10px 20px; font-weight:700; }
.bs-btn:hover { filter:brightness(.95); }
.bs-btn:focus-visible, .bs-ghost:focus-visible { outline:2px solid var(--bs-navy); outline-offset:2px; }
.bs-ghost { background:#fff; color:var(--bs-navy); border:1px solid var(--bs-border); border-radius:10px; padding:8px 14px; font-weight:600; font-size:13px; }

/* Report */
.bs-report-head { display:flex; gap:16px; align-items:center; padding:18px 22px; border-bottom:1px solid var(--bs-border); }
.bs-report-head img { width:58px; height:58px; object-fit:contain; border-radius:50%; }
.bs-report-head h2 { font-family:'Playfair Display',serif; font-size:20px; color:var(--bs-navy); margin:0; }
.bs-report-head .bs-hint { font-size:12px; }
.bs-sel { display:flex; flex-wrap:wrap; gap:6px; padding:12px 22px; border-bottom:1px solid var(--bs-border); }
.bs-pill { background:var(--bs-surface); border:1px solid var(--bs-border); border-radius:20px; font-size:11.5px; padding:2px 10px; color:var(--bs-navy); font-weight:600; }

.bs-section-title { font-size:15px; font-weight:700; color:var(--bs-navy); margin:0 0 12px; }

/* The one memorable element: the champion line */
.bs-champ { display:flex; align-items:center; gap:18px; padding:18px 22px; background:var(--bs-gold-bg); border-bottom:1px solid #f3e1b5; }
.bs-champ-rank { font-family:'Playfair Display',serif; font-size:56px; line-height:1; color:var(--bs-gold); min-width:56px; text-align:center; }
.bs-champ-name { font-family:'Playfair Display',serif; font-size:22px; color:var(--bs-navy); line-height:1.15; }
.bs-champ-val { margin-left:auto; text-align:right; }
.bs-champ-val strong { font-size:28px; color:var(--bs-navy); display:block; line-height:1; }

.bs-table { width:100%; border-collapse:collapse; font-size:12.5px; }
.bs-table th { background:var(--bs-surface); color:var(--bs-navy); font-weight:700; padding:8px 12px; text-align:left; border-bottom:1px solid var(--bs-border); }
.bs-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.bs-table td.num { text-align:right; font-weight:700; color:var(--bs-navy); white-space:nowrap; }
.bs-rank { display:inline-flex; width:26px; height:26px; border-radius:50%; align-items:center; justify-content:center; font-size:11px; font-weight:800; border:2px solid; }
.bs-r1 { border-color:#d69e2e; background:#fefcbf; color:#975a16; }
.bs-r2 { border-color:#a0aec0; background:#f7fafc; color:#4a5568; }
.bs-r3 { border-color:#dd6b20; background:#fffaf0; color:#9c4221; }
.bs-rx { border-color:var(--bs-border); background:#fff; color:var(--bs-muted); }

.bs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
.bs-mini { border:1px solid var(--bs-border); border-radius:12px; overflow:hidden; }
.bs-mini h6 { margin:0; padding:10px 14px; background:var(--bs-navy); color:#fff; font-size:13px; font-weight:700; display:flex; justify-content:space-between; }
.bs-mini h6 span { font-weight:400; opacity:.75; font-size:11px; }
.bs-chip { display:inline-flex; align-items:center; gap:5px; margin:2px 10px 2px 0; }
.bs-chip small { color:var(--bs-muted); }

@media print {
    .no-print, .bs-form-card { display:none !important; }
    .bs-card { box-shadow:none; break-inside:avoid; }
    .bs-hero { display:none; }
    @page { margin:1.2cm; }
}
@media (prefers-reduced-motion:reduce) { * { transition:none !important; animation:none !important; } }
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid bs-wrap">

    <div class="bs-hero d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1><i class="ri-medal-line me-2"></i>Best students</h1>
            <p>Find the top students across any mix of classes and arms — overall, per class, per arm and in each subject.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            @if(Route::has('broadsheet.ranking.index'))
                <a href="{{ route('broadsheet.ranking.index') }}" class="btn btn-light btn-sm" style="border-radius:10px;font-weight:600;"><i class="ri-settings-3-line me-1"></i>Ranking settings</a>
            @endif
            <a href="{{ route('broadsheet.index') }}" class="btn btn-light btn-sm" style="border-radius:10px;font-weight:600;"><i class="ri-arrow-left-line me-1"></i>Broadsheets</a>
        </div>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    {{-- ══ Selection form ══ --}}
    <details class="bs-card bs-form-card" {{ $report ? '' : 'open' }}>
        <summary><i class="ri-filter-3-line" style="color:var(--bs-teal)"></i>{{ $report ? 'Change selection' : 'Choose classes and arms' }}</summary>
        <div class="bs-card-body">
            <form method="POST" action="{{ route('broadsheet.best-students.report') }}" id="bsForm">
                @csrf

                <span class="bs-label">Classes and arms</span>
                <p class="bs-hint mb-2">Tick a class to include all its arms, or tick single arms. You can mix both, across different classes.</p>
                <div class="bs-classes mb-3">
                    @foreach($classesByGroup as $groupName => $arms)
                        @php $gid = 'g_' . md5($groupName); $groupOn = in_array($groupName, $selGroups, true); @endphp
                        <div class="bs-group">
                            <label class="bs-group-all d-flex align-items-center gap-2">
                                <input type="checkbox" name="class_groups[]" value="{{ $groupName }}" data-group="{{ $gid }}" class="bs-group-cb" @checked($groupOn)>
                                {{ $groupName }} <span class="bs-hint">· all {{ $arms->count() }} arm{{ $arms->count() > 1 ? 's' : '' }}</span>
                            </label>
                            <div class="bs-arms">
                                @foreach($arms as $c)
                                    <label class="d-flex align-items-center gap-1">
                                        <input type="checkbox" name="class_ids[]" value="{{ $c->id }}" data-arm-of="{{ $gid }}"
                                               @checked(in_array((int) $c->id, $selIds, true)) @disabled($groupOn)>
                                        <span>{{ $c->arm ?: 'No arm' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="bs-label" for="bsSession">Session</label>
                        <select id="bsSession" name="sessionid" class="form-select form-select-sm" required>
                            @foreach($schoolsessions as $s)<option value="{{ $s->id }}" @selected((int) $val('sessionid') === (int) $s->id)>{{ $s->session }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="bs-label" for="bsTerm">Term</label>
                        <select id="bsTerm" name="termid" class="form-select form-select-sm" required>
                            @foreach($schoolterms as $t)<option value="{{ $t->id }}" @selected((int) $val('termid') === (int) $t->id)>{{ $t->term }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="bs-label" for="bsMeasure">Rank by</label>
                        <select id="bsMeasure" name="measure" class="form-select form-select-sm">
                            @foreach($measures as $k => $lbl)<option value="{{ $k }}" @selected($val('measure', 'cum_ave') === $k)>{{ $lbl }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="bs-label" for="bsBasis">Subject scores</label>
                        <select id="bsBasis" name="grade_basis" class="form-select form-select-sm">
                            <option value="cum_ave" @selected($val('grade_basis', 'cum_ave') === 'cum_ave')>Cumulative average</option>
                            <option value="total" @selected($val('grade_basis') === 'total')>Term total</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="bs-label" for="bsTop">Overall top</label>
                        <input id="bsTop" type="number" name="top_n" min="1" max="20" value="{{ $val('top_n', 5) }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="bs-label" for="bsSubTop">Per subject top</label>
                        <input id="bsSubTop" type="number" name="subject_top_n" min="1" max="10" value="{{ $val('subject_top_n', 3) }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="bs-label" for="bsMin">Min. subjects</label>
                        <input id="bsMin" type="number" name="min_subjects" min="0" max="40" value="{{ $val('min_subjects', 6) }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="exclude_failed" value="1" id="bsExf" @checked($val('exclude_failed'))>
                            <label class="form-check-label small" for="bsExf">Leave out anyone who failed a subject</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="bs-btn" type="submit"><i class="ri-medal-line me-1"></i>Show best students</button>
                </div>
            </form>
        </div>
    </details>

    {{-- ══ Report ══ --}}
    @if($report)
        <div class="bs-card">
            <div class="bs-report-head">
                @if(!empty($school_logo_base64))<img src="{{ $school_logo_base64 }}" alt="School logo">@endif
                <div>
                    <h2>{{ $schoolInfo->school_name ?? 'Best students' }}</h2>
                    <div class="bs-hint">
                        Best students, {{ $report['session'] }}, {{ $report['term'] }}.
                        Ranked by {{ strtolower($report['measure_label']) }}; subject toppers use {{ $report['basis'] === 'total' ? 'term total' : 'cumulative average' }} scores.
                        {{ $report['eligible_count'] }} of {{ $report['student_count'] }} students ranked.
                    </div>
                </div>
                <button type="button" class="bs-ghost ms-auto no-print" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
            </div>
            <div class="bs-sel">
                @foreach($report['selection'] as $label)<span class="bs-pill">{{ $label }}</span>@endforeach
            </div>

            {{-- Overall --}}
            @if(!empty($report['overall']))
                @php $champs = array_filter($report['overall'], fn ($e) => $e['rank'] === 1); $rest = array_filter($report['overall'], fn ($e) => $e['rank'] > 1); @endphp
                @foreach($champs as $e)
                    <div class="bs-champ">
                        <div class="bs-champ-rank">1</div>
                        <div>
                            <div class="bs-champ-name">{{ $e['name'] }}</div>
                            <div class="bs-hint">{{ $e['arm'] }} · {{ $e['admissionno'] }}</div>
                        </div>
                        <div class="bs-champ-val">
                            <strong>{{ $fmt($e['value']) }}</strong>
                            <span class="bs-hint">{{ $report['measure_label'] }}</span>
                        </div>
                    </div>
                @endforeach
                @if(!empty($rest))
                    <div style="overflow-x:auto;">
                        <table class="bs-table">
                            <thead><tr><th style="width:60px;">Rank</th><th>Student</th><th>Class</th><th>Adm. no</th><th style="text-align:right;">{{ $report['measure_label'] }}</th></tr></thead>
                            <tbody>
                                @foreach($rest as $e)
                                    <tr>
                                        <td><span class="bs-rank {{ $badge($e['rank']) }}">{{ $e['rank'] }}</span></td>
                                        <td style="font-weight:700;color:var(--bs-navy);">{{ $e['name'] }}</td>
                                        <td>{{ $e['arm'] }}</td>
                                        <td>{{ $e['admissionno'] }}</td>
                                        <td class="num">{{ $fmt($e['value']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <p class="text-muted small p-4 mb-0">No student met the rules. Lower the minimum subjects or allow failed subjects, then try again.</p>
            @endif
        </div>

        {{-- Per class (all selected arms together) --}}
        @php $firstClass = collect($report['by_class'])->first(); $showByClass = count($report['by_class']) > 1 || (($firstClass['arms'] ?? 0) > 1); @endphp
        @if($showByClass)
            <div class="bs-card p-3 p-md-4">
                <h3 class="bs-section-title">Best in each class (selected arms together)</h3>
                <div class="bs-grid">
                    @foreach($report['by_class'] as $className => $c)
                        <div class="bs-mini">
                            <h6>{{ $className }} <span>{{ $c['arms'] }} arm{{ $c['arms'] > 1 ? 's' : '' }} · {{ $c['students'] }} students</span></h6>
                            <table class="bs-table">
                                <tbody>
                                    @forelse($c['top'] as $e)
                                        <tr>
                                            <td style="width:44px;"><span class="bs-rank {{ $badge($e['rank']) }}">{{ $e['rank'] }}</span></td>
                                            <td><strong style="color:var(--bs-navy);">{{ $e['name'] }}</strong><div class="bs-hint">{{ $e['arm'] }}</div></td>
                                            <td class="num">{{ $fmt($e['value']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted small">No eligible students.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Per arm --}}
        @if(count($report['by_arm']) > 1)
            <div class="bs-card p-3 p-md-4">
                <h3 class="bs-section-title">Best in each arm</h3>
                <div class="bs-grid">
                    @foreach($report['by_arm'] as $armLabel => $entries)
                        <div class="bs-mini">
                            <h6>{{ $armLabel }}</h6>
                            <table class="bs-table">
                                <tbody>
                                    @foreach($entries as $e)
                                        <tr>
                                            <td style="width:44px;"><span class="bs-rank {{ $badge($e['rank']) }}">{{ $e['rank'] }}</span></td>
                                            <td><strong style="color:var(--bs-navy);">{{ $e['name'] }}</strong><div class="bs-hint">{{ $e['admissionno'] }}</div></td>
                                            <td class="num">{{ $fmt($e['value']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Per subject --}}
        @if(!empty($report['by_subject']))
            <div class="bs-card">
                <div class="bs-card-head" style="cursor:default;"><i class="ri-book-open-line" style="color:var(--bs-teal)"></i>Best in each subject, across the whole selection</div>
                <div style="overflow-x:auto;">
                    <table class="bs-table">
                        <thead><tr><th style="width:22%;">Subject</th><th>Toppers</th></tr></thead>
                        <tbody>
                            @foreach($report['by_subject'] as $subj)
                                <tr>
                                    <td style="font-weight:700;color:var(--bs-navy);">{{ $subj['subject'] }}<div class="bs-hint">{{ $subj['count'] }} scored</div></td>
                                    <td>
                                        @foreach($subj['top'] as $e)
                                            <span class="bs-chip">
                                                <span class="bs-rank {{ $badge($e['rank']) }}">{{ $e['rank'] }}</span>
                                                <span><strong>{{ $e['name'] }}</strong> <small>{{ $e['arm'] }}</small></span>
                                                <strong>{{ $fmt($e['value']) }}</strong>
                                                @if($e['grade'] && $e['grade'] !== '-')<small>{{ $e['grade'] }}</small>@endif
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <p class="bs-hint text-center mb-4">Generated {{ $report['generatedAt'] }}. Unofficial ranking — report-card positions are not changed.</p>
    @endif

</div></div></div>

<script>
(function () {
    'use strict';
    // Ticking "all arms" covers the whole class, so its single-arm boxes are disabled (and not submitted).
    document.querySelectorAll('.bs-group-cb').forEach(function (g) {
        g.addEventListener('change', function () {
            document.querySelectorAll('[data-arm-of="' + g.dataset.group + '"]').forEach(function (a) {
                a.disabled = g.checked;
                if (g.checked) a.checked = false;
            });
        });
    });
    var form = document.getElementById('bsForm');
    if (form) form.addEventListener('submit', function (e) {
        if (!form.querySelector('input[name="class_groups[]"]:checked, input[name="class_ids[]"]:checked')) {
            e.preventDefault();
            alert('Select at least one class or arm.');
        }
    });
})();
</script>
@endsection
