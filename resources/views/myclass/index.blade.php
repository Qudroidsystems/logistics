@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
:root {
    --cb-navy:      #0f2342;
    --cb-teal:      #0d9488;
    --cb-sky:       #0ea5e9;
    --cb-amber:     #f59e0b;
    --cb-rose:      #f43f5e;
    --cb-green:     #22c55e;
    --cb-muted:     #64748b;
    --cb-border:    #e2e8f0;
    --cb-surface:   #f8fafc;
    --cb-white:     #ffffff;
    --cb-radius:    14px;
    --cb-shadow:    0 4px 16px rgba(15,35,66,.10);
    --cb-shadow-lg: 0 8px 32px rgba(15,35,66,.14);
}

*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; }

/* Hero Section */
.cb-hero {
    background: linear-gradient(135deg, var(--cb-navy) 0%, #1e4a7e 55%, #0d9488 100%);
    border-radius: var(--cb-radius);
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.cb-hero::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -80px;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
    border-radius: 50%;
}
.cb-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 8px;
}
.cb-hero p {
    font-size: 13px;
    color: rgba(255,255,255,.72);
    margin: 0;
}
.cb-hero .meta-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
}
.cb-meta-pill {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Stat Cards */
.cb-stat {
    background: var(--cb-white);
    border: 1px solid var(--cb-border);
    border-radius: var(--cb-radius);
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: transform .15s, box-shadow .15s;
}
.cb-stat:hover {
    transform: translateY(-2px);
    box-shadow: var(--cb-shadow);
}
.cb-stat .stat-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--cb-radius) var(--cb-radius) 0 0;
}
.cb-stat .stat-value {
    font-size: 30px;
    font-weight: 700;
    color: var(--cb-navy);
    line-height: 1;
    margin-top: 8px;
}
.cb-stat .stat-label {
    font-size: 12px;
    color: var(--cb-muted);
    margin-top: 5px;
    font-weight: 500;
}
.cb-stat .stat-ico {
    font-size: 36px;
    opacity: .08;
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
}

/* Main Card */
.cb-card {
    background: var(--cb-white);
    border: 1px solid var(--cb-border);
    border-radius: var(--cb-radius);
    box-shadow: var(--cb-shadow);
    overflow: hidden;
}
.cb-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--cb-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    background: linear-gradient(to right, #f8fafc, #f0fdf9);
}
.cb-card-header h5 {
    font-size: 15px;
    font-weight: 700;
    color: var(--cb-navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-create {
    background: linear-gradient(135deg, var(--cb-teal), #0f766e);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-create:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13,148,136,0.3);
}

/* Table Styles */
.cb-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.cb-table thead th {
    background: linear-gradient(135deg, var(--cb-navy), #1e4a7e);
    color: #fff;
    padding: 14px 16px;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
    text-align: left;
    letter-spacing: 0.3px;
}
.cb-table thead th:first-child { border-top-left-radius: 0; }
.cb-table thead th:last-child { border-top-right-radius: 0; }
.cb-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--cb-border);
    color: #334155;
}
.cb-table tbody tr:hover td {
    background: #f0fdf9;
}
.cb-table tbody tr:last-child td {
    border-bottom: none;
}

/* Enhanced Action Buttons */
.action-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
    cursor: pointer;
    border: none;
    position: relative;
    overflow: hidden;
}
.action-btn i {
    font-size: 14px;
    transition: transform 0.2s ease;
}
.action-btn:hover i {
    transform: scale(1.1);
}
.action-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.4s, height 0.4s;
}
.action-btn:hover::before {
    width: 200px;
    height: 200px;
}

/* Students Button */
.btn-students {
    background: linear-gradient(135deg, #e0f2fe, #bae6fd);
    color: #0369a1;
    border: 1px solid #7dd3fc;
}
.btn-students:hover {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: white;
    border-color: #0ea5e9;
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(14,165,233,0.25);
}

/* Broadsheet Button */
.btn-broadsheet {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #15803d;
    border: 1px solid #86efac;
}
.btn-broadsheet:hover {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    border-color: #22c55e;
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(34,197,94,0.25);
}

/* Term Badge */
.term-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}
.term-badge i {
    font-size: 11px;
}
.term-1 { background: #dbeafe; color: #1e40af; }
.term-2 { background: #dcfce7; color: #166534; }
.term-3 { background: #fef3c7; color: #92400e; }

/* Class Badge */
.class-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    color: var(--cb-navy);
    border: 1px solid var(--cb-border);
}
.class-badge i {
    color: var(--cb-teal);
    font-size: 14px;
}

/* Arm Badge */
.arm-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 45px;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    background: var(--cb-teal);
    color: white;
    text-transform: uppercase;
}

/* Session Badge */
.session-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    background: #e0e7ff;
    color: #3730a3;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px;
}
.empty-state i {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 16px;
    display: block;
}
.empty-state h6 {
    font-size: 18px;
    color: var(--cb-navy);
    margin-bottom: 8px;
}
.empty-state p {
    color: var(--cb-muted);
    font-size: 13px;
}

