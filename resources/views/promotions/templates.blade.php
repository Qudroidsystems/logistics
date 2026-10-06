@extends('layouts.master')

@section('content')
<style>
:root {
    --ps-primary: #1e3a5f;
    --ps-accent: #2563eb;
    --ps-success: #16a34a;
    --ps-warning: #d97706;
    --ps-danger: #dc2626;
    --ps-info: #0891b2;
    --ps-muted: #6b7280;
    --ps-border: #e2e8f0;
    --ps-bg: #f8fafc;
    --ps-radius: 12px;
    --ps-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.ps-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--ps-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.ps-hero::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 220px;
    height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.ps-hero h1 {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 6px;
    position: relative;
}
.ps-hero p {
    font-size: 13px;
    color: rgba(255,255,255,.75);
    margin: 0;
    position: relative;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    color: #fff;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
}
.back-link:hover {
    background: rgba(255,255,255,0.3);
    color: #fff;
    transform: translateX(-2px);
}

.setting-card {
    background: #fff;
    border: 1px solid var(--ps-border);
    border-radius: var(--ps-radius);
    padding: 20px;
    margin-bottom: 20px;
    transition: all .3s ease;
    height: 100%;
}
.setting-card:hover {
    box-shadow: var(--ps-shadow);
    transform: translateY(-2px);
}
.setting-card.has-rules {
    border-left: 4px solid var(--ps-success);
}
.setting-card.inactive {
    border-left: 4px solid var(--ps-muted);
    opacity: .75;
}

.active-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.active-badge.is-active {
    background: #dcfce7;
    color: #166534;
}
.active-badge.is-inactive {
    background: #f3f4f6;
    color: #6b7280;
}

.template-card {
    background: #fff;
    border: 1px solid var(--ps-border);
    border-radius: var(--ps-radius);
    padding: 20px;
    margin-bottom: 20px;
    transition: all .3s ease;
    height: 100%;
}
.template-card:hover {
    box-shadow: var(--ps-shadow);
    transform: translateY(-2px);
}
.template-card.inactive {
    opacity: .75;
    background: #f9fafb;
}

.modal-content {
    border-radius: 16px;
    overflow: hidden;
}
.modal-header {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    padding: 20px 28px;
    border-bottom: none;
}
.modal-header .modal-title {
    color: #fff;
    font-weight: 700;
}
.modal-header .btn-close {
    filter: invert(1);
}
.modal-body {
    padding: 1.5rem;
    max-height: 78vh;
    overflow-y: auto;
}
.modal-footer {
    border-top: 1px solid var(--ps-border);
    padding: 1rem 1.5rem;
}

.form-section {
    background: var(--ps-bg);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 18px;
}
.form-section-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--ps-primary);
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--ps-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.info-banner {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 14px;
    display: flex;
    gap: 12px;
}
.info-banner i {
    font-size: 18px;
    color: #2563eb;
    flex-shrink: 0;
    margin-top: 2px;
}
.info-banner .text {
    font-size: 12px;
    color: #1e40af;
    line-height: 1.5;
}

