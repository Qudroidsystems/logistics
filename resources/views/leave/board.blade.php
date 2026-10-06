{{-- resources/views/leave/board.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $prev = $anchor->copy()->subMonth()->format('Y-m');
    $next = $anchor->copy()->addMonth()->format('Y-m');
    $show = request('show', 'all');
    $today = \Carbon\Carbon::today();
    $q = fn($extra) => array_merge(request()->query(), $extra);
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Who's Away" icon="ri-team-line" subtitle="A single view of every approved absence — staff and students — for today and across the month.">
        <x-slot name="actions">
            @can('View leave records')<a href="{{ route('leave.records') }}" class="action-btn btn-go"><i class="ri-briefcase-line"></i>Staff records</a>@endcan
            @can('View student leave records')<a href="{{ route('student-leave.records') }}" class="action-btn btn-go"><i class="ri-user-line"></i>Student records</a>@endcan
        </x-slot>
    </x-cb.hero>

    <div class="row g-3 mb-3">
        @if($staff)<div class="col-6 col-lg-3"><x-cb.stat label="Staff away today" :value="$stats['staff_today']" icon="ri-briefcase-line" accent="warning" /></div>@endif
        @if($students)<div class="col-6 col-lg-3"><x-cb.stat label="Students away today" :value="$stats['student_today']" icon="ri-user-unfollow-line" accent="info" /></div>@endif
        <div class="col-6 col-lg-3"><x-cb.stat label="Returning / starting (7 days)" :value="$stats['upcoming']" icon="ri-calendar-todo-line" accent="success" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Away today (total)" :value="$todayEvents->count()" icon="ri-team-line" /></div>
    </div>

    {{-- Scope filter --}}
    @if(auth()->user()->can('View leave records') || auth()->user()->can('Approve leave'))
    @if(auth()->user()->can('View student leave records') || auth()->user()->can('Approve student leave'))
    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="{{ route('leave.board', $q(['show'=>'all'])) }}" class="action-btn {{ $show==='all'?'btn-primary-cb':'btn-open' }}"><i class="ri-apps-line"></i>Everyone</a>
        <a href="{{ route('leave.board', $q(['show'=>'staff'])) }}" class="action-btn {{ $show==='staff'?'btn-primary-cb':'btn-open' }}"><i class="ri-briefcase-line"></i>Staff only</a>
        <a href="{{ route('leave.board', $q(['show'=>'students'])) }}" class="action-btn {{ $show==='students'?'btn-primary-cb':'btn-open' }}"><i class="ri-user-line"></i>Students only</a>
    </div>
    @endif
    @endif

    <div class="row g-3">
        {{-- Today + upcoming --}}
        <div class="col-xl-4">
            <x-cb.card title="Away today" icon="ri-calendar-check-line" :count="$todayEvents->count()" :flush="true">
                @if($todayEvents->isEmpty())
                    <div class="empty-state"><i class="ri-emotion-happy-line"></i><h6>Everyone's in</h6><p>No approved absences today.</p></div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($todayEvents as $e)
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <span class="cb-dot" style="background: {{ $e->color }}"></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $e->name }}</div>
                                    <div class="small text-muted">{{ $e->sub }} · {{ $e->type }}</div>
                                </div>
                                <span class="term-chip">till {{ $e->end->format('d M') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-cb.card>

            <x-cb.card title="Next 7 days" icon="ri-calendar-todo-line" :count="$upcoming->count()" :flush="true">
                @if($upcoming->isEmpty())
                    <div class="empty-state"><i class="ri-calendar-line"></i><h6>Nothing upcoming</h6><p>No absences begin in the next week.</p></div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($upcoming as $e)
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <span class="cb-dot" style="background: {{ $e->color }}"></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $e->name }}</div>
                                    <div class="small text-muted">{{ $e->sub }} · {{ $e->type }}</div>
                                </div>
                                <span class="term-chip">{{ $e->start->format('d M') }}–{{ $e->end->format('d M') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-cb.card>
        </div>

        {{-- Month calendar --}}
        <div class="col-xl-8">
            <x-cb.card title="{{ $anchor->format('F Y') }}" icon="ri-calendar-2-line">
                <x-slot name="tools">
                    <a href="{{ route('leave.board', $q(['month'=>$prev])) }}" class="action-btn btn-open" title="Previous month"><i class="ri-arrow-left-s-line"></i></a>
                    <a href="{{ route('leave.board', $q(['month'=>now()->format('Y-m')])) }}" class="action-btn btn-open">Today</a>
                    <a href="{{ route('leave.board', $q(['month'=>$next])) }}" class="action-btn btn-open" title="Next month"><i class="ri-arrow-right-s-line"></i></a>
                </x-slot>

                <div class="cb-cal">
                    <div class="cb-cal-head">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)<div>{{ $d }}</div>@endforeach
                    </div>
                    @foreach($weeks as $week)
                        <div class="cb-cal-row">
                            @foreach($week as $cell)
                                @php $isToday = $cell->date->isSameDay($today); @endphp
                                <div class="cb-cal-cell {{ $cell->inMonth ? '' : 'muted' }} {{ $isToday ? 'today' : '' }}">
                                    <div class="cb-cal-date">{{ $cell->date->day }}</div>
                                    @foreach($cell->events->take(4) as $e)
                                        <div class="cb-cal-ev" style="border-left-color: {{ $e->color }}" title="{{ $e->name }} — {{ $e->type }} ({{ $e->sub }})">
                                            <span class="cb-dot" style="background: {{ $e->color }}"></span>{{ \Illuminate\Support\Str::limit($e->name, 12) }}
                                        </div>
                                    @endforeach
                                    @if($cell->events->count() > 4)<div class="cb-cal-more">+{{ $cell->events->count() - 4 }} more</div>@endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-3 mt-3 small text-muted flex-wrap">
                    @if($staff)<span><span class="cb-dot" style="background:#0f766e"></span> Staff</span>@endif
                    @if($students)<span><span class="cb-dot" style="background:#2563eb"></span> Student</span>@endif
                    <span class="ms-auto">Colours match each leave type.</span>
                </div>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>

@once
<style>
.cb-dot{display:inline-block;width:9px;height:9px;border-radius:50%;flex:0 0 auto;margin-right:2px}
.cb-cal{border:1px solid var(--cb-border,#e5e7eb);border-radius:12px;overflow:hidden}
.cb-cal-head{display:grid;grid-template-columns:repeat(7,1fr);background:var(--cb-soft,#f8fafc);font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.cb-cal-head>div{padding:8px 6px;text-align:center;border-right:1px solid var(--cb-border,#eef2f7)}
.cb-cal-head>div:last-child{border-right:0}
.cb-cal-row{display:grid;grid-template-columns:repeat(7,1fr);border-top:1px solid var(--cb-border,#eef2f7)}
.cb-cal-cell{min-height:96px;padding:5px 5px 8px;border-right:1px solid var(--cb-border,#eef2f7);background:#fff}
.cb-cal-cell:last-child{border-right:0}
.cb-cal-cell.muted{background:#fafbfc}
.cb-cal-cell.muted .cb-cal-date{color:#cbd5e1}
.cb-cal-cell.today{background:#f0fdfa;box-shadow:inset 0 0 0 2px #99f6e4}
.cb-cal-date{font-size:.78rem;font-weight:600;color:#334155;margin-bottom:4px;text-align:right}
.cb-cal-ev{display:flex;align-items:center;font-size:.7rem;line-height:1.2;padding:2px 4px;margin-bottom:2px;border-radius:4px;background:#f8fafc;border-left:3px solid #94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cb-cal-more{font-size:.66rem;color:#64748b;padding:0 4px}
@media (max-width:640px){.cb-cal-cell{min-height:64px}.cb-cal-ev{font-size:.62rem}}
</style>
@endonce
@endsection
