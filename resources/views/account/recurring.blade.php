@extends('layouts.master')

@section('content')
@php $R = \App\Modules\Recurring\RecurringDeliveryService::class; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Recurring deliveries" icon="ri-repeat-line" subtitle="The same trip on a schedule. We book each one the day before at the instant price and pay it from your plan or wallet." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="row g-3">
        <div class="col-xl-7">
            @forelse($rows as $r)
                <x-cb.card :title="$r['name']" icon="ri-repeat-line" class="mb-3">
                    <x-slot:tools>
                        <span class="badge {{ $r['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ ucfirst($r['status']) }}</span>
                    </x-slot:tools>
                    <div class="small mb-1"><strong>{{ $r['when'] }}</strong>@if($r['ends_on']) · until {{ \Illuminate\Support\Carbon::parse($r['ends_on'])->format('j M Y') }}@endif</div>
                    <div class="small text-muted">From {{ $r['dropoff_centre'] ? 'a partner store' : ($r['pickup']['line1'] ?? '') }} to {{ $r['dropoff']['line1'] ?? '' }} ({{ $r['dropoff']['contact_name'] ?? '' }})</div>
                    <div class="small text-muted">{{ $r['description'] }} · {{ ucfirst($r['size']) }}{{ $r['fragile'] ? ' · fragile' : '' }}</div>
                    @if($r['next'] && $r['status'] === 'active')<div class="small mt-1">Next pickup: {{ \Illuminate\Support\Carbon::parse($r['next'])->setTimezone(config('app.timezone'))->format('D j M, g:i a') }}</div>@endif
                    @if(count($r['runs']))
                        <table class="table table-sm small mt-2 mb-2">
                            @foreach($r['runs'] as $x)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($x['pickup_at'])->setTimezone(config('app.timezone'))->format('D j M, g:i a') }}</td>
                                    <td>
                                        @if($x['status'] === 'paid')<span class="text-success">Booked</span>
                                        @elseif($x['status'] === 'needs_payment')<a href="{{ route('account.pay', $x['agreement']) }}" class="text-warning fw-semibold">Needs payment: pay now</a>
                                        @elseif($x['status'] === 'skipped')<span class="text-muted">Skipped</span>
                                        @else<span class="text-danger">Not booked</span>@endif
                                        @if($x['message'])<div class="text-muted">{{ $x['message'] }}</div>@endif
                                    </td>
                                    <td class="text-end">{{ $x['amount'] !== null ? Money::format($x['amount'], true) : '' }}@if($x['shipment']) · <a href="{{ route('account.order', $x['shipment']) }}">Track</a>@endif</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                    <div class="d-flex gap-2">
                        @if($r['status'] === 'active')
                            <form method="POST" action="{{ route('account.recurring.status', $r['id']) }}">@csrf<input type="hidden" name="status" value="paused"><button class="btn btn-sm btn-outline-secondary">Pause</button></form>
                        @else
                            <form method="POST" action="{{ route('account.recurring.status', $r['id']) }}">@csrf<input type="hidden" name="status" value="active"><button class="btn btn-sm btn-outline-success">Start again</button></form>
                        @endif
                        <form method="POST" action="{{ route('account.recurring.status', $r['id']) }}" onsubmit="return confirm('Delete this schedule? Trips already booked are kept.')">@csrf<input type="hidden" name="status" value="ended"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
                    </div>
                </x-cb.card>
            @empty
                <div class="text-muted mb-3">No schedules yet. Set one up on the right.</div>
            @endforelse
        </div>

        <div class="col-xl-5">
            <x-cb.card title="New schedule" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('account.recurring.store') }}" id="rform">@csrf
                    <label class="form-label small mb-0">Name</label>
                    <input name="name" class="form-control form-control-sm mb-2" required maxlength="80" placeholder="e.g. Daily bank run" value="{{ old('name') }}">

                    @if(count($options['centres'] ?? []))
                    <div class="mb-2 small">
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="from" value="address" id="fA" @checked(old('from', 'address') === 'address')><label class="form-check-label" for="fA">Pick up from an address</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="from" value="store" id="fS" @checked(old('from') === 'store')><label class="form-check-label" for="fS">I drop it at a store</label></div>
                    </div>
                    <div id="storeBox" class="mb-2"><select name="dropoff_centre" class="form-select form-select-sm">@foreach($options['centres'] as $c)<option value="{{ $c['id'] }}" @selected(old('dropoff_centre') === $c['id'])>{{ $c['name'] }} · {{ $c['address'] }}</option>@endforeach</select></div>
                    @else
                        <input type="hidden" name="from" value="address">
                    @endif

                    <div id="pickBox">
                        <label class="form-label small mb-0">Pickup address</label>
                        <div class="position-relative mb-1"><input id="pAddr" name="pickup_line1" class="form-control form-control-sm" autocomplete="off" value="{{ old('pickup_line1') }}" placeholder="Start typing"><div class="list-group position-absolute w-100 shadow-sm" id="pSug" style="z-index:20; display:none"></div></div>
                        <input type="hidden" name="pickup_lat" id="pLat" value="{{ old('pickup_lat') }}"><input type="hidden" name="pickup_lng" id="pLng" value="{{ old('pickup_lng') }}">
                        <div class="row g-1 mb-1"><div class="col-6"><input name="pickup_name" class="form-control form-control-sm" placeholder="Contact name" value="{{ old('pickup_name', $me->name) }}"></div><div class="col-6"><input name="pickup_phone" class="form-control form-control-sm" placeholder="Phone" value="{{ old('pickup_phone', $me->phone_number) }}"></div></div>
                        <input name="pickup_instructions" class="form-control form-control-sm mb-2" maxlength="300" placeholder="Pickup notes (optional)" value="{{ old('pickup_instructions') }}">
                    </div>

                    <label class="form-label small mb-0">Drop-off address</label>
                    <div class="position-relative mb-1"><input id="dAddr" name="dropoff_line1" class="form-control form-control-sm" autocomplete="off" required value="{{ old('dropoff_line1') }}" placeholder="Start typing"><div class="list-group position-absolute w-100 shadow-sm" id="dSug" style="z-index:20; display:none"></div></div>
                    <input type="hidden" name="dropoff_lat" id="dLat" value="{{ old('dropoff_lat') }}"><input type="hidden" name="dropoff_lng" id="dLng" value="{{ old('dropoff_lng') }}">
                    <div class="row g-1 mb-1"><div class="col-6"><input name="dropoff_name" class="form-control form-control-sm" required placeholder="Recipient name" value="{{ old('dropoff_name') }}"></div><div class="col-6"><input name="dropoff_phone" class="form-control form-control-sm" required placeholder="Recipient phone" value="{{ old('dropoff_phone') }}"></div></div>
                    <input name="dropoff_instructions" class="form-control form-control-sm mb-2" maxlength="300" placeholder="Drop-off notes (optional)" value="{{ old('dropoff_instructions') }}">

                    <div class="row g-1 mb-2">
                        <div class="col-7"><input name="description" class="form-control form-control-sm" required maxlength="200" placeholder="What is it? e.g. deposit bag" value="{{ old('description') }}"></div>
                        <div class="col-5"><select name="size" class="form-select form-select-sm">@foreach($options['sizes'] as $s)<option value="{{ $s['code'] }}" @selected(old('size') === $s['code'])>{{ $s['label'] }}</option>@endforeach</select></div>
                        @if(count($options['vehicles']))<div class="col-7"><select name="vehicle" class="form-select form-select-sm">@foreach($options['vehicles'] as $v)<option value="{{ $v['code'] }}" @selected(old('vehicle') === $v['code'])>{{ $v['name'] }}</option>@endforeach</select></div>@endif
                        <div class="col-5 d-flex align-items-center"><div class="form-check small"><input class="form-check-input" type="checkbox" name="fragile" value="1" id="fr" @checked(old('fragile'))><label class="form-check-label" for="fr">Fragile</label></div></div>
                    </div>

                    <label class="form-label small mb-0">When</label>
                    <select name="pattern" id="pat" class="form-select form-select-sm mb-1">@foreach($patterns as $k => $v)<option value="{{ $k }}" @selected(old('pattern', 'weekdays') === $k)>{{ $v }}</option>@endforeach</select>
                    <div id="daysBox" class="mb-1">@foreach($days as $n => $label)<div class="form-check form-check-inline small"><input class="form-check-input" type="checkbox" name="days[]" value="{{ $n }}" id="d{{ $n }}" @checked(in_array($n, array_map('intval', old('days', [])), true))><label class="form-check-label" for="d{{ $n }}">{{ $label }}</label></div>@endforeach</div>
                    <div class="row g-1 mb-3">
                        <div class="col-4"><label class="form-label small mb-0">Pickup time</label><input type="time" name="time" class="form-control form-control-sm" required value="{{ old('time', '10:00') }}"></div>
                        <div class="col-4"><label class="form-label small mb-0">Starts</label><input type="date" name="starts_on" class="form-control form-control-sm" required value="{{ old('starts_on', now(config('app.timezone'))->addDay()->toDateString()) }}"></div>
                        <div class="col-4"><label class="form-label small mb-0">Ends (optional)</label><input type="date" name="ends_on" class="form-control form-control-sm" value="{{ old('ends_on') }}"></div>
                    </div>
                    <button class="btn btn-primary w-100">Save schedule</button>
                    <div class="small text-muted mt-2">Each trip is priced when we book it, the day before. Keep enough in your wallet, or use a business plan.</div>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
