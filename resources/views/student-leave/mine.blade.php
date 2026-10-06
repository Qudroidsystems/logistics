{{-- resources/views/student-leave/mine.blade.php --}}
@extends('layouts.master')

@section('content')
@php $S = \App\Models\StudentLeaveRequest::STATUS; $multi = count($students) > 1; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Leave of Absence" icon="ri-calendar-event-line" subtitle="Request permission for {{ $multi ? 'your children' : 'yourself' }} to be away from school, and track the decision." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-5">
            <x-cb.card title="New request" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('student-leave.store') }}" enctype="multipart/form-data">@csrf
                    <label class="form-label small">Student</label>
                    <select name="student_id" class="form-select mb-2" required>
                        @foreach($students as $s)<option value="{{ $s->id }}">{{ trim($s->firstname . ' ' . $s->lastname) }}{{ $s->admissionNo ? ' · ' . $s->admissionNo : '' }}</option>@endforeach
                    </select>
                    <label class="form-label small">Reason</label>
                    <select name="reason_type" class="form-select mb-2" required>@foreach($reasons as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label small">From</label><input type="date" name="start_date" class="form-control" min="{{ now()->toDateString() }}" required></div>
                        <div class="col-6"><label class="form-label small">To</label><input type="date" name="end_date" class="form-control" min="{{ now()->toDateString() }}" required></div>
                    </div>
                    <label class="form-label small">Details</label>
                    <textarea name="reason" class="form-control mb-2" rows="3" minlength="5" maxlength="1000" required placeholder="Explain why the student needs to be away."></textarea>
                    <label class="form-label small">Contact phone (optional)</label>
                    <input name="contact_phone" class="form-control mb-2" maxlength="30" placeholder="A number the school can reach">
                    <label class="form-label small">Supporting document (optional)</label>
                    <input type="file" name="attachment" class="form-control mb-3" accept=".pdf,.jpg,.jpeg,.png">
                    <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-line"></i>Send request</button>
                    <div class="small text-muted mt-2">The class teacher reviews it first, then the principal approves. You'll be notified at each step.</div>
                </form>
            </x-cb.card>
        </div>
        <div class="col-xl-7">
            <x-cb.card title="Requests" icon="ri-history-line" :count="$requests->count()" :flush="true">
                @if($requests->isEmpty())
                    <div class="empty-state"><i class="ri-calendar-event-line"></i><h6>No requests yet</h6><p>Use the form to request leave of absence.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Student</th><th>Reason</th><th>Dates</th><th class="text-end">Days</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($requests as $r) @php [$l, $c] = $r->label(); @endphp
                            <tr>
                                <td>{{ trim(($r->student->firstname ?? '') . ' ' . ($r->student->lastname ?? '')) }}</td>
                                <td>{{ $r->reasonLabel() }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($r->reason, 40) }}</div></td>
                                <td class="small">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M Y') }}</td>
                                <td class="text-end">{{ $r->days }}</td>
                                <td><span class="status-pill {{ $c }}">{{ $l }}</span>
                                    @if($r->status === 'rejected' && $r->approver_note)<div class="small text-danger">{{ $r->approver_note }}</div>@endif</td>
                                <td class="text-end">
                                    @if($r->attachment)<a href="{{ route('student-leave.attachment', $r) }}" class="action-btn btn-open" title="Document"><i class="ri-attachment-2"></i></a>@endif
                                    @if($r->isOpen())<form method="POST" action="{{ route('student-leave.cancel', $r) }}" class="d-inline" onsubmit="return confirm('Withdraw this request?')">@csrf<button class="action-btn btn-open" title="Withdraw"><i class="ri-close-line"></i></button></form>@endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
