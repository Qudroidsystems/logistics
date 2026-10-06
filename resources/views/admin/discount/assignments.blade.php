{{-- resources/views/admin/discount/assignments.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --disc-primary: #1e3a5f;
    --disc-success: #16a34a;
    --disc-warning: #d97706;
    --disc-danger: #dc2626;
    --disc-border: #e2e8f0;
}

/* ── Assignment cards ─────────────────────────────────── */
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500;
}
.status-active  { background: #dcfce7; color: #16a34a; }
.status-expired { background: #fee2e2; color: #dc2626; }
.status-removed { background: #f3f4f6; color: #6b7280; }

.assignment-card {
    background: white;
    border: 1px solid var(--disc-border);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    transition: all 0.2s;
}
.assignment-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

/* ── Multi-step assign modal ──────────────────────────── */
.ad-modal-content {
    border: none !important;
    border-radius: 20px !important;
    overflow: hidden !important;
}
.ad-header {
    background: linear-gradient(135deg, #4338ca 0%, #7c3aed 100%);
    padding: 20px 28px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.ad-header-icon {
    width: 46px; height: 46px;
    background: rgba(255,255,255,0.18);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff; flex-shrink: 0;
}
.ad-steps-bar {
    display: flex; align-items: center; justify-content: center;
    padding: 14px 28px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.ad-step {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 500; color: #94a3b8;
    transition: color 0.25s;
}
.ad-step.active { color: #4f46e5; }
.ad-step.done   { color: #059669; }
.ad-step-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: #e2e8f0; color: #94a3b8;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 500; flex-shrink: 0;
    border: 1.5px solid transparent;
    transition: all 0.25s;
}
.ad-step.active .ad-step-num { background: #ede9fe; color: #4f46e5; border-color: #4f46e5; }
.ad-step.done   .ad-step-num { background: #d1fae5; color: #059669; border-color: #059669; }
.ad-step-line { flex: 1; height: 1.5px; background: #e2e8f0; margin: 0 12px; max-width: 60px; }

.ad-body {
    padding: 24px 28px;
    background: #f8fafc;
    max-height: 520px;
    overflow-y: auto;
}

.ad-section-card {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 16px;
}
.ad-section-head {
    padding: 11px 16px;
    font-size: 11.5px; font-weight: 600;
    color: #64748b; background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    text-transform: uppercase; letter-spacing: 0.4px;
    display: flex; align-items: center; gap: 8px;
}
.ad-section-body { padding: 16px; }

/* Student results list */
.ad-student-results {
    border: 1px solid #e2e8f0;
    border-radius: 8px; overflow: hidden;
    max-height: 320px; overflow-y: auto;
    margin-top: 8px;
}
.ad-student-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; cursor: pointer;
    border-bottom: 0.5px solid #f1f5f9;
    transition: background 0.12s;
}
.ad-student-row:last-child { border-bottom: none; }
.ad-student-row:hover { background: #ede9fe; }
.ad-student-row.selected { background: #ede9fe; border-left: 3px solid #4f46e5; }

.ad-avatar {
    width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 500; overflow: hidden;
}
.ad-avatar img { width: 100%; height: 100%; object-fit: cover; }

.ad-class-badge {
    font-size: 11px; padding: 3px 10px; border-radius: 20px;
    background: #ede9fe; color: #4f46e5; font-weight: 500; white-space: nowrap;
}

/* Selected student banner */
.ad-selected-banner {
    background: #ede9fe;
    border: 1.5px solid #c4b5fd;
    border-radius: 10px; padding: 14px 16px;
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 16px;
}
.ad-selected-avatar {
    width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 500; color: #fff;
    overflow: hidden;
}
.ad-selected-avatar img { width: 100%; height: 100%; object-fit: cover; }
.ad-change-btn {
    margin-left: auto;
    font-size: 12px; padding: 5px 12px;
    background: #fff; border: 0.5px solid #c4b5fd;
    border-radius: 8px; color: #4f46e5;
    cursor: pointer; font-weight: 500;
    transition: all 0.15s;
    white-space: nowrap;
}
.ad-change-btn:hover { background: #4f46e5; color: #fff; }

/* Discount option cards */
.ad-discount-opt {
    border: 1.5px solid #e2e8f0;
    border-radius: 8px; padding: 13px 15px;
    cursor: pointer; transition: all 0.15s;
    margin-bottom: 8px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.ad-discount-opt:hover { border-color: #4f46e5; background: #f5f3ff; }
.ad-discount-opt.picked { border-color: #4f46e5; background: #ede9fe; }
.ad-discount-opt .check { display: none; color: #4f46e5; font-size: 18px; margin-right: 4px; }
.ad-discount-opt.picked .check { display: inline-block; }

.ad-disc-value {
    font-size: 14px; font-weight: 500;
    color: #4f46e5; background: #fff;
    border: 0.5px solid #c4b5fd;
    border-radius: 8px; padding: 4px 12px;
    white-space: nowrap; transition: all 0.15s;
}
.ad-discount-opt.picked .ad-disc-value { background: #4f46e5; color: #fff; border-color: #4f46e5; }

/* Summary rows */
.ad-summary-row {
    display: flex; padding: 10px 0;
    border-bottom: 0.5px solid #f1f5f9; font-size: 13px;
}
.ad-summary-row:last-child { border-bottom: none; }
.ad-summary-row .lbl { width: 38%; color: #64748b; }
.ad-summary-row .val { flex: 1; font-weight: 500; color: #1e293b; }

/* Footer */
.ad-footer {
    padding: 16px 28px; background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center;
}
.ad-btn-back {
    background: #fff; border: 0.5px solid #e2e8f0;
    border-radius: 8px; padding: 9px 20px;
    font-size: 13px; font-weight: 500; cursor: pointer; color: #1e293b;
    display: flex; align-items: center; gap: 6px;
    transition: background 0.15s;
}
.ad-btn-back:hover { background: #f1f5f9; }
.ad-btn-next {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border: none; border-radius: 8px; padding: 9px 22px;
    font-size: 13px; font-weight: 500; cursor: pointer; color: #fff;
    display: flex; align-items: center; gap: 6px;
    transition: opacity 0.15s, transform 0.1s;
}
.ad-btn-next:hover:not(:disabled) { transform: translateY(-1px); opacity: 0.92; }
.ad-btn-next:disabled { opacity: 0.45; cursor: not-allowed; }

.ad-spinner {
    display: inline-block; width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff; border-radius: 50%;
    animation: adSpin 0.7s linear infinite; vertical-align: -2px;
}
@keyframes adSpin { to { transform: rotate(360deg); } }

.ad-no-results {
    text-align: center; padding: 28px; color: #94a3b8; font-size: 13px;
}
.ad-loading-cell {
    text-align: center; padding: 20px; color: #94a3b8; font-size: 13px;
}
.ad-success-screen { text-align: center; padding: 32px 20px; }
.ad-success-icon {
    width: 64px; height: 64px; background: #d1fae5; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px; font-size: 28px; color: #059669;
}

.student-info-detail {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.student-info-detail span {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold" style="color: var(--disc-primary);">
                        <i class="ri-user-settings-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.discount.index') }}">Discounts</a></li>
                            <li class="breadcrumb-item active">Assignments</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.discount.index') }}" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i>Back to Discounts
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.discount.assignments') }}">
                All <span class="badge bg-secondary ms-1">{{ array_sum($statusCounts) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'active' ? 'active' : '' }}" href="{{ route('admin.discount.assignments', ['status' => 'active']) }}">
                Active <span class="badge bg-success ms-1">{{ $statusCounts['active'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'expired' ? 'active' : '' }}" href="{{ route('admin.discount.assignments', ['status' => 'expired']) }}">
                Expired <span class="badge bg-secondary ms-1">{{ $statusCounts['expired'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'removed' ? 'active' : '' }}" href="{{ route('admin.discount.assignments', ['status' => 'removed']) }}">
                Removed <span class="badge bg-danger ms-1">{{ $statusCounts['removed'] ?? 0 }}</span>
            </a>
        </li>
    </ul>

    {{-- Search + Assign button --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="position-relative">
                        <input type="text" class="form-control ps-5" id="searchInput"
                               placeholder="Search by student name or admission number…">
                        <i class="ri-search-line position-absolute"
                           style="left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success" id="assignDiscountBtn"
                            data-bs-toggle="modal" data-bs-target="#assignModal">
                        <i class="ri-add-line me-1"></i>Assign Discount
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Assignments Grid --}}
    <div class="row" id="assignmentsContainer">
        @forelse($assignments as $assignment)
        <div class="col-md-6 col-lg-4">
            <div class="assignment-card"
                 data-search="{{ strtolower($assignment->student->firstname ?? '') }} {{ strtolower($assignment->student->lastname ?? '') }} {{ $assignment->student->admissionNo ?? '' }}">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="mb-1 fw-bold">{{ $assignment->discount->title ?? 'N/A' }}</h6>
                        <small class="text-muted">{{ $assignment->discount->discount_no ?? 'N/A' }}</small>
                    </div>
                    <span class="status-badge status-{{ $assignment->status }}">
                        {{ ucfirst($assignment->status) }}
                    </span>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="small text-muted">Student</div>
                            <div class="fw-semibold">
                                {{ $assignment->student->firstname ?? '' }}
                                {{ $assignment->student->lastname ?? '' }}
                            </div>
                            <div class="small text-muted">Adm: {{ $assignment->student->admissionNo ?? 'N/A' }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Discount Value</div>
                            <div class="fw-bold text-success">
                                @if($assignment->value_type == 'percentage')
                                    {{ $assignment->value }}%
                                @else
                                    ₦{{ number_format($assignment->value, 2) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="row">
                        <div class="col-6">
                            <div class="small text-muted">Effective From</div>
                            <div>{{ \Carbon\Carbon::parse($assignment->effective_from)->format('d M Y') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Effective To</div>
                            <div>
                                {{ $assignment->effective_to
                                    ? \Carbon\Carbon::parse($assignment->effective_to)->format('d M Y')
                                    : 'Ongoing' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if($assignment->status == 'active')
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-danger remove-btn flex-grow-1"
                            data-id="{{ $assignment->id }}">
                        <i class="ri-close-line me-1"></i>Remove
                    </button>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                No discount assignments found.
            </div>
        </div>
        @endforelse
    </div>

    @if($assignments->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $assignments->links() }}
    </div>
    @endif

</div>
</div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ASSIGN DISCOUNT MODAL  —  Multi-step wizard
═══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="assignModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:840px;">
        <div class="modal-content ad-modal-content">

            {{-- Header --}}
            <div class="ad-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="ad-header-icon">
                        <i class="ri-discount-percent-line"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 text-white fw-semibold" style="font-size:17px;">Assign Discount</h5>
                        <small style="color:rgba(255,255,255,.75);">Attach a discount to a student account</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" id="adModalClose"></button>
            </div>

            {{-- Step bar --}}
            <div class="ad-steps-bar">
                @foreach([1 => 'Find student', 2 => 'Select discount', 3 => 'Set dates', 4 => 'Confirm'] as $n => $label)
                    <div class="ad-step {{ $n === 1 ? 'active' : '' }}" id="adSb{{ $n }}">
                        <div class="ad-step-num">{{ $n }}</div>
                        <span>{{ $label }}</span>
                    </div>
                    @if($n < 4)
                        <div class="ad-step-line"></div>
                    @endif
                @endforeach
            </div>

            {{-- Body --}}
            <div class="ad-body" id="adBody">

                {{-- ── STEP 1: Search student ── --}}
                <div id="adStep1">
                    <div class="ad-section-card">
                        <div class="ad-section-head">
                            <i class="ri-search-line"></i> Search student
                        </div>
                        <div class="ad-section-body">
                            <div class="position-relative mb-1">
                                <input type="text" id="adStudentSearch"
                                       class="form-control ps-5"
                                       placeholder="Type student name or admission number…"
                                       autocomplete="off">
                                <i class="ri-search-line position-absolute"
                                   style="left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;"></i>
                            </div>
                            <div id="adStudentResults" class="ad-student-results">
                                <div class="ad-no-results">
                                    <i class="ri-user-search-line ri-2x d-block mb-2"></i>
                                    Type at least 2 characters to search
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── STEP 2: Select discount ── --}}
                <div id="adStep2" style="display:none;">
                    <div id="adStep2Banner"></div>
                    <div class="ad-section-card">
                        <div class="ad-section-head">
                            <i class="ri-discount-percent-line"></i> Choose a discount
                        </div>
                        <div class="ad-section-body" id="adDiscountList">
                            <div class="ad-loading-cell">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Loading discounts…
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── STEP 3: Dates ── --}}
                <div id="adStep3" style="display:none;">
                    <div id="adStep3Banner"></div>
                    <div class="ad-section-card">
                        <div class="ad-section-head">
                            <i class="ri-calendar-line"></i> Validity period
                        </div>
                        <div class="ad-section-body">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">
                                        Effective From <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" id="adEffectiveFrom" class="form-control"
                                           value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">
                                        Effective To
                                        <span class="text-muted fw-normal">(optional)</span>
                                    </label>
                                    <input type="date" id="adEffectiveTo" class="form-control">
                                    <div class="form-text">Leave empty for ongoing</div>
                                </div>
                            </div>
                            <div>
                                <label class="form-label fw-semibold small">
                                    Reason
                                    <span class="text-muted fw-normal">(optional)</span>
                                </label>
                                <textarea id="adReason" class="form-control" rows="2"
                                          placeholder="Reason for assigning this discount…"
                                          style="resize:vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── STEP 4: Summary + confirm ── --}}
                <div id="adStep4" style="display:none;">
                    <div id="adStep4Banner"></div>
                    <div class="ad-section-card mt-3">
                        <div class="ad-section-head">
                            <i class="ri-file-list-3-line"></i> Assignment summary
                        </div>
                        <div class="ad-section-body" id="adSummaryContent"></div>
                    </div>
                    <div style="background:#fffbeb;border:0.5px solid #fde68a;border-radius:8px;
                                padding:11px 14px;font-size:12.5px;color:#92400e;
                                display:flex;gap:8px;align-items:center;">
                        <i class="ri-alert-line" style="font-size:16px;flex-shrink:0;"></i>
                        <span>Please review carefully. The discount will be applied immediately upon confirmation.</span>
                    </div>
                </div>

                {{-- Error banner --}}
                <div class="alert alert-danger d-none mt-3" id="adErrors"></div>

            </div>{{-- /ad-body --}}

            {{-- Footer --}}
            <div class="ad-footer">
                <button type="button" class="ad-btn-back"
                        id="adBtnBack" style="visibility:hidden;"
                        onclick="adGoBack()">
                    <i class="ri-arrow-left-line"></i> Back
                </button>
                <button type="button" class="ad-btn-next"
                        id="adBtnNext" disabled
                        onclick="adGoNext()">
                    Continue <i class="ri-arrow-right-line"></i>
                </button>
            </div>

        </div>{{-- /modal-content --}}
    </div>
</div>

{{-- Remove Confirmation Modal --}}
<div class="modal fade" id="removeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-close-line me-2"></i>Remove Discount</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove this discount from the student?</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reason for Removal</label>
                    <textarea id="removeReason" class="form-control" rows="3"
                              placeholder="Optional reason…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveBtn">Remove</button>
            </div>
        </div>
    </div>
</div>

{{-- Image Zoom Modal for Student Photos --}}
<div class="modal fade image-zoom-modal" id="imageZoomModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 20px; right: 30px; z-index: 1060; background-color: rgba(0,0,0,0.7); border-radius: 50%; padding: 12px;"></button>
            <div class="modal-body text-center">
                <img id="zoomedImage" src="" alt="Student Photo" class="zoomed-image" style="max-width: 90vw; max-height: 75vh; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.3); border: 4px solid white; cursor: pointer;">
                <div class="zoomed-image-name" id="zoomedImageName" style="color: white; margin-top: 20px; font-size: 18px; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.3); background: rgba(0,0,0,0.5); padding: 8px 20px; border-radius: 40px; display: inline-block;"></div>
                <div class="zoomed-image-details" id="zoomedImageDetails" style="color: rgba(255,255,255,0.8); margin-top: 8px; font-size: 14px; text-align: center;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;
let removeId = null;

/* ═══════════════════════════════════════════════════════════════
   ASSIGN MODAL — state & helpers
═══════════════════════════════════════════════════════════════ */
let adCurrentStep  = 1;
let adSelectedStudent  = null;
let adSelectedDiscount = null;
let adSearchTimer  = null;

// Avatar colours cycling on student id
const AD_AVATAR_PALETTES = [
    ['#c7d2fe','#4338ca'], ['#bbf7d0','#065f46'], ['#fde68a','#92400e'],
    ['#fecaca','#991b1b'], ['#ddd6fe','#5b21b6'], ['#bfdbfe','#1e40af'],
    ['#fbcfe8','#9d174d'], ['#d9f99d','#365314'],
];
function adAvatarColors(id) {
    return AD_AVATAR_PALETTES[id % AD_AVATAR_PALETTES.length];
}
function adInitials(s) {
    return ((s.firstname || '?')[0] + (s.lastname || '?')[0]).toUpperCase();
}
function adFormatValue(discount) {
    if (!discount) return '';
    return discount.value_type === 'percentage'
        ? parseFloat(discount.value) + '%'
        : '₦' + parseFloat(discount.value).toLocaleString('en-NG', { minimumFractionDigits: 2 });
}
function adFormatDate(str) {
    if (!str) return 'Ongoing';
    const d = new Date(str);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

/* ── Step bar renderer ── */
function adSetStep(n) {
    [1, 2, 3, 4].forEach(i => {
        const el  = document.getElementById('adSb' + i);
        const num = el.querySelector('.ad-step-num');
        el.classList.remove('active', 'done');
        if (i < n) {
            el.classList.add('done');
            num.innerHTML = '<i class="ri-check-line" style="font-size:12px;"></i>';
        } else {
            num.textContent = i;
            if (i === n) el.classList.add('active');
        }
    });
}

/* ── Student banner (shown on steps 2-4) ── */
function adStudentBanner(student, discount) {
    const [bg, fg] = adAvatarColors(student.id);
    const className = student.class_display || (student.class_name && student.arm_name ? `${student.class_name} ${student.arm_name}` : (student.class_name || 'N/A'));
    const termDisplay = student.current_term_display || (student.term && student.session ? `${student.term} · ${student.session}` : 'Not assigned');

    const pictureHtml = student.picture
        ? `<img src="${student.picture}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`
        : adInitials(student);

    return `
    <div class="ad-selected-banner">
        <div class="ad-selected-avatar" style="background:linear-gradient(135deg,${bg},${fg});">
            ${pictureHtml}
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:600;color:#1e293b;">
                ${student.firstname} ${student.lastname}
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">
                <i class="ri-id-card-line" style="vertical-align:-2px;margin-right:3px;"></i>${student.admissionNo || '—'}
            </div>
            <div class="student-info-detail">
                <span><i class="ri-building-line"></i> ${className}</span>
                <span><i class="ri-calendar-line"></i> ${termDisplay}</span>
            </div>
            ${discount
                ? `<span style="display:inline-block;margin-top:5px;background:#4f46e5;color:#fff;border-radius:20px;padding:2px 12px;font-size:11px;font-weight:500;">
                       <i class="ri-discount-percent-line" style="vertical-align:-2px;margin-right:3px;"></i>${discount.title}
                   </span>`
                : ''}
        </div>
        <button type="button" class="ad-change-btn" onclick="adGoToStep(1)">
            <i class="ri-pencil-line me-1"></i>Change
        </button>
    </div>`;
}

/* ── Navigate to a step ── */
function adGoToStep(n) {
    adCurrentStep = n;
    [1, 2, 3, 4].forEach(i => {
        document.getElementById('adStep' + i).style.display = i === n ? 'block' : 'none';
    });
    document.getElementById('adErrors').classList.add('d-none');
    adSetStep(n);

    const btnBack = document.getElementById('adBtnBack');
    const btnNext = document.getElementById('adBtnNext');
    btnBack.style.visibility = n > 1 ? 'visible' : 'hidden';

    if (n === 1) {
        btnNext.disabled = !adSelectedStudent;
        btnNext.innerHTML = 'Continue <i class="ri-arrow-right-line"></i>';

    } else if (n === 2) {
        document.getElementById('adStep2Banner').innerHTML = adStudentBanner(adSelectedStudent, null);
        adRenderDiscounts();
        btnNext.disabled = !adSelectedDiscount;
        btnNext.innerHTML = 'Continue <i class="ri-arrow-right-line"></i>';

    } else if (n === 3) {
        document.getElementById('adStep3Banner').innerHTML = adStudentBanner(adSelectedStudent, adSelectedDiscount);
        btnNext.disabled = false;
        btnNext.innerHTML = 'Review <i class="ri-arrow-right-line"></i>';

    } else if (n === 4) {
        document.getElementById('adStep4Banner').innerHTML = adStudentBanner(adSelectedStudent, adSelectedDiscount);
        adRenderSummary();
        btnNext.disabled = false;
        btnNext.innerHTML = '<i class="ri-check-line"></i> Confirm & Assign';
    }
}

function adGoNext() {
    if (adCurrentStep === 4) { adSubmit(); return; }
    adGoToStep(adCurrentStep + 1);
}
function adGoBack() {
    if (adCurrentStep > 1) adGoToStep(adCurrentStep - 1);
}

/* ── STEP 1: Student search ── */
document.getElementById('adStudentSearch').addEventListener('input', function () {
    clearTimeout(adSearchTimer);
    const val = this.value.trim();
    const box = document.getElementById('adStudentResults');

    if (val.length < 2) {
        box.innerHTML = `<div class="ad-no-results">
            <i class="ri-user-search-line ri-2x d-block mb-2"></i>
            Type at least 2 characters to search
        </div>`;
        return;
    }

    box.innerHTML = `<div class="ad-loading-cell">
        <div class="spinner-border spinner-border-sm text-primary me-2"></div>Searching…
    </div>`;

    adSearchTimer = setTimeout(() => {
        const discId = adSelectedDiscount ? adSelectedDiscount.id : '';
        fetch(`{{ route("admin.discount.eligible-students") }}?q=${encodeURIComponent(val)}&discount_id=${discId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.students || !data.students.length) {
                    box.innerHTML = `<div class="ad-no-results">
                        <i class="ri-search-eye-line ri-2x d-block mb-2"></i>
                        No students found
                    </div>`;
                    return;
                }
                box.innerHTML = data.students.map(s => {
                    const [bg, fg] = adAvatarColors(s.id);
                    const className = s.class_display || (s.class_name && s.arm_name ? `${s.class_name} ${s.arm_name}` : (s.class_name || 'N/A'));
                    const termDisplay = s.current_term_display || (s.term && s.session ? `${s.term} · ${s.session}` : 'Not assigned');
                    const isSelected = adSelectedStudent && adSelectedStudent.id === s.id;
                    const pictureHtml = s.picture
                        ? `<img src="${s.picture}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`
                        : s.initials;
                    return `
                    <div class="ad-student-row ${isSelected ? 'selected' : ''}"
                         data-id="${s.id}"
                         onclick='adSelectStudent(${JSON.stringify(s).replace(/'/g,"&#39;")})'>
                        <div class="ad-avatar"
                             style="background:${bg};color:${fg};border:1.5px solid ${bg};">
                            ${pictureHtml}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:500;color:#1e293b;">
                                ${s.firstname} ${s.lastname}
                            </div>
                            <div style="font-size:11px;color:#64748b;margin-top:2px;">
                                <i class="ri-id-card-line" style="vertical-align:-2px;margin-right:3px;"></i>${s.admissionNo || '—'}
                                ${s.gender ? `&nbsp;·&nbsp;${s.gender}` : ''}
                            </div>
                            <div style="font-size:10px;color:#64748b;margin-top:2px;">
                                <i class="ri-building-line" style="vertical-align:-2px;margin-right:3px;"></i>${className}
                                &nbsp;·&nbsp;
                                <i class="ri-calendar-line" style="vertical-align:-2px;margin-right:3px;"></i>${termDisplay}
                            </div>
                        </div>
                    </div>`;
                }).join('');
            })
            .catch(() => {
                box.innerHTML = `<div class="ad-no-results" style="color:#dc2626;">
                    <i class="ri-error-warning-line ri-2x d-block mb-2"></i>
                    Search failed. Please try again.
                </div>`;
            });
    }, 350);
});

function adSelectStudent(student) {
    adSelectedStudent = student;
    document.querySelectorAll('.ad-student-row').forEach(r => r.classList.remove('selected'));
    const row = document.querySelector(`.ad-student-row[data-id="${student.id}"]`);
    if (row) row.classList.add('selected');
    document.getElementById('adBtnNext').disabled = false;

    // Also update the avatar in the banner if we go back
    if (adCurrentStep > 1) {
        const bannerDiv = document.getElementById(`adStep${adCurrentStep}Banner`);
        if (bannerDiv) {
            bannerDiv.innerHTML = adStudentBanner(student, adSelectedDiscount);
        }
    }
}

/* ── STEP 2: Render discounts ── */
function adRenderDiscounts() {
    const list = document.getElementById('adDiscountList');
    const discounts = @json($discounts ?? []);

    if (!discounts.length) {
        list.innerHTML = `<div class="ad-no-results">
            <i class="ri-inbox-line ri-2x d-block mb-2"></i>No active discounts found
        </div>`;
        return;
    }

    list.innerHTML = discounts.map(d => {
        const val = d.value_type === 'percentage'
            ? parseFloat(d.value) + '%'
            : '₦' + parseFloat(d.value).toLocaleString('en-NG', { minimumFractionDigits: 2 });
        const isPicked = adSelectedDiscount && adSelectedDiscount.id === d.id;
        return `
        <div class="ad-discount-opt ${isPicked ? 'picked' : ''}"
             data-id="${d.id}"
             onclick='adSelectDiscount(${JSON.stringify(d).replace(/'/g,"&#39;")})'>
            <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:0;">
                <i class="ri-check-circle-fill check"></i>
                <div>
                    <div style="font-size:13px;font-weight:500;color:#1e293b;">${d.title}</div>
                    <div style="font-size:11px;color:#64748b;margin-top:2px;">
                        ${d.discount_no}
                        ${d.description ? `&nbsp;·&nbsp;${d.description.substring(0, 50)}` : ''}
                    </div>
                </div>
            </div>
            <span class="ad-disc-value">${val}</span>
        </div>`;
    }).join('');
}

function adSelectDiscount(discount) {
    adSelectedDiscount = discount;
    document.querySelectorAll('.ad-discount-opt').forEach(el => el.classList.remove('picked'));
    const opt = document.querySelector(`.ad-discount-opt[data-id="${discount.id}"]`);
    if (opt) opt.classList.add('picked');
    document.getElementById('adBtnNext').disabled = false;
}

/* ── STEP 4: Summary ── */
function adRenderSummary() {
    const from   = document.getElementById('adEffectiveFrom').value;
    const to     = document.getElementById('adEffectiveTo').value;
    const reason = document.getElementById('adReason').value;
    const val    = adFormatValue(adSelectedDiscount);
    const className = adSelectedStudent.class_display || (adSelectedStudent.class_name && adSelectedStudent.arm_name ? `${adSelectedStudent.class_name} ${adSelectedStudent.arm_name}` : (adSelectedStudent.class_name || 'N/A'));
    const termDisplay = adSelectedStudent.current_term_display || (adSelectedStudent.term && adSelectedStudent.session ? `${adSelectedStudent.term} · ${adSelectedStudent.session}` : 'Not assigned');

    const rows = [
        ['Student',       `${adSelectedStudent.firstname} ${adSelectedStudent.lastname}`],
        ['Admission No.', adSelectedStudent.admissionNo || '—'],
        ['Class',         className],
        ['Current Term',  termDisplay],
        ['Discount',      `${adSelectedDiscount.title} <span style="color:#94a3b8;font-weight:400;">(${adSelectedDiscount.discount_no})</span>`],
        ['Value',         `<span style="color:#059669;font-size:15px;">${val}</span>`],
        ['Effective From',adFormatDate(from)],
        ['Effective To',  adFormatDate(to)],
        ...(reason ? [['Reason', reason]] : []),
    ];

    document.getElementById('adSummaryContent').innerHTML = rows.map(([l, v]) => `
        <div class="ad-summary-row">
            <span class="lbl">${l}</span>
            <span class="val">${v}</span>
        </div>`).join('');
}

/* ── Submit ── */
async function adSubmit() {
    const btnNext = document.getElementById('adBtnNext');
    btnNext.disabled = true;
    btnNext.innerHTML = '<span class="ad-spinner"></span> Assigning…';

    const formData = new FormData();
    formData.append('_token', CSRF_TOKEN);
    formData.append('discount_id', adSelectedDiscount.id);
    formData.append('student_id', adSelectedStudent.id);
    formData.append('effective_from', document.getElementById('adEffectiveFrom').value);
    formData.append('effective_to', document.getElementById('adEffectiveTo').value);
    formData.append('reason', document.getElementById('adReason').value);

    try {
        const res = await fetch('{{ route("admin.discount.assign") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: formData,
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Discount Assigned!',
                text: data.message,
                confirmButtonColor: '#4f46e5',
            }).then(() => location.reload());
        } else {
            const errBox = document.getElementById('adErrors');
            errBox.classList.remove('d-none');
            errBox.textContent = data.message || 'Assignment failed.';
            btnNext.disabled = false;
            btnNext.innerHTML = '<i class="ri-check-line"></i> Confirm & Assign';
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.', confirmButtonColor: '#4f46e5' });
        btnNext.disabled = false;
        btnNext.innerHTML = '<i class="ri-check-line"></i> Confirm & Assign';
    }
}

/* ── Reset modal on open ── */
document.getElementById('assignModal').addEventListener('show.bs.modal', function () {
    adCurrentStep      = 1;
    adSelectedStudent  = null;
    adSelectedDiscount = null;

    document.getElementById('adStudentSearch').value = '';
    document.getElementById('adStudentResults').innerHTML = `
        <div class="ad-no-results">
            <i class="ri-user-search-line ri-2x d-block mb-2"></i>
            Type at least 2 characters to search
        </div>`;
    document.getElementById('adEffectiveTo').value = '';
    document.getElementById('adReason').value = '';
    document.getElementById('adErrors').classList.add('d-none');
    document.getElementById('adBtnNext').disabled = true;
    document.getElementById('adBtnNext').innerHTML = 'Continue <i class="ri-arrow-right-line"></i>';
    document.getElementById('adBtnBack').style.visibility = 'hidden';

    [2, 3, 4].forEach(i => document.getElementById('adStep' + i).style.display = 'none');
    document.getElementById('adStep1').style.display = 'block';
    adSetStep(1);
});

/* ═══════════════════════════════════════════════════════════════
   REMOVE MODAL
═══════════════════════════════════════════════════════════════ */
$(document).ready(function () {

    // Assignment card search
    $('#searchInput').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('.assignment-card').each(function () {
            const searchData = $(this).data('search') || '';
            $(this).closest('.col-md-6, .col-lg-4')
                   .toggle(searchData.includes(value) || value === '');
        });
    });

    // Remove button
    $(document).on('click', '.remove-btn', function () {
        removeId = $(this).data('id');
        $('#removeModal').modal('show');
    });

    $('#confirmRemoveBtn').on('click', async function () {
        if (!removeId) return;

        try {
            const response = await fetch(`/admin/discount/assignment/${removeId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({ reason: $('#removeReason').val() }),
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('Removed!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch {
            Swal.fire('Error!', 'Something went wrong.', 'error');
        }
        $('#removeModal').modal('hide');
    });
});

// Image Zoom Functionality for Student Photos
function setupImageZoom() {
    $(document).on('click', '.ad-avatar, .ad-selected-avatar', function(e) {
        e.stopPropagation();
        const $row = $(this).closest('.ad-student-row, .ad-selected-banner');
        let studentData = null;

        // Try to get student data from the row's onclick attribute or from the banner
        if ($row.find('.ad-student-row').length) {
            const onclickAttr = $row.find('.ad-student-row').attr('onclick');
            if (onclickAttr) {
                const match = onclickAttr.match(/adSelectStudent\(({.*?})\)/);
                if (match) {
                    studentData = JSON.parse(match[1].replace(/&#39;/g, "'"));
                }
            }
        } else if ($row.hasClass('ad-selected-banner')) {
            // Get from global selected student
            studentData = adSelectedStudent;
        }

        if (studentData) {
            const zoomedImage = document.getElementById('zoomedImage');
            const zoomedImageName = document.getElementById('zoomedImageName');
            const zoomedImageDetails = document.getElementById('zoomedImageDetails');

            zoomedImageName.textContent = `${studentData.firstname} ${studentData.lastname}`;
            zoomedImageDetails.innerHTML = `
                <i class="ri-id-card-line me-1"></i> ${studentData.admissionNo || 'N/A'} &nbsp;|&nbsp;
                <i class="ri-building-line me-1"></i> ${studentData.class_display || (studentData.class_name && studentData.arm_name ? `${studentData.class_name} ${studentData.arm_name}` : (studentData.class_name || 'N/A'))} &nbsp;|&nbsp;
                <i class="ri-calendar-line me-1"></i> ${studentData.current_term_display || (studentData.term && studentData.session ? `${studentData.term} · ${studentData.session}` : 'Not assigned')}
            `;

            if (studentData.picture) {
                zoomedImage.src = studentData.picture;
                zoomedImage.style.display = 'block';
            } else {
                // Create canvas with initials
                const canvas = document.createElement('canvas');
                canvas.width = 400;
                canvas.height = 400;
                const ctx = canvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
                const [bg, fg] = adAvatarColors(studentData.id);
                gradient.addColorStop(0, bg);
                gradient.addColorStop(1, fg);
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 160px "Segoe UI", Arial, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(adInitials(studentData), canvas.width/2, canvas.height/2);
                zoomedImage.src = canvas.toDataURL();
                zoomedImage.style.display = 'block';
            }

            $('#imageZoomModal').modal('show');
        }
    });
}

// Initialize image zoom
setupImageZoom();

// Close zoom modal on escape or click on image
$(document).on('click', '.zoomed-image', function() {
    $('#imageZoomModal').modal('hide');
});
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        $('#imageZoomModal').modal('hide');
    }
});
</script>
@endsection
