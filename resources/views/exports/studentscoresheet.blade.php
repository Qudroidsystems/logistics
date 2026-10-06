<table>
    {{-- ── Row 1: School name ── --}}
    <tr>
        <td colspan="{{ 3 + $assessments->count() + 6 }}">{{ $school->school_name ?? 'School Name' }}</td>
    </tr>
    {{-- ── Row 2: Address ── --}}
    <tr>
        <td colspan="{{ 3 + $assessments->count() + 6 }}">{{ $school->school_address ?? '' }}</td>
    </tr>
    {{-- ── Row 3: Contact / motto ── --}}
    <tr>
        <td colspan="{{ 3 + $assessments->count() + 6 }}">
            {{ $school->school_phone ? 'Phone: ' . $school->school_phone : '' }}
            {{ $school->school_email ? ' | Email: ' . $school->school_email : '' }}
            {{ $school->school_motto ? ' | Motto: ' . $school->school_motto : '' }}
        </td>
    </tr>
    {{-- ── Row 4: Subject / Class / Term / Session ── --}}
    <tr>
        <td colspan="{{ 3 + $assessments->count() + 6 }}">
            @if($broadsheets->isNotEmpty())
                Subject: {{ $broadsheets->first()->subject ?? '-' }} |
                Class: {{ $broadsheets->first()->schoolclass ?? '-' }} {{ $broadsheets->first()->arm ?? '' }} |
                Term: {{ $broadsheets->first()->term ?? '-' }} |
                Session: {{ $broadsheets->first()->session ?? '-' }} |
                Teacher: {{ $broadsheets->first()->staffname ?? '-' }}
            @endif
        </td>
    </tr>
    {{-- ── Row 5: Empty spacer ── --}}
    <tr><td colspan="{{ 3 + $assessments->count() + 6 }}"></td></tr>

    {{-- ── Row 6: Table headers ── --}}
    <thead>
        <tr>
            <th>#</th>
            <th>Admission No</th>
            <th>Student Name</th>
            @foreach($assessments as $assessment)
                <th>{{ $assessment->name }} ({{ $assessment->max_score }})</th>
            @endforeach
            <th>Total ({{ $assessments->sum('max_score') }})</th>
            <th>BF</th>
            <th>Cum</th>
            <th>Grade</th>
            <th>Position</th>
            <th>Remark</th>
        </tr>
    </thead>

    {{-- ── Data rows ── --}}
    <tbody>
        @forelse($broadsheets as $index => $broadsheet)
            @php
                $rowTotal = 0;
                foreach ($assessments as $a) {
                    $scoreObj  = $broadsheet->assessmentScores->where('assessment_id', $a->id)->first();
                    $rowTotal += $scoreObj ? (float)$scoreObj->score : 0;
                }
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $broadsheet->admissionno ?? '-' }}</td>
                <td>{{ trim(($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '')) }}</td>
                @foreach($assessments as $assessment)
                    @php
                        $so = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                    @endphp
                    <td>{{ $so ? number_format($so->score, 1) : '0.0' }}</td>
                @endforeach
                <td>{{ number_format($rowTotal, 1) }}</td>
                <td>{{ number_format($broadsheet->bf ?? 0, 2) }}</td>
                <td>{{ number_format($broadsheet->cum ?? 0, 2) }}</td>
                <td>{{ $broadsheet->grade ?? '-' }}</td>
                <td>{{ $broadsheet->position ?? '-' }}</td>
                <td>{{ $broadsheet->remark ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 3 + $assessments->count() + 6 }}">No scores available.</td>
            </tr>
        @endforelse
    </tbody>
</table>
