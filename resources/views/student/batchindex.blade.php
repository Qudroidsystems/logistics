{{-- resources/views/student/batchindex.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
/* ── Batch Upload Design System (ported from scoresheet) ─────────── */
:root {
    --ss-primary:   #1e3a5f;
    --ss-accent:    #2563eb;
    --ss-success:   #16a34a;
    --ss-warning:   #d97706;
    --ss-danger:    #dc2626;
    --ss-info:      #4338ca;
    --ss-muted:     #6b7280;
    --ss-border:    #e2e8f0;
    --ss-bg:        #f8fafc;
    --ss-card:      #ffffff;
    --ss-radius:    10px;
    --ss-shadow:    0 1px 4px rgba(0,0,0,.08);
}

/* Stat cards */
.stat-card { background: var(--ss-card); border: 1px solid var(--ss-border); border-radius: var(--ss-radius); padding: 14px 18px; box-shadow: var(--ss-shadow); transition: transform .15s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-card .stat-value { font-size: 22px; font-weight: 700; color: var(--ss-primary); }
.stat-card .stat-label { font-size: 11px; color: var(--ss-muted); margin-top: 2px; }
.stat-card .stat-icon  { font-size: 28px; opacity: .15; float: right; margin-top: -6px; }

/* Card / table chrome */
.ss-card-header { background: var(--ss-primary); }
.ss-card-header h5 { color: #fff; }

#batchListTable { font-size: 12.5px; }
#batchListTable thead tr { background: var(--ss-primary); color: #fff; }
#batchListTable thead th { padding: 10px 8px; font-weight: 600; white-space: nowrap; border: none; }
#batchListTable tbody td { padding: 10px 8px; vertical-align: middle; border-bottom: 1px solid var(--ss-border); }
#batchListTable tbody tr { transition: background .14s ease, box-shadow .18s ease, transform .18s cubic-bezier(.34,1.4,.64,1); }
#batchListTable tbody tr:hover {
    background: #f0f6ff !important;
    box-shadow: inset 3px 0 0 var(--ss-accent);
    transform: translateY(-1px);
    position: relative; z-index: 1;
}
#batchListTable tbody tr.selected-row { background: #eef4ff !important; box-shadow: inset 3px 0 0 var(--ss-accent); }

/* Status pill badges */
.status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.status-pill.success    { background: #dcfce7; color: var(--ss-success); }
.status-pill.processing { background: #fef3c7; color: var(--ss-warning); }
.status-pill.partial    { background: #e0e7ff; color: var(--ss-info); }
.status-pill.failed     { background: #fee2e2; color: var(--ss-danger); }

/* Filter bar */
.filter-card { background: var(--ss-card); border: 1px solid var(--ss-border); border-radius: var(--ss-radius); box-shadow: var(--ss-shadow); }

/* Modal headers */
.ss-modal-header { background: var(--ss-primary); border: none; }
.ss-modal-header .modal-title { color: #fff; }

/* Progress bars */
.ss-progress-wrap { display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius: 10px; background:#fefce8; }
.ss-progress-track { height:6px; border-radius:4px; background:#f1f5f9; overflow:hidden; margin-top:4px; }
.ss-progress-fill  { height:100%; border-radius:4px; transition:width .3s ease; }

/* Loaders inside modals */
#batch-loader, #update-class-loader, #template-loader {
    backdrop-filter: blur(2px);
    font-size: 1.1rem;
    color: #333;
}
#batch-loader .spinner-border, #update-class-loader .spinner-border {
    width: 2rem; height: 2rem;
}
#deleteRecordModal .spinner-border { width: 1.5rem; height: 1.5rem; }

/* Toast */
.ss-toast {
    position: fixed; bottom: 20px; right: 20px; z-index: 99999;
    min-width: 280px; border-radius: 10px; color: #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,.18);
}
.ss-toast .toast-body { display:flex; align-items:center; padding: 12px 14px; }

/* Force checkbox pointer cursor and kill theme interference */
#batchListTable input[type="checkbox"] { cursor: pointer; }
#checkAll { cursor: pointer; }

/* ── Template picker (checkbox multi-select) ───────────────────── */
.tpl-picker {
    border: 1px solid var(--ss-border);
    border-radius: var(--ss-radius);
    background: #fff;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 260px;
}
.tpl-picker-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-bottom: 1px solid var(--ss-border);
    background: #f8fafc;
    border-radius: var(--ss-radius) var(--ss-radius) 0 0;
}
.tpl-picker-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--ss-primary);
}
.tpl-picker-actions { font-size: 11px; display: flex; gap: 4px; align-items: center; }
.tpl-picker-actions .btn-link { text-decoration: none; font-size: 11px; }
.tpl-picker-search { margin: 8px 12px 0; width: calc(100% - 24px); }
.tpl-picker-body {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 8px 12px 12px;
    max-height: 260px;
}
.tpl-check {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 6px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12.5px;
    transition: background .12s;
    margin: 0;
}
.tpl-check:hover { background: #f0f6ff; }
.tpl-check input[type="checkbox"] { cursor: pointer; margin: 0; flex-shrink: 0; }
.tpl-check span { user-select: none; }
.tpl-check.is-hidden { display: none; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- ══ PAGE TITLE ══════════════════════════════════════════════ --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Batch Uploads</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Student Management</a></li>
                                <li class="breadcrumb-item active">Batch Uploads</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- ══ STAT SUMMARY CARDS ══════════════════════════════════════ --}}
            @php
                $totalBatches      = $batch->count();
                $processingBatches = $batch->where('status', 'Processing')->count();
                $successBatches    = $batch->where('status', 'Success')->count();
                $partialBatches    = $batch->where('status', 'Partial')->count();
                $failedBatches     = $batch->where('status', 'Failed')->count();
            @endphp
            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-2-4" style="flex:0 0 20%;max-width:20%;">
                    <div class="stat-card text-center h-100">
                        <div class="stat-icon">📦</div>
                        <div class="stat-value text-primary">{{ $totalBatches }}</div>
                        <div class="stat-label">Total Batches</div>
                    </div>
                </div>
                <div class="col-6" style="flex:0 0 20%;max-width:20%;">
                    <div class="stat-card text-center h-100">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-value" style="color:var(--ss-warning);">{{ $processingBatches }}</div>
                        <div class="stat-label">Processing</div>
                    </div>
                </div>
                <div class="col-6" style="flex:0 0 20%;max-width:20%;">
                    <div class="stat-card text-center h-100">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value" style="color:var(--ss-success);">{{ $successBatches }}</div>
                        <div class="stat-label">Success</div>
                    </div>
                </div>
                <div class="col-6" style="flex:0 0 20%;max-width:20%;">
                    <div class="stat-card text-center h-100">
                        <div class="stat-icon">🔶</div>
                        <div class="stat-value" style="color:var(--ss-info);">{{ $partialBatches }}</div>
                        <div class="stat-label">Partial</div>
                    </div>
                </div>
                <div class="col-6" style="flex:0 0 20%;max-width:20%;">
                    <div class="stat-card text-center h-100">
                        <div class="stat-icon">❌</div>
                        <div class="stat-value" style="color:var(--ss-danger);">{{ $failedBatches }}</div>
                        <div class="stat-label">Failed</div>
                    </div>
                </div>
            </div>

            {{-- ══ BATCH STATUS CHART ══════════════════════════════════════ --}}
            <div class="row mb-3">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header ss-card-header">
                            <h5 class="card-title mb-0"><i class="ri-bar-chart-2-line me-1"></i>Batch Upload Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="batchStatusChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div id="batchList">

                {{-- ══ FILTER BAR ══════════════════════════════════════════ --}}
                <div class="row mb-3">
                    <div class="col-lg-12">
                        <div class="filter-card p-3">
                            <div class="row g-3">
                                <div class="col-xxl-4">
                                    <div class="search-box">
                                        <input type="text" class="form-control search" placeholder="Search batches">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-xxl-3 col-sm-6">
                                    <select class="form-control" id="idStatus" data-choices data-choices-search-false>
                                        <option value="all">Select Status</option>
                                        <option value="Processing">Processing</option>
                                        <option value="Partial">Partial</option>
                                        <option value="Success">Success</option>
                                        <option value="Failed">Failed</option>
                                    </select>
                                </div>
                                <div class="col-xxl-3 col-sm-6">
                                    <select class="form-control" id="idClass" data-choices data-choices-search-false>
                                        <option value="all">Select Class</option>
                                        @foreach ($batch->pluck('schoolclass')->unique() as $class)
                                            <option value="{{ $class }}">{{ $class }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xxl-2 col-sm-6">
                                    <button type="button" class="btn btn-secondary w-100" onclick="filterData();"><i class="bi bi-funnel align-baseline me-1"></i> Filters</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══ MAIN TABLE CARD ═════════════════════════════════════ --}}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header ss-card-header d-flex align-items-center flex-wrap gap-2 py-3">
                                <div class="flex-grow-1">
                                    <h5 class="mb-0"><i class="ri-file-list-3-line me-2"></i>Batch Uploads <span class="badge bg-white text-primary ms-1">{{ $batch->count() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        @can('Create student-bulk-upload')
                                            <button type="button" class="btn btn-light text-danger d-none" id="remove-actions"><i class="ri-delete-bin-2-line"></i></button>
                                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#generateTemplateModal"><i class="bi bi-file-earmark-spreadsheet align-baseline me-1"></i> Generate Template</button>
                                            <button type="button" class="btn btn-warning add-btn" data-bs-toggle="modal" data-bs-target="#addBatchModal"><i class="bi bi-plus-circle align-baseline me-1"></i> New Batch Upload</button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle mb-0" id="batchListTable">
                                        <thead>
                                            <tr>
                                                <th style="width:44px;">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                                        <label class="form-check-label" for="checkAll"></label>
                                                    </div>
                                                </th>
                                                <th class="sort cursor-pointer" data-sort="sn">SN</th>
                                                <th class="sort cursor-pointer" data-sort="title">Batch Title</th>
                                                <th class="sort cursor-pointer" data-sort="schoolclass">School Class</th>
                                                <th class="sort cursor-pointer" data-sort="arm">School Arm</th>
                                                <th class="sort cursor-pointer" data-sort="term">Term</th>
                                                <th class="sort cursor-pointer" data-sort="session">Session</th>
                                                <th class="sort cursor-pointer" data-sort="status">Status</th>
                                                <th class="sort cursor-pointer" data-sort="upload_date">Upload Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @php $i = 0 @endphp
                                            @forelse ($batch as $sc)
                                                @php
                                                    $pillClass = match ($sc->status) {
                                                        'Success'    => 'success',
                                                        'Processing' => 'processing',
                                                        'Partial'    => 'partial',
                                                        default      => 'failed',
                                                    };
                                                    $pillIcon = match ($sc->status) {
                                                        'Success'    => 'ri-check-line',
                                                        'Processing' => 'ri-loader-4-line',
                                                        'Partial'    => 'ri-error-warning-line',
                                                        default      => 'ri-close-line',
                                                    };
                                                @endphp
                                                <tr data-row-id="{{ $sc->id }}">
                                                    <td class="id" data-id="{{ $sc->id }}">
                                                        <div class="form-check">
                                                            <input class="form-check-input chk-child" type="checkbox" name="chk_child" value="{{ $sc->id }}" id="chk_{{ $sc->id }}">
                                                            <label class="form-check-label" for="chk_{{ $sc->id }}"></label>
                                                        </div>
                                                    </td>
                                                    <td class="sn">{{ ++$i }}</td>
                                                    <td class="title fw-semibold">{{ $sc->title }}</td>
                                                    <td class="schoolclass">{{ $sc->schoolclass }}</td>
                                                    <td class="arm">{{ $sc->arm }}</td>
                                                    <td class="term">{{ $sc->term }}</td>
                                                    <td class="session">{{ $sc->session }}</td>
                                                    <td class="status" data-status="{{ $sc->status }}">
                                                        <span class="status-pill {{ $pillClass }}">
                                                            <i class="{{ $pillIcon }}"></i>{{ $sc->status }}
                                                        </span>
                                                    </td>
                                                    <td class="upload_date">
                                                        <i class="ri-calendar-line me-1 text-muted"></i>{{ Carbon\Carbon::parse($sc->upload_date)->format('Y-m-d') }}
                                                    </td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @if (in_array($sc->status, ['Failed', 'Partial']))
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-soft-warning view-errors-btn" data-id="{{ $sc->id }}" title="View Import Errors"><i class="ph-warning"></i></a>
                                                                </li>
                                                            @endif
                                                            @can('Create student-bulk-upload')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-soft-info update-item-btn" data-id="{{ $sc->id }}" data-schoolclass="{{ $sc->schoolclass }}" data-arm="{{ $sc->arm }}" data-schoolclassid="{{ $sc->schoolclassid }}" data-armid="{{ $sc->armid }}" data-classcategoryid="{{ $sc->classcategoryid ?? '' }}"><i class="ph-pencil"></i></a>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-sm btn-soft-danger remove-item-btn" data-id="{{ $sc->id }}"><i class="ph-trash"></i></a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="noresult text-center text-muted py-4" style="display: block;">
                                                        <i class="ri-inbox-line ri-2x d-block mb-2"></i>No results found
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 px-3 pb-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $batch->count() }}</span> of <span class="fw-semibold">{{ $batch->count() }}</span> Results
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ GENERATE TEMPLATE MODAL ═══════════════════════════════════ --}}
            <div id="generateTemplateModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header ss-modal-header">
                            <h5 class="modal-title"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Generate Batch Upload Template</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body position-relative">
                            <div id="template-loader" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                                 style="background: rgba(255,255,255,0.85); z-index: 1000;">
                                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                                <span class="ms-2">Generating template(s)...</span>
                            </div>

                            <div class="alert alert-info small">
                                <i class="ri-information-line me-1"></i>
                                Tick one or more classes, terms, and sessions. A template is generated for
                                every combination — those values are locked into each spreadsheet
                                automatically. Select more than one combination and you'll get a .zip of
                                all the templates.
                            </div>

                            <div class="row g-3">
                                {{-- Classes --}}
                                <div class="col-md-4">
                                    <div class="tpl-picker">
                                        <div class="tpl-picker-head">
                                            <span class="tpl-picker-title">School Class &amp; Arm</span>
                                            <div class="tpl-picker-actions">
                                                <button type="button" class="btn btn-link btn-sm p-0" data-tpl-toggle="tpl_schoolclass">All</button>
                                                <span class="text-muted">·</span>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-tpl-clear="tpl_schoolclass">Clear</button>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control form-control-sm tpl-picker-search mb-2"
                                               placeholder="Search classes..." data-tpl-search="tpl_schoolclass">
                                        <div class="tpl-picker-body" id="tpl_schoolclass">
                                            @foreach ($schoolclasses as $sc)
                                                <label class="tpl-check">
                                                    <input type="checkbox" name="tpl_schoolclassid[]" value="{{ $sc->id }}">
                                                    <span>{{ $sc->schoolclass }} - {{ $sc->arm }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Terms --}}
                                <div class="col-md-4">
                                    <div class="tpl-picker">
                                        <div class="tpl-picker-head">
                                            <span class="tpl-picker-title">Term</span>
                                            <div class="tpl-picker-actions">
                                                <button type="button" class="btn btn-link btn-sm p-0" data-tpl-toggle="tpl_term">All</button>
                                                <span class="text-muted">·</span>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-tpl-clear="tpl_term">Clear</button>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control form-control-sm tpl-picker-search mb-2"
                                               placeholder="Search terms..." data-tpl-search="tpl_term">
                                        <div class="tpl-picker-body" id="tpl_term">
                                            @foreach ($schoolterms as $sc)
                                                <label class="tpl-check">
                                                    <input type="checkbox" name="tpl_termid[]" value="{{ $sc->id }}">
                                                    <span>{{ $sc->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Sessions --}}
                                <div class="col-md-4">
                                    <div class="tpl-picker">
                                        <div class="tpl-picker-head">
                                            <span class="tpl-picker-title">Session</span>
                                            <div class="tpl-picker-actions">
                                                <button type="button" class="btn btn-link btn-sm p-0" data-tpl-toggle="tpl_session">All</button>
                                                <span class="text-muted">·</span>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-tpl-clear="tpl_session">Clear</button>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control form-control-sm tpl-picker-search mb-2"
                                               placeholder="Search sessions..." data-tpl-search="tpl_session">
                                        <div class="tpl-picker-body" id="tpl_session">
                                            @foreach ($schoolsessions as $sc)
                                                <label class="tpl-check">
                                                    <input type="checkbox" name="tpl_sessionid[]" value="{{ $sc->id }}">
                                                    <span>{{ $sc->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label for="tpl_rows" class="form-label fw-semibold">Blank rows (per template)</label>
                                    <input type="number" id="tpl_rows" class="form-control" value="30" min="1" max="500">
                                </div>
                                <div class="col-md-8 d-flex align-items-end">
                                    <div id="tpl-combo-count" class="small text-muted mb-2"></div>
                                </div>
                            </div>

                            <div class="alert alert-danger d-none mt-3 mb-0" id="template-alert-error-msg"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="generate-template-btn"
                                    style="background:var(--ss-primary);border-color:var(--ss-primary);">
                                <i class="bi bi-download me-1"></i> Generate &amp; Download
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ ADD BATCH MODAL ═══════════════════════════════════════════ --}}
            <div id="addBatchModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header ss-modal-header">
                            <h5 id="addModalLabel" class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Batch Upload</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-batch-form" action="{{ route('student.bulkuploadsave') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body position-relative">
                                <div id="batch-loader" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.85); z-index: 1000;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Processing Batch...</span>
                                </div>
                                <div class="mb-3">
                                    <label for="title" class="form-label fw-semibold">Batch Title</label>
                                    <input type="text" id="title" name="title" class="form-control" placeholder="Enter batch title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="schoolclassid" class="form-label fw-semibold">School Class &amp; Arm</label>
                                    <select id="schoolclassid" name="schoolclassid" class="form-control" data-choices data-choices-search-true required>
                                        <option value="">Select Class</option>
                                        @foreach ($schoolclasses as $sc)
                                            <option value="{{ $sc->id }}">{{ $sc->schoolclass }} - {{ $sc->arm }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="termid" class="form-label fw-semibold">Term</label>
                                    <select id="termid" name="termid" class="form-control" data-choices data-choices-search-true required>
                                        <option value="">Select Term</option>
                                        @foreach ($schoolterms as $sc)
                                            <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="sessionid" class="form-label fw-semibold">Session</label>
                                    <select id="sessionid" name="sessionid" class="form-control" data-choices data-choices-search-true required>
                                        <option value="">Select Session</option>
                                        @foreach ($schoolsessions as $sc)
                                            <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="filesheet" class="form-label fw-semibold">Upload File</label>
                                    <input type="file" id="filesheet" name="filesheet" class="form-control" accept=".xlsx,.xls,.csv" required>
                                </div>
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn" style="background:var(--ss-primary);border-color:var(--ss-primary);">Add Batch</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ══ IMPORT PROGRESS MODAL ═════════════════════════════════════ --}}
            <div id="importProgressModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header ss-modal-header">
                            <h5 class="modal-title"><i class="ri-loader-4-line me-2"></i>Importing Students</h5>
                        </div>
                        <div class="modal-body text-center">
                            <p class="text-muted mb-3" id="importProgressMessage">Starting import...</p>
                            <div class="progress mb-2" style="height: 10px; border-radius: 10px;">
                                <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background:var(--ss-primary);">0%</div>
                            </div>
                            <p class="small text-muted" id="importProgressCount">0 / 0 rows</p>
                            <div id="importResultIcon" class="mt-3 d-none"></div>
                        </div>
                        <div class="modal-footer d-none" id="importProgressFooter">
                            <button type="button" class="btn btn-outline-warning d-none" id="importViewErrorsBtn">View Errors</button>
                            <button type="button" class="btn btn-primary" style="background:var(--ss-primary);border-color:var(--ss-primary);" data-bs-dismiss="modal" onclick="window.location.reload()">Close &amp; Refresh</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ IMPORT ERRORS MODAL ═══════════════════════════════════════ --}}
            <div id="importErrorsModal" class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header ss-modal-header">
                            <h5 class="modal-title" id="importErrorsTitle"><i class="ri-error-warning-line me-2"></i>Import Errors</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="importErrorsLoading" class="text-center py-3">
                                <div class="spinner-border text-primary" role="status"></div>
                            </div>
                            <div id="importErrorsList" class="d-none"></div>
                            <div id="importErrorsEmpty" class="d-none text-muted text-center py-3">No detailed errors were recorded for this batch.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ UPDATE CLASS MODAL ════════════════════════════════════════ --}}
            <div id="updateClassModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header ss-modal-header">
                            <h5 class="modal-title"><i class="ph-pencil me-2"></i>Update Class</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="update-class-form" action="{{ route('student.updateclass') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body position-relative">
                                <div id="update-class-loader" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.85); z-index: 1000;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Updating Class...</span>
                                </div>
                                <div class="mb-3">
                                    <label for="update_batch_id" class="form-label fw-semibold">Batch ID</label>
                                    <input type="text" id="update_batch_id" name="batch_id" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="update_schoolclass" class="form-label fw-semibold">School Class Name</label>
                                    <input type="text" id="update_schoolclass" name="schoolclass" class="form-control" placeholder="Enter school class name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_arm" class="form-label fw-semibold">Arm Name</label>
                                    <input type="text" id="update_arm" name="arm" class="form-control" placeholder="Enter arm name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_schoolclassid" class="form-label fw-semibold">School Class ID</label>
                                    <input type="text" id="update_schoolclassid" name="schoolclassid" class="form-control" placeholder="Enter school class ID" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_armid" class="form-label fw-semibold">Arm ID</label>
                                    <input type="text" id="update_armid" name="armid" class="form-control" placeholder="Enter arm ID" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_classcategoryid" class="form-label fw-semibold">Class Category ID</label>
                                    <input type="text" id="update_classcategoryid" name="classcategoryid" class="form-control" placeholder="Enter class category ID" required>
                                </div>
                                <div class="alert alert-danger d-none" id="update-alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="update-btn" style="background:var(--ss-primary);border-color:var(--ss-primary);">Update Class</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ══ DELETE BATCH MODAL ════════════════════════════════════════ --}}
            <div id="deleteRecordModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close" id="deleteRecord-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-md-5">
                            <div class="text-center">
                                <div class="text-danger">
                                    <i class="bi bi-trash display-4"></i>
                                </div>
                                <div class="mt-4">
                                    <h3 class="mb-2">Are you sure?</h3>
                                    <p class="text-muted fs-lg mx-3 mb-0">Are you sure you want to remove this batch?</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                <button type="button" class="btn w-sm btn-light btn-hover" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn w-sm btn-danger btn-hover" id="delete-record">
                                    <span id="delete-btn-text">Yes, Delete It!</span>
                                    <span id="delete-btn-loader" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>Deleting...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    /* ══ Toast helper ══ */
    function showToast(msg, type = 'info') {
        const colors = { success:'#16a34a', warning:'#d97706', danger:'#dc2626', info:'#2563eb' };
        const id = 'toast_' + Date.now();
        document.body.insertAdjacentHTML('beforeend',
            `<div id="${id}" class="ss-toast toast align-items-center border-0 show" role="alert"
              style="background:${colors[type]||colors.info};">
              <div class="toast-body"><div class="me-auto">${msg}</div>
              <button class="btn-close btn-close-white ms-2" onclick="this.closest('.ss-toast').remove()"></button></div></div>`);
        setTimeout(() => document.getElementById(id)?.remove(), 4000);
    }

    // ============================================================
    // MODAL BACKDROP CLEANUP
    // ============================================================
    function cleanupStrayModalBackdrop() {
        const anyModalOpen = document.querySelector('.modal.show');
        if (!anyModalOpen) {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }
    }

    let currentDeleteId = null;
    let currentUpdateId = null;
    let importPollTimer = null;
    let lastProgressKey = null;

    // ══════════════════════════════════════════════════════════
    // MULTIPLE DELETE HELPERS
    // ══════════════════════════════════════════════════════════
    function getChkChildren() {
        return document.querySelectorAll('#batchListTable tbody input[name="chk_child"]');
    }

    function toggleRemoveActions() {
        const removeActionsBtn = document.getElementById('remove-actions');
        if (!removeActionsBtn) return;
        const anyChecked = Array.from(getChkChildren()).some(c => c.checked);
        removeActionsBtn.classList.toggle('d-none', !anyChecked);
    }

    function refreshRowHighlight() {
        getChkChildren().forEach(function (chk) {
            const row = chk.closest('tr');
            if (row) row.classList.toggle('selected-row', chk.checked);
        });
    }

    function syncCheckAllState() {
        const checkAll = document.getElementById('checkAll');
        if (!checkAll) return;
        const kids = getChkChildren();
        const total = kids.length;
        const checked = Array.from(kids).filter(c => c.checked).length;
        checkAll.checked = total > 0 && checked === total;
        checkAll.indeterminate = checked > 0 && checked < total;
    }

    function clearAllSelections() {
        getChkChildren().forEach(function (chk) { chk.checked = false; });
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.checked = false;
            checkAll.indeterminate = false;
        }
        refreshRowHighlight();
        toggleRemoveActions();
    }

    // ============================================================
    // MULTIPLE DELETE – Global handler
    // ============================================================
    window.deleteMultiple = function () {
        const selectedIds = Array.from(getChkChildren())
            .filter(chk => chk.checked)
            .map(chk => chk.value || chk.closest('tr')?.querySelector('td.id')?.getAttribute('data-id'))
            .filter(Boolean);

        if (selectedIds.length === 0) {
            showToast('Please select at least one batch to delete.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            html: `You are about to delete <strong>${selectedIds.length}</strong> batch(es).<br>
                   All students, pictures, and related records in those batches will be permanently removed.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: () => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                return axios.post('{{ route("student.batch.bulkDelete") }}', {
                    ids: selectedIds
                }, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.data)
                .catch(error => {
                    Swal.showValidationMessage(
                        error.response?.data?.message || 'Failed to delete batches.'
                    );
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: result.value?.message || 'Batches deleted successfully.',
                    timer: 1600,
                    showConfirmButton: false
                }).then(() => window.location.reload());
            }
        });
    };

    // ============================================================
    // MULTIPLE DELETE — Global delegated listeners
    // ============================================================
    document.addEventListener('change', function (e) {
        const t = e.target;
        if (!t || t.type !== 'checkbox') return;

        if (t.id === 'checkAll') {
            const isChecked = t.checked;
            getChkChildren().forEach(function (chk) { chk.checked = isChecked; });
            refreshRowHighlight();
            toggleRemoveActions();
            return;
        }

        if (t.name === 'chk_child' && t.closest('#batchListTable')) {
            syncCheckAllState();
            refreshRowHighlight();
            toggleRemoveActions();
        }
    });

    document.addEventListener('click', function (e) {
        const label = e.target.closest('#batchListTable label.form-check-label');
        if (!label) return;
        const td = label.closest('td.id');
        if (!td) return;
        const chk = td.querySelector('input[name="chk_child"]');
        if (!chk) return;
        setTimeout(function () {
            syncCheckAllState();
            refreshRowHighlight();
            toggleRemoveActions();
        }, 0);
    });

    // ============================================================
    // TEMPLATE PICKER — checkbox multi-select (global scope)
    // ============================================================
    function tplPickerItems(groupId) {
        const root = document.getElementById(groupId);
        return root ? Array.from(root.querySelectorAll('input[type="checkbox"]')) : [];
    }

    function tplCheckedValues(groupId) {
        return tplPickerItems(groupId)
            .filter(chk => chk.checked)
            .map(chk => chk.value);
    }

    function updateComboCount() {
        const countEl = document.getElementById('tpl-combo-count');
        if (!countEl) return;

        const c = tplCheckedValues('tpl_schoolclass').length;
        const t = tplCheckedValues('tpl_term').length;
        const s = tplCheckedValues('tpl_session').length;
        const total = c * t * s;

        if (total === 0) {
            countEl.textContent = '';
            countEl.classList.remove('text-danger');
            return;
        }

        countEl.textContent = total === 1
            ? '1 template will be generated.'
            : `${total} templates will be generated (downloaded as a .zip).`;

        countEl.classList.toggle('text-danger', total > 60);
        if (total > 60) {
            countEl.textContent += ' Please narrow your selection — max 60 at a time.';
        }
    }

    function resetTemplatePicker() {
        ['tpl_schoolclass', 'tpl_term', 'tpl_session'].forEach(function (id) {
            tplPickerItems(id).forEach(chk => { chk.checked = false; });
            const search = document.querySelector(`[data-tpl-search="${id}"]`);
            if (search) {
                search.value = '';
                applyTplSearch(id, '');
            }
        });
        updateComboCount();
        const err = document.getElementById('template-alert-error-msg');
        if (err) err.classList.add('d-none');
    }

    function applyTplSearch(groupId, term) {
        const needle = (term || '').trim().toLowerCase();
        tplPickerItems(groupId).forEach(function (chk) {
            const label = chk.closest('.tpl-check');
            if (!label) return;
            const text = (label.textContent || '').toLowerCase();
            label.classList.toggle('is-hidden', needle !== '' && !text.includes(needle));
        });
    }

    /* Delegated listeners for template picker — global, attach once */
    document.addEventListener('change', function (e) {
        const t = e.target;
        if (!t || t.type !== 'checkbox') return;
        if (t.closest('#tpl_schoolclass, #tpl_term, #tpl_session')) {
            updateComboCount();
        }
    });

    document.addEventListener('input', function (e) {
        const t = e.target;
        if (t && t.matches('[data-tpl-search]')) {
            applyTplSearch(t.getAttribute('data-tpl-search'), t.value);
        }
    });

    document.addEventListener('click', function (e) {
        const toggleBtn = e.target.closest('[data-tpl-toggle]');
        if (toggleBtn) {
            e.preventDefault();
            const groupId = toggleBtn.getAttribute('data-tpl-toggle');
            const items = tplPickerItems(groupId).filter(function (chk) {
                const label = chk.closest('.tpl-check');
                return !label || !label.classList.contains('is-hidden');
            });
            const allChecked = items.length > 0 && items.every(chk => chk.checked);
            items.forEach(chk => { chk.checked = !allChecked; });
            updateComboCount();
            return;
        }

        const clearBtn = e.target.closest('[data-tpl-clear]');
        if (clearBtn) {
            e.preventDefault();
            const groupId = clearBtn.getAttribute('data-tpl-clear');
            tplPickerItems(groupId).forEach(chk => { chk.checked = false; });
            updateComboCount();
        }
    });

    const generateTemplateModalEl = document.getElementById('generateTemplateModal');
    if (generateTemplateModalEl) {
        generateTemplateModalEl.addEventListener('shown.bs.modal', updateComboCount);
        generateTemplateModalEl.addEventListener('hidden.bs.modal', resetTemplatePicker);
    }

    // ============================================================
    // MAIN INIT
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.remove-item-btn');
        const updateButtons = document.querySelectorAll('.update-item-btn');
        const deleteRecordModal = document.getElementById('deleteRecordModal');
        const updateClassModal = document.getElementById('updateClassModal');
        const deleteBtn = document.getElementById('delete-record');
        const updateForm = document.getElementById('update-class-form');
        const addBatchForm = document.getElementById('add-batch-form');

        document.querySelectorAll('.modal').forEach(function (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', cleanupStrayModalBackdrop);
        });

        syncCheckAllState();
        refreshRowHighlight();
        toggleRemoveActions();

        // ============================================================
        // CHOICES.JS RE-INIT – Add Batch Modal
        // ============================================================
        function initChoicesForAddBatchModal() {
            ['schoolclassid', 'termid', 'sessionid'].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el || typeof Choices === 'undefined') return;

                if (el._choicesInstance) {
                    try { el._choicesInstance.destroy(); } catch (e) {}
                    el._choicesInstance = null;
                }

                el._choicesInstance = new Choices(el, {
                    searchEnabled: true,
                    shouldSort: false,
                    itemSelectText: '',
                });
            });
        }

        const addBatchModalEl = document.getElementById('addBatchModal');
        if (addBatchModalEl) {
            addBatchModalEl.addEventListener('shown.bs.modal', initChoicesForAddBatchModal);

            addBatchModalEl.addEventListener('hidden.bs.modal', function () {
                ['schoolclassid', 'termid', 'sessionid'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (!el) return;
                    if (el._choicesInstance) {
                        try { el._choicesInstance.setChoiceByValue(''); } catch (e) {}
                    }
                    el.value = '';
                });
            });
        }

        // ===== Delete single batch =====
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                currentDeleteId = this.getAttribute('data-id');
                if (deleteRecordModal) new bootstrap.Modal(deleteRecordModal).show();
            });
        });

        // ===== Update batch class =====
        updateButtons.forEach(button => {
            button.addEventListener('click', function () {
                currentUpdateId = this.getAttribute('data-id');
                document.getElementById('update_batch_id').value = currentUpdateId;
                document.getElementById('update_schoolclass').value = this.getAttribute('data-schoolclass');
                document.getElementById('update_arm').value = this.getAttribute('data-arm');
                document.getElementById('update_schoolclassid').value = this.getAttribute('data-schoolclassid');
                document.getElementById('update_armid').value = this.getAttribute('data-armid');
                document.getElementById('update_classcategoryid').value = this.getAttribute('data-classcategoryid') || '';
                if (updateClassModal) new bootstrap.Modal(updateClassModal).show();
            });
        });

        if (deleteBtn) {
            deleteBtn.addEventListener('click', handleDeleteConfirmation);
        }

        function handleDeleteConfirmation() {
            if (!currentDeleteId) return;

            const deleteBtnText = document.getElementById('delete-btn-text');
            const deleteBtnLoader = document.getElementById('delete-btn-loader');
            deleteBtnText.classList.add('d-none');
            deleteBtnLoader.classList.remove('d-none');
            deleteBtn.disabled = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            axios.delete(`/student/deletestudentbatch?studentbatchid=${currentDeleteId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            })
            .then(function (response) {
                const modal = bootstrap.Modal.getInstance(deleteRecordModal);
                if (modal) modal.hide();
                Swal.fire({ icon: 'success', title: 'Success', text: response.data.message || 'Batch deleted successfully!', showConfirmButton: false, timer: 1500 })
                    .then(() => window.location.reload());
            })
            .catch(function (error) {
                deleteBtnText.classList.remove('d-none');
                deleteBtnLoader.classList.add('d-none');
                deleteBtn.disabled = false;
                const modal = bootstrap.Modal.getInstance(deleteRecordModal);
                if (modal) modal.hide();
                Swal.fire({ icon: error.response?.status === 404 ? 'warning' : 'error', title: 'Error', text: error.response?.data?.message || 'Error deleting batch', showConfirmButton: true });
            });
        }

        // ===== Update class form =====
        if (updateForm) {
            updateForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const updateBtnText = document.getElementById('update-btn');
                const updateLoader = document.getElementById('update-class-loader');
                const errorMsg = document.getElementById('update-alert-error-msg');

                updateBtnText.disabled = true;
                updateLoader.classList.remove('d-none');

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const formData = new FormData(updateForm);

                axios.post(updateForm.action, formData, { headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'multipart/form-data' } })
                .then(function (response) {
                    const modal = bootstrap.Modal.getInstance(updateClassModal);
                    if (modal) modal.hide();
                    showToast(response.data.message || 'Class updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 900);
                })
                .catch(function (error) {
                    updateBtnText.disabled = false;
                    updateLoader.classList.add('d-none');
                    errorMsg.textContent = error.response?.data?.message || 'Error updating class';
                    errorMsg.classList.remove('d-none');
                });
            });
        }

        // ===== Generate template(s) — checkbox multi-select =====
        const generateBtn = document.getElementById('generate-template-btn');
        if (generateBtn) {
            generateBtn.addEventListener('click', function () {
                const schoolclassids = tplCheckedValues('tpl_schoolclass');
                const termids        = tplCheckedValues('tpl_term');
                const sessionids     = tplCheckedValues('tpl_session');
                const rows           = document.getElementById('tpl_rows').value || 30;
                const errorMsg       = document.getElementById('template-alert-error-msg');
                const loader         = document.getElementById('template-loader');

                errorMsg.classList.add('d-none');

                if (!schoolclassids.length || !termids.length || !sessionids.length) {
                    errorMsg.textContent = 'Please select at least one class, term, and session.';
                    errorMsg.classList.remove('d-none');
                    return;
                }

                const totalCombinations = schoolclassids.length * termids.length * sessionids.length;
                if (totalCombinations > 60) {
                    errorMsg.textContent = `That's ${totalCombinations} combinations — please narrow your selection to 60 or fewer at a time.`;
                    errorMsg.classList.remove('d-none');
                    return;
                }

                loader.classList.remove('d-none');
                generateBtn.disabled = true;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                axios.post('{{ route("student.batch.generateTemplateBulk") }}', {
                    schoolclassids, termids, sessionids, rows
                }, {
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    responseType: 'blob',
                    timeout: 180000
                })
                .then(function (response) {
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');
                    link.href = url;

                    let filename = totalCombinations > 1 ? 'batch-templates.zip' : 'student-batch-template.xlsx';
                    const contentDisposition = response.headers['content-disposition'];
                    if (contentDisposition) {
                        const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                        if (match && match[1]) filename = match[1].replace(/['"]/g, '');
                    }

                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    const modal = bootstrap.Modal.getInstance(document.getElementById('generateTemplateModal'));
                    if (modal) modal.hide();
                    showToast(
                        totalCombinations > 1 ? `${totalCombinations} templates downloaded as a zip!` : 'Template downloaded successfully!',
                        'success'
                    );

                    setTimeout(cleanupStrayModalBackdrop, 350);
                })
                .catch(async function (error) {
                    let message = 'Failed to generate template(s).';
                    if (error.response?.data instanceof Blob) {
                        try {
                            const text = await error.response.data.text();
                            message = JSON.parse(text).message || message;
                        } catch (e) {}
                    } else if (error.response?.data?.message) {
                        message = error.response.data.message;
                    }
                    errorMsg.textContent = message;
                    errorMsg.classList.remove('d-none');
                })
                .finally(function () {
                    loader.classList.add('d-none');
                    generateBtn.disabled = false;
                });
            });
        }

        // ===== Add batch (queued import) =====
        if (addBatchForm) {
            addBatchForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const loader = document.getElementById('batch-loader');
                const errorMsg = document.getElementById('alert-error-msg');
                const addBtn = document.getElementById('add-btn');

                errorMsg.classList.add('d-none');
                loader.classList.remove('d-none');
                addBtn.disabled = true;

                const formData = new FormData(addBatchForm);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                axios.post(addBatchForm.action, formData, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(function (response) {
                    loader.classList.add('d-none');
                    addBtn.disabled = false;

                    if (response.data.success) {
                        const addModal = bootstrap.Modal.getInstance(document.getElementById('addBatchModal'));
                        if (addModal) addModal.hide();

                        addBatchForm.reset();
                        startProgressPolling(response.data.progress_key, response.data.batch_id);
                    } else {
                        errorMsg.textContent = response.data.message || 'Failed to queue import.';
                        errorMsg.classList.remove('d-none');
                    }
                })
                .catch(function (error) {
                    loader.classList.add('d-none');
                    addBtn.disabled = false;
                    errorMsg.textContent = error.response?.data?.message || 'Failed to queue import.';
                    errorMsg.classList.remove('d-none');
                });
            });
        }

        function startProgressPolling(progressKey, batchId) {
            lastProgressKey = progressKey;

            const modal = new bootstrap.Modal(document.getElementById('importProgressModal'));
            const bar = document.getElementById('importProgressBar');
            const message = document.getElementById('importProgressMessage');
            const count = document.getElementById('importProgressCount');
            const resultIcon = document.getElementById('importResultIcon');
            const footer = document.getElementById('importProgressFooter');
            const viewErrorsBtn = document.getElementById('importViewErrorsBtn');

            bar.style.width = '0%';
            bar.textContent = '0%';
            bar.className = 'progress-bar progress-bar-striped progress-bar-animated';
            bar.style.background = 'var(--ss-primary)';
            message.textContent = 'Waiting to start...';
            count.textContent = '0 / 0 rows';
            resultIcon.classList.add('d-none');
            resultIcon.innerHTML = '';
            footer.classList.add('d-none');
            viewErrorsBtn.classList.add('d-none');

            modal.show();

            if (importPollTimer) clearInterval(importPollTimer);

            importPollTimer = setInterval(function () {
                axios.get('{{ route("student.batch.importProgress") }}', { params: { progress_key: progressKey } })
                .then(function (response) {
                    const p = response.data.progress;
                    const pct = p.total > 0 ? Math.round((p.progress / p.total) * 100) : 0;

                    bar.style.width = pct + '%';
                    bar.textContent = pct + '%';
                    count.textContent = `${p.progress} / ${p.total} rows`;
                    message.textContent = p.message || '';

                    if (p.status === 'complete') {
                        clearInterval(importPollTimer);
                        bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
                        bar.style.background = 'var(--ss-success)';
                        resultIcon.classList.remove('d-none');
                        resultIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success display-4"></i>';
                        footer.classList.remove('d-none');
                    } else if (p.status === 'partial') {
                        clearInterval(importPollTimer);
                        bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
                        bar.style.background = 'var(--ss-warning)';
                        resultIcon.classList.remove('d-none');
                        resultIcon.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning display-4"></i>';
                        footer.classList.remove('d-none');
                        viewErrorsBtn.classList.remove('d-none');
                        viewErrorsBtn.onclick = () => showImportErrors(batchId);
                    } else if (p.status === 'failed') {
                        clearInterval(importPollTimer);
                        bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
                        bar.style.background = 'var(--ss-danger)';
                        resultIcon.classList.remove('d-none');
                        resultIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger display-4"></i>';
                        footer.classList.remove('d-none');
                        viewErrorsBtn.classList.remove('d-none');
                        viewErrorsBtn.onclick = () => showImportErrors(batchId);
                    }
                })
                .catch(function () {
                    // transient network hiccup — keep polling
                });
            }, 1500);
        }

        // ===== View import errors =====
        document.querySelectorAll('.view-errors-btn').forEach(button => {
            button.addEventListener('click', function () {
                showImportErrors(this.getAttribute('data-id'));
            });
        });

        function showImportErrors(batchId) {
            const modalEl = document.getElementById('importErrorsModal');
            const modal = new bootstrap.Modal(modalEl);
            const loading = document.getElementById('importErrorsLoading');
            const list = document.getElementById('importErrorsList');
            const empty = document.getElementById('importErrorsEmpty');
            const title = document.getElementById('importErrorsTitle');

            loading.classList.remove('d-none');
            list.classList.add('d-none');
            empty.classList.add('d-none');
            list.innerHTML = '';
            title.innerHTML = '<i class="ri-error-warning-line me-2"></i>Import Errors';

            modal.show();

            axios.get(`/student/batch/${batchId}/errors`)
                .then(function (response) {
                    loading.classList.add('d-none');
                    const data = response.data;
                    title.innerHTML = `<i class="ri-error-warning-line me-2"></i>Import Errors — ${data.title || 'Batch'}`;

                    if (!data.errors || data.errors.length === 0) {
                        empty.classList.remove('d-none');
                        return;
                    }

                    const html = data.errors.map(function (err) {
                        const rowLabel = err.row ? `Row ${err.row}` : 'General error';
                        const messages = Array.isArray(err.errors) ? err.errors.join('<br>') : err.errors;
                        return `
                            <div class="alert alert-warning mb-2">
                                <strong>${rowLabel}</strong>
                                ${err.attribute ? ` — <em>${err.attribute}</em>` : ''}
                                <div class="small mt-1">${messages}</div>
                            </div>
                        `;
                    }).join('');

                    list.innerHTML = html;
                    list.classList.remove('d-none');
                })
                .catch(function () {
                    loading.classList.add('d-none');
                    empty.textContent = 'Failed to load error details.';
                    empty.classList.remove('d-none');
                });
        }
    });
</script>
@endsection