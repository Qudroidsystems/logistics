@if($handover)
<x-cb.card title="Pickup point" icon="ri-store-2-line" class="mb-3">
    <div class="small"><strong>{{ $handover['point']->name }}</strong> ({{ $handover['point']->code }}), {{ $handover['point']->line1 }}</div>
    @if($handover['waiting'])
        <div class="small mt-1">Waiting since {{ \Illuminate\Support\Carbon::parse($handover['held_at'])->format('j M H:i') }}, kept until {{ \Illuminate\Support\Carbon::parse($handover['hold_until'])->format('j M') }}.@if($handover['overdue']) <span class="text-danger">Past its collection time.</span>@endif</div>
        <form method="POST" action="{{ route('provider.points.collect', $s->public_id) }}" class="d-flex gap-1 mt-2">@csrf
            <input name="code" class="form-control form-control-sm" placeholder="Receiver's code" inputmode="numeric" maxlength="12" required>
            <button class="btn btn-sm btn-success">Hand over</button>
        </form>
        @if($handover['overdue'])
            <form method="POST" action="{{ route('provider.points.not_collected', $s->public_id) }}" class="mt-1" onsubmit="return confirm('Close this delivery as not collected? The failed-delivery terms in the agreement apply.')">@csrf
                <button class="btn btn-sm btn-link text-danger p-0">Nobody came: close as not collected</button>
            </form>
        @endif
    @else
        <div class="small text-muted mt-1">The rider leaves the parcel here; the receiver collects it with their code.</div>
    @endif
</x-cb.card>
@endif

@if($returnInfo['parent'])
<x-cb.card title="This is a return" icon="ri-arrow-go-back-line" class="mb-3">
    <div class="small">Bringing back order <a href="{{ route('provider.job', $returnInfo['parent']->public_id) }}">{{ $returnInfo['parent']->order_number }}</a>@if($returnInfo['reason']): {{ strtolower($returnInfo['reason']) }}@endif.</div>
</x-cb.card>
@elseif($returnInfo['return'])
<x-cb.card title="Return" icon="ri-arrow-go-back-line" class="mb-3">
    <div class="small">Return booked as order <a href="{{ route('provider.job', $returnInfo['return']->public_id) }}">{{ $returnInfo['return']->order_number }}</a> ({{ str_replace('_', ' ', $returnInfo['return']->status) }}).</div>
</x-cb.card>
@elseif(! $returnInfo['blocked'])
<x-cb.card title="Book a return" icon="ri-arrow-go-back-line" class="mb-3">
    <p class="small text-muted">A new job that collects the parcel from where it is now and brings it back to the pickup address, or to one of your pickup points. It is priced and paid like any booking.</p>
    <form method="POST" action="{{ route('provider.job.return', $s->public_id) }}">@csrf
        <select name="reason" class="form-select form-select-sm mb-1" required>
            <option value="">Why is it coming back?</option>
            @foreach(\App\Modules\Returns\ReturnService::REASONS as $k => $v)<option value="{{ $k }}" @selected(old('reason', $s->status === 'failed_attempt' ? 'not_collected' : null) === $k)>{{ $v }}</option>@endforeach
        </select>
        @if(count($returnPoints))
            <select name="to_point" class="form-select form-select-sm mb-1">
                <option value="">Back to the pickup address</option>
                @foreach($returnPoints as $pp)<option value="{{ $pp->code }}">To pickup point {{ $pp->name }}</option>@endforeach
            </select>
        @endif
        <select name="payment" class="form-select form-select-sm mb-1" required>
            @foreach(\App\Modules\Backoffice\ManualBookingService::PAYMENTS as $k => $v)<option value="{{ $k }}" @selected(old('payment', 'cash') === $k)>{{ $v }}</option>@endforeach
        </select>
        <div class="input-group input-group-sm mb-1"><span class="input-group-text">₦</span><input name="price_naira" class="form-control" inputmode="decimal" placeholder="Price (blank: your price list)" value="{{ old('price_naira') }}"></div>
        <input name="note" class="form-control form-control-sm mb-2" maxlength="300" placeholder="Note for the driver (optional)">
        <button class="btn btn-sm btn-outline-primary w-100">Book the return</button>
    </form>
</x-cb.card>
@endif
