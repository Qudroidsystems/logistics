{{-- Print: ranked students. Expects $entries, $showClass, $isSum, $fmt --}}
<table>
    <thead><tr>
        <th style="width:34px;text-align:center;">Pos</th>
        <th>Student</th>
        <th>Adm. No</th>
        @if($showClass)<th>Class</th>@endif
        <th class="num">Subjects</th>
        <th class="num">Average</th>
        <th class="num">Total</th>
        <th>Grade</th>
    </tr></thead>
    <tbody>
    @forelse($entries as $e)
        <tr class="{{ $e['rank'] <= 3 ? 'p' . $e['rank'] : '' }}">
            <td class="pos">{{ $e['rank'] }}</td>
            <td><strong>{{ $e['name'] }}</strong></td>
            <td>{{ $e['admissionno'] }}</td>
            @if($showClass)<td>{{ $e['class_label'] }}</td>@endif
            <td class="num">{{ $e['subjects'] }}</td>
            <td class="num">{!! $isSum ? e($fmt($e['avg'])) : '<strong>' . e($fmt($e['avg'])) . '</strong>' !!}</td>
            <td class="num">{!! $isSum ? '<strong>' . e($fmt($e['sum'])) . '</strong>' : e($fmt($e['sum'])) !!}</td>
            <td><strong>{{ $e['grade'] }}</strong></td>
        </tr>
    @empty
        <tr><td colspan="{{ $showClass ? 8 : 7 }}" class="empty">No student met the selected criteria</td></tr>
    @endforelse
    </tbody>
</table>
