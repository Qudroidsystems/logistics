@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$ticket->subject ?: 'Help request'" icon="ri-customer-service-2-line" :subtitle="$ticket->number.' · '.($statuses[$ticket->status] ?? $ticket->status).' · '.ucfirst($ticket->priority).' priority'" :back="route('ops.support')" back-label="Support" />
    @include('ops._flash')
    <div class="row g-3">
        <div class="col-xl-8">
            @foreach($messages as $m)
                <div class="d-flex {{ $m['mine'] ? '' : 'justify-content-end' }} mb-2">
                    <div class="p-3 rounded-3 {{ $m['internal'] ? 'bg-warning-subtle' : ($m['mine'] ? 'bg-light' : 'bg-primary-subtle') }}" style="max-width: 85%">
                        <div class="small fw-semibold mb-1">{{ $m['mine'] ? $ticket->customer : ($m['sender'] ?: 'Staff') }}@if($m['internal']) <span class="badge bg-warning text-dark">Internal note</span>@endif
                            <span class="text-muted fw-normal">· {{ \Illuminate\Support\Carbon::parse($m['created_at'])->format('j M, H:i') }}</span></div>
                        <div style="white-space: pre-wrap">{{ $m['body'] }}</div>
                    </div>
                </div>
            @endforeach
            @if($canReply)
            <form method="POST" action="{{ route('ops.support.reply', $ticket->public_id) }}" class="mt-3">@csrf
                <textarea name="body" class="form-control mb-2" rows="4" maxlength="4000" required placeholder="Reply to {{ $ticket->customer }}">{{ old('body') }}</textarea>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select name="status" class="form-select form-select-sm" style="max-width: 240px">
                        <option value="pending_customer">Then: waiting for customer</option>
                        <option value="open">Then: keep open</option>
                        <option value="resolved">Then: resolved</option>
                    </select>
                    <div class="form-check"><input type="hidden" name="internal" value="0"><input class="form-check-input" type="checkbox" name="internal" value="1" id="int"><label class="form-check-label small" for="int">Internal note (customer won't see it)</label></div>
                    <button class="btn btn-primary ms-auto">Send</button>
                </div>
            </form>
            @endif
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Customer" icon="ri-user-line" class="mb-3">
                <div class="fw-semibold">{{ $ticket->customer }}</div>
                <div class="small">{{ $ticket->customer_email }}</div>
                @if($ticket->customer_phone)<div class="small">{{ \App\Support\Phone::display($ticket->customer_phone) }}</div>@endif
                @if($ticket->order_number)
                    <hr><div class="small">Order <a href="{{ route('ops.orders', ['q' => $ticket->order_number]) }}">{{ $ticket->order_number }}</a> · {{ str_replace('_', ' ', $ticket->shipment_status) }}</div>
                    <div class="small text-muted">Tracking {{ $ticket->tracking_code }}</div>
                @endif
            </x-cb.card>
            <x-cb.card title="Handle" icon="ri-settings-4-line" class="mb-3">
                <div class="small mb-2">Assigned to: <strong>{{ $ticket->assignee ?: 'nobody' }}</strong></div>
                <form method="POST" action="{{ route('ops.support.assign', $ticket->public_id) }}" class="d-flex gap-2 mb-2">@csrf
                    <button name="to" value="me" class="btn btn-sm btn-outline-primary">Assign to me</button>
                    @if($ticket->assignee_id)<button name="to" value="nobody" class="btn btn-sm btn-outline-secondary">Unassign</button>@endif
                </form>
                @if($canClose)
                <form method="POST" action="{{ route('ops.support.status', $ticket->public_id) }}" class="d-flex gap-2">@csrf
                    <select name="status" class="form-select form-select-sm">@foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($ticket->status === $k)>{{ $v }}</option>@endforeach</select>
                    <button class="btn btn-sm btn-outline-secondary">Set</button>
                </form>
                @endif
                <div class="small text-muted mt-2">Opened {{ \Illuminate\Support\Carbon::parse($ticket->created_at)->diffForHumans() }} via {{ $ticket->channel }} · {{ $categories[$ticket->category] ?? $ticket->category }}</div>
            </x-cb.card>
            @if(count($others))
            <x-cb.card title="Other requests from this customer" icon="ri-history-line" :flush="true">
                <div class="list-group list-group-flush">@foreach($others as $o)<a class="list-group-item list-group-item-action small" href="{{ route('ops.support.show', $o->public_id) }}">{{ $o->number }} · {{ $o->subject }} <span class="text-muted">({{ $statuses[$o->status] ?? $o->status }})</span></a>@endforeach</div>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
