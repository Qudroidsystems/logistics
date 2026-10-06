{{-- resources/views/attendance/admin/holidays.blade.php --}}
@extends('layouts.master')
@section('content')

<style>
:root {
    --hol-primary: #1e3a5f;
    --hol-accent:  #2563eb;
    --hol-success: #16a34a;
    --hol-danger:  #dc2626;
    --hol-border:  #e2e8f0;
    --hol-radius:  10px;
    --hol-shadow:  0 1px 4px rgba(0,0,0,.08);
}

.hol-hero {
    background: linear-gradient(135deg, var(--hol-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--hol-radius);
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.hol-hero::before {
    content:'';
    position:absolute;
    top:-60px;
    right:-60px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
}
.hol-hero h4 {
    color:#fff;
    font-weight:700;
    margin:0;
    position:relative;
}
.hol-hero p {
    color:rgba(255,255,255,.75);
    margin:0;
    font-size:13px;
    position:relative;
}

.hol-card {
    background:#fff;
    border:1px solid var(--hol-border);
    border-radius:var(--hol-radius);
    box-shadow:var(--hol-shadow);
    overflow:hidden;
}
.hol-card .card-header {
    background:#fff;
    border-bottom:1px solid var(--hol-border);
    padding:16px 20px;
    font-weight:700;
    font-size:14px;
    color:var(--hol-primary);
}
.hol-card .card-body {
    padding:20px;
}

.hol-table th {
    background:var(--hol-primary);
    color:#fff;
    padding:12px 16px;
    font-weight:600;
    font-size:13px;
    border:none;
}
.hol-table td {
    padding:12px 16px;
    vertical-align:middle;
    border-bottom:1px solid var(--hol-border);
    font-size:13px;
}
.hol-table tr:hover td {
    background:#eff6ff;
}

.hol-form-label {
    font-size:13px;
    font-weight:600;
    color:#374151;
    margin-bottom:6px;
}
.hol-form-control, .hol-form-select {
    border:1.5px solid var(--hol-border);
    border-radius:8px;
    font-size:13px;
    padding:9px 14px;
    transition:border .15s;
}
.hol-form-control:focus, .hol-form-select:focus {
    border-color:var(--hol-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
    outline:none;
}
.hol-form-control-sm, .hol-form-select-sm {
    padding:6px 10px;
    border-radius:7px;
}

.hol-toast {
    position:fixed;
    bottom:20px;
    right:20px;
    z-index:99999;
    padding:14px 20px;
    border-radius:10px;
    background:#fff;
    box-shadow:0 4px 20px rgba(0,0,0,.12);
    font-weight:600;
    font-size:13px;
    animation: holToastIn .3s ease;
}
@keyframes holToastIn {
    from { opacity:0; transform:translateY(20px); }
    to { opacity:1; transform:translateY(0); }
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- ══ HERO ═══════════════════════════════════════════════════════════ --}}
            <div class="hol-hero d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4><i class="ri-rest-time-line me-2"></i>Holidays & Breaks</h4>
                    <p>Manage school holidays, mid-term breaks, and other non-working days</p>
                </div>
                <a href="{{ route('attendance.settings') }}" class="btn btn-light btn-sm">
                    <i class="ri-arrow-left-line me-1"></i>Back to Settings
                </a>
            </div>

            {{-- ══ ADD HOLIDAY FORM ════════════════════════════════════════════ --}}
            @can('Create attendance-holidays')
            <div class="hol-card mb-3">
                <div class="card-header"><i class="ri-add-circle-line me-2 text-primary"></i>Add Holiday / Break</div>
                <div class="card-body">
                    <form id="holidayForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="hol-form-label">Term <span class="text-danger">*</span></label>
                                <select name="term_id" class="hol-form-select" required>
                                    <option value="">Select Term</option>
                                    @foreach($terms as $t)
                                        <option value="{{ $t->id }}">{{ $t->term }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="hol-form-label">Session <span class="text-danger">*</span></label>
                                <select name="session_id" class="hol-form-select" required>
                                    <option value="">Select Session</option>
                                    @foreach($sessions as $s)
                                        <option value="{{ $s->id }}">{{ $s->session }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="hol-form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="holiday_date" class="hol-form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="hol-form-label">End Date <small class="text-muted fw-normal">(optional)</small></label>
                                <input type="date" name="holiday_end_date" class="hol-form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="hol-form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="holiday_name" class="hol-form-control" placeholder="e.g. Mid-Term Break" required>
                            </div>
                            <div class="col-md-3">
                                <label class="hol-form-label">Type <span class="text-danger">*</span></label>
                                <select name="holiday_type" class="hol-form-select" required>
                                    <option value="public">Public Holiday</option>
                                    <option value="midterm">Mid-Term Break</option>
                                    <option value="school_event">School Event</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="hol-form-label">Notes <small class="text-muted fw-normal">(optional)</small></label>
                                <input type="text" name="notes" class="hol-form-control" placeholder="Any additional notes…">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1"></i>Save Holiday
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

            {{-- ══ HOLIDAYS LIST ════════════════════════════════════════════════ --}}
            <div class="hol-card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <i class="ri-list-check-2 me-2 text-primary"></i>Holidays List
                        <span class="badge bg-dark-subtle text-dark ms-1">{{ $holidays->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table hol-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Term</th>
                                    <th>Session</th>
                                    <th>Notes</th>
                                    @can('Delete attendance-holidays')<th></th>@endcan
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($holidays as $i => $h)
                            @php
                                $typeColors = ['public'=>'primary','midterm'=>'info','school_event'=>'success','other'=>'secondary'];
                                $tc = $typeColors[$h->holiday_type] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $i+1 }}</td>
                                <td><strong>{{ $h->holiday_name }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $tc }}-subtle text-{{ $tc }}">
                                        {{ ucfirst(str_replace('_', ' ', $h->holiday_type)) }}
                                    </span>
                                </td>
                                <td>{{ $h->holiday_date->format('d M Y') }}</td>
                                <td>{{ $h->holiday_end_date ? $h->holiday_end_date->format('d M Y') : '—' }}</td>
                                <td>{{ $h->term?->term }}</td>
                                <td>{{ $h->session?->session }}</td>
                                <td class="text-muted" style="font-size:12px;">{{ $h->notes ?? '—' }}</td>
                                @can('Delete attendance-holidays')
                                <td>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteHoliday({{ $h->id }})">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                                @endcan
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="ri-inbox-line ri-2x d-block mb-2 text-muted"></i>
                                    <p class="text-muted mb-0">No holidays added yet.</p>
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

<script>
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function holToast(msg, type = 'success') {
    const colors = { success:'#16a34a', danger:'#dc2626', warning:'#d97706', info:'#2563eb' };
    const id = 'hol_toast_' + Date.now();
    document.body.insertAdjacentHTML('beforeend',
        `<div id="${id}" class="hol-toast" style="background:${colors[type] || colors.success};color:#fff;min-width:220px;border-radius:10px;padding:14px 20px;box-shadow:0 4px 20px rgba(0,0,0,.12);font-weight:600;font-size:13px;animation:holToastIn .3s ease;">
            ${msg}
            <button onclick="document.getElementById('${id}').remove()" style="background:none;border:none;color:#fff;float:right;margin-left:12px;font-size:16px;cursor:pointer;">×</button>
        </div>`
    );
    setTimeout(() => document.getElementById(id)?.remove(), 4500);
}

@can('Create attendance-holidays')
document.getElementById('holidayForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const origHtml = btn?.innerHTML;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
    }
    try {
        const r = await fetch('{{ route('attendance.holidays.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(this)
        });
        const d = await r.json();
        holToast(d.message, d.success ? 'success' : 'danger');
        if (d.success) setTimeout(() => location.reload(), 1000);
    } catch (e) {
        holToast('Failed to save holiday. Please try again.', 'danger');
        console.error(e);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml || '<i class="ri-save-line me-1"></i>Save Holiday';
        }
    }
});
@endcan

@can('Delete attendance-holidays')
async function deleteHoliday(id) {
    if (!confirm('Delete this holiday?')) return;
    try {
        const r = await fetch(`/attendance/holidays/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const d = await r.json();
        holToast(d.message, d.success ? 'success' : 'danger');
        if (d.success) setTimeout(() => location.reload(), 800);
    } catch (e) {
        holToast('Failed to delete holiday.', 'danger');
        console.error(e);
    }
}
@endcan
</script>
@endsection