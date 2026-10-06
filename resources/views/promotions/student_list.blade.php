{{-- resources/views/promotions/student_list.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $pagetitle }} — {{ $scopeLabel }}</title>
<style id="pageSizeStyle">@page { size: A4 portrait; margin: 12mm; }</style>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #1e293b; margin: 0; background: #eef2f7; font-size: 12px; }
    .toolbar { position: sticky; top: 0; z-index: 10; background: #0f2342; color: #fff; padding: 10px 16px;
               display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
    .toolbar label { font-size: 12px; display: inline-flex; align-items: center; gap: 6px; }
    .toolbar select { padding: 4px 6px; border-radius: 6px; border: none; font-size: 12px; }
    .toolbar button { background: #2563eb; color: #fff; border: none; padding: 7px 14px; border-radius: 7px; font-weight: 600; cursor: pointer; }
    .toolbar .spacer { flex: 1; }
    .sheet { background: #fff; max-width: 1100px; margin: 18px auto; padding: 24px 28px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
    .head { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 12px; }
    .head img { width: 64px; height: 64px; object-fit: contain; }
    .head h1 { font-size: 18px; margin: 0; color: #1e3a5f; }
    .head .meta { font-size: 11.5px; color: #475569; margin-top: 3px; }
    .summary { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 14px; }
    .chip { border: 1px solid #cbd5e1; border-radius: 14px; padding: 3px 10px; font-size: 11px; }
    .class-block { margin-bottom: 18px; }
    .class-title { background: #1e3a5f; color: #fff; padding: 6px 10px; font-weight: 700; font-size: 13px; }
    .group-title { font-weight: 700; padding: 6px 2px 4px; font-size: 12px; border-bottom: 1px solid #e2e8f0; }
    .g-promoted { color: #15803d; } .g-trial { color: #b45309; } .g-see_principal { color: #1d4ed8; }
    .g-repeated { color: #b91c1c; } .g-awaiting, .g-__other { color: #475569; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; }
    th { background: #f1f5f9; font-size: 11px; }
    td.num, th.num { text-align: center; }
    .foot { font-size: 10.5px; color: #64748b; margin-top: 12px; display: flex; justify-content: space-between; }
    .empty { text-align: center; padding: 40px; color: #64748b; }
    body.hide-avg .col-avg, body.hide-pos .col-pos, body.hide-rule .col-rule { display: none; }
    body.break-class .class-block + .class-block { break-before: page; }
    @media print {
        body { background: #fff; }
        .toolbar { display: none; }
        .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
        tr { break-inside: avoid; }
    }
</style>
</head>
<body>
<div class="toolbar">
    <label>Paper
        <select id="optSize">
            @foreach (['A4','A3','A2','A1','Legal','Letter'] as $size)
                <option value="{{ $size }}">{{ $size }}</option>
            @endforeach
        </select>
    </label>
    <label>Orientation
        <select id="optOrient"><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select>
    </label>
    <label><input type="checkbox" id="optAvg" checked> Average</label>
    <label><input type="checkbox" id="optPos" checked> Position</label>
    <label><input type="checkbox" id="optRule"> Matched rule</label>
    <label><input type="checkbox" id="optBreak"> New page per class</label>
    <span class="spacer"></span>
    <button type="button" onclick="window.print()">Print</button>
</div>

<div class="sheet">
    <div class="head">
        <img src="{{ $logo }}" alt="Logo">
        <div>
            <h1>{{ $schoolInfo->school_name ?? 'School' }}</h1>
            <div class="meta">
                Student Promotion List — <strong>{{ $scopeLabel }}</strong> ·
                {{ $sessionName }} · {{ $termName }} ·
                Basis: {{ $averageBasis === 'cum' ? 'Cumulative (Cum Avg)' : 'Term Total' }}
            </div>
        </div>
    </div>

    @if ($grandTotal > 0)
        <div class="summary">
            <span class="chip"><strong>{{ $grandTotal }}</strong> students</span>
            @foreach ($overall as $key => $count)
                <span class="chip g-{{ $key }}">{{ $labels[$key] ?? $key }}: <strong>{{ $count }}</strong></span>
            @endforeach
        </div>
    @endif

    @forelse ($classGroups as $class)
        <div class="class-block">
            <div class="class-title">{{ $class['label'] }} — {{ $class['totalStudents'] }} student(s)</div>
            @foreach ($class['grouped'] as $key => $rows)
                <div class="group-title g-{{ $key }}">{{ $labels[$key] ?? $key }} ({{ count($rows) }})</div>
                <table>
                    <thead>
                        <tr>
                            <th class="num" style="width:36px">S/N</th>
                            <th style="width:110px">Adm. No</th>
                            <th>Name</th>
                            <th style="width:70px">Gender</th>
                            @if ($scope !== 'class')<th style="width:60px">Arm</th>@endif
                            <th class="num col-avg" style="width:80px">Average</th>
                            <th class="num col-pos" style="width:70px">Position</th>
                            <th class="col-rule">Matched rule</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $i => $r)
                            <tr>
                                <td class="num">{{ $i + 1 }}</td>
                                <td>{{ $r['admissionno'] }}</td>
                                <td>{{ $r['name'] }}</td>
                                <td>{{ $r['gender'] }}</td>
                                @if ($scope !== 'class')<td>{{ $r['arm'] ?? '—' }}</td>@endif
                                <td class="num col-avg">{{ $r['overall_average'] !== null ? number_format($r['overall_average'], 1) . '%' : '—' }}</td>
                                <td class="num col-pos">{{ $r['position'] ?? '—' }}</td>
                                <td class="col-rule">{{ $r['rule'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    @empty
        <div class="empty">No students found for this scope, session and term.</div>
    @endforelse

    <div class="foot">
        <span>Recommendations are computed live from promotion settings; saved decisions may differ.</span>
        <span>Generated {{ $generatedAt }}</span>
    </div>
</div>

<script>
(function () {
    const KEY = 'promoListPrintOpts';
    const els = {
        size: document.getElementById('optSize'), orient: document.getElementById('optOrient'),
        avg: document.getElementById('optAvg'), pos: document.getElementById('optPos'),
        rule: document.getElementById('optRule'), brk: document.getElementById('optBreak'),
    };
    let saved = {};
    try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { saved = {}; }
    if (saved.size)   els.size.value = saved.size;
    if (saved.orient) els.orient.value = saved.orient;
    ['avg', 'pos', 'rule', 'brk'].forEach(k => { if (typeof saved[k] === 'boolean') els[k].checked = saved[k]; });

    function apply() {
        document.getElementById('pageSizeStyle').textContent =
            `@page { size: ${els.size.value} ${els.orient.value}; margin: 12mm; }`;
        document.body.classList.toggle('hide-avg', !els.avg.checked);
        document.body.classList.toggle('hide-pos', !els.pos.checked);
        document.body.classList.toggle('hide-rule', !els.rule.checked);
        document.body.classList.toggle('break-class', els.brk.checked);
        try {
            localStorage.setItem(KEY, JSON.stringify({
                size: els.size.value, orient: els.orient.value,
                avg: els.avg.checked, pos: els.pos.checked, rule: els.rule.checked, brk: els.brk.checked,
            }));
        } catch (e) { /* storage unavailable -- settings just won't persist */ }
    }
    Object.values(els).forEach(el => el.addEventListener('change', apply));
    apply();
})();
</script>
</body>
</html>
