{{-- resources/views/activities/show.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$act->name" :icon="$c['icon']" :subtitle="$c['lead_label'] . ': ' . ($act->lead_name ?? '—') . ($act->venue ? ' · ' . $act->venue : '')"
               :back="route('activities.index', [$type, 'session_id' => $sessionId])" :back-label="$c['plural']">
        <x-slot:actions>
            <a href="{{ route('activities.export', [$type, $act->id, 'session_id' => $sessionId]) }}" class="cb-hero-btn"><i class="ri-download-2-line"></i>Export CSV</a>
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-group-line"></i>{{ $members->count() }}{{ !empty($act->capacity) ? ' / ' . $act->capacity : '' }} members</span>
            @if(!empty($act->meeting_day))<span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $act->meeting_day }} {{ $act->meeting_time }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Members" icon="ri-team-line" :count="$members->count()" :flush="true">
                <form class="cb-toolbar" method="GET">
                    <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
                        @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->id == $sessionId)>{{ $s->session }}</option>@endforeach
                    </select>
                </form>
                @if($members->isEmpty())
                    <div class="empty-state"><i class="ri-user-add-line"></i><h6>No members this session</h6><p>Add students on the right.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Student</th><th>Class</th><th>Role</th>@if($type === 'sport' && $teams->isNotEmpty())<th>Team</th>@endif<th>Joined</th><th></th></tr></thead>
                            <tbody>
                            @foreach($members as $m)
                                <tr>
                                    <td><strong>{{ $m->lastname }} {{ $m->firstname }}</strong><div class="small text-muted">{{ $m->admissionNo }}</div></td>
                                    <td class="small">{{ $m->class_name ?: '—' }}</td>
                                    @if($canManage)
                                        <td colspan="{{ $type === 'sport' && $teams->isNotEmpty() ? 2 : 1 }}">
                                            <form method="POST" action="{{ route('activities.member.update', [$type, $m->id]) }}" class="d-flex gap-1">@csrf @method('PUT')
                                                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Role">
                                                    @foreach($c['roles'] as $k => $l)<option value="{{ $k }}" @selected($m->role === $k)>{{ $l }}</option>@endforeach
                                                </select>
                                                @if($type === 'sport' && $teams->isNotEmpty())
                                                    <select name="team_id" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Team">
                                                        <option value="">No team</option>
                                                        @foreach($teams as $tid => $tn)<option value="{{ $tid }}" @selected($m->team_id == $tid)>{{ $tn }}</option>@endforeach
                                                    </select>
                                                @endif
                                            </form>
                                        </td>
                                    @else
                                        <td>{{ $c['roles'][$m->role] ?? $m->role }}</td>
                                        @if($type === 'sport' && $teams->isNotEmpty())<td>{{ $m->team_name ?? '—' }}</td>@endif
                                    @endif
                                    <td class="small text-muted">{{ $m->joined_on ? \Carbon\Carbon::parse($m->joined_on)->format('d M Y') : '—' }}</td>
                                    <td class="text-end">
                                        @if($canManage)
                                            <form method="POST" action="{{ route('activities.member.remove', [$type, $m->id]) }}" onsubmit="return confirm('Remove {{ addslashes($m->firstname) }}?')">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-light" aria-label="Remove"><i class="ri-close-line"></i></button></form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-cb.card>
        </div>

        <div class="col-xl-4">
            @if($canManage)
                <x-cb.card title="Add students" icon="ri-user-add-line">
                    <form method="POST" action="{{ route('activities.add', [$type, $act->id]) }}?session_id={{ $sessionId }}">@csrf
                        <select class="cb-select w-100 mb-2" id="acClass" aria-label="Class">
                            <option value="">Choose a class…</option>
                            @foreach($classes as $cid => $cn)<option value="{{ $cid }}">{{ $cn }}</option>@endforeach
                        </select>
                        <label class="form-check small mb-1"><input type="checkbox" class="form-check-input" id="acAll"> <span class="form-check-label">Tick all</span></label>
                        <div id="acList" class="border rounded p-2 mb-2" style="max-height:280px;overflow:auto"><div class="small text-muted">Pick a class to list its students.</div></div>
                        <select name="role" class="form-select form-select-sm mb-2" aria-label="Role">
                            @foreach($c['roles'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                        <button class="action-btn btn-go"><i class="ri-add-line"></i>Add ticked students</button>
                    </form>
                </x-cb.card>

                <x-cb.card title="Details" icon="ri-information-line">
                    <form method="POST" action="{{ route('activities.details', [$type, $act->id]) }}">@csrf @method('PUT')
                        <label class="form-label small">{{ $c['lead_label'] }}</label>
                        <select name="lead_id" class="form-select form-select-sm mb-2">
                            @foreach($staff as $sid => $sn)<option value="{{ $sid }}" @selected($act->{$c['lead']} == $sid)>{{ $sn }}</option>@endforeach
                        </select>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input name="meeting_day" class="form-control form-control-sm" value="{{ $act->meeting_day ?? '' }}" placeholder="Meeting day" aria-label="Meeting day"></div>
                            <div class="col-6"><input name="meeting_time" class="form-control form-control-sm" value="{{ $act->meeting_time ?? '' }}" placeholder="Time, e.g. 2:30pm" aria-label="Meeting time"></div>
                            <div class="col-8"><input name="venue" class="form-control form-control-sm" value="{{ $act->venue ?? '' }}" placeholder="Venue" aria-label="Venue"></div>
                            <div class="col-4"><input name="capacity" type="number" min="1" class="form-control form-control-sm" value="{{ $act->capacity ?? '' }}" placeholder="Max" aria-label="Capacity"></div>
                        </div>
                        <textarea name="description" class="form-control form-control-sm mb-2" rows="2" placeholder="Description">{{ $act->description }}</textarea>
                        <label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(!isset($act->is_active) || $act->is_active)> <span class="form-check-label">Active (students can join)</span></label>
                        <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save</button>
                    </form>
                </x-cb.card>

                @if($type === 'sport')
                    <x-cb.card title="Teams" icon="ri-shield-star-line">
                        @forelse($teams as $tn)<span class="term-chip me-1 mb-1 d-inline-block">{{ $tn }}</span>@empty<div class="small text-muted mb-2">No teams yet (e.g. Junior, Senior, Girls).</div>@endforelse
                        <form method="POST" action="{{ route('activities.team', $act->id) }}" class="d-flex gap-2 mt-2">@csrf
                            <input name="team_name" class="form-control form-control-sm" placeholder="New team name" required aria-label="Team name">
                            <button class="action-btn btn-open"><i class="ri-add-line"></i></button>
                        </form>
                    </x-cb.card>
                @endif
            @endif
        </div>
    </div>
</div>
</div>
</div>

@if($canManage)
<script>
(function () {
    const sel = document.getElementById('acClass'), list = document.getElementById('acList'), all = document.getElementById('acAll');
    const url = @json(route('activities.class-students', [$type, $act->id])) + '?session_id={{ $sessionId }}';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    sel.addEventListener('change', async () => {
        if (!sel.value) { list.innerHTML = '<div class="small text-muted">Pick a class to list its students.</div>'; return; }
        list.innerHTML = '<div class="small text-muted">Loading…</div>';
        try {
            const rows = await (await fetch(url + '&class_id=' + encodeURIComponent(sel.value), {headers: {'Accept': 'application/json'}})).json();
            list.innerHTML = rows.length ? rows.map(s => `<label class="form-check"><input class="form-check-input ac-st" type="checkbox" name="student_ids[]" value="${s.id}" ${s.on ? 'checked disabled' : ''}>
                <span class="form-check-label">${esc(s.name)} <small class="text-muted">${esc(s.adm)}</small>${s.on ? ' <span class="status-pill st-info">member</span>' : ''}</span></label>`).join('')
                : '<div class="small text-muted">No active students in this class.</div>';
            all.checked = false;
        } catch (e) { list.innerHTML = '<div class="small text-danger">Could not load students.</div>'; }
    });
    all.addEventListener('change', () => list.querySelectorAll('.ac-st:not(:disabled)').forEach(c => c.checked = all.checked));
})();
</script>
@endif
@endsection
