{{-- resources/views/finance/payroll/review-show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $n = fn ($v) => '₦' . number_format((float) $v, 2); $ok = collect($p['rows'])->whereNull('note'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$r->name" icon="ri-line-chart-line" :subtitle="($r->type === 'percent_raise' ? rtrim(rtrim(number_format($r->percent, 2), '0'), '.') . '% raise' : 'Step increment') . ' from ' . $r->effective_from->format('d M Y') . ($r->grade_ids ? ' · ' . collect($r->grade_ids)->map(fn ($g) => $grades[$g] ?? $g)->implode(', ') : ' · all grades')"
               :back="route('payroll.reviews')" back-label="Salary reviews">
        <x-slot:pills><span class="cb-meta-pill"><i class="ri-flag-line"></i>{{ ucfirst($r->status) }}</span></x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Staff affected" :value="$ok->count()" icon="ri-team-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Monthly pay now" :value="$n($p['old_total'])" icon="ri-wallet-line" accent="sky" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Monthly pay after" :value="$n($p['new_total'])" icon="ri-wallet-3-line" accent="violet" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Extra cost a month" :value="$n($p['new_total'] - $p['old_total'])" icon="ri-arrow-up-line" accent="amber" :hint="$p['old_total'] > 0 ? '+' . round(($p['new_total'] - $p['old_total']) / $p['old_total'] * 100, 1) . '%' : null" /></div>
    </div>

    @if($r->status !== 'applied')
        <div class="cb-card mb-3">
            @if($p['past_periods']->isNotEmpty())
                <div class="cb-banner warning mb-2"><i class="ri-time-line"></i><div>Back-dated: {{ $p['past_periods']->pluck('period_name')->implode(', ') }} {{ $p['past_periods']->count() > 1 ? 'are' : 'is' }} already approved. The difference will be paid as arrears in the month you choose below.</div></div>
            @endif
            <div class="d-flex flex-wrap gap-2 align-items-end">
                @if($r->status === 'draft')
                    @can('Approve payroll')
                        <form method="POST" action="{{ route('payroll.reviews.approve', $r) }}">@csrf<button class="action-btn btn-go"><i class="ri-checkbox-circle-line"></i>Approve</button></form>
                    @else <span class="small text-muted">Waiting for approval by someone with "Approve payroll".</span> @endcan
                @elseif($r->status === 'approved')
                    @can('Approve payroll')
                        <form method="POST" action="{{ route('payroll.reviews.apply', $r) }}" class="d-flex gap-2 align-items-end" onsubmit="return confirm('Apply this review now?')">@csrf
                            @if($p['past_periods']->isNotEmpty())
                                <div><label class="small">Pay arrears in</label><select name="arrears_period_id" class="form-select form-select-sm" required>
                                    <option value="">Choose draft month…</option>@foreach($periods as $pp)<option value="{{ $pp->id }}">{{ $pp->period_name }}</option>@endforeach</select></div>
                            @endif
                            <button class="action-btn btn-primary-cb"><i class="ri-play-line"></i>Apply review</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    @endif

    <x-cb.card title="Staff" icon="ri-team-line" :count="count($p['rows'])" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Staff</th><th>Grade</th><th>Step</th><th class="text-end">Monthly now</th><th class="text-end">After</th><th class="text-end">Change</th></tr></thead>
            <tbody>
            @forelse($p['rows'] as $row)
                <tr class="{{ $row['note'] ? 'text-muted' : '' }}">
                    <td>{{ $row['name'] }}@if($row['note'])<div class="small text-warning">{{ $row['note'] }}</div>@endif</td>
                    <td>{{ $row['grade'] }}</td>
                    <td>{{ $row['from'] }}{{ $row['to'] != $row['from'] ? ' → ' . $row['to'] : '' }}</td>
                    <td class="text-end">{{ $n($row['old']) }}</td>
                    <td class="text-end">{{ $n($row['new']) }}</td>
                    <td class="text-end fw-bold {{ $row['new'] > $row['old'] ? 'text-success' : '' }}">{{ $row['new'] > $row['old'] ? '+' : '' }}{{ $n($row['new'] - $row['old']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No staff are on the scale for these grades on that date.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div>
</div>
</div>
@endsection
