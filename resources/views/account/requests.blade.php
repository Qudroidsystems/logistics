@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="My requests" icon="ri-chat-quote-line" subtitle="Deliveries you have asked providers to price." :back="route('account.dashboard')" back-label="Home">
        <x-slot:actions><a href="{{ route('account.request.new') }}" class="action-btn btn-primary-cb"><i class="ri-add-line"></i>New request</a></x-slot:actions>
    </x-cb.hero>
    @include('account._flash')
    <x-cb.card title="Requests" icon="ri-list-check" :count="count($rows)" :flush="true">
        <table class="table table-hover align-middle mb-0"><thead><tr><th>Request</th><th>Status</th><th>Distance</th><th>Sent</th></tr></thead><tbody>
        @forelse($rows as $r)
            <tr><td><a href="{{ route('account.request', $r['public_id']) }}" class="fw-semibold">{{ ucfirst($r['type']) }}</a><div class="small text-muted">{{ $r['visibility'] === 'direct' ? 'Sent to one provider' : 'Open to providers' }}</div></td>
                <td><span class="badge bg-light text-dark">{{ $r['status'] }}</span></td><td>{{ $r['distance_m'] ? number_format($r['distance_m'] / 1000, 1).' km' : '—' }}</td>
                <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r['created_at'])->diffForHumans() }}</td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">No requests yet.</td></tr>
        @endforelse
        </tbody></table>
    </x-cb.card>
</div></div></div>
@endsection
