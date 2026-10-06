{{-- resources/views/student-leave/approvals.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Student Leave Approvals" icon="ri-user-follow-line" subtitle="Review leave requests for students. Class teachers recommend first, then the principal approves.">
        <x-slot name="actions">
            @can('View student leave records')<a href="{{ route('student-leave.records') }}" class="action-btn btn-go"><i class="ri-archive-line"></i>All records</a>@endcan
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <x-cb.card title="Awaiting your action" icon="ri-time-line" :count="$pending->count()" :flush="true">
        @if($pending->isEmpty())
            <div class="empty-state"><i class="ri-checkbox-circle-line"></i><h6>Nothing waiting</h6><p>There are no student leave requests for you to review right now.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>Class</th><th>Reason</th><th>Dates</th><th class="text-end">Days</th><th>Stage</th><th class="text-end">Decision</th></tr></thead>
                <tbody>
                @foreach($pending as $r) @php [$l, $c] = $r->label(); $isTeacherStage = $r->status === 'pending_teacher'; @endphp
                    <tr>
                        <td>
                            {{ trim(($r->student->firstname ?? '') . ' ' . ($r->student->lastname ?? '')) }}
                            @if($r->student->admissionNo ?? null)<div class="small text-muted">{{ $r->student->admissionNo }}</div>@endif
                        </td>
                        <td class="small">{{ $classNames[$r->class_id] ?? '—' }}</td>
                        <td>{{ $r->reasonLabel() }}
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($r->reason, 50) }}</div>
                            @if($r->teacher_note)<div class="small text-muted"><i class="ri-chat-quote-line"></i> Teacher: {{ \Illuminate\Support\Str::limit($r->teacher_note, 50) }}</div>@endif
                        </td>
                        <td class="small">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M Y') }}</td>
                        <td class="text-end">{{ $r->days }}</td>
                        <td><span class="status-pill {{ $c }}">{{ $l }}</span></td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end align-items-center">
                                @if($r->attachment)<a href="{{ route('student-leave.attachment', $r) }}" class="action-btn btn-open" title="Document"><i class="ri-attachment-2"></i></a>@endif
                                <button class="action-btn btn-primary-cb" data-sl-act="{{ $r->id }}" data-sl-yes="1" data-sl-stage="{{ $isTeacherStage ? 'recommend' : 'approve' }}">
                                    <i class="ri-check-line"></i>{{ $isTeacherStage ? 'Recommend' : 'Approve' }}
                                </button>
                                <button class="action-btn btn-open" data-sl-act="{{ $r->id }}" data-sl-yes="0" data-sl-stage="{{ $isTeacherStage ? 'recommend' : 'approve' }}">
                                    <i class="ri-close-line"></i>Decline
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>

    @if($recent->isNotEmpty())
    <x-cb.card title="Recently decided" icon="ri-history-line" :count="$recent->count()" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Class</th><th>Reason</th><th>Dates</th><th>Outcome</th></tr></thead>
            <tbody>
            @foreach($recent as $r) @php [$l, $c] = $r->label(); @endphp
                <tr>
                    <td>{{ trim(($r->student->firstname ?? '') . ' ' . ($r->student->lastname ?? '')) }}</td>
                    <td class="small">{{ $classNames[$r->class_id] ?? '—' }}</td>
                    <td class="small">{{ $r->reasonLabel() }}</td>
                    <td class="small">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M Y') }}</td>
                    <td><span class="status-pill {{ $c }}">{{ $l }}</span>
                        @if($r->approver_note)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($r->approver_note, 40) }}</div>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </x-cb.card>
    @endif
</div>
</div>
</div>

{{-- Hidden decision form + tiny prompt --}}
<form method="POST" id="slActForm" action="" class="d-none">@csrf
    <input type="hidden" name="decision" id="slDecision">
    <input type="hidden" name="note" id="slNote">
</form>
<script>
(function () {
    var base = "{{ url('student-leave') }}";
    document.querySelectorAll('[data-sl-act]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-sl-act');
            var yes = btn.getAttribute('data-sl-yes') === '1';
            var stage = btn.getAttribute('data-sl-stage');
            var verb = stage === 'recommend' ? (yes ? 'recommend' : 'not recommend') : (yes ? 'approve' : 'decline');
            var note = '';
            if (!yes) {
                note = window.prompt('Optional note for the ' + verb + ' decision (why?):', '');
                if (note === null) return; // cancelled
            } else if (!window.confirm('Are you sure you want to ' + verb + ' this leave request?')) {
                return;
            }
            var f = document.getElementById('slActForm');
            f.action = base + '/' + id + '/act';
            document.getElementById('slDecision').value = yes ? 'yes' : 'no';
            document.getElementById('slNote').value = note || '';
            f.submit();
        });
    });
})();
</script>
@endsection