/* Pagination */
.pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 10px;
}
.page-item {
    display: inline-flex;
    padding: 8px 14px;
    border: 1px solid var(--cb-border);
    border-radius: 10px;
    color: var(--cb-navy);
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s;
    background: white;
    cursor: pointer;
}
.page-item:hover:not(.disabled) {
    background: var(--cb-teal);
    color: white;
    border-color: var(--cb-teal);
    transform: translateY(-1px);
}
.page-item.active {
    background: var(--cb-teal);
    color: white;
    border-color: var(--cb-teal);
}
.page-item.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* History Table */
.history-table {
    background: #f8fafc;
}
.history-table tbody tr:hover td {
    background: #f1f5f9;
}

/* Responsive */
@media (max-width: 768px) {
    .cb-hero { padding: 24px 20px; }
    .cb-hero h1 { font-size: 22px; }
    .cb-table thead { display: none; }
    .cb-table tbody td {
        display: block;
        text-align: right;
        padding-left: 50%;
        position: relative;
        border-bottom: 1px solid var(--cb-border);
    }
    .cb-table tbody td:before {
        content: attr(data-label);
        position: absolute;
        left: 16px;
        width: 45%;
        text-align: left;
        font-weight: 700;
        color: var(--cb-navy);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .action-group {
        justify-content: flex-end;
    }
    .action-btn {
        padding: 6px 12px;
        font-size: 11px;
    }
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.cb-card {
    animation: fadeInUp 0.4s ease;
}

/* Tooltip */
[data-tooltip] {
    position: relative;
    cursor: pointer;
}
[data-tooltip]:before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background: #1e293b;
    color: white;
    font-size: 11px;
    font-weight: 500;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
    z-index: 10;
    margin-bottom: 8px;
}
[data-tooltip]:hover:before {
    opacity: 1;
    visibility: visible;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

<!-- Hero Section -->
<div class="cb-hero">
    <h1><i class="ri-graduation-cap-line me-2"></i>My Classes</h1>
    <p>View your assigned classes, manage students, and access broadsheet reports.</p>
    <div class="meta-pills">
        <span class="cb-meta-pill"><i class="ri-user-line"></i>{{ Auth::user()->name }}</span>
        <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ date('F j, Y') }}</span>
        <span class="cb-meta-pill"><i class="ri-time-line"></i>{{ date('g:i A') }}</span>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-6">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-navy),var(--cb-teal));"></div>
            <div class="stat-ico"><i class="ri-book-open-line"></i></div>
            <div class="stat-value">{{ $myclass->total() }}</div>
            <div class="stat-label">Current Classes</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-sky),#38bdf8);"></div>
            <div class="stat-ico"><i class="ri-group-line"></i></div>
            <div class="stat-value text-info" id="totalStudents">—</div>
            <div class="stat-label">Total Students Enrolled</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-amber),#fcd34d);"></div>
            <div class="stat-ico"><i class="ri-history-line"></i></div>
            <div class="stat-value text-warning">{{ $myclasshistory->count() }}</div>
            <div class="stat-label">Previous Assignments</div>
        </div>
    </div>
</div>

