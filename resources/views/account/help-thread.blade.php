@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$ticket->subject ?: 'Help request'" icon="ri-customer-service-2-line" :subtitle="$ticket->number.' · '.\App\Modules\Support\SupportService::customerStatus($ticket->status)" :back="route('account.help')" back-label="Help">
        @if($ticket->shipment)<x-slot:pills><span class="cb-meta-pill"><a class="text-reset" href="{{ route('account.order', $ticket->shipment) }}">Order {{ $ticket->order_number }}</a></span></x-slot:pills>@endif
    </x-cb.hero>
    @include('account._flash')
    <div class="row justify-content-center"><div class="col-xl-7 col-lg-9">
        @foreach($messages as $m)
            <div class="d-flex {{ $m['mine'] ? 'justify-content-end' : '' }} mb-2">
                <div class="p-3 rounded-3 {{ $m['mine'] ? 'bg-primary-subtle' : 'bg-light' }}" style="max-width: 85%">
                    <div class="small fw-semibold mb-1">{{ $m['mine'] ? 'You' : 'Support' }} <span class="text-muted fw-normal">· {{ \Illuminate\Support\Carbon::parse($m['created_at'])->diffForHumans() }}</span></div>
                    <div style="white-space: pre-wrap">{{ $m['body'] }}</div>
                </div>
            </div>
        @endforeach
        <form method="POST" action="{{ route('account.help.reply', $ticket->public_id) }}" class="mt-3">@csrf
            <textarea name="body" class="form-control mb-2" rows="3" maxlength="4000" required placeholder="{{ in_array($ticket->status, ['resolved', 'closed'], true) ? 'Still need help? Reply to reopen this request.' : 'Write a reply' }}">{{ old('body') }}</textarea>
            <button class="btn btn-primary">Send reply</button>
        </form>
    </div></div>
</div></div></div>
@endsection
