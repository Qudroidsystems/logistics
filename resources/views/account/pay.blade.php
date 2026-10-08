@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); $enough = $balance >= $total; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Pay to start the job" icon="ri-bank-card-line" :subtitle="$a->provider.' · agreement '.$a->number" :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <div class="row justify-content-center"><div class="col-lg-6">
        <x-cb.card title="What you are paying" icon="ri-receipt-line">
            <div class="d-flex justify-content-between"><span>Delivery price</span><span>{{ $naira($a->price) }}</span></div>
            @if($a->goods_budget > 0)<div class="d-flex justify-content-between"><span>Goods budget <span class="text-muted small">(unspent money comes back)</span></span><span>{{ $naira($a->goods_budget) }}</span></div>@endif
            @if($a->tip > 0)<div class="d-flex justify-content-between"><span>Tip</span><span>{{ $naira($a->tip) }}</span></div>@endif
            <hr><div class="d-flex justify-content-between fw-semibold fs-5"><span>Total</span><span>{{ $naira($total) }}</span></div>
            <div class="small text-muted mt-2">Your money is held safely and only released to the provider when you confirm the delivery.</div>
        </x-cb.card>
        <form method="POST" action="{{ route('account.pay.do', $a->public_id) }}" class="mt-3">@csrf
            <button name="method" value="wallet" class="btn btn-success w-100 mb-2" @disabled(! $enough)>Pay {{ $naira($total) }} from my wallet ({{ $naira($balance) }})</button>
            @unless($enough)<div class="small text-muted mb-2">Your wallet is short by {{ $naira($total - $balance) }}. <a href="{{ route('account.wallet') }}">Top it up</a>, or pay by card.</div>@endunless
            <button name="method" value="card" class="btn btn-primary w-100">Pay by card or bank transfer</button>
        </form>
    </div></div>
</div></div></div>
@endsection
