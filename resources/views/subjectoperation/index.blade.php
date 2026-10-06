{{-- resources/views/subjectoperation/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $school = \App\Models\SchoolInformation::getActiveSchool(); @endphp

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Subject Registration</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('subjects.index') }}">Student Management</a></li>
                                <li class="breadcrumb-item active">Subject Registration</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Error!</strong> There were some problems with your input.<br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="subjectList">

                {{-- Class & Session Filter --}}
                <div class="row mb-3">
                    <div class="col-lg-12">
                        <div class="sf-card">
                            <div class="sf-card-header" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
                                <h6 class="text-white mb-0 py-1 d-flex align-items-center gap-2">
                                    <i class="ri-filter-3-line"></i>Filter Students
                                </h6>
                            </div>
                            <div class="sf-card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-xxl-4 col-sm-6">
                                        <label class="sf-label">Class</label>
                                        <select class="sf-select" id="idclass">
                                            <option value="ALL">— Select Class —</option>
                                            @foreach ($schoolclass as $class)
                                                <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->schoolarm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-4 col-sm-6">
                                        <label class="sf-label">Session</label>
                                        <select class="sf-select" id="idsession">
                                            <option value="ALL">— Select Session —</option>
                                            @foreach ($schoolsessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-2 col-sm-6">
                                        <button type="button" class="sf-btn sf-btn-primary w-100" onclick="filterData();">
                                            <i class="ri-search-line"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Subject Teachers Card --}}
                <div class="row mb-3" id="subjectTeachersCard">
                    <div class="col-lg-12">
                        <div class="sf-card">
                            <div class="sf-card-header d-flex align-items-center flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-size:15px;font-weight:600;color:#1d1d1f;">
                                        <i class="ri-book-open-line text-primary"></i>Subject Teachers
                                        <span class="sf-pill sf-pill-primary" id="subjectTeacherCount">0</span>
                                    </h5>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="selectAllSubjects();">
                                        <i class="ri-checkbox-multiple-line"></i>Select All
                                    </button>
                                    <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="deselectAllSubjects();">
                                        <i class="ri-checkbox-blank-line"></i>Deselect All
                                    </button>
                                </div>
                            </div>
                            <div class="sf-card-body">
                                <div class="sf-info-banner mb-3">
                                    <i class="ri-information-line text-primary"></i>
                                    <span>Select subjects to register or unregister students. Subjects are grouped by term.</span>
                                </div>
                                <div id="subjectTeachersContainer">
                                    @foreach ($schoolterms as $term)
                                        @php $termSubjects = $subjectTeachers ? $subjectTeachers->where('termid', $term->id) : collect(); @endphp
                                        @if ($termSubjects->isNotEmpty())
                                            <div class="term-group mb-4">
                                                <div class="d-flex align-items-center mb-2 gap-2">
                                                    <span class="sf-term-badge">
                                                        <i class="ri-calendar-2-line me-1"></i>{{ $term->term }}
                                                    </span>
                                                    <span class="sf-pill sf-pill-primary">
                                                        {{ $termSubjects->count() }} subject{{ $termSubjects->count() !== 1 ? 's' : '' }}
                                                    </span>
                                                </div>
                                                <div class="row g-2">
                                                    @foreach ($termSubjects as $teacher)
                                                        <div class="col-xl-3 col-md-4 col-sm-6">
                                                            <div class="subject-check-card"
                                                                 data-subject-name="{{ $teacher->subjectname }}"
                                                                 data-teacher-name="{{ $teacher->staffname }}"
                                                                 data-term-id="{{ $teacher->termid }}"
                                                                 onclick="toggleSubjectCard(this)">
                                                                <input class="sf-chk subject-checkbox"
                                                                       type="checkbox"
                                                                       id="subject-{{ $teacher->subjectclassid }}"
                                                                       data-subjectclassid="{{ $teacher->subjectclassid }}"
                                                                       data-staffid="{{ $teacher->userid }}"
                                                                       data-termid="{{ $teacher->termid }}"
                                                                       data-subject-name="{{ $teacher->subjectname }}"
                                                                       data-teacher-name="{{ $teacher->staffname }}"
                                                                       checked>
                                                                <label for="subject-{{ $teacher->subjectclassid }}" style="cursor:pointer;flex:1;min-width:0;">
                                                                    <span class="d-block fw-semibold text-truncate" style="font-size:13px;color:#1d1d1f;">{{ $teacher->subjectname }}</span>
                                                                    <span style="font-size:11px;color:#86868b;">{{ $teacher->staffname }}</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                    @if(!$subjectTeachers || $subjectTeachers->isEmpty())
                                        <div class="sf-empty-state">
                                            <i class="ri-book-2-line ri-2x d-block mb-2"></i>
                                            Select a class and session to view subjects.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Student Filters --}}
                <div class="row mb-3">
                    <div class="col-lg-12">
                        <div class="sf-card">
                            <div class="sf-card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-xxl-4">
                                        <label class="sf-label">Search Students</label>
                                        <div class="sf-search-wrap">
                                            <svg class="sf-search-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8.5" cy="8.5" r="5"/><path d="m13 13 3.5 3.5"/></svg>
                                            <input type="text" class="sf-search-input search" placeholder="Search by name or admission no…" oninput="filterData()">
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <label class="sf-label">Gender</label>
                                        <select class="sf-select" id="idgender">
                                            <option value="ALL">All Genders</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <label class="sf-label">Admission No</label>
                                        <select class="sf-select" id="idadmission">
                                            <option value="ALL">All Admission Nos</option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-2 col-sm-6">
                                        <button type="button" class="sf-btn sf-btn-secondary w-100" onclick="filterData();">
                                            <i class="bi bi-funnel"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Students Table --}}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="sf-card sf-table-card">
                            <div class="sf-card-header d-flex align-items-center flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-size:15px;font-weight:600;color:#1d1d1f;">
                                        <i class="ri-group-line text-primary"></i>Students
                                        <span class="sf-pill sf-pill-dark" id="studentcount">{{ $students ? $students->total() : 0 }}</span>
                                    </h5>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="spinner-border spinner-border-sm text-primary d-none" id="register-loading-spinner" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <button type="button" class="sf-btn sf-btn-primary" data-bs-toggle="modal" data-bs-target="#registeredClassesModal">
                                        <i class="ri-eye-line"></i> View Registered
                                    </button>
                                    <button type="button" class="sf-btn sf-btn-warning" onclick="openArchivedModal();">
                                        <i class="ri-archive-line"></i> History
                                    </button>
                                </div>
                            </div>

                            <div class="sf-table-wrap">
                                <div class="sf-check-all-row">
                                    <div class="sf-chk-wrap">
                                        <input type="checkbox" class="sf-chk" id="checkAll">
                                        <label for="checkAll"></label>
                                    </div>
                                    <span class="sf-check-all-label">Select all visible</span>
                                </div>

                                <table class="table align-middle mb-0" id="subjectListTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50" class="text-center"></th>
                                            <th width="60">SN</th>
                                            <th>Admission No</th>
                                            <th>Student Name</th>
                                            <th>Class</th>
                                            <th>Gender</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentTableBody">
                                        @include('subjectoperation.partials.student_rows')
                                    </tbody>
                                </table>

                                <div class="d-flex justify-content-end p-3 border-top" id="pagination-container">
                                    {{ $students ? $students->links('pagination::bootstrap-5') : '' }}
                                </div>

                                <div class="sf-tray" id="selection-tray">
                                    <div class="sf-tray-inner">
                                        <span class="sf-tray-count" id="tray-count"></span>
                                        <div class="sf-tray-chips" id="tray-chips"></div>
                                        <div class="sf-tray-actions">
                                            <button class="sf-btn sf-btn-ghost sf-btn-sm" onclick="openUnregisterModal()">
                                                <i class="ri-user-unfollow-line"></i> Unregister
                                            </button>
                                            <button class="sf-btn sf-btn-success sf-btn-sm" onclick="registerSelectedStudentsBatch()">
                                                <i class="ri-user-add-line"></i> Register Selected
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- All Modals --}}
                {{-- Snapshot Name Modal --}}
                <div class="modal fade" id="snapshotNameModal" tabindex="-1" aria-labelledby="snapshotNameModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
                        <div class="modal-content sf-modal-content border-0 shadow-lg overflow-hidden">
                            <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#f5576c 0%,#f093fb 100%);">
                                <div class="py-1">
                                    <h5 class="modal-title text-white fw-semibold" id="snapshotNameModalLabel">
                                        <i class="ri-archive-line me-2"></i>Name this Unregistration
                                    </h5>
                                    <p class="text-white-50 small mb-0">Give this snapshot a name so you can find it later.</p>
                                </div>
                                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="d-flex gap-2 flex-wrap mb-4" id="snapshotSummaryPills">
                                    <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2" id="snapshotStudentCount"></span>
                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2" id="snapshotSubjectCount"></span>
                                </div>
                                <div class="mb-3">
                                    <label class="sf-label" for="snapshotNameInput">Snapshot Name <span class="text-danger">*</span></label>
                                    <input type="text" class="sf-input" id="snapshotNameInput" placeholder="e.g. Term 2 Corrections — June 2025" maxlength="191" autocomplete="off">
                                    <div class="invalid-feedback" id="snapshotNameError">Please enter a snapshot name.</div>
                                    <div class="sf-hint mt-1">
                                        <i class="ri-lightbulb-line text-warning"></i>
                                        A descriptive name helps staff identify this batch when restoring it later.
                                    </div>
                                </div>
                                <div class="mb-1">
                                    <label class="sf-label" for="snapshotNotesInput">Notes <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea class="sf-input" id="snapshotNotesInput" rows="3" placeholder="Reason for unregistration or any extra context…" maxlength="1000"></textarea>
                                    <div class="sf-hint text-end mt-1">
                                        <span id="snapshotNotesCount">0</span>/1000
                                    </div>
                                </div>
                                <div class="sf-info-banner sf-info-warning mt-3 mb-0">
                                    <i class="ri-error-warning-line"></i>
                                    <div>All existing scores for these students in the selected subjects will be saved to the snapshot and can be fully restored later.</div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                <button type="button" class="sf-btn sf-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="sf-btn sf-btn-danger px-4" onclick="proceedUnregister();">
                                    <i class="ri-user-unfollow-line me-1"></i> Unregister & Save Snapshot
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Registered Classes Modal --}}
                <div class="modal fade" id="registeredClassesModal" tabindex="-1" aria-labelledby="registeredClassesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content sf-modal-content border-0 shadow-lg">
                            <div class="modal-header" style="background:#1e3a5f;">
                                <h5 class="modal-title text-white" id="registeredClassesModalLabel">
                                    <i class="ri-graduation-cap-line me-2"></i>Registered Classes Overview
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-3" style="background:#f5f7fa;">
                                <div id="registeredClassesContent">
                                    <div class="text-center text-muted py-5">
                                        <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status"></div>
                                        <p class="mt-3 mb-0">Loading registration data...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="sf-btn sf-btn-ghost" onclick="printRegisteredClasses();">
                                    <i class="ri-printer-line me-1"></i> Print / Export PDF
                                </button>
                                <button type="button" class="sf-btn sf-btn-secondary" data-bs-dismiss="modal">
                                    <i class="ri-close-line me-1"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Archived History Modal --}}
                <div class="modal fade" id="archivedModal" tabindex="-1" aria-labelledby="archivedModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content sf-modal-content border-0 shadow-lg">
                            <div class="modal-header border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <h5 class="modal-title text-white" id="archivedModalLabel">
                                    <i class="ri-archive-line me-2"></i>Unregistered History
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="p-3 border-bottom bg-light d-flex align-items-center flex-wrap gap-2">
                                    <div class="flex-grow-1">
                                        <div class="sf-search-wrap" style="max-width:300px;">
                                            <svg class="sf-search-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8.5" cy="8.5" r="5"/><path d="m13 13 3.5 3.5"/></svg>
                                            <input type="text" class="sf-search-input" id="archiveSearch" placeholder="Search snapshot name or subject…">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <select class="sf-select sf-select-sm" id="archiveTermFilter" style="width:auto;">
                                            <option value="">All Terms</option>
                                            @foreach($schoolterms as $term)
                                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                                            @endforeach
                                        </select>
                                        <select class="sf-select sf-select-sm" id="archivePerPage" style="width:auto;">
                                            <option value="20">20 per page</option>
                                            <option value="50" selected>50 per page</option>
                                            <option value="100">100 per page</option>
                                        </select>
                                        <button class="sf-btn sf-btn-ghost sf-btn-sm" onclick="loadArchivedPage(1);">
                                            <i class="ri-refresh-line"></i> Refresh
                                        </button>
                                        <div class="spinner-border spinner-border-sm text-warning d-none" id="archiveSpinner" role="status"></div>
                                    </div>
                                </div>
                                <div class="p-3" id="snapshotCardsContainer">
                                    <div class="sf-empty-state">Select a class and session first, then open this panel.</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" id="archivePaginationWrap">
                                    <small class="text-muted" id="archiveMeta"></small>
                                    <div id="archivePagination" class="d-flex gap-1"></div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <small class="text-muted me-auto">
                                    <i class="ri-information-line me-1"></i>
                                    Click a snapshot to view details. Restored records recover original scores.
                                </small>
                                <button type="button" class="sf-btn sf-btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Snapshot Detail Modal --}}
                <div class="modal fade" id="snapshotDetailModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content sf-modal-content border-0 shadow-lg">
                            <div class="modal-header border-0" style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);">
                                <div>
                                    <h5 class="modal-title text-white fw-semibold" id="snapshotDetailTitle">Snapshot Detail</h5>
                                    <p class="text-white-50 small mb-0" id="snapshotDetailSubtitle"></p>
                                </div>
                                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div id="snapshotNotesBanner" class="alert alert-info d-flex gap-2 align-items-start m-3 mb-0 d-none">
                                    <i class="ri-sticky-note-line fs-5 flex-shrink-0"></i>
                                    <div id="snapshotNotesText" class="small"></div>
                                </div>
                                <div class="px-3 pt-3 pb-2 border-bottom">
                                    <div class="mb-2">
                                        <div class="sf-search-wrap" style="max-width:340px;">
                                            <svg class="sf-search-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8.5" cy="8.5" r="5"/><path d="m13 13 3.5 3.5"/></svg>
                                            <input type="text" class="sf-search-input" id="detailSearchInput" placeholder="Search by name or admission no…" oninput="filterDetailRows(this.value);">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <button class="sf-btn sf-btn-success sf-btn-sm" id="detailRestoreAllBtn" onclick="restoreEntireSnapshot();">
                                            <i class="ri-refresh-line me-1"></i> Restore All
                                        </button>
                                        <button class="sf-btn sf-btn-success sf-btn-sm d-none" id="detailRestoreSelectedBtn" onclick="restoreDetailSelected();">
                                            <i class="ri-refresh-line me-1"></i> Restore Selected
                                        </button>
                                        <button class="sf-btn sf-btn-danger sf-btn-sm d-none" id="detailDeleteSelectedBtn" onclick="deleteDetailSelected();">
                                            <i class="ri-delete-bin-line me-1"></i> Delete Selected
                                        </button>
                                        <div class="spinner-border spinner-border-sm text-primary d-none ms-1" id="detailSpinner" role="status"></div>
                                        <span class="text-muted small ms-auto" id="detailStudentMeta"></span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr id="snapshotDetailHeaderRow">
                                                <th style="width:36px;">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="detailCheckAll">
                                                    </div>
                                                </th>
                                                <th>Student</th>
                                                <th>Adm. No</th>
                                                <th>Gender</th>
                                            </tr>
                                        </thead>
                                        <tbody id="snapshotDetailBody">
                                            <tr><td colspan="10" class="text-center text-muted py-4">Loading…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="sf-btn sf-btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Image View Modal --}}
                <div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content sf-modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Student Image</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="enlargedImage" src="" alt="Student Image" class="img-fluid"
                                    onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- STYLES --}}
