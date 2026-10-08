@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="ucfirst($req->type).' request'" icon="ri-inbox-line" :subtitle="($stops['pickup']['line1'] ?? '').' to '.($stops['dropoff']['line1'] ?? '')" :back="route('provider.requests')" back-label="Requests">
        <x-slot:pills><span class="cb-meta-pill">{{ $req->status }}</span>@if($req->distance_m)<span class="cb-meta-pill">{{ number_format($req->distance_m / 1000, 1) }} km</span>@endif</x-slot:pills>
    </x-cb.hero>
    @include('provider._flash')
    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="The job" icon="ri-route-line">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Pickup</dt><dd class="col-sm-8">{{ $stops['pickup']['line1'] ?? '—' }}</dd>
                    <dt class="col-sm-4">Drop-off</dt><dd class="col-sm-8">{{ $stops['dropoff']['line1'] ?? '—' }}</dd>
                    @if($req->needed_by)<dt class="col-sm-4">Needed by</dt><dd class="col-sm-8">{{ \Illuminate\Support\Carbon::parse($req->needed_by)->format('d M Y H:i') }}</dd>@endif
                    @if($req->budget_min || $req->budget_max)<dt class="col-sm-4">Budget</dt><dd class="col-sm-8">{{ $req->budget_min ? $naira($req->budget_min).' to ' : 'up to ' }}{{ $naira($req->budget_max) }}</dd>@endif
                </dl>
                @if(count($packages))
                <hr>
                <div class="fw-semibold mb-1">{{ $req->type === 'shopping' ? 'Shopping list' : 'Items' }}</div>
                <ul class="mb-0">@foreach($packages as $p)<li>{{ is_array($p) ? ($p['description'] ?? json_encode($p)) : $p }}</li>@endforeach</ul>
                @endif
            </x-cb.card>
        </div>
        <div class="col-lg-5">
            @if($thread)
                <x-cb.card title="Your offer" icon="ri-chat-quote-line">
                    <p class="text-muted">You have already replied to this request.</p>
                    <a class="btn btn-primary w-100" href="{{ route('provider.thread', $thread) }}">Open the conversation</a>
                </x-cb.card>
            @elseif($canOffer)
                <x-cb.card title="Make an offer" icon="ri-price-tag-3-line">
                    <form method="POST" action="{{ route('provider.request.offer', $req->public_id) }}">@csrf
                        <label class="form-label small">Your price (₦)</label><input type="number" step="0.01" min="1" name="price_naira" class="form-control mb-2" value="{{ old('price_naira') }}" required>
                        @if(in_array($req->type, ['shopping', 'errand'], true))
                        <label class="form-label small">Goods budget the customer should fund (₦)</label><input type="number" step="0.01" min="0" name="goods_budget_naira" class="form-control mb-2" value="{{ old('goods_budget_naira') }}">
                        @endif
                        <label class="form-label small">Tip (₦, optional)</label><input type="number" step="0.01" min="0" name="tip_naira" class="form-control mb-2" value="{{ old('tip_naira') }}">
                        <input name="note" class="form-control mb-2" placeholder="Terms or note (optional)" maxlength="500" value="{{ old('note') }}">
                        <input name="message" class="form-control mb-3" placeholder="Message to the customer (optional)" maxlength="1000" value="{{ old('message') }}">
                        <button class="btn btn-primary w-100">Send offer</button>
                    </form>
                </x-cb.card>
            @else
                <x-cb.card title="Closed" icon="ri-lock-line"><span class="text-muted">This request is no longer taking offers.</span></x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
