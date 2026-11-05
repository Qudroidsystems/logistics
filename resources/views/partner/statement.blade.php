@extends('public._layout', ['title' => $s['label'].' statement · '.$centre->name, 'noindex' => true])
@section('content')
@php $tz = config('app.timezone'); $fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->setTimezone($tz)->format('j M, H:i') : '-'; @endphp
<div class="container py-4" style="max-width: 860px">
    <div class="d-flex justify-content-between align-items-start mb-3 d-print-none">
        <a href="{{ $back }}" class="small">&larr; Back</a>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print or save as PDF</button>
    </div>
    <div class="pub-card">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <div>
                <div class="text-muted small">{{ $brand->name ?? config('app.name') }} · partner store statement</div>
                <h1 class="h4 fw-bold mb-0">{{ $centre->name }}</h1>
                <div class="small text-muted">{{ $centre->code }} · {{ $centre->line1 }}</div>
            </div>
            <div class="text-end"><div class="h5 fw-bold mb-0">{{ $s['label'] }}</div><div class="small text-muted">Issued {{ now($tz)->format('j M Y') }}</div></div>
        </div>

        <div class="row g-2 text-center mb-3">
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Parcels handed over</div><div class="fw-bold fs-5">{{ $s['count'] }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Earned this month</div><div class="fw-bold fs-5">{{ Money::format($s['earned'], true) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Paid to you this month</div><div class="fw-bold fs-5">{{ Money::format($s['paid'], true) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small text-muted">Still owed today</div><div class="fw-bold fs-5">{{ Money::format($s['owed'], true) }}</div></div></div>
        </div>

        @if(count($s['payouts']))
            <h2 class="h6 fw-bold">Payments</h2>
            <table class="table table-sm small"><thead><tr><th>Date</th><th>Reference</th><th class="text-end">Parcels</th><th class="text-end">Amount</th></tr></thead><tbody>
            @foreach($s['payouts'] as $p)<tr><td>{{ $fmt($p->paid_at) }}</td><td>{{ $p->payout_ref ?: '-' }}</td><td class="text-end">{{ $p->parcels }}</td><td class="text-end">{{ Money::format((int) $p->amount, true) }}</td></tr>@endforeach
            </tbody></table>
        @endif

        <h2 class="h6 fw-bold">Parcels</h2>
        <table class="table table-sm small mb-0"><thead><tr><th>Tracking</th><th>Received</th><th>Handed to driver</th><th class="text-end">Commission</th><th>Status</th></tr></thead><tbody>
        @forelse($s['parcels'] as $p)
            <tr><td class="font-monospace">{{ $p->tracking_code }}</td><td>{{ $fmt($p->received_at) }}</td><td>{{ $fmt($p->collected_at) }}</td>
                <td class="text-end">{{ Money::format((int) $p->commission, true) }}</td><td>{{ $p->commission_status === 'paid' ? 'Paid' : 'Owed' }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No parcels handed over this month.</td></tr>
        @endforelse
        </tbody>
        @if($s['count'])<tfoot><tr class="fw-semibold"><td colspan="3">Total</td><td class="text-end">{{ Money::format($s['earned'], true) }}</td><td></td></tr></tfoot>@endif
        </table>
        <p class="small text-muted mt-3 mb-0">Times are {{ $tz }}. Questions about this statement? Reply to the email it came with or contact us from the counter screen.</p>
    </div>
</div>
@endsection