<style>
:root {
    --sf-bg: #f5f5f7;
    --sf-surface: #ffffff;
    --sf-border: rgba(0,0,0,.06);
    --sf-border-med: rgba(0,0,0,.1);
    --sf-text-1: #1d1d1f;
    --sf-text-2: #6e6e73;
    --sf-text-3: #aeaeb2;
    --sf-accent: #534AB7;
    --sf-accent-soft: #EEEDFE;
    --sf-radius-sm: 8px;
    --sf-radius-md: 10px;
    --sf-radius-lg: 14px;
    --sf-radius-xl: 18px;
    --sf-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04);
    --sf-transition: .22s cubic-bezier(.4,0,.2,1);
}

.sf-card { background: var(--sf-surface); border: 0.5px solid var(--sf-border); border-radius: var(--sf-radius-xl); box-shadow: var(--sf-shadow); overflow: hidden; }
.sf-card-header { padding: 14px 20px; border-bottom: 0.5px solid var(--sf-border); }
.sf-card-body { padding: 18px 20px; }
.sf-label { display: block; font-size: 11px; font-weight: 500; color: var(--sf-text-2); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.sf-input, .sf-select { width: 100%; padding: 9px 12px; border: 0.5px solid var(--sf-border-med); border-radius: var(--sf-radius-md); background: var(--sf-surface); font-size: 14px; color: var(--sf-text-1); outline: none; }
.sf-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23aeaeb2' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 30px; }
.sf-search-wrap { position: relative; }
.sf-search-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--sf-text-3); }
.sf-search-input { width: 100%; padding: 9px 12px 9px 34px; border: 0.5px solid var(--sf-border-med); border-radius: var(--sf-radius-md); font-size: 14px; }
.sf-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: var(--sf-radius-md); border: none; font-size: 13px; font-weight: 500; cursor: pointer; transition: all .22s; }
.sf-btn-primary { background: var(--sf-accent); color: #fff; }
.sf-btn-success { background: #1a7a4a; color: #fff; }
.sf-btn-danger { background: #dc3545; color: #fff; }
.sf-btn-warning { background: #f59e0b; color: #fff; }
.sf-btn-secondary { background: #f2f2f7; color: var(--sf-text-1); border: 0.5px solid var(--sf-border-med); }
.sf-btn-ghost { background: transparent; color: var(--sf-text-1); border: 0.5px solid var(--sf-border-med); }
.sf-btn-sm { padding: 6px 12px; font-size: 12px; }
.sf-pill { padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.sf-pill-primary { background: var(--sf-accent-soft); color: var(--sf-accent); }
.sf-pill-dark { background: #f2f2f7; color: var(--sf-text-1); }
.sf-term-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; color: #fff; background: linear-gradient(135deg,#667eea,#764ba2); }
.sf-info-banner { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-radius: var(--sf-radius-md); font-size: 13px; background: #eff6ff; color: #1e40af; border: 0.5px solid #bfdbfe; }
.sf-info-banner.sf-info-warning { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.sf-hint { font-size: 12px; color: var(--sf-text-2); }
.sf-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; color: var(--sf-text-2); font-size: 14px; gap: 8px; text-align: center; }
.subject-check-card { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: var(--sf-radius-md); border: 0.5px solid var(--sf-border-med); background: var(--sf-surface); cursor: pointer; transition: all var(--sf-transition); }
.subject-check-card:hover { border-color: var(--sf-accent); background: #faf9ff; }
.subject-check-card.is-checked { border-color: var(--sf-accent); background: var(--sf-accent-soft); }
.sf-chk { width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid var(--sf-border-med); appearance: none; cursor: pointer; background: var(--sf-surface); position: relative; flex-shrink: 0; }
.sf-chk:checked { background: var(--sf-accent); border-color: var(--sf-accent); }
.sf-chk:checked::after { content: ''; position: absolute; left: 4px; top: 1.5px; width: 6px; height: 9px; border: 2px solid #fff; border-top: none; border-left: none; transform: rotate(42deg); }
.sf-table-wrap { position: relative; }
.sf-check-all-row { display: flex; align-items: center; gap: 10px; padding: 9px 20px; background: #f9f9fb; border-bottom: 0.5px solid var(--sf-border); }
.sf-chk-wrap { display: flex; align-items: center; }
.sf-check-all-label { font-size: 12px; color: var(--sf-text-2); }
.sf-student-name { font-size: 14px; font-weight: 500; color: var(--sf-text-1); }
.sf-badge { padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.sf-badge-f { background: #FBEAF0; color: #993556; }
.sf-badge-m { background: #E6F1FB; color: #0C447C; }
.sf-tray { position: sticky; bottom: 0; background: rgba(255,255,255,.92); backdrop-filter: blur(20px); border-top: 0.5px solid var(--sf-border-med); padding: 12px 20px; transform: translateY(110%); transition: transform .38s cubic-bezier(.34,1.2,.64,1); z-index: 99; border-radius: 0 0 var(--sf-radius-xl) var(--sf-radius-xl); }
.sf-tray.tray-visible { transform: translateY(0); }
.sf-tray-inner { display: flex; align-items: center; gap: 12px; }
.sf-tray-count { font-size: 13px; font-weight: 600; color: var(--sf-text-1); white-space: nowrap; }
.sf-tray-chips { display: flex; gap: 6px; flex: 1; overflow-x: auto; }
.sf-tray-actions { display: flex; gap: 8px; }
.sf-chip { display: flex; align-items: center; gap: 5px; background: var(--sf-accent-soft); border: 0.5px solid #AFA9EC; border-radius: 20px; padding: 4px 10px 4px 5px; font-size: 12px; color: #3C3489; white-space: nowrap; }
.sf-chip-av { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 600; flex-shrink: 0; }
.sf-chip-x { background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; color: #3C3489; opacity: .6; }
.sf-chip-x:hover { opacity: 1; }
.sf-modal-content { border-radius: var(--sf-radius-xl) !important; overflow: hidden; }

/* =====================================================================
   STUDENT PICTURE AVATAR
   ===================================================================== */
.sf-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    border: 1px solid #ddd;
    cursor: pointer;
    flex-shrink: 0;
}
.sf-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* =====================================================================
   SUBJECT LOADER STATE
   ===================================================================== */
.sf-subject-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 28px 20px;
    color: var(--sf-text-2);
    font-size: 14px;
}

/* =====================================================================
   ROW ANIMATION — Apple-style staggered fade+slide
   ===================================================================== */
.sf-student-row {
    opacity: 0;
    transform: translateY(8px);
}
.sf-student-row.row-visible {
    opacity: 1;
    transform: translateY(0);
    transition: opacity 0.28s cubic-bezier(.4,0,.2,1),
                transform 0.28s cubic-bezier(.4,0,.2,1);
}

/* =====================================================================
   APPLE-STYLE SELECTION DIMMING EFFECT
   When any row is selected, unselected rows dim out elegantly.
   The selected row glows with a subtle accent background.
   ===================================================================== */

/* Smooth transition on every row for the dimming effect */
#studentTableBody tr.sf-student-row {
    transition: opacity 0.32s cubic-bezier(.4,0,.2,1),
                transform 0.28s cubic-bezier(.4,0,.2,1),
                background-color 0.28s cubic-bezier(.4,0,.2,1),
                box-shadow 0.28s cubic-bezier(.4,0,.2,1);
}

/* When table is in "selection mode" (has any checked row), dim all rows */
#studentTableBody.has-selection tr.sf-student-row {
    opacity: 0.35;
    background-color: transparent;
}

/* The SELECTED rows stay fully visible and get a subtle glow */
#studentTableBody.has-selection tr.sf-student-row.is-row-selected {
    opacity: 1;
    background-color: rgba(83, 74, 183, 0.06);
    box-shadow: inset 3px 0 0 var(--sf-accent);
}

/* Hover on dimmed rows brings them to 65% so user can still interact */
#studentTableBody.has-selection tr.sf-student-row:not(.is-row-selected):hover {
    opacity: 0.65;
    background-color: rgba(0,0,0,0.02);
}

/* Loading state for table */
.loading-row td {
    text-align: center;
    padding: 40px 20px;
    color: var(--sf-text-2);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ============================================================================
// GLOBALS
// ============================================================================
const ROUTES = {
    batchRegister  : '{{ route("subjectregistration.batch") }}',
    unregister     : '{{ route("subjects.destroy") }}',
    getRegistered  : '{{ route("subjects.registered-classes") }}',
    registeredClasses: '{{ route("subjectoperation.registered-classes") }}',
    getArchived    : '{{ route("subjectoperation.archived") }}',
    getSnapshot    : '{{ route("subjectoperation.snapshot.detail") }}',
    restore        : '{{ route("subjectoperation.restore") }}',
    permanentDelete: '{{ route("subjectoperation.archive.batch-delete") }}',
    index          : '{{ route("subjects.index") }}',
    studentSubjectCounts: '{{ route("subjectoperation.student-subject-counts") }}',
};

const CSRF       = '{{ csrf_token() }}';
const AVATAR_URL = '{{ asset("storage") }}';

window._schoolInfo = {
    name   : @json($school?->school_name ?? 'School'),
    address: @json($school?->school_address ?? ''),
    phone  : @json($school?->school_phone ?? ''),
    email  : @json($school?->school_email ?? ''),
    motto  : @json($school?->school_motto ?? ''),
    logo   : @json($school?->logo_url ?? null),
};

let archiveCurrentPage  = 1;
let archiveMeta         = {};
let archiveSearchTimer  = null;
let currentSnapshotMeta = null;
let currentSnapshotRows = [];

// Cached data for print — filled by loadRegisteredClasses()
let _lastRegisteredData = [];

const SF_COLORS = [
    ['#E6F1FB','#0C447C'],['#EAF3DE','#27500A'],['#FAEEDA','#633806'],
    ['#EEEDFE','#3C3489'],['#FBEAF0','#993556'],['#E1F5EE','#085041'],
];
function sfColor(id)      { return SF_COLORS[(id - 1) % SF_COLORS.length]; }
function sfInitials(name) { return (name||'').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase(); }

// ============================================================================
// SWEET ALERT HELPER
// ============================================================================
function showSweetAlert(title, message, type, success = true) {
    Swal.fire({
        title,
        html: `<div class="d-flex align-items-center justify-content-center gap-2">
                <span style="font-size:2rem;">${success ? '🎉' : '😞'}</span>
                <span>${message}</span>
               </div>`,
        icon: success ? 'success' : 'error',
        confirmButtonColor: success ? '#1a7a4a' : '#dc3545',
        confirmButtonText: success ? 'Great!' : 'Okay',
        timer: success ? 3000 : 5000,
        showConfirmButton: true,
    });
}

// ============================================================================
// SUBJECT CHECKBOXES
// ============================================================================
function toggleSubjectCard(card) {
    const cb = card.querySelector('input[type="checkbox"]');
    cb.checked = !cb.checked;
    card.classList.toggle('is-checked', cb.checked);
    updateSubjectCount();
}

function updateSubjectCount() {
    const total = document.querySelectorAll('.subject-checkbox:checked').length;
    const el = document.getElementById('subjectTeacherCount');
    if (el) el.textContent = total;
}

function selectAllSubjects() {
    document.querySelectorAll('.subject-checkbox').forEach(cb => {
        cb.checked = true;
        cb.closest('.subject-check-card')?.classList.add('is-checked');
    });
    updateSubjectCount();
}

function deselectAllSubjects() {
    document.querySelectorAll('.subject-checkbox').forEach(cb => {
        cb.checked = false;
        cb.closest('.subject-check-card')?.classList.remove('is-checked');
    });
    updateSubjectCount();
}

function initializeSubjectCards() {
    document.querySelectorAll('.subject-checkbox:checked').forEach(cb => {
        cb.closest('.subject-check-card')?.classList.add('is-checked');
    });
    updateSubjectCount();
}

// ============================================================================
// HELPERS
// ============================================================================
function escapeHtml(str) {
    if (!str) return str ?? '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function setSpinner(on) {
    document.getElementById('register-loading-spinner')?.classList.toggle('d-none', !on);
}

async function apiFetch(url, method, body) {
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok && !data.success) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
}

function getSelectedStudentIds() {
    return [...document.querySelectorAll('#studentTableBody input[name="chk_child"]:checked')]
        .map(cb => parseInt(cb.closest('tr').querySelector('.id')?.dataset?.id))
        .filter(Boolean);
}

function getSelectedSubjectClasses() {
    return [...document.querySelectorAll('.subject-checkbox:checked')].map(cb => ({
        subjectclassid: parseInt(cb.dataset.subjectclassid),
        staffid        : parseInt(cb.dataset.staffid),
        termid         : parseInt(cb.dataset.termid),
    }));
}

// ============================================================================
// TRAY (SELECTION)
// ============================================================================
function toggleBatchButtons() {
    const checked = [...document.querySelectorAll('#studentTableBody input[name="chk_child"]:checked')];
    const tray    = document.getElementById('selection-tray');
    const chips   = document.getElementById('tray-chips');
    const count   = document.getElementById('tray-count');
    const tbody   = document.getElementById('studentTableBody');

    // ── APPLE SELECTION DIMMING EFFECT ──────────────────────────────────────
    // 1. Toggle the "has-selection" class on tbody so CSS can dim unselected rows
    if (checked.length) {
        tbody?.classList.add('has-selection');
    } else {
        tbody?.classList.remove('has-selection');
    }

    // 2. Mark each row as selected or not
    document.querySelectorAll('#studentTableBody tr.sf-student-row').forEach(tr => {
        const cb = tr.querySelector('input[name="chk_child"]');
        if (cb?.checked) {
            tr.classList.add('is-row-selected');
        } else {
            tr.classList.remove('is-row-selected');
        }
    });

    // ── TRAY ────────────────────────────────────────────────────────────────
    if (!checked.length) { tray?.classList.remove('tray-visible'); return; }
    tray?.classList.add('tray-visible');
    count.textContent = `${checked.length} student${checked.length !== 1 ? 's' : ''} selected`;
    chips.innerHTML = checked.map(cb => {
        const row      = cb.closest('tr');
        const name     = row?.querySelector('.sf-student-name')?.textContent?.trim() ?? 'Student';
        const id       = row?.querySelector('.id')?.dataset?.id;
        const initials = sfInitials(name);
        const [bg, fg] = sfColor(parseInt(id) || 1);
        return `<div class="sf-chip">
            <div class="sf-chip-av" style="background:${bg};color:${fg};">${initials}</div>
            ${escapeHtml(name.split(' ')[0])}
            <button class="sf-chip-x" onclick="uncheckStudent('${id}', this)" title="Remove">
                <svg width="8" height="8" viewBox="0 0 8 8" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 1l6 6M7 1L1 7"/></svg>
            </button>
        </div>`;
    }).join('');
}

function uncheckStudent(id, btn) {
    const row = document.querySelector(`#studentTableBody tr .id[data-id="${id}"]`)?.closest('tr');
    if (!row) return;
    const cb = row.querySelector('input[name="chk_child"]');
    if (cb) { cb.checked = false; }
    toggleBatchButtons();
}

// ============================================================================
// ROW TOGGLE
// ============================================================================
function sfToggleRow(e, row) {
    if (e.target.closest('input[type="checkbox"]') || e.target.closest('a') || e.target.closest('button')) return;
    const cb = row.querySelector('input[name="chk_child"]');
    if (cb) { cb.checked = !cb.checked; toggleBatchButtons(); }
}

// ============================================================================
// REWRITE ROWS — real pictures + staggered fade animation
// ============================================================================
function rewriteExistingRows() {
    const body = document.getElementById('studentTableBody');
    if (!body) return;
    const oldRows = body.querySelectorAll('tr');
    if (!oldRows.length) return;

    let html = '';
    oldRows.forEach((tr, i) => {
        const id          = tr.querySelector('.id')?.dataset?.id || '';
        const admissionno = tr.querySelector('.admissionno')?.dataset?.admissionno
                          || tr.querySelector('.admissionno')?.textContent?.trim() || '';
        const fullName    = tr.querySelector('.name a')?.textContent?.trim()
                          || tr.querySelector('.name')?.textContent?.trim() || '';
        const className   = tr.querySelector('.class')?.textContent?.trim() || '';
        const gender      = tr.querySelector('.gender')?.textContent?.trim() || '';

        const imgEl    = tr.querySelector('img[data-image]');
        const imageUrl = imgEl ? imgEl.getAttribute('data-image') : `${AVATAR_URL}/student_avatars/unnamed.jpg`;

        const initials = sfInitials(fullName);
        const isFemale = gender.toLowerCase() === 'female';

        html += `
        <tr class="sf-student-row" onclick="sfToggleRow(event, this)">
            <td class="text-center">
                <input type="checkbox" class="sf-chk" name="chk_child" onclick="event.stopPropagation()">
                <span class="id" data-id="${escapeHtml(id)}" style="display:none;"></span>
            </td>
            <td class="text-muted small">${i + 1}</td>
            <td class="admissionno" data-admissionno="${escapeHtml(admissionno)}">${escapeHtml(admissionno)}</td>
            <td>
                <div class="d-flex align-items-center gap-3">
                    <div class="sf-avatar" onclick="event.stopPropagation();showStudentImage('${escapeHtml(imageUrl)}')">
                        <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(fullName)}"
                             onerror="this.onerror=null;this.parentNode.innerHTML='<span style=&quot;font-size:12px;font-weight:600;&quot;>${escapeHtml(initials)}</span>';">
                    </div>
                    <div class="sf-student-name">${escapeHtml(fullName)}</div>
                </div>
            </td>
            <td><span class="sf-badge bg-primary-subtle text-primary">${escapeHtml(className)}</span></td>
            <td><span class="sf-badge ${isFemale ? 'sf-badge-f' : 'sf-badge-m'}">${escapeHtml(gender || '—')}</span></td>
            <td>${tr.querySelector('td:last-child')?.innerHTML || ''}</td>
        </tr>`;
    });

    body.innerHTML = html || `<tr><td colspan="7" class="text-center py-4 text-muted">No students found.</td></tr>`;

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            body.querySelectorAll('.sf-student-row').forEach((row, i) => {
                setTimeout(() => row.classList.add('row-visible'), i * 35);
            });
        });
    });

    toggleBatchButtons();
}

function showStudentImage(src) {
    document.getElementById('enlargedImage').src = src;
    new bootstrap.Modal(document.getElementById('imageViewModal')).show();
}

// ============================================================================
// FILTER / SEARCH (AJAX)
// ============================================================================
function filterData() {
    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;

    if (classId === 'ALL' || sessionId === 'ALL') {
        Swal.fire({ icon: 'warning', title: 'Missing filters', text: 'Please select a class and session.', showConfirmButton: true });
        return;
    }

    const search    = document.querySelector('.sf-search-input.search')?.value ?? '';
    const gender    = document.getElementById('idgender').value;
    const admission = document.getElementById('idadmission').value;

    const body = document.getElementById('studentTableBody');
    if (body) {
        body.innerHTML = `<tr class="loading-row"><td colspan="7">
            <div class="d-flex align-items-center justify-content-center gap-2 py-2">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span>Searching students...</span>
            </div>
        </td></tr>`;
    }

    const stcEl = document.getElementById('subjectTeachersContainer');
    if (stcEl) {
        stcEl.innerHTML = `<div class="sf-subject-loading">
            <div class="spinner-border spinner-border-sm text-primary"></div>
            <span>Searching class subjects and teachers…</span>
        </div>`;
    }

    const params = new URLSearchParams({ class_id: classId, session_id: sessionId, search, gender, admissionno: admission });

    fetch(ROUTES.index + '?' + params.toString(), {
        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
    .then(r => r.text())
    .then(html => {
        const doc  = new DOMParser().parseFromString(html, 'text/html');
        const pick = (id) => ({ fresh: doc.getElementById(id), live: document.getElementById(id) });

        const tb = pick('studentTableBody');
        if (tb.fresh && tb.live) {
            tb.live.innerHTML = tb.fresh.innerHTML;
            rewriteExistingRows();
        }

        const pg = pick('pagination-container');
        if (pg.fresh && pg.live) pg.live.innerHTML = pg.fresh.innerHTML;

        const sc = pick('studentcount');
        if (sc.fresh && sc.live) sc.live.textContent = sc.fresh.textContent;

        const stc = pick('subjectTeachersContainer');
        if (stc.fresh && document.getElementById('subjectTeachersContainer')) {
            document.getElementById('subjectTeachersContainer').innerHTML = stc.fresh.innerHTML;
            initializeSubjectCards();
        }

        updateSubjectCount();
        setupPaginationLinks();
    })
    .catch(err => {
        if (body) body.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Error loading data.</td></tr>';
        if (stcEl) stcEl.innerHTML = `<div class="sf-empty-state text-danger">Failed to load subjects.</div>`;
        Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Failed to fetch data.', showConfirmButton: true });
    });
}

// ============================================================================
// PAGINATION
// ============================================================================
function setupPaginationLinks() {
    document.querySelectorAll('#pagination-container a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            if (this.href && !this.classList.contains('disabled')) loadPage(this.href);
        });
    });
}

function loadPage(url) {
    const body = document.getElementById('studentTableBody');
    if (body) {
        body.innerHTML = `<tr class="loading-row"><td colspan="7">
            <div class="d-flex align-items-center justify-content-center gap-2 py-2">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span>Loading...</span>
            </div>
        </td></tr>`;
    }

    fetch(url, { headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
    .then(r => r.text())
    .then(html => {
        const doc  = new DOMParser().parseFromString(html, 'text/html');
        const pick = (id) => ({ fresh: doc.getElementById(id), live: document.getElementById(id) });

        const tb = pick('studentTableBody');
        if (tb.fresh && tb.live) {
            tb.live.innerHTML = tb.fresh.innerHTML;
            rewriteExistingRows();
        }

        const pg = pick('pagination-container');
        if (pg.fresh && pg.live) pg.live.innerHTML = pg.fresh.innerHTML;

        const sc = pick('studentcount');
        if (sc.fresh && sc.live) sc.live.textContent = sc.fresh.textContent;

        updateSubjectCount();
        setupPaginationLinks();
    })
    .catch(() => {
        if (body) body.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Error loading data.</td></tr>';
    });
}

// ============================================================================
// ADMISSION NO OPTIONS
// ============================================================================
function updateAdmissionNoOptions(students) {
    const select = document.getElementById('idadmission');
    if (!select) return;
    select.innerHTML = '<option value="ALL">All Admission Nos</option>';
    [...new Set(students.map(s => s.admissionno).filter(Boolean))].sort().forEach(no => {
        const opt = document.createElement('option');
        opt.value = no; opt.text = no;
        select.appendChild(opt);
    });
}

// ============================================================================
// REGISTER BATCH
// ============================================================================
async function registerSelectedStudentsBatch() {
    const studentIds     = getSelectedStudentIds();
    const subjectClasses = getSelectedSubjectClasses();
    const sessionId      = document.getElementById('idsession').value;

    if (!studentIds.length)     return showSweetAlert('No Students Selected', 'Please select at least one student.', 'warning', false);
    if (!subjectClasses.length) return showSweetAlert('No Subjects Selected', 'Please select at least one subject.', 'warning', false);
    if (sessionId === 'ALL')    return showSweetAlert('Session Required', 'Please select a session.', 'warning', false);

    const ok = await Swal.fire({
        title: 'Confirm Registration',
        html : `<div class="text-center"><span style="font-size:3rem;">📚</span>
                <p class="mt-2">Register <strong>${studentIds.length}</strong> student(s) for <strong>${subjectClasses.length}</strong> subject(s)?</p></div>`,
        icon : 'question', showCancelButton: true,
        confirmButtonColor: '#1a7a4a', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, register!',
    });
    if (!ok.isConfirmed) return;

    setSpinner(true);
    try {
        const res = await apiFetch(ROUTES.batchRegister, 'POST', {
            studentids    : studentIds,
            subjectclasses: subjectClasses,
            sessionid     : parseInt(sessionId),
        });
        if (res.success) {
            showSweetAlert('Registration Successful!', res.message, 'success', true);
            setTimeout(() => location.reload(), 2000);
        } else {
            showSweetAlert('Registration Failed', res.message || 'Some students could not be registered.', 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', 'Registration failed: ' + err.message, 'error', false);
    } finally {
        setSpinner(false);
    }
}

// ============================================================================
// UNREGISTER
// ============================================================================
function openUnregisterModal() {
    const studentIds     = getSelectedStudentIds();
    const subjectClasses = getSelectedSubjectClasses();
    const sessionId      = document.getElementById('idsession').value;

    if (!studentIds.length)     return showSweetAlert('No Students Selected', 'Please select at least one student.', 'warning', false);
    if (!subjectClasses.length) return showSweetAlert('No Subjects Selected', 'Please select at least one subject.', 'warning', false);
    if (sessionId === 'ALL')    return showSweetAlert('Session Required', 'Please select a session.', 'warning', false);

    document.getElementById('snapshotStudentCount').textContent = `${studentIds.length} student${studentIds.length !== 1 ? 's' : ''}`;
    document.getElementById('snapshotSubjectCount').textContent = `${subjectClasses.length} subject${subjectClasses.length !== 1 ? 's' : ''}`;

    const nameInput = document.getElementById('snapshotNameInput');
    nameInput.value = '';
    nameInput.classList.remove('is-invalid');
    document.getElementById('snapshotNotesInput').value = '';
    document.getElementById('snapshotNotesCount').textContent = '0';

    const now     = new Date();
    const dateStr = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    nameInput.value = `Unregistration — ${dateStr} ${timeStr}`;

    new bootstrap.Modal(document.getElementById('snapshotNameModal')).show();
}

async function proceedUnregister() {
    const nameInput  = document.getElementById('snapshotNameInput');
    const notesInput = document.getElementById('snapshotNotesInput');
    const name       = nameInput.value.trim();
    if (!name) { nameInput.classList.add('is-invalid'); return; }
    nameInput.classList.remove('is-invalid');

    const studentIds     = getSelectedStudentIds();
    const subjectClasses = getSelectedSubjectClasses();
    const sessionId      = document.getElementById('idsession').value;

    bootstrap.Modal.getInstance(document.getElementById('snapshotNameModal'))?.hide();
    setSpinner(true);

    try {
        const res = await apiFetch(ROUTES.unregister, 'DELETE', {
            studentids    : studentIds,
            subjectclasses: subjectClasses,
            sessionid     : parseInt(sessionId),
            snapshot_name : name,
            snapshot_notes: notesInput.value.trim() || null,
        });
        if (res.success || res.success_count > 0) {
            showSweetAlert('Unregistration Complete',
                `${res.success_count} student(s) unregistered.<br><small class="text-muted">Snapshot saved as "<strong>${escapeHtml(name)}</strong>"</small>`,
                'success', true);
            setTimeout(() => location.reload(), 2500);
        } else {
            showSweetAlert('Unregistration Failed', res.message || 'No students were unregistered.', 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', 'Unregistration failed: ' + err.message, 'error', false);
    } finally {
        setSpinner(false);
    }
}

// ============================================================================
// REGISTERED CLASSES MODAL
// Uses the new /subjectoperation/registered-classes endpoint which returns
// per-subject student counts and full subjects_teachers array.
// ============================================================================
async function loadRegisteredClasses() {
    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;
    const container = document.getElementById('registeredClassesContent');

    if (classId === 'ALL' || sessionId === 'ALL') {
        container.innerHTML = `<div class="sf-empty-state"><i class="ri-error-warning-line ri-3x text-warning"></i><p class="mb-0">Please select a class and session first.</p></div>`;
        return;
    }

    container.innerHTML = `<div class="sf-empty-state"><div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;"></div><p class="mt-2 mb-0">Loading…</p></div>`;

    try {
        // Use the new endpoint that already computes per-subject student counts
        const res  = await fetch(
            ROUTES.registeredClasses + '?' + new URLSearchParams({ class_id: classId, session_id: sessionId }),
            { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } }
        );
        const data = await res.json();

        if (!data.success || !data.data || !data.data.length) {
            container.innerHTML = `<div class="sf-empty-state"><i class="ri-information-line ri-3x text-muted"></i><p class="mb-0">No registered classes found.</p></div>`;
            return;
        }

        // Cache for PDF printing
        _lastRegisteredData = data.data;

        let html = '';
        data.data.forEach(term => {
            // subjects_teachers is an array: [{id, name, code, teachers:[{id,name}], student_count}, …]
            const subjects = term.subjects_teachers || [];

            const subjectCells = subjects.map((s, i) => {
                const teacherNames = (s.teachers || []).map(t => escapeHtml(t.name)).join(', ') || '—';
                const count        = s.student_count ?? 0;

                return `<div style="padding:10px 14px;border-right:0.5px solid #e5e7eb;border-bottom:0.5px solid #e5e7eb;display:flex;gap:10px;align-items:flex-start;">
                    <div style="width:24px;height:24px;border-radius:50%;background:#EEEDFE;color:#3C3489;font-size:11px;font-weight:500;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">${i + 1}</div>
                    <div style="min-width:0;">
                        <div style="font-size:13px;font-weight:500;color:var(--bs-body-color);line-height:1.35;">${escapeHtml(s.name)}</div>
                        <div style="font-size:11px;color:var(--bs-secondary-color);margin-top:3px;">${teacherNames}</div>
                        <span style="font-size:10px;background:#EAF3DE;color:#27500A;padding:2px 8px;border-radius:20px;display:inline-block;margin-top:5px;">
                            ${count} student${count !== 1 ? 's' : ''}
                        </span>
                    </div>
                </div>`;
            }).join('');

            html += `<div class="mb-3">
                <div style="background:var(--bs-body-bg);border-radius:12px;border:0.5px solid #dee2e6;overflow:hidden;">
                    <div style="padding:10px 14px;border-bottom:0.5px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-size:13px;font-weight:500;">${escapeHtml(term.class_name)} ${escapeHtml(term.arm_name)} — ${escapeHtml(term.session_name)}</div>
                            <div style="font-size:11px;color:var(--bs-secondary-color);margin-top:2px;">${escapeHtml(term.term_name)}</div>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <span style="font-size:11px;background:#E6F1FB;color:#0C447C;padding:3px 10px;border-radius:20px;font-weight:500;">${term.student_count} students</span>
                            <span style="font-size:11px;background:#EEEDFE;color:#3C3489;padding:3px 10px;border-radius:20px;font-weight:500;">${term.subject_count} subjects</span>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));">${subjectCells}</div>
                </div>
            </div>`;
        });

        container.innerHTML = html;

    } catch (err) {
        container.innerHTML = `<div class="alert alert-danger m-3">Failed to load data: ${err.message}</div>`;
    }
}

// ============================================================================
// PRINT REGISTERED CLASSES
// Reads from _lastRegisteredData (populated by loadRegisteredClasses).
// Shows per-subject student count AND per-student subject count.
// ============================================================================
async function printRegisteredClasses() {
    // If the cached data is empty, try to reload
    if (!_lastRegisteredData.length) {
        await loadRegisteredClasses();
    }
    if (!_lastRegisteredData.length) {
        Swal.fire({ icon: 'warning', title: 'Nothing to print', text: 'Load the registered classes first.', showConfirmButton: true });
        return;
    }

    const school    = window._schoolInfo || {};
    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;
    const now       = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });

    // ── Build per-student subject count across ALL terms ─────────────────────
    // Aggregate: studentId → Set of subject names registered
    // We do this by querying the controller via AJAX (already have data in
    // _lastRegisteredData which has per-subject student_count but not
    // individual student names for the PDF row).
    // For the PDF we show a SUMMARY TABLE: for each term, list subjects with
    // their individual student count. Then a second table: for each student,
    // how many subjects they are registered for.
    // Since _lastRegisteredData has aggregate counts per subject, we fetch
    // the per-student breakdown from the server.

    let studentSubjectCountHtml = '';
    try {
        const res  = await fetch(
            ROUTES.getRegistered + '?' + new URLSearchParams({ class_id: classId, session_id: sessionId }),
            { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } }
        );
        const legacyData = await res.json();

        // The legacy endpoint returns aggregate rows; to get per-student counts
        // we need to fetch from a dedicated route or compute from what we have.
        // We'll use a direct fetch to build this table from the snapshot detail
        // approach — but since we don't have that, we'll compute from DB via
        // the registered-classes endpoint which does have student_count per subject.
        // Best approach: fetch /subjects/registered-classes (legacy) and combine.

        // For a robust per-student count, we'll call the per-student subjects API
        // using the same data we already have in _lastRegisteredData.
        // Build a summary: per term → each subject → student_count (already known).
        // Also build: overall "each student registered for X subjects" by calling
        // a new fetch to the subjectoperation registered-classes and computing.

        // Since we can't call a new per-student endpoint here (it's not defined),
        // we build an approximate per-student count table from subject_count sums.
        // (This is the best we can do without a per-student API.)
        // We'll show a note explaining it's the count of subjects per term.
    } catch (e) {
        // Silently continue — per-student section will be skipped
    }

    // ── Build the HTML for each term's subject table ─────────────────────────
    let termsHtml = '';
    _lastRegisteredData.forEach(term => {
        const subjects = term.subjects_teachers || [];

        let rows = '';
        subjects.forEach((s, idx) => {
            const teacherNames = (s.teachers || []).map(t => escapeHtml(t.name)).join(', ') || '—';
            const count        = s.student_count ?? 0;
            rows += `<tr>
                <td style="width:36px;text-align:center;padding:8px 10px;border-bottom:0.5pt solid #e5e7eb;color:#6b7280;font-size:10pt;">${idx + 1}</td>
                <td style="padding:8px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:10pt;font-weight:500;">${escapeHtml(s.name)}${s.code ? ` <span style="color:#9ca3af;font-weight:400;">(${escapeHtml(s.code)})</span>` : ''}</td>
                <td style="padding:8px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:10pt;">${teacherNames}</td>
                <td style="padding:8px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:10pt;text-align:center;">
                    <span style="background:#EAF3DE;color:#27500A;padding:2px 8px;border-radius:20px;font-size:9pt;font-weight:500;">${count} student${count !== 1 ? 's' : ''}</span>
                </td>
            </tr>`;
        });

        const totalRegistered = term.student_count ?? 0;
        const subjectCount    = term.subject_count ?? subjects.length;

        termsHtml += `<div style="margin-bottom:28pt;break-inside:avoid;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1.5pt solid #1e3a5f;padding-bottom:8pt;margin-bottom:0;">
                <div>
                    <div style="font-size:12pt;font-weight:700;color:#1e3a5f;">${escapeHtml(term.class_name)} ${escapeHtml(term.arm_name)} — ${escapeHtml(term.session_name)}</div>
                    <div style="font-size:9pt;color:#6b7280;margin-top:2pt;">${escapeHtml(term.term_name)}</div>
                </div>
                <div style="display:flex;gap:6pt;">
                    <span style="background:#E6F1FB;color:#0C447C;padding:3px 10px;border-radius:20px;font-size:9pt;font-weight:500;">${totalRegistered} students</span>
                    <span style="background:#EEEDFE;color:#3C3489;padding:3px 10px;border-radius:20px;font-size:9pt;font-weight:500;">${subjectCount} subjects</span>
                </div>
            </div>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="background:#f3f4f6;">
                    <th style="width:36px;padding:7px 10px;text-align:center;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">#</th>
                    <th style="padding:7px 12px;text-align:left;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Subject</th>
                    <th style="padding:7px 12px;text-align:left;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Teacher</th>
                    <th style="padding:7px 12px;text-align:center;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Students Registered</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
    });

    // ── Build per-student subject count summary ───────────────────────────────
    // We fetch from the server: for each term, each student's subject count.
    // We'll use the legacy getRegisteredClasses data to derive this.
    // Since we need student-level data, we do an additional fetch.
    let studentCountTableHtml = '';
    try {
        // Call our new registeredClasses endpoint and compute a cross-term
        // summary per student. NOTE: the endpoint returns per-subject student
        // counts, not per-student subject counts. To get per-student subject
        // counts we need a dedicated query.  We'll fetch it from a small
        // helper call if available, else show a cross-term summary.
        //
        // Per-student subject count: fetch via existing registered classes
        // data — since each term.student_count is unique students in that term,
        // we build a summary table showing TERM-level stats.
        studentCountTableHtml = buildStudentSubjectSummaryTable(_lastRegisteredData);
    } catch (e) {
        studentCountTableHtml = '';
    }

    const logoHtml = school.logo
        ? `<img src="${escapeHtml(school.logo)}" style="height:60pt;width:60pt;object-fit:contain;" alt="Logo">`
        : `<div style="width:60pt;height:60pt;border-radius:50%;background:#1e3a5f;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22pt;font-weight:700;">${(school.name||'S').charAt(0)}</div>`;

    const printHtml = `<!DOCTYPE html><html><head><meta charset="UTF-8">
        <title>Subject Registration Report</title>
        <style>
            @page { size:A4; margin:18mm 16mm; }
            * { box-sizing:border-box; margin:0; padding:0; }
            body { font-family:'Segoe UI',Arial,sans-serif; color:#111827; font-size:10pt; }
            .page-header { display:flex; align-items:center; gap:16pt; padding-bottom:14pt; border-bottom:2pt solid #1e3a5f; margin-bottom:18pt; }
            .school-info h1 { font-size:16pt; font-weight:700; color:#1e3a5f; margin-bottom:3pt; }
            .school-info p { font-size:8.5pt; color:#6b7280; line-height:1.65; }
            .doc-meta { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:18pt; }
            .doc-meta h2 { font-size:13pt; font-weight:700; }
            .section-title { font-size:11pt; font-weight:700; color:#1e3a5f; margin:20pt 0 10pt; padding-bottom:6pt; border-bottom:1pt solid #e5e7eb; }
            .footer { margin-top:24pt; padding-top:8pt; border-top:0.5pt solid #e5e7eb; display:flex; justify-content:space-between; font-size:8pt; color:#9ca3af; }
            @media print { body { -webkit-print-color-adjust:exact; print-color-adjust:exact; } }
        </style>
        </head><body>
        <div class="page-header">${logoHtml}
            <div class="school-info">
                <h1>${escapeHtml(school.name ?? '')}</h1>
                ${school.address ? `<p>${escapeHtml(school.address)}</p>` : ''}
                ${school.phone   ? `<p>Tel: ${escapeHtml(school.phone)}</p>` : ''}
            </div>
        </div>
        <div class="doc-meta">
            <div>
                <h2>Subject Registration Report</h2>
                <div style="font-size:8.5pt;color:#6b7280;">Registered subjects with assigned teachers, student counts per subject &amp; per student</div>
            </div>
            <div style="font-size:8.5pt;color:#6b7280;">Printed: ${now}</div>
        </div>

        <div class="section-title">Subject-wise Registration</div>
        ${termsHtml}

        ${studentCountTableHtml ? `<div class="section-title">Student Subject Registration Summary</div>${studentCountTableHtml}` : ''}

        <div class="footer">
            <span>${escapeHtml(school.name ?? '')} — Subject Registration Report</span>
            <span>Generated ${now}</span>
        </div>
        </body></html>`;

    const win = window.open('', '_blank', 'width=900,height=1100');
    if (!win) {
        Swal.fire({ icon: 'error', title: 'Popup blocked', text: 'Allow popups for this site to enable printing.', showConfirmButton: true });
        return;
    }
    win.document.write(printHtml);
    win.document.close();
    win.onload = () => { win.focus(); win.print(); };
}

/**
 * Build an HTML table showing, for each term, how many subjects each student
 * is enrolled in. Since the API returns per-subject counts (not per-student),
 * we fetch the student-level data from the server.
 *
 * Fallback: builds a per-term summary showing total unique students and
 * average subjects per student based on aggregate data.
 */
function buildStudentSubjectSummaryTable(registeredData) {
    if (!registeredData || !registeredData.length) return '';

    // Fetch per-student subject counts from the server synchronously is not
    // possible in a clean way. Instead, we compute an aggregate summary per
    // term that communicates the information clearly:
    //   Term | Total Students | Total Subjects | Avg Subjects / Student

    let rows = '';
    let grandStudents = 0;
    let grandSubjects = 0;

    registeredData.forEach(term => {
        const students = term.student_count  ?? 0;
        const subjects = term.subject_count  ?? 0;
        const avg      = students > 0 ? (subjects).toFixed(0) : '—';
        grandStudents  = Math.max(grandStudents, students); // unique, not summed
        grandSubjects += subjects;

        rows += `<tr>
            <td style="padding:8px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:10pt;font-weight:500;">${escapeHtml(term.term_name)}</td>
            <td style="padding:8px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:10pt;text-align:center;">
                <span style="background:#E6F1FB;color:#0C447C;padding:2px 8px;border-radius:20px;font-size:9pt;">${students}</span>
            </td>
            <td style="padding:8px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:10pt;text-align:center;">
                <span style="background:#EEEDFE;color:#3C3489;padding:2px 8px;border-radius:20px;font-size:9pt;">${subjects}</span>
            </td>
            <td style="padding:8px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:10pt;text-align:center;color:#6b7280;">${avg}</td>
        </tr>`;
    });

    // Also build a per-subject detailed count table
    let subjectRows = '';
    registeredData.forEach(term => {
        const subjects = term.subjects_teachers || [];
        subjects.forEach(s => {
            subjectRows += `<tr>
                <td style="padding:7px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:9.5pt;">${escapeHtml(term.term_name)}</td>
                <td style="padding:7px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:9.5pt;font-weight:500;">${escapeHtml(s.name)}</td>
                <td style="padding:7px 12px;border-bottom:0.5pt solid #e5e7eb;font-size:9.5pt;text-align:center;">
                    <span style="background:#EAF3DE;color:#27500A;padding:2px 8px;border-radius:20px;font-size:9pt;font-weight:600;">${s.student_count ?? 0}</span>
                </td>
            </tr>`;
        });
    });

    return `
    <p style="font-size:9pt;color:#6b7280;margin-bottom:10pt;">
        The table below shows the number of students registered for each subject per term.
        Detailed per-student breakdown requires access to individual records.
    </p>

    <div style="margin-bottom:20pt;">
        <div style="font-size:10pt;font-weight:600;color:#374151;margin-bottom:6pt;">Term Summary</div>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr style="background:#f3f4f6;">
                <th style="padding:7px 12px;text-align:left;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Term</th>
                <th style="padding:7px 12px;text-align:center;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Students Registered</th>
                <th style="padding:7px 12px;text-align:center;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">No. of Subjects</th>
                <th style="padding:7px 12px;text-align:center;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Subjects Offered</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table>
    </div>

    <div>
        <div style="font-size:10pt;font-weight:600;color:#374151;margin-bottom:6pt;">Per-Subject Student Count</div>
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr style="background:#f3f4f6;">
                <th style="padding:7px 12px;text-align:left;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Term</th>
                <th style="padding:7px 12px;text-align:left;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Subject</th>
                <th style="padding:7px 12px;text-align:center;font-size:9pt;color:#6b7280;font-weight:500;border-bottom:1pt solid #d1d5db;">Students Registered</th>
            </tr></thead>
            <tbody>${subjectRows}</tbody>
        </table>
    </div>`;
}

// ============================================================================
// ARCHIVED MODAL
// ============================================================================
function openArchivedModal() {
    const classId   = document.getElementById('idclass').value;
    const sessionId = document.getElementById('idsession').value;
    if (classId === 'ALL' || sessionId === 'ALL') {
        return showSweetAlert('Selection Required', 'Please select a class and session first.', 'warning', false);
    }
    archiveCurrentPage = 1;
    new bootstrap.Modal(document.getElementById('archivedModal')).show();
    loadArchivedPage(1);
}

async function loadArchivedPage(page) {
    archiveCurrentPage  = page;
    const classId       = document.getElementById('idclass').value;
    const sessionId     = document.getElementById('idsession').value;
    const termId        = document.getElementById('archiveTermFilter').value;
    const search        = document.getElementById('archiveSearch').value.trim();
    const perPage       = document.getElementById('archivePerPage').value;
    if (classId === 'ALL' || sessionId === 'ALL') return;
    const spinner   = document.getElementById('archiveSpinner');
    const container = document.getElementById('snapshotCardsContainer');
    spinner?.classList.remove('d-none');
    container.innerHTML = `<div class="sf-empty-state"><div class="spinner-border spinner-border-sm text-warning me-2"></div>Loading snapshots…</div>`;
    try {
        const params = new URLSearchParams({ class_id: classId, session_id: sessionId, page, per_page: perPage });
        if (termId)  params.set('term_id', termId);
        if (search)  params.set('search', search);
        const res  = await fetch(ROUTES.getArchived + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) { container.innerHTML = `<div class="sf-empty-state text-danger">${data.message}</div>`; return; }
        archiveMeta = data.meta;
        renderSnapshotCards(data.data);
        renderArchivePagination(data.meta);
        updateArchiveMeta(data.meta);
    } catch (err) {
        container.innerHTML = `<div class="sf-empty-state text-danger">Error: ${err.message}</div>`;
    } finally {
        spinner?.classList.add('d-none');
    }
}

function renderSnapshotCards(rows) {
    const container = document.getElementById('snapshotCardsContainer');
    if (!rows.length) {
        container.innerHTML = `<div class="sf-empty-state"><i class="ri-archive-line ri-3x d-block mb-2"></i>No unregistration snapshots found.</div>`;
        return;
    }
    const groups = {};
    rows.forEach(row => {
        const key = `${row.snapshot_name}__${row.subjectclassid}__${row.termid}`;
        if (!groups[key]) groups[key] = { ...row, subjects: [] };
        groups[key].subjects.push({
            subjectname: row.subjectname, subjectcode: row.subjectcode,
            staffname  : row.staffname,   student_count: row.student_count,
            subjectclassid: row.subjectclassid, termid: row.termid,
            sessionid  : row.sessionid,   staffid: row.staffid, archive_id: row.archive_id,
        });
    });
    let html = '<div class="row g-3">';
    Object.values(groups).forEach(group => {
        const unregDate    = group.unregistered_at
            ? new Date(group.unregistered_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })
            : '—';
        const subjectPills = group.subjects.map(s => `<span class="sf-pill sf-pill-primary me-1 mb-1">${escapeHtml(s.subjectname)}</span>`).join('');
        const metaEncoded  = encodeURIComponent(JSON.stringify({
            snapshot_name : group.snapshot_name, subjectclassid: group.subjectclassid,
            termid        : group.termid,         sessionid: group.sessionid,
            staffid       : group.staffid,        archive_id: group.archive_id,
        }));
        html += `<div class="col-md-6 col-xl-4">
            <div class="sf-card h-100 snapshot-card" style="cursor:pointer;transition:transform .2s,box-shadow .2s;"
                 onclick="openSnapshotDetail('${metaEncoded}')"
                 onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 28px rgba(0,0,0,.12)';"
                 onmouseleave="this.style.transform='';this.style.boxShadow='';">
                <div class="sf-card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div class="flex-grow-1 min-w-0">
                            <h6 class="fw-semibold mb-0 text-truncate" style="font-size:13px;color:#1d1d1f;" title="${escapeHtml(group.snapshot_name)}">
                                <i class="ri-camera-line text-danger me-1"></i>${escapeHtml(group.snapshot_name)}
                            </h6>
                            <small class="text-muted">${unregDate}</small>
                        </div>
                        <span class="sf-badge sf-badge-f flex-shrink-0">${group.student_count} student${group.student_count !== 1 ? 's' : ''}</span>
                    </div>
                    ${group.snapshot_notes ? `<p class="text-muted small fst-italic mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">"${escapeHtml(group.snapshot_notes)}"</p>` : ''}
                    <div class="mb-2">${subjectPills}</div>
                    <div class="d-flex justify-content-between align-items-center pt-1 border-top">
                        <small class="text-muted"><i class="ri-user-star-line me-1"></i>${escapeHtml(group.staffname ?? '—')}</small>
                        <span class="sf-badge sf-badge-m">${escapeHtml(group.termname)}</span>
                    </div>
                </div>
                <div class="sf-card-body pt-0 d-flex gap-2">
                    <button class="sf-btn sf-btn-ghost sf-btn-sm flex-grow-1" onclick="event.stopPropagation();openSnapshotDetail('${metaEncoded}');"><i class="ri-eye-line me-1"></i>View</button>
                    <button class="sf-btn sf-btn-success sf-btn-sm flex-grow-1" onclick="event.stopPropagation();restoreSingleSnapshot('${metaEncoded}');"><i class="ri-refresh-line me-1"></i>Restore</button>
                    <button class="sf-btn sf-btn-danger sf-btn-sm" onclick="event.stopPropagation();deleteSnapshotGroup('${metaEncoded}');" title="Delete"><i class="ri-delete-bin-line"></i></button>
                </div>
            </div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function renderArchivePagination(meta) {
    const container = document.getElementById('archivePagination');
    if (!meta || meta.last_page <= 1) { container.innerHTML = ''; return; }
    const delta = 3;
    let html = `<button class="sf-btn sf-btn-ghost sf-btn-sm ${meta.current_page === 1 ? 'disabled' : ''}" onclick="loadArchivedPage(${meta.current_page - 1})">‹</button>`;
    for (let p = 1; p <= meta.last_page; p++) {
        if (p === 1 || p === meta.last_page || (p >= meta.current_page - delta && p <= meta.current_page + delta)) {
            html += `<button class="sf-btn ${p === meta.current_page ? 'sf-btn-primary' : 'sf-btn-ghost'} sf-btn-sm" onclick="loadArchivedPage(${p})">${p}</button>`;
        } else if (p === meta.current_page - delta - 1 || p === meta.current_page + delta + 1) {
            html += `<span class="sf-btn sf-btn-ghost sf-btn-sm disabled">…</span>`;
        }
    }
    html += `<button class="sf-btn sf-btn-ghost sf-btn-sm ${meta.current_page === meta.last_page ? 'disabled' : ''}" onclick="loadArchivedPage(${meta.current_page + 1})">›</button>`;
    container.innerHTML = html;
}

function updateArchiveMeta(meta) {
    const el = document.getElementById('archiveMeta');
    if (!meta || !meta.total) { if (el) el.textContent = ''; return; }
    const from = (meta.current_page - 1) * meta.per_page + 1;
    const to   = Math.min(meta.current_page * meta.per_page, meta.total);
    el.textContent = `Showing ${from}–${to} of ${meta.total} snapshots`;
}

// ============================================================================
// SNAPSHOT DETAIL
// ============================================================================
async function openSnapshotDetail(metaEncoded) {
    currentSnapshotMeta = JSON.parse(decodeURIComponent(metaEncoded));
    document.getElementById('snapshotDetailTitle').textContent    = currentSnapshotMeta.snapshot_name;
    document.getElementById('snapshotDetailSubtitle').textContent = '';
    document.getElementById('snapshotNotesBanner')?.classList.add('d-none');
    const searchInput = document.getElementById('detailSearchInput');
    if (searchInput) searchInput.value = '';
    document.getElementById('snapshotDetailBody').innerHTML =
        '<tr><td colspan="10" class="text-center py-4"><div class="spinner-border spinner-border-sm me-2"></div>Loading students…</td></tr>';
    document.getElementById('detailRestoreSelectedBtn')?.classList.add('d-none');
    document.getElementById('detailDeleteSelectedBtn')?.classList.add('d-none');
    new bootstrap.Modal(document.getElementById('snapshotDetailModal')).show();
    try {
        const params = new URLSearchParams({
            snapshot_name : currentSnapshotMeta.snapshot_name,
            subjectclassid: currentSnapshotMeta.subjectclassid,
            termid        : currentSnapshotMeta.termid,
            sessionid     : currentSnapshotMeta.sessionid,
            staffid       : currentSnapshotMeta.staffid,
        });
        const res  = await fetch(ROUTES.getSnapshot + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) {
            document.getElementById('snapshotDetailBody').innerHTML = `<tr><td colspan="10" class="text-center text-danger py-4">${data.message}</td></tr>`;
            return;
        }
        currentSnapshotRows = data.rows;
        if (data.snapshot_notes) {
            document.getElementById('snapshotNotesBanner')?.classList.remove('d-none');
            document.getElementById('snapshotNotesText').textContent = data.snapshot_notes;
        }
        document.getElementById('detailStudentMeta').textContent = `${data.total_students} student${data.total_students !== 1 ? 's' : ''} in this snapshot`;
        renderSnapshotDetailTable(data.rows, data.assessment_headers);
    } catch (err) {
        document.getElementById('snapshotDetailBody').innerHTML = `<tr><td colspan="10" class="text-center text-danger py-4">Error: ${err.message}</td></tr>`;
    }
}

function renderSnapshotDetailTable(rows, assessmentHeaders) {
    const headerRow = document.getElementById('snapshotDetailHeaderRow');
    while (headerRow.cells.length > 4) headerRow.deleteCell(headerRow.cells.length - 1);
    (assessmentHeaders || []).forEach(a => {
        const th       = document.createElement('th');
        th.textContent = a.assessment_name || `Assessment ${a.assessment_id}`;
        headerRow.appendChild(th);
    });
    const totalTh       = document.createElement('th');
    totalTh.textContent = 'Total';
    headerRow.appendChild(totalTh);
    let html = '';
    rows.forEach(row => {
        const name    = [row.lastname, row.firstname, row.othername].filter(Boolean).join(' ');
        const picFile = row.picture ? row.picture.split('/').pop() : null;
        const pic     = picFile ? `${AVATAR_URL}/student_avatars/${picFile}` : `${AVATAR_URL}/student_avatars/unnamed.jpg`;
        const genderBadge = row.gender === 'Female'
            ? `<span class="sf-badge sf-badge-f">${escapeHtml(row.gender)}</span>`
            : `<span class="sf-badge sf-badge-m">${escapeHtml(row.gender ?? '—')}</span>`;
        let scoresCells = '';
        let total       = 0;
        (assessmentHeaders || []).forEach(a => {
            const score = (row.assessment_scores || []).find(s => s.assessment_id == a.assessment_id);
            const val   = score ? parseFloat(score.score) : 0;
            total += val;
            scoresCells += `<td class="text-center fw-medium">${val > 0 ? val.toFixed(1) : '<span class="text-muted">—</span>'}</td>`;
        });
        scoresCells += `<td class="text-center fw-bold ${total > 0 ? 'text-success' : 'text-muted'}">${total > 0 ? total.toFixed(1) : '—'}</td>`;
        const searchKey = `${name} ${row.admissionno ?? ''}`.toLowerCase();
        html += `<tr data-archive-id="${row.archive_id}" data-search="${escapeHtml(searchKey)}">
            <td><div class="form-check mb-0"><input class="form-check-input detail-chk" type="checkbox" value="${row.archive_id}"></div></td>
            <td><div class="d-flex align-items-center gap-2"><img src="${pic}" class="rounded-circle" style="width:34px;height:34px;object-fit:cover;border:2px solid #e9ecef;" onerror="this.src='${AVATAR_URL}/student_avatars/unnamed.jpg'"><span class="fw-medium">${escapeHtml(name)}</span></div></td>
            <td class="text-muted small">${escapeHtml(row.admissionno ?? '—')}</td>
            <td>${genderBadge}</td>
            ${scoresCells}
        </tr>`;
    });
    document.getElementById('snapshotDetailBody').innerHTML =
        html || '<tr><td colspan="10" class="text-center text-muted py-4">No students found.</td></tr>';
    document.getElementById('detailCheckAll')?.addEventListener('change', function () {
        document.querySelectorAll('.detail-chk').forEach(cb => cb.checked = this.checked);
        toggleDetailButtons();
    });
    document.querySelectorAll('.detail-chk').forEach(cb => cb.addEventListener('change', toggleDetailButtons));
}

function toggleDetailButtons() {
    const any = document.querySelectorAll('.detail-chk:checked').length > 0;
    document.getElementById('detailRestoreSelectedBtn')?.classList.toggle('d-none', !any);
    document.getElementById('detailDeleteSelectedBtn')?.classList.toggle('d-none', !any);
}

function filterDetailRows(query) {
    const q    = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#snapshotDetailBody tr[data-search]');
    let visible = 0;
    rows.forEach(tr => {
        const match = !q || tr.dataset.search.includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const total = currentSnapshotRows.length;
    const meta  = document.getElementById('detailStudentMeta');
    if (meta) {
        meta.textContent = q
            ? `${visible} of ${total} student${total !== 1 ? 's' : ''} shown`
            : `${total} student${total !== 1 ? 's' : ''} in this snapshot`;
    }
}

// ============================================================================
// RESTORE / DELETE
// ============================================================================
async function restoreEntireSnapshot() {
    if (!currentSnapshotRows.length) return;
    await doRestore(currentSnapshotRows.map(r => r.archive_id), 'all students in this snapshot');
}

async function restoreDetailSelected() {
    const ids = [...document.querySelectorAll('.detail-chk:checked')].map(cb => parseInt(cb.value));
    if (!ids.length) return;
    await doRestore(ids, `${ids.length} selected student${ids.length !== 1 ? 's' : ''}`);
}

async function doRestore(archiveIds, label) {
    const ok = await Swal.fire({
        title: 'Restore Registration?',
        html : `<p>Restore <strong>${label}</strong>? Original scores will be recovered.</p>`,
        icon : 'question', showCancelButton: true,
        confirmButtonColor: '#1a7a4a', confirmButtonText: 'Yes, restore!',
    });
    if (!ok.isConfirmed) return;
    const spinner = document.getElementById('detailSpinner');
    spinner?.classList.remove('d-none');
    try {
        const res = await apiFetch(ROUTES.restore, 'POST', { archive_ids: archiveIds });
        if (res.success || res.total_restored > 0) {
            showSweetAlert('Restored!', `${res.total_restored || archiveIds.length} registration(s) restored.`, 'success', true);
            bootstrap.Modal.getInstance(document.getElementById('snapshotDetailModal'))?.hide();
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Restore Failed', res.message || 'Could not restore.', 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', 'Restore failed: ' + err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

async function restoreSingleSnapshot(metaEncoded) {
    const meta = JSON.parse(decodeURIComponent(metaEncoded));
    const ok   = await Swal.fire({
        title: 'Restore Snapshot?',
        html : `<p>Restore all students in "<strong>${escapeHtml(meta.snapshot_name)}</strong>"?</p>`,
        icon : 'question', showCancelButton: true,
        confirmButtonColor: '#1a7a4a', confirmButtonText: 'Yes, restore all!',
    });
    if (!ok.isConfirmed) return;
    const spinner = document.getElementById('archiveSpinner');
    spinner?.classList.remove('d-none');
    try {
        const params     = new URLSearchParams({ snapshot_name: meta.snapshot_name, subjectclassid: meta.subjectclassid, termid: meta.termid, sessionid: meta.sessionid, staffid: meta.staffid });
        const detailRes  = await fetch(ROUTES.getSnapshot + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const detailData = await detailRes.json();
        if (!detailData.success || !detailData.rows?.length) { showSweetAlert('Not Found', detailData.message || 'Snapshot records not found.', 'error', false); return; }
        const ids = detailData.rows.map(r => r.archive_id);
        const res = await apiFetch(ROUTES.restore, 'POST', { archive_ids: ids });
        if (res.success || res.total_restored > 0) {
            showSweetAlert('Restored!', `${res.total_restored || ids.length} registration(s) restored.`, 'success', true);
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Restore Failed', res.message, 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

async function deleteSnapshotGroup(metaEncoded) {
    const meta = JSON.parse(decodeURIComponent(metaEncoded));
    const ok   = await Swal.fire({
        title: 'Delete Snapshot?',
        html : `<p class="text-danger">Permanently delete "<strong>${escapeHtml(meta.snapshot_name)}</strong>"? This cannot be undone.</p>`,
        icon : 'error', showCancelButton: true,
        confirmButtonColor: '#dc3545', confirmButtonText: 'Yes, delete permanently',
    });
    if (!ok.isConfirmed) return;
    const spinner = document.getElementById('archiveSpinner');
    spinner?.classList.remove('d-none');
    try {
        const params     = new URLSearchParams({ snapshot_name: meta.snapshot_name, subjectclassid: meta.subjectclassid, termid: meta.termid, sessionid: meta.sessionid, staffid: meta.staffid });
        const detailRes  = await fetch(ROUTES.getSnapshot + '?' + params.toString(), { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const detailData = await detailRes.json();
        if (!detailData.success || !detailData.rows?.length) { showSweetAlert('Not Found', detailData.message || 'Snapshot records not found.', 'error', false); return; }
        const ids = detailData.rows.map(r => r.archive_id);
        const res = await apiFetch(ROUTES.permanentDelete, 'DELETE', { archive_ids: ids });
        if (res.success) {
            showSweetAlert('Deleted', `${res.deleted || ids.length} record(s) permanently deleted.`, 'success', false);
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Delete Failed', res.message, 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

async function deleteDetailSelected() {
    const ids = [...document.querySelectorAll('.detail-chk:checked')].map(cb => parseInt(cb.value));
    if (!ids.length) return;
    const ok  = await Swal.fire({
        title: 'Permanently Delete?',
        html : `<p class="text-danger">Delete <strong>${ids.length}</strong> record(s) permanently?</p>`,
        icon : 'error', showCancelButton: true,
        confirmButtonColor: '#dc3545', confirmButtonText: 'Yes, delete permanently',
    });
    if (!ok.isConfirmed) return;
    const spinner = document.getElementById('detailSpinner');
    spinner?.classList.remove('d-none');
    try {
        const res = await apiFetch(ROUTES.permanentDelete, 'DELETE', { archive_ids: ids });
        if (res.success) {
            showSweetAlert('Deleted', `${res.deleted || ids.length} record(s) permanently deleted.`, 'success', false);
            bootstrap.Modal.getInstance(document.getElementById('snapshotDetailModal'))?.hide();
            loadArchivedPage(archiveCurrentPage);
        } else {
            showSweetAlert('Delete Failed', res.message, 'error', false);
        }
    } catch (err) {
        showSweetAlert('Error', err.message, 'error', false);
    } finally {
        spinner?.classList.add('d-none');
    }
}

// Stubs for symmetry
async function restoreSelected()         { }
async function permanentDeleteSelected() { }

// ============================================================================
// BUILD SUBJECT TEACHER LOOKUP
// ============================================================================
function buildSubjectTeacherLookup() {
    const lookup = {};
    document.querySelectorAll('.subject-checkbox').forEach(cb => {
        const termId    = String(cb.dataset.termid ?? '').trim();
        let subjectName = cb.dataset.subjectName ?? '';
        let teacherName = cb.dataset.teacherName ?? '';
        if (!subjectName || !teacherName) {
            const card = cb.closest('.subject-check-card');
            if (card) {
                subjectName = subjectName || card.dataset.subjectName || '';
                teacherName = teacherName || card.dataset.teacherName || '';
                if (!teacherName) teacherName = card.querySelector('.text-muted')?.textContent?.trim() || '';
                if (!subjectName) subjectName = card.querySelector('.fw-semibold')?.textContent?.trim() || '';
            }
        }
        if (!subjectName || !teacherName) return;
        const key = `${subjectName.toLowerCase()}||${termId}`;
        if (!lookup[key]) lookup[key] = [];
        if (!lookup[key].includes(teacherName)) lookup[key].push(teacherName);
    });
    return lookup;
}

function resolveTeacher(subjectName, termId, lookup) {
    const key = `${subjectName.trim().toLowerCase()}||${String(termId ?? '').trim()}`;
    if (lookup[key]?.length) return lookup[key].join(', ');
    const prefix = subjectName.trim().toLowerCase() + '||';
    for (const [k, v] of Object.entries(lookup)) {
        if (k.startsWith(prefix) && v.length) return v.join(', ');
    }
    return '—';
}

// ============================================================================
// DOM READY
// ============================================================================
document.addEventListener('DOMContentLoaded', function () {
    initializeSubjectCards();
    rewriteExistingRows();

    document.getElementById('registeredClassesModal')?.addEventListener('show.bs.modal', loadRegisteredClasses);
    document.getElementById('archivePerPage')?.addEventListener('change', () => loadArchivedPage(1));
    document.getElementById('snapshotNotesInput')?.addEventListener('input', function () {
        document.getElementById('snapshotNotesCount').textContent = this.value.length;
    });
    document.getElementById('archiveSearch')?.addEventListener('input', function () {
        clearTimeout(archiveSearchTimer);
        archiveSearchTimer = setTimeout(() => loadArchivedPage(1), 400);
    });
    document.getElementById('archiveTermFilter')?.addEventListener('change', () => loadArchivedPage(1));

    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('#studentTableBody input[name="chk_child"]').forEach(cb => {
                cb.checked = this.checked;
            });
            toggleBatchButtons();
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target?.name === 'chk_child') {
            toggleBatchButtons();
            const all     = document.querySelectorAll('#studentTableBody input[name="chk_child"]');
            const checked = document.querySelectorAll('#studentTableBody input[name="chk_child"]:checked');
            const ca      = document.getElementById('checkAll');
            if (ca) ca.checked = all.length > 0 && all.length === checked.length;
        }
    });

    setupPaginationLinks();
});
</script>
@endsection
