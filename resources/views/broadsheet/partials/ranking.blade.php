{{-- resources/views/broadsheet/partials/ranking.blade.php
     Unofficial best-student ranking panel. Rendered only when $ranking is set.
     Never reads or alters the official position columns. --}}
@isset($ranking)
@php
    $isCombinedView = !empty($is_combined);
    $medals = ['🥇','🥈','🥉'];
@endphp
<div class="ranking-panel" style="margin:0 0 18px;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05);">
    <div style="background:linear-gradient(90deg,#1e3a5f,#0ea5a4);color:#fff;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <div style="font-weight:700;letter-spacing:.5px;">
            <i class="ri-trophy-line me-1"></i>BEST STUDENTS
            @unless($ranking['is_default'])
                <span style="background:#f59e0b;color:#1f2937;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;margin-left:8px;">UNOFFICIAL RANKING</span>
            @endunless
        </div>
        {{-- Rank-by one-off override --}}
        <div class="no-print" style="display:flex;align-items:center;gap:8px;font-size:12px;">
            <span style="opacity:.85;">Rank by:</span>
            <select id="rankBySelect" class="form-select form-select-sm" style="width:auto;min-width:170px;"
                    onchange="document.getElementById('rankByInput').value=this.value;document.getElementById('rankByForm').submit();">
                @foreach(\App\Models\BroadsheetRankingSetting::MEASURES as $k => $lbl)
                    <option value="{{ $k }}" @selected($ranking['measure_key']===$k)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div style="padding:14px 18px;">
        <div class="small text-muted mb-3"><i class="ri-information-line me-1"></i>{{ $ranking['criteria_text'] }}@unless($ranking['is_default']) · <strong>not the saved default</strong>@endunless</div>

        {{-- Best-student highlight --}}
        @if(($ranking['best']['type'] ?? '') === 'per_arm')
            @foreach($ranking['best']['groups'] as $arm => $list)
                <div class="fw-semibold small mb-1">{{ $arm }}</div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach($list as $b)
                        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;min-width:170px;">
                            <div style="font-size:12px;color:#6b7280;">#{{ $b['rank'] }}</div>
                            <div class="fw-semibold">{{ $b['name'] }}</div>
                            <div style="font-size:12px;color:#0ea5a4;font-weight:700;">{{ rtrim(rtrim(number_format($b['value'],2),'0'),'.') }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <div class="d-flex flex-wrap gap-3 mb-2">
                @forelse($ranking['best']['list'] as $i => $b)
                    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px 16px;min-width:190px;flex:1;max-width:260px;background:{{ $i===0 ? 'linear-gradient(135deg,#fff7ed,#fff)' : '#fff' }};">
                        <div style="font-size:22px;">{{ $medals[$i] ?? ('#'.$b['rank']) }}</div>
                        <div class="fw-bold" style="font-size:15px;">{{ $b['name'] }}</div>
                        @if($isCombinedView && $b['arm'])<div style="font-size:12px;color:#6b7280;">{{ $b['arm'] }}</div>@endif
                        <div style="margin-top:4px;color:#0ea5a4;font-weight:700;">{{ $ranking['measure_label'] }}: {{ rtrim(rtrim(number_format($b['value'],2),'0'),'.') }}</div>
                    </div>
                @empty
                    <div class="text-muted small">No eligible students for the current criteria.</div>
                @endforelse
            </div>
        @endif

        {{-- Full ranking (collapsible) --}}
        @if(count($ranking['ranked']))
        <details class="mt-2">
            <summary class="small text-primary" style="cursor:pointer;">Show full ranking ({{ count($ranking['ranked']) }})</summary>
            <div class="table-responsive mt-2" style="max-height:360px;overflow:auto;">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead><tr><th style="width:50px;">Rank</th><th>Student</th>@if($isCombinedView)<th>Arm</th>@endif<th class="text-end">{{ $ranking['measure_label'] }}</th></tr></thead>
                    <tbody>
                        @foreach($ranking['ranked'] as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r['rank'] }}</td>
                                <td>{{ $r['name'] }}</td>
                                @if($isCombinedView)<td class="text-muted">{{ $r['arm'] }}</td>@endif
                                <td class="text-end">{{ rtrim(rtrim(number_format($r['value'],2),'0'),'.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
        @endif

        @if(count($ranking['ineligible']))
        <div class="small text-muted mt-2"><i class="ri-error-warning-line me-1"></i>{{ count($ranking['ineligible']) }} student(s) excluded by eligibility rules.</div>
        @endif
    </div>
</div>

{{-- Rank-by resubmission form (mirrors the grade-basis form's hidden fields) --}}
<form id="rankByForm" method="POST" action="{{ url()->current() }}" style="display:none;">
    @csrf
    @if(!empty($is_combined))
        <input type="hidden" name="classgroup" value="{{ request('classgroup') }}">
    @else
        <input type="hidden" name="schoolclassid" value="{{ request('schoolclassid') }}">
    @endif
    <input type="hidden" name="sessionid" value="{{ request('sessionid') }}">
    <input type="hidden" name="termid" value="{{ request('termid') }}">
    <input type="hidden" name="grade_basis" value="{{ $grade_basis ?? 'cum_ave' }}">
    <input type="hidden" name="rank_by" id="rankByInput" value="{{ $ranking['measure_key'] }}">
    @foreach(request('selectedColumns', []) as $i => $col)
        <input type="hidden" name="selectedColumns[{{ $i }}]" value="{{ $col }}">
    @endforeach
</form>
@endisset
