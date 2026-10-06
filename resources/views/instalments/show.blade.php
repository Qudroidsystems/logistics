{{-- resources/views/instalments/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $money = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$plan->name" icon="ri-calendar-todo-line" :subtitle="$term . ' · ' . $session . ($plan->description ? ' — ' . $plan->description : '')"
               :back="route('instalment-plans.index', ['session_id' => $plan->session_id])" back-label="Instalment plans">
        @can('Manage instalment-plans')
            <x-slot:actions>
                <a href="{{ route('instalment-plans.edit', $plan) }}" class="cb-hero-btn"><i class="ri-edit-line"></i>Edit</a>
                <form method="POST" action="{{ route('instalment-plans.toggle', $plan) }}" class="d-inline">@csrf
                    <button class="cb-hero-btn"><i class="ri-toggle-line"></i>{{ $plan->is_active ? 'Switch off' : 'Switch on' }}</button></form>
                <form method="POST" action="{{ route('instalment-plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('Delete this plan? Payments are not affected.')">@csrf @method('DELETE')
                    <button class="cb-hero-btn"><i class="ri-delete-bin-line"></i>Delete</button></form>
            </x-slot:actions>
        @endcan
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-group-line"></i>{{ \App\Models\FeeInstalmentPlan::APPLIES[$plan->applies_to] }}</span>
            <span class="cb-meta-pill"><i class="ri-{{ $plan->is_active ? 'checkbox-circle' : 'pause-circle' }}-line"></i>{{ $plan->is_active ? 'Active' : 'Off' }}</span>
            @if($plan->results_when_on_track)<span class="cb-meta-pill"><i class="ri-file-chart-line"></i>Results open when on track</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <x-cb.card title="Schedule" icon="ri-calendar-schedule-line" :flush="true">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Instalment</th><th>Due by</th><th class="text-end">Share</th><th class="text-end">On ₦100,000</th></tr></thead>
                    <tbody>
                    @foreach($preview['steps'] as $s)
                        <tr><td>{{ $s['label'] }}</td><td>{{ $s['due_date'] }}</td><td class="text-end">{{ rtrim(rtrim(number_format($s['percent'], 2), '0'), '.') }}%</td><td class="text-end">{{ $money($s['amount']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </x-cb.card>
        </div>
        <div class="col-lg-7">
            @can('Manage instalment-plans')
            <x-cb.card title="Add students by name" icon="ri-user-add-line">
                <form method="POST" action="{{ route('instalment-plans.assign', $plan) }}" id="ipAssign">@csrf
                    <div class="d-flex gap-2 mb-2">
                        <select class="cb-select" id="ipClass" aria-label="Class">
                            <option value="">Choose a class…</option>
                            @foreach($classes as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                        <label class="form-check ms-auto align-self-center mb-0"><input type="checkbox" class="form-check-input" id="ipAll"> <span class="form-check-label small">Tick all</span></label>
                    </div>
                    <div id="ipList" class="border rounded p-2 mb-2" style="max-height:240px;overflow:auto"><div class="small text-muted">Pick a class to list its students.</div></div>
                    <input name="note" class="form-control form-control-sm mb-2" placeholder="Note (optional), e.g. approved by principal">
                    <button class="action-btn btn-go"><i class="ri-add-line"></i>Add ticked students</button>
                    <span class="small text-muted ms-2">A student can be on one plan per term — adding them here moves them off any other.</span>
                </form>
            </x-cb.card>
            @endcan
        </div>
    </div>

    <x-cb.card title="Students on this plan" icon="ri-team-line" :count="$students->total()" :flush="true">
        <form class="cb-toolbar" method="GET">
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Name or admission no">
            <button class="action-btn btn-open"><i class="ri-search-line"></i>Search</button>
        </form>
        @if($students->isEmpty())
            <div class="empty-state"><i class="ri-team-line"></i><h6>No students yet</h6><p>{{ $plan->applies_to === 'selected' ? 'Add students above.' : 'No active students are placed in the chosen classes for this session.' }}</p></div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Student</th><th>Class</th><th class="text-end">Term fees</th><th class="text-end">Paid</th><th class="text-end">Overdue</th><th>Next</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($students as $st)
                        @php $s = $st->sched; @endphp
                        <tr>
                            <td><strong>{{ $st->lastname }} {{ $st->firstname }}</strong><div class="small text-muted">{{ $st->admissionNo }}</div></td>
                            <td class="small">{{ $classes[$st->class_id] ?? '—' }}</td>
                            @if($st->other_plan)
                                <td colspan="5" class="small text-muted">On another plan: {{ $st->other_plan }}</td>
                            @elseif(!$s)
                                <td colspan="5" class="small text-muted">No fee record for this term</td>
                            @else
                                <td class="text-end">{{ $money($s['payable']) }}</td>
                                <td class="text-end text-success">{{ $money($s['paid']) }}</td>
                                <td class="text-end fw-bold {{ $s['overdue'] > 0 ? 'text-danger' : '' }}">{{ $money($s['overdue']) }}</td>
                                <td class="small">{{ $s['next'] ? $money($s['next']['amount']) . ' by ' . $s['next']['due_date'] : '—' }}</td>
                                <td>
                                    @if($s['payable'] <= 0)<span class="status-pill st-muted">No fees</span>
                                    @elseif($s['balance'] <= 0)<span class="status-pill st-paid">Paid</span>
                                    @elseif($s['on_track'])<span class="status-pill st-info">On track</span>
                                    @else<span class="status-pill st-danger">Behind</span>@endif
                                </td>
                            @endif
                            <td class="text-end">
                                @if($st->assigned)
                                    @can('Manage instalment-plans')
                                    <form method="POST" action="{{ route('instalment-plans.unassign', [$plan, $st->id]) }}" onsubmit="return confirm('Remove this student from the plan?')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light" title="Remove" aria-label="Remove"><i class="ri-close-line"></i></button></form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $students->links() }}</div>
        @endif
    </x-cb.card>
</div>
</div>
</div>

@can('Manage instalment-plans')
<script>
(function () {
    const sel = document.getElementById('ipClass'), list = document.getElementById('ipList'), all = document.getElementById('ipAll');
    const url = @json(route('instalment-plans.students', $plan));
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    sel.addEventListener('change', async () => {
        if (!sel.value) { list.innerHTML = '<div class="small text-muted">Pick a class to list its students.</div>'; return; }
        list.innerHTML = '<div class="small text-muted">Loading…</div>';
        try {
            const r = await fetch(url + '?class_id=' + encodeURIComponent(sel.value), {headers: {'Accept': 'application/json'}});
            const rows = await r.json();
            list.innerHTML = rows.length ? rows.map(s => `<label class="form-check d-flex gap-2"><input class="form-check-input ip-st" type="checkbox" name="student_ids[]" value="${s.id}" ${s.on ? 'checked disabled' : ''}>
                <span class="form-check-label">${esc(s.name)} <small class="text-muted">${esc(s.adm)}</small>${s.on ? ' <span class="status-pill st-info">on plan</span>' : ''}</span></label>`).join('')
                : '<div class="small text-muted">No active students in this class for the session.</div>';
            all.checked = false;
        } catch (e) { list.innerHTML = '<div class="small text-danger">Could not load students.</div>'; }
    });
    all.addEventListener('change', () => list.querySelectorAll('.ip-st:not(:disabled)').forEach(c => c.checked = all.checked));
})();
</script>
@endcan
@endsection
