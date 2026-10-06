{{--
    Mass Student Account Management Modal (partial)
    – Markup + styles ONLY. All JavaScript lives in the page that includes
      this partial (currently users/index.blade.php) to avoid duplicate
      DOM IDs / competing event listeners if this were ever included twice
      or alongside an inline copy.
    – Include with: @include('users.partials.mass-student-modal')
--}}

<div id="massStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content msm-modal-content">

            {{-- HEADER --}}
            <div class="modal-header msm-header">
                <div class="msm-header-inner">
                    <div class="msm-header-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <h5 class="modal-title msm-title">Mass Student Account Management</h5>
                        <p class="msm-subtitle">Create, reset, revoke or reprint student credentials in bulk</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- STEP BAR --}}
            <div class="msm-steps-bar">
                <div class="msm-step active" id="stepBar1">
                    <div class="msm-step-circle">1</div><span>Select Students</span>
                </div>
                <div class="msm-step-line"></div>
                <div class="msm-step" id="stepBar2">
                    <div class="msm-step-circle">2</div><span>Choose Action</span>
                </div>
                <div class="msm-step-line"></div>
                <div class="msm-step" id="stepBar3">
                    <div class="msm-step-circle">3</div><span>Results</span>
                </div>
            </div>

            <div class="modal-body msm-body">

                {{-- ═══════════════ STEP 1 ═══════════════ --}}
                <div id="massStep1">
                    <div class="msm-card msm-filter-card mb-3">
                        <div class="msm-card-header"><i class="bi bi-funnel-fill me-2"></i>Filter Students</div>
                        <div class="msm-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="msm-label">Search</label>
                                    <div class="position-relative">
                                        <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none;"></i>
                                        <input type="text" id="massStudentSearch" class="form-control msm-input ps-4" placeholder="Name or Admission No…">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="msm-label">Class</label>
                                    <select id="massClassFilter" class="form-select msm-input">
                                        <option value="">All Classes</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="msm-label">Account Status</label>
                                    <select id="massAccountStatus" class="form-select msm-input">
                                        <option value="all">All Students</option>
                                        <option value="no">No Account Only</option>
                                        <option value="yes">Has Account Only</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="msm-info-banner mb-3">
                        <i class="bi bi-envelope-at-fill me-2"></i>
                        Emails are auto-generated as <code>firstname.lastname@csskabba.ng</code> — special characters removed.
                    </div>

                    <div class="msm-card mb-3">
                        <div class="msm-card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-lines-fill me-2"></i>Select Students</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="msm-count-badge" id="massSelectedCount">0 selected</span>
                                <button type="button" class="msm-btn-sm" id="selectAllStudents">
                                    <i class="bi bi-check-all me-1"></i>Select All
                                </button>
                                <button type="button" class="msm-btn-sm" id="deselectAll" style="border-color:#94a3b8;color:#94a3b8;">
                                    <i class="bi bi-x me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                        <div class="msm-card-body p-0">
                            <div class="table-responsive msm-table-wrap">
                                <table class="table msm-table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="40"><input type="checkbox" id="selectAllCheckbox" class="msm-checkbox"></th>
                                            <th>Admission No</th>
                                            <th>Student Name</th>
                                            <th>Class</th>
                                            <th>Status</th>
                                            <th>Generated Email</th>
                                        </tr>
                                    </thead>
                                    <tbody id="massStudentList">
                                        <tr><td colspan="6" class="msm-loading-cell">
                                            <div class="spinner-border spinner-border-sm me-2 text-primary"></div>Loading students…
                                        </td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="msm-legend mb-3">
                        <div class="msm-legend-title"><i class="bi bi-lightbulb-fill me-1"></i>Available Actions</div>
                        <div class="msm-legend-grid">
                            <div class="msm-legend-item create"><i class="bi bi-person-plus-fill"></i><div><strong>Create</strong> — new accounts for students without one</div></div>
                            <div class="msm-legend-item reset"><i class="bi bi-key-fill"></i><div><strong>Reset</strong> — new passwords for existing accounts</div></div>
                            <div class="msm-legend-item revoke"><i class="bi bi-person-x-fill"></i><div><strong>Revoke</strong> — remove user access (student record kept)</div></div>
                            <div class="msm-legend-item reprint"><i class="bi bi-printer-fill"></i><div><strong>Reprint</strong> — show existing credentials (password hidden)</div></div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="msm-btn-primary" id="proceedToAction">
                            Continue to Action <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                {{-- ═══════════════ STEP 2 ═══════════════ --}}
                <div id="massStep2" style="display:none;">
                    <div class="msm-card mb-3">
                        <div class="msm-card-header">
                            <i class="bi bi-check-square-fill me-2"></i>
                            Selected Students — <span id="step2SelectedCount" class="fw-bold">0</span>
                        </div>
                        <div class="msm-card-body p-0">
                            <div class="table-responsive msm-summary-wrap">
                                <table class="table msm-table msm-table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Admission No</th>
                                            <th>Class</th>
                                            <th>Status</th>
                                            <th>Generated Email</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedStudentsList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="msm-card mb-3">
                        <div class="msm-card-header"><i class="bi bi-lightning-charge-fill me-2"></i>Choose Action</div>
                        <div class="msm-card-body">
                            <div class="msm-action-grid">
                                <div class="msm-action-card" data-action="create">
                                    <div class="msm-action-icon create"><i class="bi bi-person-plus-fill"></i></div>
                                    <div class="msm-action-label">Create Accounts</div>
                                    <div class="msm-action-desc">For students without accounts</div>
                                </div>
                                <div class="msm-action-card" data-action="reset">
                                    <div class="msm-action-icon reset"><i class="bi bi-key-fill"></i></div>
                                    <div class="msm-action-label">Reset Passwords</div>
                                    <div class="msm-action-desc">New password for existing accounts</div>
                                </div>
                                <div class="msm-action-card" data-action="revoke">
                                    <div class="msm-action-icon revoke"><i class="bi bi-person-x-fill"></i></div>
                                    <div class="msm-action-label">Revoke Accounts</div>
                                    <div class="msm-action-desc">Remove user access</div>
                                </div>
                                <div class="msm-action-card" data-action="reprint">
                                    <div class="msm-action-icon reprint"><i class="bi bi-printer-fill"></i></div>
                                    <div class="msm-action-label">Reprint Credentials</div>
                                    <div class="msm-action-desc">Print without password shown</div>
                                </div>
                            </div>
                            <input type="hidden" id="selectedAction" value="">
                        </div>
                    </div>

                    <div id="passwordSettings" style="display:none;">
                        <div class="msm-card mb-3">
                            <div class="msm-card-header"><i class="bi bi-lock-fill me-2"></i>Password Settings</div>
                            <div class="msm-card-body">
                                <div class="msm-radio-group">
                                    <label class="msm-radio-card">
                                        <input type="radio" name="passwordTypeRadio" value="individual" checked>
                                        <div class="msm-radio-inner">
                                            <i class="bi bi-shuffle"></i>
                                            <div><strong>Individual Random Passwords</strong><p>Each student gets a unique random password</p></div>
                                        </div>
                                    </label>
                                    <label class="msm-radio-card">
                                        <input type="radio" name="passwordTypeRadio" value="same">
                                        <div class="msm-radio-inner">
                                            <i class="bi bi-people-fill"></i>
                                            <div><strong>Same Password for All</strong><p>All selected students share one password</p></div>
                                        </div>
                                    </label>
                                </div>
                                <div id="sharedPasswordContainer" style="display:none;" class="mt-3">
                                    <label class="msm-label">Shared Password</label>
                                    <input type="text" id="sharedPassword" class="form-control msm-input" placeholder="Minimum 6 characters">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="roleSettings" style="display:none;">
                        <div class="msm-card mb-3">
                            <div class="msm-card-header"><i class="bi bi-tags-fill me-2"></i>Role Assignment</div>
                            <div class="msm-card-body">
                                <div class="msm-role-notice">
                                    <i class="bi bi-shield-check-fill me-2"></i>
                                    Student accounts are automatically assigned the <strong>Student</strong> role.
                                </div>
                                @php
                                    $allRoles   = Spatie\Permission\Models\Role::all();
                                    $studentRole = $allRoles->where('name','Student')->first();
                                    $otherRoles  = $allRoles->where('name','!=','Student');
                                @endphp
                                @if($studentRole)
                                <div class="msm-role-assigned">
                                    <i class="bi bi-person-badge-fill me-2 text-success"></i>
                                    <strong class="text-success">{{ $studentRole->name }}</strong>
                                    <span class="msm-badge-default ms-2">Auto-assigned</span>
                                </div>
                                @endif
                                @if($otherRoles->count() > 0)
                                <div class="mt-3">
                                    <p class="text-muted small mb-2"><i class="bi bi-lock-fill me-1"></i>These roles cannot be assigned to student accounts:</p>
                                    <div class="msm-disabled-roles">
                                        @foreach($otherRoles as $role)
                                        <span class="msm-disabled-role"><i class="bi bi-lock-fill me-1"></i>{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="actionWarning" class="msm-warning-banner mb-3" style="display:none;"></div>

                    <div class="d-flex justify-content-between mt-1">
                        <button type="button" class="msm-btn-secondary" id="backToStep1">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </button>
                        <button type="button" class="msm-btn-primary" id="executeAction">
                            <i class="bi bi-check-circle me-1"></i>Execute Action
                        </button>
                    </div>
                </div>

                {{-- ═══════════════ STEP 3 ═══════════════ --}}
                <div id="massStep3" style="display:none;">
                    <div id="resultsContainer"></div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="msm-btn-secondary" id="newAction">
                            <i class="bi bi-plus-circle me-1"></i>New Action
                        </button>
                        <button type="button" class="msm-btn-primary" id="printResults">
                            <i class="bi bi-printer me-1"></i>Print Credentials
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     STYLES (scoped to .msm- prefix — safe to load once via this partial)
══════════════════════════════════════════════════════ --}}
<style>
:root {
    --msm-primary: #1e3a5f;
    --msm-accent:  #2563eb;
    --msm-indigo:  #4f46e5;
    --msm-success: #16a34a;
    --msm-warning: #d97706;
    --msm-danger:  #dc2626;
    --msm-info:    #0ea5e9;
    --msm-border:  #e2e8f0;
    --msm-radius:  12px;
}
.msm-modal-content { border:none; border-radius:20px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.msm-header { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 55%,#4f46e5 100%); padding:20px 24px; border:none; }
.msm-header-inner { display:flex; align-items:center; gap:14px; }
.msm-header-icon { width:44px; height:44px; background:rgba(255,255,255,.15); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; }
.msm-title    { color:#fff; font-size:17px; font-weight:700; margin:0; }
.msm-subtitle { color:rgba(255,255,255,.75); font-size:12px; margin:2px 0 0; }
.msm-steps-bar { display:flex; align-items:center; justify-content:center; padding:14px 24px; background:#f1f5f9; border-bottom:1px solid var(--msm-border); }
.msm-step { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:#94a3b8; }
.msm-step.active { color:var(--msm-accent); }
.msm-step.done   { color:var(--msm-success); }
.msm-step-circle { width:28px; height:28px; border-radius:50%; background:#e2e8f0; color:#94a3b8; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; transition:all .3s; }
.msm-step.active .msm-step-circle { background:var(--msm-accent); color:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.2); }
.msm-step.done   .msm-step-circle { background:var(--msm-success); color:#fff; }
.msm-step-line { flex:1; height:2px; background:#e2e8f0; margin:0 12px; max-width:80px; }
.msm-body { padding:20px 24px; background:#f8fafc; max-height:76vh; overflow-y:auto; }
.msm-card { background:#fff; border:1px solid var(--msm-border); border-radius:var(--msm-radius); overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.msm-filter-card { border-top:3px solid var(--msm-accent); }
.msm-card-header { padding:12px 18px; background:#f8fafc; border-bottom:1px solid var(--msm-border); font-size:13px; font-weight:600; color:#1e293b; }
.msm-card-body { padding:16px 18px; }
.msm-label { font-size:11.5px; font-weight:700; color:#64748b; margin-bottom:5px; display:block; text-transform:uppercase; letter-spacing:.4px; }
.msm-input { border:1.5px solid var(--msm-border); border-radius:8px; font-size:13px; transition:border-color .2s, box-shadow .2s; }
.msm-input:focus { border-color:var(--msm-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.msm-info-banner    { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:12.5px; color:#1e40af; }
.msm-warning-banner { background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px 14px; font-size:12.5px; color:#92400e; }
.msm-count-badge { background:var(--msm-accent); color:#fff; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; }
.msm-btn-sm { background:#fff; border:1.5px solid var(--msm-accent); color:var(--msm-accent); border-radius:7px; padding:4px 12px; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s; }
.msm-btn-sm:hover { background:var(--msm-accent); color:#fff; }
.msm-btn-primary { background:linear-gradient(135deg,var(--msm-accent),var(--msm-indigo)); color:#fff; border:none; border-radius:9px; padding:10px 22px; font-size:14px; font-weight:600; cursor:pointer; box-shadow:0 3px 12px rgba(37,99,235,.3); transition:transform .15s, box-shadow .15s; }
.msm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(37,99,235,.35); }
.msm-btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; box-shadow:none; }
.msm-btn-secondary { background:#fff; color:#1e293b; border:1.5px solid var(--msm-border); border-radius:9px; padding:10px 22px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s; }
.msm-btn-secondary:hover { background:#f1f5f9; }
.msm-table-wrap   { max-height:360px; overflow-y:auto; }
.msm-summary-wrap { max-height:200px; overflow-y:auto; }
.msm-table { font-size:12.5px; }
.msm-table thead th { background:#1e3a5f; color:#fff; border-bottom:none; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:10px 12px; position:sticky; top:0; z-index:5; }
.msm-table tbody tr:hover { background:#f0f9ff; }
.msm-table tbody td { padding:9px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.msm-table-sm tbody td { padding:6px 12px; }
.msm-checkbox, .student-checkbox { accent-color:var(--msm-accent); width:15px; height:15px; cursor:pointer; }
.msm-loading-cell { text-align:center; padding:32px; color:#64748b; font-size:13px; }
.msm-badge-has  { background:#dcfce7; color:#166534; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
.msm-badge-none { background:#f1f5f9; color:#475569; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
.msm-legend { background:#fff; border:1px solid var(--msm-border); border-radius:var(--msm-radius); padding:14px 16px; }
.msm-legend-title { font-size:11.5px; font-weight:700; color:#64748b; margin-bottom:10px; text-transform:uppercase; letter-spacing:.5px; }
.msm-legend-grid  { display:grid; grid-template-columns:repeat(2,1fr); gap:8px; }
.msm-legend-item  { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; font-size:12px; border:1px solid transparent; }
.msm-legend-item i { font-size:16px; }
.msm-legend-item.create  { background:#f0fdf4; border-color:#bbf7d0; } .msm-legend-item.create  i { color:var(--msm-success); }
.msm-legend-item.reset   { background:#fffbeb; border-color:#fde68a; } .msm-legend-item.reset   i { color:var(--msm-warning); }
.msm-legend-item.revoke  { background:#fef2f2; border-color:#fecaca; } .msm-legend-item.revoke  i { color:var(--msm-danger); }
.msm-legend-item.reprint { background:#f0f9ff; border-color:#bae6fd; } .msm-legend-item.reprint i { color:var(--msm-info); }
.msm-action-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.msm-action-card { border:2px solid var(--msm-border); border-radius:var(--msm-radius); padding:18px 12px; text-align:center; cursor:pointer; background:#fff; transition:all .2s; }
.msm-action-card:hover  { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.08); }
.msm-action-card.selected { border-color:var(--msm-accent); background:#eff6ff; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.msm-action-icon { font-size:28px; margin-bottom:8px; }
.msm-action-icon.create  { color:var(--msm-success); }
.msm-action-icon.reset   { color:var(--msm-warning); }
.msm-action-icon.revoke  { color:var(--msm-danger); }
.msm-action-icon.reprint { color:var(--msm-info); }
.msm-action-label { font-size:13px; font-weight:700; color:#1e293b; }
.msm-action-desc  { font-size:11px; color:#64748b; margin-top:3px; }
.msm-radio-group { display:flex; gap:12px; flex-wrap:wrap; }
.msm-radio-card  { flex:1; min-width:200px; cursor:pointer; }
.msm-radio-card input { display:none; }
.msm-radio-inner { display:flex; align-items:center; gap:12px; border:2px solid var(--msm-border); border-radius:10px; padding:14px; background:#fff; transition:all .2s; }
.msm-radio-inner i { font-size:22px; color:#64748b; }
.msm-radio-inner p { font-size:11px; color:#64748b; margin:2px 0 0; }
.msm-radio-card input:checked ~ .msm-radio-inner { border-color:var(--msm-accent); background:#eff6ff; }
.msm-radio-card input:checked ~ .msm-radio-inner i { color:var(--msm-accent); }
.msm-role-notice   { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:10px 14px; font-size:12.5px; color:#166534; margin-bottom:12px; }
.msm-role-assigned { display:flex; align-items:center; background:#f8fafc; border:1.5px solid var(--msm-border); border-radius:8px; padding:10px 14px; font-size:13px; }
.msm-badge-default { background:var(--msm-success); color:#fff; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:600; }
.msm-disabled-roles { display:flex; flex-wrap:wrap; gap:8px; }
.msm-disabled-role  { background:#f1f5f9; color:#94a3b8; border-radius:20px; padding:3px 12px; font-size:11px; border:1px solid #e2e8f0; }
@media (max-width:768px) {
    .msm-action-grid { grid-template-columns:repeat(2,1fr); }
    .msm-legend-grid { grid-template-columns:1fr; }
}
</style>
