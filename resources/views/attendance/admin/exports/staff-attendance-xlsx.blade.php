{{-- resources/views/attendance/admin/exports/staff-attendance-xlsx.blade.php
     Row layout is FIXED (always 6 header rows before the table header on
     row 7), whether or not $school exists or which of its fields are
     filled in — this is what lets StaffAttendanceExport::styles() target
     exact row numbers without them ever drifting. If you add a row here,
     update the row numbers in StaffAttendanceExport::styles() to match. --}}
<table>
    <tr><td colspan="9">{{ $school->school_name ?? 'Staff Attendance Report' }}</td></tr>

    <tr><td colspan="9">
        @php
            $contactParts = array_filter([
                $school->school_address ?? null,
                $school->formatted_phones ?? null,
                $school->school_email ?? null,
            ]);
        @endphp
        {{ $contactParts ? implode(' · ', $contactParts) : '' }}
        {{ ($school->school_motto ?? null) ? ' — "' . $school->school_motto . '"' : '' }}
    </td></tr>

    <tr><td colspan="9"></td></tr>

    <tr><td colspan="9">Staff Attendance Report</td></tr>

    <tr><td colspan="9">{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</td></tr>

    <tr><td colspan="9"></td></tr>

    <tr>
        <th>#</th>
        <th>Staff Name</th>
        <th>Employment ID</th>
        <th>Department</th>
        <th>Present</th>
        <th>Late</th>
        <th>Excused</th>
        <th>Absent</th>
        <th>Attendance %</th>
    </tr>
    @foreach($rows as $i => $r)
    <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $r->full_name }}</td>
        <td>{{ $r->employmentid }}</td>
        <td>{{ $r->department ?? '—' }}</td>
        <td>{{ $r->days_present }}</td>
        <td>{{ $r->days_late }}</td>
        <td>{{ $r->days_excused }}</td>
        <td>{{ $r->days_absent }}</td>
        <td>{{ $r->attendance_percentage }}%</td>
    </tr>
    @endforeach
</table>