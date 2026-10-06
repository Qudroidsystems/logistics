{{-- resources/views/promotions/settings.blade.php --}}
@extends('layouts.master')
@section('content')
<style>
:root {
    --ps-primary:#1e3a5f; --ps-accent:#2563eb; --ps-success:#16a34a;
    --ps-warning:#d97706; --ps-danger:#dc2626; --ps-info:#0891b2;
    --ps-muted:#6b7280; --ps-border:#e2e8f0; --ps-bg:#f8fafc;
    --ps-radius:12px; --ps-shadow:0 2px 8px rgba(0,0,0,.08);
}
.ps-hero { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#4f46e5 100%);
    border-radius:var(--ps-radius); padding:28px 32px; margin-bottom:24px; position:relative; overflow:hidden; }
.ps-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px;
    background:rgba(255,255,255,.06); border-radius:50%; }
.ps-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.ps-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.setting-card { background:#fff; border:1px solid var(--ps-border); border-radius:var(--ps-radius);
    padding:20px; margin-bottom:20px; transition:all .3s; height:100%; }
.setting-card:hover   { box-shadow:var(--ps-shadow); transform:translateY(-2px); }
.setting-card.has-rules  { border-left:4px solid var(--ps-success); }
.setting-card.inactive   { border-left:4px solid var(--ps-muted); opacity:.75; }

.active-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px;
    border-radius:20px; font-size:11px; font-weight:700; }
