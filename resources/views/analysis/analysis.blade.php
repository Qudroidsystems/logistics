@extends('layouts.master')
@section('content')

            <!--begin::Main-->
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <!--begin::Content wrapper-->
                <div class="d-flex flex-column flex-column-fluid">

                <!--begin::Toolbar-->
                <div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 ">

                            <!--begin::Toolbar container-->
                        <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">

                            <!--begin::Page title-->
                            <div  class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">
                                <!--begin::Title-->
                                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                               Analysis Book for Term and Session
                                        </h1>
                                <!--end::Title-->


                                    <!--begin::Breadcrumb-->
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                        <!--begin::Item-->
                                                        <li class="breadcrumb-item text-muted">
                                                            <a href="{{ route('analysis.index') }}" class="text-muted text-hover-primary">School BIlls for Term and Sessions</a>
                                                                        </li>
                                                            <!--end::Item-->
                                                                <!--begin::Item-->
                                                <li class="breadcrumb-item">
                                                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                                </li>
                                                <!--end::Item-->

                                                        <!--begin::Item-->
                                                                <li class="breadcrumb-item text-muted">Analysis for Term and Session</li>
                                                            <!--end::Item-->

                                                </ul>
                                    <!--end::Breadcrumb-->
                                </div>
                            <!--end::Page title-->
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

                                @if (\Session::has('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ \Session::get('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                @endif
                                @if (\Session::has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ \Session::get('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                @endif

                            </div>
                            <!--end::Toolbar container-->
                        </div>
                    <!--end::Toolbar-->


                    <div id="kt_app_content" class="app-content  flex-column-fluid " >
                        <!--begin::Content container-->
                        <div id="kt_app_content_container" class="app-container  container-xxl ">

                   <!--begin::Toolbar-->
                        <div class="d-flex flex-wrap flex-stack my-5">
                            <!--begin::Heading-->
                            <h2 class="fs-2 fw-semibold my-2">
                              Analysis for Class, Term and Session
                                <span class="fs-6 text-gray-400 ms-1">Database</span>
                            </h2>
                            <!--end::Heading-->


                        </div>
                    <!--end::Toolbar-->


                    <div class="btn-group">
    <a href="{{ route('school.wide.payment.analysis', ['termid_id' => 3, 'session_id' => 1, 'action' => 'view']) }}" 
       class="btn btn-primary" target="_blank">
        <i class="fas fa-eye"></i> View School-Wide Analysis
    </a>
    <a href="{{ route('school.wide.payment.analysis', ['termid_id' => 3, 'session_id' => 1, 'action' => 'download']) }}" 
       class="btn btn-success">
        <i class="fas fa-download"></i> Download School-Wide Analysis
    </a>
    <!-- Single buttons -->
<a href="{{ route('school.wide.payment.analysis', ['termid_id' => 3, 'session_id' => 1, 'action' => 'download', 'format' => 'pdf']) }}" 
   class="btn btn-success">
    <i class="fas fa-download"></i> Download PDF
</a>

<a href="{{ route('school.wide.payment.analysis', ['termid_id' => 3, 'session_id' => 1, 'action' => 'download', 'format' => 'word']) }}" 
   class="btn btn-success">
    <i class="fas fa-download"></i> Download Word
</a>
</div>

        <!--begin::Card-->
    <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">

                  <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                              <!--begin::Toolbar-->
                                    <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                                <!--begin::Add user-->
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                                                    <i class="ki-duotone ki-plus fs-2"></i>     Select Class, Term and Session to View a Specific Class
                                                </button>
                                                <!--end::Add user-->
                                    </div>

                                     <!--begin::Modal - Add task-->
                                        <div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true">
                                            <!--begin::Modal dialog-->
                                            <div class="modal-dialog modal-dialog-centered mw-650px">
                                                <!--begin::Modal content-->
                                                <div class="modal-content">
                                                    <!--begin::Modal header-->
                                                    <div class="modal-header" id="kt_modal_add_user_header">
                                                        <!--begin::Modal title-->
                                                        <h2 class="fw-bold">  Select Class, Term and Session</h2>
                                                        <!--end::Modal title-->

                                                        <!--begin::Close-->
                                                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-users-modal-action="close">
                                                            <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>                </div>
                                                        <!--end::Close-->
                                                    </div>
                                                    <!--end::Modal header-->


                                                    <!--begin::Modal body-->
                                                    <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                                                        <!--begin::Form-->
                                                        <form id="kt_modal_add_user_form" class="form" action="{{ route('analysis.analysisClassTermSession') }}" method="POST">
                                                            @csrf
                                                            <!--begin::Scroll-->
                                                            <div class="d-flex flex-column scroll-y me-n7 pe-7" id="kt_modal_add_user_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_user_header" data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">


                                                                    <div class="mb-7">
                                                                        <!--begin::Label-->
                                                                        <label class="required fw-semibold fs-6 mb-5">Select  Class </label>
                                                                        <!--end::Label-->
                                                                                <!--begin::Input row-->
                                                                                <div class="fv-row mb-7">

                                                                                        <!--begin::Input-->

                                                                                        <select name ="class_id" id="class_id" data-control="select2" data-placeholder="Select an option" data-hide-search="true" class="form-select form-select-solid form-select-lg fw-semibold fs-6 text-gray-700">
                                                                                            <option value="" selected>Select Class </option>
                                                                                            @foreach ($schoolclasses as $schoolclass )
                                                                                             <option value="{{$schoolclass->id}}">{{ $schoolclass->schoolclass }} {{ $schoolclass->schoolarm }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                        <!--end::Input-->

                                                                                </div>
                                                                                <!--end::Input row-->
                                                                            </div>
                                                                            <!--end::Input group-->



                                                                  <!--begin::Input group-->
                                                                  <div class="mb-7">
                                                                    <!--begin::Label-->
                                                                    <label class="required fw-semibold fs-6 mb-5">Select Term</label>
                                                                    <!--end::Label-->
                                                                            <!--begin::Input row-->
                                                                            <div class="fv-row mb-7">
                                                                                    <!--begin::Input-->

                                                                                    <select name ="termid_id" id="termid" data-control="select2" data-placeholder="Select an option" data-hide-search="true" class="form-select form-select-solid form-select-lg fw-semibold fs-6 text-gray-700">
                                                                                        <option value="" selected>Select Term </option>
                                                                                        @foreach ($schoolterms as $term => $name )
                                                                                        <option value="{{$name->id}}">{{ $name->term}}</option>
                                                                                       @endforeach
                                                                                    </select>
                                                                                    <!--end::Input-->
                                                                            </div>
                                                                            <!--end::Input row-->
                                                                        </div>
                                                                        <!--end::Input group-->



                                                                          <!--begin::Input group-->
                                                                          <div class="mb-7">
                                                                            <!--begin::Label-->
                                                                            <label class="required fw-semibold fs-6 mb-5">Select Session</label>
                                                                            <!--end::Label-->
                                                                                    <!--begin::Input row-->
                                                                                    <div class="fv-row mb-7">

                                                                                            <!--begin::Input-->

                                                                                            <select name ="session_id" id="sessionid" class="sel-sesson form-control form-control-solid mb-3 mb-lg-0" data-control="select2" data-placeholder="Select an option" data-hide-search="true"
                                                                                            class="sel-sesson form-select form-select-solid form-select-lg fw-semibold fs-6 text-gray-700">
                                                                                                <option value="" selected>Select Session </option>
                                                                                                @foreach ($schoolsessions as $schoolsession => $name )
                                                                                                 <option value="{{$name->id}}">{{ $name->session}}</option>
                                                                                                @endforeach
                                                                                            </select>
                                                                                            <!--end::Input-->

                                                                                    </div>
                                                                                    <!--end::Input row-->
                                                                                </div>
                                                                                <!--end::Input group-->


                                                            </div>
                                                            <!--end::Scroll-->

                                                            <!--begin::Actions-->
                                                            <div class="text-center pt-15">
                                                                <button type="reset" class="btn btn-light me-3" data-kt-users-modal-action="cancel">
                                                                    Discard
                                                                </button>

                                                                <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
                                                                    <span class="indicator-label">
                                                                        Submit
                                                                    </span>
                                                                    <span class="indicator-progress">
                                                                        Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                                    </span>
                                                                </button>
                                                            </div>
                                                            <!--end::Actions-->
                                                        </form>
                                                        <!--end::Form-->
                                                    </div>
                                                    <!--end::Modal body-->
                                                </div>
                                                <!--end::Modal content-->
                                            </div>
                                            <!--end::Modal dialog-->
                                            </div>
                                    <!--end::Modal - Add task-->



                                    </div>
                                    <!--end::Card toolbar-->
                                            <!--begin::Card toolbar-->
                                            <div class="card-toolbar">
                                                <!--begin::Search-->
                                                <div class="d-flex align-items-center position-relative my-1"  data-kt-view-roles-table-toolbar="base">
                                                    <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-6"><span class="path1"></span><span class="path2"></span></i>                <input type="text" data-kt-roles-table-filter="search" class="form-control form-control-solid w-250px ps-15" placeholder="Search ..." />
                                                </div>
                                                <!--end::Search-->

                                                <!--begin::Group actions-->
                                                <div class="d-flex justify-content-end align-items-center d-none" data-kt-view-roles-table-toolbar="selected">
                                                    <div class="fw-bold me-5">
                                                        <span class="me-2" data-kt-view-roles-table-select="selected_count"></span> Selected
                                                    </div>

                                                    <button type="button" class="btn btn-danger" data-kt-view-roles-table-select="delete_selected">
                                                        Delete Selected
                                                    </button>
                                                </div>
                                                <!--end::Group actions-->
                                            </div>
                                            <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->

            @if (count($errors) > 0)
            <div class="row animated fadeInUp">
                @if (count($errors) > 0)
            <div class="alert alert-warning fade in">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
                <strong>Opps!</strong> Something went wrong, please check below errors.<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            </div>
            @endif
            <!--begin::Card body-->
            <div class="card-body py-4">



            </div>
            <!--end::Card body-->
      </div>
            <!--end::Card-->
    </div>

            <!--end::Content container-->
</div>
        <!--end::Content-->


@endsection
