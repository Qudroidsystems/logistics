@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Requests" icon="ri-inbox-line" subtitle="Customers asking for a quote, and the offers you have made." />
    @include('provider._flash')

    <x-cb.card title="Waiting for your offer" icon="ri-inbox-line" :count="count($waiting)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Type</th><th>From</th><th>To</th><th class="text-end">Distance</th><th class="text-end">Budget</th><th></th></tr></thead>
            <tbody>
            @forelse($waiting as $r)
                <tr>
                    <td>{{ ucfirst($r['type']) }}</td>
                    <td class="small">{{ $r['stops']['pickup']['line1'] ?? '—' }}</td>
                    <td class="small">{{ $r['stops']['dropoff']['line1'] ?? '—' }}</td>
                    <td class="text-end">{{ !empty($r['distance_m']) ? number_format($r['distance_m'] / 1000, 1).' km' : '—' }}</td>
                    <td class="text-end">{{ !empty($r['budget_max']) ? $naira($r['budget_max']) : '—' }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('provider.request', $r['public_id']) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No requests waiting.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    <x-cb.card title="Your offers" icon="ri-chat-quote-line" :count="count($threads)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Type</th><th class="text-end">Distance</th><th>Status</th><th>Last activity</th><th></th></tr></thead>
            <tbody>
            @forelse($threads as $t)
                <tr>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td class="text-end">{{ $t->distance_m ? number_format($t->distance_m / 1000, 1).' km' : '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $t->status }}</span></td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($t->updated_at)->format('d M H:i') }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('provider.thread', $t->thread) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">You have not made any offers yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
