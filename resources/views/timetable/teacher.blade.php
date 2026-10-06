{{-- resources/views/timetable/teacher.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Page Header --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">
                            <i class="ri-calendar-todo-line me-2"></i>{{ $pagetitle }}
                            <span class="badge bg-success-subtle text-success ms-2" id="liveStatus">
                                <i class="ri-checkbox-circle-fill me-1" style="font-size:8px;"></i> Live
                            </span>
                        </h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">My Timetable</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if($todayHoliday)
            <div class="row">
                <div class="col-12">
                    <div class="alert {{ $todayHoliday->is_full_day ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center gap-2 mb-4 border-0 shadow-sm" role="alert">
                        <i class="ri-calendar-event-fill fs-18"></i>
                        <div>
                            <strong>Today ({{ $todayDayName }}) is a holiday &mdash; {{ $todayHoliday->title }}.</strong>
                            @if($todayHoliday->is_full_day)
                                No classes are expected today.
                            @else
                                Classes are expected to end by {{ \Carbon\Carbon::parse($todayHoliday->cutoff_time)->format('H:i') }} today.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Quick Stats Row --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ri-book-line text-primary fs-20"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Total Classes</h6>
                                    <h4 class="mb-0" id="totalClasses">{{ $slots->flatten()->whereNotNull('subject_id')->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ri-door-line text-success fs-20"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Rooms</h6>
                                    <h4 class="mb-0">{{ $slots->flatten()->whereNotNull('room_id')->unique('room_id')->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ri-school-line text-warning fs-20"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Classes Taught</h6>
                                    <h4 class="mb-0">{{ $slots->flatten()->whereNotNull('subject_id')->pluck('class_full_name')->filter()->unique()->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ri-alert-line text-danger fs-20"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Conflicts</h6>
                                    <h4 class="mb-0 text-danger" id="conflictCount">
                                        {{ $conflictGroups->count() }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{--
                Conflicts Alert
                $conflictGroups comes from the controller, grouped by
                teacher+day+CLOCK-TIME (not period_id) — so a genuine
                double-booking across two different class arms at the same
                time is actually detected and shown here.
            --}}
            @if($conflictGroups->isNotEmpty())
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-alert-line ri-2x me-3 text-danger"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-danger">
                                    <i class="ri-error-warning-line me-1"></i>
                                    Teacher Conflicts Detected!
                                </h6>
                                <p class="mb-1 small">You are scheduled to teach multiple classes at the same time:</p>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($conflictGroups as $key => $group)
                                        @php
                                            $parts = explode('|', $key, 3);
                                            $day = $parts[1] ?? '';
                                            $first = $group->first();
                                            $classes = $group->pluck('class_full_name')->unique()->implode(', ');
                                            $subject = $first->subject;
                                            $periodLabel = $first->period
                                                ? ($first->period->name . ' (' . substr($first->period->start_time, 0, 5) . ' - ' . substr($first->period->end_time, 0, 5) . ')')
                                                : $day;
                                        @endphp
                                        <span class="badge bg-danger p-2">
                                            <i class="ri-time-line me-1"></i>
                                            {{ $day }} · {{ $periodLabel }}
                                            <strong class="mx-1">{{ $subject->subject ?? '' }}</strong>
                                            <span class="text-white-50">→</span>
                                            {{ $classes }}
                                            <span class="text-white-50 ms-1">({{ $group->count() }} classes)</span>
                                        </span>
                                    @endforeach
                                </div>
                                <div class="mt-2 small">
                                    <i class="ri-information-line me-1"></i>
                                    <strong>Solution:</strong> Assign a <strong>shared room</strong> (e.g., "Computer Lab") to all these slots 
                                    or use the <strong>Room Management</strong> module to create combined sessions.
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Subscription & Export Bar --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body d-flex flex-wrap align-items-center gap-3">
                            <div class="flex-grow-1">
                                <i class="ri-rss-line text-primary me-2"></i>
                                <span class="fw-semibold">Sync with Calendar:</span>
                                <span class="text-muted small">Subscribe to automatically get updates</span>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('{{ $webcalUrl }}')">
                                    <i class="ri-link me-1"></i> Copy WebCal Link
                                </button>
                                <a href="{{ $icsUrl }}" class="btn btn-primary btn-sm">
                                    <i class="ri-calendar-download-line me-1"></i> Download .ics
                                </a>
                                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                    <i class="ri-printer-line me-1"></i> Print
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportTeacherTimetable()">
                                    <i class="ri-file-excel-line me-1"></i> Export CSV
                                </button>
                                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#requestSubstituteModal">
                                    <i class="ri-user-star-line me-1"></i> Request Substitute
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Enhanced Filter Section --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="{{ route('timetable.teacher') }}" id="filterForm" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <i class="ri-calendar-2-line me-1"></i>Session
                                    </label>
                                    <select class="form-select" name="session_id" onchange="this.form.submit()">
                                        <option value="">All Sessions</option>
                                        @foreach($sessions as $session)
                                            <option value="{{ $session->id }}" {{ $sessionId == $session->id ? 'selected' : '' }}>
                                                {{ $session->session }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <i class="ri-file-list-3-line me-1"></i>Term
                                    </label>
                                    <select class="form-select" name="term_id" onchange="this.form.submit()">
                                        <option value="">All Terms</option>
                                        @foreach($terms as $term)
                                            <option value="{{ $term->id }}" {{ $termId == $term->id ? 'selected' : '' }}>
                                                {{ $term->term }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <i class="ri-school-line me-1"></i>Class
                                    </label>
                                    <select class="form-select" name="class_id" onchange="this.form.submit()">
                                        <option value="">All Classes</option>
                                        @foreach($teacherClasses as $class)
                                            <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>
                                                {{ $class->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="ri-refresh-line me-2"></i>Refresh
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()" title="Reset Filters">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Filters Badge --}}
            @if($sessionId || $termId || $classId)
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted small">Active Filters:</span>
                        @if($sessionId)
                            <span class="badge bg-primary-subtle text-primary p-2">
                                <i class="ri-calendar-2-line me-1"></i>
                                {{ $sessions->firstWhere('id', $sessionId)->session ?? 'Session' }}
                                <a href="?{{ http_build_query(array_merge(request()->query(), ['session_id' => ''])) }}" class="text-danger ms-1" title="Remove filter">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif
                        @if($termId)
                            <span class="badge bg-info-subtle text-info p-2">
                                <i class="ri-file-list-3-line me-1"></i>
                                {{ $terms->firstWhere('id', $termId)->term ?? 'Term' }}
                                <a href="?{{ http_build_query(array_merge(request()->query(), ['term_id' => ''])) }}" class="text-danger ms-1" title="Remove filter">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif
                        @if($classId)
                            <span class="badge bg-warning-subtle text-warning p-2">
                                <i class="ri-school-line me-1"></i>
                                {{ $teacherClasses->firstWhere('id', $classId)->full_name ?? 'Class' }}
                                <a href="?{{ http_build_query(array_merge(request()->query(), ['class_id' => ''])) }}" class="text-danger ms-1" title="Remove filter">
                                    <i class="ri-close-line"></i>
                                </a>
                            </span>
                        @endif
                        <a href="{{ route('timetable.teacher') }}" class="btn btn-sm btn-outline-danger">
                            <i class="ri-close-circle-line me-1"></i>Clear All
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Teacher Profile Card --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card bg-gradient-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-4">
                                <div class="flex-shrink-0">
                                    @if($teacherPicture)
                                        <img src="{{ $teacherPicture }}" class="rounded-circle border border-3 border-white"
                                             style="width: 80px; height: 80px; object-fit: cover;">
                                    @else
                                        <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center"
                                             style="width: 80px; height: 80px;">
                                            <i class="ri-user-line ri-3x"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="mb-1">{{ Auth::user()->name }}</h3>
                                    <p class="mb-0 opacity-75">
                                        <i class="ri-mail-line me-2"></i>{{ Auth::user()->email }}
                                    </p>
                                    @if($classId)
                                        <span class="badge bg-white text-primary mt-2">
                                            <i class="ri-school-line me-1"></i> Filtered by: {{ $teacherClasses->firstWhere('id', $classId)->full_name ?? '' }}
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-light" onclick="window.print()">
                                        <i class="ri-printer-line me-2"></i>Print
                                    </button>
                                    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#requestSubstituteModal">
                                        <i class="ri-user-star-line me-2"></i>Substitute
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Today's Schedule Alert --}}
            @if(isset($todaySlots) && $todaySlots->whereNotNull('subject_id')->isNotEmpty())
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-calendar-check-line ri-2x me-3 text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Today's Schedule</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($todaySlots->whereNotNull('subject_id') as $slot)
                                        <span class="badge bg-primary-subtle text-primary p-2">
                                            <strong>{{ $slot->subject->subject ?? '—' }}</strong>
                                            <span class="text-muted mx-1">·</span>
                                            {{ $slot->period->name ?? '' }}
                                            <span class="text-muted mx-1">·</span>
                                            {{ $slot->class_full_name ?? '' }}
                                            @if($slot->room) <i class="ri-door-line ms-1"></i>{{ $slot->room->room_name ?? $slot->room }} @endif
                                            @if($slot->is_double) <span class="badge bg-primary ms-1">Double</span> @endif
                                            @if(($slot->combined_count ?? 0) > 1 && !($slot->conflict_count ?? 0))
                                                <span class="badge bg-warning ms-1">Combined</span>
                                            @endif
                                            @if(($slot->conflict_count ?? 0) > 0)
                                                <span class="badge bg-danger ms-1">Conflict</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Upcoming Classes Alert --}}
            @if(count($upcomingSlots) > 0)
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-time-line ri-2x me-3 text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Upcoming Classes (Next 7 Days)</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($upcomingSlots as $slot)
                                        <span class="badge bg-light text-dark p-2 border">
                                            <i class="ri-calendar-event-line me-1"></i>
                                            {{ $slot['date'] ?? $slot['day'] }}
                                            <strong class="mx-1">{{ $slot['subject'] }}</strong>
                                            <span class="text-muted">{{ $slot['class'] }}</span>
                                            @if($slot['room']) <i class="ri-door-line ms-1"></i>{{ $slot['room'] }} @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Weekly Summary Cards --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="ri-bar-chart-2-line me-2 text-primary"></i>Weekly Overview
                            </h5>
                            <span class="badge bg-primary-subtle text-primary">
                                {{ $slots->flatten()->whereNotNull('subject_id')->count() }} total classes
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($weeklySummary as $day => $summary)
                                <div class="col-md-2 col-6">
                                    <div class="card text-center border h-100 {{ date('l') == $day ? 'border-primary shadow-sm' : '' }}">
                                        <div class="card-body py-3">
                                            <h6 class="mb-2">
                                                {{ $day }}
                                                @if(date('l') == $day)
                                                    <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:8px;">Today</span>
                                                @endif
                                            </h6>
                                            <h3 class="mb-2 {{ $summary['count'] > 0 ? 'text-primary' : 'text-muted' }}">
                                                {{ $summary['count'] }}
                                            </h3>
                                            <small class="text-muted">classes</small>
                                            @if($summary['count'] > 0)
                                            <div class="mt-2">
                                                @foreach(array_slice($summary['subjects'], 0, 2) as $subject)
                                                    <span class="badge bg-light text-dark me-1">{{ Str::limit($subject, 10) }}</span>
                                                @endforeach
                                                @if(count($summary['subjects']) > 2)
                                                    <span class="badge bg-light">+{{ count($summary['subjects']) - 2 }}</span>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Timetable Grid --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0">
                                <i class="ri-table-line me-2 text-primary"></i>My Timetable
                            </h5>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="showRoomNumbers" checked onchange="toggleRooms()">
                                    <label class="form-check-label small" for="showRoomNumbers">Show Rooms</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="compactView" onchange="toggleCompact()">
                                    <label class="form-check-label small" for="compactView">Compact View</label>
                                </div>
                                <span class="badge bg-success-subtle text-success">
                                    <i class="ri-check-line me-1"></i> {{ $slots->flatten()->whereNotNull('subject_id')->count() }} classes
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered teacher-timetable mb-0" id="teacherTimetable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 140px; min-width: 120px;">
                                                <div>Period / Time</div>
                                            </th>
                                            @foreach($days as $day)
                                                @php $isHolidayCol = $todayHoliday && $day === $todayDayName; @endphp
                                                <th class="text-center {{ $isHolidayCol ? 'teacher-holiday-col' : '' }}" style="min-width: 120px;">
                                                    <div>{{ $day }}</div>
                                                    <small class="opacity-75" id="dayCount_{{ $day }}">
                                                        {{ ($slots[$day] ?? collect())->whereNotNull('subject_id')->where('is_free', false)->count() }}
                                                    </small>
                                                    @if($isHolidayCol)
                                                        <div class="mt-1">
                                                            <span class="badge bg-danger">
                                                                <i class="ri-flag-fill me-1"></i>Holiday
                                                            </span>
                                                        </div>
                                                    @endif
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allPeriods as $period)
                                        <tr data-period-id="{{ $period->id }}" class="{{ $period->is_break ? 'table-light' : '' }}">
                                            <td class="fw-semibold" style="background: #f8f9fa; vertical-align: middle;">
                                                <div>{{ $period->name }}</div>
                                                <small class="text-muted">{{ substr($period->start_time, 0, 5) }} - {{ substr($period->end_time, 0, 5) }}</small>
                                                @if($period->is_break)
                                                    <span class="badge bg-warning-subtle text-warning ms-1 d-block mt-1">
                                                        <i class="ri-coffee-line me-1"></i>Break
                                                    </span>
                                                @endif
                                            </td>
                                            @foreach($days as $day)
                                                @php
                                                    $slot = $slots[$day] ?? collect();

                                                    // Match by CLOCK TIME, not period_id — every class/arm owns its
                                                    // own TimetablePeriod rows, so the same 09:20–10:00 slot has a
                                                    // different period_id in every class's timetable. Matching by
                                                    // period_id (the old bug) silently dropped any other class the
                                                    // teacher had at the same time from this cell.
                                                    $matches = $slot->filter(function ($s) use ($period) {
                                                        return $s->period
                                                            && substr($s->period->start_time, 0, 5) === substr($period->start_time, 0, 5)
                                                            && substr($s->period->end_time, 0, 5) === substr($period->end_time, 0, 5);
                                                    })->values();

                                                    $classSlots = $matches->filter(fn($s) => !$s->is_free && $s->subject)->values();
                                                    $hasClass = $classSlots->isNotEmpty();
                                                    $cellHasConflict = $classSlots->contains(fn($s) => ($s->conflict_count ?? 0) > 0);
                                                    $cellHasCombined = $classSlots->contains(fn($s) => ($s->combined_count ?? 0) > 1);

                                                    $meta = $periodDayMeta[$period->id][$day] ?? ['applicable' => true, 'effective_type' => $period->type];
                                                    $isApplicable = $meta['applicable'] ?? true;
                                                    $effectiveType = $meta['effective_type'] ?? $period->type;
                                                    $isHolidayCol = $todayHoliday && $day === $todayDayName;

                                                    $tooltipHtml = '';
                                                    if ($hasClass) {
                                                        $tooltipHtml = $classSlots->map(function ($s) {
                                                            $lines = [];
                                                            $lines[] = "<strong class='d-block mb-1'>" . e($s->subject->subject ?? '') . "</strong>";
                                                            $lines[] = "<small>Class: " . e($s->class_full_name ?? '') . "</small><br>";
                                                            if ($s->room) $lines[] = "<small>Room: " . e($s->room->room_name ?? '') . "</small><br>";
                                                            if ($s->is_double) $lines[] = "<small class='text-primary'>Double Period</small><br>";
                                                            if (($s->conflict_count ?? 0) > 0) {
                                                                $lines[] = "<small class='text-danger'>⚠️ Teacher Conflict</small><br>";
                                                            } elseif (($s->combined_count ?? 0) > 1) {
                                                                $lines[] = "<small class='text-warning'>Combined Session</small><br>";
                                                            }
                                                            if ($s->notes) $lines[] = "<small>Note: " . e(Str::limit($s->notes, 50)) . "</small>";
                                                            return implode('', $lines);
                                                        })->implode('<hr class="my-1">');
                                                    }
                                                @endphp
                                                <td class="timetable-cell text-center align-middle {{ $period->is_break || $effectiveType === 'assembly' ? 'bg-light' : '' }} {{ $isHolidayCol ? 'teacher-holiday-col' : '' }}"
                                                    style="{{ $cellHasCombined && !$cellHasConflict ? 'background: rgba(245, 158, 11, 0.08);' : '' }}{{ $cellHasConflict ? 'border-left: 3px solid #ef4444;' : '' }}"
                                                    @if($hasClass)
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="top"
                                                        title="<div class='text-start' style='max-width:280px;'>{!! $tooltipHtml !!}</div>"
                                                    @endif
                                                >
                                                    @if($hasClass)
                                                        @foreach($classSlots as $currentSlot)
                                                            @php
                                                                $isCombined = ($currentSlot->combined_count ?? 0) > 1;
                                                                $isConflict = ($currentSlot->conflict_count ?? 0) > 0;
                                                            @endphp
                                                            <div class="py-2 class-cell {{ $currentSlot->is_double ? 'double-period' : '' }} {{ $isCombined && !$isConflict ? 'combined-session' : '' }} {{ $isConflict ? 'conflict-session' : '' }} {{ !$loop->last ? 'mb-1 border-bottom pb-1' : '' }}">
                                                                <span class="fw-semibold d-block subject-name">
                                                                    {{ $currentSlot->subject->subject ?? 'N/A' }}
                                                                    @if($isCombined && !$isConflict)
                                                                        <span class="badge bg-warning-subtle text-warning ms-1" style="font-size:8px;">
                                                                            <i class="ri-git-branch-line me-1"></i>{{ $currentSlot->combined_count ?? 2 }}
                                                                        </span>
                                                                    @endif
                                                                    @if($isConflict)
                                                                        <span class="badge bg-danger-subtle text-danger ms-1" style="font-size:8px;">
                                                                            <i class="ri-alert-line me-1"></i>Conflict
                                                                        </span>
                                                                    @endif
                                                                </span>
                                                                <small class="text-muted class-name">
                                                                    {{ $currentSlot->class_full_name ?? '' }}
                                                                </small>
                                                                @if($currentSlot->room)
                                                                    <small class="text-muted d-block room-name" style="font-size:10px;">
                                                                        <i class="ri-door-line"></i> {{ $currentSlot->room->room_name ?? '' }}
                                                                    </small>
                                                                @endif
                                                                @if($currentSlot->is_double)
                                                                    <span class="badge bg-primary-subtle text-primary mt-1" style="font-size:9px;">
                                                                        <i class="ri-repeat-2-line me-1"></i>Double
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @elseif($effectiveType === 'assembly')
                                                        <span class="text-muted">
                                                            <i class="ri-flag-line me-1"></i> Assembly
                                                        </span>
                                                    @elseif($period->is_break || $effectiveType === 'short_break' || $effectiveType === 'long_break')
                                                        <span class="text-muted">
                                                            <i class="ri-coffee-line me-1"></i> Break
                                                        </span>
                                                    @elseif(!$isApplicable)
                                                        <span class="text-muted">—</span>
                                                    @else
                                                        <span class="text-muted free-period">
                                                            <i class="ri-subtract-line"></i>
                                                        </span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-4 align-items-center">
                                <span class="fw-semibold me-2">Legend:</span>
                                <span><i class="ri-checkbox-blank-circle-fill text-success me-1"></i> Regular Class</span>
                                <span><i class="ri-checkbox-blank-circle-fill text-primary me-1"></i> Double Period</span>
                                <span><i class="ri-checkbox-blank-circle-fill text-warning me-1"></i> Combined Session</span>
                                <span><i class="ri-checkbox-blank-circle-fill text-danger me-1"></i> Conflict</span>
                                <span><i class="ri-checkbox-blank-circle-fill text-muted me-1"></i> Free Period</span>
                                <span><i class="ri-coffee-line me-1"></i> Break Time</span>
                                <span><i class="ri-flag-line me-1"></i> Assembly</span>
                                <span><i class="ri-door-line me-1"></i> Room/Venue</span>
                                <span class="text-muted">
                                    <i class="ri-information-line me-1"></i>
                                    Hover for details
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Request Substitute Modal --}}
<div class="modal fade" id="requestSubstituteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">
                    <i class="ri-user-star-line me-2"></i>Request Substitute Teacher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="ri-information-line me-2"></i>
                    Request a substitute teacher for one of your classes. Your request will be sent for approval.
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Select Class/Slot</label>
                        <select class="form-select" id="substituteSlotId">
                            <option value="">-- Select a class --</option>
                            @foreach($slots as $day => $daySlots)
                                @foreach($daySlots as $slot)
                                    @if($slot->subject && !$slot->is_free)
                                        <option value="{{ $slot->id }}">
                                            {{ $slot->day }} - {{ $slot->period->name }}:
                                            {{ $slot->subject->subject }}
                                            ({{ $slot->class_full_name ?? 'Unknown' }})
                                        </option>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Substitute Teacher</label>
                        <select class="form-select" id="substituteTeacherId">
                            <option value="">-- Select substitute --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assignment Date</label>
                        <input type="date" class="form-control" id="substituteDate" min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Reason</label>
                        <textarea class="form-control" id="substituteReason" rows="3"
                            placeholder="Please provide a reason for the substitute request..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="requestSubstitute()">
                    <i class="ri-send-plane-line me-2"></i>Submit Request
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================================
// ROUTES
// ============================================================================
const ROUTES = {
    availableSubstitutes: '{{ route("timetable.available-substitutes") }}',
    requestSubstitute: '{{ route("timetable.request-substitute") }}',
    exportTeacher: '{{ route("timetable.export-teacher") }}',
};

const CSRF = '{{ csrf_token() }}';

// ============================================================================
// FILTERS
// ============================================================================
function resetFilters() {
    const form = document.getElementById('filterForm');
    form.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });
    form.submit();
}

// ============================================================================
// VIEW TOGGLES
// ============================================================================
function toggleRooms() {
    const show = document.getElementById('showRoomNumbers').checked;
    document.querySelectorAll('.room-name').forEach(el => {
        el.style.display = show ? '' : 'none';
    });
    localStorage.setItem('tt_show_rooms', show ? '1' : '0');
}

function toggleCompact() {
    const compact = document.getElementById('compactView').checked;
    const table = document.getElementById('teacherTimetable');
    table.classList.toggle('compact', compact);
    localStorage.setItem('tt_compact', compact ? '1' : '0');
}

// Load saved preferences
document.addEventListener('DOMContentLoaded', function() {
    const showRooms = localStorage.getItem('tt_show_rooms') !== '0';
    document.getElementById('showRoomNumbers').checked = showRooms;
    toggleRooms();

    const compact = localStorage.getItem('tt_compact') === '1';
    document.getElementById('compactView').checked = compact;
    toggleCompact();

    // Update conflict count
    const conflictCount = document.getElementById('conflictCount');
    if (conflictCount && parseInt(conflictCount.textContent) > 0) {
        conflictCount.style.color = '#dc3545';
    }
});

// ============================================================================
// SUBSTITUTE REQUESTS
// ============================================================================
document.getElementById('substituteSlotId')?.addEventListener('change', function() {
    const slotId = this.value;
    const select = document.getElementById('substituteTeacherId');
    select.innerHTML = '<option value="">Loading...</option>';

    if (!slotId) {
        select.innerHTML = '<option value="">-- Select substitute --</option>';
        return;
    }

    fetch(`${ROUTES.availableSubstitutes}?slot_id=${slotId}`, {
        headers: { 'X-CSRF-TOKEN': CSRF }
    })
    .then(res => res.json())
    .then(data => {
        select.innerHTML = '<option value="">-- Select substitute --</option>';
        if (data.success && data.substitutes) {
            data.substitutes.forEach(teacher => {
                const opt = document.createElement('option');
                opt.value = teacher.id;
                opt.textContent = teacher.name;
                if (teacher.email) opt.textContent += ` (${teacher.email})`;
                select.appendChild(opt);
            });
        } else {
            select.innerHTML += `<option value="" disabled>No available substitutes</option>`;
        }
    })
    .catch(() => {
        select.innerHTML = '<option value="">Error loading substitutes</option>';
    });
});

function requestSubstitute() {
    const slotId = document.getElementById('substituteSlotId').value;
    const substituteTeacherId = document.getElementById('substituteTeacherId').value;
    const assignmentDate = document.getElementById('substituteDate').value;
    const reason = document.getElementById('substituteReason').value;

    if (!slotId || !substituteTeacherId || !assignmentDate || !reason) {
        Swal.fire({
            icon: 'error',
            title: 'Incomplete Form',
            text: 'Please fill all fields before submitting.',
            confirmButtonColor: '#667eea'
        });
        return;
    }

    Swal.fire({
        title: 'Submit Substitute Request?',
        text: 'Your request will be sent for approval.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Submit Request',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch(ROUTES.requestSubstitute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify({
                slot_id: slotId,
                substitute_teacher_id: substituteTeacherId,
                assignment_date: assignmentDate,
                reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Request Submitted!',
                    text: 'Your substitute request has been sent for approval.',
                    timer: 3000,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('requestSubstituteModal')).hide();
                document.getElementById('substituteReason').value = '';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: data.message || 'An error occurred. Please try again.',
                    confirmButtonColor: '#667eea'
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: 'Could not connect to the server. Please check your internet connection.',
                confirmButtonColor: '#667eea'
            });
        });
    });
}

// ============================================================================
// EXPORT
// ============================================================================
function exportTeacherTimetable() {
    const form = document.getElementById('filterForm');
    const sessionId = form.querySelector('select[name="session_id"]').value;
    const termId = form.querySelector('select[name="term_id"]').value;
    const classId = form.querySelector('select[name="class_id"]').value;

    let url = ROUTES.exportTeacher;
    const params = new URLSearchParams();
    if (sessionId) params.append('session_id', sessionId);
    if (termId) params.append('term_id', termId);
    if (classId) params.append('class_id', classId);
    if (params.toString()) url += '?' + params.toString();

    window.open(url, '_blank');
}

// ============================================================================
// COPY TO CLIPBOARD
// ============================================================================
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'WebCal link copied to clipboard. Paste it into your calendar app.',
                timer: 2500,
                showConfirmButton: false
            });
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'WebCal link copied to clipboard.',
            timer: 2500,
            showConfirmButton: false
        });
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Copy Failed',
            text: 'Please copy the link manually:\n' + text,
            confirmButtonColor: '#667eea'
        });
    }
    document.body.removeChild(textarea);
}

