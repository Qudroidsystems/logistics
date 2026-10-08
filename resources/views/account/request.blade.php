@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="ucfirst($req->type).' request'" icon="ri-chat-quote-line" :subtitle="($stops['pickup']['line1'] ?? '').' to '.($stops['dropoff']['line1'] ?? '')" :back="route('account.requests')" back-label="Requests">
        <x-slot:pills><span class="cb-meta-pill">{{ $req->status }}</span>@if($req->distance_m)<span class="cb-meta-pill">{{ number_format($req->distance_m / 1000, 1) }} km</span>@endif</x-slot:pills>
    </x-cb.hero>
    @include('account._flash')
    <x-cb.card title="Offers" icon="ri-price-tag-3-line" :count="count($offers)" :flush="true">
        <table class="table align-middle mb-0"><thead><tr><th>Provider</th><th>Standing</th><th>Status</th><th></th></tr></thead><tbody>
        @forelse($offers as $o)
            <tr><td class="fw-semibold">{{ $o['provider'] }}</td>
                <td class="small">{{ ucfirst($o['tier'] ?? 'verified') }} · {{ $o['rating_avg'] ? number_format($o['rating_avg'], 1).' ★' : 'New' }} · {{ $o['jobs_completed'] ?? 0 }} jobs</td>
                <td><span class="badge bg-light text-dark">{{ $o['status'] }}</span></td>
                <td class="text-end"><a href="{{ route('account.thread', $o['thread']) }}" class="btn btn-sm btn-primary">View offer</a></td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">No offers yet. Providers have been told; this page updates as they reply.</td></tr>
        @endforelse
        </tbody></table>
    </x-cb.card>
</div></div></div>
@endsection