.active-badge.is-active   { background:#dcfce7; color:#166534; }
.active-badge.is-inactive { background:#f3f4f6; color:#6b7280; }

.modal-content { border-radius:16px; overflow:hidden; }
.modal-header  { background:linear-gradient(135deg,#1e3a5f,#2563eb); padding:20px 28px; border-bottom:none; }
.modal-header .modal-title { color:#fff; font-weight:700; }
.modal-header .btn-close   { filter:invert(1); }
.modal-body   { padding:1.5rem; max-height:78vh; overflow-y:auto; }
.modal-footer { border-top:1px solid var(--ps-border); padding:1rem 1.5rem; }

.form-section { background:var(--ps-bg); border-radius:12px; padding:20px; margin-bottom:18px; }
.form-section-title { font-size:14px; font-weight:700; color:var(--ps-primary);
    margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid var(--ps-border);
    display:flex; align-items:center; justify-content:space-between; }

.info-banner { background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;
    padding:12px 16px; margin-bottom:14px; display:flex; gap:12px; }
.info-banner i { font-size:18px; color:#2563eb; flex-shrink:0; margin-top:2px; }
.info-banner .text { font-size:12px; color:#1e40af; line-height:1.5; }

.rule-card { background:#fff; border:2px solid var(--ps-border); border-radius:12px;
    margin-bottom:18px; overflow:hidden; transition:all .2s; }
.rule-card:hover { border-color:var(--ps-accent); box-shadow:0 4px 12px rgba(0,0,0,.1); }
.rule-card-header { background:linear-gradient(90deg,#f8fafc,#fff); border-bottom:1px solid var(--ps-border);
    padding:12px 18px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.rule-num-badge { background:var(--ps-primary); color:#fff; font-size:11px; font-weight:700;
    padding:3px 12px; border-radius:20px; white-space:nowrap; }
.rule-name-input { flex:1; min-width:180px; font-size:13px; }
.rule-card-body  { padding:18px; }

.label-selector { display:flex; gap:8px; flex-wrap:wrap; }
.label-pill { display:inline-flex; align-items:center; gap:5px; padding:6px 14px;
    border-radius:30px; font-size:12px; font-weight:600; border:2px solid transparent;
    cursor:pointer; transition:all .2s; user-select:none; }
.label-pill:hover { transform:translateY(-1px); }
.label-pill.active { box-shadow:0 0 0 3px rgba(0,0,0,.12); transform:scale(1.03); }
.label-pill.lp-promoted  { background:#dcfce7; color:#166534; border-color:#bbf7d0; }
.label-pill.lp-promoted.active   { background:#16a34a; color:#fff; }
.label-pill.lp-trial     { background:#fef9c3; color:#854d0e; border-color:#fde68a; }
.label-pill.lp-trial.active      { background:#ca8a04; color:#fff; }
.label-pill.lp-principal { background:#e0f2fe; color:#075985; border-color:#bae6fd; }
.label-pill.lp-principal.active  { background:#0284c7; color:#fff; }
.label-pill.lp-repeat    { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }
.label-pill.lp-repeat.active     { background:#dc2626; color:#fff; }

.rule-section { border:1px solid var(--ps-border); border-radius:10px; margin-bottom:14px; overflow:hidden; }
.rule-section-header { background:linear-gradient(90deg,#f1f5f9,#f8fafc); padding:10px 16px;
    font-size:13px; font-weight:700; color:var(--ps-primary);
    display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--ps-border); }
.rule-section-body { padding:14px; }

.comp-subj-row { display:grid; grid-template-columns:1fr 140px; gap:10px; align-items:center;
    padding:8px 12px; border-bottom:1px solid #f1f5f9; }
.comp-subj-row:last-child { border-bottom:none; }
.comp-subj-row .subj-name { font-size:13px; font-weight:500; }
.comp-subj-row .subj-code { font-size:11px; color:var(--ps-muted); font-family:monospace; }
.default-badge { font-size:10px; color:var(--ps-warning); }

.cond-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    padding:8px 12px; border-bottom:1px solid #f1f5f9; }
.cond-row:last-child { border-bottom:none; }
.grade-pill { display:inline-flex; align-items:center; justify-content:center;
    width:36px; height:36px; border-radius:8px; font-size:14px; font-weight:800; flex-shrink:0; }
.gp-A,.gp-A1 { background:#dcfce7; color:#166534; }
.gp-B,.gp-B2,.gp-B3 { background:#dbeafe; color:#1e40af; }
.gp-C,.gp-C4,.gp-C5,.gp-C6 { background:#fef9c3; color:#854d0e; }
.gp-D,.gp-D7 { background:#ffedd5; color:#9a3412; }
.gp-E,.gp-E8 { background:#f3e8ff; color:#6b21a8; }
.gp-F,.gp-F9 { background:#fee2e2; color:#991b1b; }
.cond-text { font-size:12px; color:var(--ps-muted); white-space:nowrap; }

.avg-box { background:#f0f9ff; border:1.5px solid #bae6fd; border-radius:10px; padding:14px; margin-top:12px; }

.grade-sel { border:1.5px solid var(--ps-border); border-radius:8px; padding:5px 8px;
    font-size:12px; font-weight:600; background:#fff; width:100%; }
.grade-sel:focus { border-color:var(--ps-accent); outline:none; box-shadow:0 0 0 2px rgba(37,99,235,.1); }

.loading-spinner { display:inline-block; width:14px; height:14px; border:2px solid #e2e8f0;
    border-radius:50%; border-top-color:#2563eb; animation:spin .6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

.no-rules-ph { text-align:center; padding:36px 20px; color:var(--ps-muted);
    background:var(--ps-bg); border-radius:12px; border:2px dashed var(--ps-border); }

.chip { display:inline-flex; align-items:center; gap:3px; padding:2px 8px;
    border-radius:12px; font-size:10px; font-weight:600; }
.chip-blue  { background:#dbeafe; color:#1e40af; }
.chip-green { background:#dcfce7; color:#166534; }
.chip-amber { background:#fef9c3; color:#854d0e; }
.chip-red   { background:#fee2e2; color:#991b1b; }

.template-badge { background:#ede9fe; color:#5b21b6; border-radius:8px;
    padding:2px 10px; font-size:10px; font-weight:700; }

.rule-interp-panel strong { font-weight:700; }
.rule-interp-panel em     { font-style:italic; }
.ri-interp-and { font-style:normal; font-weight:700; color:#64748b;
    padding:0 4px; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="ps-hero">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="ri-settings-4-line me-2"></i>Promotion Settings</h1>
            <p>Define flexible grade-count based promotion rules per class. Rules are evaluated by priority — first match wins.</p>
        </div>
        <a href="{{ route('promotion.templates.index') }}" class="btn btn-light btn-sm">
            <i class="ri-file-copy-line me-1"></i>Rule Templates
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap"
         style="padding:16px 20px;background:#fff;border-bottom:2px solid var(--ps-border);">
        <h5 class="mb-0 fw-semibold" style="color:var(--ps-primary);">
            <i class="ri-list-check me-2"></i>Promotion Rules
            <span class="badge bg-primary ms-2">{{ $settings->count() }}</span>
            <span class="badge bg-success ms-1">{{ $settings->where('is_active',true)->count() }} Active</span>
            <span class="badge bg-secondary ms-1">{{ $settings->where('is_active',false)->count() }} Inactive</span>
        </h5>
        <button type="button" class="btn btn-primary" id="openAddBtn">
            <i class="ri-add-line me-1"></i>Add New Setting
        </button>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse ($settings as $setting)
            @php
                $armData  = $setting->schoolclass?->arm ? DB::table('schoolarm')->where('id',$setting->schoolclass->arm)->first() : null;
                $armName  = $armData?->arm ?? '';
                $fullName = trim($setting->schoolclass?->schoolclass . ' ' . $armName);
                $isActive = (bool)$setting->is_active;
                $rules    = $setting->promotion_rules ?? [];
                $logicLabel = match($setting->rule_logic ?? 'grade_count') {
                    'average_only' => '📈 Average Only',
                    'both'         => '🎯 Grades + Average',
                    default        => '📊 Grade Count',
                };
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="setting-card {{ !empty($rules) ? 'has-rules' : '' }} {{ !$isActive ? 'inactive' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input toggle-active-switch" type="checkbox" role="switch"
                                   id="sw{{ $setting->id }}" data-id="{{ $setting->id }}" {{ $isActive ? 'checked' : '' }}>
                            <label class="form-check-label" for="sw{{ $setting->id }}">
                                <span class="active-badge {{ $isActive ? 'is-active' : 'is-inactive' }}" id="ab{{ $setting->id }}">
                                    <i class="{{ $isActive ? 'ri-checkbox-circle-line' : 'ri-close-circle-line' }}"></i>
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                            </label>
                        </div>
                        @if($setting->template)
                            <span class="template-badge"><i class="ri-file-copy-line me-1"></i>{{ $setting->template->name }}</span>
                        @endif
                    </div>

                    <h6 class="fw-bold mb-0">{{ $fullName }}</h6>
                    <small class="text-muted">
                        {{ $setting->session?->session ?? 'All Sessions' }} &mdash; {{ $setting->term?->term ?? 'All Terms' }}
                    </small>
                    <div class="mt-1 mb-3">
                        <span class="badge bg-info">{{ $logicLabel }}</span>
                        @if($setting->promotion_pass_average !== null && $setting->promotion_pass_average !== '')
                            <span class="badge bg-secondary ms-1">≥{{ $setting->promotion_pass_average }}% avg</span>
                        @endif
                    </div>

                    @if(!empty($rules))
                    <div style="max-height:200px;overflow-y:auto;">
                        @foreach($rules as $i => $rule)
                        @php
                            $stCls = match($rule['status_label'] ?? 'repeat') {
                                'promoted'=>'success','trial'=>'warning','see_principal'=>'info',default=>'danger'
                            };
                            $compSubjCount  = count($rule['compulsory_section']['subjects'] ?? []);
                            $compCondCount  = count($rule['compulsory_section']['count_conditions'] ?? []);
                            $otherCondCount = count($rule['other_section']['count_conditions'] ?? []);
                        @endphp
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="fw-semibold small">
                                    <span class="badge bg-light text-dark me-1" style="font-size:10px;">{{ $i+1 }}</span>
                                    {{ $rule['rule_name'] ?? 'Unnamed' }}
                                </span>
                                <span class="badge bg-{{ $stCls }} px-2" style="font-size:10px;">
                                    {{ ucfirst(str_replace('_',' ',$rule['status_label'] ?? '')) }}
                                </span>
                            </div>
                            <div class="mt-1" style="font-size:10px;">
                                @if($compSubjCount)  <span class="chip chip-blue  me-1">{{ $compSubjCount }} comp subj</span>  @endif
                                @if($compCondCount)  <span class="chip chip-green me-1">{{ $compCondCount }} comp cond</span>  @endif
                                @if($otherCondCount) <span class="chip chip-amber me-1">{{ $otherCondCount }} other cond</span>@endif
                                @if(!empty($rule['average_condition']['enabled']))
                                    <span class="chip chip-red">Avg ≥{{ $rule['average_condition']['min_average'] }}%</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="alert alert-warning py-2 px-3 mb-0 small"><i class="ri-alert-line me-1"></i>No rules yet.</div>
                    @endif

                    <div class="border-top pt-3 mt-3">
                        <div class="row g-1" style="font-size:11px;">
                            <div class="col-6"><span class="text-muted">Promoted:</span> {{ $setting->promoted_label }}</div>
                            <div class="col-6"><span class="text-muted">Trial:</span> {{ $setting->trial_label }}</div>
                            <div class="col-6"><span class="text-muted">Principal:</span> {{ $setting->see_principal_label }}</div>
                            <div class="col-6"><span class="text-muted">Repeat:</span> {{ $setting->repeat_label }}</div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary edit-setting flex-fill"
                            data-id="{{ $setting->id }}"
                            data-schoolclass_id="{{ $setting->schoolclass_id }}"
                            data-session_id="{{ $setting->session_id ?? '' }}"
                            data-term_id="{{ $setting->term_id ?? '' }}"
                            data-promoted_label="{{ $setting->promoted_label }}"
                            data-trial_label="{{ $setting->trial_label }}"
                            data-see_principal_label="{{ $setting->see_principal_label }}"
                            data-repeat_label="{{ $setting->repeat_label }}"
                            data-rule_logic="{{ $setting->rule_logic ?? 'grade_count' }}"
                            data-promotion_pass_average="{{ $setting->promotion_pass_average !== null ? $setting->promotion_pass_average : '' }}"
                            data-is_active="{{ $isActive ? '1' : '0' }}"
                            data-template_id="{{ $setting->template_id ?? '' }}"
                            data-promotion_rules="{{ json_encode($rules) }}">
                            <i class="ri-pencil-line"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-setting"
                            data-id="{{ $setting->id }}" data-name="{{ $fullName }}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <i class="ri-settings-4-line" style="font-size:48px;opacity:.3;"></i>
                <p class="mt-3 text-muted">No promotion rules configured yet.</p>
                <button class="btn btn-primary" id="openAddBtn2">Create first rule</button>
            </div>
            @endforelse
        </div>
    </div>
</div>

</div></div></div>

{{-- MODAL --}}
<div class="modal fade" id="settingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-settings-4-line me-2"></i>Promotion Rule Settings</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="settingForm" hidden>
        @csrf
        <input type="hidden" name="id"              id="setting_id">
        <input type="hidden" name="promotion_rules" id="promotion_rules_input">
        <input type="hidden" name="template_id"     id="template_id_input">
      </form>

      <div class="modal-body">
        {{-- Class & Scope --}}
        <div class="form-section">
          <div class="form-section-title"><span><i class="ri-book-2-line me-2"></i>Class &amp; Scope</span></div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
              <select class="form-select" id="schoolclass_id" required>
                <option value="">-- Select Class --</option>
                @foreach ($schoolclasses as $class)
                <option value="{{ $class->id }}">{{ trim($class->schoolclass . ' ' . ($class->arm_name ?? '')) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Session <small class="text-muted">(optional)</small></label>
              <select class="form-select" id="session_id">
                <option value="">-- All Sessions --</option>
                @foreach ($sessions as $s)
                <option value="{{ $s->id }}">{{ $s->session }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Term <small class="text-muted">(optional)</small></label>
              <select class="form-select" id="term_id">
                <option value="">-- All Terms --</option>
                @foreach ($terms as $t)
                <option value="{{ $t->id }}">{{ $t->term }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div id="subjectLoadStatus" class="mt-3" style="display:none;">
            <div class="d-flex align-items-center gap-2 text-muted">
              <div class="loading-spinner"></div><small>Loading class info…</small>
            </div>
          </div>
          <div id="subjectSummary" class="mt-2" style="display:none;"></div>
        </div>

        {{-- Template loader --}}
        <div class="form-section">
          <div class="form-section-title"><span><i class="ri-file-copy-line me-2"></i>Load from Template <small class="text-muted fw-normal">(optional)</small></span></div>
          <div class="d-flex gap-2 align-items-end flex-wrap">
            <div style="flex:1;min-width:200px;">
              <label class="form-label fw-semibold small mb-1">Select Template</label>
              <select class="form-select" id="templateSelect">
                <option value="">-- None --</option>
                @foreach($templates as $tpl)
                <option value="{{ $tpl->id }}" data-scale="{{ $tpl->grade_scale }}">
                  {{ $tpl->name }} ({{ $tpl->grade_scale }})
                </option>
                @endforeach
              </select>
            </div>
            <button type="button" class="btn btn-outline-primary" id="loadTemplateBtn" disabled>
              <i class="ri-download-line me-1"></i>Load Template
            </button>
            <div id="templateStatus" class="small text-muted align-self-center"></div>
          </div>
        </div>

        {{-- Evaluation Mode + Active --}}
        <div class="form-section">
          <div class="form-section-title"><span><i class="ri-git-branch-line me-2"></i>Evaluation Mode &amp; Status</span></div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Evaluation Mode</label>
              <select class="form-select" id="rule_logic">
                <option value="grade_count">📊 Grade Count Rules Only</option>
                <option value="average_only">📈 Minimum Average Only</option>
                <option value="both">🎯 Grade Count AND/OR Average</option>
              </select>
            </div>
            <div class="col-md-4" id="globalAvgSection" style="display:none;">
              <label class="form-label fw-semibold">Global Minimum Average (%)</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="range" class="form-range flex-fill" id="avg_slider" min="0" max="100" step="1" value="50">
                <input type="number" class="form-control" id="promotion_pass_average" style="width:80px;" min="0" max="100" step="0.5">
              </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div>
                <label class="form-label fw-semibold d-block">Active Status</label>
                <div class="d-flex align-items-center gap-2">
                  <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="modal_is_active" checked>
                    <label class="form-check-label fw-semibold" for="modal_is_active">Active</label>
                  </div>
                  <span id="modalActiveBadge" class="active-badge is-active">
                    <i class="ri-checkbox-circle-line"></i> Active
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Rules --}}
        <div class="form-section">
          <div class="form-section-title">
            <span><i class="ri-price-tag-3-line me-2"></i>Promotion Rules
              <small class="text-muted fw-normal ms-2" id="ruleScopeInfo"></small>
            </span>
            <button type="button" class="btn btn-sm btn-primary" id="addRuleBtn" disabled>
              <i class="ri-add-line me-1"></i>Add Rule
            </button>
          </div>
          <div class="info-banner">
            <i class="ri-lightbulb-line"></i>
            <div class="text">
              <strong>Rule priority order</strong>
              Rules are checked top-to-bottom. The <strong>first rule where ALL conditions pass</strong> wins.
              If no rule matches → Advice to Repeat.
            </div>
          </div>

          {{-- Global interpretation panel --}}
          <div id="globalInterpPanel"></div>

          <div id="rulesContainer">
            <div class="no-rules-ph" id="noRulesMsg">
              <i class="ri-clipboard-line d-block mb-2" style="font-size:2rem;opacity:.3;"></i>
              Select a class first, then click <strong>Add Rule</strong>.
            </div>
          </div>
        </div>

        {{-- Labels --}}
        <div class="form-section">
          <div class="form-section-title"><span><i class="ri-price-tag-line me-2"></i>Status Labels</span></div>
          <div class="row g-3">
            <div class="col-md-3"><label class="form-label fw-semibold small">Promoted</label>
              <input type="text" class="form-control form-control-sm" id="promoted_label" value="Promoted"></div>
            <div class="col-md-3"><label class="form-label fw-semibold small">Trial</label>
              <input type="text" class="form-control form-control-sm" id="trial_label" value="Promoted on Trial"></div>
            <div class="col-md-3"><label class="form-label fw-semibold small">See Principal</label>
              <input type="text" class="form-control form-control-sm" id="see_principal_label" value="Advised to See Principal"></div>
            <div class="col-md-3"><label class="form-label fw-semibold small">Repeat</label>
              <input type="text" class="form-control form-control-sm" id="repeat_label" value="Advice to Repeat"></div>
          </div>
        </div>
      </div>{{-- /modal-body --}}

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveSettingBtn">
          <i class="ri-save-line me-1"></i>Save Settings
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ============================================================
// RULE INTERPRETATION ENGINE
// ============================================================

const RuleInterpreter = (() => {
    const GRADE_LABELS_SENIOR = {
        A1: 'A1 (Distinction)', B2: 'B2 (Very Good)', B3: 'B3 (Good)',
        C4: 'C4 (Credit)', C5: 'C5 (Credit)', C6: 'C6 (Credit)',
        D7: 'D7 (Pass)', E8: 'E8 (Below Pass)', F9: 'F9 (Fail)',
    };

    const GRADE_LABELS_JUNIOR = {
        A: 'A (Excellent)', B: 'B (Good)', C: 'C (Credit)',
        D: 'D (Pass)', F: 'F (Fail)',
    };

    const GROUP_LABELS_SENIOR = {
        A: 'distinctions (A1)', B: 'very-good/good grades (B2–B3)',
        C: 'credit grades (C4–C6)', D: 'pass grades (D7)',
        E: 'below-pass grades (E8)', F: 'fail grades (F9)',
    };

    const GROUP_LABELS_JUNIOR = {
        A: 'A grades (Excellent)', B: 'B grades (Good)',
        C: 'C grades (Credit)', D: 'D grades (Pass)', F: 'F grades (Fail)',
    };

    function gradeLabel(g, grouping, senior) {
        g = (g || '').toUpperCase();
        if (grouping === 'grouped') {
            return (senior ? GROUP_LABELS_SENIOR : GROUP_LABELS_JUNIOR)[g] || g;
        }
        return (senior ? GRADE_LABELS_SENIOR : GRADE_LABELS_JUNIOR)[g] || g;
    }

    function opPhrase(operator, count, noun) {
        const n = parseInt(count, 10);
        switch (operator) {
            case '>=': return n === 0 ? `any number of ${noun}` : `at least ${n} ${noun}`;
            case '<=': return n === 0 ? `zero ${noun}` : `at most ${n} ${noun}`;
            case '=':  return n === 0 ? `exactly zero ${noun}` : `exactly ${n} ${noun}`;
            case '>':  return `more than ${n} ${noun}`;
            case '<':  return n === 1 ? `zero ${noun}` : `fewer than ${n} ${noun}`;
            default:   return `${operator}${n} ${noun}`;
        }
    }

    function scopePhrase(scope) {
        switch (scope) {
            case 'compulsory_only': return 'compulsory subjects';
            case 'other_only':      return 'non-compulsory subjects';
            default:                return 'all subjects';
        }
    }

    function describeCompSubjectMins(subjects, senior) {
        const withMin = (subjects || []).filter(s => s.min_grade);
        if (!withMin.length) return null;

        if (withMin.every(s => s.min_grade === withMin[0].min_grade)) {
            const label = (senior ? GRADE_LABELS_SENIOR : GRADE_LABELS_JUNIOR)[withMin[0].min_grade.toUpperCase()] || withMin[0].min_grade;
            if (withMin.length === subjects.length && subjects.length > 0) {
                return `every compulsory subject scores at least <strong>${label}</strong>`;
            }
            return `${withMin.length} compulsory subject${withMin.length > 1 ? 's' : ''} score${withMin.length === 1 ? 's' : ''} at least <strong>${label}</strong>`;
        }

        const lines = withMin.map(s => {
            const label = (senior ? GRADE_LABELS_SENIOR : GRADE_LABELS_JUNIOR)[s.min_grade.toUpperCase()] || s.min_grade;
            const name  = s.subject_name ? `<em>${s.subject_name}</em>` : `subject #${s.subject_id}`;
            return `${name} ≥ <strong>${label}</strong>`;
        });
        return lines.join(', ');
    }

    function describeCountCond(cond, grouping, senior) {
        const g = (cond.grade || '').toUpperCase();
        const op = cond.operator || '>=';
        const count = cond.count ?? 1;
        const scope = cond.scope || 'all';
        const gradeTxt = gradeLabel(g, grouping, senior);
        const scopeTxt = scopePhrase(scope);
        const noun = `${gradeTxt} in ${scopeTxt}`;
        return opPhrase(op, count, noun);
    }

    function describeAvgCond(avgCond) {
        if (!avgCond || !avgCond.enabled) return null;
        const min = avgCond.min_average ?? '?';
        const logic = (avgCond.logic || 'AND').toUpperCase();
        const base = `overall average ≥ <strong>${min}%</strong>`;
        return logic === 'OR'
            ? `${base} <em>(OR — this alone can qualify the student)</em>`
            : `${base} <em>(AND — must also be met)</em>`;
    }

    function interpret(rule, snr, ruleIndex) {
        if (!rule || !rule.rule_name) {
            return { summary: '', bullets: [], firesWhen: 'Rule has no name yet.', neverFires: false, isCatchAll: false, outcomeKey: 'repeat' };
        }

        const grouping = rule.grade_grouping || 'grouped';
        const compSubj = rule.compulsory_section?.subjects || [];
        const compConds = rule.compulsory_section?.count_conditions || [];
        const otherConds = rule.other_section?.count_conditions || [];
        const avgCond = rule.average_condition;

        const clauses = [];

        const subjMinLine = describeCompSubjectMins(compSubj, snr);
        if (subjMinLine) clauses.push(subjMinLine);

        for (const c of compConds) {
            if (!(c.grade || '').trim()) continue;
            clauses.push(describeCountCond({ ...c, scope: c.scope || 'compulsory_only' }, grouping, snr));
        }

        for (const c of otherConds) {
            if (!(c.grade || '').trim()) continue;
            clauses.push(describeCountCond(c, grouping, snr));
        }

        const avgLine = describeAvgCond(avgCond);
        if (avgLine) clauses.push(avgLine);

        const hasRealConditions = !!(subjMinLine
            || compConds.some(c => c.grade)
            || otherConds.some(c => c.grade)
            || (avgCond && avgCond.enabled));

        let neverFires = false;
        const allConds = [...compConds, ...otherConds];
        const byScopeGrade = {};
        for (const c of allConds) {
            const key = `${c.scope || 'all'}__${(c.grade || '').toUpperCase()}`;
            if (!byScopeGrade[key]) byScopeGrade[key] = [];
            byScopeGrade[key].push(c);
        }
        for (const conds of Object.values(byScopeGrade)) {
            if (conds.length < 2) continue;
            const mins = conds.filter(c => ['>=', '>'].includes(c.operator)).map(c => parseInt(c.count ?? 0));
            const maxs = conds.filter(c => ['<=', '<'].includes(c.operator)).map(c => parseInt(c.count ?? 0));
            if (mins.length && maxs.length) {
                const maxMin = Math.max(...mins);
                const minMax = Math.min(...maxs);
                if (maxMin > minMax) neverFires = true;
            }
        }

        const outcome = rule.status_label || 'promoted';
        const outcomeMap = { promoted: 'Promoted', trial: 'Promoted on Trial', see_principal: 'See Principal', repeat: 'Repeat' };
        const outcomeTxt = outcomeMap[outcome] || outcome;

        let firesWhen;
        if (!hasRealConditions) {
            firesWhen = `<strong>Always matches (catch-all)</strong> — no conditions set. Every student who reaches this rule gets <em>${outcomeTxt}</em>.`;
        } else if (neverFires) {
            firesWhen = `<span style="color:#dc2626;font-weight:700;">⚠ Contradictory conditions</span> — this rule can never match any student.`;
        } else {
            const joined = clauses.map((c, i) => i === 0 ? c : `<span class="ri-interp-and">AND</span> ${c}`).join(' ');
            firesWhen = `<strong>Fires when:</strong> ${joined} → <strong>${outcomeTxt}</strong>`;
        }

        return { summary: `Rule ${ruleIndex}: ${rule.rule_name}`, bullets: clauses, firesWhen, neverFires, isCatchAll: !hasRealConditions, outcomeKey: outcome };
    }

    function interpretAll(rules, snr, ruleLogic, requiredAverage) {
        const results = rules.map((r, i) => interpret(r, snr, i + 1));
        const catchAllIdx = results.findIndex(r => r.isCatchAll);
        const hasUnreachable = catchAllIdx >= 0 && catchAllIdx < rules.length - 1;
        const logicLabels = {
            grade_count: 'Grade count rules only — checked top-to-bottom, first match wins. If no rule matches → Advice to Repeat.',
            average_only: `Minimum average only — student passes if overall average ≥ ${requiredAverage !== undefined && requiredAverage !== null && requiredAverage !== '' ? requiredAverage : '?'}%.`,
            both: 'Grade count AND average — both evaluated together. Average logic (AND/OR) is set per-rule.',
        };
        return { rules: results, logicDescription: logicLabels[ruleLogic] || '', hasUnreachable, unreachableFrom: catchAllIdx >= 0 ? catchAllIdx + 2 : null };
    }

    function renderPanel(interp) {
        if (!interp) return '';
        const { firesWhen, neverFires, isCatchAll, bullets, outcomeKey } = interp;
        const colorMap = { promoted: '#dcfce7', trial: '#fef9c3', see_principal: '#e0f2fe', repeat: '#fee2e2' };
        const borderMap = { promoted: '#16a34a', trial: '#ca8a04', see_principal: '#0284c7', repeat: '#dc2626' };
        const bg = neverFires ? '#fff1f2' : (colorMap[outcomeKey] || '#f8fafc');
        const border = neverFires ? '#dc2626' : (borderMap[outcomeKey] || '#cbd5e1');
        const icon = neverFires ? 'ri-error-warning-line' : isCatchAll ? 'ri-git-branch-line' : 'ri-lightbulb-line';

        let html = `<div class="rule-interp-panel" style="background:${bg};border:1.5px solid ${border};border-radius:10px;padding:12px 16px;margin-top:14px;font-size:12.5px;line-height:1.7;">
            <div style="display:flex;align-items:flex-start;gap:8px;">
                <i class="${icon}" style="font-size:16px;color:${border};flex-shrink:0;margin-top:2px;"></i>
                <div style="flex:1;">${firesWhen}</div>
            </div>`;

        if (bullets.length > 1 && !neverFires && !isCatchAll) {
            html += `<ul style="margin:8px 0 0 24px;padding:0;list-style:disc;">`;
            for (const b of bullets) html += `<li style="margin-bottom:3px;">${b}</li>`;
            html += `</ul>`;
        }

        html += `</div>`;
        return html;
    }

    return { interpret, interpretAll, renderPanel };
})();

// ── State ──────────────────────────────────────────────────────────────────────
let promotionRules = [];
let gradeScale = ['A1','B2','B3','C4','C5','C6','D7','E8','F9'];
let isSenior = true;
let totalSubjects = 0;
let compulsoryCount = 0;
let otherCount = 0;
let classPassAvg = null;
let compulsorySubjects = [];

const GRADE_SCALES = {
    senior: ['A1','B2','B3','C4','C5','C6','D7','E8','F9'],
    junior: ['A','B','C','D','F']
};

const GROUPED_SENIOR = { A:['A1'], B:['B2','B3'], C:['C4','C5','C6'], D:['D7'], E:['E8'], F:['F9'] };
const GROUPED_JUNIOR = { A:['A'], B:['B'], C:['C'], D:['D'], F:['F'] };

const STATUS_LABELS = [
    {key:'promoted', label:'Promoted', cls:'lp-promoted', icon:'ri-checkbox-circle-line'},
    {key:'trial', label:'Promoted on Trial', cls:'lp-trial', icon:'ri-time-line'},
    {key:'see_principal', label:'Advised to See Principal', cls:'lp-principal', icon:'ri-user-star-line'},
    {key:'repeat', label:'Advice to Repeat', cls:'lp-repeat', icon:'ri-repeat-line'},
];

const SCOPE_OPTIONS = [
    ['all', '📚 All Subjects'],
    ['compulsory_only', '⭐ Compulsory Only'],
    ['other_only', '📖 Other Only'],
];

function getGroupedGrades() { return isSenior ? Object.keys(GROUPED_SENIOR) : Object.keys(GROUPED_JUNIOR); }
function getExactGrades() { return gradeScale; }
function getGradesForGrouping(grouping) { return grouping === 'grouped' ? getGroupedGrades() : getExactGrades(); }

function _getConds(ruleIdx, sec) {
    if (!promotionRules[ruleIdx]) return [];
    return sec === 'comp'
        ? promotionRules[ruleIdx].compulsory_section.count_conditions
        : promotionRules[ruleIdx].other_section.count_conditions;
}

function buildCondRows(conds, ruleIdx, section, availGrades) {
    if (!conds.length) return '';
    return conds.map((cond, ci) => {
        const operators = ['>=', '<=', '=', '>', '<'];
        const opOpts = operators.map(op => `<option value="${op}" ${cond.operator === op ? 'selected' : ''}>${op}</option>`).join('');
        const gradeOpts = availGrades.map(g => `<option value="${g}" ${cond.grade === g ? 'selected' : ''}>${g}</option>`).join('');
        const scopeOpts = SCOPE_OPTIONS.map(([v, l]) => `<option value="${v}" ${(cond.scope ?? 'all') === v ? 'selected' : ''}>${l}</option>`).join('');
        const pillClass = `gp-${cond.grade || 'F'}`;
        return `<div class="cond-row" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">
            <span class="grade-pill ${pillClass}" id="gp_${ruleIdx}_${section}_${ci}">${escH(cond.grade || '?')}</span>
            <select class="form-select form-select-sm cond-grade-sel" style="width:85px;" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">${gradeOpts}</select>
            <span class="cond-text">count</span>
            <select class="form-select form-select-sm cond-op-sel" style="width:65px;" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">${opOpts}</select>
            <input type="number" class="form-control form-control-sm cond-count-inp" style="width:65px;" min="0" max="99" value="${cond.count ?? 1}" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">
            <span class="cond-text">subj. in</span>
            <select class="form-select form-select-sm cond-scope-sel" style="width:160px;" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">${scopeOpts}</select>
            <button class="btn btn-sm btn-outline-danger remove-cond-btn ms-auto" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}" title="Remove condition"><i class="ri-close-line"></i></button>
        </div>`;
    }).join('');
}

function buildRuleHTML(rule, idx) {
    const selSt = STATUS_LABELS.find(s => s.key === rule.status_label) || STATUS_LABELS[0];
    const grouping = rule.grade_grouping ?? 'grouped';
    const availGrades = getGradesForGrouping(grouping);
    const labelPills = STATUS_LABELS.map(sl => `<span class="label-pill ${sl.cls} ${rule.status_label === sl.key ? 'active' : ''}" data-idx="${idx}" data-status="${sl.key}"><i class="${sl.icon} me-1"></i>${sl.label}</span>`).join('');
    const groupingOpts = [
        ['grouped', isSenior ? 'Grouped (A=A1, B=B2+B3…)' : 'Grouped (A, B, C…)'],
        ['exact', isSenior ? 'Exact (A1, B2, B3 separately)' : 'Exact (A, B, C separately)']
    ].map(([v, l]) => `<option value="${v}" ${grouping === v ? 'selected' : ''}>${l}</option>`).join('');

    const subjects = rule.compulsory_section?.subjects ?? [];
    const compSubjRowsHtml = !subjects.length
        ? `<div class="text-muted small p-3"><i class="ri-information-line me-1"></i>No compulsory subjects assigned to this class.</div>`
        : subjects.map((subj, si) => `<div class="comp-subj-row">
            <input type="hidden" class="subject-id-field" data-idx="${idx}" data-si="${si}" value="${subj.subject_id || ''}">
            <div>
                <span class="subj-name">${escH(subj.subject_name)}</span>
                ${subj.subject_code ? `<span class="subj-code ms-1">(${escH(subj.subject_code)})</span>` : ''}
                ${subj.default_min_grade ? `<span class="default-badge ms-2"><i class="ri-information-line"></i> default: ${subj.default_min_grade}</span>` : ''}
                ${subj.override && subj.min_grade && subj.min_grade !== subj.default_min_grade ? `<span class="badge bg-warning text-dark ms-1">overridden</span>` : ''}
            </div>
            <select class="grade-sel comp-subj-grade-sel" data-idx="${idx}" data-si="${si}">
                ${['', ...gradeScale].map(g => `<option value="${g}" ${subj.min_grade === g ? 'selected' : ''}>${g === '' ? '— Any (default pass/fail)' : g}</option>`).join('')}
            </select>
        </div>`).join('');

    const gradeOptsComp = availGrades.map(g => `<option>${g}</option>`).join('');
    const gradeOptsOther = availGrades.map(g => `<option>${g}</option>`).join('');
    const compCondRows = buildCondRows(rule.compulsory_section?.count_conditions ?? [], idx, 'comp', availGrades);
    const otherCondRows = buildCondRows(rule.other_section?.count_conditions ?? [], idx, 'other', availGrades);
    const avg = rule.average_condition ?? { enabled: false, min_average: classPassAvg ?? 50, logic: 'AND' };
    const statusBadgeClass = selSt.key === 'promoted' ? 'success' : selSt.key === 'trial' ? 'warning' : selSt.key === 'see_principal' ? 'info' : 'danger';

    return `<div class="rule-card" data-rule-idx="${idx}">
      <div class="rule-card-header">
        <span class="rule-num-badge">Rule ${idx + 1}</span>
        <span class="badge bg-${statusBadgeClass}" id="statusBadge_${idx}"><i class="${selSt.icon} me-1"></i>${selSt.label}</span>
        <input type="text" class="form-control form-control-sm rule-name-input" data-idx="${idx}" value="${escH(rule.rule_name)}" placeholder="Rule name">
        <div class="d-flex gap-1 align-items-center ms-auto">
          <span class="text-muted small me-1">Priority:</span>
          <input type="number" class="form-control form-control-sm priority-input" data-idx="${idx}" value="${rule.priority ?? idx + 1}" min="1" style="width:65px;">
          <button class="btn btn-sm btn-outline-secondary move-up-btn" data-idx="${idx}" title="Move up"><i class="ri-arrow-up-line"></i></button>
          <button class="btn btn-sm btn-outline-secondary move-down-btn" data-idx="${idx}" title="Move down"><i class="ri-arrow-down-line"></i></button>
          <button class="btn btn-sm btn-outline-danger remove-rule-btn" data-idx="${idx}" title="Remove rule"><i class="ri-delete-bin-line"></i></button>
        </div>
      </div>
      <div class="rule-card-body">
        <div class="mb-3">
          <label class="fw-semibold small d-block mb-2"><i class="ri-award-line me-1"></i>Promotion Outcome</label>
          <div class="label-selector">${labelPills}</div>
        </div>
        <div class="mb-3">
          <label class="fw-semibold small"><i class="ri-git-branch-line me-1"></i>Grade Grouping <small class="text-muted fw-normal ms-1">(changing this resets count conditions)</small></label>
          <select class="form-select form-select-sm grouping-sel mt-1" data-idx="${idx}" style="max-width:300px;">${groupingOpts}</select>
        </div>

        <div class="rule-section mb-3">
          <div class="rule-section-header">
            <span><i class="ri-star-fill text-warning me-2"></i>Section 1 — Compulsory Subjects <small class="text-muted fw-normal ms-2">(${subjects.length} subjects)</small></span>
          </div>
          <div class="rule-section-body">
            <div class="mb-3">
              <div class="fw-semibold small mb-2"><i class="ri-shield-check-line text-warning me-1"></i>Per-subject minimum grade</div>
              <div id="compSubjRows_${idx}">${compSubjRowsHtml}</div>
            </div>
            <hr class="my-2">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold small"><i class="ri-bar-chart-line me-1"></i>Grade count conditions across compulsory subjects</div>
                <div class="d-flex gap-1 align-items-center">
                  <select class="form-select form-select-sm" id="addCompGrade_${idx}" style="width:80px;">${gradeOptsComp}</select>
                  <button class="btn btn-outline-primary btn-sm add-comp-cond-btn" data-idx="${idx}"><i class="ri-add-line"></i> Add</button>
                </div>
              </div>
              <div id="compCondRows_${idx}">${compCondRows || '<div class="text-muted small ps-1 py-1">No count conditions — click Add above.</div>'}</div>
            </div>
          </div>
        </div>

        <div class="rule-section mb-3">
          <div class="rule-section-header">
            <span><i class="ri-book-open-line text-primary me-2"></i>Section 2 — Other / All Subjects Grade Count <small class="text-muted fw-normal ms-2">(${otherCount} other subjects)</small></span>
          </div>
          <div class="rule-section-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-semibold small"><i class="ri-bar-chart-line me-1"></i>Grade count conditions</div>
              <div class="d-flex gap-1 align-items-center">
                <select class="form-select form-select-sm" id="addOtherGrade_${idx}" style="width:80px;">${gradeOptsOther}</select>
                <button class="btn btn-outline-primary btn-sm add-other-cond-btn" data-idx="${idx}"><i class="ri-add-line"></i> Add</button>
              </div>
            </div>
            <div id="otherCondRows_${idx}">${otherCondRows || '<div class="text-muted small ps-1 py-1">No count conditions — click Add above.</div>'}</div>
          </div>
        </div>

        <div class="avg-box">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input avg-toggle-cb" type="checkbox" role="switch" id="avgCb_${idx}" data-idx="${idx}" ${avg.enabled ? 'checked' : ''}>
            <label class="form-check-label fw-semibold small" for="avgCb_${idx}"><i class="ri-percent-line text-info me-1"></i>Section 3 — Minimum Average Condition (optional)</label>
          </div>
          <div id="avgFields_${idx}" style="${avg.enabled ? '' : 'opacity:.4;pointer-events:none;'}">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Min Average (%)</label>
                <input type="number" class="form-control form-control-sm avg-min-inp" data-idx="${idx}" min="0" max="100" step="0.5" value="${avg.min_average ?? (classPassAvg ?? 50)}">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Logic with Sections 1+2</label>
                <select class="form-select form-select-sm avg-logic-sel" data-idx="${idx}">
                  <option value="AND" ${avg.logic === 'AND' ? 'selected' : ''}>AND (all sections must pass)</option>
                  <option value="OR" ${avg.logic === 'OR' ? 'selected' : ''}>OR (average alone qualifies)</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div id="ruleInterp_${idx}"></div>
      </div>
    </div>`;
}

function rerenderRules() {
    const container = document.getElementById('rulesContainer');
    const noMsg = document.getElementById('noRulesMsg');
    if (!promotionRules.length) {
        if (container) { container.innerHTML = ''; if (noMsg) { container.appendChild(noMsg); noMsg.style.display = 'block'; } }
        updateGlobalInterpPanel();
        return;
    }
    if (noMsg) noMsg.style.display = 'none';
    if (container) {
        container.innerHTML = '';
        promotionRules.forEach((rule, idx) => {
            const div = document.createElement('div');
            div.innerHTML = buildRuleHTML(rule, idx);
            container.appendChild(div.firstElementChild);
        });
    }
    updateRuleInterpretations();
}

function updateRuleInterpretations() {
    promotionRules.forEach((rule, idx) => {
        const container = document.getElementById(`ruleInterp_${idx}`);
        if (!container) return;
        const interp = RuleInterpreter.interpret(rule, isSenior, idx + 1);
        container.innerHTML = RuleInterpreter.renderPanel(interp);
    });
    updateGlobalInterpPanel();
}

function updateGlobalInterpPanel() {
    const panel = document.getElementById('globalInterpPanel');
    if (!panel) return;
    if (!promotionRules.length) { panel.innerHTML = ''; return; }

    const ruleLogic = document.getElementById('rule_logic')?.value || 'grade_count';
    const reqAvg = document.getElementById('promotion_pass_average')?.value;
    const { rules, logicDescription, hasUnreachable, unreachableFrom } = RuleInterpreter.interpretAll(promotionRules, isSenior, ruleLogic, reqAvg);

    let html = `<div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:12.5px;line-height:1.7;">
        <div style="font-weight:700;color:#0c4a6e;margin-bottom:6px;"><i class="ri-route-line me-1"></i>Evaluation flow — ${rules.length} rule${rules.length > 1 ? 's' : ''}</div>
        <div style="color:#075985;">${logicDescription}</div>`;

    if (hasUnreachable) {
        html += `<div style="margin-top:8px;color:#b45309;font-weight:600;background:#fef9c3;padding:6px 10px;border-radius:8px;">
            <i class="ri-alert-line me-1"></i>Rule ${unreachableFrom} onwards is unreachable — Rule ${unreachableFrom - 1} has no conditions and always matches first.
        </div>`;
    }

    const colorMap = { promoted:'#16a34a', trial:'#ca8a04', see_principal:'#0284c7', repeat:'#dc2626' };
    html += `<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">`;
    rules.forEach((r, i) => {
        const c = r.neverFires ? '#dc2626' : (colorMap[r.outcomeKey] || '#6b7280');
        const name = r.summary.replace(/^Rule \d+: /, '') || `Rule ${i+1}`;
        html += `<span title="${escH(name)}" style="background:${c};color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis;display:inline-block;">
            ${i+1}. ${escH(name.length > 16 ? name.slice(0,14)+'…' : name)}
            ${r.neverFires ? ' ⚠' : ''}
        </span>`;
        if (i < rules.length - 1) html += `<i class="ri-arrow-right-s-line" style="color:#94a3b8;font-size:16px;"></i>`;
    });
    html += `<i class="ri-arrow-right-s-line" style="color:#94a3b8;font-size:16px;"></i>`;
    html += `<span style="background:#6b7280;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">No match → Repeat</span>`;
    html += `</div></div>`;

    panel.innerHTML = html;
}

// ── Event Delegation ──────────────────────────────────────────────────────────
function setupEventDelegation() {
    const container = document.getElementById('rulesContainer');
    if (!container) return;
    container.removeEventListener('click', handleContainerClick);
    container.addEventListener('click', handleContainerClick);
    container.removeEventListener('change', handleContainerChange);
    container.addEventListener('change', handleContainerChange);
    container.removeEventListener('input', handleContainerInput);
    container.addEventListener('input', handleContainerInput);
}

function handleContainerClick(e) {
    const addCompBtn = e.target.closest('.add-comp-cond-btn');
    if (addCompBtn) {
        e.preventDefault();
        const idx = parseInt(addCompBtn.dataset.idx);
        const gradeSelect = document.getElementById(`addCompGrade_${idx}`);
        if (gradeSelect && gradeSelect.value) {
            if (!promotionRules[idx].compulsory_section.count_conditions) promotionRules[idx].compulsory_section.count_conditions = [];
            promotionRules[idx].compulsory_section.count_conditions.push({ grade: gradeSelect.value, operator: '>=', count: 1, scope: 'compulsory_only' });
            rerenderRules(); return;
        }
        if (gradeSelect && !gradeSelect.value) Swal.fire('Warning', 'Please select a grade first.', 'warning');
        return;
    }
    const addOtherBtn = e.target.closest('.add-other-cond-btn');
    if (addOtherBtn) {
        e.preventDefault();
        const idx = parseInt(addOtherBtn.dataset.idx);
        const gradeSelect = document.getElementById(`addOtherGrade_${idx}`);
        if (gradeSelect && gradeSelect.value) {
            if (!promotionRules[idx].other_section.count_conditions) promotionRules[idx].other_section.count_conditions = [];
            promotionRules[idx].other_section.count_conditions.push({ grade: gradeSelect.value, operator: '>=', count: 1, scope: 'other_only' });
            rerenderRules(); return;
        }
        if (gradeSelect && !gradeSelect.value) Swal.fire('Warning', 'Please select a grade first.', 'warning');
        return;
    }
    const removeCondBtn = e.target.closest('.remove-cond-btn');
    if (removeCondBtn) {
        e.preventDefault();
        _getConds(parseInt(removeCondBtn.dataset.rule), removeCondBtn.dataset.sec).splice(parseInt(removeCondBtn.dataset.ci), 1);
        rerenderRules(); return;
    }
    const removeRuleBtn = e.target.closest('.remove-rule-btn');
    if (removeRuleBtn) {
        e.preventDefault();
        promotionRules.splice(parseInt(removeRuleBtn.dataset.idx), 1);
        rerenderRules(); return;
    }
    const moveUpBtn = e.target.closest('.move-up-btn');
    if (moveUpBtn) {
        e.preventDefault();
        const idx = parseInt(moveUpBtn.dataset.idx);
        if (idx > 0) { [promotionRules[idx - 1], promotionRules[idx]] = [promotionRules[idx], promotionRules[idx - 1]]; rerenderRules(); }
        return;
    }
    const moveDownBtn = e.target.closest('.move-down-btn');
    if (moveDownBtn) {
        e.preventDefault();
        const idx = parseInt(moveDownBtn.dataset.idx);
        if (idx < promotionRules.length - 1) { [promotionRules[idx], promotionRules[idx + 1]] = [promotionRules[idx + 1], promotionRules[idx]]; rerenderRules(); }
        return;
    }
    const statusPill = e.target.closest('.label-pill');
    if (statusPill) {
        e.preventDefault();
        const idx = parseInt(statusPill.dataset.idx);
        const stat = statusPill.dataset.status;
        promotionRules[idx].status_label = stat;
        statusPill.closest('.label-selector').querySelectorAll('.label-pill').forEach(p => p.classList.toggle('active', p.dataset.status === stat));
        const selSt = STATUS_LABELS.find(s => s.key === stat);
        const badgeEl = document.getElementById(`statusBadge_${idx}`);
        if (badgeEl && selSt) {
            const cls = stat === 'promoted' ? 'success' : stat === 'trial' ? 'warning' : stat === 'see_principal' ? 'info' : 'danger';
            badgeEl.className = `badge bg-${cls}`;
            badgeEl.innerHTML = `<i class="${selSt.icon} me-1"></i>${selSt.label}`;
        }
        updateRuleInterpretations();
        return;
    }
}

function handleContainerChange(e) {
    const compGradeSel = e.target.closest('.comp-subj-grade-sel');
    if (compGradeSel) {
        const idx = parseInt(compGradeSel.dataset.idx), si = parseInt(compGradeSel.dataset.si);
        promotionRules[idx].compulsory_section.subjects[si].min_grade = compGradeSel.value;
        promotionRules[idx].compulsory_section.subjects[si].override = !!compGradeSel.value;
        updateRuleInterpretations(); return;
    }
    const groupingSel = e.target.closest('.grouping-sel');
    if (groupingSel) {
        const idx = parseInt(groupingSel.dataset.idx);
        promotionRules[idx].grade_grouping = groupingSel.value;
        promotionRules[idx].compulsory_section.count_conditions = [];
        promotionRules[idx].other_section.count_conditions = [];
        rerenderRules(); return;
    }
    const condGradeSel = e.target.closest('.cond-grade-sel');
    if (condGradeSel) {
        const ri = parseInt(condGradeSel.dataset.rule), sec = condGradeSel.dataset.sec, ci = parseInt(condGradeSel.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) {
            conds[ci].grade = condGradeSel.value;
            const pill = document.getElementById(`gp_${ri}_${sec}_${ci}`);
            if (pill) { pill.textContent = condGradeSel.value; pill.className = `grade-pill gp-${condGradeSel.value}`; }
        }
        updateRuleInterpretations(); return;
    }
    const condOpSel = e.target.closest('.cond-op-sel');
    if (condOpSel) {
        const ri = parseInt(condOpSel.dataset.rule), sec = condOpSel.dataset.sec, ci = parseInt(condOpSel.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) conds[ci].operator = condOpSel.value;
        updateRuleInterpretations(); return;
    }
    const condScopeSel = e.target.closest('.cond-scope-sel');
    if (condScopeSel) {
        const ri = parseInt(condScopeSel.dataset.rule), sec = condScopeSel.dataset.sec, ci = parseInt(condScopeSel.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) conds[ci].scope = condScopeSel.value;
        updateRuleInterpretations(); return;
    }
    const avgToggle = e.target.closest('.avg-toggle-cb');
    if (avgToggle) {
        const idx = parseInt(avgToggle.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.enabled = avgToggle.checked;
        const f = document.getElementById(`avgFields_${idx}`);
        if (f) { f.style.opacity = avgToggle.checked ? '1' : '0.4'; f.style.pointerEvents = avgToggle.checked ? 'auto' : 'none'; }
        updateRuleInterpretations(); return;
    }
    const avgLogicSel = e.target.closest('.avg-logic-sel');
    if (avgLogicSel) {
        const idx = parseInt(avgLogicSel.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.logic = avgLogicSel.value;
        updateRuleInterpretations(); return;
    }
}

function handleContainerInput(e) {
    const ruleName = e.target.closest('.rule-name-input');
    if (ruleName) {
        promotionRules[parseInt(ruleName.dataset.idx)].rule_name = ruleName.value;
        updateRuleInterpretations(); return;
    }
    const priorityInp = e.target.closest('.priority-input');
    if (priorityInp) { promotionRules[parseInt(priorityInp.dataset.idx)].priority = parseInt(priorityInp.value); return; }
    const condCountInp = e.target.closest('.cond-count-inp');
    if (condCountInp) {
        const ri = parseInt(condCountInp.dataset.rule), sec = condCountInp.dataset.sec, ci = parseInt(condCountInp.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) conds[ci].count = parseInt(condCountInp.value);
        updateRuleInterpretations(); return;
    }
    const avgMinInp = e.target.closest('.avg-min-inp');
    if (avgMinInp) {
        const idx = parseInt(avgMinInp.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.min_average = parseFloat(avgMinInp.value);
        updateRuleInterpretations(); return;
    }
}

function escH(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Class Info Loading ───────────────────────────────────────────────────────
async function refreshClassInfo() {
    const classId = document.getElementById('schoolclass_id').value;
    const termId = document.getElementById('term_id').value;
    const sessionId = document.getElementById('session_id').value;
    const addBtn = document.getElementById('addRuleBtn');
    const loadEl = document.getElementById('subjectLoadStatus');
    const summaryEl = document.getElementById('subjectSummary');
    const scopeInfo = document.getElementById('ruleScopeInfo');

    // Store current average value - IMPORTANT: preserve 0 as a valid value
    const currentAvg = document.getElementById('promotion_pass_average').value;
    const hasCurrentAvg = currentAvg !== '' && currentAvg !== null && currentAvg !== undefined;

    addBtn.disabled = true;
    summaryEl.style.display = 'none';
    if (!classId) { scopeInfo.textContent = ''; rerenderRules(); return; }
    loadEl.style.display = 'block';
    try {
        let url = `/promotion-settings/class-promotion-data?classid=${classId}`;
        if (termId && termId !== '') url += `&termid=${termId}`;
        if (sessionId && sessionId !== '') url += `&sessionid=${sessionId}`;
        const res = await fetch(url);
        const data = await res.json();
        loadEl.style.display = 'none';
        if (data.success) {
            isSenior = data.is_senior;
            totalSubjects = data.total_subjects ?? 0;
            compulsoryCount = data.compulsory_count ?? 0;
            otherCount = data.other_count ?? 0;
            classPassAvg = data.pass_average ?? null;
            gradeScale = (data.grade_scale && data.grade_scale.length) ? data.grade_scale : GRADE_SCALES[isSenior ? 'senior' : 'junior'];

            compulsorySubjects = (data.compulsory_subjects ?? []).map(cs => ({
                subject_id: cs.id,
                subject_name: cs.subject,
                subject_code: cs.subject_code,
                default_min_grade: cs.default_min_grade || '',
                min_grade: cs.default_min_grade || '',
                override: false
            }));

            // FIX: Only set default if no value is currently set (including checking for 0)
            if (classPassAvg !== null && !hasCurrentAvg) {
                document.getElementById('promotion_pass_average').value = classPassAvg;
                document.getElementById('avg_slider').value = classPassAvg;
            } else if (hasCurrentAvg) {
                // Preserve the existing value (including 0)
                document.getElementById('promotion_pass_average').value = currentAvg;
                document.getElementById('avg_slider').value = currentAvg;
            }

            const scaleLabel = isSenior ? 'Senior (A1–F9)' : 'Junior (A–F)';
            scopeInfo.textContent = `${totalSubjects} total | ${compulsoryCount} compulsory | ${otherCount} other | ${scaleLabel}`;

            let summaryHtml = `<div class="alert alert-success py-2 mb-0"><i class="ri-checkbox-circle-line me-1"></i><strong>${totalSubjects}</strong> total subjects &nbsp;|&nbsp;<strong>${compulsoryCount}</strong> compulsory &nbsp;|&nbsp;<strong>${otherCount}</strong> other &nbsp;|&nbsp;Grade scale: <strong>${scaleLabel}</strong>`;
            if (compulsoryCount > 0) summaryHtml += `<br><small class="text-muted mt-1 d-block"><i class="ri-star-fill text-warning me-1"></i>${compulsoryCount} compulsory subject${compulsoryCount > 1 ? 's' : ''} loaded — minimum grades pre-filled from Compulsory Subject setup (overridable per rule).</small>`;
            else summaryHtml += `<br><small class="text-muted mt-1 d-block"><i class="ri-information-line me-1"></i>No compulsory subjects assigned to this class. You can still add grade count conditions.</small>`;
            summaryHtml += `</div>`;
            summaryEl.innerHTML = summaryHtml;
            summaryEl.style.display = 'block';
            addBtn.disabled = false;

            if (promotionRules.length > 0) {
                promotionRules = promotionRules.map(rule => {
                    if (!rule.compulsory_section) rule.compulsory_section = { subjects: [], count_conditions: [] };
                    if (!rule.other_section) rule.other_section = { count_conditions: [] };
                    const existing = (rule.compulsory_section.subjects || []).reduce((m, s) => {
                        if (s.subject_id) m[String(s.subject_id)] = s;
                        return m;
                    }, {});
                    rule.compulsory_section.subjects = compulsorySubjects.map(cs => ({
                        subject_id: cs.subject_id,
                        subject_name: cs.subject_name,
                        subject_code: cs.subject_code,
                        default_min_grade: cs.default_min_grade ?? '',
                        min_grade: existing[String(cs.subject_id)]?.min_grade ?? cs.default_min_grade ?? '',
                        override: !!(existing[String(cs.subject_id)]?.min_grade),
                    }));
                    return rule;
                });
            }
            rerenderRules();
        } else {
            summaryEl.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="ri-error-warning-line me-1"></i>${data.message || 'Failed to load class data.'}</div>`;
            summaryEl.style.display = 'block';
        }
    } catch (err) {
        loadEl.style.display = 'none';
        summaryEl.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="ri-error-warning-line me-1"></i>Error: ${err.message}</div>`;
        summaryEl.style.display = 'block';
        console.error('Error loading class info:', err);
    }
}

// ── Modal Functions ───────────────────────────────────────────────────────────
function openModal() { new bootstrap.Modal(document.getElementById('settingModal')).show(); }

function resetModal() {
    ['setting_id','session_id','term_id','template_id_input','promotion_pass_average'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.getElementById('schoolclass_id').value = '';
    document.getElementById('promoted_label').value = 'Promoted';
    document.getElementById('trial_label').value = 'Promoted on Trial';
    document.getElementById('see_principal_label').value = 'Advised to See Principal';
    document.getElementById('repeat_label').value = 'Advice to Repeat';
    document.getElementById('rule_logic').value = 'grade_count';
    document.getElementById('avg_slider').value = 50;
    document.getElementById('modal_is_active').checked = true;
    document.getElementById('globalAvgSection').style.display = 'none';
    document.getElementById('subjectSummary').style.display = 'none';
    document.getElementById('subjectSummary').innerHTML = '';
    document.getElementById('subjectLoadStatus').style.display = 'none';
    document.getElementById('addRuleBtn').disabled = true;
    document.getElementById('templateSelect').value = '';
    document.getElementById('loadTemplateBtn').disabled = true;
    document.getElementById('templateStatus').textContent = '';
    document.getElementById('ruleScopeInfo').textContent = '';
    const badge = document.getElementById('modalActiveBadge');
    if (badge) { badge.className = 'active-badge is-active'; badge.innerHTML = '<i class="ri-checkbox-circle-line"></i> Active'; }
    promotionRules = [];
    gradeScale = GRADE_SCALES.senior;
    isSenior = true;
    totalSubjects = 0;
    compulsoryCount = 0;
    otherCount = 0;
    classPassAvg = null;
    compulsorySubjects = [];
    rerenderRules();
}

async function handleEditClick(e) {
    const d = e.currentTarget.dataset;
    resetModal();
    document.getElementById('setting_id').value = d.id;
    document.getElementById('schoolclass_id').value = d.schoolclass_id;
    document.getElementById('session_id').value = d.session_id || '';
    document.getElementById('term_id').value = d.term_id || '';
    document.getElementById('promoted_label').value = d.promoted_label;
    document.getElementById('trial_label').value = d.trial_label;
    document.getElementById('see_principal_label').value = d.see_principal_label;
    document.getElementById('repeat_label').value = d.repeat_label;

    const ruleLogic = d.rule_logic || 'grade_count';
    document.getElementById('rule_logic').value = ruleLogic;

    // FIX: Handle 0 correctly - check if property exists and is not undefined/null
    // Use d.promotion_pass_average !== undefined && d.promotion_pass_average !== null
    const avgValue = (d.promotion_pass_average !== undefined && d.promotion_pass_average !== null && d.promotion_pass_average !== '')
        ? d.promotion_pass_average
        : '';
    document.getElementById('promotion_pass_average').value = avgValue;
    document.getElementById('avg_slider').value = (avgValue !== '' ? avgValue : 50);

    document.getElementById('template_id_input').value = d.template_id || '';
    if (d.template_id) document.getElementById('templateSelect').value = d.template_id;

    const isActive = d.is_active === '1';
    document.getElementById('modal_is_active').checked = isActive;
    const badge = document.getElementById('modalActiveBadge');
    if (badge) {
        badge.className = isActive ? 'active-badge is-active' : 'active-badge is-inactive';
        badge.innerHTML = isActive ? '<i class="ri-checkbox-circle-line"></i> Active' : '<i class="ri-close-circle-line"></i> Inactive';
    }

    // Manually trigger change event to show/hide average section
    const ruleLogicSelect = document.getElementById('rule_logic');
    const changeEvent = new Event('change');
    ruleLogicSelect.dispatchEvent(changeEvent);

    try { promotionRules = JSON.parse(d.promotion_rules || '[]'); } catch { promotionRules = []; }
    openModal();
    await refreshClassInfo();
}

function bindEditButtons() {
    document.querySelectorAll('.edit-setting').forEach(btn => {
        btn.removeEventListener('click', handleEditClick);
        btn.addEventListener('click', handleEditClick);
    });
}

async function handleDeleteClick(e) {
    const btn = e.currentTarget;
    const result = await Swal.fire({ title: 'Confirm Delete', icon: 'warning', html: `Delete rules for <strong>${escH(btn.dataset.name)}</strong>?`, showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, Delete' });
    if (!result.isConfirmed) return;
    Swal.fire({ title: 'Deleting…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch(`/promotion-settings/${btn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' } });
        const data = await res.json();
        if (data.success) Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, timer: 1500, showConfirmButton: false }).then(() => location.reload());
        else Swal.fire('Error', data.message || 'Failed.', 'error');
    } catch { Swal.fire('Error', 'Network error.', 'error'); }
}

function bindDeleteButtons() {
    document.querySelectorAll('.delete-setting').forEach(btn => {
        btn.removeEventListener('click', handleDeleteClick);
        btn.addEventListener('click', handleDeleteClick);
    });
}

// ── Save ───────────────────────────────────────────────────────────────────────
document.getElementById('saveSettingBtn')?.addEventListener('click', async function() {
    const classId = document.getElementById('schoolclass_id').value;
    if (!classId) { Swal.fire('Validation', 'Please select a class.', 'warning'); return; }

    // Validate average based on rule logic
    const ruleLogic = document.getElementById('rule_logic').value;
    let avgValue = document.getElementById('promotion_pass_average').value;

    // FIX: For average_only and both, require a numeric value (0 is valid)
    if (ruleLogic === 'average_only' || ruleLogic === 'both') {
        if (avgValue === '' || avgValue === null || avgValue === undefined) {
            Swal.fire('Validation', 'Minimum average is required for Average Only or Both evaluation modes.', 'warning');
            return;
        }
        // Ensure it's a number
        avgValue = parseFloat(avgValue);
        if (isNaN(avgValue)) {
            Swal.fire('Validation', 'Minimum average must be a valid number.', 'warning');
            return;
        }
    }

    for (const [i, rule] of promotionRules.entries()) {
        if (!rule.rule_name?.trim()) { Swal.fire('Validation', `Rule ${i + 1} needs a name.`, 'warning'); return; }
        const hasCompSubjGrades = (rule.compulsory_section?.subjects ?? []).some(s => s.min_grade);
        const hasCompConds = (rule.compulsory_section?.count_conditions ?? []).length > 0;
        const hasOtherConds = (rule.other_section?.count_conditions ?? []).length > 0;
        const hasAvg = rule.average_condition?.enabled;
        if (!hasCompSubjGrades && !hasCompConds && !hasOtherConds && !hasAvg) {
            Swal.fire('Validation', `Rule ${i + 1} has no conditions.`, 'warning');
            return;
        }
    }

    document.getElementById('promotion_rules_input').value = JSON.stringify(promotionRules);
    const fd = new FormData(document.getElementById('settingForm'));
    fd.set('schoolclass_id', classId);
    fd.set('session_id', document.getElementById('session_id').value || '');
    fd.set('term_id', document.getElementById('term_id').value || '');
    fd.set('promoted_label', document.getElementById('promoted_label').value);
    fd.set('trial_label', document.getElementById('trial_label').value);
    fd.set('see_principal_label', document.getElementById('see_principal_label').value);
    fd.set('repeat_label', document.getElementById('repeat_label').value);
    fd.set('rule_logic', ruleLogic);

    // FIX: Send 0 as a valid value, not empty string
    if (ruleLogic === 'average_only' || ruleLogic === 'both') {
        fd.set('promotion_pass_average', avgValue.toString());
    } else {
        fd.set('promotion_pass_average', '');
    }

    fd.set('is_active', document.getElementById('modal_is_active').checked ? '1' : '0');
    fd.set('template_id', document.getElementById('template_id_input').value || '');
    const id = document.getElementById('setting_id').value;
    let url = '/promotion-settings';
    if (id) { url = `/promotion-settings/${id}`; fd.append('_method', 'PUT'); }

    // Debug log to verify what's being sent
    console.log('Saving with values:', {
        rule_logic: ruleLogic,
        promotion_pass_average: avgValue,
        class_id: classId
    });

    Swal.fire({ title: 'Saving…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: fd
        });
        const data = await res.json();
        if (data.success) Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false }).then(() => location.reload());
        else Swal.fire('Error', data.message || 'Failed.', 'error');
    } catch { Swal.fire('Error', 'An error occurred.', 'error'); }
});

// ── Toggle Active ──────────────────────────────────────────────────────────────
document.addEventListener('change', async function(e) {
    if (!e.target.classList.contains('toggle-active-switch')) return;
    const toggle = e.target, sid = toggle.dataset.id, isActive = toggle.checked;
    const badge = document.getElementById('ab' + sid);
    const card = toggle.closest('.setting-card');
    try {
        const res = await fetch(`/promotion-settings/${sid}/toggle-active`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ is_active: isActive }) });
        const data = await res.json();
        if (data.success) {
            if (badge) { badge.className = isActive ? 'active-badge is-active' : 'active-badge is-inactive'; badge.innerHTML = isActive ? '<i class="ri-checkbox-circle-line"></i> Active' : '<i class="ri-close-circle-line"></i> Inactive'; }
            card?.classList.toggle('inactive', !isActive);
        } else { toggle.checked = !isActive; Swal.fire('Error', data.message, 'error'); }
    } catch { toggle.checked = !isActive; Swal.fire('Error', 'Network error.', 'error'); }
});

// ── Event Listeners ───────────────────────────────────────────────────────────
document.getElementById('openAddBtn')?.addEventListener('click', openModal);
document.getElementById('openAddBtn2')?.addEventListener('click', openModal);
document.getElementById('settingModal')?.addEventListener('hidden.bs.modal', resetModal);
document.getElementById('modal_is_active')?.addEventListener('change', function() {
    const b = document.getElementById('modalActiveBadge');
    if (b) { b.className = this.checked ? 'active-badge is-active' : 'active-badge is-inactive'; b.innerHTML = this.checked ? '<i class="ri-checkbox-circle-line"></i> Active' : '<i class="ri-close-circle-line"></i> Inactive'; }
});
document.getElementById('rule_logic')?.addEventListener('change', function() {
    const showAvg = this.value === 'average_only' || this.value === 'both';
    document.getElementById('globalAvgSection').style.display = showAvg ? 'block' : 'none';
    updateGlobalInterpPanel();
});
document.getElementById('avg_slider')?.addEventListener('input', e => {
    document.getElementById('promotion_pass_average').value = e.target.value;
    updateGlobalInterpPanel();
});
document.getElementById('promotion_pass_average')?.addEventListener('input', e => {
    document.getElementById('avg_slider').value = e.target.value;
    updateGlobalInterpPanel();
});
document.getElementById('addRuleBtn')?.addEventListener('click', () => {
    promotionRules.push({
        rule_name: '', status_label: 'promoted', priority: promotionRules.length + 1,
        grade_grouping: 'grouped',
        compulsory_section: {
            subjects: compulsorySubjects.map(cs => ({
                subject_id: cs.subject_id,
                subject_name: cs.subject_name,
                subject_code: cs.subject_code,
                default_min_grade: cs.default_min_grade ?? '',
                min_grade: cs.default_min_grade ?? '',
                override: false,
            })),
            count_conditions: [],
        },
        other_section: { count_conditions: [] },
        average_condition: { enabled: false, min_average: classPassAvg ?? 50, logic: 'AND' },
    });
    rerenderRules();
});
document.getElementById('templateSelect')?.addEventListener('change', function() {
    document.getElementById('loadTemplateBtn').disabled = !this.value;
    document.getElementById('template_id_input').value = this.value;
});
document.getElementById('loadTemplateBtn')?.addEventListener('click', async function() {
    const tplId = document.getElementById('templateSelect').value;
    const classId = document.getElementById('schoolclass_id').value;
    if (!tplId) { Swal.fire('', 'Select a template first.', 'info'); return; }
    if (!classId) { Swal.fire('', 'Select a class first.', 'info'); return; }
    const termId = document.getElementById('term_id').value;
    const sessionId = document.getElementById('session_id').value;
    const status = document.getElementById('templateStatus');
    status.textContent = 'Loading…';
    try {
        let url = `/promotion-templates/${tplId}/load-for-class?classid=${classId}`;
        if (termId) url += `&termid=${termId}`;
        if (sessionId) url += `&sessionid=${sessionId}`;
        const res = await fetch(url);
        const data = await res.json();
        if (data.success) {
            promotionRules = data.merged_rules ?? [];
            status.textContent = `✓ ${data.template.name} loaded (${promotionRules.length} rules)`;
            status.style.color = '#16a34a';
            rerenderRules();
        } else {
            status.textContent = '✗ ' + (data.message || 'Failed');
            status.style.color = '#dc2626';
        }
    } catch (err) { status.textContent = '✗ Error: ' + err.message; status.style.color = '#dc2626'; }
});
['schoolclass_id','session_id','term_id'].forEach(id => document.getElementById(id)?.addEventListener('change', refreshClassInfo));

// ── Initialize ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    setupEventDelegation();
    bindEditButtons();
    bindDeleteButtons();
    const originalRerender = rerenderRules;
    window.rerenderRules = function() { originalRerender(); setupEventDelegation(); };
    rerenderRules = window.rerenderRules;
});
</script>
@endsection
