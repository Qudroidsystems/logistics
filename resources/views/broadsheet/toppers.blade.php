{{-- resources/views/broadsheet/partials/toppers.blade.php (CSS Kabba)
     Best in each arm (combined view) + best in each subject.
     Sits under the existing ranking partial; uses the cb- variables and
     .pos-badge classes already defined in broadsheet/web.blade.php. --}}
@php
    $tp       = $toppers;
    $showArms = !empty($is_combined) && count($tp['by_arm'] ?? []) > 1;
    $fmt      = fn ($v) => is_numeric($v) ? rtrim(rtrim(number_format((float) $v, 2), '0'), '.') : '—';
    $badge    = fn ($r) => $r <= 3 ? 'pos-' . $r : 'pos-other';
@endphp

@if($showArms || !empty($tp['by_subject']))
<style>
.tp-panel { background:var(--cb-white); border:1px solid var(--cb-border); border-left:4px solid var(--cb-teal); border-radius:var(--cb-radius); box-shadow:var(--cb-shadow); margin-bottom:24px; }
.tp-head { padding:14px 22px; border-bottom:1px solid var(--cb-border); display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; }
.tp-head h5 { margin:0; font-size:15px; font-weight:700; color:var(--cb-navy); }
.tp-sub { font-size:12px; color:var(--cb-muted); margin-top:2px; }
.tp-body { padding:16px 22px; }
.tp-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; }
.tp-block h6 { font-size:12.5px; font-weight:700; color:var(--cb-navy); margin:0 0 6px; }
.tp-row { display:flex; align-items:center; gap:10px; padding:6px 0; border-bottom:1px dashed var(--cb-border); }
.tp-row:last-child { border-bottom:none; }
.tp-row .pos-badge { width:26px; height:26px; font-size:11px; }
.tp-name { font-weight:700; font-size:12.5px; color:var(--cb-navy); line-height:1.25; }
.tp-meta { font-size:10.5px; color:var(--cb-muted); }
.tp-val { margin-left:auto; font-weight:800; font-size:13px; color:var(--cb-navy); }
.tp-subj { width:100%; border-collapse:collapse; font-size:12px; }
.tp-subj th { background:var(--cb-surface); color:var(--cb-navy); font-weight:700; padding:7px 10px; border-bottom:1px solid var(--cb-border); text-align:left; }
.tp-subj td { padding:7px 10px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.tp-chip { display:inline-flex; align-items:center; gap:5px; margin:2px 8px 2px 0; white-space:nowrap; }
.tp-chip b { font-size:10px; color:#92400e; background:#fef3c7; border-radius:4px; padding:1px 5px; }
.tp-toggle { background:none; border:1px solid var(--cb-border); border-radius:8px; padding:5px 12px; font-size:12px; font-weight:600; color:var(--cb-navy); cursor:pointer; }
.tp-toggle:focus-visible { outline:2px solid var(--cb-teal); outline-offset:2px; }
@media print { .tp-panel .no-print { display:none !important; } .tp-collapse { display:block !important; } }
</style>

<div class="tp-panel">
    <div class="tp-head">
        <div>
            <h5><i class="ri-medal-line me-1" style="color:var(--cb-teal)"></i>Toppers{{ $showArms ? ' by arm and subject' : ' by subject' }}</h5>
            <div class="tp-sub">
                Ranked by {{ strtolower($tp['measure_label']) }}. Subject toppers use
                {{ $tp['basis'] === 'total' ? 'term total' : 'cumulative average' }} scores. Unofficial.
            </div>
        </div>
        @if(Route::has('broadsheet.best-students'))
            <a href="{{ route('broadsheet.best-students') }}" class="tp-toggle text-decoration-none no-print">
                <i class="ri-medal-line me-1"></i>Compare more classes
            </a>
        @endif
    </div>

    <div class="tp-body">
        @if($showArms)
            <div class="tp-grid mb-3">
                @foreach($tp['by_arm'] as $armLabel => $entries)
                    <div class="tp-block">
                        <h6>{{ $armLabel }} — top {{ $tp['top_n'] }}</h6>
                        @foreach($entries as $e)
                            <div class="tp-row">
                                <span class="pos-badge {{ $badge($e['rank']) }}">{{ $e['rank'] }}</span>
                                <div>
                                    <div class="tp-name">{{ $e['name'] }}</div>
                                    <div class="tp-meta">{{ $e['admissionno'] }}</div>
                                </div>
                                <span class="tp-val">{{ $fmt($e['value']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($tp['by_subject']))
            <button type="button" class="tp-toggle no-print" aria-expanded="false" aria-controls="tpSubjects"
                    onclick="var b=document.getElementById('tpSubjects');var o=b.style.display!=='none';b.style.display=o?'none':'block';this.setAttribute('aria-expanded',!o);">
                <i class="ri-book-open-line me-1"></i>Best in each subject (top {{ $tp['subject_top_n'] }})
            </button>
            <div id="tpSubjects" class="tp-collapse mt-3" style="display:none;overflow-x:auto;">
                <table class="tp-subj">
                    <thead><tr><th style="width:24%;">Subject</th><th>Toppers</th></tr></thead>
                    <tbody>
                        @foreach($tp['by_subject'] as $subj)
                            <tr>
                                <td style="font-weight:600;color:var(--cb-navy);">{{ $subj['subject'] }}
                                    <div class="tp-meta">{{ $subj['count'] }} scored</div></td>
                                <td>
                                    @foreach($subj['top'] as $e)
                                        <span class="tp-chip">
                                            <b>{{ $e['rank'] }}</b>{{ $e['name'] }}
                                            @if(!empty($is_combined))<span class="tp-meta">({{ $e['arm'] }})</span>@endif
                                            <strong>{{ $fmt($e['value']) }}</strong>
                                            @if($e['grade'] && $e['grade'] !== '-')<span class="tp-meta">{{ $e['grade'] }}</span>@endif
                                        </span>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endif
