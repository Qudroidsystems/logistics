@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style>
:root {
    --principal-primary: #1e3a5f;
    --principal-accent:  #2563eb;
    --principal-success: #16a34a;
    --principal-warning: #d97706;
    --principal-danger:  #dc2626;
    --principal-muted:   #6b7280;
    --principal-border:  #e2e8f0;
    --principal-bg:      #f8fafc;
    --principal-radius:  12px;
    --principal-shadow:  0 2px 8px rgba(0,0,0,.08);
}

/* Hero Section */
.principal-hero {
    background: linear-gradient(135deg, var(--principal-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--principal-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.principal-hero::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 220px;
    height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.principal-hero::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: -30px;
    width: 260px;
    height: 260px;
    background: rgba(255,255,255,.03);
    border-radius: 50%;
}
.principal-hero h1 {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 6px;
    position: relative;
}
.principal-hero p {
    font-size: 13px;
    color: rgba(255,255,255,.75);
    margin: 0;
    position: relative;
}

/* Stat Cards */
.stat-card {
    background: #fff;
    border: 1px solid var(--principal-border);
    border-radius: var(--principal-radius);
    padding: 18px 20px;
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--principal-shadow);
}
.stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--principal-primary);
}
.stat-card .stat-label {
    font-size: 12px;
    color: var(--principal-muted);
    margin-top: 4px;
}
.stat-card .stat-icon {
    font-size: 32px;
    opacity: .12;
    float: right;
    margin-top: -8px;
}

/* Table Styling */
.principal-table th {
    background: var(--principal-primary);
    color: #fff;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 13px;
    white-space: nowrap;
}
.principal-table td {
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--principal-border);
    font-size: 13px;
}
.principal-table tr:hover td {
    background: #eff6ff;
}

/* Badges */
.principal-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.principal-badge-session {
    background: #dbeafe;
    color: #2563eb;
}
.principal-badge-term {
    background: #dcfce7;
    color: #16a34a;
}
.principal-badge-updated {
    background: #fef3c7;
    color: #d97706;
}

/* DataTables Overrides */
.dataTables_wrapper .dataTables_filter input {
    border: 1.5px solid var(--principal-border);
    border-radius: 8px;
    padding: 7px 14px;
    margin-left: 8px;
    font-size: 13px;
    transition: border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--principal-accent);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border: 1.5px solid var(--principal-border);
    border-radius: 8px;
    padding: 6px 10px;
    margin: 0 6px;
    font-size: 13px;
}
.dataTables_wrapper .dataTables_info {
    font-size: 13px;
    color: var(--principal-muted);
}
.dataTables_wrapper .paginate_button {
    border-radius: 6px !important;
    font-size: 13px !important;
    padding: 4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background: var(--principal-accent) !important;
    border-color: var(--principal-accent) !important;
    color: #fff !important;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero Section --}}
    <div class="principal-hero">
        <h1><i class="ri-chat-quote-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Manage and enter Principal's comments for assigned classes across different sessions and terms.</p>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value" id="statTotal">{{ $assignments->count() }}</div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-line"></i></div>
                <div class="stat-value text-primary" id="statSessions">—</div>
                <div class="stat-label">Unique Sessions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-book-open-line"></i></div>
                <div class="stat-value text-success" id="statTerms">—</div>
                <div class="stat-label">Unique Terms</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-time-line"></i></div>
                <div class="stat-value text-warning" id="statUpdated">—</div>
                <div class="stat-label">Recent Updates</div>
            </div>
        </div>
    </div>

    {{-- Assignments Table Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color: var(--principal-primary)">
                    <i class="ri-list-check me-2"></i>My Class Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">{{ $assignments->count() }}</span>
                </h5>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table principal-table w-100 mb-0" id="assignmentsTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Class</th>
                            <th>Session</th>
                            <th>Term</th>
                            <th>Last Updated</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $index => $assignment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $assignment->sclass }} {{ $assignment->schoolarm ?? '' }}</strong>
                            </td>
                            <td>
                                <span class="principal-badge principal-badge-session">
                                    <i class="ri-calendar-line me-1"></i>
                                    {{ $assignment->session_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="principal-badge principal-badge-term">
                                    <i class="ri-bookmark-line me-1"></i>
                                    {{ $assignment->term_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                @if($assignment->updated_at)
                                    <span class="principal-badge principal-badge-updated">
                                        <i class="ri-time-line me-1"></i>
                                        {{ $assignment->updated_at->format('d M Y, h:i A') }}
                                    </span>
                                @else
                                    <span class="text-muted">Never updated</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('myprincipalscomment.classbroadsheet', [
                                    $assignment->schoolclassid,
                                    $assignment->session_id,
                                    $assignment->term_id
                                ]) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="ri-eye-line me-1"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json"
                                           trigger="loop"
                                           colors="primary:#121331,secondary:#08a88a"
                                           style="width:120px;height:120px">
                                </lord-icon>
                                <h5 class="mt-4 text-muted">No Classes Assigned</h5>
                                <p class="text-muted mb-0">You have not been assigned any class for entering Principal's comments yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#assignmentsTable').DataTable({
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading...',
            search: '',
            searchPlaceholder: 'Search by class, session, or term...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ assignments',
            infoEmpty: 'No assignments found',
            zeroRecords: 'No matching assignments',
            emptyTable: 'No class assignments available',
            paginate: {
                first: '<i class="ri-skip-left-line"></i>',
                last: '<i class="ri-skip-right-line"></i>',
                previous: '<i class="ri-arrow-left-s-line"></i>',
                next: '<i class="ri-arrow-right-s-line"></i>'
            }
        },
        order: [[0, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            updateStats();
        }
    });

    // Calculate and update statistics
    function updateStats() {
        const rows = $('#assignmentsTable tbody tr').not('.dataTables_empty');
        const totalRows = rows.length;

        if (totalRows === 0) return;

        // Get unique sessions and terms
        const sessions = new Set();
        const terms = new Set();
        let recentUpdates = 0;
        const now = new Date();
        const thirtyDaysAgo = new Date(now.setDate(now.getDate() - 30));

        rows.each(function() {
            const row = $(this);
            const sessionText = row.find('td:eq(2)').text().trim();
            const termText = row.find('td:eq(3)').text().trim();
            const updatedText = row.find('td:eq(4)').text().trim();

            if (sessionText && sessionText !== 'N/A') sessions.add(sessionText);
            if (termText && termText !== 'N/A') terms.add(termText);

            // Check for recent updates (simplified - you may want to parse actual dates)
            if (updatedText && updatedText !== 'Never updated') {
                recentUpdates++;
            }
        });

        $('#statSessions').text(sessions.size);
        $('#statTerms').text(terms.size);
        $('#statUpdated').text(recentUpdates);
        $('#statTotal').text(totalRows);
        $('#totalBadge').text(totalRows);
    }

    // Initial stats calculation
    updateStats();

    // Custom search placeholder styling
    $('.dataTables_filter input').attr('placeholder', 'Search by class, session, or term...');
    $('.dataTables_filter label').contents().filter(function() {
        return this.nodeType === 3;
    }).remove();

    // Add icons to pagination buttons after initialization
    setTimeout(() => {
        $('.paginate_button.previous').html('<i class="ri-arrow-left-s-line"></i>');
        $('.paginate_button.next').html('<i class="ri-arrow-right-s-line"></i>');
        $('.paginate_button.first').html('<i class="ri-skip-left-line"></i>');
        $('.paginate_button.last').html('<i class="ri-skip-right-line"></i>');
    }, 100);
});
</script>
@endsection
