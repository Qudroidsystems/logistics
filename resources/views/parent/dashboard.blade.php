{{-- resources/views/parent/dashboard.blade.php --}}
@extends('layouts.master')

@section('content')
@php $naira = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Welcome, ' . $user->name" icon="ri-parent-line" subtitle="Results, fees, attendance and school notices for your children." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    @if($cards->isEmpty())
        <div class="cb-card"><div class="empty-state"><i class="ri-user-search-line"></i><h6>No children linked yet</h6>
            <p>Your phone number is not on any active student's record. Please contact the school office.</p></div></div>
    @else
        <div class="row g-3 mb-3">
            @foreach($cards as $c)
                <div class="col-xl-4 col-md-6">
                    <div class="cb-card h-100 pp-child">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if($c->photo)
                                <img src="{{ $c->photo }}" alt="" class="rounded-circle" width="56" height="56" style="object-fit:cover" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'pp-initial',textContent:'{{ strtoupper(substr($c->firstname, 0, 1)) }}'}))">
                            @else
                                <span class="pp-initial">{{ strtoupper(substr($c->firstname, 0, 1)) }}</span>
                            @endif
                            <div>
                                <h5 class="mb-0">{{ $c->firstname }} {{ $c->lastname }}</h5>
                                <div class="small text-muted">{{ $c->class_name ?: 'No class' }} · {{ $c->admissionNo }}</div>
                            </div>
                        </div>
                        <div class="row g-2 small mb-3">
                            <div class="col-4"><div class="text-muted">Fees owed</div>
                                <strong class="{{ ($c->balance ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ $c->balance === null ? '—' : $naira($c->balance) }}</strong></div>
                            <div class="col-4"><div class="text-muted">Attendance</div>
                                <strong>{{ $c->attendance === null ? '—' : $c->attendance . '%' }}</strong>
                                @if($c->absent)<div class="text-danger">{{ $c->absent }} absent</div>@endif</div>
                            <div class="col-4"><div class="text-muted">Latest result</div><strong>{{ $c->latest ?? '—' }}</strong></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('parent.results', $c->id) }}" class="action-btn btn-open"><i class="ri-file-chart-line"></i>Results</a>
                            <a href="{{ route('parent.fees', $c->id) }}" class="action-btn btn-open"><i class="ri-wallet-3-line"></i>Fees</a>
                            <a href="{{ route('parent.attendance', $c->id) }}" class="action-btn btn-open"><i class="ri-calendar-check-line"></i>Attendance</a>
                            <a href="{{ route('parent.timetable', $c->id) }}" class="action-btn btn-open"><i class="ri-time-line"></i>Timetable</a>
                            @if(($c->balance ?? 0) > 0)
                                <a href="{{ route('parent.pay', $c->id) }}" class="action-btn btn-primary-cb"><i class="ri-secure-payment-line"></i>Pay now</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-cb.card title="Latest notices" icon="ri-notification-3-line" :flush="true">
        <x-slot:tools><a href="{{ route('notifications.index') }}" class="action-btn btn-open">All notices</a></x-slot:tools>
        @if($notices->isEmpty())
            <div class="empty-state"><i class="ri-notification-off-line"></i><h6>No notices yet</h6></div>
        @else
            <div class="list-group list-group-flush">
                @foreach($notices as $n)
                    <a href="{{ route('notifications.open', $n->id) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                        <span class="pn-icon"><i class="{{ $n->data['icon'] ?? 'ri-notification-3-line' }}"></i></span>
                        <span class="flex-grow-1">
                            <span class="d-flex justify-content-between gap-2"><strong>{{ $n->data['title'] ?? 'Notice' }}</strong><small class="text-muted text-nowrap">{{ $n->created_at->diffForHumans() }}</small></span>
                            <span class="d-block small text-muted text-truncate">{{ \Illuminate\Support\Str::limit($n->data['body'] ?? '', 140) }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
<style>
.pp-initial{width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:var(--cb-teal-soft,#e6f6f4);color:var(--cb-teal,#0f766e);font-weight:700;font-size:1.3rem}
.pn-icon{width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:rgba(15,118,110,.1);color:#0f766e;flex-shrink:0}
</style>
@endsection
