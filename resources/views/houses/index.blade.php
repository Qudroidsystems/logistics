{{-- resources/views/houses/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $colour = fn ($c) => preg_match('/^#[0-9A-Fa-f]{3,8}$|^[a-zA-Z]{3,20}$|^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/', trim((string) $c)) ? trim($c) : '#94a3b8';
    $ranked = $summary->sortByDesc('points')->values();
    $maxPts = max(1, $ranked->max('points'));
    $totalMembers = $summary->sum('members');
    $houseNames = $summary->pluck('house', 'id');
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="School Houses" icon="ri-home-heart-line" subtitle="House members, captains, balanced placement and the house points table.">
        <x-slot:actions>
            @can('View schoolhouse')<a href="{{ route('schoolhouse.index') }}" class="cb-hero-btn"><i class="ri-add-line"></i>Create / edit houses</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <form class="cb-card mb-3" method="GET"><div class="cb-toolbar">
        <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
            @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->id == $sessionId)>{{ $s->session }}</option>@endforeach
        </select>
        <span class="small text-muted">{{ number_format($totalMembers) }} students in houses · {{ number_format($unassigned) }} without a house</span>
    </div></form>

    {{-- Auto-assign --}}
    @can('Update schoolhouse')
        @if($plan && $plan['session_id'] == $sessionId)
            <div class="cb-banner info"><i class="ri-shuffle-line"></i>
                <div class="flex-grow-1"><strong>Proposed placement for {{ count($plan['plan']) }} student(s):</strong>
                    @foreach($plan['per_house'] as $hid => $n) <span class="status-pill st-info">{{ $houseNames[$hid] ?? '#' . $hid }}: +{{ $n }}</span> @endforeach
                    <div class="small">Each class and gender is spread evenly and siblings are kept in the same house. Nobody who already has a house is moved.</div>
                    <form method="POST" action="{{ route('houses.auto.apply') }}" class="d-inline">@csrf <button class="action-btn btn-go mt-2"><i class="ri-check-line"></i>Apply placement</button></form>
                    <a href="{{ route('houses.index', ['session_id' => $sessionId]) }}" class="action-btn btn-open mt-2">Discard</a>
                </div>
            </div>
        @elseif($unassigned > 0 && $summary->where('active', true)->count() > 1)
            <div class="cb-banner warning"><i class="ri-user-unfollow-line"></i>
                <div class="flex-grow-1">{{ $unassigned }} student(s) placed in a class this session have no house.
                    <form method="POST" action="{{ route('houses.auto.preview') }}?session_id={{ $sessionId }}" class="d-inline">@csrf <button class="action-btn btn-open ms-2"><i class="ri-shuffle-line"></i>Place them automatically…</button></form></div>
            </div>
        @endif
    @endcan

    {{-- House cards --}}
    <div class="row g-3 mb-3">
        @foreach($summary as $h)
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('houses.show', [$h->id, 'session_id' => $sessionId]) }}" class="cb-card d-block h-100 text-reset text-decoration-none hs-card {{ $h->active ? '' : 'opacity-50' }}" style="--hc: {{ $colour($h->housecolour) }}">
                    <div class="d-flex align-items-center gap-2 mb-2"><span class="hs-dot"></span><h5 class="mb-0">{{ $h->house }}</h5></div>
                    @if(!empty($h->motto))<div class="small fst-italic text-muted mb-2">“{{ $h->motto }}”</div>@endif
                    <div class="row g-2 small">
                        <div class="col-6"><div class="text-muted">Members</div><strong class="fs-5">{{ $h->members }}</strong></div>
                        <div class="col-6"><div class="text-muted">Points</div><strong class="fs-5">{{ number_format($h->points) }}</strong></div>
                        <div class="col-6"><div class="text-muted">Boys / girls</div>{{ $h->boys }} / {{ $h->girls }}</div>
                        <div class="col-6"><div class="text-muted">House master</div>{{ $h->master_name ?? '—' }}</div>
                        <div class="col-6"><div class="text-muted">Patron</div>{{ $h->patron_name ?? '—' }}</div>
                        <div class="col-6"><div class="text-muted">Assistant</div>{{ $h->assistant_name ?? '—' }}</div>
                    </div>
                    @if(!empty($h->mascot) || !empty($h->meeting_place))<div class="small text-muted mt-2">{{ collect([$h->mascot ? 'Mascot: ' . $h->mascot : null, $h->meeting_place ? 'Meets at ' . $h->meeting_place : null])->filter()->implode(' · ') }}</div>@endif
                    @if($h->captain)<div class="small mt-2"><i class="ri-star-line"></i> Captain: {{ $h->captain }}</div>@endif
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="House points table" icon="ri-trophy-line">
                @if($ranked->isEmpty())
                    <div class="empty-state"><i class="ri-home-heart-line"></i><h6>No houses yet</h6></div>
                @else
                    @foreach($ranked as $i => $h)
                        @php $cat = $byCategory[$h->id] ?? collect(); @endphp
                        <div class="d-flex align-items-center gap-3 mb-2" style="--hc: {{ $colour($h->housecolour) }}"
                             title="{{ collect(\App\Services\Houses\HouseService::CATEGORIES)->map(fn ($l, $k) => $l . ': ' . (int) ($cat[$k] ?? 0))->implode(' · ') }}">
                            <span class="fw-bold text-muted" style="width:20px">{{ $i + 1 }}</span>
                            <span style="width:110px" class="text-truncate">{{ $h->house }}</span>
                            <div class="flex-grow-1 hs-track"><div class="hs-fill" style="width:{{ $h->points > 0 ? max(2, round($h->points / $maxPts * 100)) : 0 }}%"></div></div>
                            <strong style="width:60px" class="text-end">{{ number_format($h->points) }}</strong>
                        </div>
                    @endforeach
                    <div class="small text-muted mt-2">Hover a row to see points by category.</div>
                @endif
            </x-cb.card>

            <x-cb.card title="Recent points" icon="ri-history-line" :flush="true">
                @if($log->isEmpty())
                    <div class="empty-state"><i class="ri-trophy-line"></i><h6>No points yet this session</h6></div>
                @else
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Date</th><th>House</th><th class="text-end">Points</th><th>Reason</th><th></th></tr></thead>
                        <tbody>
                        @foreach($log as $p)
                            <tr>
                                <td class="small text-nowrap">{{ \Carbon\Carbon::parse($p->event_date)->format('d M') }}</td>
                                <td style="--hc: {{ $colour($p->housecolour) }}"><span class="hs-dot me-1"></span>{{ $p->house }}</td>
                                <td class="text-end fw-bold {{ $p->points < 0 ? 'text-danger' : 'text-success' }}">{{ $p->points > 0 ? '+' : '' }}{{ $p->points }}</td>
                                <td class="small">{{ $p->reason }} <span class="text-muted">· {{ \App\Services\Houses\HouseService::CATEGORIES[$p->category] ?? $p->category }}{{ $p->student_name ? ' · ' . $p->student_name : '' }}{{ $p->by_name ? ' · by ' . $p->by_name : '' }}</span></td>
                                <td class="text-end">
                                    @canany(['Award house points', 'Update schoolhouse'])
                                        <form method="POST" action="{{ route('houses.points.delete', $p->id) }}" onsubmit="return confirm('Remove this entry?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light" aria-label="Remove"><i class="ri-close-line"></i></button></form>
                                    @endcanany
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>

        <div class="col-lg-5">
            @canany(['Award house points', 'Update schoolhouse'])
                <x-cb.card title="Award or deduct points" icon="ri-add-circle-line">
                    <form method="POST" action="{{ route('houses.points.award') }}?session_id={{ $sessionId }}">@csrf
                        <select name="house_id" class="form-select form-select-sm mb-2" required aria-label="House">
                            <option value="">Choose house…</option>
                            @foreach($summary->where('active', true) as $h)<option value="{{ $h->id }}">{{ $h->house }}</option>@endforeach
                        </select>
                        <div class="row g-2 mb-2">
                            <div class="col-5"><input name="points" type="number" class="form-control form-control-sm" placeholder="e.g. 10 or -5" required aria-label="Points"></div>
                            <div class="col-7"><select name="category" class="form-select form-select-sm" aria-label="Category">
                                @foreach(\App\Services\Houses\HouseService::CATEGORIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                            </select></div>
                        </div>
                        <input name="reason" class="form-control form-control-sm mb-2" placeholder="Reason, e.g. 1st place — inter-house 100m" required maxlength="255">
                        <input name="event_date" type="date" class="form-control form-control-sm mb-2" value="{{ now()->toDateString() }}" aria-label="Date">
                        <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save</button>
                    </form>
                </x-cb.card>
            @endcanany
        </div>
    </div>
</div>
</div>
</div>
<style>
.hs-card{border-top:4px solid var(--hc);transition:transform .15s, box-shadow .15s}.hs-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.08)}
.hs-dot{display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--hc);box-shadow:0 0 0 2px var(--bs-body-bg,#fff), 0 0 0 3px rgba(0,0,0,.12)}
.hs-track{height:14px;background:rgba(148,163,184,.18);border-radius:7px;overflow:hidden}.hs-fill{height:100%;background:var(--hc);border-radius:7px}
</style>
@endsection
