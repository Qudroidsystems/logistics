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
                               Student Bulk Upload
                                        </h1>
                                <!--end::Title-->


                                    <!--begin::Breadcrumb-->
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                                        <!--begin::Item-->
                                                        <li class="breadcrumb-item text-muted">
                                                            <a href="{{ route('student.index') }}" class="text-muted text-hover-primary">Student Bulk Upload </a>
                                                                        </li>
                                                            <!--end::Item-->
                                                                <!--begin::Item-->
                                                <li class="breadcrumb-item">
                                                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                                </li>
                                                <!--end::Item-->

                                                        <!--begin::Item-->
                                                                <li class="breadcrumb-item text-muted">Student Bulk Upload</li>
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
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
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

                                <!--begin::Actions-->
<div class="d-flex align-items-center gap-2 gap-lg-3">


<!--begin::Secondary button-->


<!--begin::Primary button-->
    <a href="{{ route('student.batchindex') }}" class="btn btn-sm fw-bold btn-primary" >
    << Back        </a>
<!--end::Primary button-->
</div>
<!--end::Actions-->
                            </div>
                            <!--end::Toolbar container-->
                        </div>
                    <!--end::Toolbar-->


        <div id="kt_app_content" class="app-content  flex-column-fluid " >
            <!--begin::Content container-->

        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container  ">
           <form id="kt_ecommerce_add_category_form" class="form d-flex flex-column flex-lg-row" action="{{ route('student.bulkuploadsave') }}" method="POST" enctype="multipart/form-data">
                @csrf

    <!--begin::Main column-->
    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
        <!--begin::General options-->
<div class="card card-flush py-4">
    <!--begin::Card header-->
    <div class="card-header">
        <div class="card-title">
            <h2>Student Bulk Upload</h2>
        </div>
    </div>
    <!--end::Card header-->

    <!--begin::Card body-->
    <div class="card-body pt-0">

        <div class="row mb-6">
            <!--begin::Label-->
            <label class="col-lg-4 col-form-label required fw-semibold fs-6">Batch Upload Title</label>
            <!--end::Label-->

            <!--begin::Col-->
            <div class="col-lg-8">
                <!--begin::Row-->
                <div class="row">
                    <!--begin::Col-->
                    <div class="col-lg-6 fv-row">
                        <input type="text" name="title" id="title" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" placeholder="Batch Title"  required />
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Col-->
        </div>
        <!--end::Input group-->


                            <!--begin::Input group-->
                    <div class="mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6 mb-5">Select Class and Arm </label>
                        <!--end::Label-->
                                <!--begin::Input row-->
                                <div class="fv-row mb-7">

                                        <!--begin::Input-->
                                        <select name ="schoolclassid" id="schoolclassid"  class="form-control form-control-solid mb-3 mb-lg-0" required>
                                            <option value="">Select Class and Arm</option>
                                            @foreach ($schoolclass as $sc)
                                            <option value="{{ $sc->id }}"> {{ $sc->schoolclass }}.....{{ $sc->arm }}</option>
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
                                <select name ="termid" id="termid"  class="form-control form-control-solid mb-3 mb-lg-0" required>
                                    <option value="">Select Term</option>
                                    @foreach ($schoolterm as $sc)
                                    <option value="{{ $sc->id }}"> {{ $sc->term }}</option>
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
                                                <select name ="sessionid" id="sessionid"  class="form-control form-control-solid mb-3 mb-lg-0" required>
                                                    <option value="">Select Session</option>
                                                    @foreach ($schoolsession as $sc)
                                                    <option value="{{ $sc->id }}"> {{ $sc->session }}</option>
                                                    @endforeach
                                                </select>
                                                <!--end::Input-->

                                        </div>
                                        <!--end::Input row-->
                                    </div>
                                    <!--end::Input group-->


                                     <!--begin::Input group-->
            <div class="row mb-6">
                <!--begin::Label-->
                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Batch Upload</label>
                <!--end::Label-->

                <!--begin::Col-->
                <div class="col-lg-8">
                    <!--begin::Row-->
                    <div class="row">
                        <!--begin::Col-->
                        <div class="col-lg-6 fv-row">
                            <input type="file" name="filesheet" id="filesheet" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" placeholder="Admission Number" required  />
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->


                    </div>
                    <!--end::Card header-->

                    <div class="d-flex justify-content-end">
                        <!--begin::Button-->
                        <a href="products.html" id="kt_ecommerce_add_product_cancel" class="btn btn-light me-5">
                        Cancel
                    </a>
                    <!--end::Button-->

                    <!--begin::Button-->
                    <button type="submit" id="kt_ecommerce_add_category_submit" class="btn btn-primary">
                        <span class="indicator-label">
                            Proceed
                        </span>
                        <span class="indicator-progress">
                            Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    <!--end::Button-->
                </div>
                </div>
                <!--end::General options-->

                    </div>
                    <!--end::Main column-->
                </form>
 </div>

            <!--end::Content container-->
        </div>
                <!--end::Content-->


@endsection
