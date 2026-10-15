@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="ucfirst($req->type).' request'" icon="ri-chat-quote-line" :subtitle="($stops['pickup']['line1'] ?? '').' to '.($stops['dropoff']['line1'] ?? '')" :back="route('account.requests')" back-label="Requests">
        <x-slot:pills><span class="cb-meta-pill">{{ $req->status }}</span>@if($req->distance_m)<span class="cb-meta-pill">{{ number_format($req->distance_m / 1000, 1) }} km</span>@endif</x-slot:pills>
    </x-cb.hero>
    @include('account._flash')
    @include('partials.booking-details', ['bookingRequest' => $req])
    @if(in_array($req->status, ['open', 'negotiating', 'agreed'], true))
    <x-cb.card title="Photos and documents" icon="ri-attachment-2" class="mb-3">
        <p class="small text-muted mb-2">Add a photo of the item, a waybill or an invoice so providers can price it properly. Up to {{ \App\Modules\Booking\RequestAttachments::MAX_FILES }} files, 10 MB each.</p>
        <form method="POST" action="{{ route('account.request.file', $req->public_id) }}" enctype="multipart/form-data" class="row g-2">@csrf
            <div class="col-md-5"><input type="file" name="file" class="form-control" required accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"></div>
            <div class="col-md-5"><input name="caption" class="form-control" maxlength="200" placeholder="What is it? (optional)"></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Add</button></div>
        </form>
        @php $myFiles = app(\App\Modules\Booking\RequestAttachments::class)->forRequest((int) $req->id); @endphp
        @if(count($myFiles) && in_array($req->status, ['open', 'negotiating'], true))
        <ul class="list-unstyled small mt-2 mb-0">@foreach($myFiles as $f)
            <li class="d-flex align-items-center gap-2 mb-1"><a href="{{ route('request-files.show', $f['id']) }}" target="_blank">{{ $f['caption'] ?: $f['name'] }}</a>
                <form method="POST" action="{{ route('account.file.delete', $f['id']) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0">Remove</button></form></li>
        @endforeach</ul>
        @endif
    </x-cb.card>
    @endif
    <x-cb.card title="Offers" icon="ri-price-tag-3-line" :count="count($offers)" :flush="true">
        <table class="table align-middle mb-0"><thead><tr><th>Provider</th><th>Standing</th><th>Status</th><th></th></tr></thead><tbody>
        @forelse($offers as $o)
            <tr><td class="fw-semibold">{{ $o['provider'] }}
                    @if(!empty($o['proofs']['dropoff']))<div class="small text-muted fw-normal"><i class="ri-shield-check-line"></i> Hand-over: {{ implode(', ', $o['proofs']['dropoff']) }}</div>@endif</td>
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
