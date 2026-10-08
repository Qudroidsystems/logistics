@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$t->provider" icon="ri-chat-quote-line" :subtitle="ucfirst($t->type).' request'" :back="route('account.request', $t->request)" back-label="Request">
        <x-slot:pills><span class="cb-meta-pill">{{ $t->status }}</span></x-slot:pills>
    </x-cb.hero>
    @include('account._flash')
    <div class="row g-3">
        <div class="col-lg-8">
            @include('negotiation._messages', ['messages' => $messages, 'me' => $me, 'naira' => $naira])
            @if($t->status === 'open')
            <form method="POST" action="{{ route('account.thread.say', $t->public_id) }}" class="d-flex gap-2 mt-3">@csrf
                <input name="text" class="form-control" placeholder="Write a message" maxlength="2000" required><button class="btn btn-outline-primary">Send</button>
            </form>
            @endif
        </div>
        <div class="col-lg-4">
            @if($latest)
            <x-cb.card title="Latest offer" icon="ri-price-tag-3-line">
                <div class="fs-3 fw-semibold">{{ $naira($latest['offer']['price'] ?? 0) }}</div>
                @if(!empty($latest['offer']['tip']))<div class="small text-muted">plus {{ $naira($latest['offer']['tip']) }} tip</div>@endif
                @if(!empty($latest['offer']['goods_budget']))<div class="small text-muted">goods budget {{ $naira($latest['offer']['goods_budget']) }}</div>@endif
                @if($canAccept)
                <form method="POST" action="{{ route('account.thread.accept', $t->public_id) }}" class="mt-3">@csrf<input type="hidden" name="offer_id" value="{{ $latest['id'] }}"><button class="btn btn-success w-100">Accept this offer</button></form>
                @endif
            </x-cb.card>
            @endif
            @if($t->status === 'open')
            <x-cb.card title="Counter-offer" icon="ri-exchange-line" class="mt-3">
                <form method="POST" action="{{ route('account.thread.counter', $t->public_id) }}">@csrf
                    <label class="form-label small">Your price (₦)</label><input type="number" step="0.01" min="1" name="price_naira" class="form-control mb-2" required>
                    <input name="message" class="form-control mb-2" placeholder="Add a note (optional)" maxlength="500">
                    <button class="btn btn-outline-primary w-100">Send counter-offer</button>
                </form>
                <form method="POST" action="{{ route('account.thread.reject', $t->public_id) }}" class="mt-2" onsubmit="return confirm('Decline this provider?')">@csrf<button class="btn btn-link text-danger w-100">Decline this provider</button></form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
