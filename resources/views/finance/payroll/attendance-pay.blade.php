{{-- resources/views/finance/payroll/attendance-pay.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $S = $settings; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Attendance & Pay" icon="ri-fingerprint-line" subtitle="How clock-in records, unexplained absences and lateness affect salary — preview before you calculate payroll.">
        <x-slot:actions><a href="{{ route('payroll.claims') }}" class="cb-hero-btn"><i class="ri-time-line"></i>Duty claims @if($pendingClaims)<span class="badge bg-danger ms-1">{{ $pendingClaims }}</span>@endif</a></x-slot:actions>
        <x-slot:pills><span class="cb-meta-pill"><i class="ri-{{ $S['enabled'] ? 'checkbox-circle' : 'pause-circle' }}-line"></i>Attendance deductions {{ $S['enabled'] ? 'ON' : 'OFF' }}</span></x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(!$S['enabled'])<div class="cb-banner info"><i class="ri-information-line"></i><div>Deductions are off, so the figures below are an <b>estimate only</b>. Approved duty claims are still paid.</div></div>@endif

    @php $withData = $rows->where('has_data', true); @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Staff with clock-ins" :value="$withData->count() . ' / ' . $rows->count()" icon="ri-fingerprint-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Unexplained absences" :value="$withData->sum('absent')" icon="ri-user-unfollow-line" accent="rose" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Late arrivals" :value="$withData->sum('late')" icon="ri-alarm-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Estimated deduction" :value="$m($rows->sum('est'))" icon="ri-indeterminate-circle-line" accent="rose" hint="Based on current payroll gross" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card :title="'Month · ' . ($period->period_name ?? '—')" icon="ri-calendar-check-line" :count="$rows->count()" :flush="true">
                <x-slot:tools>
                    <form method="GET"><select name="period" class="cb-select" onchange="this.form.submit()">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($period && $p->id === $period->id)>{{ $p->period_name }}</option>@endforeach</select></form>
                </x-slot:tools>
                @if(!$period)
                    <div class="empty-state"><i class="ri-calendar-line"></i><h6>No payroll month</h6><p>Create a payroll period first.</p></div>
                @else
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Staff</th><th class="text-end">Clocked in</th><th class="text-end">Leave</th><th class="text-end">Late</th><th class="text-end">Absent</th><th class="text-end">Est. deduction</th></tr></thead>
                    <tbody>
                    @foreach($rows->sortByDesc('est') as $r)
                        <tr class="{{ $r->est > 0 ? 'table-warning' : '' }}">
                            <td>{{ $r->name }} @if($r->exempt)<span class="status-pill st-muted">exempt</span>@elseif(!$r->has_data)<span class="status-pill st-muted">no records</span>@endif</td>
                            <td class="text-end">{{ $r->present }} / {{ $r->counted_days }}</td><td class="text-end">{{ $r->leave }}</td>
                            <td class="text-end">{{ $r->late }}</td>
                            <td class="text-end" title="{{ collect($r->absent_dates)->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d M'))->implode(', ') }}">{{ $r->has_data ? $r->absent : '—' }}</td>
                            <td class="text-end fw-bold">{{ $r->est > 0 ? $m($r->est) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @can('Manage payroll settings')
            <x-cb.card title="Rules" icon="ri-settings-3-line">
                <form method="POST" action="{{ route('payroll.attendance-pay.settings') }}" class="small">@csrf
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="enabled" value="1" id="en" @checked($S['enabled'])><label class="form-check-label" for="en"><b>Apply attendance to pay</b></label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="deduct_absence" value="1" id="da" @checked($S['deduct_absence'])><label class="form-check-label" for="da">Deduct a day's pay for each absence with no approved leave</label></div>
                    <div class="d-flex justify-content-between align-items-center mb-1"><label class="mb-0">Free absences per month</label><input type="number" name="grace_absences" min="0" max="10" value="{{ $S['grace_absences'] }}" class="form-control form-control-sm" style="width:80px"></div>
                    <div class="d-flex justify-content-between align-items-center mb-2"><label class="mb-0">Lates that equal 1 day (0 = off)</label><input type="number" name="lates_per_day" min="0" max="30" value="{{ $S['lates_per_day'] }}" class="form-control form-control-sm" style="width:80px"></div>
                    <label class="form-label mb-1">Not on the clock-in device (never deducted)</label>
                    <select name="exempt_staff[]" class="form-select form-select-sm mb-2" multiple size="5">@foreach($staff as $s)<option value="{{ $s->id }}" @selected(in_array($s->id, (array) $S['exempt_staff']))>{{ $s->name }}</option>@endforeach</select>
                    <hr>
                    <div class="fw-bold mb-1">Duty claim rates</div>
                    @foreach(['extra_lesson' => 'Per extra lesson', 'overtime_hour' => 'Per overtime hour', 'weekend_duty' => 'Per weekend / holiday day'] as $k => $l)
                        <div class="d-flex justify-content-between align-items-center mb-1"><label class="mb-0">{{ $l }} (₦)</label><input type="number" step="0.01" min="0" name="rates[{{ $k }}]" value="{{ $S['rates'][$k] ?? 0 }}" class="form-control form-control-sm" style="width:110px"></div>
                    @endforeach
                    <div class="form-check mb-2 mt-2"><input class="form-check-input" type="checkbox" name="overtime_taxable" value="1" id="ot" @checked($S['overtime_taxable'])><label class="form-check-label" for="ot">Duty payments are taxable (PAYE)</label></div>
                    <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-save-line"></i>Save rules</button>
                </form>
            </x-cb.card>
            @endcan
            <div class="small text-muted">Weekends, school holidays and device-outage days are never counted. Staff with no clock-ins all month are not deducted — they're flagged on the payroll instead. A day's pay = monthly regular pay ÷ working days in the month.</div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
