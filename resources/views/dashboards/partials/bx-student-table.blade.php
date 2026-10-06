{{-- Ranked students table. Expects: $entries, $report, $showClass (bool) --}}
@php
    $bxFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $bxSp  = fn ($v) => $v >= 75 ? 'sp-A' : ($v >= 60 ? 'sp-B' : ($v >= 50 ? 'sp-C' : ($v >= 40 ? 'sp-D' : 'sp-F')));
    $bxRk  = fn ($r) => $r === 1 ? 'r1' : ($r === 2 ? 'r2' : ($r === 3 ? 'r3' : 'rn'));
    $isSum = ($report['options']['measure'] ?? 'average') === 'sum';
@endphp
<div style="overflow-x:auto;">
<table class="dt">
    <thead><tr>
        <th style="width:40px;">Pos</th>
        <th>Student</th>
        @if($showClass)<th>Class</th>@endif
        <th style="text-align:right;">Subjects</th>
        <th style="text-align:right;">Average</th>
        <th style="text-align:right;">Total</th>
        <th>Grade</th>
    </tr></thead>
    <tbody>
    @forelse($entries as $e)
        <tr>
            <td><span class="rank {{ $bxRk($e['rank']) }}">{{ $e['rank'] }}</span></td>
            <td>
                <div style="font-weight:600;font-size:12.5px;color:var(--c-text);">{{ $e['name'] }}</div>
                <div style="font-size:10.5px;color:var(--c-muted);">{{ $e['admissionno'] }}</div>
            </td>
            @if($showClass)<td style="font-size:12px;">{{ $e['class_label'] }}</td>@endif
            <td style="text-align:right;">{{ $e['subjects'] }}</td>
            <td style="text-align:right;">
                @if(!$isSum)<span class="sp {{ $bxSp($e['avg']) }}">{{ $bxFmt($e['avg']) }}</span>@else{{ $bxFmt($e['avg']) }}@endif
            </td>
            <td style="text-align:right;{{ $isSum ? 'font-weight:700;color:var(--c-text);' : '' }}">{{ $bxFmt($e['sum']) }}</td>
            <td style="font-weight:700;">{{ $e['grade'] }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ $showClass ? 7 : 6 }}" class="text-center text-muted py-3" style="font-size:12px;">No student met the selected criteria</td></tr>
    @endforelse
    </tbody>
</table>
</div>
