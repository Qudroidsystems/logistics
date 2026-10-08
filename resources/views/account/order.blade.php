@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Order '.$s->order_number" icon="ri-shopping-bag-3-line" :subtitle="$s->provider.' · tracking '.$s->tracking_code" :back="route('account.orders')" back-label="Orders">
        <x-slot:pills><span class="cb-meta-pill">{{ str_replace('_', ' ', $s->status) }}</span><span class="cb-meta-pill">{{ $naira($s->total) }}</span></x-slot:pills>
    </x-cb.hero>
    @include('account._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            @if($canConfirm)
            <x-cb.card title="Delivered. Is everything right?" icon="ri-checkbox-circle-line" class="mb-3">
                <p class="small text-muted">Confirming pays the provider. If something is wrong, report it instead and we hold the money while we look into it.</p>
                <form method="POST" action="{{ route('account.order.confirm', $s->public_id) }}">@csrf
                    <label class="form-label small">Rate the provider <span class="text-muted">(optional)</span></label>
                    <select name="rating" class="form-select mb-2"><option value="">No rating</option>@foreach([5 => '5 - Excellent', 4 => '4 - Good', 3 => '3 - Okay', 2 => '2 - Poor', 1 => '1 - Bad'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                    <input name="comment" class="form-control mb-2" placeholder="Say a few words (optional)" maxlength="500">
                    <button class="btn btn-success w-100">Yes, confirm and pay</button>
                </form>
                <hr>
                <form method="POST" action="{{ route('account.order.object', $s->public_id) }}">@csrf
                    <label class="form-label small">Something is wrong</label>
                    <select name="type" class="form-select mb-2" required>@foreach(['damage' => 'Item damaged', 'lost' => 'Item lost or missing', 'quality' => 'Not what I asked for', 'late' => 'Very late', 'price' => 'Charged wrongly', 'driver_conduct' => 'Rider behaviour', 'fraud' => 'Suspected fraud'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                    <textarea name="reason" rows="3" class="form-control mb-2" placeholder="Tell us what happened (at least 10 characters)" minlength="10" maxlength="1000" required></textarea>
                    <button class="btn btn-outline-danger w-100">Report a problem</button>
                </form>
            </x-cb.card>
            @endif

            @if($canRate)
            <x-cb.card title="Rate this delivery" icon="ri-star-line" class="mb-3">
                <form method="POST" action="{{ route('account.order.rate', $s->public_id) }}">@csrf
                    <select name="rating" class="form-select mb-2" required>@foreach([5 => '5 - Excellent', 4 => '4 - Good', 3 => '3 - Okay', 2 => '2 - Poor', 1 => '1 - Bad'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                    <input name="comment" class="form-control mb-2" placeholder="Say a few words (optional)" maxlength="500">
                    <button class="btn btn-primary w-100">Send rating</button>
                </form>
            </x-cb.card>
            @endif

            <x-cb.card title="Progress" icon="ri-time-line" :flush="true">
                <table class="table mb-0"><tbody>
                @forelse($timeline as $e)<tr><td>{{ ucfirst(str_replace('_', ' ', $e->type)) }}</td><td class="small text-muted text-end">{{ \Illuminate\Support\Carbon::parse($e->created_at)->format('d M H:i') }}</td></tr>
                @empty<tr><td class="text-center text-muted py-3">Waiting to start.</td></tr>@endforelse
                </tbody></table>
            </x-cb.card>
        </div>

        <div class="col-lg-5">
            @if($code)
            <x-cb.card title="Delivery code" icon="ri-key-2-line" class="mb-3">
                <div class="display-6 fw-bold text-center letter-spacing" style="letter-spacing:.3em">{{ $code }}</div>
                <div class="small text-muted text-center">Give this to the person receiving the goods. The rider needs it to finish the delivery.</div>
            </x-cb.card>
            @endif
            @if(!empty($preview['cancellable']))
            <x-cb.card title="Need to cancel?" icon="ri-close-circle-line">
                <p class="small mb-2">Cancelling now costs <strong>{{ $naira($preview['fee'] ?? 0) }}</strong>. The rest is refunded to your wallet.</p>
                <form method="POST" action="{{ route('account.order.cancel', $s->public_id) }}" onsubmit="return confirm('Cancel this order?')">@csrf
                    <select name="reason" class="form-select mb-2" required><option value="">Why?</option>@foreach(['changed_mind' => 'I changed my mind', 'wrong_details' => 'Wrong details', 'too_slow' => 'Taking too long', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                    <button class="btn btn-outline-danger w-100">Cancel order</button>
                </form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