// ============================================================================
// INITIALIZE TOOLTIPS
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap 5 tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover',
            container: 'body'
        });
    });

    // Update day counts
    @foreach($days as $day)
        const count_{{ $day }} = {{ ($slots[$day] ?? collect())->whereNotNull('subject_id')->where('is_free', false)->count() }};
        document.getElementById('dayCount_{{ $day }}').textContent = count_{{ $day }} || 0;
    @endforeach
});
</script>

<style>
/* ── Modern Timetable Styles ──────────────────────── */
.teacher-timetable {
    font-size: 13px;
    border-collapse: separate;
    border-spacing: 0;
}
.teacher-timetable thead th {
    padding: 12px 8px;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.teacher-timetable td {
    vertical-align: middle;
    padding: 6px 8px;
    transition: background-color 0.15s ease;
    min-height: 70px;
}
.teacher-timetable .timetable-cell {
    cursor: default;
    position: relative;
}
.teacher-timetable .timetable-cell:hover {
    background-color: rgba(102, 126, 234, 0.06) !important;
}

/* Holiday column indicator — today's day column when a holiday falls on it */
.teacher-holiday-col {
    background-color: rgba(239, 68, 68, 0.06) !important;
}
.teacher-timetable thead th.teacher-holiday-col {
    background-color: rgba(239, 68, 68, 0.35) !important;
}

/* ── Class cells ───────────────────────────────────── */
.class-cell {
    transition: all 0.2s ease;
    border-radius: 6px;
    padding: 6px 4px;
}
.class-cell .subject-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}
.class-cell .class-name {
    font-size: 11px;
    color: #64748b;
}
.class-cell .room-name {
    font-size: 10px;
    color: #94a3b8;
}
.class-cell.double-period {
    border-left: 3px solid #667eea;
    background: rgba(102, 126, 234, 0.06);
    border-radius: 4px;
}
.class-cell.combined-session {
    border-left: 3px solid #f59e0b;
    background: rgba(245, 158, 11, 0.08);
    border-radius: 4px;
}
.class-cell.conflict-session {
    border-left: 3px solid #ef4444;
    background: rgba(239, 68, 68, 0.08);
    border-radius: 4px;
}

