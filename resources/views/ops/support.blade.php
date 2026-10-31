@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Support" icon="ri-customer-service-2-line" subtitle="Customers' help requests from the website and the app. High-priority requests (late, damaged, missing, payment) should get a first reply within an hour, others within four." :back="route('ops.dashboard')" back-label="Operations">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ $counts['open'] }} open</span>
            <span class="cb-meta-pill {{ $counts['overdue'] ? 'text-danger' : '' }}">{{ $counts['overdue'] }} past first-reply time</span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        @foreach(['open' => 'Open', 'mine' => 'Mine', 'unassigned' => 'Unassigned', 'waiting' => 'Waiting for customer', 'resolved' => 'Resolved', 'all' => 'All'] as $k => $v)
            <a href="{{ route('ops.support', ['view' => $k]) }}" class="btn btn-sm {{ $view === $k ? 'btn-primary' : 'btn-light' }}">{{ $v }}@isset($counts[$k]) <span class="badge bg-white text-dark ms-1">{{ $counts[$k] }}</span>@endisset</a>
        @endforeach
        <form method="GET" action="{{ route('ops.support') }}" class="ms-auto d-flex gap-1"><input type="hidden" name="view" value="all">
            <input name="q" class="form-control form-control-sm" placeholder="Number, customer, email" value="{{ $q }}"><button class="btn btn-sm btn-outline-secondary">Search</button></form>
    </div>
    <x-cb.card title="Requests" icon="ri-inbox-line" :count="count($rows)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Request</th><th>Customer</th><th>Status</th><th>Assigned</th><th>Last message</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                @php $late = in_array($r->status, ['new', 'open'], true) && ! $r->first_responded_at && $r->sla_first_response_due && \Illuminate\Support\Carbon::parse($r->sla_first_response_due)->isPast(); @endphp
                <tr>
                    <td><a href="{{ route('ops.support.show', $r->public_id) }}" class="fw-semibold">{{ $r->subject }}</a>
                        @if($r->staff_unread)<span class="badge bg-primary ms-1">Unread</span>@endif
                        @if($r->priority === 'high')<span class="badge bg-danger-subtle text-danger ms-1">High</span>@endif
                        <div class="small text-muted">{{ $r->number }} · {{ $r->channel }}@if($late) · <span class="text-danger">first reply overdue</span>@endif</div></td>
                    <td>{{ $r->customer }}</td>
                    <td class="small">{{ $statuses[$r->status] ?? $r->status }}</td>
                    <td class="small">{{ $r->assignee ?: '-' }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r->last_message_at ?? $r->created_at)->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
