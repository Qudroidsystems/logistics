<table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_exams_table">
    <thead>
        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
            <th class="min-w-125px">SN</th>
            <th class="min-w-125px">Title</th>
            <th class="min-w-125px">Subject</th>
            <th class="min-w-125px">Description</th>
            <th class="min-w-125px">Duration</th>
            <th class="min-w-125px">Start Time</th>
            <th class="min-w-125px">End Time</th>
            <th class="min-w-125px">Status</th>
            <th class="min-w-100px">Actions</th>
        </tr>
    </thead>
    <tbody class="fw-semibold text-gray-600">
        @php
            $i = 1; // fallback
            if (method_exists($exams, 'currentPage') && method_exists($exams, 'perPage')) {
                $i = ($exams->currentPage() - 1) * $exams->perPage() + 1;
            }
        @endphp

        @forelse ($exams as $exam)
            @php
                $now = now();
                $start = $exam->start_time ? \Carbon\Carbon::parse($exam->start_time) : null;
                $end   = $exam->end_time   ? \Carbon\Carbon::parse($exam->end_time)   : null;
                $hasAttempted = in_array($exam->id, $attempts ?? []);
                $status = 'Unknown';

                if ($start && $end) {
                    if ($hasAttempted) {
                        $status = 'Completed';
                    } elseif ($now->lt($start)) {
                        $status = 'Upcoming';
                    } elseif ($now->between($start, $end)) {
                        $status = 'Ongoing';
                    } else {
                        $status = 'Ended';
                    }
                } elseif (!$start || !$end) {
                    $status = 'Invalid Dates';
                }
            @endphp
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $exam->title ?? '—' }}</td>
                <td>
                    {{ $exam->subject->subject ?? 'No Subject' }}
                </td>
                <td>{{ \Illuminate\Support\Str::limit($exam->description ?? '', 50) }}</td>
                <td>{{ $exam->duration ?? '—' }} mins</td>
                <td>{{ $exam->start_time ? \Carbon\Carbon::parse($exam->start_time)->format('Y-m-d h:i A') : 'N/A' }}</td>
                <td>{{ $exam->end_time ? \Carbon\Carbon::parse($exam->end_time)->format('Y-m-d h:i A') : 'N/A' }}</td>
                <td>
                    @if ($hasAttempted)
                        <span class="badge bg-info-subtle text-info">Completed</span>
                    @elseif ($status === 'Upcoming')
                        <span class="badge bg-warning-subtle text-warning">Upcoming</span>
                    @elseif ($status === 'Ongoing')
                        <span class="badge bg-success-subtle text-success">Ongoing</span>
                    @elseif ($status === 'Ended')
                        <span class="badge bg-danger-subtle text-danger">Ended</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">{{ $status }}</span>
                    @endif
                </td>
                <td>
                    @if ($hasAttempted)
                        <span class="badge bg-info-subtle text-info">Exam Taken</span>
                    @elseif ($status === 'Ongoing')
                        @can('Take cbt-exam')
                            <a href="{{ route('cbt.take', $exam->id) }}" class="btn btn-sm btn-primary">Take Exam</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endcan
                    @elseif ($status === 'Upcoming')
                        <span class="text-muted">Starts: {{ $exam->start_time ? \Carbon\Carbon::parse($exam->start_time)->diffForHumans() : 'N/A' }}</span>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    @if ($selectedTermId && $selectedSessionId)
                        No exams available for your registered subjects in the selected term and session.
                        @if ($totalreg > 0 && $reg == 0)
                            <br><small class="text-warning">You have not registered for any subjects yet.</small>
                        @endif
                    @else
                        Please select term and session to view exams.
                    @endif
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