/* ── Compact View ──────────────────────────────────── */
.teacher-timetable.compact .class-cell {
    padding: 3px 2px;
}
.teacher-timetable.compact .class-cell .subject-name {
    font-size: 11px;
}
.teacher-timetable.compact .class-cell .class-name {
    font-size: 9px;
}
.teacher-timetable.compact .class-cell .room-name {
    font-size: 8px;
}
.teacher-timetable.compact td {
    padding: 3px 4px;
    min-height: 40px;
}
.teacher-timetable.compact .badge {
    font-size: 7px;
    padding: 1px 4px;
}

/* ── Free periods ──────────────────────────────────── */
.free-period {
    color: #cbd5e1;
    font-size: 14px;
}

/* ── Responsive ────────────────────────────────────── */
@media (max-width: 768px) {
    .teacher-timetable {
        font-size: 11px;
    }
    .teacher-timetable thead th {
        font-size: 10px;
        padding: 6px 4px;
    }
    .teacher-timetable td {
        padding: 4px 3px;
        min-height: 50px;
    }
    .class-cell .subject-name {
        font-size: 10px;
    }
    .class-cell .class-name {
        font-size: 8px;
    }
    .class-cell .room-name {
        font-size: 8px;
    }
    .teacher-timetable .timetable-cell .badge {
        font-size: 7px;
        padding: 1px 4px;
    }
}

