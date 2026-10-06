<!-- Mass Student Account Management Modal -->
<div id="massStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">
                    <i class="bi bi-people-fill me-2"></i> Mass Student Account Management
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">

                <!-- ==================== STEP 1: SELECT STUDENTS ==================== -->
                <div id="massStep1">
                    <!-- Filter Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> Filter Students</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Search</label>
                                    <input type="text" id="massStudentSearch" class="form-control" placeholder="Name, Admission No...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Class</label>
                                    <select id="massClassFilter" class="form-select">
                                        <option value="">All Classes</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Arm</label>
                                    <select id="massArmFilter" class="form-select">
                                        <option value="">All Arms</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Account Status</label>
                                    <select id="massAccountStatus" class="form-select">
                                        <option value="all">All Students</option>
                                        <option value="no">No Account Only</option>
                                        <option value="yes">Has Account Only</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Email Format:</strong> Student emails are automatically generated as <code>firstname.lastname@csskabba.ng</code> with all special characters removed.
                    </div>

                    <!-- Student Selection Table -->
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-person-lines-fill me-1"></i> Select Students</h6>
                            <div>
                                <span class="badge bg-primary" id="massSelectedCount">0 selected</span>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="selectAllStudents">
                                    <i class="bi bi-check-all"></i> Select All
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAllCheckbox">
                                            </th>
                                            <th>Admission No</th>
                                            <th>Student Name</th>
                                            <th>Class/Arm</th>
                                            <th>Status</th>
                                            <th>Generated Email</th>
                                        </tr>
                                    </thead>
                                    <tbody id="massStudentList">
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="spinner-border spinner-border-sm me-2"></div>
                                                Loading students...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Action Cards Preview -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-secondary">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                <strong>Available Actions:</strong>
                                <ul class="mb-0 mt-1">
                                    <li><strong>Create Accounts</strong> - Creates new user accounts for students WITHOUT accounts (auto-generates email: firstname.lastname@csskabba.ng)</li>
                                    <li><strong>Reset Passwords</strong> - Generates new passwords for students WITH accounts</li>
                                    <li><strong>Revoke Accounts</strong> - Removes user access (student record remains)</li>
                                    <li><strong>Reprint Credentials</strong> - Shows existing credentials (passwords hidden for security)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-primary btn-lg" id="proceedToAction">
                            Continue to Action <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- ==================== STEP 2: CONFIGURE ACTION ==================== -->
                <div id="massStep2" style="display: none;">
                    <!-- Selected Students Summary -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-check-square-fill me-1"></i> Selected Students (<span id="step2SelectedCount">0</span>)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 200px;">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Admission No</th>
                                            <th>Current Status</th>
                                            <th>Generated Email</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedStudentsList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Action Type Selection Cards -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-lightning-charge-fill me-1"></i> Choose Action</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="card action-card" data-action="create" style="cursor: pointer; border: 2px solid #e0e0e0;">
                                        <div class="card-body text-center">
                                            <i class="bi bi-person-plus-fill fs-1 text-success"></i>
                                            <h6 class="mt-2 mb-0">Create Accounts</h6>
                                            <small class="text-muted">For students without accounts</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card action-card" data-action="reset" style="cursor: pointer; border: 2px solid #e0e0e0;">
                                        <div class="card-body text-center">
                                            <i class="bi bi-key-fill fs-1 text-warning"></i>
                                            <h6 class="mt-2 mb-0">Reset Passwords</h6>
                                            <small class="text-muted">New password for existing</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card action-card" data-action="revoke" style="cursor: pointer; border: 2px solid #e0e0e0;">
                                        <div class="card-body text-center">
                                            <i class="bi bi-person-x-fill fs-1 text-danger"></i>
                                            <h6 class="mt-2 mb-0">Revoke Accounts</h6>
                                            <small class="text-muted">Remove user access</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card action-card" data-action="reprint" style="cursor: pointer; border: 2px solid #e0e0e0;">
                                        <div class="card-body text-center">
                                            <i class="bi bi-printer-fill fs-1 text-info"></i>
                                            <h6 class="mt-2 mb-0">Reprint Credentials</h6>
                                            <small class="text-muted">Print without password</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="selectedAction" value="">
                        </div>
                    </div>

                    <!-- Password Settings (shown for create/reset) - RADIO BUTTONS -->
                    <div id="passwordSettings" style="display: none;">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-lock-fill me-1"></i> Password Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Password Type</label>
                                        <div class="d-flex gap-4 mt-2">
                                            <div class="form-check">
                                                <input type="radio" id="passwordTypeIndividual" name="passwordTypeRadio" value="individual" class="form-check-input" checked>
                                                <label class="form-check-label" for="passwordTypeIndividual">
                                                    <strong>Individual Random Passwords</strong>
                                                    <br><small class="text-muted">Each student gets a unique random password</small>
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="passwordTypeSame" name="passwordTypeRadio" value="same" class="form-check-input">
                                                <label class="form-check-label" for="passwordTypeSame">
                                                    <strong>Same Password for All</strong>
                                                    <br><small class="text-muted">All selected students get the same password</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-3" id="sharedPasswordContainer" style="display: none;">
                                        <label class="form-label">Shared Password</label>
                                        <input type="text" id="sharedPassword" class="form-control" placeholder="Enter password">
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role Settings - ONLY Student role is enabled -->
                    <div id="roleSettings" style="display: none;">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-tags-fill me-1"></i> Assign Role</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning mb-3">
                                    <i class="bi bi-shield-exclamation me-2"></i>
                                    <strong>Security Notice:</strong> Student accounts can only have the <strong>"Student"</strong> role for security purposes. Administrative roles cannot be assigned to student accounts.
                                </div>

                                <div class="row">
                                    @php
                                        $allRoles = Spatie\Permission\Models\Role::all();
                                        $studentRole = $allRoles->where('name', 'Student')->first();
                                        $otherRoles = $allRoles->where('name', '!=', 'Student');
                                    @endphp

                                    <!-- Student Role (Enabled and Checked - This is the only role that can be assigned) -->
                                    @if($studentRole)
                                        <div class="col-md-12 mb-3">
                                            <div class="card border-success">
                                                <div class="card-body bg-success bg-opacity-10">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input"
                                                               name="roles[]" value="{{ $studentRole->name }}"
                                                               id="role_{{ $studentRole->name }}" checked>
                                                        <label class="form-check-label fw-bold text-success fs-5" for="role_{{ $studentRole->name }}">
                                                            <i class="bi bi-person-badge-fill me-2"></i>
                                                            {{ $studentRole->name }}
                                                            <span class="badge bg-success ms-2">Default Role</span>
                                                        </label>
                                                        <p class="text-muted mt-2 mb-0 ms-4">
                                                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                            This role is automatically assigned to all student accounts.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Other Roles (All Disabled - Cannot be selected) -->
                                    @if($otherRoles->count() > 0)
                                        <div class="col-md-12">
                                            <hr>
                                            <p class="text-muted mb-2"><i class="bi bi-shield-lock-fill me-1"></i> The following roles cannot be assigned to student accounts:</p>
                                            <div class="row">
                                                @foreach($otherRoles as $role)
                                                    <div class="col-md-4 mb-2">
                                                        <div class="card bg-light">
                                                            <div class="card-body py-2">
                                                                <div class="form-check">
                                                                    <input type="checkbox" class="form-check-input"
                                                                           value="{{ $role->name }}"
                                                                           id="role_{{ $role->name }}" disabled>
                                                                    <label class="form-check-label text-muted" for="role_{{ $role->name }}">
                                                                        <i class="bi bi-shield-lock-fill me-1"></i>
                                                                        {{ $role->name }}
                                                                        <span class="badge bg-secondary ms-1">Disabled</span>
                                                                    </label>
                                                                </div>
                                                                <small class="text-danger d-block mt-1">
                                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                                    Cannot assign {{ $role->name }} role to student account
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warning Message -->
                    <div id="actionWarning" class="alert alert-warning" style="display: none;"></div>

                    <!-- Email Format Note -->
                    <div class="alert alert-info" id="emailFormatNote" style="display: none;">
                        <i class="bi bi-envelope-fill me-2"></i>
                        <strong>Email Format:</strong> Student emails will be generated as <code>firstname.lastname@csskabba.ng</code> with all special characters removed.
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" id="backToStep1">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="executeAction">
                            <i class="bi bi-check-circle"></i> Execute Action
                        </button>
                    </div>
                </div>

                <!-- ==================== STEP 3: RESULTS ==================== -->
                <div id="massStep3" style="display: none;">
                    <div id="resultsContainer"></div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary" id="newAction">
                            <i class="bi bi-plus-circle"></i> New Action
                        </button>
                        <button type="button" class="btn btn-primary" id="printResults">
                            <i class="bi bi-printer"></i> Print Results
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedStudents = [];
    let allStudents = [];
    let currentResults = null;

    // Helper function to generate preview email with @csskabba.ng domain
    function generatePreviewEmail(firstname, lastname) {
        let cleanFirst = firstname.toLowerCase().replace(/[^a-z0-9]/g, '');
        let cleanLast = lastname.toLowerCase().replace(/[^a-z0-9]/g, '');
        if (!cleanFirst) cleanFirst = 'student';
        if (!cleanLast) cleanLast = 'user';
        return cleanFirst + '.' + cleanLast + '@csskabba.ng';
    }

    // Load students function
    function loadStudents() {
        const search = $('#massStudentSearch').val();
        const classId = $('#massClassFilter').val();
        const armId = $('#massArmFilter').val();
        const accountStatus = $('#massAccountStatus').val();

        $('#massStudentList').html('<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>');

        let url = '{{ route("get.students") }}?limit=2000';
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (classId) url += `&class_id=${classId}`;
        if (armId) url += `&arm_id=${armId}`;
        if (accountStatus !== 'all') url += `&has_account=${accountStatus}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    allStudents = data.students;
                    renderStudentTable();
                    if (data.classes && $('#massClassFilter option').length <= 1) {
                        populateFilters(data.classes, data.arms);
                    }
                } else {
                    $('#massStudentList').html('<tr><td colspan="6" class="text-center text-danger">Error loading students</td></tr>');
                }
            })
            .catch(() => {
                $('#massStudentList').html('<tr><td colspan="6" class="text-center text-danger">Network error</td></tr>');
            });
    }

    function renderStudentTable() {
        if (!allStudents.length) {
            $('#massStudentList').html('<tr><td colspan="6" class="text-center">No students found</td></tr>');
            $('#massSelectedCount').text('0 selected');
            return;
        }

        let html = '';
        allStudents.forEach(student => {
            const isSelected = selectedStudents.some(s => s.id === student.id);
            const statusBadge = student.has_account
                ? '<span class="badge bg-success">Has Account</span>'
                : '<span class="badge bg-secondary">No Account</span>';
            const previewEmail = generatePreviewEmail(student.firstname, student.lastname);

            html += `
                <tr>
                    <td><input type="checkbox" class="student-checkbox" data-id="${student.id}" ${isSelected ? 'checked' : ''}></td>
                    <td><strong>${student.admissionNo || 'N/A'}</strong></td>
                    <td>${student.name}</td>
                    <td>${student.class_name || 'N/A'} ${student.arm_name ? '/' + student.arm_name : ''}</td>
                    <td>${statusBadge}</td>
                    <td><small class="text-muted">${previewEmail}</small></td>
                </tr>
            `;
        });
        $('#massStudentList').html(html);

        updateSelectedCount();

        $('.student-checkbox').off('change').on('change', function() {
            const studentId = parseInt($(this).data('id'));
            const student = allStudents.find(s => s.id === studentId);
            if ($(this).is(':checked')) {
                if (!selectedStudents.some(s => s.id === studentId)) {
                    selectedStudents.push(student);
                }
            } else {
                selectedStudents = selectedStudents.filter(s => s.id !== studentId);
            }
            updateSelectedCount();
        });
    }

    function updateSelectedCount() {
        $('#massSelectedCount').text(`${selectedStudents.length} selected`);
        $('#selectAllCheckbox').prop('checked', selectedStudents.length === allStudents.length && allStudents.length > 0);
    }

    function populateFilters(classes, arms) {
        let classHtml = '<option value="">All Classes</option>';
        classes.forEach(cls => classHtml += `<option value="${cls.id}">${cls.name}</option>`);
        $('#massClassFilter').html(classHtml);

        let armHtml = '<option value="">All Arms</option>';
        arms.forEach(arm => armHtml += `<option value="${arm.id}">${arm.name}</option>`);
        $('#massArmFilter').html(armHtml);
    }

    // Filter change events
    $('#massStudentSearch, #massClassFilter, #massArmFilter, #massAccountStatus').on('input change', function() {
        selectedStudents = [];
        loadStudents();
    });

    // Select all functionality
    $('#selectAllStudents, #selectAllCheckbox').on('click', function() {
        const isChecked = $(this).prop('checked');
        selectedStudents = isChecked ? [...allStudents] : [];
        renderStudentTable();
    });

    // Proceed to step 2
    $('#proceedToAction').on('click', function() {
        if (selectedStudents.length === 0) {
            Swal.fire('Warning', 'Please select at least one student', 'warning');
            return;
        }

        let summaryHtml = '';
        selectedStudents.forEach(student => {
            const statusBadge = student.has_account
                ? '<span class="badge bg-success">Has Account</span>'
                : '<span class="badge bg-secondary">No Account</span>';
            const previewEmail = generatePreviewEmail(student.firstname, student.lastname);
            summaryHtml += `
                <tr>
                    <td>${student.name}</td>
                    <td>${student.admissionNo || 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td><small>${previewEmail}</small></td>
                </tr>
            `;
        });
        $('#selectedStudentsList').html(summaryHtml);
        $('#step2SelectedCount').text(selectedStudents.length);

        $('#massStep1').hide();
        $('#massStep2').show();
    });

    // Action card click handler
    $('.action-card').on('click', function() {
        $('.action-card').css('border', '2px solid #e0e0e0').css('background', 'white');
        $(this).css('border', '2px solid #0d6efd').css('background', '#f0f8ff');

        const action = $(this).data('action');
        $('#selectedAction').val(action);

        if (action === 'create' || action === 'reset') {
            $('#passwordSettings').show();
            $('#roleSettings').show();
            $('#emailFormatNote').show();
        } else {
            $('#passwordSettings').hide();
            $('#roleSettings').hide();
            $('#emailFormatNote').hide();
        }

        const hasAccountStudents = selectedStudents.filter(s => s.has_account);
        const noAccountStudents = selectedStudents.filter(s => !s.has_account);

        let warningHtml = '';
        if (action === 'create' && hasAccountStudents.length > 0) {
            warningHtml = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${hasAccountStudents.length} selected student(s) already have accounts and will be skipped.`;
        } else if (action === 'reset' && noAccountStudents.length > 0) {
            warningHtml = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${noAccountStudents.length} selected student(s) don't have accounts and will be skipped.`;
        } else if (action === 'revoke' && noAccountStudents.length > 0) {
            warningHtml = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${noAccountStudents.length} selected student(s) don't have accounts and will be skipped.`;
        }

        if (warningHtml) {
            $('#actionWarning').html(warningHtml).show();
        } else {
            $('#actionWarning').hide();
        }
    });

    // Password type radio button change handler
    $('input[name="passwordTypeRadio"]').on('change', function() {
        $('#sharedPasswordContainer').toggle($(this).val() === 'same');
    });

    // Back button
    $('#backToStep1').on('click', function() {
        $('#massStep2').hide();
        $('#massStep1').show();
    });

    // Execute action
    $('#executeAction').on('click', function() {
        const actionType = $('#selectedAction').val();

        if (!actionType) {
            Swal.fire('Error', 'Please select an action', 'error');
            return;
        }

        const students = selectedStudents.map(s => ({ student_id: s.id }));
        const payload = {
            _token: '{{ csrf_token() }}',
            students: students,
            action_type: actionType,
        };

        if (actionType === 'create' || actionType === 'reset') {
            const passwordType = $('input[name="passwordTypeRadio"]:checked').val();
            payload.password_type = passwordType;

            if (passwordType === 'same') {
                payload.shared_password = $('#sharedPassword').val();
                if (!payload.shared_password || payload.shared_password.length < 6) {
                    Swal.fire('Error', 'Shared password must be at least 6 characters', 'error');
                    return;
                }
            }

            // Only assign the "Student" role (capital S)
            payload.roles = ['Student'];
        }

        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we process your request',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch('{{ route("users.mass-create-students") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                currentResults = data;
                displayResults(data);
                $('#massStep2').hide();
                $('#massStep3').show();
            } else {
                Swal.fire('Error', data.message || 'Operation failed', 'error');
            }
        })
        .catch(() => {
            Swal.close();
            Swal.fire('Error', 'Network error occurred', 'error');
        });
    });

    // Display results
    function displayResults(data) {
        let html = '<div class="alert alert-success"><h5><i class="bi bi-check-circle-fill"></i> Operation Complete!</h5><p>' + data.message + '</p>';

        if (data.created && data.created.length > 0) {
            html += `<div class="mt-3"><strong><i class="bi bi-person-plus-fill"></i> Created Accounts (${data.created.length}):</strong>
                <div class="table-responsive mt-2"><table class="table table-sm table-bordered">
                <thead class="table-success"><tr><th>Name</th><th>Username</th><th>Email</th><th>Password</th><th>Admission No</th></tr></thead><tbody>`;
            data.created.forEach(c => {
                html += `<tr><td>${c.name}</td><td><code>${c.username}</code></td><td>${c.email}</td><td><code class="bg-light p-1">${c.password}</code></td><td>${c.admissionNo || 'N/A'}</td></tr>`;
            });
            html += `</tbody></table></div></div>`;
        }

        if (data.reset && data.reset.length > 0) {
            html += `<div class="mt-3"><strong><i class="bi bi-key-fill"></i> Reset Passwords (${data.reset.length}):</strong>
                <div class="table-responsive mt-2"><table class="table table-sm table-bordered">
                <thead class="table-warning"><tr><th>Name</th><th>Username</th><th>Email</th><th>New Password</th><th>Admission No</th></tr></thead><tbody>`;
            data.reset.forEach(r => {
                html += `<tr><td>${r.name}</td><td><code>${r.username}</code></td><td>${r.email}</td><td><code class="bg-light p-1">${r.password}</code></td><td>${r.admissionNo || 'N/A'}</td></tr>`;
            });
            html += `</tbody></table></div></div>`;
        }

        if (data.revoked && data.revoked.length > 0) {
            html += `<div class="mt-3"><strong><i class="bi bi-person-x-fill"></i> Revoked Accounts (${data.revoked.length}):</strong><ul class="mt-2">`;
            data.revoked.forEach(r => {
                html += `<li>${r.name} (${r.admissionNo || 'N/A'}) - Account removed</li>`;
            });
            html += `</ul></div>`;
        }

        if (data.reprinted && data.reprinted.length > 0) {
            html += `<div class="mt-3"><strong><i class="bi bi-printer-fill"></i> Reprinted Credentials (${data.reprinted.length}):</strong>
                <div class="table-responsive mt-2"><table class="table table-sm table-bordered">
                <thead class="table-info"><tr><th>Name</th><th>Username</th><th>Email</th><th>Admission No</th><th>Note</th></tr></thead><tbody>`;
            data.reprinted.forEach(r => {
                html += `<tr><td>${r.name}</td><td><code>${r.username}</code></td><td>${r.email}</td><td>${r.admissionNo || 'N/A'}</td><td><small class="text-muted">Password hidden for security</small></td></tr>`;
            });
            html += `</tbody></table></div></div>`;
        }

        if (data.skipped && data.skipped.length > 0) {
            html += `<div class="mt-3 text-warning"><strong><i class="bi bi-skip-forward-fill"></i> Skipped (${data.skipped.length}):</strong><ul>`;
            data.skipped.forEach(s => html += `<li>${s}</li>`);
            html += `</ul></div>`;
        }

        html += `</div>`;
        $('#resultsContainer').html(html);
    }

    // Print results
    $('#printResults').on('click', function() {
        if (!currentResults) return;

        const printWindow = window.open('', '_blank');
        let printHtml = `<!DOCTYPE html><html><head>
            <title>Student Credentials Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                h1 { color: #333; margin-bottom: 5px; }
                .subtitle { color: #666; margin-bottom: 5px; }
                .date { color: #999; font-size: 12px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #4e73df; color: white; }
                .section-title { margin-top: 30px; margin-bottom: 10px; color: #333; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #999; }
                @media print {
                    body { margin: 0; padding: 10px; }
                }
            </style>
        </head><body>
            <div class="header">
                <h1>Student Account Credentials Report</h1>
                <div class="subtitle">Generated by School Management System</div>
                <div class="date">Print Date: ${new Date().toLocaleString()}</div>
                <div class="date">Email Domain: @csskabba.ng</div>
                <div class="date">Role: Student Only</div>
            </div>`;

        if (currentResults.created && currentResults.created.length) {
            printHtml += `<h3 class="section-title">✓ Newly Created Accounts (${currentResults.created.length})</h3>
                <table><thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Password</th><th>Admission No</th></tr></thead><tbody>`;
            currentResults.created.forEach((c, idx) => {
                printHtml += `<tr><td>${idx+1}</td><td>${c.name}</td><td>${c.username}</td><td>${c.email}</td><td><strong>${c.password}</strong></td><td>${c.admissionNo || 'N/A'}</td></tr>`;
            });
            printHtml += `</tbody></table>`;
        }

        if (currentResults.reset && currentResults.reset.length) {
            printHtml += `<h3 class="section-title">🔄 Password Reset Accounts (${currentResults.reset.length})</h3>
                <table><thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>New Password</th><th>Admission No</th></tr></thead><tbody>`;
            currentResults.reset.forEach((r, idx) => {
                printHtml += `<tr><td>${idx+1}</td><td>${r.name}</td><td>${r.username}</td><td>${r.email}</td><td><strong>${r.password}</strong></td><td>${r.admissionNo || 'N/A'}</td></tr>`;
            });
            printHtml += `</tbody></table>`;
        }

        printHtml += `<div class="footer"><p>This is an official document. Please keep these credentials secure.</p>
            <p>Students can use these credentials to access the school portal.</p>
            <p><strong>Note:</strong> These accounts have only the "Student" role for security purposes.</p></div>
            <script>window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 1000); };<\/script>
        </body></html>`;

        printWindow.document.write(printHtml);
        printWindow.document.close();
    });

    // Reset and new action
    $('#newAction, #massStudentModal').on('hidden.bs.modal', function() {
        selectedStudents = [];
        currentResults = null;
        $('#massStep2, #massStep3').hide();
        $('#massStep1').show();
        $('#selectedAction').val('');
        $('.action-card').css('border', '2px solid #e0e0e0').css('background', 'white');
        loadStudents();
    });

    // Initial load
    $('#massStudentModal').on('show.bs.modal', function() {
        selectedStudents = [];
        loadStudents();
    });
});
</script>

<style>
.action-card {
    transition: all 0.3s ease;
}
.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.sticky-top {
    position: sticky;
    position: sticky;
    top: 0;
    z-index: 10;
    background: white;
}
.form-check-input:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}
</style>
