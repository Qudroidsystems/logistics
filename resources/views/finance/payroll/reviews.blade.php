{{-- resources/views/finance/payroll/reviews.blade.php --}}
@extends('layouts.master')

@section('content')
@php $S = ['draft' => ['Draft', 'st-muted'], 'approved' => ['Approved', 'st-pending'], 'applied' => ['Applied', 'st-paid']]; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Salary Reviews" icon="ri-line-chart-line" subtitle="Yearly step increments or % raises from a date. Back-dated reviews pay the difference as arrears automatically." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="Reviews" icon="ri-history-line" :count="$reviews->count()" :flush="true">
                @if($reviews->isEmpty())
                    <div class="empty-state"><i class="ri-line-chart-line"></i><h6>No reviews yet</h6><p>Create one on the right, e.g. "2026/27 annual increment" from 1 September.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Review</th><th>From</th><th>Status</th><th class="text-end">Result</th><th></th></tr></thead>
                        <tbody>
                        @foreach($reviews as $r)
                            <tr>
                                <td><strong>{{ $r->name }}</strong><div class="small text-muted">{{ $r->type === 'percent_raise' ? rtrim(rtrim(number_format($r->percent, 2), '0'), '.') . '% raise' : 'Step increment' }}</div></td>
                                <td class="small">{{ $r->effective_from->format('d M Y') }}</td>
                                <td><span class="status-pill {{ $S[$r->status][1] }}">{{ $S[$r->status][0] }}</span></td>
                                <td class="text-end small">@if($r->summary){{ $r->summary['staff'] }} staff · +₦{{ number_format($r->summary['monthly_increase'], 2) }}/mo @if($r->summary['arrears_total'])<div>arrears ₦{{ number_format($r->summary['arrears_total'], 2) }}</div>@endif @else — @endif</td>
                                <td class="text-end"><a href="{{ route('payroll.reviews.show', $r) }}" class="action-btn btn-open"><i class="ri-eye-line"></i>Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-5">
            @can('Manage salary structures')
            <x-cb.card title="New review" icon="ri-add-line">
                <form method="POST" action="{{ route('payroll.reviews.store') }}">@csrf
                    <input name="name" class="form-control form-control-sm mb-2" placeholder="e.g. 2026/27 annual increment" required>
                    <select name="type" class="form-select form-select-sm mb-2" id="rvType">@foreach(\App\Models\SalaryReview::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small">Takes effect from</label><input name="effective_from" type="date" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
                        <div class="col-6 rv-pct"><label class="small">Raise %</label><input name="percent" type="number" step="0.01" min="0.01" class="form-control form-control-sm"></div>
                    </div>
                    <div class="rv-pct mb-2"><div class="small mb-1">Which parts rise</div>
                        @foreach($cols as $c => [$code, $label])<label class="form-check form-check-inline small"><input class="form-check-input" type="checkbox" name="components[]" value="{{ $c }}" @checked($c === 'basic')> <span class="form-check-label">{{ str_replace(' allowance', '', $label) }}</span></label>@endforeach
                    </div>
                    <div class="small mb-1">Grades (none ticked = all)</div>
                    <div class="border rounded p-2 mb-2" style="max-height:160px;overflow:auto">
                        @foreach($grades as $g)<label class="form-check small"><input class="form-check-input" type="checkbox" name="grade_ids[]" value="{{ $g->id }}"> <span class="form-check-label">{{ $g->code }} — {{ $g->name }}</span></label>@endforeach
                    </div>
                    <button class="action-btn btn-go"><i class="ri-arrow-right-line"></i>Create & preview</button>
                </form>
            </x-cb.card>
            @endcan
            <div class="small text-muted">Reviews only affect staff paid from the salary scale (not individual salary structures). Nothing changes until the review is approved and applied.</div>
        </div>
    </div>
</div>
</div>
</div>
<script>
(function () { const t = document.getElementById('rvType'); if (!t) return;
  const f = () => document.querySelectorAll('.rv-pct').forEach(e => e.style.display = t.value === 'percent_raise' ? '' : 'none'); t.addEventListener('change', f); f(); })();
</script>
@endsection
