@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Disputes" icon="ri-scales-3-line" subtitle="Oldest first. Deciding a dispute moves money, so check the evidence." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <x-cb.card title="Disputes" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(['open' => 'Open', 'resolved' => 'Resolved', 'all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select></form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Order</th><th>Provider</th><th>Type</th><th>Status</th><th>Decision</th><th>Opened</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><a href="{{ route('ops.dispute', $r['dispute_id']) }}" class="fw-semibold">{{ $r['order_number'] }}</a></td>
                    <td>{{ $r['provider'] }}</td>
                    <td>{{ str_replace('_', ' ', $r['type']) }}</td>
                    <td><span class="badge bg-{{ in_array($r['status'], ['open', 'evidence']) ? 'danger' : 'secondary' }}">{{ $r['status'] }}</span></td>
                    <td>{{ $r['decision'] ? str_replace('_', ' ', $r['decision']) : '—' }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r['created_at'])->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
