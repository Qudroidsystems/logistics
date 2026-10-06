@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">{{ $pagetitle ?? 'Holiday Management' }}</h4>
                <div class="page-title-right">
                    @can('Create holidays')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                        <i class="ri-add-line"></i> Add Holiday
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Holidays Card -->
    @if($upcomingHolidays->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-calendar-event-line me-2"></i>Upcoming Holidays</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($upcomingHolidays as $holiday)
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">{{ Carbon\Carbon::parse($holiday->date)->format('D, M d') }}</h6>
                                    <h5 class="card-title">{{ $holiday->title }}</h5>
                                    <span class="badge bg-{{ $holiday->is_full_day ? 'danger' : 'warning' }}">
                                        {{ $holiday->is_full_day ? 'Full Day' : 'Half Day' }}
                                    </span>
                                    @if($holiday->session)
                                    <p class="text-muted small mt-2">{{ $holiday->session->session }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Holidays Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-hover datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Cut-off Time</th>
                                <th>Session</th>
                                <th>Term</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $index => $holiday)
                            <tr>
                                <td>{{ $holidays->firstItem() + $index }}</td>
                                <td>{{ Carbon\Carbon::parse($holiday->date)->format('d M Y') }}</td>
                                <td><strong>{{ $holiday->title }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $holiday->is_full_day ? 'danger' : 'warning' }}">
                                        {{ $holiday->is_full_day ? 'Full Day' : 'Half Day' }}
                                    </span>
                                </td>
                                <td>{{ $holiday->cutoff_time ? Carbon\Carbon::parse($holiday->cutoff_time)->format('H:i') : 'N/A' }}</td>
                                <td>{{ $holiday->session?->session ?? 'All Sessions' }}</td>
                                <td>{{ $holiday->term?->term ?? 'All Terms' }}</td>
                                <td>{{ $holiday->creator?->name ?? 'System' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @can('Edit holidays')
                                        <button type="button" class="btn btn-info edit-holiday" 
                                                data-id="{{ $holiday->id }}"
                                                data-date="{{ $holiday->date }}"
                                                data-title="{{ $holiday->title }}"
                                                data-is_full_day="{{ $holiday->is_full_day ? '1' : '0' }}"
                                                data-cutoff_time="{{ $holiday->cutoff_time }}"
                                                data-session_id="{{ $holiday->session_id }}"
                                                data-term_id="{{ $holiday->term_id }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editHolidayModal">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        @endcan
                                        @can('Delete holidays')
                                        <button type="button" class="btn btn-danger delete-holiday" 
                                                data-id="{{ $holiday->id }}"
                                                data-title="{{ $holiday->title }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">No holidays found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    
                    <div class="d-flex justify-content-center">
                        {{ $holidays->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-add-line me-2"></i>Add New Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addHolidayForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Christmas Day" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_full_day" name="is_full_day" value="1" checked>
                            <label class="form-check-label" for="is_full_day">Full Day Holiday</label>
                        </div>
                    </div>
                    <div class="mb-3" id="cutoff_time_group">
                        <label for="cutoff_time" class="form-label">Cut-off Time (for half-day)</label>
                        <input type="time" class="form-control" id="cutoff_time" name="cutoff_time">
                        <small class="text-muted">Classes after this time will be canceled</small>
                    </div>
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session (Optional)</label>
                        <select class="form-select" id="session_id" name="session_id">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="term_id" class="form-label">Term (Optional)</label>
                        <select class="form-select" id="term_id" name="term_id">
                            <option value="">All Terms</option>
                            @foreach(\App\Models\Schoolterm::orderBy('id', 'desc')->get() as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="affects_timetable" name="affects_timetable" value="1" checked>
                            <label class="form-check-label" for="affects_timetable">Affects Timetable</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveHolidayBtn">Save Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Holiday Modal -->
<div class="modal fade" id="editHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-edit-line me-2"></i>Edit Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editHolidayForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_holiday_id" name="holiday_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_is_full_day" name="is_full_day" value="1">
                            <label class="form-check-label" for="edit_is_full_day">Full Day Holiday</label>
                        </div>
                    </div>
                    <div class="mb-3" id="edit_cutoff_time_group">
                        <label for="edit_cutoff_time" class="form-label">Cut-off Time (for half-day)</label>
                        <input type="time" class="form-control" id="edit_cutoff_time" name="cutoff_time">
                        <small class="text-muted">Classes after this time will be canceled</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_session_id" class="form-label">Session (Optional)</label>
                        <select class="form-select" id="edit_session_id" name="session_id">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_term_id" class="form-label">Term (Optional)</label>
                        <select class="form-select" id="edit_term_id" name="term_id">
                            <option value="">All Terms</option>
                            @foreach(\App\Models\Schoolterm::orderBy('id', 'desc')->get() as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_affects_timetable" name="affects_timetable" value="1" checked>
                            <label class="form-check-label" for="edit_affects_timetable">Affects Timetable</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateHolidayBtn">Update Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle cut-off time visibility based on full day checkbox
    $('#is_full_day, #edit_is_full_day').on('change', function() {
        const isFullDay = $(this).is(':checked');
        const groupId = $(this).attr('id') === 'is_full_day' ? 'cutoff_time_group' : 'edit_cutoff_time_group';
        $('#' + groupId).toggle(!isFullDay);
    });

    // Add Holiday
    $('#addHolidayForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Convert checkbox values to boolean
        data.is_full_day = data.is_full_day ? true : false;
        data.affects_timetable = data.affects_timetable ? true : false;

        $('#saveHolidayBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: '{{ route("holidays.store") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
            },
            complete: function() {
                $('#saveHolidayBtn').prop('disabled', false).html('Save Holiday');
            }
        });
    });

    // Edit Holiday - Load data into modal
    $('.edit-holiday').on('click', function() {
        const id = $(this).data('id');
        const date = $(this).data('date');
        const title = $(this).data('title');
        const isFullDay = $(this).data('is_full_day') == 1;
        const cutoffTime = $(this).data('cutoff_time');
        const sessionId = $(this).data('session_id');
        const termId = $(this).data('term_id');

        $('#edit_holiday_id').val(id);
        $('#edit_date').val(date);
        $('#edit_title').val(title);
        $('#edit_is_full_day').prop('checked', isFullDay);
        $('#edit_cutoff_time').val(cutoffTime || '');
        $('#edit_session_id').val(sessionId || '');
        $('#edit_term_id').val(termId || '');
        
        // Show/hide cutoff time
        $('#edit_cutoff_time_group').toggle(!isFullDay);
    });

    // Update Holiday
    $('#editHolidayForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#edit_holiday_id').val();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        data.is_full_day = data.is_full_day ? true : false;
        data.affects_timetable = data.affects_timetable ? true : false;

        $('#updateHolidayBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');

        $.ajax({
            url: `/holidays/${id}`,
            method: 'POST',
            data: {
                ...data,
                _method: 'PUT'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
            },
            complete: function() {
                $('#updateHolidayBtn').prop('disabled', false).html('Update Holiday');
            }
        });
    });

    // Delete Holiday
    $('.delete-holiday').on('click', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');

        Swal.fire({
            title: 'Delete Holiday',
            text: `Are you sure you want to delete "${title}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/holidays/${id}`,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: response.message,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'An error occurred'
                        });
                    }
                });
            }
        });
    });

    // Auto-dismiss modal on close
    $('#addHolidayModal, #editHolidayModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
    });
});
</script>
@endpush