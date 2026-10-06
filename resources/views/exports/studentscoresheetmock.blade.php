<style>
@charset "UTF-8";

@media print {
    body {
        margin: 0;
        width: 940px;
    }

    .print-body {
        background-color: white;
    }

    @page {
        size: 940px;
        margin: 0;
    }

    nav {
        display: none;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Times New Roman', Times, serif;
    }

    th, td {
        border: 1px solid black;
        padding: 5px;
        text-align: center;
        font-size: 12px;
    }

    th {
        font-weight: bold;
        background-color: #f0f0f0;
    }

    p.school-name1 {
        font-size: 40px;
        font-weight: 500;
        text-align: center;
    }

    p.school-name2 {
        font-size: 30px;
        font-weight: bold;
        text-align: center;
    }

    .result-details {
        font-size: 16px;
        font-weight: lighter;
        font-style: italic;
    }

    .fraction {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        font-size: 10px;
    }

    .fraction .numerator {
        border-bottom: 2px solid black;
        padding: 0 5px;
    }

    .fraction .denominator {
        padding-top: 5px;
    }
}
</style>

<table>
    <!-- School Information Header -->
    <tr>
        <td colspan="15">{{ $school->school_name ?? 'School Name Not Set' }}</td>
    </tr>
    <tr>
        <td colspan="15">{{ $school->school_address ?? 'Address Not Set' }}</td>
    </tr>
    <tr>
        <td colspan="15">
            @if($school->school_phone || $school->school_email || $school->school_motto)
                {{ $school->school_phone ? 'Phone: ' . $school->school_phone : '' }}
                {{ $school->school_email ? ($school->school_phone ? ' | ' : '') . 'Email: ' . $school->school_email : '' }}
                {{ $school->school_motto ? ($school->school_phone || $school->school_email ? ' | ' : '') . 'Motto: ' . $school->school_motto : '' }}
            @else
                Contact Information Not Set
            @endif
        </td>
    </tr>
    <tr>
        <td colspan="15">
            @if($broadsheets->isNotEmpty())
                Subject: {{ $broadsheets->first()->subject ?? '-' }} |
                Class: {{ $broadsheets->first()->schoolclass ?? '-' }} {{ $broadsheets->first()->arm ?? '' }} |
                Term: {{ $broadsheets->first()->term ?? '-' }} |
                Session: {{ $broadsheets->first()->session ?? '-' }}
            @else
                No Scores Available
            @endif
        </td>
    </tr>
    <tr><td colspan="15"></td></tr> <!-- Empty row for spacing -->

    <!-- Scoresheet Table -->
    @if($broadsheets->isNotEmpty())
        <thead>
            <tr>
                <th>#</th>
                <th>Admission No.</th>
                <th>Name</th>
                <th>Exam</th>
                <th>
                    <div>Total</div>
                </th>
                <th>Grade</th>
                <th>Class Avg.</th>
                <th>Position</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($broadsheets as $index => $broadsheet)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $broadsheet->admissionno ?? '-' }}</td>
                    <td>
                        @if($broadsheet->lname || $broadsheet->fname || $broadsheet->mname)
                            <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span> {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ number_format($broadsheet->exam ?? 0, 1) }}</td>
                    <td>{{ $broadsheet->grade ?? '-' }}</td>
                    <td>{{ number_format($broadsheet->avg ?? 0, 1) }}</td>
                    <td>{{ $broadsheet->position ?? '-' }}</td>
                    <td>{{ $broadsheet->remark ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    @endif
</table>