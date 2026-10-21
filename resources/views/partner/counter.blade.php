@extends('public._layout', ['title' => 'Counter · '.$centre->name])
@section('content')
<div class="container py-4" style="max-width: 1000px">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <div class="text-muted small">Drop-off counter · {{ $centre->code }}</div>
            <h1 class="h3 fw-bold mb-0">{{ $centre->name }}</h1>
            <div class="small text-muted">{{ $centre->line1 }}</div>
        </div>
        @if(count($centres) > 1)
            <form method="get" action="{{ route('counter') }}"><select name="centre" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($centres as $c)<option value="{{ $c->public_id }}" @selected($c->public_id === $centre->public_id)>{{ $c->name }}</option>@endforeach
            </select></form>
        @endif
    </div>

    @foreach(['error' => 'danger', 'success' => 'success'] as $k => $cl)
        @if(session($k))<div class="alert alert-{{ $cl }}">{{ session($k) }}</div>@endif
    @endforeach
    @if($centre->status !== 'active')<div class="alert alert-warning">This store is paused: new bookings can't choose it. Parcels already booked still come through.</div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <form method="post" action="{{ route('counter.receive') }}" class="pub-card h-100">
                @csrf
                <h2 class="h5 fw-bold">A customer is handing in a parcel</h2>
                <p class="small text-muted">Type the code from their booking or scan the parcel label.</p>
                <div class="input-group input-group-lg">
                    <input name="code" class="form-control text-uppercase font-monospace" placeholder="QD…" required maxlength="40" autofocus autocomplete="off" value="{{ old('code') }}">
                    <button class="btn btn-brand">Receive</button>
                </div>
            </form>
        </div>
        <div class="col-md-6">
            <form method="post" action="{{ route('counter.collect') }}" class="pub-card h-100">
                @csrf
                <h2 class="h5 fw-bold">A driver is collecting</h2>
                <p class="small text-muted">Check the code on the parcel matches before you hand it over.</p>
                <div class="input-group input-group-lg">
                    <input name="code" class="form-control text-uppercase font-monospace" placeholder="QD…" required maxlength="40" autocomplete="off">
                    <button class="btn btn-dark">Hand over</button>
                </div>
            </form>
        </div>
    </div>

    <div class="pub-card mb-3">
        <h2 class="h6 fw-bold">At the counter, waiting for a driver ({{ count($parcels['waiting']) }})</h2>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Code</th><th>From</th><th>Received</th><th>Driver</th></tr></thead>
            <tbody>
            @forelse($parcels['waiting'] as $p)
                <tr><td class="font-monospace fw-semibold">{{ $p->tracking_code }}</td><td>{{ $p->sender }}</td><td class="small">{{ \Illuminate\Support\Carbon::parse($p->received_at)->diffForHumans() }}</td>
                    <td class="small">{{ $p->driver ?: 'Finding a driver…' }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-muted small py-3 text-center">Nothing waiting.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="pub-card h-100">
                <h2 class="h6 fw-bold">Expected ({{ count($parcels['expected']) }})</h2>
                <p class="small text-muted">Booked to your store, not handed in yet.</p>
                <ul class="list-unstyled small mb-0">
                    @forelse($parcels['expected'] as $p)<li class="d-flex justify-content-between border-top py-1"><span class="font-monospace">{{ $p->tracking_code }}</span><span class="text-muted">{{ $p->sender }}</span></li>
                    @empty<li class="text-muted">None.</li>@endforelse
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="pub-card h-100">
                <h2 class="h6 fw-bold">Your earnings</h2>
                <div class="small text-muted mb-2">{{ $commission }}</div>
                <div class="d-flex justify-content-between"><span>Still to be paid</span><strong>{{ Money::format($earnings['unpaid'], true) }}</strong></div>
                <div class="small text-muted">Handed over today: {{ count($parcels['collected']) }}</div>
                @if(count($earnings['months']))
                    <table class="table table-sm small mt-2 mb-0"><thead><tr><th>Month</th><th class="text-end">Parcels</th><th class="text-end">Earned</th></tr></thead><tbody>
                    @foreach($earnings['months'] as $m)<tr><td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $m['month'])->format('M Y') }}</td><td class="text-end">{{ $m['parcels'] }}</td><td class="text-end">{{ Money::format($m['amount'], true) }}</td></tr>@endforeach
                    </tbody></table>
                @endif
            </div>
        </div>
    </div>

    <details class="mt-3 small">
        <summary>Add a colleague</summary>
        <form method="post" action="{{ route('counter.staff') }}" class="d-flex gap-2 mt-2" style="max-width: 460px">@csrf
            <input name="email" type="email" class="form-control form-control-sm" placeholder="Their account email" required>
            <button class="btn btn-sm btn-outline-dark">Add</button>
        </form>
        <div class="text-muted mt-1">They need an account first: they can sign up at {{ route('register') }}.</div>
    </details>
</div>
@endsection
