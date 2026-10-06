{{-- resources/views/activities/student.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $myClubs  = $mine ? $mine['club']->pluck('activity_id')->map(fn ($v) => (int) $v)->all() : [];
    $mySports = $mine ? $mine['sport']->pluck('activity_id')->map(fn ($v) => (int) $v)->all() : [];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Clubs & Sports by Student" icon="ri-user-settings-line" subtitle="Find a student and tick every club and sport they belong to.">
        <x-slot:actions>
            @can('View club')<a href="{{ route('activities.index', 'club') }}" class="cb-hero-btn"><i class="ri-team-line"></i>Clubs</a>@endcan
            @can('View sport')<a href="{{ route('activities.index', 'sport') }}" class="cb-hero-btn"><i class="ri-basketball-line"></i>Sports</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="cb-card mb-3">
        <form method="GET" class="cb-toolbar gap-2 position-relative" id="acFind">
            <select name="session_id" class="cb-select" aria-label="Session" onchange="this.form.submit()">
                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->id == $sessionId)>{{ $s->session }}</option>@endforeach
            </select>
            <input type="hidden" name="student_id" id="acSid" value="{{ $student->id ?? '' }}">
            <div class="flex-grow-1 position-relative">
                <input type="search" class="cb-search w-100" id="acQ" placeholder="Type a name or admission number…" autocomplete="off"
                       value="{{ $student ? trim($student->lastname . ' ' . $student->firstname) . ' (' . $student->admissionNo . ')' : '' }}">
                <div class="list-group position-absolute w-100 shadow-sm" id="acRes" style="z-index:20;display:none"></div>
            </div>
        </form>
    </div>

    @if($student)
        <form method="POST" action="{{ route('activities.student.save') }}?session_id={{ $sessionId }}">@csrf
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <div class="row g-3">
                <div class="col-lg-6">
                    <x-cb.card title="Clubs" icon="ri-team-line" :count="count($myClubs)">
                        @forelse($clubs as $cl)
                            <label class="form-check mb-1"><input class="form-check-input" type="checkbox" name="clubs[]" value="{{ $cl->id }}" @checked(in_array((int) $cl->id, $myClubs, true)) @cannot('Update club') disabled @endcannot>
                                <span class="form-check-label">{{ $cl->name }} <small class="text-muted">{{ $cl->members }}{{ !empty($cl->capacity) ? '/' . $cl->capacity : '' }}</small></span></label>
                        @empty <div class="small text-muted">No active clubs.</div> @endforelse
                    </x-cb.card>
                </div>
                <div class="col-lg-6">
                    <x-cb.card title="Sports" icon="ri-basketball-line" :count="count($mySports)">
                        @forelse($sports as $sp)
                            <label class="form-check mb-1"><input class="form-check-input" type="checkbox" name="sports[]" value="{{ $sp->id }}" @checked(in_array((int) $sp->id, $mySports, true)) @cannot('Update sport') disabled @endcannot>
                                <span class="form-check-label">{{ $sp->name }} <small class="text-muted">{{ $sp->members }}{{ !empty($sp->capacity) ? '/' . $sp->capacity : '' }}</small></span></label>
                        @empty <div class="small text-muted">No active sports.</div> @endforelse
                    </x-cb.card>
                </div>
            </div>
            @if(auth()->user()->can('Update club') || auth()->user()->can('Update sport'))
                <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save for {{ $student->firstname }}</button>
            @endif
        </form>
    @endif
</div>
</div>
</div>
<script>
(function () {
    const q = document.getElementById('acQ'), res = document.getElementById('acRes'), sid = document.getElementById('acSid'), form = document.getElementById('acFind');
    const url = @json(route('activities.students.search'));
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let t;
    q.addEventListener('input', () => {
        clearTimeout(t);
        if (q.value.trim().length < 2) { res.style.display = 'none'; return; }
        t = setTimeout(async () => {
            const rows = await (await fetch(url + '?q=' + encodeURIComponent(q.value.trim()), {headers: {'Accept': 'application/json'}})).json();
            res.innerHTML = rows.map(r => `<button type="button" class="list-group-item list-group-item-action" data-id="${r.id}">${esc(r.text)}</button>`).join('') || '<div class="list-group-item small text-muted">No match</div>';
            res.style.display = '';
        }, 250);
    });
    res.addEventListener('click', e => { const b = e.target.closest('[data-id]'); if (b) { sid.value = b.dataset.id; form.submit(); } });
    document.addEventListener('click', e => { if (!form.contains(e.target)) res.style.display = 'none'; });
})();
</script>
@endsection