<!-- Current Classes Card -->
<div class="cb-card">
    <div class="cb-card-header">
        <h5>
            <i class="ri-table-alt-line" style="color:var(--cb-teal)"></i>
            My Current Class Assignments
            <span class="cb-badge" style="background: var(--cb-teal); color: white; border-radius: 20px; padding: 2px 10px; font-size: 11px;">
                {{ $myclass->total() }} Classes
            </span>
        </h5>
        @can('Create my-class')
        <button type="button" class="btn-create" data-bs-toggle="modal" data-bs-target="#addClassModal">
            <i class="ri-add-line"></i> Create Class Setting
        </button>
        @endcan
    </div>

    <div style="overflow-x: auto;">
        <table class="cb-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Class</th>
                    <th style="width: 80px;">Arm</th>
                    <th style="width: 100px;">Term</th>
                    <th style="width: 130px;">Session</th>
                    <th style="width: 140px;">Students</th>
                    <th style="width: 180px;">Broadsheet</th>
                </tr>
            </thead>
            <tbody>
                @php $i = ($myclass->currentPage() - 1) * $myclass->perPage() @endphp
                @forelse ($myclass as $sc)
                @php
                    // Determine term badge class
                    $termClass = 'term-1';
                    if(stripos($sc->term, '2') !== false) $termClass = 'term-2';
                    if(stripos($sc->term, '3') !== false) $termClass = 'term-3';

                    // Get student count for this class
                    $studentCount = \App\Models\Studentclass::where('schoolclassid', $sc->schoolclassid)
                        ->where('sessionid', $sc->sessionid)
                        ->count();
                @endphp
                <tr>
                    <td data-label="#" style="font-weight: 600; color: var(--cb-navy);">{{ ++$i }}</td>

                    <td data-label="Class">
                        <div class="class-badge">
                            <i class="ri-building-line"></i>
                            {{ $sc->schoolclass }}
                        </div>
                    </td>

                    <td data-label="Arm">
                        <span class="arm-badge">{{ $sc->schoolarm }}</span>
                    </td>

                    <td data-label="Term">
                        <span class="term-badge {{ $termClass }}">
                            <i class="ri-calendar-line"></i>
                            {{ $sc->term }}
                        </span>
                    </td>

                    <td data-label="Session">
                        <span class="session-badge">
                            <i class="ri-calendar-event-line"></i>
                            {{ $sc->session }}
                        </span>
                    </td>

                    <td data-label="Students">
                        <a href="{{ route('viewstudent', [$sc->schoolclassid, $sc->termid, $sc->sessionid]) }}"
                           class="action-btn btn-students"
                           data-tooltip="View all students in {{ $sc->schoolclass }} {{ $sc->schoolarm }}">
                            <i class="ri-group-line"></i>
                            <span>View Students</span>
                            <span style="background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 20px; font-size: 10px; margin-left: 4px;">
                                {{ $studentCount }}
                            </span>
                        </a>
                    </td>

                    <td data-label="Broadsheet">
                        <a href="{{ route('classbroadsheet.viewcomments', [$sc->schoolclassid, $sc->sessionid, $sc->termid]) }}"
                           class="action-btn btn-broadsheet"
                           data-tooltip="View broadsheet for {{ $sc->term }} {{ $sc->session }}">
                            <i class="ri-file-text-line"></i>
                            <span>Broadsheet</span>
                            <span style="background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 20px; font-size: 10px; margin-left: 4px;">
                                {{ $sc->term }}
                            </span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="ri-inbox-line"></i>
                            <h6>No Classes Assigned</h6>
                            <p>You haven't been assigned to any classes for the current session.</p>
                            <small class="text-muted">Contact the administrator to assign you to classes.</small>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($myclass->total() > 0)
    <div style="padding: 20px 24px; border-top: 1px solid var(--cb-border); background: #fafbfc;">
        <div class="row align-items-center">
            <div class="col-sm">
                <div class="text-muted" style="font-size: 12px;">
                    <i class="ri-information-line me-1"></i>
                    Showing <span class="fw-semibold text-dark">{{ $myclass->count() }}</span> of
                    <span class="fw-semibold text-dark">{{ $myclass->total() }}</span> classes
                </div>
            </div>
            <div class="col-sm-auto">
                <div class="pagination-wrap">
                    @if($myclass->onFirstPage())
                        <span class="page-item disabled"><i class="ri-arrow-left-s-line"></i> Prev</span>
                    @else
                        <a class="page-item" href="{{ $myclass->previousPageUrl() }}">
                            <i class="ri-arrow-left-s-line"></i> Prev
                        </a>
                    @endif

                    @foreach ($myclass->getUrlRange(max(1, $myclass->currentPage() - 2), min($myclass->lastPage(), $myclass->currentPage() + 2)) as $page => $url)
                        @if($page == $myclass->currentPage())
                            <span class="page-item active">{{ $page }}</span>
                        @else
                            <a class="page-item" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($myclass->hasMorePages())
                        <a class="page-item" href="{{ $myclass->nextPageUrl() }}">
                            Next <i class="ri-arrow-right-s-line"></i>
                        </a>
                    @else
                        <span class="page-item disabled">
                            Next <i class="ri-arrow-right-s-line"></i>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Class History Section -->
