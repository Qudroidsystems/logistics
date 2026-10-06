{{-- resources/views/finance/payroll/claims.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $T = \App\Models\StaffDutyClaim::TYPES; $canDecide = auth()->user()->can('Approve duty claims'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Extra Duty Claims" icon="ri-time-line" subtitle="Extra lessons, overtime and weekend duty — approved claims are paid with the next payroll." :back="route('payroll.attendance-pay')" back-label="Attendance & pay" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        @foreach(\App\Models\StaffDutyClaim::STATUS as $k => [$l, $c])
            <div class="col-md-3 col-6"><a href="{{ route('payroll.claims', ['status' => $k]) }}" class="text-reset"><x-cb.stat :label="$l" :value="$m($totals[$k]->amt ?? 0)" icon="ri-time-line" :accent="['pending' => 'amber', 'approved' => 'teal', 'rejected' => 'rose', 'paid' => 'green'][$k]" :hint="($totals[$k]->n ?? 0) . ' claim(s)'" /></a></div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <form method="POST" action="{{ route('payroll.claims.decide') }}">@csrf
            <x-cb.card :title="ucfirst($status) . ' claims'" icon="ri-list-check-2" :count="$claims->total()" :flush="true">
                <x-slot:tools>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="cb-select" onchange="location='{{ route('payroll.claims') }}?status='+this.value">@foreach(['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Declined', 'all' => 'All'] as $k => $l)<option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>@endforeach</select>
                        @if($canDecide && $status === 'pending')
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (if declining)" style="max-width:180px">
                            <button name="action" value="approve" class="action-btn btn-go"><i class="ri-check-line"></i>Approve ticked</button>
                            <button name="action" value="reject" class="action-btn btn-open">Decline ticked</button>
                        @endif
                    </div>
                </x-slot:tools>
                @if($claims->isEmpty())
                    <div class="empty-state"><i class="ri-time-line"></i><h6>Nothing here</h6><p>Staff submit claims from My Pay › Extra duty.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr>@if($canDecide && $status === 'pending')<th style="width:36px"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked)"></th>@endif<th>Staff</th><th>Date</th><th>Duty</th><th>Details</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($claims as $c) @php [$l, $cl] = $c->label(); @endphp
                            <tr>
                                @if($canDecide && $status === 'pending')<td><input type="checkbox" class="form-check-input pick" name="ids[]" value="{{ $c->id }}"></td>@endif
                                <td>{{ $c->staff_name }}</td><td>{{ $c->work_date->format('D d M Y') }}</td>
                                <td>{{ $c->typeLabel() }} · {{ rtrim(rtrim(number_format($c->quantity, 2), '0'), '.') }}</td>
                                <td class="small">{{ $c->description }}@if($c->rejection_reason)<div class="text-danger">{{ $c->rejection_reason }}</div>@endif</td>
                                <td class="text-end fw-bold">{{ $m($c->amount) }}</td>
                                <td><span class="status-pill {{ $cl }}">{{ $l }}</span>@if($c->status === 'paid' && $c->period)<div class="small text-muted">{{ $c->period->period_name }}</div>@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <div class="p-3">{{ $claims->links() }}</div>
                @endif
            </x-cb.card>
            </form>
        </div>
        <div class="col-xl-3">
            @if($canDecide)
            <x-cb.card title="Record duty for a staff member" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('payroll.claims.store') }}" class="small">@csrf
                    <select name="staff_id" class="form-select form-select-sm mb-2" required><option value="">Staff…</option>@foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                    <input type="date" name="work_date" class="form-control form-control-sm mb-2" max="{{ now()->toDateString() }}" required>
                    <select name="type" class="form-select form-select-sm mb-2">@foreach($T as $k => [$l])<option value="{{ $k }}">{{ $l }}{{ $k !== 'other' ? ' · ' . $m($rates[$k] ?? 0) : '' }}</option>@endforeach</select>
                    <div class="row g-2 mb-2"><div class="col-6"><input type="number" step="0.5" min="0.5" name="quantity" value="1" class="form-control form-control-sm" placeholder="Qty"></div><div class="col-6"><input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" placeholder="₦ (other)"></div></div>
                    <input type="text" name="description" class="form-control form-control-sm mb-2" placeholder="Details" required>
                    <button class="action-btn btn-go w-100 justify-content-center">Save (approved)</button>
                </form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div>
</div>
</div>
@endsection
