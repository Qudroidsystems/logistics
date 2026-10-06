@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">My Mock Subject Vetting Assignments</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('mymocksubjectvettings.index') }}">Mock Subject Vetting</a></li>
                                <li class="breadcrumb-item active">My Assignments</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('danger'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('danger') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="mockSubjectVettingList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search vetting assignments">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div>
                                            <select class="form-control" id="idTerm" data-choices data-choices-search-false data-choices-removeItem>
                                                <option value="all">Select Term</option>
                                                @foreach ($terms as $term)
                                                    <option value="{{ $term->term }}">{{ $term->term }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div>
                                            <select class="form-control" id="idSession" data-choices data-choices-search-false data-choices-removeItem>
                                                <option value="all">Select Session</option>
                                                @foreach ($sessions as $session)
                                                    <option value="{{ $session->session }}">{{ $session->session }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-1 col-sm-6">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();"><i class="bi bi-funnel align-baseline me-1"></i> Filters</button>
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
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">My Mock Vetting Assignments <span class="badge bg-dark-subtle text-dark ms-1" id="total-records">{{ $mocksubjectvettings->count() }}</span></h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_mock_subject_vetting_table">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-125px sort cursor-pointer" data-sort="subjectname">Subject</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="teachername">Teacher</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="sclass">Class</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="schoolarm">Arm</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="termname">Term</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="sessionname">Session</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="status">Status</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list">
                                            @forelse ($mocksubjectvettings as $sv)
                                                <tr data-id="{{ $sv->svid }}">
                                                    <td class="subjectname" data-subjectid="{{ $sv->subjectid }}">{{ $sv->subjectname }} ({{ $sv->subjectcode }})</td>
                                                    <td class="teachername" data-subtid="{{ $sv->staffid }}">{{ $sv->teachername }}</td>
                                                    <td class="sclass" data-schoolclassid="{{ $sv->schoolclassid }}">{{ $sv->sclass }}</td>
                                                    <td class="schoolarm">{{ $sv->schoolarm }}</td>
                                                    <td class="termname" data-termid="{{ $sv->termid }}">{{ $sv->termname }}</td>
                                                    <td class="sessionname" data-sessionid="{{ $sv->sessionid }}">{{ $sv->sessionname }}</td>
                                                    <td class="status">{{ $sv->status }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('View my-mock-subject-vettings')
                                                                <li>
                                                                    <a href="{{ route('mymocksubjectvettings.classbroadsheet', [$sv->schoolclassid, $sv->subjectclassId, $sv->staffid, $sv->termid, $sv->sessionid]) }}" title="Mock Broadsheet for {{ $sv->sclass }} {{ $sv->schoolarm }}" class="btn btn-subtle-success btn-icon"><i class="ph-eye"></i></a>
                                                                </li>
                                                            @endcan
                                                            @can('Update my-mock-subject-vettings')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"><i class="ph-pencil"></i></a>
                                                                </li>
                                                            @endcan
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="noresult">
                                                    <td colspan="8" class="text-center">No results found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span id="showing-records">0</span> of <span id="total-records-footer">{{ $mocksubjectvettings->count() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap">
                                            <nav aria-label="Page navigation">
                                                <ul class="pagination listjs-pagination"></ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="editModal" class="modal fade" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="editModalLabel" class="modal-title">Update Mock Vetting Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form class="tablelist-form" autocomplete="off" id="edit-mocksubjectvetting-form">
                                <div class="modal-body">
                                    <input type="hidden" id="edit-id-field" name="id">
                                    <div class="mb-3">
                                        <label for="edit-status" class="form-label">Status</label>
                                        <select name="status" id="edit-status" class="form-control" required>
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    window.vettingStatusCounts = @json($statusCounts);
    console.log('Initial vettingStatusCounts:', window.vettingStatusCounts);
</script>
{{-- <script src="{{ asset('js/mymocksubjectvetting.init.js') }}"></script> --}}
@endsection