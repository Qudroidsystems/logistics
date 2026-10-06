@extends('layouts.master')

@section('content')
<style>
    .highlight-red { color: red !important; }
    .avatar-sm { width: 32px; height: 32px; object-fit: cover; }
    .table-active { background-color: rgba(0, 0, 0, 0.05); }
    .table-centered th, .table-centered td { text-align: center; vertical-align: middle; }
    .table-nowrap th, .table-nowrap td { white-space: nowrap; }
    .sort.cursor-pointer:hover { background-color: #f5f5f5; }
    .bg-success-subtle { background-color: #d4edda !important; }
    .bg-danger-subtle { background-color: #f8d7da !important; }
    .bg-warning-subtle { background-color: #fff3cd !important; }
    input[readonly] { background-color: #f8f9fa; cursor: not-allowed; }
    .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: 0.4s; border-radius: 50%; }
    input:checked + .slider { background-color: #28a745; }
    input:checked + .slider:before { transform: translateX(26px); }

    @media (max-width: 767px) {
        .table-responsive { overflow-x: hidden; }
        .table { display: block; }
        .table thead { display: none; }
        .table tbody, .table tr, .table td { display: block; width: 100%; }
        .table tr { margin-bottom: 15px; padding: 10px; border: 1px solid #dee2e6; border-radius: 5px; }
        .table td { text-align: left !important; padding: 8px; position: relative; border: none; }
        .table td:before { content: attr(data-label); font-weight: bold; display: inline-block; width: 40%; padding-right: 10px; }
        .table td.admissionno, .table td.name { font-size: 1.1rem; font-weight: 500; }
        .table td input[readonly] { width: 60%; display: inline-block; }
        .table td .toggle-switch { width: 60%; display: inline-block; }
        .avatar-sm { width: 24px; height: 24px; }
    }
</style>

<div class="main-content class-broadsheet">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Mock Terminal Broadsheet</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('mymocksubjectvettings.index') }}">Mock Subject Vetting</a></li>
                                <li class="breadcrumb-item active">Mock Terminal Broadsheet</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($broadsheets->isNotEmpty())
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="d-flex flex-wrap flex-stack mb-4">
                                        <div class="d-flex flex-column flex-grow-1 pe-8">
                                            <div class="d-flex flex-wrap">
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-book fs-3 text-primary me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->subject }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Subject</div>
                                                </div>
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-code fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->subject_code }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Subject Code</div>
                                                </div>
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->schoolclass }} {{ $broadsheets->first()->arm }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Class</div>
                                                </div>
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-calendar fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->term }} | {{ $broadsheets->first()->session }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Term | Session</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h5 class="card-title mb-0">Mock Terminal Broadsheet for {{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->arm : 'N/A' }} - {{ $schoolterm }} ({{ $schoolsession }})</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm bg-white">
                                        <div class="search-box mb-3">
                                            <input type="text" class="form-control search" placeholder="Search students, admission no...">
                                        </div>
                                        <div class="mt-3 result-table">
                                            <div id="studentListTable" class="table-responsive">
                                                <table class="table table-centered align-middle table-nowrap mb-0">
                                                    <thead class="table-active">
                                                        <tr>
                                                            <th>SN</th>
                                                            <th>Admission No</th>
                                                            <th>Name</th>
                                                            <th>Exam</th>
                                                            <th>Vetted Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php $i = 0; @endphp
                                                        @forelse ($broadsheets as $broadsheet)
                                                            <tr class="{{ $broadsheet->vettedstatus === '1' ? 'bg-success-subtle' : ($broadsheet->vettedstatus === '0' ? 'bg-danger-subtle' : 'bg-warning-subtle') }}">
                                                                <td data-label="SN">{{ ++$i }}</td>
                                                                <td data-label="Admission No" class="admissionno">{{ $broadsheet->admissionno ?? '-' }}</td>
                                                                <td data-label="Name" class="name">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="avatar-sm me-2">
                                                                            <img src="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" alt="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '') }}" class="rounded-circle w-100 student-image" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" data-picture="{{ $broadsheet->picture ?? 'none' }}" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                                        </div>
                                                                        <div class="d-flex flex-column">
                                                                            <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span> {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td data-label="Exam">
                                                                    <input type="text" class="form-control text-center" value="{{ $broadsheet->exam ? number_format($broadsheet->exam, 1) : '0.0' }}" readonly>
                                                                </td>
                                                                <td data-label="Vetted Status">
                                                                    <label class="toggle-switch">
                                                                        <input type="checkbox" class="vetted-status-toggle" data-broadsheet-id="{{ $broadsheet->id }}" {{ $broadsheet->vettedstatus === '1' ? 'checked' : '' }}>
                                                                        <span class="slider"></span>
                                                                    </label>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center">No scores available.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-warning">
                            No student data found for this class, term, and session.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('.search');
        const tableRows = document.querySelectorAll('#studentListTable tbody tr');

        searchInput.addEventListener('input', function () {
            const searchQuery = this.value.trim().toLowerCase();

            tableRows.forEach(row => {
                const admissionNo = row.querySelector('.admissionno').textContent.toLowerCase();
                const name = row.querySelector('.name').textContent.toLowerCase();

                if (searchQuery === '') {
                    row.style.display = '';
                } else if (admissionNo.includes(searchQuery) || name.includes(searchQuery)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        const toggles = document.querySelectorAll('.vetted-status-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('change', function () {
                const broadsheetId = this.getAttribute('data-broadsheet-id');
                const vettedStatus = this.checked ? 1 : 0;
                const row = this.closest('tr');

                row.classList.remove('bg-success-subtle', 'bg-danger-subtle', 'bg-warning-subtle');
                if (vettedStatus === 1) {
                    row.classList.add('bg-success-subtle');
                } else {
                    row.classList.add('bg-danger-subtle');
                }

                fetch('{{ route('mymocksubjectvettings.update-vetted-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        broadsheet_id: broadsheetId,
                        vettedstatus: vettedStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Vetted status updated successfully');
                    } else {
                        console.error('Failed to update vetted status:', data.message);
                        this.checked = !this.checked;
                        row.classList.remove('bg-success-subtle', 'bg-danger-subtle', 'bg-warning-subtle');
                        row.classList.add(vettedStatus === 1 ? 'bg-danger-subtle' : 'bg-success-subtle');
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.checked = !this.checked;
                    row.classList.remove('bg-success-subtle', 'bg-danger-subtle', 'bg-warning-subtle');
                    row.classList.add(vettedStatus === 1 ? 'bg-danger-subtle' : 'bg-success-subtle');
                    alert('Error updating vetted status');
                });
            });
        });
    });
</script>
@endsection