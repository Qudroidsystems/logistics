<table>
    <thead>
        <tr>
            <th colspan="{{ 3 + $assessments->count() + 7 }}" style="text-align: center; font-size: 14pt; font-weight: bold;">
                {{ $school->school_name ?? 'School Name' }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ 3 + $assessments->count() + 7 }}" style="text-align: center;">
                {{ $school->school_address ?? '' }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ 3 + $assessments->count() + 7 }}" style="text-align: center;">
                @if($school)
                    Phone: {{ $school->school_phone ?? '' }} | Email: {{ $school->school_email ?? '' }} | Motto: {{ $school->school_motto ?? '' }}
                @endif
            </th>
        </tr>
        <tr>
            <th colspan="{{ 3 + $assessments->count() + 7 }}" style="text-align: center;">
                @if($broadsheets->isNotEmpty())
                    Subject: {{ $broadsheets->first()->subject ?? '-' }} |
                    Class: {{ $broadsheets->first()->schoolclass ?? '-' }} {{ $broadsheets->first()->arm ?? '' }} |
                    Term: {{ $broadsheets->first()->term ?? '-' }} |
                    Session: {{ $broadsheets->first()->session ?? '-' }}
                @endif
            </th>
        </tr>
        <tr><td colspan="{{ 3 + $assessments->count() + 7 }}"></td></tr>
        <tr style="background-color: #1e3a5f; color: white; font-weight: bold;">
            <th style="width: 40px;">#</th>
            <th style="width: 120px;">Admission No</th>
            <th style="width: 200px;">Student Name</th>
            @foreach($assessments as $assessment)
                <th style="width: 80px; text-align: center;">{{ $assessment->name }}<br>({{ $assessment->max_score }})</th>
            @endforeach
            <th style="width: 80px; text-align: center;">Total</th>
            <th style="width: 80px; text-align: center;">BF</th>
            <th style="width: 80px; text-align: center;">Cum</th>
            <th style="width: 60px; text-align: center;">Grade</th>
            <th style="width: 80px; text-align: center;">Position</th>
            <th style="width: 100px; text-align: center;">Remark</th>
        </tr>
    </thead>
    <tbody>
        @forelse($broadsheets as $index => $broadsheet)
            @php
                $rowTotal = 0;
                foreach ($assessments as $a) {
                    $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $a->id)->first();
                    $rowTotal += $scoreObj ? (float)$scoreObj->score : 0;
                }
            @endphp
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $broadsheet->admissionno ?? '-' }}</td>
                <td>{{ trim(($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '')) }}</td>
                @foreach($assessments as $assessment)
                    @php
                        $so = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                    @endphp
                    <td style="text-align: center;">{{ $so ? number_format($so->score, 1) : '0.0' }}</td>
                @endforeach
                <td style="text-align: center; font-weight: bold;">{{ number_format($rowTotal, 1) }}</td>
                <td style="text-align: center;">{{ number_format($broadsheet->bf ?? 0, 2) }}</td>
                <td style="text-align: center;">{{ number_format($broadsheet->cum ?? 0, 2) }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $broadsheet->grade ?? '-' }}</td>
                <td style="text-align: center;">{{ $broadsheet->position ?? '-' }}</td>
                <td>{{ $broadsheet->remark ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 3 + $assessments->count() + 7 }}" style="text-align: center;">No scores available.</td>
            </tr>
        @endforelse
    </tbody>
</table>
