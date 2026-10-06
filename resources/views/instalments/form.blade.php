{{-- resources/views/instalments/form.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $rows = old('percent') !== null
        ? collect(old('percent'))->map(fn ($p, $i) => ['label' => old('label')[$i] ?? '', 'percent' => $p, 'due_date' => old('due_date')[$i] ?? ''])->values()->all()
        : ($plan->schedule ?? []);
    $selClasses = array_map('intval', old('class_ids', $plan->class_ids ?? []));
    $applies = old('applies_to', $plan->applies_to ?? 'selected');
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$pagetitle" icon="ri-calendar-todo-line" subtitle="Each part is a % of the student's term fees (after scholarships and discounts)."
               :back="$plan->exists ? route('instalment-plans.show', $plan) : route('instalment-plans.index')" back-label="Back" />

    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <form method="POST" action="{{ $plan->exists ? route('instalment-plans.update', $plan) : route('instalment-plans.store') }}">
        @csrf @if($plan->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-lg-7">
                <x-cb.card title="Plan" icon="ri-file-list-3-line">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label" for="name">Name</label>
                            <input id="name" name="name" class="form-control" value="{{ old('name', $plan->name) }}" placeholder="e.g. First term 3-part plan" required></div>
                        <div class="col-12"><label class="form-label" for="description">Note (optional)</label>
                            <input id="description" name="description" class="form-control" value="{{ old('description', $plan->description) }}"></div>
                        <div class="col-sm-6"><label class="form-label" for="session_id">Session</label>
                            <select id="session_id" name="session_id" class="form-select" required>
                                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('session_id', $plan->session_id) == $s->id)>{{ $s->session }}</option>@endforeach
                            </select></div>
                        <div class="col-sm-6"><label class="form-label" for="term_id">Term</label>
                            <select id="term_id" name="term_id" class="form-select" required>
                                @foreach($terms as $t)<option value="{{ $t->id }}" @selected(old('term_id', $plan->term_id) == $t->id)>{{ $t->term }}</option>@endforeach
                            </select></div>
                    </div>
                </x-cb.card>

                <x-cb.card title="Instalments" icon="ri-calendar-schedule-line">
                    <table class="table align-middle mb-2" id="ipRows">
                        <thead><tr><th>Label</th><th style="width:110px">%</th><th style="width:170px">Due by</th><th style="width:40px"></th></tr></thead>
                        <tbody>
                        @foreach($rows as $r)
                            <tr>
                                <td><input name="label[]" class="form-control form-control-sm" value="{{ $r['label'] }}" aria-label="Label"></td>
                                <td><input name="percent[]" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm ip-pct" value="{{ $r['percent'] }}" aria-label="Percent" required></td>
                                <td><input name="due_date[]" type="date" class="form-control form-control-sm" value="{{ $r['due_date'] }}" aria-label="Due date" required></td>
                                <td><button type="button" class="btn btn-sm btn-light ip-del" aria-label="Remove"><i class="ri-delete-bin-line"></i></button></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="action-btn btn-open" id="ipAdd"><i class="ri-add-line"></i>Add instalment</button>
                        <span>Total: <strong id="ipTotal">0</strong>%</span>
                    </div>
                </x-cb.card>
            </div>

            <div class="col-lg-5">
                <x-cb.card title="Who is on this plan" icon="ri-group-line">
                    @foreach(\App\Models\FeeInstalmentPlan::APPLIES as $k => $label)
                        <label class="form-check mb-2"><input class="form-check-input ip-applies" type="radio" name="applies_to" value="{{ $k }}" @checked($applies === $k)> <span class="form-check-label">{{ $label }}</span></label>
                    @endforeach
                    <div id="ipClasses" class="border rounded p-2 mt-2" style="max-height:260px;overflow:auto;{{ $applies === 'classes' ? '' : 'display:none' }}">
                        @foreach($classes as $c)
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="class_ids[]" value="{{ $c->id }}" @checked(in_array((int) $c->id, $selClasses, true))> <span class="form-check-label">{{ $c->name }}</span></label>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-2">Students added to a plan by name always use that plan, even if their class has a different one.</div>
                </x-cb.card>

                <x-cb.card title="Rules" icon="ri-shield-check-line">
                    <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="results_when_on_track" value="1" @checked(old('results_when_on_track', $plan->results_when_on_track))>
                        <span class="form-check-label">Students who are up to date with the plan can see their results, even with a balance left</span></label>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                        <span class="form-check-label">Plan is active</span></label>
                    <div class="small text-muted mt-2">Fee reminders to families on a plan only go out when an instalment is overdue, and ask for the overdue amount.</div>
                </x-cb.card>

                <button class="action-btn btn-primary-cb w-100 justify-content-center py-2"><i class="ri-save-line"></i>Save plan</button>
            </div>
        </div>
    </form>
</div>
</div>
</div>

<template id="ipRowTpl">
    <tr>
        <td><input name="label[]" class="form-control form-control-sm" aria-label="Label"></td>
        <td><input name="percent[]" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm ip-pct" aria-label="Percent" required></td>
        <td><input name="due_date[]" type="date" class="form-control form-control-sm" aria-label="Due date" required></td>
        <td><button type="button" class="btn btn-sm btn-light ip-del" aria-label="Remove"><i class="ri-delete-bin-line"></i></button></td>
    </tr>
</template>
<script>
(function () {
    const body = document.querySelector('#ipRows tbody'), total = document.getElementById('ipTotal');
    const sum = () => {
        const t = [...body.querySelectorAll('.ip-pct')].reduce((a, i) => a + (parseFloat(i.value) || 0), 0);
        total.textContent = Math.round(t * 100) / 100;
        total.className = Math.abs(t - 100) < 0.001 ? 'text-success' : 'text-danger';
    };
    document.getElementById('ipAdd').addEventListener('click', () => {
        body.appendChild(document.getElementById('ipRowTpl').content.cloneNode(true)); sum();
    });
    body.addEventListener('click', e => {
        const b = e.target.closest('.ip-del');
        if (b && body.rows.length > 2) { b.closest('tr').remove(); sum(); }
    });
    body.addEventListener('input', sum);
    document.querySelectorAll('.ip-applies').forEach(r => r.addEventListener('change', () => {
        document.getElementById('ipClasses').style.display = document.querySelector('.ip-applies:checked').value === 'classes' ? '' : 'none';
    }));
    sum();
})();
</script>
@endsection
