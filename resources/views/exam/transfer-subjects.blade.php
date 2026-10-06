@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.subject-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}
.subject-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    transform: translateY(-2px);
}
.subject-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Transfer Exam Scores - Select Subject</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">Exams</a></li>
                                <li class="breadcrumb-item"><a href="{{ url()->previous() }}">Students</a></li>
                                <li class="breadcrumb-item active">Select Subject</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Filter Summary Card -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-primary rounded-circle">
                                            <i class="ph-funnel fs-4"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">Selected Filters</h5>
                                    <p class="text-muted mb-0">
                                        <span class="badge bg-primary me-2" id="selectedTerm"></span>
                                        <span class="badge bg-info me-2" id="selectedSession"></span>
                                        <span class="badge bg-success" id="subjectCount">0 Subjects</span>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <button class="btn btn-outline-primary" onclick="window.history.back()">
                                        <i class="ph-arrow-left me-1"></i> Change Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subjects Grid -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    <i class="ph-book-open me-2"></i>My Subjects
                                </h5>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="input-group" style="width: 300px;">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search subjects...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="ph-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="subjectsLoader" style="display: none;" class="text-center py-5">
                                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3 text-muted">Loading your subjects...</p>
                            </div>

                            <div id="noSubjectsAlert" class="alert alert-info text-center" style="display: none;">
                                <i class="ph-info fs-3 mb-3 d-block"></i>
                                <h5>No Subjects Found</h5>
                                <p class="mb-0">No subjects found for the selected term and session.</p>
                            </div>

                            <div class="row" id="subjectsContainer">
                                <!-- Subjects will be loaded here dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const termId = urlParams.get('termid');
    const sessionId = urlParams.get('sessionid');

    if (!termId || !sessionId) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Parameters',
            text: 'Please select both term and session.'
        }).then(() => {
            window.location.href = '{{ route("exams.index") }}';
        });
        return;
    }

    // Load subjects
    loadSubjects(termId, sessionId);

    function loadSubjects(termId, sessionId) {
        const loader = document.getElementById('subjectsLoader');
        const container = document.getElementById('subjectsContainer');
        const noAlert = document.getElementById('noSubjectsAlert');
        const subjectCount = document.getElementById('subjectCount');

        loader.style.display = 'block';
        container.innerHTML = '';
        noAlert.style.display = 'none';

        const formData = new FormData();
        formData.append('termid', termId);
        formData.append('sessionid', sessionId);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('{{ route("exams.transfer.subjects.post") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            loader.style.display = 'none';

            if (data.success) {
                // Update filter badges
                document.getElementById('selectedTerm').textContent = data.data.term || `Term ${termId}`;
                document.getElementById('selectedSession').textContent = data.data.session || `Session ${sessionId}`;

                const subjects = data.data.mysubjects;
                subjectCount.textContent = subjects.length + ' Subjects';

                if (subjects.length === 0) {
                    noAlert.style.display = 'block';
                } else {
                    displaySubjects(subjects);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to load subjects'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            loader.style.display = 'none';
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'An error occurred while loading subjects'
            });
        });
    }

    function displaySubjects(subjects) {
        const container = document.getElementById('subjectsContainer');
        let html = '';

        subjects.forEach(subject => {
            const subjectclassid = subject.subjectclassid;
            const schoolclassid = subject.schoolclassid;
            const userid = subject.userid;
            const termid = subject.termid;
            const sessionid = subject.session_id;

            const scoresheetUrl = `/exams/transfer/scoresheet/${schoolclassid}/${subjectclassid}/${userid}/${termid}/${sessionid}`;

            html += `
                <div class="col-md-6 col-lg-4 subject-item">
                    <div class="subject-card">
                        <div class="d-flex">
                            <div class="subject-icon me-3">
                                <i class="ph-book"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">${subject.subject}</h5>
                                <p class="text-muted mb-2">
                                    <span class="badge bg-light text-dark me-1">${subject.subjectcode}</span>
                                    <span class="badge bg-light text-dark">${subject.schoolclass}</span>
                                </p>
                                <p class="small text-muted mb-3">
                                    <i class="ph-chalkboard-teacher me-1"></i> ${subject.staffname || 'Unknown'}<br>
                                    <i class="ph-tag me-1"></i> ${subject.classcategories || 'No categories'}
                                </p>
                                <a href="${scoresheetUrl}" class="btn btn-primary btn-sm w-100">
                                    <i class="ph-arrow-right me-1"></i> Transfer Scores
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const subjectItems = document.querySelectorAll('.subject-item');

        function filterSubjects() {
            const searchTerm = searchInput.value.toLowerCase();

            subjectItems.forEach(item => {
                const subjectName = item.querySelector('h5').textContent.toLowerCase();
                const subjectCode = item.querySelector('.badge').textContent.toLowerCase();
                const className = item.querySelector('.text-muted .badge:last-child').textContent.toLowerCase();

                if (subjectName.includes(searchTerm) ||
                    subjectCode.includes(searchTerm) ||
                    className.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterSubjects);

        if (clearSearch) {
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                subjectItems.forEach(item => item.style.display = '');
            });
        }
    }
});
</script>
@endsection
