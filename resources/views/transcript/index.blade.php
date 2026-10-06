@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Student Transcript Generator</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Results</a></li>
                                <li class="breadcrumb-item active">Transcript</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @foreach(['success','error','warning'] as $bag)
                @if(session($bag))
                    <div class="alert alert-{{ $bag === 'error' ? 'danger' : $bag }} alert-dismissible fade show">
                        {{ session($bag) }}<button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            @endforeach

            <div class="row g-4">

                {{-- LEFT: Search & Config --}}
                <div class="col-lg-5">

                    {{-- Search Card --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header" style="background:#1e3a5f;">
                            <h5 class="card-title mb-0 text-white">
                                <i class="ri-search-line me-2"></i>Find Student
                            </h5>
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Search by Name or Admission No.</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                                    <input type="text" id="studentSearch" class="form-control"
                                           placeholder="Type name or admission number…">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size:12px;">Filter by Session</label>
                                    <select id="filterSession" class="form-select form-select-sm">
                                        <option value="">All Sessions</option>
                                        @foreach($sessions as $s)
                                            <option value="{{ $s->id }}">{{ $s->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size:12px;">Filter by Class</label>
                                    <select id="filterClass" class="form-select form-select-sm">
                                        <option value="">All Classes</option>
                                        @foreach($schoolclasses as $cls)
                                            <option value="{{ $cls->id }}">{{ $cls->schoolclass }} {{ $cls->arm }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Results --}}
                            <div id="searchResults" style="display:none;max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;">
                                <div id="searchList"></div>
                            </div>
                            <div id="searchLoader" style="display:none;" class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                                <span class="ms-2 text-muted" style="font-size:13px;">Searching…</span>
                            </div>
                            <div id="noResults" style="display:none;" class="text-center py-3 text-muted" style="font-size:13px;">
                                No students found.
                            </div>
                        </div>
                    </div>

                    {{-- Selected Student Card --}}
                    <div class="card shadow-sm mb-4" id="selectedStudentCard" style="display:none;">
                        <div class="card-header d-flex align-items-center" style="background:#f0f4fa;">
                            <h6 class="mb-0 fw-bold text-primary flex-grow-1">
                                <i class="ri-user-line me-2"></i>Selected Student
                            </h6>
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearStudent()">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <img id="selStudentImg" src="" class="rounded-circle"
                                     style="width:56px;height:56px;object-fit:cover;border:3px solid #bfdbfe;"
                                     onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
                                <div>
                                    <div class="fw-bold" id="selStudentName" style="font-size:15px;color:#1e3a5f;"></div>
                                    <div class="text-muted" id="selStudentAdm" style="font-size:12px;"></div>
                                    <div id="selStudentClass" style="font-size:12px;color:#2563eb;"></div>
                                </div>
                            </div>
                            <input type="hidden" id="selectedStudentId">
                        </div>
                    </div>

                    {{-- Transcript Options --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header" style="background:#1e3a5f;">
                            <h5 class="card-title mb-0 text-white">
                                <i class="ri-settings-3-line me-2"></i>Transcript Options
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Transcript Type</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="transcriptType"
                                               id="typeAll" value="full" checked onchange="toggleTypeOptions()">
                                        <label class="form-check-label" for="typeAll">
                                            <strong>Full Transcript</strong>
                                            <span class="text-muted d-block" style="font-size:11px;">All sessions and terms on record</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="transcriptType"
                                               id="typeSession" value="session" onchange="toggleTypeOptions()">
                                        <label class="form-check-label" for="typeSession">
                                            <strong>Single Session</strong>
                                            <span class="text-muted d-block" style="font-size:11px;">All terms within one session</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="transcriptType"
                                               id="typeTerm" value="term" onchange="toggleTypeOptions()">
                                        <label class="form-check-label" for="typeTerm">
                                            <strong>Single Term</strong>
                                            <span class="text-muted d-block" style="font-size:11px;">One specific term only</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="sessionOptions" style="display:none;" class="mb-3">
                                <label class="form-label fw-semibold">Session</label>
                                <select id="transcriptSession" class="form-select">
                                    <option value="">Select session…</option>
                                    @foreach($sessions as $s)
                                        <option value="{{ $s->id }}">{{ $s->session }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="termOptions" style="display:none;" class="mb-3">
                                <label class="form-label fw-semibold">Term</label>
                                <select id="transcriptTerm" class="form-select">
                                    <option value="">Select term…</option>
                                    @foreach($terms as $t)
                                        <option value="{{ $t->id }}">{{ $t->term }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Copy Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="copyType"
                                               id="copyOriginal" value="original" checked>
                                        <label class="form-check-label" for="copyOriginal">
                                            Original
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="copyType"
                                               id="copyDuplicate" value="duplicate">
                                        <label class="form-check-label" for="copyDuplicate">
                                            Duplicate
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button class="btn btn-outline-primary flex-grow-1" id="previewBtn"
                                        onclick="doAction('preview')" disabled>
                                    <i class="ri-eye-line me-1"></i>Preview
                                </button>
                                <button class="btn btn-danger flex-grow-1" id="pdfBtn"
                                        onclick="doAction('pdf')" disabled>
                                    <i class="ri-file-pdf-line me-1"></i>Download PDF
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- RIGHT: Info panel --}}
                <div class="col-lg-7">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header" style="background:#1e3a5f;">
                            <h5 class="card-title mb-0 text-white">
                                <i class="ri-information-line me-2"></i>What's in the Transcript
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @php
                                $features = [
                                    ['ri-building-line','#2563eb','School Header','Official school name, address, logo, motto and contact information with watermarks.'],
                                    ['ri-user-3-line','#16a34a','Student Profile','Full name, admission number, date of birth, gender and student photo.'],
                                    ['ri-calendar-line','#d97706','Session History','Results grouped by academic session and term for a complete academic record.'],
                                    ['ri-book-2-line','#7c3aed','Subject Scores','Term total, brought forward (BF), cumulative score, grade and class position per subject.'],
                                    ['ri-trophy-line','#dc2626','Performance Summary','Term average, cumulative average, GPA, overall class position and promotion status.'],
                                    ['ri-chat-quote-line','#0891b2','Principal\'s Comment','Official remark from the principal for each term where available.'],
                                    ['ri-seal-line','#1e3a5f','Watermark','"ORIGINAL COPY" or "DUPLICATE" stamp with school logo watermark on every page.'],
                                    ['ri-pen-nib-line','#374151','Signatures','Authorisation signatures from class teacher and principal with date.'],
                                ];
                                @endphp
                                @foreach($features as $f)
                                <div class="col-md-6">
                                    <div class="d-flex gap-3 align-items-start p-3 rounded-3"
                                         style="background:#f8fafc;border:1px solid #e2e8f0;">
                                        <i class="{{ $f[0] }}" style="font-size:22px;color:{{ $f[1] }};flex-shrink:0;margin-top:2px;"></i>
                                        <div>
                                            <div class="fw-bold" style="font-size:13px;color:#1e3a5f;">{{ $f[2] }}</div>
                                            <div class="text-muted" style="font-size:11.5px;margin-top:2px;line-height:1.4;">{{ $f[3] }}</div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="alert alert-info mt-4 mb-0 d-flex gap-2">
                                <i class="ri-shield-check-line fs-5 flex-shrink-0"></i>
                                <div style="font-size:13px;">
                                    <strong>Security Features:</strong> Each transcript PDF includes the school logo as a background watermark, an "ORIGINAL COPY" or "DUPLICATE" diagonal stamp, and the generation date and authorising officer's name at the bottom.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

{{-- Hidden export form --}}
<form id="exportForm" method="POST" target="_blank" style="display:none;">
    @csrf
    <input type="hidden" name="student_id" id="ef_student">
    <input type="hidden" name="type"        id="ef_type">
    <input type="hidden" name="session_id"  id="ef_session">
    <input type="hidden" name="term_id"     id="ef_term">
    <input type="hidden" name="copy_type"   id="ef_copy">
</form>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let searchTimer = null;
let selectedStudent = null;

// ── Search ────────────────────────────────────────────────────────────────────
document.getElementById('studentSearch').addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        return;
    }
    searchTimer = setTimeout(() => doSearch(q), 350);
});

['filterSession', 'filterClass'].forEach(id => {
    document.getElementById(id).addEventListener('change', function () {
        const q = document.getElementById('studentSearch').value.trim();
        if (q.length >= 2) doSearch(q);
    });
});

function doSearch(q) {
    document.getElementById('searchLoader').style.display = 'block';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('noResults').style.display = 'none';

    const sessionId = document.getElementById('filterSession').value;
    const classId   = document.getElementById('filterClass').value;

    fetch('{{ route("transcript.search") }}', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body   : JSON.stringify({ q, session_id: sessionId, class_id: classId }),
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('searchLoader').style.display = 'none';
        if (!data.success || !data.students.length) {
            document.getElementById('noResults').style.display = 'block';
            return;
        }
        renderSearchResults(data.students);
    })
    .catch(() => { document.getElementById('searchLoader').style.display = 'none'; });
}

function renderSearchResults(students) {
    const list = document.getElementById('searchList');
    list.innerHTML = '';

    students.forEach(s => {
        const img = s.picture
            ? `/storage/student_avatars/${s.picture.split('/').pop()}`
            : `/storage/student_avatars/unnamed.jpg`;

        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-3 p-3';
        div.style.cssText = 'cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .15s;';
        div.onmouseenter = () => div.style.background = '#f0f9ff';
        div.onmouseleave = () => div.style.background = '';
        div.innerHTML = `
            <img src="${img}" class="rounded-circle" style="width:38px;height:38px;object-fit:cover;border:2px solid #e2e8f0;"
                 onerror="this.src='/storage/student_avatars/unnamed.jpg'">
            <div class="flex-grow-1">
                <div class="fw-bold" style="font-size:13px;">${s.lastname}, ${s.firstname} ${s.othername || ''}</div>
                <div class="text-muted" style="font-size:11px;">${s.admissionno} &nbsp;·&nbsp; ${s.schoolclass || ''} ${s.arm || ''}</div>
            </div>
            <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">${s.session || ''}</span>
        `;
        div.onclick = () => selectStudent(s);
        list.appendChild(div);
    });

    document.getElementById('searchResults').style.display = 'block';
}

function selectStudent(s) {
    selectedStudent = s;
    document.getElementById('selectedStudentId').value = s.id;

    const img = s.picture
        ? `/storage/student_avatars/${s.picture.split('/').pop()}`
        : `/storage/student_avatars/unnamed.jpg`;

    document.getElementById('selStudentImg').src = img;
    document.getElementById('selStudentName').textContent = `${s.lastname} ${s.firstname} ${s.othername || ''}`.trim();
    document.getElementById('selStudentAdm').textContent  = 'Adm: ' + s.admissionno;
    document.getElementById('selStudentClass').textContent= (s.schoolclass || '') + ' ' + (s.arm || '') + (s.session ? ' · ' + s.session : '');

    document.getElementById('selectedStudentCard').style.display = 'block';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('studentSearch').value = '';

    document.getElementById('previewBtn').disabled = false;
    document.getElementById('pdfBtn').disabled     = false;
}

function clearStudent() {
    selectedStudent = null;
    document.getElementById('selectedStudentId').value = '';
    document.getElementById('selectedStudentCard').style.display = 'none';
    document.getElementById('previewBtn').disabled = true;
    document.getElementById('pdfBtn').disabled     = true;
}

// ── Type toggle ───────────────────────────────────────────────────────────────
function toggleTypeOptions() {
    const type = document.querySelector('input[name="transcriptType"]:checked')?.value;
    document.getElementById('sessionOptions').style.display = (type === 'session' || type === 'term') ? 'block' : 'none';
    document.getElementById('termOptions').style.display    = (type === 'term') ? 'block' : 'none';
}

// ── Export / Preview ──────────────────────────────────────────────────────────
function doAction(action) {
    const studentId = document.getElementById('selectedStudentId').value;
    if (!studentId) { alert('Please select a student first.'); return; }

    const type      = document.querySelector('input[name="transcriptType"]:checked')?.value ?? 'full';
    const sessionId = document.getElementById('transcriptSession').value;
    const termId    = document.getElementById('transcriptTerm').value;
    const copyType  = document.querySelector('input[name="copyType"]:checked')?.value ?? 'original';

    if ((type === 'session' || type === 'term') && !sessionId) {
        alert('Please select a session.'); return;
    }
    if (type === 'term' && !termId) {
        alert('Please select a term.'); return;
    }

    const form = document.getElementById('exportForm');
    form.action = action === 'pdf'
        ? '{{ route("transcript.pdf") }}'
        : '{{ route("transcript.preview") }}';

    document.getElementById('ef_student').value = studentId;
    document.getElementById('ef_type').value    = type;
    document.getElementById('ef_session').value = sessionId;
    document.getElementById('ef_term').value    = termId;
    document.getElementById('ef_copy').value    = copyType;

    form.submit();
}
</script>
@endsection
