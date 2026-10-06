{{-- Print: best in each subject. Expects $subjects, $showArm, $fmt --}}
<table>
    <thead><tr>
        <th style="width:26%;">Subject</th>
        <th style="width:34px;text-align:center;">Pos</th>
        <th>Student</th>
        <th>Adm. No</th>
        @if($showArm)<th>Arm</th>@endif
        <th class="num">Score</th>
        <th>Grade</th>
    </tr></thead>
    <tbody>
    @forelse($subjects as $subj)
        @foreach($subj['top'] as $i => $e)
            <tr class="{{ $e['rank'] <= 3 ? 'p' . $e['rank'] : '' }}">
                @if($i === 0)
                    <td class="subj" rowspan="{{ count($subj['top']) }}">
                        {{ $subj['subject'] }}
                        <div class="muted">{{ $subj['count'] }} scored · subject average {{ $fmt($subj['mean']) }}</div>
                    </td>
                @endif
                <td class="pos">{{ $e['rank'] }}</td>
                <td><strong>{{ $e['name'] }}</strong></td>
                <td>{{ $e['admissionno'] }}</td>
                @if($showArm)<td>{{ $e['arm'] ?: $e['class_label'] }}</td>@endif
                <td class="num"><strong>{{ $fmt($e['value']) }}</strong></td>
                <td><strong>{{ $e['grade'] }}</strong></td>
            </tr>
        @endforeach
    @empty
        <tr><td colspan="{{ $showArm ? 7 : 6 }}" class="empty">No subject scores recorded</td></tr>
    @endforelse
    </tbody>
</table>