<script>
(function () {
    var COUNTRY = @json(strtolower((string) config('region.country')));
    function $(id) { return document.getElementById(id); }
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
    function search(inputId, boxId, latId, lngId) {
        var input = $(inputId), box = $(boxId), timer, items = [];
        input.addEventListener('input', function () {
            $(latId).value = ''; $(lngId).value = '';
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 4) { box.style.display = 'none'; return; }
            timer = setTimeout(function () {
                fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&countrycodes=' + COUNTRY + '&q=' + encodeURIComponent(q), {headers: {'Accept': 'application/json'}})
                    .then(function (r) { return r.json(); }).then(function (rows) {
                        items = rows || [];
                        box.innerHTML = items.map(function (r, i) { return '<button type="button" class="list-group-item list-group-item-action small" data-i="' + i + '">' + esc(r.display_name) + '</button>'; }).join('');
                        box.style.display = items.length ? 'block' : 'none';
                    }).catch(function () { box.style.display = 'none'; });
            }, 450);
        });
        box.addEventListener('click', function (e) {
            var i = e.target.getAttribute('data-i'); if (i === null) return;
            var r = items[+i];
            input.value = r.display_name; $(latId).value = r.lat; $(lngId).value = r.lon; box.style.display = 'none';
        });
    }
    search('pAddr', 'pSug', 'pLat', 'pLng');
    search('dAddr', 'dSug', 'dLat', 'dLng');
    function sync() {
        var store = $('fS') && $('fS').checked;
        if ($('storeBox')) $('storeBox').style.display = store ? '' : 'none';
        $('pickBox').style.display = store ? 'none' : '';
        $('daysBox').style.display = $('pat').value === 'weekly' ? '' : 'none';
    }
    document.querySelectorAll('input[name="from"]').forEach(function (r) { r.addEventListener('change', sync); });
    $('pat').addEventListener('change', sync);
    $('rform').addEventListener('submit', function (e) {
        var store = $('fS') && $('fS').checked;
        if ((!store && !$('pLat').value) || !$('dLat').value) { e.preventDefault(); alert('Choose each address from the list that appears as you type.'); }
    });
    sync();
})();
</script>
@endsection
