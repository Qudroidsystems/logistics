{{-- resources/views/finance/payroll/remittance-show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $T = \App\Models\StatutoryRemittance::TYPES; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$T[$r->type][0] . ' — ' . $r->authority" :icon="$T[$r->type][1]" :subtitle="($r->period->period_name ?? '') . ' · due ' . ($r->due_date?->format('d M Y') ?? '—')"
               :back="route('payroll.remittances')" back-label="Remittances">
        <x-slot:actions><a href="{{ route('payroll.remittances.schedule', $r) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Download schedule</a></x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($r->needs_review)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>The payroll for this month changed after this was paid. Compare the schedule below with what was paid.</div></div>@endif
    @if(str_contains($r->authority, 'not set'))<div class="cb-banner warning"><i class="ri-user-settings-line"></i><div>These staff have no {{ $r->type === 'paye' ? 'tax state' : 'PFA' }} on their pay profile. Update them, then "Prepare / refresh" this month.</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Staff" :value="$r->staff_count" icon="ri-team-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Amount due" :value="$m($r->amount_due)" icon="ri-file-list-3-line" accent="violet" :hint="$r->employer_amount > 0 ? 'staff ' . $m($r->employee_amount) . ' + school ' . $m($r->employer_amount) : null" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Paid" :value="$m($r->amount_paid)" icon="ri-checkbox-circle-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Balance" :value="$m($r->balance())" icon="ri-time-line" :accent="$r->isOverdue() ? 'rose' : 'amber'" :hint="$r->isOverdue() ? 'Overdue' : null" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Schedule" icon="ri-table-line" :count="count($rows)" :flush="true">
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr>@foreach($head as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                    <tbody>@foreach($rows as $row)<tr>@foreach($row as $cell)<td class="{{ is_numeric($cell) && !is_int($cell) ? 'text-end' : '' }}">{{ is_float($cell) || (is_numeric($cell) && str_contains((string) $cell, '.')) ? number_format((float) $cell, 2) : $cell }}</td>@endforeach</tr>@endforeach</tbody>
                </table></div>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @if($r->status !== 'paid')
                @can('Manage remittances')
                <x-cb.card title="Record payment" icon="ri-bank-card-line">
                    <form method="POST" action="{{ route('payroll.remittances.pay', $r) }}" enctype="multipart/form-data">@csrf
                        <label class="small">Amount paid</label><input name="amount" type="number" step="0.01" min="0.01" class="form-control form-control-sm mb-2" value="{{ number_format($r->balance(), 2, '.', '') }}" required>
                        <label class="small">Date paid</label><input name="paid_at" type="date" class="form-control form-control-sm mb-2" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                        <label class="small">Receipt / reference (e.g. Remita RRR)</label><input name="reference" class="form-control form-control-sm mb-2" required maxlength="120">
                        <label class="small">Method</label><select name="payment_method" class="form-select form-select-sm mb-2"><option>Remita</option><option>Bank transfer</option><option>Bank deposit</option><option>Paystack</option><option>Other</option></select>
                        <label class="small">Receipt file (PDF/photo)</label><input name="evidence" type="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm mb-2">
                        <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Notes (optional)"></textarea>
                        @if(in_array($r->type, ['pension', 'nhf']))<label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="notify" value="1" checked> <span class="form-check-label">Tell each staff member it has been paid (portal)</span></label>@endif
                        <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-save-line"></i>Record payment</button>
                    </form>
                </x-cb.card>
                @endcan
            @endif
            <x-cb.card title="Payment record" icon="ri-history-line">
                <div class="small">
                    <div><span class="text-muted">Status:</span> {{ ucfirst($r->status) }}</div>
                    @if($r->paid_at)<div><span class="text-muted">Paid on:</span> {{ $r->paid_at->format('d M Y') }}</div>@endif
                    @if($r->reference)<div><span class="text-muted">Reference:</span> {{ $r->reference }}</div>@endif
                    @if($r->payment_method)<div><span class="text-muted">Method:</span> {{ $r->payment_method }}</div>@endif
                    @if($recorder)<div><span class="text-muted">Recorded by:</span> {{ $recorder }}</div>@endif
                    @if($r->notes)<div class="mt-1">{{ $r->notes }}</div>@endif
                    @if($r->evidence)<a href="{{ route('payroll.remittances.evidence', $r) }}" target="_blank" class="action-btn btn-open mt-2"><i class="ri-attachment-line"></i>View receipt</a>@endif
                </div>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
