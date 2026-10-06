@if($broadsheets->isNotEmpty())
    @php $i = 0; @endphp
    @foreach($broadsheets as $broadsheet)
        <tr>
            <td>{{ ++$i }}</td>
            <td>{{ $broadsheet->admissionno ?? '-' }}</td>
            <td>{{ ($broadsheet->fname ?? '') . ' ' . ($broadsheet->lname ?? '') }}</td>
            <td>{{ $broadsheet->ca1 ?? '-' }}</td>
            <td>{{ $broadsheet->ca2 ?? '-' }}</td>
            <td>{{ $broadsheet->ca3 ?? '-' }}</td>
            <td>{{ $broadsheet->exam ?? '-' }}</td>
            <td>{{ $broadsheet->total ?? '-' }}</td>
            <td>{{ $broadsheet->grade ?? '-' }}</td>
            <td>{{ $broadsheet->position ?? '-' }}</td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="10" class="text-center">No scores available.</td>
    </tr>
@endif