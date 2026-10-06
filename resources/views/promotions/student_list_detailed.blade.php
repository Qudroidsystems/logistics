{{-- resources/views/promotions/student_list_detailed.blade.php
     "Detailed" print format (ported from topclass-portal). The compact
     format lives in student_list.blade.php -- both are served by
     PromotionController::studentList(), picked with ?format=. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Promotion Recommendation List — {{ $scopeLabel }}</title>
<style id="dynamicPageStyle">@page { size: A4 landscape; margin: 1.2cm; }</style>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', 'Arial', sans-serif; font-size: 13px; background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #0f2342; line-height: 1.5; min-height: 100vh; }

@media print {
    body { background: #fff !important; font-size: 11px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .page-wrap { max-width: none !important; padding: 0 !important; background: #fff !important; }
    .school-header { page-break-after: avoid; }
    .group-section { page-break-inside: avoid; }
}

.page-wrap { max-width: 1400px; margin: 0 auto; padding: 24px 20px; }

.school-header { background: linear-gradient(135deg, #0f2342 0%, #1e3a5f 55%, #0d9488 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 24px; display: flex; align-items: center; gap: 24px; color: white; }
.school-logo { width: 85px; height: 85px; border-radius: 50%; object-fit: contain; border: 3px solid rgba(255,255,255,.4); background: white; flex-shrink: 0; }
.school-info { flex: 1; text-align: center; }
.school-name { font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; line-height: 1.2; }
.school-address { font-size: 12px; opacity: 0.85; margin-top: 6px; }
.school-motto { font-size: 11.5px; font-style: italic; opacity: 0.7; margin-top: 4px; }

.list-title-bar { background: linear-gradient(135deg, #0f2342, #1e4a7e); color: white; text-align: center; padding: 12px 24px; font-size: 16px; font-weight: 700; letter-spacing: 2px; border-radius: 12px; margin-bottom: 20px; }

.meta-strip { display: flex; border: 1px solid #e2e8f0; border-radius: 12px; background: white; margin-bottom: 24px; overflow: hidden; flex-wrap: wrap; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.meta-cell { flex: 1; padding: 12px 16px; border-right: 1px solid #e2e8f0; text-align: center; min-width: 100px; }
.meta-cell:last-child { border-right: none; }
.meta-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; }
.meta-value { font-size: 14px; font-weight: 700; color: #0f2342; display: block; margin-top: 4px; }

.toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: white; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.toolbar-title { font-size: 16px; font-weight: 700; color: #0f2342; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.toolbar-actions button, .toolbar-actions a { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn-settings { background: #f1f5f9; color: #0f2342; }
.btn-print    { background: #0d9488; color: white; }
.btn-pdf      { background: #7c3aed; color: white; }
.btn-close-tab{ background: #f1f5f9; color: #64748b; }

.settings-panel { display: none; background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.settings-panel.open { display: block; }
.settings-panel h4 { font-size: 13px; margin-bottom: 12px; color: #0f2342; display: flex; align-items: center; gap: 6px; }
.settings-group { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 14px; }
.settings-group label { font-size: 12px; font-weight: 600; color: #334155; display: flex; flex-direction: column; gap: 4px; }
.settings-group select { padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; }
.settings-group label input[type="checkbox"] { margin-right: 6px; }
.columns-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 8px; }
.columns-grid label { font-size: 12px; font-weight: 500; color: #334155; display: flex; flex-direction: row; align-items: center; gap: 6px; }

.class-section { margin-bottom: 36px; }
.class-section-header { background: linear-gradient(135deg, #0f2342, #1e3a5f); color: white; padding: 10px 18px; border-radius: 10px; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.class-section-header .count-badge { margin-left: auto; background: rgba(255,255,255,.18); padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }

.group-section { margin-bottom: 20px; }
.group-header { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.status-promoted      { background: #d1fae5; color: #065f46; }
.status-trial         { background: #fef3c7; color: #92400e; }
.status-see_principal { background: #dbeafe; color: #1e40af; }
.status-repeated      { background: #fee2e2; color: #991b1b; }
.status-awaiting      { background: #f1f5f9; color: #475569; }
.status-other         { background: #f1f5f9; color: #475569; }
.count-badge { background: rgba(0,0,0,.08); padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

.table-responsive-wrapper { overflow-x: auto; border-radius: 10px; border: 1px solid #e2e8f0; }
.student-table { width: 100%; border-collapse: collapse; background: white; font-size: 12.5px; }
.student-table thead th { background: #f8fafc; color: #334155; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.4px; padding: 8px 10px; text-align: left; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.student-table tbody td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.student-table tbody tr:last-child td { border-bottom: none; }
.student-table tbody tr:nth-child(even) { background: #fafbfc; }
.student-table tr { break-inside: avoid; }
.sn-cell { text-align: center; color: #94a3b8; font-weight: 600; }
.name-cell { font-weight: 600; }
.adm-cell { color: #64748b; }
.gender-cell, .arm-cell { text-align: center; }
.student-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; }
.avatar-initials { width: 30px; height: 30px; border-radius: 50%; background: #0d9488; color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }

.summary-footer { background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.summary-footer h4 { font-size: 14px; margin-bottom: 14px; color: #0f2342; display: flex; align-items: center; gap: 6px; }
.summary-grid { display: flex; gap: 12px; flex-wrap: wrap; }
.summary-item { flex: 1; min-width: 110px; border-radius: 10px; padding: 14px; text-align: center; border: 2px solid; }
.summary-count { font-size: 24px; font-weight: 800; display: block; }
.summary-lbl { font-size: 11px; font-weight: 600; display: block; margin-top: 2px; }
.class-summary-table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }
.class-summary-table th { text-align: left; padding: 6px 10px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: 10.5px; text-transform: uppercase; color: #64748b; }
.class-summary-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }

.generated-line { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 16px; }

.hide-col { display: none !important; }
</style>
</head>
<body>
<div class="page-wrap">

    {{-- ══ TOOLBAR (no-print) ══ --}}
    <div class="toolbar no-print">
        <div class="toolbar-title">
            <span style="font-size:20px;">🖨️</span>
            Promotion Recommendation List
            <span style="font-size:12px;font-weight:400;color:#64748b;">
                — {{ $scopeLabel }} &nbsp;·&nbsp; {{ $schoolsession->session ?? '' }} &nbsp;·&nbsp; {{ $schoolterm->term ?? '' }}
            </span>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn-settings" onclick="toggleSettings()">⚙️ Settings</button>
            <button type="button" class="btn-print" onclick="printStudentList()">🖨️ Print</button>
            <button type="button" class="btn-pdf" onclick="printStudentList()" title="Choose 'Save as PDF' in the print dialog">📄 Save as PDF</button>
            <a href="javascript:window.close()" class="btn-close-tab">✕ Close</a>
        </div>
    </div>

    {{-- ══ SETTINGS PANEL (client-side only) ══ --}}
    <div id="settingsPanel" class="settings-panel no-print">
        <h4>⚙️ Print &amp; Display Settings</h4>
        <div class="settings-group">
            <label>
                <span>📄 Page Orientation</span>
                <select id="printOrientation">
                    <option value="portrait">Portrait</option>
                    <option value="landscape" selected>Landscape</option>
                </select>
            </label>
            <label>
                <span>📏 Paper Size</span>
                <select id="paperSize">
                    @foreach (['A4','A3','A2','A1','Legal','Letter'] as $size)
                        <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>📄 New Page per Class</span>
                <select id="newPagePerClass">
                    <option value="yes" selected>Yes</option>
                    <option value="no">No</option>
                </select>
            </label>
            <label>
                <span>📄 New Page per Recommendation Group</span>
                <select id="newPagePerGroup">
                    <option value="yes">Yes</option>
                    <option value="no" selected>No</option>
                </select>
            </label>
        </div>
        <div class="settings-group">
            <label style="flex-direction:row;align-items:center;"><input type="checkbox" id="showPhotosCheckbox" {{ $showPhotos ? 'checked' : '' }}> 📷 Show Photos</label>
            <label style="flex-direction:row;align-items:center;"><input type="checkbox" id="showSnCheckbox" {{ $showSn ? 'checked' : '' }}> 🔢 Show Serial Numbers</label>
        </div>
        <div>
            <h4 style="font-size:12px;">📋 Columns to Display</h4>
            <div class="columns-grid">
                @php
                    $columnOptions = [
                        'admissionno'     => 'Admission Number',
                        'gender'          => 'Gender',
                        'dateofbirth'     => 'Date of Birth',
                        'arm'             => 'Arm',
                        'overall_average' => 'Overall Average',
                        'position'        => 'Class Position',
                    ];
                @endphp
                @foreach($columnOptions as $key => $label)
                    <label>
                        <input type="checkbox" class="column-checkbox" value="{{ $key }}"
                            {{ in_array($key, $listFields) ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ SCHOOL HEADER ══ --}}
    <div class="school-header">
        <img src="{{ $schoolLogoBase64 }}" class="school-logo" alt="Logo">
        <div class="school-info">
            <div class="school-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
            @if(!empty($schoolInfo->school_address))
                <div class="school-address">{{ $schoolInfo->school_address }}</div>
            @endif
            @if(!empty($schoolInfo->school_motto))
                <div class="school-motto">"{{ $schoolInfo->school_motto }}"</div>
            @endif
        </div>
        <div style="width:80px;flex-shrink:0;"></div>
    </div>

    <div class="list-title-bar">STUDENT PROMOTION RECOMMENDATION LIST</div>

    {{-- ══ META STRIP ══ --}}
    <div class="meta-strip">
        <div class="meta-cell"><span class="meta-label">Scope</span><span class="meta-value" style="font-size:13px;">{{ $scopeLabel }}</span></div>
        <div class="meta-cell"><span class="meta-label">Session</span><span class="meta-value">{{ $schoolsession->session ?? '-' }}</span></div>
        <div class="meta-cell"><span class="meta-label">Term</span><span class="meta-value">{{ $schoolterm->term ?? '-' }}</span></div>
        <div class="meta-cell"><span class="meta-label">Basis</span><span class="meta-value" style="font-size:12px;">{{ $averageBasis === 'cum' ? 'Cumulative (Cum Avg)' : 'Term Total' }}</span></div>
        <div class="meta-cell"><span class="meta-label">Classes</span><span class="meta-value">{{ count($classGroups) }}</span></div>
        <div class="meta-cell"><span class="meta-label">Total Students</span><span class="meta-value">{{ $grandTotal }}</span></div>
        <div class="meta-cell"><span class="meta-label">Generated</span><span class="meta-value" style="font-size:11px;">{{ $generatedAt }}</span></div>
    </div>

    @php
        $statusMeta = [
            'promoted'      => ['label' => 'Promoted',                 'icon' => '✅', 'class' => 'status-promoted'],
            'trial'         => ['label' => 'Promoted on Trial',        'icon' => '⚠️', 'class' => 'status-trial'],
            'see_principal' => ['label' => 'Advised to See Principal', 'icon' => '👤', 'class' => 'status-see_principal'],
            'repeated'      => ['label' => 'Advice to Repeat',         'icon' => '🔁', 'class' => 'status-repeated'],
            'awaiting'      => ['label' => 'Awaiting Decision',        'icon' => '⏳', 'class' => 'status-awaiting'],
            '__other'       => ['label' => 'Other',                    'icon' => '📌', 'class' => 'status-other'],
        ];
        $globalSn = 0;
    @endphp

    {{-- ══ CLASS SECTIONS ══ --}}
    <div id="listContent">
        @forelse($classGroups as $cg)
            <div class="class-section" data-class-id="{{ $cg['schoolclassid'] }}">
                <div class="class-section-header">
                    <span>🏫</span>
                    <span>{{ $cg['label'] }}</span>
                    <span class="count-badge">{{ $cg['totalStudents'] }} Student{{ $cg['totalStudents'] === 1 ? '' : 's' }}</span>
                </div>

                @foreach($cg['grouped'] as $statusKey => $students)
                    @php
                        $meta       = $statusMeta[$statusKey] ?? $statusMeta['__other'];
                        $groupLabel = $students[0]['promotion_label'] ?? $meta['label'];
                        $groupCount = count($students);
                    @endphp
                    <div class="group-section" data-status="{{ $statusKey }}">
                        <div class="group-header {{ $meta['class'] }}">
                            <span>{{ $meta['icon'] }}</span>
                            <span>{{ $groupLabel }}</span>
                            <span class="count-badge">{{ $groupCount }} Student{{ $groupCount === 1 ? '' : 's' }}</span>
                        </div>
                        <div class="table-responsive-wrapper">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th class="col-sn">#</th>
                                        <th class="col-photo">&nbsp;</th>
                                        <th>Student Name</th>
                                        <th class="col-admissionno">Admission No</th>
                                        <th class="col-gender">Gender</th>
                                        <th class="col-dateofbirth">Date of Birth</th>
                                        <th class="col-arm">Arm</th>
                                        <th class="col-overall_average">Overall Avg</th>
                                        <th class="col-position">Position</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $stu)
                                        @php
                                            $globalSn++;
                                            $hasPic   = !empty($stu['picture']) && $stu['picture'] !== 'unnamed.jpg';
                                            $imgSrc   = $hasPic ? asset('storage/student_avatars/' . basename($stu['picture'])) : null;
                                            $initials = strtoupper(substr($stu['lastname'] ?? '', 0, 1) . substr($stu['firstname'] ?? '', 0, 1)) ?: 'ST';
                                            $dob = '—';
                                            if (!empty($stu['dateofbirth']) && $stu['dateofbirth'] !== 'N/A') {
                                                try { $dob = \Carbon\Carbon::parse($stu['dateofbirth'])->format('d M Y'); }
                                                catch (\Throwable $e) { $dob = $stu['dateofbirth']; }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="sn-cell col-sn">{{ $globalSn }}</td>
                                            <td class="col-photo" style="width:44px;">
                                                @if($imgSrc)
                                                    <img src="{{ $imgSrc }}" class="student-avatar" alt=""
                                                         onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'">
                                                    <span class="avatar-initials" style="display:none;">{{ $initials }}</span>
                                                @else
                                                    <span class="avatar-initials">{{ $initials }}</span>
                                                @endif
                                            </td>
                                            <td class="name-cell">{{ strtoupper($stu['lastname'] ?? '') }}, {{ $stu['firstname'] ?? '' }}</td>
                                            <td class="adm-cell col-admissionno">{{ $stu['admissionno'] ?? '—' }}</td>
                                            <td class="gender-cell col-gender">{{ $stu['gender'] ?? '—' }}</td>
                                            <td class="col-dateofbirth">{{ $dob }}</td>
                                            <td class="arm-cell col-arm">{{ $stu['arm'] ?: '—' }}</td>
                                            <td class="col-overall_average" style="text-align:center;font-weight:700;color:#7c3aed;">
                                                {{ $stu['overall_average'] !== null ? number_format($stu['overall_average'], 1) . '%' : '—' }}
                                            </td>
                                            <td class="col-position" style="text-align:center;font-weight:700;color:#1e40af;">{{ $stu['position'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
                <div style="font-size:40px;">📭</div>
                <p style="margin-top:10px;">No students found for this scope, session and term.</p>
            </div>
        @endforelse
    </div>

    {{-- ══ SUMMARY FOOTER ══ --}}
    <div class="summary-footer">
        <h4>📊 Summary by Recommendation (All Classes in Scope)</h4>
        <div class="summary-grid">
            @foreach($overallGrouped as $statusKey => $count)
                @php
                    $meta = $statusMeta[$statusKey] ?? $statusMeta['__other'];
                    $colors = match($statusKey) {
                        'promoted'      => ['bg' => '#d1fae5', 'text' => '#065f46'],
                        'trial'         => ['bg' => '#fef3c7', 'text' => '#92400e'],
                        'see_principal' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'repeated'      => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                        default         => ['bg' => '#f1f5f9', 'text' => '#475569'],
                    };
                @endphp
                <div class="summary-item" style="background:{{ $colors['bg'] }};border-color:{{ $colors['bg'] }};">
                    <span class="summary-count" style="color:{{ $colors['text'] }};">{{ $count }}</span>
                    <span class="summary-lbl" style="color:{{ $colors['text'] }};">{{ $meta['label'] }}</span>
                </div>
            @endforeach
            <div class="summary-item" style="background:linear-gradient(135deg,#0f2342,#1e3a5f);border-color:#0f2342;">
                <span class="summary-count" style="color:white;">{{ $grandTotal }}</span>
                <span class="summary-lbl" style="color:rgba(255,255,255,.8);">Total Students</span>
            </div>
        </div>

        @if(count($classGroups) > 1)
            <table class="class-summary-table">
                <thead>
                    <tr>
                        <th>Class / Arm</th>
                        <th>Total</th>
                        @foreach($recommendationOrder as $status)
                            <th>{{ $statusMeta[$status]['label'] ?? ucfirst($status) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($classGroups as $cg)
                        <tr>
                            <td style="font-weight:600;">{{ $cg['label'] }}</td>
                            <td>{{ $cg['totalStudents'] }}</td>
                            @foreach($recommendationOrder as $status)
                                <td>{{ count($cg['grouped'][$status] ?? []) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="generated-line">
        Generated: {{ $generatedAt }} &nbsp;·&nbsp; {{ $scopeLabel }} &nbsp;·&nbsp; {{ $schoolsession->session ?? '' }} &nbsp;·&nbsp; {{ $schoolterm->term ?? '' }}
        <br>Recommendations are computed live from promotion settings; saved decisions may differ.
    </div>
</div>

<script>
var PREF_KEY = 'promoListDetailedPrefs';
var ALL_COLS = ['admissionno', 'gender', 'dateofbirth', 'arm', 'overall_average', 'position'];

function toggleSettings() {
    document.getElementById('settingsPanel').classList.toggle('open');
}

function applyPageSize() {
    var orientation = document.getElementById('printOrientation').value;
    var paperSize   = document.getElementById('paperSize').value;
    document.getElementById('dynamicPageStyle').textContent =
        '@page { size: ' + paperSize + ' ' + orientation + '; margin: 1.2cm; }';
}

function applyPageBreaks() {
    var perClass = document.getElementById('newPagePerClass').value === 'yes';
    var perGroup = document.getElementById('newPagePerGroup').value === 'yes';

    var classSections = document.querySelectorAll('.class-section');
    classSections.forEach(function (sec, idx) {
        sec.style.breakAfter = (perClass && idx < classSections.length - 1) ? 'page' : 'auto';
    });
    document.querySelectorAll('.group-section').forEach(function (grp) {
        grp.style.breakAfter = perGroup ? 'page' : 'auto';
    });
}

function applyColumnVisibility() {
    var checked = [];
    document.querySelectorAll('.column-checkbox:checked').forEach(function (cb) { checked.push(cb.value); });
    ALL_COLS.forEach(function (col) {
        var show = checked.indexOf(col) !== -1;
        document.querySelectorAll('.col-' + col).forEach(function (el) { el.classList.toggle('hide-col', !show); });
    });
    var showPhotos = document.getElementById('showPhotosCheckbox').checked;
    document.querySelectorAll('.col-photo').forEach(function (el) { el.classList.toggle('hide-col', !showPhotos); });
    var showSn = document.getElementById('showSnCheckbox').checked;
    document.querySelectorAll('.col-sn').forEach(function (el) { el.classList.toggle('hide-col', !showSn); });
}

function applyAll() {
    applyPageSize();
    applyPageBreaks();
    applyColumnVisibility();
    savePreferences();
}

function savePreferences() {
    try {
        var cols = [];
        document.querySelectorAll('.column-checkbox:checked').forEach(function (cb) { cols.push(cb.value); });
        localStorage.setItem(PREF_KEY, JSON.stringify({
            cols: cols,
            photos: document.getElementById('showPhotosCheckbox').checked,
            sn: document.getElementById('showSnCheckbox').checked,
            orientation: document.getElementById('printOrientation').value,
            paper: document.getElementById('paperSize').value,
            perClass: document.getElementById('newPagePerClass').value,
            perGroup: document.getElementById('newPagePerGroup').value
        }));
    } catch (e) { /* storage unavailable -- page still works */ }
}

function loadPreferences() {
    try {
        var p = JSON.parse(localStorage.getItem(PREF_KEY) || 'null');
        if (!p) return;
        if (Array.isArray(p.cols)) {
            document.querySelectorAll('.column-checkbox').forEach(function (cb) { cb.checked = p.cols.indexOf(cb.value) !== -1; });
        }
        if (typeof p.photos === 'boolean') document.getElementById('showPhotosCheckbox').checked = p.photos;
        if (typeof p.sn === 'boolean')     document.getElementById('showSnCheckbox').checked = p.sn;
        if (p.orientation) document.getElementById('printOrientation').value = p.orientation;
        if (p.paper)       document.getElementById('paperSize').value = p.paper;
        if (p.perClass)    document.getElementById('newPagePerClass').value = p.perClass;
        if (p.perGroup)    document.getElementById('newPagePerGroup').value = p.perGroup;
    } catch (e) { /* ignore */ }
}

function printStudentList() {
    applyPageSize();
    applyPageBreaks();
    window.print();
}

window.onbeforeprint = function () {
    var panel = document.getElementById('settingsPanel');
    if (panel) panel.classList.remove('open');
};

document.addEventListener('DOMContentLoaded', function () {
    loadPreferences();
    applyAll();
    document.querySelectorAll('#settingsPanel select, #settingsPanel input[type="checkbox"]').forEach(function (el) {
        el.addEventListener('change', applyAll);
    });
});
</script>
</body>
</html>
