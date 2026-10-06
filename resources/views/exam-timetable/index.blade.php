{{-- resources/views/exam-timetable/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">
                            <i class="ri-exam-line me-2"></i>{{ $pagetitle }}
                        </h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Exam Timetable</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @can('Create exam timetable')
            <div class="row mb-3">
                <div class="col-lg-12">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createExamTimetableModal">
                        <i class="ri-add-line me-2"></i>Create New Exam Timetable
                    </button>
                </div>
            </div>
            @endcan

            {{-- Exam Timetables List --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Exam Timetables</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Session</th>
                                            <th>Term</th>
                                            <th>Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($examTimetables as $timetable)
                                        <tr>
                                            <td class="fw-medium">{{ $timetable->name }}</td>
                                            <td>{{ $timetable->session->session ?? 'N/A' }}</td>
                                            <td>{{ $timetable->term->term ?? 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $typeColors = [
                                                        'mid_term' => 'info',
                                                        'end_of_term' => 'primary',
                                                        'mock' => 'warning',
                                                        'entrance' => 'danger',
                                                        'other' => 'secondary'
                                                    ];
                                                @endphp
                                                <span class="badge bg-{{ $typeColors[$timetable->exam_type] ?? 'secondary' }}-subtle text-{{ $typeColors[$timetable->exam_type] ?? 'secondary' }}">
                                                    {{ str_replace('_', ' ', ucfirst($timetable->exam_type)) }}
                                                </span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($timetable->start_date)->format('d M Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($timetable->end_date)->format('d M Y') }}</td>
                                            <td>
                                                @if($timetable->status == 'published')
                                                    <span class="badge bg-success">Published</span>
                                                @elseif($timetable->status == 'draft')
                                                    <span class="badge bg-warning">Draft</span>
                                                @else
                                                    <span class="badge bg-secondary">Archived</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewExamTimetable({{ $timetable->id }})" title="View Details">
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                                @can('Edit exam timetable')
                                                <button class="btn btn-sm btn-outline-primary" onclick="editExamTimetable({{ $timetable->id }})" title="Edit">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="manageSlots({{ $timetable->id }})" title="Manage Slots">
                                                    <i class="ri-calendar-line"></i>
                                                </button>
                                                @endcan
                                                @can('Delete exam timetable')
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteExamTimetable({{ $timetable->id }})" title="Delete">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                                @endcan
                                                <button class="btn btn-sm btn-outline-secondary" onclick="exportExamTimetable({{ $timetable->id }})" title="Export CSV">
                                                    <i class="ri-download-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                {{ $examTimetables->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create/Edit Exam Timetable Modal --}}
<div class="modal fade" id="examTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white" id="examTimetableModalTitle">Create Exam Timetable</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="examTimetableForm">
                    <input type="hidden" id="examTimetableId">
                    <div class="mb-3">
                        <label class="form-label">Timetable Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="examTimetableName" required placeholder="e.g., 2024 End of Term Exams">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Session <span class="text-danger">*</span></label>
                            <select class="form-select" id="examTimetableSessionId" required>
                                <option value="">Select Session</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Term <span class="text-danger">*</span></label>
                            <select class="form-select" id="examTimetableTermId" required>
                                <option value="">Select Term</option>
                                @foreach($terms as $term)
                                    <option value="{{ $term->id }}">{{ $term->term }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Exam Type</label>
                            <select class="form-select" id="examTimetableType">
                                <option value="mid_term">Mid Term</option>
                                <option value="end_of_term">End of Term</option>
                                <option value="mock">Mock Exam</option>
                                <option value="entrance">Entrance Exam</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="examTimetableStartDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="examTimetableEndDate" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea class="form-control" id="examTimetableInstructions" rows="3" placeholder="Exam instructions for students..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveExamTimetable()">Save Timetable</button>
            </div>
        </div>
    </div>
</div>

{{-- Manage Slots Modal --}}
<div class="modal fade" id="examSlotsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">Manage Exam Slots</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button class="btn btn-primary btn-sm" onclick="showAddSlotForm()">
                            <i class="ri-add-line me-1"></i>Add Exam Slot
                        </button>
                    </div>
                </div>
                <div id="addSlotForm" style="display: none;" class="mb-4 p-3 border rounded">
                    <h6 class="mb-3">Add New Exam Slot</h6>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <select class="form-select" id="slotSubjectId">
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->subject }} ({{ $subject->subject_code ?? '' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select class="form-select" id="slotClassId">
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->armRelation->arm ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="date" class="form-control" id="slotExamDate" placeholder="Exam Date">
                        </div>
                        <div class="col-md-1 mb-2">
                            <input type="time" class="form-control" id="slotStartTime">
                        </div>
                        <div class="col-md-1 mb-2">
                            <input type="time" class="form-control" id="slotEndTime">
                        </div>
                        <div class="col-md-2 mb-2">
                            <select class="form-select" id="slotVenueId">
                                <option value="">Venue</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 mb-2">
                            <input type="number" class="form-control" id="slotTotalMarks" placeholder="Marks" value="100">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <button class="btn btn-success btn-sm" onclick="addExamSlot()">Add Slot</button>
                            <button class="btn btn-secondary btn-sm" onclick="hideAddSlotForm()">Cancel</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="examSlotsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Venue</th>
                                <th>Marks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="examSlotsBody">
                            <tr><td colspan="7" class="text-center text-muted">No exam slots added yet</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" onclick="publishExamTimetable()">
                    <i class="ri-send-plane-line me-1"></i>Publish Timetable
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
    let currentExamTimetableId = null;
    let examSlots = [];

    function saveExamTimetable() {
        const id = document.getElementById('examTimetableId').value;
        const data = {
            name: document.getElementById('examTimetableName').value,
            session_id: document.getElementById('examTimetableSessionId').value,
            term_id: document.getElementById('examTimetableTermId').value,
            exam_type: document.getElementById('examTimetableType').value,
            start_date: document.getElementById('examTimetableStartDate').value,
            end_date: document.getElementById('examTimetableEndDate').value,
            instructions: document.getElementById('examTimetableInstructions').value
        };

        const method = id ? 'PUT' : 'POST';
        const url = id ? `/exam-timetable/${id}` : '/exam-timetable';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Success', 'Exam timetable saved successfully', 'success');
                bootstrap.Modal.getInstance(document.getElementById('examTimetableModal')).hide();
                location.reload();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    function editExamTimetable(id) {
        document.getElementById('examTimetableModalTitle').innerText = 'Edit Exam Timetable';
        document.getElementById('examTimetableId').value = id;

        fetch(`/exam-timetable/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tt = data.exam_timetable;
                    document.getElementById('examTimetableName').value = tt.name;
                    document.getElementById('examTimetableSessionId').value = tt.session_id;
                    document.getElementById('examTimetableTermId').value = tt.term_id;
                    document.getElementById('examTimetableType').value = tt.exam_type;
                    document.getElementById('examTimetableStartDate').value = tt.start_date;
                    document.getElementById('examTimetableEndDate').value = tt.end_date;
                    document.getElementById('examTimetableInstructions').value = tt.instructions || '';

                    new bootstrap.Modal(document.getElementById('examTimetableModal')).show();
                }
            });
    }

    function deleteExamTimetable(id) {
        Swal.fire({
            title: 'Delete Exam Timetable?',
            text: 'This will delete all exam slots as well!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/exam-timetable/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function manageSlots(id) {
        currentExamTimetableId = id;
        loadExamSlots();
        new bootstrap.Modal(document.getElementById('examSlotsModal')).show();
    }

    function loadExamSlots() {
        fetch(`/exam-timetable/${currentExamTimetableId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.exam_timetable.slots) {
                    examSlots = data.exam_timetable.slots;
                    renderExamSlots();
                }
            });
    }

    function renderExamSlots() {
        const tbody = document.getElementById('examSlotsBody');
        if (!examSlots.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No exam slots added yet</td></tr>';
            return;
        }

        tbody.innerHTML = examSlots.map(slot => `
            <tr>
                <td>${slot.exam_date}</td>
                <td>${slot.start_time} - ${slot.end_time}</td>
                <td>${slot.subject?.subject || 'N/A'}</td>
                <td>${slot.class?.schoolclass || 'N/A'}</td>
                <td>${slot.venue?.room_name || 'TBA'}</td>
                <td>${slot.total_marks}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeExamSlot(${slot.id})">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function addExamSlot() {
        const data = {
            subject_id: document.getElementById('slotSubjectId').value,
            class_id: document.getElementById('slotClassId').value,
            exam_date: document.getElementById('slotExamDate').value,
            start_time: document.getElementById('slotStartTime').value,
            end_time: document.getElementById('slotEndTime').value,
            venue_id: document.getElementById('slotVenueId').value || null,
            total_marks: document.getElementById('slotTotalMarks').value
        };

        if (!data.subject_id || !data.class_id || !data.exam_date || !data.start_time || !data.end_time) {
            Swal.fire('Error', 'Please fill all required fields', 'error');
            return;
        }

        fetch(`/exam-timetable/${currentExamTimetableId}/slots`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Success', 'Exam slot added successfully', 'success');
                loadExamSlots();
                hideAddSlotForm();
                document.getElementById('slotSubjectId').value = '';
                document.getElementById('slotClassId').value = '';
                document.getElementById('slotExamDate').value = '';
                document.getElementById('slotStartTime').value = '';
                document.getElementById('slotEndTime').value = '';
                document.getElementById('slotVenueId').value = '';
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    function removeExamSlot(slotId) {
        Swal.fire({
            title: 'Remove Exam Slot?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/exam-timetable/slots/${slotId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Removed!', data.message, 'success');
                        loadExamSlots();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function publishExamTimetable() {
        Swal.fire({
            title: 'Publish Exam Timetable?',
            text: 'This will send notifications to all teachers and students.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, publish it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/exam-timetable/${currentExamTimetableId}/publish`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Published!', data.message, 'success');
                        bootstrap.Modal.getInstance(document.getElementById('examSlotsModal')).hide();
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    function exportExamTimetable(id) {
        window.location.href = `/exam-timetable/${id}/export`;
    }

    function showAddSlotForm() {
        document.getElementById('addSlotForm').style.display = 'block';
    }

    function hideAddSlotForm() {
        document.getElementById('addSlotForm').style.display = 'none';
    }

    function viewExamTimetable(id) {
        fetch(`/exam-timetable/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tt = data.exam_timetable;
                    let slotsHtml = '';
                    if (tt.slots && tt.slots.length) {
                        slotsHtml = '<table class="table table-sm"><thead><tr><th>Date</th><th>Time</th><th>Subject</th><th>Class</th><th>Venue</th></tr></thead><tbody>';
                        tt.slots.forEach(slot => {
                            slotsHtml += `<tr>
                                <td>${slot.exam_date}</td>
                                <td>${slot.start_time} - ${slot.end_time}</td>
                                <td>${slot.subject?.subject || 'N/A'}</td>
                                <td>${slot.class?.schoolclass || 'N/A'}</td>
                                <td>${slot.venue?.room_name || 'TBA'}</td>
                            </tr>`;
                        });
                        slotsHtml += '</tbody></table>';
                    } else {
                        slotsHtml = '<p class="text-muted">No exam slots added yet.</p>';
                    }

                    Swal.fire({
                        title: tt.name,
                        html: `
                            <div class="text-start">
                                <p><strong>Session:</strong> ${tt.session?.session || 'N/A'}</p>
                                <p><strong>Term:</strong> ${tt.term?.term || 'N/A'}</p>
                                <p><strong>Type:</strong> ${tt.exam_type}</p>
                                <p><strong>Period:</strong> ${tt.start_date} to ${tt.end_date}</p>
                                <hr>
                                <h6>Exam Slots:</h6>
                                ${slotsHtml}
                                ${tt.instructions ? `<hr><p><strong>Instructions:</strong><br>${tt.instructions}</p>` : ''}
                            </div>
                        `,
                        width: 800,
                        icon: 'info'
                    });
                }
            });
    }
</script>

@endsection
