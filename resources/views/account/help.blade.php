@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Help" icon="ri-customer-service-2-line" subtitle="Ask us anything about a delivery, a payment or your account. We answer here and send you a notification." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <div class="row g-3">
        <div class="col-lg-5 order-lg-2">
            <x-cb.card title="Ask for help" icon="ri-question-answer-line">
                <form method="POST" action="{{ route('account.help.store') }}">@csrf
                    <label class="form-label small mb-0" for="cat">What is it about?</label>
                    <select id="cat" name="category" class="form-select mb-2" required>
                        @foreach($categories as $k => $v)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $v }}</option>@endforeach
                    </select>
                    <label class="form-label small mb-0" for="ord">Which order? (optional)</label>
                    <select id="ord" name="order" class="form-select mb-2">
                        <option value="">Not about an order</option>
                        @foreach($orders as $o)<option value="{{ $o->public_id }}" @selected(old('order', $order) === $o->public_id)>{{ $o->order_number }} · {{ str_replace('_', ' ', $o->status) }} · {{ \Illuminate\Support\Carbon::parse($o->created_at)->format('j M') }}</option>@endforeach
                    </select>
                    <label class="form-label small mb-0" for="msg">Tell us what happened</label>
                    <textarea id="msg" name="body" class="form-control mb-2" rows="5" maxlength="4000" required>{{ old('body') }}</textarea>
                    <button class="btn btn-primary w-100">Send</button>
                </form>
            </x-cb.card>
        </div>
        <div class="col-lg-7 order-lg-1">
            <x-cb.card title="Your requests" icon="ri-chat-history-line" :count="count($tickets)" :flush="true">
                <div class="list-group list-group-flush">
                    @forelse($tickets as $t)
                        <a href="{{ route('account.help.show', $t['public_id']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                            <div><div class="fw-semibold">{{ $t['subject'] }} @if($t['customer_unread'])<span class="badge bg-primary ms-1">New reply</span>@endif</div>
                                <div class="small text-muted">{{ $t['number'] }} · {{ $t['status_label'] }}</div></div>
                            <span class="small text-muted text-nowrap">{{ \Illuminate\Support\Carbon::parse($t['last_message_at'] ?? $t['created_at'])->diffForHumans() }}</span>
                        </a>
                    @empty
                        <div class="list-group-item text-muted small py-4 text-center">No requests yet.</div>
                    @endforelse
                </div>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
