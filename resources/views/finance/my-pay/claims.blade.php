{{-- resources/views/finance/my-pay/claims.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $T = \App\Models\StaffDutyClaim::TYPES; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Extra Duty & Attendance" icon="ri-time-line" subtitle="Claim extra lessons, overtime and weekend duty; see how this month's attendance looks." />
    @include('finance.my-pay._nav', ['section' => 'claims'])

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    @if($summary)
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat :label="'Working days · ' . $period->period_name" :value="$summary['counted_days'] . ' / ' . $summary['working_days']" icon="ri-calendar-check-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Days clocked in" :value="$summary['present']" icon="ri-fingerprint-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Late arrivals" :value="$summary['late']" icon="ri-alarm-line" :accent="$summary['late'] ? 'amber' : 'green'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Absent (no leave)" :value="$summary['has_data'] ? $summary['absent'] : '—'" icon="ri-user-unfollow-line" :accent="$summary['absent'] ? 'rose' : 'green'" :hint="$attendanceOn ? 'Unexplained absences may reduce pay' : null" /></div>
    </div>
    @if($summary['absent'] && $summary['has_data'])<div class="cb-banner warning"><i class="ri-information-line"></i><div>No clock-in on: {{ collect($summary['absent_dates'])->map(fn ($d) => \Carbon\Carbon::parse($d)->format('D d M'))->implode(', ') }}. If you were at work or on leave, speak to the admin office before payroll is calculated.</div></div>@endif
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="My claims" icon="ri-list-check-2" :count="$claims->count()" :flush="true">
                @if($claims->isEmpty())
                    <div class="empty-state"><i class="ri-time-line"></i><h6>No claims</h6><p>Claims approved before payroll is calculated are paid with that month's salary.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Date</th><th>Duty</th><th>Details</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($claims as $c) @php [$l, $cl] = $c->label(); @endphp
                            <tr><td>{{ $c->work_date->format('d M Y') }}</td><td>{{ $c->typeLabel() }} · {{ rtrim(rtrim(number_format($c->quantity, 2), '0'), '.') }} {{ $T[$c->type][1] ?? '' }}</td><td class="small">{{ $c->description }}@if($c->rejection_reason)<div class="text-danger">{{ $c->rejection_reason }}</div>@endif</td>
                                <td class="text-end fw-bold">{{ $m($c->amount) }}</td><td><span class="status-pill {{ $cl }}">{{ $l }}</span></td>
                                <td>@if($c->status === 'pending')<form method="POST" action="{{ route('my-pay.claims.withdraw', $c) }}">@csrf @method('DELETE')<button class="action-btn btn-open" title="Withdraw"><i class="ri-delete-bin-line"></i></button></form>@endif</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="New claim" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('my-pay.claims.store') }}">@csrf
                    <label class="form-label small">Date</label><input type="date" name="work_date" class="form-control mb-2" max="{{ now()->toDateString() }}" value="{{ old('work_date') }}" required>
                    <label class="form-label small">Duty</label>
                    <select name="type" class="form-select mb-2" onchange="document.getElementById('amt').style.display=this.value==='other'?'':'none'">
                        @foreach($T as $k => [$l, $u])<option value="{{ $k }}">{{ $l }}{{ $k !== 'other' ? ' · ' . $m($rates[$k] ?? 0) . ' per ' . rtrim($u, '(s)') : '' }}</option>@endforeach
                    </select>
                    <label class="form-label small">How many (lessons / hours / days)</label><input type="number" name="quantity" step="0.5" min="0.5" class="form-control mb-2" value="{{ old('quantity', 1) }}" required>
                    <div id="amt" style="display:none"><label class="form-label small">Amount (₦)</label><input type="number" name="amount" step="0.01" min="0" class="form-control mb-2"></div>
                    <label class="form-label small">What you did</label><input type="text" name="description" class="form-control mb-3" maxlength="255" placeholder="e.g. SS3 Physics revision class" required>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-line"></i>Submit</button>
                </form>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
