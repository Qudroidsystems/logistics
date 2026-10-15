{{-- Stops in driving order, the chosen pickup time, rush, and the customer's photos and documents.
     Pass either $bookingRequest (a service_requests row) or $bookingShipmentId. --}}
@php
    $bk = isset($bookingRequest) ? $bookingRequest : null;
    $bkStops = $bk ? \App\Modules\Booking\StopList::ordered(json_decode($bk->stops, true) ?? []) : [];
    $bkFiles = $bk ? app(\App\Modules\Booking\RequestAttachments::class)->forRequest((int) $bk->id)
        : app(\App\Modules\Booking\RequestAttachments::class)->forShipment((int) ($bookingShipmentId ?? 0));
    $bkPickupAt = $bk->pickup_at ?? (isset($bookingShipmentId) ? \Illuminate\Support\Facades\DB::table('shipments')->where('id', $bookingShipmentId)->value('pickup_window_start') : null);
@endphp
@if(count($bkStops) > 2 || $bkPickupAt || ($bk->rush ?? false) || count($bkFiles))
<x-cb.card title="Booking details" icon="ri-calendar-schedule-line" class="mb-3">
    @if($bkPickupAt)<div class="mb-2"><i class="ri-time-line"></i> Pickup at <strong>{{ \Illuminate\Support\Carbon::parse($bkPickupAt)->format('D d M Y, H:i') }}</strong></div>@endif
    @if($bk->rush ?? false)<div class="mb-2"><span class="badge bg-danger">Rush</span> The customer asked for this to go as fast as possible.</div>@endif
    @if(count($bkStops) > 2)
        <div class="fw-semibold small mb-1">Route ({{ count($bkStops) }} stops)</div>
        <ol class="mb-2 small">
            @foreach($bkStops as $st)<li><span class="badge {{ $st['type'] === 'pickup' ? 'bg-success' : 'bg-danger' }}">{{ $st['type'] === 'pickup' ? 'Pickup' : 'Drop-off' }}</span> {{ $st['line1'] ?? '' }}{{ !empty($st['contact_name']) ? ' · '.$st['contact_name'] : '' }}</li>@endforeach
        </ol>
    @endif
    @if(count($bkFiles))
        <div class="fw-semibold small mb-1">Photos and documents</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($bkFiles as $f)
                <a href="{{ route('request-files.show', $f['id']) }}" target="_blank" class="border rounded p-2 small text-decoration-none" style="max-width:180px">
                    @if($f['kind'] === 'photo')<img src="{{ route('request-files.show', $f['id']) }}" alt="" style="width:100%;height:90px;object-fit:cover;border-radius:4px">@else<i class="ri-file-text-line fs-3"></i>@endif
                    <div class="text-truncate">{{ $f['caption'] ?: $f['name'] }}</div>
                </a>
            @endforeach
        </div>
    @endif
</x-cb.card>
@endif