@media (max-width: 576px) {
    .teacher-timetable {
        font-size: 10px;
    }
    .teacher-timetable thead th {
        font-size: 8px;
        padding: 4px 2px;
    }
    .teacher-timetable td {
        padding: 2px 2px;
        min-height: 35px;
    }
    .class-cell {
        padding: 2px 1px;
    }
    .class-cell .subject-name {
        font-size: 8px;
    }
    .class-cell .class-name {
        font-size: 7px;
    }
    .class-cell .room-name {
        font-size: 7px;
    }
    .class-cell .badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
    }
}

/* ── Print Styles ──────────────────────────────────── */
@media print {
    .page-title-box,
    .card-header .d-flex,
    .alert,
    .btn,
    .breadcrumb,
    .navbar,
    .sidebar,
    .page-title-right,
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
        margin-bottom: 10px !important;
    }
    .card-header {
        background: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6 !important;
    }
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .container-fluid {
        padding: 0 !important;
        max-width: 100% !important;
    }
    .teacher-timetable {
        font-size: 10px !important;
    }
    .teacher-timetable td {
        padding: 4px 6px !important;
    }
    .teacher-timetable thead th {
        background: #1e293b !important;
        color: white !important;
        padding: 6px 8px !important;
    }
    .class-cell {
        background: none !important;
        border: none !important;
    }
    .class-cell.double-period {
        border-left: 2px solid #667eea !important;
    }
    .class-cell.combined-session {
        border-left: 2px solid #f59e0b !important;
    }
    .class-cell.conflict-session {
        border-left: 2px solid #ef4444 !important;
    }
    .text-muted {
        color: #6c757d !important;
    }
}

/* ── Scrollable table wrapper ────────────────────── */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* ── Avatar / stats styling ───────────────────────── */
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.avatar-sm .fs-20 {
    font-size: 20px;
}

/* ── Badge improvements ───────────────────────────── */
.badge.bg-primary-subtle {
    background: #e0e7ff !important;
    color: #4338ca !important;
}
.badge.bg-success-subtle {
    background: #d1fae5 !important;
    color: #065f46 !important;
}
.badge.bg-warning-subtle {
    background: #fef3c7 !important;
    color: #92400e !important;
}
.badge.bg-info-subtle {
    background: #e0f2fe !important;
    color: #0369a1 !important;
}
.badge.bg-danger-subtle {
    background: #fee2e2 !important;
    color: #991b1b !important;
}

/* ── Active filter badges ─────────────────────────── */
.badge a {
    text-decoration: none;
}
.badge a:hover {
    opacity: 0.7;
}

/* ── Gradient card ─────────────────────────────────── */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}
</style>
@endsection