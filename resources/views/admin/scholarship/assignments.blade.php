{{-- resources/views/admin/scholarship/assignments.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
:root {
    --sch-primary: #1e3a5f;
    --sch-accent: #2563eb;
    --sch-border: #e2e8f0;
    --sch-radius: 12px;
}

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.status-active   { background: #dcfce7; color: #16a34a; }
.status-pending  { background: #fef3c7; color: #d97706; }
.status-approved { background: #dbeafe; color: #2563eb; }
.status-expired  { background: #fee2e2; color: #dc2626; }
.status-revoked  { background: #f3f4f6; color: #6b7280; }

/* Student avatar styles */
.student-avatar-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}
.student-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
}

/* Modal styles */
.assign-modal .modal-dialog { max-width: 680px; }
.assign-modal .modal-content { border-radius: 16px; overflow: hidden; }

.modal-header-custom {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    padding: 20px 24px;
    position: relative;
}
.modal-header-custom h5 { color: white; margin: 0; font-weight: 600; }
.modal-header-custom p { color: rgba(255,255,255,.7); margin: 4px 0 0; font-size: 13px; }

.sch-card-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    max-height: 320px;
    overflow-y: auto;
    padding: 4px;
}
.sch-card {
    border: 2px solid var(--sch-border);
    border-radius: 12px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    background: white;
}
.sch-card:hover { border-color: #93c5fd; background: #eff6ff; }
.sch-card.selected { border-color: #2563eb; background: #eff6ff; }
.sch-card .sch-check {
    position: absolute; top: -8px; right: -8px;
    width: 22px; height: 22px; background: #2563eb; border-radius: 50%;
    display: none; align-items: center; justify-content: center; color: white; font-size: 12px;
}
.sch-card.selected .sch-check { display: flex; }
.sch-card-title { font-size: 14px; font-weight: 600; color: #1e3a5f; margin-bottom: 4px; }
.sch-card-meta { font-size: 11px; color: #6b7280; }
.sch-card-value {
    margin-top: 8px; padding: 6px 10px; background: #f1f5f9; border-radius: 8px;
    font-size: 12px; font-weight: 600; color: #2563eb;
}

/* Student grid styles */
.student-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    max-height: 320px;
    overflow-y: auto;
    padding: 4px;
}

@media (min-width: 768px) {
    .student-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.student-card {
    border: 2px solid var(--sch-border);
    border-radius: 12px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: white;
    position: relative;
}
.student-card:hover { border-color: #93c5fd; background: #f0f9ff; }
.student-card.selected { border-color: #2563eb; background: #eff6ff; }
.student-card .s-check {
    position: absolute; top: -8px; right: -8px;
    width: 22px; height: 22px; background: #2563eb; border-radius: 50%;
    display: none; align-items: center; justify-content: center; color: white; font-size: 11px;
}
.student-card.selected .s-check { display: flex; }
.s-name { font-size: 13px; font-weight: 600; color: #1e3a5f; }
.s-no { font-size: 11px; color: #6b7280; }
.s-class {
    font-size: 11px;
    color: #6b7280;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.s-class i {
    font-size: 10px;
}

.summary-card {
    background: #f8fafc; border: 1px solid var(--sch-border);
    border-radius: 12px; padding: 16px;
}
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--sch-border); }
.summary-row:last-child { border-bottom: none; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold" style="color: var(--sch-primary)">
                        <i class="ri-user-star-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.scholarship.index') }}">Scholarships</a></li>
                            <li class="breadcrumb-item active">Assignments</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('admin.scholarship.index') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Back to Scholarships
                </a>
            </div>
        </div>
    </div>

    {{-- Status Tabs --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.scholarship.assignments') }}">
                All <span class="badge bg-secondary ms-1">{{ array_sum($statusCounts) }}</span>
            </a>
        </li>
        @foreach(['active'=>'success', 'pending'=>'warning', 'approved'=>'info', 'expired'=>'secondary', 'revoked'=>'danger'] as $s => $c)
        <li class="nav-item">
            <a class="nav-link {{ request('status') == $s ? 'active' : '' }}"
               href="{{ route('admin.scholarship.assignments', ['status' => $s]) }}">
                {{ ucfirst($s) }} <span class="badge bg-{{ $c }} ms-1">{{ $statusCounts[$s] ?? 0 }}</span>
            </a>
        </li>
        @endforeach
    </ul>

    {{-- Search and Assign Button --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="search-box">
                        <input type="text" class="form-control" id="searchInput"
                               placeholder="Search by student name or admission number...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success" id="openAssignBtn">
                        <i class="ri-add-line me-1"></i>Assign Scholarship
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Assignments Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="ri-list-check me-2"></i>Scholarship Assignments</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="assignmentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Scholarship</th>
                            <th>Student</th>
                            <th>Admission No</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Effective Period</th>
                            <th>Assigned By</th>
                            <th>Date</th>
                            <th width="80">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $index => $assignment)
                            @php
                                $student = $assignment->student;
                                $picture = $student->picture ?? null;
                                $avatarUrl = null;

                                if ($picture && $picture->picture) {
                                    $avatarUrl = asset('storage/student_avatars/' . $picture->picture);
                                }
                                $initials = strtoupper(substr($student->firstname ?? '?', 0, 1) . substr($student->lastname ?? '', 0, 1));
                            @endphp
                            <tr>
                                <td>{{ $assignments->firstItem() + $index }}</td>
                                <td>{{ $assignment->scholarship->title ?? 'N/A' }}</td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="Student"
                                                 class="student-avatar-sm"
                                                 style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                                        @else
                                            <div class="student-avatar-placeholder" style="width: 35px; height: 35px; font-size: 14px;">
                                                {{ $initials }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $student->firstname ?? '' }} {{ $student->lastname ?? '' }}</div>
                                            <div class="small text-muted">{{ $student->admissionNo ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $student->admissionNo ?? 'N/A' }}</td>
                                <td>
                                    @if($assignment->value_type == 'percentage')
                                        <span class="badge bg-info">{{ $assignment->value }}%</span>
                                    @else
                                        <span class="badge bg-success">₦{{ number_format($assignment->value, 2) }}</span>
                                    @endif
                                </td>
                                <td><span class="status-badge status-{{ $assignment->status }}">{{ ucfirst($assignment->status) }}</span></td>
                                <td>
                                    <small>
                                        {{ \Carbon\Carbon::parse($assignment->effective_from)->format('d M Y') }}
                                        → {{ $assignment->effective_to ? \Carbon\Carbon::parse($assignment->effective_to)->format('d M Y') : 'Ongoing' }}
                                    </small>
                                </td>
                                <td>{{ $assignment->assignedBy->name ?? 'System' }}</td>
                                <td>{{ $assignment->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($assignment->status == 'active')
                                        <button class="btn btn-sm btn-danger revoke-btn" data-id="{{ $assignment->id }}" title="Revoke">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                                    No scholarship assignments found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($assignments->hasPages())
            <div class="card-footer bg-white">{{ $assignments->links() }}</div>
        @endif
    </div>

</div>
</div>
</div>

{{-- Assign Scholarship Modal --}}
<div class="modal fade assign-modal" id="assignModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5><i class="ri-graduation-cap-line me-2"></i>Assign Scholarship</h5>
                <p>Select a scholarship, choose a student, and set the effective dates.</p>
                <button type="button" class="btn-close btn-close-white position-absolute" style="top: 16px; right: 20px;" data-bs-dismiss="modal"></button>
            </div>

            {{-- Step Indicator --}}
            <div class="d-flex border-bottom bg-light">
                <div class="flex-fill text-center py-3 step-tab active" data-step="1">
                    <span class="badge bg-primary rounded-circle me-1">1</span> Scholarship
                </div>
                <div class="flex-fill text-center py-3 step-tab" data-step="2">
                    <span class="badge bg-secondary rounded-circle me-1">2</span> Student
                </div>
                <div class="flex-fill text-center py-3 step-tab" data-step="3">
                    <span class="badge bg-secondary rounded-circle me-1">3</span> Confirm
                </div>
            </div>

            {{-- Step 1: Select Scholarship --}}
            <div class="modal-body step-content" id="step1Content">
                <p class="text-muted small mb-3">Select the scholarship to assign:</p>

                @if(($scholarships ?? collect())->isEmpty())
                    <div class="text-center py-4">
                        <i class="ri-graduation-cap-line ri-2x d-block mb-2 text-muted"></i>
                        <p class="text-muted">No scholarships available.</p>
                        <a href="{{ route('admin.scholarship.create') }}" class="btn btn-sm btn-primary">
                            <i class="ri-add-line me-1"></i>Create Scholarship
                        </a>
                    </div>
                @else
                    <div class="sch-card-grid" id="scholarshipGrid">
                        @foreach($scholarships as $sch)
                            <div class="sch-card" data-id="{{ $sch->id }}"
                                 data-title="{{ $sch->title }}"
                                 data-no="{{ $sch->scholarship_no }}"
                                 data-vtype="{{ $sch->value_type }}"
                                 data-value="{{ $sch->value }}"
                                 data-status="{{ $sch->status }}">
                                <div class="sch-check"><i class="ri-check-line"></i></div>
                                <div class="sch-card-title">{{ $sch->title }}</div>
                                <div class="sch-card-meta">{{ $sch->scholarship_no }} · {{ $sch->type->name ?? 'N/A' }}</div>
                                <div class="sch-card-value">
                                    @if($sch->value_type == 'percentage')
                                        <i class="ri-percent-line me-1"></i>{{ $sch->value }}% discount
                                    @else
                                        <i class="ri-money-naira-circle-line me-1"></i>₦{{ number_format($sch->value, 2) }}
                                    @endif
                                </div>
                                <span class="badge bg-secondary position-absolute top-0 end-0 mt-2 me-2">{{ ucfirst($sch->status) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Step 2: Select Student --}}
            <div class="modal-body step-content" id="step2Content" style="display: none;">
                <div class="mb-3">
                    <div class="search-box">
                        <input type="text" id="studentSearch" class="form-control" placeholder="Search by name or admission number...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div id="studentsList" class="student-grid">
                    <div class="text-center py-4 text-muted" style="grid-column: 1/-1;">
                        <i class="ri-user-search-line ri-2x d-block mb-2"></i>
                        Search to find eligible students
                    </div>
                </div>
            </div>

            {{-- Step 3: Confirm Details --}}
            <div class="modal-body step-content" id="step3Content" style="display: none;">
                <div id="selectedInfo" class="mb-4">
                    <div class="alert alert-info" id="selectionSummary">
                        <i class="ri-information-line me-2"></i>No scholarship selected yet.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                        <input type="date" id="effectiveFrom" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Effective To</label>
                        <input type="date" id="effectiveTo" class="form-control">
                        <small class="text-muted">Leave empty for ongoing</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Reason (Optional)</label>
                        <input type="text" id="assignReason" class="form-control" placeholder="e.g., Merit-based award">
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="prevBtn" style="display: none;">
                    <i class="ri-arrow-left-line me-1"></i>Previous
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn">Next <i class="ri-arrow-right-line ms-1"></i></button>
                <button type="button" class="btn btn-success" id="submitBtn" style="display: none;">
                    <i class="ri-check-line me-1"></i>Assign Scholarship
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Revoke Modal --}}
<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-close-circle-line me-2"></i>Revoke Assignment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to revoke this scholarship assignment?</p>
                <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                <textarea id="revokeReason" class="form-control" rows="3" placeholder="Please provide a reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRevokeBtn">Revoke</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

let currentStep = 1;
let selectedScholarship = null;
let selectedStudent = null;
let revokeId = null;

$(document).ready(function() {
    // Modal elements
    const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));

    // Open modal
    $('#openAssignBtn').on('click', function() {
        resetModal();
        assignModal.show();
    });

    // Scholarship selection
    $(document).on('click', '.sch-card', function() {
        $('.sch-card').removeClass('selected');
        $(this).addClass('selected');

        selectedScholarship = {
            id: $(this).data('id'),
            title: $(this).data('title'),
            no: $(this).data('no'),
            value_type: $(this).data('vtype'),
            value: $(this).data('value'),
            status: $(this).data('status')
        };

        $('#nextBtn').prop('disabled', false);
    });

    // Step navigation
    function goToStep(step) {
        currentStep = step;

        $('.step-content').hide();
        $(`#step${step}Content`).show();

        $('.step-tab').removeClass('active');
        $(`.step-tab[data-step="${step}"]`).addClass('active');

        if (step === 1) {
            $('#prevBtn').hide();
            $('#nextBtn').show();
            $('#submitBtn').hide();
            $('#nextBtn').prop('disabled', !selectedScholarship);
        } else if (step === 2) {
            $('#prevBtn').show();
            $('#nextBtn').show();
            $('#submitBtn').hide();
            $('#nextBtn').prop('disabled', !selectedStudent);
            if (selectedScholarship) {
                loadEligibleStudents();
            }
        } else if (step === 3) {
            $('#prevBtn').show();
            $('#nextBtn').hide();
            $('#submitBtn').show();
            updateConfirmationSummary();
        }
    }

    $('#nextBtn').on('click', function() {
        if (currentStep < 3) {
            goToStep(currentStep + 1);
        }
    });

    $('#prevBtn').on('click', function() {
        if (currentStep > 1) {
            goToStep(currentStep - 1);
        }
    });

    function resetModal() {
        currentStep = 1;
        selectedScholarship = null;
        selectedStudent = null;
        $('.sch-card').removeClass('selected');
        $('#studentsList').html(`
            <div class="text-center py-4 text-muted" style="grid-column: 1/-1;">
                <i class="ri-user-search-line ri-2x d-block mb-2"></i>
                Search to find eligible students
            </div>
        `);
        $('#studentSearch').val('');
        $('#effectiveFrom').val('{{ date("Y-m-d") }}');
        $('#effectiveTo').val('');
        $('#assignReason').val('');
        goToStep(1);
    }

    async function loadEligibleStudents() {
        if (!selectedScholarship) return;

        $('#studentsList').html(`
            <div class="text-center py-4" style="grid-column: 1/-1;">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 text-muted">Loading students...</p>
            </div>
        `);

        try {
            const response = await fetch(`{{ route("admin.scholarship.eligible-students") }}?scholarship_id=${selectedScholarship.id}`);
            const data = await response.json();

            if (data.success && data.students && data.students.length > 0) {
                renderStudents(data.students);
            } else {
                $('#studentsList').html(`
                    <div class="text-center py-4 text-muted" style="grid-column: 1/-1;">
                        <i class="ri-user-unfollow-line ri-2x d-block mb-2"></i>
                        No eligible students found.
                    </div>
                `);
            }
        } catch (error) {
            console.error('Error loading students:', error);
            $('#studentsList').html(`
                <div class="text-center py-4 text-danger" style="grid-column: 1/-1;">
                    <i class="ri-error-warning-line ri-2x d-block mb-2"></i>
                    Failed to load students. Please try again.
                </div>
            `);
        }
    }

    function renderStudents(students) {
        const container = $('#studentsList');
        container.empty();

        students.forEach(student => {
            const initials = (student.firstname?.charAt(0) || '?') + (student.lastname?.charAt(0) || '?');
            const avatarUrl = student.avatar || null;

            container.append(`
                <div class="student-card" data-id="${student.id}"
                     data-name="${student.firstname} ${student.lastname}"
                     data-admission="${student.admissionNo}"
                     data-firstname="${student.firstname}"
                     data-lastname="${student.lastname}"
                     data-avatar="${avatarUrl || ''}"
                     data-current-class="${student.current_class || 'Not Assigned'}">
                    <div class="s-check"><i class="ri-check-line"></i></div>
                    <div class="student-avatar">
                        ${avatarUrl ?
                            `<img src="${avatarUrl}" alt="Student" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;"
                                  onerror="this.onerror=null; this.src=''; this.style.display='none'; this.nextElementSibling.style.display='flex';">` :
                            ''
                        }
                        <div class="student-avatar-placeholder" style="width: 44px; height: 44px; font-size: 18px; display: ${avatarUrl ? 'none' : 'flex'};">
                            ${initials.toUpperCase()}
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <div class="s-name">${escapeHtml(student.firstname)} ${escapeHtml(student.lastname)}</div>
                        <div class="s-no">${escapeHtml(student.admissionNo)}</div>
                        <div class="s-class">
                            <i class="ri-book-open-line"></i>
                            ${escapeHtml(student.current_class || 'Not Assigned')}
                        </div>
                    </div>
                </div>
            `);
        });

        // Bind click events
        $('.student-card').off('click').on('click', function() {
            $('.student-card').removeClass('selected');
            $(this).addClass('selected');

            selectedStudent = {
                id: $(this).data('id'),
                name: $(this).data('name'),
                admission: $(this).data('admission'),
                firstname: $(this).data('firstname'),
                lastname: $(this).data('lastname'),
                avatar: $(this).data('avatar'),
                currentClass: $(this).data('current-class')
            };

            $('#nextBtn').prop('disabled', false);
        });
    }

    // Helper function to escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    $('#studentSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.student-card').each(function() {
            const name = $(this).data('name')?.toLowerCase() || '';
            const admission = $(this).data('admission')?.toLowerCase() || '';
            $(this).toggle(name.includes(searchTerm) || admission.includes(searchTerm));
        });
    });

    function updateConfirmationSummary() {
        // Get avatar HTML for selected student
        let avatarHtml = '';
        if (selectedStudent.avatar) {
            avatarHtml = `<img src="${selectedStudent.avatar}" alt="Student" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #2563eb;">`;
        } else {
            const initials = (selectedStudent.firstname?.charAt(0) || '?') + (selectedStudent.lastname?.charAt(0) || '?');
            avatarHtml = `<div class="student-avatar-placeholder" style="width: 60px; height: 60px; font-size: 24px; margin: 0 auto;">${initials.toUpperCase()}</div>`;
        }

        let html = `
            <div class="summary-card">
                <div class="text-center mb-3">
                    ${avatarHtml}
                </div>
                <div class="summary-row"><span class="s-key">Scholarship:</span><span class="s-val fw-bold">${escapeHtml(selectedScholarship.title)}</span></div>
                <div class="summary-row"><span class="s-key">Reference No.:</span><span class="s-val"><code>${escapeHtml(selectedScholarship.no)}</code></span></div>
                <div class="summary-row"><span class="s-key">Student:</span><span class="s-val">${escapeHtml(selectedStudent.name)} (${escapeHtml(selectedStudent.admission)})</span></div>
                <div class="summary-row"><span class="s-key">Current Class:</span><span class="s-val"><i class="ri-book-open-line me-1"></i>${escapeHtml(selectedStudent.currentClass || 'Not Assigned')}</span></div>
                <div class="summary-row"><span class="s-key">Value:</span><span class="s-val text-primary">
                    ${selectedScholarship.value_type == 'percentage' ? selectedScholarship.value + '%' : '₦' + parseFloat(selectedScholarship.value).toLocaleString()}
                </span></div>
            </div>
        `;
        $('#selectionSummary').html(html);
    }

    // Submit assignment
    $('#submitBtn').on('click', async function() {
        if (!selectedScholarship || !selectedStudent) {
            Swal.fire('Error', 'Please select both scholarship and student', 'error');
            return;
        }

        const effectiveFrom = $('#effectiveFrom').val();
        if (!effectiveFrom) {
            Swal.fire('Error', 'Please select effective from date', 'error');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Assigning...');

        try {
            const formData = new URLSearchParams();
            formData.append('scholarship_id', selectedScholarship.id);
            formData.append('student_id', selectedStudent.id);
            formData.append('effective_from', effectiveFrom);
            formData.append('effective_to', $('#effectiveTo').val());
            formData.append('reason', $('#assignReason').val());
            formData.append('_token', CSRF_TOKEN);

            const response = await fetch('{{ route("admin.scholarship.assign") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('Success!', data.message, 'success').then(() => {
                    assignModal.hide();
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to assign scholarship', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        } finally {
            btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i>Assign Scholarship');
        }
    });

    // Revoke functionality
    $('.revoke-btn').on('click', function() {
        revokeId = $(this).data('id');
        $('#revokeReason').val('');
        new bootstrap.Modal(document.getElementById('revokeModal')).show();
    });

    $('#confirmRevokeBtn').on('click', async function() {
        const reason = $('#revokeReason').val();
        if (!reason) {
            Swal.fire('Error', 'Please provide a reason for revocation', 'error');
            return;
        }

        try {
            const response = await fetch(`/admin/scholarship/assignment/${revokeId}/revoke`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ reason: reason })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('Revoked!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        }
    });

    // Table search
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#assignmentsTable tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });
});
</script>
@endsection
