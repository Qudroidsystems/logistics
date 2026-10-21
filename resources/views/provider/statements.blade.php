@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Statements" icon="ri-file-list-2-line" subtitle="What you sold, what the platform kept, and what was paid into your wallet." :back="route('provider.dashboard')" back-label="Dashboard" />
    @include('provider._flash')

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Sales this month" :value="$naira($month->gross)" icon="ri-line-chart-line" accent="violet" :hint="$month->n.' jobs'" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Platform fee this month" :value="$naira($month->fee)" icon="ri-percent-line" accent="amber" :hint="$commission ? 'Your rate: '.$commission['percent'].'%'.($commission['min'] ? ', min '.$naira($commission['min']) : '') : 'No fee set'" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Your earnings this month" :value="$naira($month->net)" icon="ri-hand-coin-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Weekly statements" :value="count($statements)" icon="ri-calendar-check-line" accent="sky" /></div>
    </div>

    <x-cb.card title="Weekly statements" icon="ri-calendar-check-line" :flush="true" class="mb-3">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Week</th><th class="text-end">Sales</th><th class="text-end">Platform fee</th><th class="text-end">Adjustments</th><th class="text-end">Paid to you</th><th class="text-end">Status</th></tr></thead>
            <tbody>
            @forelse($statements as $st)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($st->period_start)->format('j M') }} – {{ \Illuminate\Support\Carbon::parse($st->period_end)->format('j M Y') }}</td>
                    <td class="text-end">{{ $naira($st->gross) }}</td>
                    <td class="text-end text-danger">−{{ $naira($st->commission) }}</td>
                    <td class="text-end">{{ (int) $st->adjustments ? $naira($st->adjustments) : '—' }}</td>
                    <td class="text-end fw-semibold">{{ $naira($st->net) }}</td>
                    <td class="text-end"><span class="badge bg-{{ $st->status === 'draft' ? 'secondary' : 'success' }}">{{ $st->status === 'draft' ? 'being checked' : $st->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No statements yet. One is made each week you have completed jobs.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    <x-cb.card title="Fee on each job" icon="ri-percent-line" :flush="true">
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Job</th><th>When</th><th class="text-end">Price</th><th class="text-end">Platform fee</th><th class="text-end">Rider's pay</th><th class="text-end">You keep</th></tr></thead>
            <tbody>
            @forelse($jobs as $j)
                <tr>
                    <td>{{ $j->order_number }}<div class="small text-muted">{{ $j->tracking_code }}</div></td>
                    <td class="small">{{ \Illuminate\Support\Carbon::parse($j->created_at)->format('j M H:i') }}</td>
                    <td class="text-end">{{ $naira($j->gross_amount) }}</td>
                    <td class="text-end text-danger">−{{ $naira($j->platform_fee) }}</td>
                    <td class="text-end">{{ (int) $j->driver_net ? $naira($j->driver_net) : '—' }}</td>
                    <td class="text-end fw-semibold">{{ $naira($j->operator_net) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No completed jobs yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
