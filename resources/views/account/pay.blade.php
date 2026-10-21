@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); $enough = $balance >= $total; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Pay to start the job" icon="ri-bank-card-line" :subtitle="$a->provider.' · agreement '.$a->number" :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <div class="row justify-content-center"><div class="col-lg-6">
        <x-cb.card title="What you are paying" icon="ri-receipt-line">
            <div class="d-flex justify-content-between"><span>Delivery price</span><span>{{ $naira($a->price) }}</span></div>
            @if($a->goods_budget > 0)<div class="d-flex justify-content-between"><span>Goods budget <span class="text-muted small">(unspent money comes back)</span></span><span>{{ $naira($a->goods_budget) }}</span></div>@endif
            @if($a->tip > 0)<div class="d-flex justify-content-between"><span>Tip</span><span>{{ $naira($a->tip) }}</span></div>@endif
            @foreach($summary['discount_lines'] ?? [] as $dl)<div class="d-flex justify-content-between text-success"><span>{{ $dl['label'] }}</span><span>-{{ $naira($dl['amount']) }}</span></div>@endforeach
            <hr><div class="d-flex justify-content-between fw-semibold fs-5"><span>Total</span><span>{{ $naira($total) }}</span></div>
            <div class="small text-muted mt-2">Your money is held safely and only released to the provider when you confirm the delivery.</div>
        </x-cb.card>
        @if($a->status === 'locked')
        <x-cb.card title="Promo code" icon="ri-coupon-3-line" class="mt-3">
            @if(!empty($summary['promo_code']))
                <form method="POST" action="{{ route('account.pay.promo.remove', $a->public_id) }}" class="d-flex align-items-center gap-2">@csrf @method('DELETE')
                    <span class="badge bg-success-subtle text-success fs-6">{{ $summary['promo_code'] }}</span><button class="btn btn-sm btn-link text-danger">Remove</button></form>
            @else
                <form method="POST" action="{{ route('account.pay.promo', $a->public_id) }}" class="d-flex gap-2">@csrf
                    <input name="code" class="form-control text-uppercase" maxlength="32" placeholder="Enter a code" required><button class="btn btn-outline-primary">Apply</button></form>
            @endif
        </x-cb.card>
        @if(!empty($summary['points']['enabled']))
        <x-cb.card title="Loyalty points" icon="ri-medal-line" class="mt-3">
            @php $pts = $summary['points']; @endphp
            @if($pts['balance'] >= $pts['min_redeem'] && $pts['balance'] > 0)
                <form method="POST" action="{{ route('account.pay.points', $a->public_id) }}" class="d-flex align-items-center justify-content-between gap-2">@csrf
                    <span>You have {{ number_format($pts['balance']) }} points (worth {{ $naira($pts['balance'] * $pts['point_value']) }}).</span>
                    <input type="hidden" name="use" value="{{ $summary['use_points'] ? 0 : 1 }}"><button class="btn btn-sm {{ $summary['use_points'] ? 'btn-outline-secondary' : 'btn-outline-primary' }}">{{ $summary['use_points'] ? 'Stop using' : 'Use points' }}</button></form>
            @else
                <div class="small text-muted">You have {{ number_format($pts['balance']) }} points. You can spend them once you have {{ number_format($pts['min_redeem']) }}.</div>
            @endif
        </x-cb.card>
        @endif
        <x-cb.card title="Gift card" icon="ri-gift-line" class="mt-3">
            @if(!empty($summary['gift_card']))
                <form method="POST" action="{{ route('account.pay.giftcard.remove', $a->public_id) }}" class="d-flex align-items-center gap-2">@csrf @method('DELETE')
                    <span class="badge bg-success-subtle text-success fs-6">Ending {{ $summary['gift_card']->last4 }}</span><span class="small text-muted">{{ $naira($summary['gift_card']->balance) }} on the card</span><button class="btn btn-sm btn-link text-danger">Remove</button></form>
            @else
                <form method="POST" action="{{ route('account.pay.giftcard', $a->public_id) }}" class="d-flex gap-2">@csrf
                    <input name="code" class="form-control text-uppercase" maxlength="40" placeholder="GC12-3456-7890" required><button class="btn btn-outline-primary">Apply</button></form>
            @endif
        </x-cb.card>
        @endif
        <x-cb.card title="If the delivery fails" icon="ri-file-list-3-line" class="mt-3">
            @foreach(\App\Modules\Marketplace\FailedDeliveryPolicy::describe($a->failed_delivery_policy ? json_decode($a->failed_delivery_policy, true) : null) as $line)<p class="small mb-2">{{ $line }}</p>@endforeach
            <div class="small text-muted">These terms are part of your agreement and were fixed when it was created.</div>
        </x-cb.card>
        <form method="POST" action="{{ route('account.pay.do', $a->public_id) }}" class="mt-3">@csrf
            <button name="method" value="wallet" class="btn btn-success w-100 mb-2" @disabled(! $enough)>Pay {{ $naira($total) }} from my wallet ({{ $naira($balance) }})</button>
            @unless($enough)<div class="small text-muted mb-2">Your wallet is short by {{ $naira($total - $balance) }}. <a href="{{ route('account.wallet') }}">Top it up</a>, or pay by card.</div>@endunless
            <button name="method" value="card" class="btn btn-primary w-100">Pay by card or bank transfer</button>
        </form>
    </div></div>
</div></div></div>
@endsection
