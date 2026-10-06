{{-- resources/views/student-leave/records.blade.php --}}
@extends('layouts.master')

@section('content')
@php $S = \App\Models\StudentLeaveRequest::STATUS; $R = \App\Models\StudentLeaveRequest::REASONS; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Student Leave Records" icon="ri-archive-line" subtitle="Every student leave request, with filters and export.">
        <x-slot name="actions">
            <a href="{{ route('student-leave.approvals') }}" class="action-btn btn-go"><i class="ri-user-follow-line"></i>Approvals</a>
            <a href="{{ route('student-leave.records.export', request()->query()) }}" class="action-btn btn-primary-cb"><i class="ri-download-2-line"></i>Export CSV</a>
        </x-slot>
    </x-cb.hero>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="On leave today" :value="$stats['on_leave']" icon="ri-user-unfollow-line" accent="warning" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Pending" :value="$stats['pending']" icon="ri-time-line" accent="info" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Approved this month" :value="$stats['approved_month']" icon="ri-checkbox-circle-line" accent="success" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Total records" :value="$rows->total()" icon="ri-file-list-3-line" /></div>
    </div>

    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Search student</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or admission no"></div>
            <div class="col-md-2"><label class="form-label small">Status</label>
                <select name="status" class="form-select"><option value="">Any</option>
                    @foreach($S as $k => $v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v[0] }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Reason</label>
                <select name="reason" class="form-select"><option value="">Any</option>
                    @foreach($R as $k => $v)<option value="{{ $k }}" @selected(request('reason')===$k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-1"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i></button></div>
        </form>
    </x-cb.card>

    <x-cb.card title="Records" icon="ri-file-list-3-line" :count="$rows->total()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-inbox-line"></i><h6>No records</h6><p>No student leave requests match these filters.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>Class</th><th>Reason</th><th>Dates</th><th class="text-end">Days</th><th>Requested by</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($rows as $r) @php [$l, $c] = $r->label(); @endphp
                    <tr>
                        <td>{{ trim(($r->student->firstname ?? '') . ' ' . ($r->student->lastname ?? '')) }}
                            @if($r->student->admissionNo ?? null)<div class="small text-muted">{{ $r->student->admissionNo }}</div>@endif</td>
                        <td class="small">{{ $classNames[$r->class_id] ?? '—' }}</td>
                        <td>{{ $r->reasonLabel() }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($r->reason, 40) }}</div></td>
                        <td class="small">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M Y') }}</td>
                        <td class="text-end">{{ $r->days }}</td>
                        <td class="small text-capitalize">{{ $r->requester_type }}</td>
                        <td><span class="status-pill {{ $c }}">{{ $l }}</span></td>
                        <td class="text-end">@if($r->attachment)<a href="{{ route('student-leave.attachment', $r) }}" class="action-btn btn-open" title="Document"><i class="ri-attachment-2"></i></a>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>

    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div>
</div>
</div>
@endsection