@if($myclasshistory->count() > 0)
<div class="cb-card mt-4">
    <div class="cb-card-header">
        <h5>
            <i class="ri-history-line" style="color:var(--cb-teal)"></i>
            Previous Class Assignments (History)
            <span class="cb-badge" style="background: #94a3b8; color: white; border-radius: 20px; padding: 2px 10px; font-size: 11px;">
                {{ $myclasshistory->count() }} Records
            </span>
        </h5>
    </div>
    <div style="overflow-x: auto;">
        <table class="cb-table history-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Class</th>
                    <th style="width: 80px;">Arm</th>
                    <th style="width: 100px;">Term</th>
                    <th style="width: 130px;">Session</th>
                    <th style="width: 120px;">Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($myclasshistory as $index => $history)
                @php
                    $termClass = 'term-1';
                    if(stripos($history->term, '2') !== false) $termClass = 'term-2';
                    if(stripos($history->term, '3') !== false) $termClass = 'term-3';
                @endphp
                <tr>
                    <td data-label="#" style="font-weight: 600; color: var(--cb-muted);">{{ $index + 1 }}</td>
                    <td data-label="Class">
                        <div class="class-badge" style="background: #f1f5f9;">
                            <i class="ri-building-line"></i>
                            {{ $history->schoolclass }}
                        </div>
                    </td>
                    <td data-label="Arm">
                        <span class="arm-badge" style="background: #94a3b8;">{{ $history->schoolarm }}</span>
                    </td>
                    <td data-label="Term">
                        <span class="term-badge {{ $termClass }}" style="opacity: 0.8;">
                            <i class="ri-calendar-line"></i>
                            {{ $history->term }}
                        </span>
                    </td>
                    <td data-label="Session">
                        <span class="session-badge" style="background: #e2e8f0; color: #475569;">
                            <i class="ri-calendar-event-line"></i>
                            {{ $history->session }}
                        </span>
                    </td>
                    <td data-label="Last Updated">
                        <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: #64748b;">
                            <i class="ri-time-line"></i>
                            {{ $history->updated_at->format('d M Y') }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

</div></div></div>

<script>
// Calculate and display total students across all assigned classes
document.addEventListener('DOMContentLoaded', function() {
    var totalStudents = 0;

    @foreach($myclass as $sc)
        @php
            $studentCount = \App\Models\Studentclass::where('schoolclassid', $sc->schoolclassid)
                ->where('sessionid', $sc->sessionid)
                ->count();
        @endphp
        totalStudents += {{ $studentCount }};
    @endforeach

    var totalStudentsEl = document.getElementById('totalStudents');
    if (totalStudentsEl) {
        totalStudentsEl.textContent = totalStudents;
        // Add animation
        totalStudentsEl.style.opacity = '0';
        totalStudentsEl.style.transform = 'scale(0.8)';
        setTimeout(function() {
            totalStudentsEl.style.transition = 'all 0.3s ease';
            totalStudentsEl.style.opacity = '1';
            totalStudentsEl.style.transform = 'scale(1)';
        }, 100);
    }
});

// Add tooltip functionality for buttons with data-tooltip
document.querySelectorAll('[data-tooltip]').forEach(function(element) {
    element.addEventListener('mouseenter', function(e) {
        var tooltip = this.getAttribute('data-tooltip');
        if (tooltip) {
            // Optional: Add custom tooltip logic if needed
        }
    });
});

// Smooth page transitions
document.querySelectorAll('.action-btn, .page-item:not(.disabled)').forEach(function(link) {
    link.addEventListener('click', function(e) {
        if (this.classList.contains('page-item') || this.classList.contains('action-btn')) {
            var href = this.getAttribute('href');
            if (href && href !== '#') {
                // Optional: Add loading effect
                var loader = document.createElement('div');
                loader.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:40px;height:40px;border:3px solid #e2e8f0;border-top-color:#0d9488;border-radius:50%;animation:spin 0.6s linear infinite;z-index:9999;';
                loader.id = 'page-loader';
                document.body.appendChild(loader);

                setTimeout(function() {
                    var existingLoader = document.getElementById('page-loader');
                    if (existingLoader) existingLoader.remove();
                }, 3000);
            }
        }
    });
});

// Add spin animation keyframes if not exists
if (!document.querySelector('#spin-keyframes')) {
    var style = document.createElement('style');
    style.id = 'spin-keyframes';
    style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(style);
}
</script>

@endsection
