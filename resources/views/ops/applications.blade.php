@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Provider applications" icon="ri-shield-user-line" subtitle="Companies, riders and shoppers asking to join. Oldest first." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <x-cb.card title="Applications" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(['submitted' => 'Waiting for review', 'needs_changes' => 'Sent back', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select></form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Applicant</th><th>Type</th><th>Status</th><th>Submitted</th><th>Tries</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><a href="{{ route('ops.application', $r['operator_id']) }}" class="fw-semibold">{{ $r['display_name'] }}</a><div class="small text-muted">{{ $r['legal_name'] }}</div></td>
                    <td>{{ str_replace('_', ' ', $r['type']) }}</td>
                    <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $r['status']) }}</span></td>
                    <td class="small text-muted">{{ $r['submitted_at'] ? \Illuminate\Support\Carbon::parse($r['submitted_at'])->diffForHumans() : '—' }}</td>
                    <td>{{ $r['submissions'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Nothing waiting.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
