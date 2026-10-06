{{-- resources/views/activities/index.blade.php (clubs & sports) --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$c['plural'] . ' & Members'" :icon="$c['icon']" :subtitle="'Students can join as many ' . strtolower($c['plural']) . ' as they like. Membership is kept per session.'">
        <x-slot:actions>
            <a href="{{ route('activities.student') }}" class="cb-hero-btn"><i class="ri-user-settings-line"></i>By student</a>
            <a href="{{ route($type . '.index') }}" class="cb-hero-btn"><i class="ri-add-line"></i>Create / edit {{ strtolower($c['plural']) }}</a>
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6"><x-cb.stat :label="$c['plural']" :value="$items->count()" :icon="$c['icon']" accent="teal" /></div>
        <div class="col-md-4 col-6"><x-cb.stat label="Memberships" :value="number_format($totalMembers)" icon="ri-links-line" accent="violet" /></div>
        <div class="col-md-4 col-6"><x-cb.stat label="Students involved" :value="number_format($uniqueStudents)" icon="ri-user-star-line" accent="green" /></div>
    </div>

    <x-cb.card :title="$c['plural']" :icon="$c['icon']" :count="$items->count()" :flush="true">
        <form class="cb-toolbar gap-2" method="GET">
            <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->id == $sessionId)>{{ $s->session }}</option>@endforeach
            </select>
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Search {{ strtolower($c['plural']) }}">
        </form>
        @if($items->isEmpty())
            <div class="empty-state"><i class="{{ $c['icon'] }}"></i><h6>No {{ strtolower($c['plural']) }} yet</h6><p>Create them on the {{ $c['plural'] }} page first.</p></div>
        @else
            <div class="row g-3 p-3">
                @foreach($items as $i)
                    <div class="col-xl-3 col-lg-4 col-sm-6">
                        <a href="{{ route('activities.show', [$type, $i->id, 'session_id' => $sessionId]) }}" class="cb-card d-block h-100 text-reset text-decoration-none ac-card {{ $i->active ? '' : 'opacity-50' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">{{ $i->name }}</h6>
                                @unless($i->active)<span class="status-pill st-muted">Inactive</span>@endunless
                            </div>
                            <div class="small text-muted mb-2">{{ $c['lead_label'] }}: {{ $i->lead_name ?? '—' }}</div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div><span class="fs-4 fw-bold">{{ $i->members }}</span><span class="small text-muted"> member{{ $i->members === 1 ? '' : 's' }}{{ !empty($i->capacity) ? ' / ' . $i->capacity : '' }}</span></div>
                                @if(!empty($i->meeting_day))<span class="small text-muted"><i class="ri-calendar-line"></i> {{ $i->meeting_day }}{{ $i->meeting_time ? ' ' . $i->meeting_time : '' }}</span>@endif
                            </div>
                            @if(!empty($i->capacity))
                                <div class="progress-track mt-2"><div class="progress-fill" style="width:{{ min(100, round($i->members / max(1, $i->capacity) * 100)) }}%"></div></div>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
<style>.ac-card{transition:transform .15s, box-shadow .15s}.ac-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.08)}</style>
@endsection
