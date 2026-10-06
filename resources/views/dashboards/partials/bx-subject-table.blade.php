{{-- Best in each subject. Expects: $subjects (subject_id => [subject, count, mean, top]), $showArm (bool) --}}
@php
    $bxFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $bxRk  = fn ($r) => $r === 1 ? 'r1' : ($r === 2 ? 'r2' : ($r === 3 ? 'r3' : 'rn'));
@endphp
<div style="overflow-x:auto;">
<table class="dt">
    <thead><tr>
        <th>Subject</th>
        <th style="width:40px;">Pos</th>
        <th>Student</th>
        @if($showArm)<th>Arm</th>@endif
        <th style="text-align:right;">Score</th>
        <th>Grade</th>
    </tr></thead>
    <tbody>
    @forelse($subjects as $subj)
        @foreach($subj['top'] as $i => $e)
        <tr>
            @if($i === 0)
                <td rowspan="{{ count($subj['top']) }}" style="font-weight:600;color:var(--c-text);vertical-align:top;">
                    {{ $subj['subject'] }}
                    <div style="font-size:10.5px;color:var(--c-muted);font-weight:400;">{{ $subj['count'] }} scored · avg {{ $bxFmt($subj['mean']) }}</div>
                </td>
            @endif
            <td><span class="rank {{ $bxRk($e['rank']) }}">{{ $e['rank'] }}</span></td>
            <td style="font-size:12px;"><span style="font-weight:600;color:var(--c-text);">{{ $e['name'] }}</span></td>
            @if($showArm)<td style="font-size:12px;">{{ $e['arm'] ?: $e['class_label'] }}</td>@endif
            <td style="text-align:right;font-weight:700;color:var(--c-text);">{{ $bxFmt($e['value']) }}</td>
            <td style="font-weight:700;">{{ $e['grade'] }}</td>
        </tr>
        @endforeach
    @empty
        <tr><td colspan="{{ $showArm ? 6 : 5 }}" class="text-center text-muted py-3" style="font-size:12px;">No subject scores recorded</td></tr>
    @endforelse
    </tbody>
</table>
</div>