.rule-card {
    background: #fff;
    border: 2px solid var(--ps-border);
    border-radius: 12px;
    margin-bottom: 18px;
    overflow: hidden;
    transition: all .2s;
}
.rule-card:hover {
    border-color: var(--ps-accent);
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
}
.rule-card-header {
    background: linear-gradient(90deg, #f8fafc, #fff);
    border-bottom: 1px solid var(--ps-border);
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.rule-num-badge {
    background: var(--ps-primary);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 12px;
    border-radius: 20px;
    white-space: nowrap;
}
.rule-name-input {
    flex: 1;
    min-width: 180px;
    font-size: 13px;
}
.rule-card-body {
    padding: 18px;
}

.label-selector {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.label-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all .2s;
}
.label-pill:hover {
    transform: translateY(-1px);
}
.label-pill.active {
    box-shadow: 0 0 0 3px rgba(0,0,0,.12);
    transform: scale(1.03);
}
.label-pill.lp-promoted {
    background: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}
.label-pill.lp-promoted.active {
    background: #16a34a;
    color: #fff;
}
.label-pill.lp-trial {
    background: #fef9c3;
    color: #854d0e;
    border-color: #fde68a;
}
.label-pill.lp-trial.active {
    background: #ca8a04;
    color: #fff;
}
.label-pill.lp-principal {
    background: #e0f2fe;
    color: #075985;
    border-color: #bae6fd;
}
.label-pill.lp-principal.active {
    background: #0284c7;
    color: #fff;
}
.label-pill.lp-repeat {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}
.label-pill.lp-repeat.active {
    background: #dc2626;
    color: #fff;
}

.rule-section {
    border: 1px solid var(--ps-border);
    border-radius: 10px;
    margin-bottom: 14px;
    overflow: hidden;
}
.rule-section-header {
    background: linear-gradient(90deg, #f1f5f9, #f8fafc);
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 700;
    color: var(--ps-primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--ps-border);
}
.rule-section-body {
    padding: 14px;
}

.comp-subj-row {
    display: grid;
    grid-template-columns: 1fr 130px;
    gap: 10px;
    align-items: center;
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
}
.comp-subj-row:last-child {
    border-bottom: none;
}
.comp-subj-row .subj-name {
    font-size: 13px;
    font-weight: 500;
}
.comp-subj-row .subj-code {
    font-size: 11px;
    color: var(--ps-muted);
    font-family: monospace;
}
.default-badge {
    font-size: 10px;
    color: var(--ps-warning);
}

.avg-box {
    background: #f0f9ff;
    border: 1.5px solid #bae6fd;
    border-radius: 10px;
    padding: 14px;
    margin-top: 12px;
}

.grade-sel {
    border: 1.5px solid var(--ps-border);
    border-radius: 8px;
    padding: 5px 8px;
    font-size: 12px;
    font-weight: 600;
    background: #fff;
    width: 100%;
}
.grade-sel:focus {
    border-color: var(--ps-accent);
    outline: none;
    box-shadow: 0 0 0 2px rgba(37,99,235,.1);
}

.loading-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid #e2e8f0;
    border-radius: 50%;
    border-top-color: #2563eb;
    animation: spin .6s linear infinite;
}
.spin {
    animation: spin 0.7s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.no-rules-ph {
    text-align: center;
    padding: 36px 20px;
    color: var(--ps-muted);
    background: var(--ps-bg);
    border-radius: 12px;
    border: 2px dashed var(--ps-border);
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}
.chip-blue { background: #dbeafe; color: #1e40af; }
.chip-green { background: #dcfce7; color: #166534; }
.chip-amber { background: #fef9c3; color: #854d0e; }
.chip-red { background: #fee2e2; color: #991b1b; }

.btn-icon {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="ps-hero">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="mb-2">
                            <a href="{{ route('promotion-settings.index') }}" class="back-link">
                                <i class="ri-arrow-left-line"></i> Back to Promotion Settings
                            </a>
                        </div>
                        <h1><i class="ri-file-copy-line me-2"></i>Promotion Rule Templates</h1>
                        <p>Create and manage reusable promotion rule templates that can be applied to multiple classes.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('promotion-settings.index') }}" class="btn btn-light">
                            <i class="ri-settings-4-line me-1"></i> Settings
                        </a>
                        <a href="{{ route('promotion.templates.create') }}" class="btn btn-light">
                            <i class="ri-add-line me-1"></i>Create Template
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap"
                     style="padding:16px 20px;background:#fff;border-bottom:2px solid var(--ps-border);">
                    <h5 class="mb-0 fw-semibold" style="color:var(--ps-primary);">
                        <i class="ri-list-check me-2"></i>Promotion Templates
                        <span class="badge bg-primary ms-2">{{ $templates->count() }}</span>
                        <span class="badge bg-success ms-1">{{ $templates->where('is_active',true)->count() }} Active</span>
                        <span class="badge bg-secondary ms-1">{{ $templates->where('is_active',false)->count() }} Inactive</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('promotion-settings.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-arrow-left-line me-1"></i>Back to Settings
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse ($templates as $template)
                        @php
                            $isActive = (bool)$template->is_active;
                            $rules = $template->promotion_rules ?? [];
                            $gradeScaleLabel = $template->grade_scale === 'senior' ? 'Senior (A1-F9)' : 'Junior (A-F)';
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="template-card {{ !$isActive ? 'inactive' : '' }}">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input toggle-active-switch" type="checkbox" role="switch"
                                               id="sw{{ $template->id }}" data-id="{{ $template->id }}" {{ $isActive ? 'checked' : '' }}>
                                        <label class="form-check-label" for="sw{{ $template->id }}">
                                            <span class="active-badge {{ $isActive ? 'is-active' : 'is-inactive' }}" id="ab{{ $template->id }}">
                                                <i class="{{ $isActive ? 'ri-checkbox-circle-line' : 'ri-close-circle-line' }}"></i>
                                                {{ $isActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </label>
                                    </div>
                                    <span class="badge bg-info">{{ $gradeScaleLabel }}</span>
                                </div>

                                <h6 class="fw-bold mb-1">{{ $template->name }}</h6>
                                @if($template->description)
                                    <p class="text-muted small mb-2">{{ Str::limit($template->description, 100) }}</p>
                                @endif

                                <div class="mt-2 mb-3">
                                    <span class="badge bg-secondary">{{ count($rules) }} rule(s)</span>
                                    @if(count($rules) > 0)
                                        <span class="badge bg-primary ms-1">
                                            {{ $rules[0]['status_label'] ?? 'Promoted' }} +
                                        </span>
                                    @endif
                                </div>

                                @if(!empty($rules))
                                <div style="max-height:150px;overflow-y:auto;">
                                    @foreach(array_slice($rules, 0, 3) as $i => $rule)
                                    @php
                                        $stCls = match($rule['status_label'] ?? 'repeat') {
                                            'promoted'=>'success','trial'=>'warning','see_principal'=>'info',default=>'danger'
                                        };
                                    @endphp
                                    <div class="border-bottom pb-2 mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="fw-semibold small">
                                                <span class="badge bg-light text-dark me-1" style="font-size:10px;">{{ $i+1 }}</span>
                                                {{ Str::limit($rule['rule_name'] ?? 'Unnamed', 30) }}
                                            </span>
                                            <span class="badge bg-{{ $stCls }} px-2" style="font-size:10px;">
                                                {{ ucfirst(str_replace('_',' ',$rule['status_label'] ?? '')) }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                    @if(count($rules) > 3)
                                        <div class="text-muted small text-center mt-1">+ {{ count($rules) - 3 }} more rule(s)</div>
                                    @endif
                                </div>
                                @else
                                <div class="alert alert-warning py-2 px-3 mb-0 small"><i class="ri-alert-line me-1"></i>No rules defined.</div>
                                @endif

                                <div class="border-top pt-3 mt-3">
                                    <div class="row g-1" style="font-size:11px;">
                                        <div class="col-6"><span class="text-muted">Created:</span><br> {{ $template->created_at ? $template->created_at->format('M d, Y') : 'N/A' }}</div>
                                        <div class="col-6"><span class="text-muted">Modified:</span><br> {{ $template->updated_at ? $template->updated_at->format('M d, Y') : 'N/A' }}</div>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <a href="{{ route('promotion.templates.edit', $template->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="ri-pencil-line"></i> Edit
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger delete-template flex-fill"
                                        data-id="{{ $template->id }}" data-name="{{ $template->name }}">
                                        <i class="ri-delete-bin-line"></i> Delete
                                    </button>
                                </div>
                                <button class="btn btn-sm btn-outline-success w-100 mt-2 use-template-btn"
                                    data-id="{{ $template->id }}" data-name="{{ $template->name }}"
                                    data-grade-scale="{{ $template->grade_scale }}">
                                    <i class="ri-download-line me-1"></i> Use Template for Class
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center py-5">
                            <i class="ri-file-copy-line" style="font-size:48px;opacity:.3;"></i>
                            <p class="mt-3 text-muted">No promotion templates created yet.</p>
                            <div class="d-flex gap-3 justify-content-center">
                                <a href="{{ route('promotion.templates.create') }}" class="btn btn-primary">
                                    <i class="ri-add-line me-1"></i>Create First Template
                                </a>
                                <a href="{{ route('promotion-settings.index') }}" class="btn btn-outline-secondary">
                                    <i class="ri-arrow-left-line me-1"></i>Back to Settings
                                </a>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Use Template Modal --}}
<div class="modal fade" id="useTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-download-line me-2"></i>Apply Template to Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="ri-information-line me-2"></i>
                    This will load the promotion rules from the template into the selected class.
                    You can then modify the rules before saving.
                </div>

                <div class="form-section">
                    <div class="form-section-title"><span><i class="ri-book-2-line me-2"></i>Select Class &amp; Scope</span></div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Template <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_template_name" readonly>
                            <input type="hidden" id="modal_template_id">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                            <select class="form-select" id="use_class_id" required>
                                <option value="">-- Select Class --</option>
                                @foreach ($schoolclasses as $class)
                                <option value="{{ $class->id }}">{{ trim($class->schoolclass . ' ' . ($class->arm_name ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Session <small class="text-muted">(optional)</small></label>
                            <select class="form-select" id="use_session_id">
                                <option value="">-- All Sessions --</option>
                                @foreach ($sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Term <small class="text-muted">(optional)</small></label>
                            <select class="form-select" id="use_term_id">
                                <option value="">-- All Terms --</option>
                                @foreach ($terms as $t)
                                <option value="{{ $t->id }}">{{ $t->term }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="templateLoadStatus" class="mt-3" style="display:none;">
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <div class="loading-spinner"></div><small>Loading template data...</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="applyTemplateBtn">
                    <i class="ri-download-line me-1"></i>Load Rules
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentTemplateId = null;

// Toggle active status
document.addEventListener('change', async function(e) {
    if (!e.target.classList.contains('toggle-active-switch')) return;
    const toggle = e.target, sid = toggle.dataset.id, isActive = toggle.checked;
    const badge = document.getElementById('ab' + sid);
    const card = toggle.closest('.template-card');
    try {
        const res = await fetch(`/promotion-templates/${sid}/toggle-active`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ is_active: isActive })
        });
        const data = await res.json();
        if (data.success) {
            if (badge) {
                badge.className = isActive ? 'active-badge is-active' : 'active-badge is-inactive';
                badge.innerHTML = isActive ? '<i class="ri-checkbox-circle-line"></i> Active' : '<i class="ri-close-circle-line"></i> Inactive';
            }
            card?.classList.toggle('inactive', !isActive);
        } else {
            toggle.checked = !isActive;
            Swal.fire('Error', data.message, 'error');
        }
    } catch {
        toggle.checked = !isActive;
        Swal.fire('Error', 'Network error.', 'error');
    }
});

// Delete template
function bindDeleteButtons() {
    document.querySelectorAll('.delete-template').forEach(btn => {
        btn.removeEventListener('click', handleDeleteClick);
        btn.addEventListener('click', handleDeleteClick);
    });
}

async function handleDeleteClick(e) {
    const btn = e.currentTarget;
    const result = await Swal.fire({
        title: 'Confirm Delete',
        icon: 'warning',
        html: `Delete template <strong>${escapeHtml(btn.dataset.name)}</strong>?<br><small class="text-muted">This action cannot be undone.</small>`,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, Delete'
    });
    if (!result.isConfirmed) return;

    Swal.fire({ title: 'Deleting…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch(`/promotion-templates/${btn.dataset.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Failed.', 'error');
        }
    } catch {
        Swal.fire('Error', 'Network error.', 'error');
    }
}

// Use template button
function bindUseTemplateButtons() {
    document.querySelectorAll('.use-template-btn').forEach(btn => {
        btn.removeEventListener('click', handleUseTemplateClick);
        btn.addEventListener('click', handleUseTemplateClick);
    });
}

async function handleUseTemplateClick(e) {
    const btn = e.currentTarget;
    currentTemplateId = btn.dataset.id;
    document.getElementById('modal_template_id').value = currentTemplateId;
    document.getElementById('modal_template_name').value = btn.dataset.name;
    document.getElementById('use_class_id').value = '';
    document.getElementById('use_session_id').value = '';
    document.getElementById('use_term_id').value = '';
    document.getElementById('templateLoadStatus').style.display = 'none';

    const modal = new bootstrap.Modal(document.getElementById('useTemplateModal'));
    modal.show();
}

// Apply template button
document.getElementById('applyTemplateBtn').addEventListener('click', async function() {
    const classId = document.getElementById('use_class_id').value;
    if (!classId) {
        Swal.fire('Validation', 'Please select a class.', 'warning');
        return;
    }

    const templateId = document.getElementById('modal_template_id').value;
    const termId = document.getElementById('use_term_id').value;
    const sessionId = document.getElementById('use_session_id').value;
    const statusEl = document.getElementById('templateLoadStatus');

    statusEl.style.display = 'block';
    statusEl.innerHTML = '<div class="d-flex align-items-center gap-2 text-muted"><div class="loading-spinner"></div><small>Loading template rules...</small></div>';

    try {
        let url = `/promotion-templates/${templateId}/load-for-class?classid=${classId}`;
        if (termId) url += `&termid=${termId}`;
        if (sessionId) url += `&sessionid=${sessionId}`;

        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            // Store the loaded rules in session storage or pass to promotion settings
            sessionStorage.setItem('loaded_promotion_rules', JSON.stringify(data.merged_rules));
            sessionStorage.setItem('loaded_template_name', data.template.name);

            // Close modal and redirect to promotion settings
            bootstrap.Modal.getInstance(document.getElementById('useTemplateModal')).hide();

            Swal.fire({
                icon: 'success',
                title: 'Template Loaded!',
                html: `<strong>${escapeHtml(data.template.name)}</strong> loaded successfully.<br>
                       You will be redirected to the promotion settings page to review and save the rules.`,
                timer: 2000,
                showConfirmButton: true
            }).then(() => {
                window.location.href = '{{ route("promotion-settings.index") }}';
            });
        } else {
            statusEl.style.display = 'none';
            Swal.fire('Error', data.message || 'Failed to load template.', 'error');
        }
    } catch (err) {
        statusEl.style.display = 'none';
        Swal.fire('Error', 'Network error: ' + err.message, 'error');
    }
});

// Check for loaded rules on promotion settings page
if (window.location.pathname === '/promotion-settings' && sessionStorage.getItem('loaded_promotion_rules')) {
    const loadedRules = sessionStorage.getItem('loaded_promotion_rules');
    const templateName = sessionStorage.getItem('loaded_template_name');
    if (loadedRules && typeof promotionRules !== 'undefined') {
        try {
            promotionRules = JSON.parse(loadedRules);
            sessionStorage.removeItem('loaded_promotion_rules');
            sessionStorage.removeItem('loaded_template_name');
            Swal.fire({
                icon: 'success',
                title: 'Template Applied',
                html: `Rules from <strong>${escapeHtml(templateName)}</strong> have been loaded.<br>
                       Please review and click Save to apply them.`,
                timer: 3000
            });
            if (typeof rerenderRules === 'function') rerenderRules();
        } catch(e) {}
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
    bindDeleteButtons();
    bindUseTemplateButtons();
});
</script>
@endsection
