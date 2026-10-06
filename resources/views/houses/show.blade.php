{{-- resources/views/houses/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $colour = preg_match('/^#[0-9A-Fa-f]{3,8}$|^[a-zA-Z]{3,20}$|^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/', trim((string) $h->housecolour)) ? trim($h->housecolour) : '#94a3b8';
    $R = \App\Services\Houses\HouseService::ROLES;
    $canManage = auth()->user()->can('Update schoolhouse');
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid" style="--hc: {{ $colour }}">
    <x-cb.hero :title="$h->house" icon="ri-home-heart-line" :subtitle="'Patron: ' . ($h->patron_name ?? '—') . ' · House master: ' . ($h->master_name ?? '—') . (!empty($h->motto) ? ' · “' . $h->motto . '”' : '')"
               :back="route('houses.index', ['session_id' => $sessionId])" back-label="All houses">
        <x-slot:pills>
            <span class="cb-meta-pill"><span class="hs-dot"></span> {{ $members->count() }} members</span>
            <span class="cb-meta-pill"><i class="ri-trophy-line"></i>{{ number_format($points) }} points</span>
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Members" icon="ri-team-line" :count="$members->count()" :flush="true">
                <form class="cb-toolbar gap-2" method="GET">
                    <input type="hidden" name="session_id" value="{{ $sessionId }}">
                    <select name="class_id" class="cb-select" onchange="this.form.submit()" aria-label="Class">
                        <option value="">All classes</option>
                        @foreach($classes as $cid => $cn)<option value="{{ $cid }}" @selected(request('class_id') == $cid)>{{ $cn }}</option>@endforeach
                    </select>
                    <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Name or admission no">
                </form>
                @if($members->isEmpty())
                    <div class="empty-state"><i class="ri-user-add-line"></i><h6>No members found</h6></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Student</th><th>Class</th><th>Gender</th><th>Role</th>@if($canManage)<th>Move to</th>@endif</tr></thead>
                        <tbody>
                        @foreach($members as $m)
                            <tr>
                                <td><strong>{{ $m->lastname }} {{ $m->firstname }}</strong><div class="small text-muted">{{ $m->admissionNo }}</div></td>
                                <td class="small">{{ $m->class_name ?: '—' }}</td>
                                <td class="small">{{ ucfirst(strtolower((string) $m->gender)) ?: '—' }}</td>
                                <td>
                                    @if($canManage)
                                        <form method="POST" action="{{ route('houses.role', $m->id) }}">@csrf
                                            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Role">
                                                @foreach($R as $k => $l)<option value="{{ $k }}" @selected(($m->role ?? 'member') === $k)>{{ $l }}</option>@endforeach
                                            </select></form>
                                    @else
                                        {{ $R[$m->role ?? 'member'] ?? 'Member' }}
                                    @endif
                                </td>
                                @if($canManage)
                                    <td>
                                        <form method="POST" action="{{ route('houses.move', $m->id) }}?session_id={{ $sessionId }}">@csrf
                                            <select name="house_id" class="form-select form-select-sm" onchange="if(confirm('Move this student?')) this.form.submit()" aria-label="Move to house">
                                                @foreach($houses as $o)<option value="{{ $o->id }}" @selected($o->id == $h->id)>{{ $o->house }}</option>@endforeach
                                            </select></form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>

        <div class="col-xl-4">
            @if($canManage)
                <x-cb.card title="Add students" icon="ri-user-add-line">
                    <form method="POST" action="{{ route('houses.assign', $h->id) }}?session_id={{ $sessionId }}">@csrf
                        <select class="cb-select w-100 mb-2" id="hsClass" aria-label="Class">
                            <option value="">Choose a class…</option>
                            @foreach($classes as $cid => $cn)<option value="{{ $cid }}">{{ $cn }}</option>@endforeach
                        </select>
                        <label class="form-check small mb-1"><input type="checkbox" class="form-check-input" id="hsNoHouse"> <span class="form-check-label">Tick everyone without a house</span></label>
                        <div id="hsList" class="border rounded p-2 mb-2" style="max-height:300px;overflow:auto"><div class="small text-muted">Pick a class to list its students.</div></div>
                        <button class="action-btn btn-go"><i class="ri-add-line"></i>Put ticked students in {{ $h->house }}</button>
                        <div class="small text-muted mt-1">Ticking a student who is in another house moves them here.</div>
                    </form>
                </x-cb.card>

                <x-cb.card title="House details" icon="ri-information-line">
                    <form method="POST" action="{{ route('houses.details', $h->id) }}">@csrf @method('PUT')
                        <label class="form-label small">Patron / Matron</label>
                        <select name="patron_id" class="form-select form-select-sm mb-2"><option value="">—</option>
                            @foreach($staff as $sid => $sn)<option value="{{ $sid }}" @selected(($h->patron_id ?? null) == $sid)>{{ $sn }}</option>@endforeach
                        </select>
                        <label class="form-label small">House master / mistress</label>
                        <select name="housemasterid" class="form-select form-select-sm mb-2">
                            @foreach($staff as $sid => $sn)<option value="{{ $sid }}" @selected($h->housemasterid == $sid)>{{ $sn }}</option>@endforeach
                        </select>
                        <label class="form-label small">Assistant house master</label>
                        <select name="assistant_master_id" class="form-select form-select-sm mb-2"><option value="">—</option>
                            @foreach($staff as $sid => $sn)<option value="{{ $sid }}" @selected(($h->assistant_master_id ?? null) == $sid)>{{ $sn }}</option>@endforeach
                        </select>
                        <div class="row g-2 mb-2">
                            <div class="col-5"><input name="mascot" class="form-control form-control-sm" value="{{ $h->mascot ?? '' }}" placeholder="Mascot" aria-label="Mascot"></div>
                            <div class="col-3"><input name="founded_year" type="number" min="1900" max="2100" class="form-control form-control-sm" value="{{ $h->founded_year ?? '' }}" placeholder="Year" aria-label="Founded"></div>
                            <div class="col-4"><input name="meeting_place" class="form-control form-control-sm" value="{{ $h->meeting_place ?? '' }}" placeholder="Meeting place" aria-label="Meeting place"></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-4"><input type="color" class="form-control form-control-sm form-control-color w-100" name="housecolour" value="{{ str_starts_with($colour, '#') && strlen($colour) === 7 ? $colour : '#0f766e' }}" aria-label="Colour"></div>
                            <div class="col-8"><input name="motto" class="form-control form-control-sm" value="{{ $h->motto ?? '' }}" placeholder="Motto" aria-label="Motto"></div>
                        </div>
                        <textarea name="description" class="form-control form-control-sm mb-2" rows="2" placeholder="About the house">{{ $h->description ?? '' }}</textarea>
                        <label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($h->active)> <span class="form-check-label">Active</span></label>
                        <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save</button>
                    </form>
                </x-cb.card>
            @endif
        </div>
    </div>
</div>
</div>
</div>
<style>.hs-dot{display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--hc);vertical-align:middle}</style>
@if($canManage)
<script>
(function () {
    const sel = document.getElementById('hsClass'), list = document.getElementById('hsList'), none = document.getElementById('hsNoHouse');
    const url = @json(route('houses.class-students')) + '?session_id={{ $sessionId }}';
    const me = {{ (int) $h->id }};
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    sel.addEventListener('change', async () => {
        if (!sel.value) { list.innerHTML = '<div class="small text-muted">Pick a class to list its students.</div>'; return; }
        list.innerHTML = '<div class="small text-muted">Loading…</div>';
        try {
            const rows = await (await fetch(url + '&class_id=' + encodeURIComponent(sel.value), {headers: {'Accept': 'application/json'}})).json();
            list.innerHTML = rows.length ? rows.map(s => `<label class="form-check"><input class="form-check-input hs-st" data-free="${s.house_id ? 0 : 1}" type="checkbox" name="student_ids[]" value="${s.id}" ${s.house_id === me ? 'checked disabled' : ''}>
                <span class="form-check-label">${esc(s.name)} <small class="text-muted">${esc(s.adm)}</small> ${s.house ? `<span class="status-pill ${s.house_id === me ? 'st-info' : 'st-muted'}">${esc(s.house)}</span>` : '<span class="status-pill st-warning">No house</span>'}</span></label>`).join('')
                : '<div class="small text-muted">No active students in this class.</div>';
            none.checked = false;
        } catch (e) { list.innerHTML = '<div class="small text-danger">Could not load students.</div>'; }
    });
    none.addEventListener('change', () => list.querySelectorAll('.hs-st[data-free="1"]').forEach(c => c.checked = none.checked));
})();
</script>
@endif
@endsection
