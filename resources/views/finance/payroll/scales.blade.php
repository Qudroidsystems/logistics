{{-- resources/views/finance/payroll/scales.blade.php --}}
@extends('layouts.master')

@section('content')
@php $n = fn ($v) => number_format((float) $v, 2); $sum = fn ($r) => collect(array_keys($cols))->sum(fn ($c) => (float) ($r->$c ?? 0)); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Salary Scales" icon="ri-stack-line" subtitle="Monthly pay for each grade and step. A raise from a new date keeps the old amounts for earlier months.">
        <x-slot:actions>
            <a href="{{ route('payroll.reviews') }}" class="cb-hero-btn"><i class="ri-line-chart-line"></i>Salary reviews</a>
            <a href="{{ route('payroll.items') }}" class="cb-hero-btn"><i class="ri-list-settings-line"></i>Allowances & deductions</a>
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-{{ $edit ? '5' : '8' }}">
            <x-cb.card title="Grades" icon="ri-stack-line" :count="$grades->count()" :flush="true">
                <form class="cb-toolbar gap-2" method="GET">
                    <label class="small text-muted mb-0">Scale in force on</label>
                    <input type="date" name="date" class="form-control form-control-sm" style="max-width:170px" value="{{ $date->toDateString() }}" onchange="this.form.submit()">
                    @if($edit)<input type="hidden" name="grade" value="{{ $edit->id }}">@endif
                </form>
                @if($grades->isEmpty())
                    <div class="empty-state"><i class="ri-stack-line"></i><h6>No grades yet</h6><p>Add a grade, e.g. GL07 "Graduate teacher", then enter pay for each step.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Grade</th><th class="text-end">Step 1 gross</th><th class="text-end">Top step gross</th><th class="text-end">Staff</th><th></th></tr></thead>
                        <tbody>
                        @foreach($grades as $g)
                            @php $st = $scale[$g->id] ?? []; @endphp
                            <tr class="{{ $edit && $edit->id === $g->id ? 'table-active' : '' }} {{ $g->is_active ? '' : 'opacity-50' }}">
                                <td><strong>{{ $g->code }}</strong> <span class="text-muted">{{ $g->name }}</span><div class="small text-muted">{{ count($st) }}/{{ $g->max_step }} steps set</div></td>
                                <td class="text-end">{{ isset($st[1]) ? '₦' . $n($sum($st[1])) : '—' }}</td>
                                <td class="text-end">{{ $st ? '₦' . $n($sum(end($st))) : '—' }}</td>
                                <td class="text-end">{{ $counts[$g->id] ?? 0 }}</td>
                                <td class="text-end">@can('Manage salary structures')<a href="{{ route('payroll.scales', ['grade' => $g->id, 'date' => $date->toDateString()]) }}" class="action-btn btn-open"><i class="ri-edit-line"></i>Steps</a>@endcan</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>

        @if($edit)
            <div class="col-xl-7">
                <x-cb.card :title="$edit->code . ' — ' . $edit->name" icon="ri-edit-2-line">
                    <form method="POST" action="{{ route('payroll.grades.update', $edit) }}" class="row g-2 mb-3">@csrf @method('PUT')
                        <div class="col-3"><input name="code" class="form-control form-control-sm" value="{{ $edit->code }}" aria-label="Code"></div>
                        <div class="col-4"><input name="name" class="form-control form-control-sm" value="{{ $edit->name }}" aria-label="Name"></div>
                        <div class="col-2"><input name="max_step" type="number" min="1" max="30" class="form-control form-control-sm" value="{{ $edit->max_step }}" title="Number of steps" aria-label="Steps"></div>
                        <div class="col-1"><input name="sort" type="number" class="form-control form-control-sm" value="{{ $edit->sort }}" title="Order" aria-label="Order"></div>
                        <div class="col-2 d-flex gap-1 align-items-center"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($edit->is_active) title="Active"><button class="btn btn-sm btn-light">Save</button></div>
                    </form>

                    <form method="POST" action="{{ route('payroll.grades.steps', $edit) }}">@csrf
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label class="small mb-0">Amounts apply from</label>
                            <input type="date" name="effective_from" class="form-control form-control-sm" style="max-width:170px" value="{{ old('effective_from', $date->toDateString()) }}" required>
                            <button type="button" class="btn btn-sm btn-light" id="fillDown" title="Copy step 1 to empty steps, adding the % each step">Fill steps…</button>
                        </div>
                        <div class="table-responsive"><table class="table table-sm align-middle" id="stepGrid">
                            <thead><tr><th>Step</th>@foreach($cols as $c => [$code, $label])<th class="text-end" style="min-width:95px">{{ str_replace(' allowance', '', $label) }}</th>@endforeach<th class="text-end">Gross</th></tr></thead>
                            <tbody>
                            @for($s = 1; $s <= $edit->max_step; $s++)
                                @php $row = $scale[$edit->id][$s] ?? null; @endphp
                                <tr>
                                    <th>{{ $s }}</th>
                                    @foreach($cols as $c => $x)<td><input name="steps[{{ $s }}][{{ $c }}]" class="form-control form-control-sm text-end sg" inputmode="decimal" value="{{ $row ? (float) $row->$c : '' }}" aria-label="Step {{ $s }} {{ $c }}"></td>@endforeach
                                    <td class="text-end fw-bold sg-total">0</td>
                                </tr>
                            @endfor
                            </tbody>
                        </table></div>
                        <label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="confirm_past" value="1"> <span class="form-check-label">I understand months already approved won't be recalculated (use Salary Reviews for back-dated raises with arrears)</span></label>
                        <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save amounts</button>
                    </form>
                </x-cb.card>
            </div>
        @else
            <div class="col-xl-4">
                @can('Manage salary structures')
                <x-cb.card title="Add grade" icon="ri-add-line">
                    <form method="POST" action="{{ route('payroll.grades.store') }}">@csrf
                        <div class="row g-2 mb-2">
                            <div class="col-4"><input name="code" class="form-control form-control-sm" placeholder="GL07" required aria-label="Code"></div>
                            <div class="col-8"><input name="name" class="form-control form-control-sm" placeholder="Graduate teacher" required aria-label="Name"></div>
                            <div class="col-6"><label class="small">Number of steps</label><input name="max_step" type="number" min="1" max="30" value="10" class="form-control form-control-sm"></div>
                            <div class="col-6"><label class="small">Order</label><input name="sort" type="number" value="{{ $grades->count() + 1 }}" class="form-control form-control-sm"></div>
                        </div>
                        <button class="action-btn btn-go"><i class="ri-add-line"></i>Add grade</button>
                    </form>
                </x-cb.card>
                @endcan
                <div class="small text-muted">To put a staff member on a grade, open their pay profile and choose "Salary scale". Staff can also stay on an individual salary structure.</div>
            </div>
        @endif
    </div>
</div>
</div>
</div>
@if($edit)
<script>
(function () {
    const grid = document.getElementById('stepGrid');
    const num = v => parseFloat(String(v).replace(/,/g, '')) || 0;
    const totals = () => grid.querySelectorAll('tbody tr').forEach(tr => {
        const t = [...tr.querySelectorAll('.sg')].reduce((a, i) => a + num(i.value), 0);
        tr.querySelector('.sg-total').textContent = t ? t.toLocaleString('en-NG', {minimumFractionDigits: 2}) : '—';
    });
    grid.addEventListener('input', totals); totals();
    document.getElementById('fillDown').addEventListener('click', () => {
        const pct = parseFloat(prompt('Increase each step by what % over the one before?', '3'));
        if (isNaN(pct)) return;
        const rows = [...grid.querySelectorAll('tbody tr')];
        for (let i = 1; i < rows.length; i++) {
            const prev = rows[i - 1].querySelectorAll('.sg'), cur = rows[i].querySelectorAll('.sg');
            cur.forEach((inp, k) => { if (!inp.value) inp.value = (Math.round(num(prev[k].value) * (1 + pct / 100) * 100) / 100) || ''; });
        }
        totals();
    });
})();
</script>
@endif
@endsection
