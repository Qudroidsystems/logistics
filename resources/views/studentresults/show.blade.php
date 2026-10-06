
@extends('layouts.master')
@section('content')


           <!--begin::Main-->
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
            <!--begin::Content wrapper-->
            <div class="d-flex flex-column flex-column-fluid">

<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar  py-3 py-lg-6 " >

        <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container  container-xxl d-flex flex-stack ">



<!--begin::Page title-->
<div  class="page-title d-flex flex-column justify-content-center flex-wrap me-3 ">
<!--begin::Title-->
<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
   Student Management
        </h1>
<!--end::Title-->


    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <!--begin::Item-->
                                <li class="breadcrumb-item text-muted">
                                                <a href="../index.html" class="text-muted text-hover-primary">
                          Student Managemt                           </a>
                                        </li>
                            <!--end::Item-->
                                <!--begin::Item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <!--end::Item-->

                        <!--begin::Item-->
                                <li class="breadcrumb-item text-muted">
                                               Add Student Record                                            </li>
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

<!--begin::Content-->
<div id="kt_app_content" class="app-content  flex-column-fluid " >


    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container  container-xxl ">


                                <!--begin::Actions-->
                                <div class="d-flex align-items-center gap-2 gap-lg-3">


                                    <!--begin::Secondary button-->


                                    <!--begin::Primary button-->
                                        <a href="{{ route('student.index') }}" class="btn btn-sm fw-bold btn-primary" >
                                        << Back        </a>
                                    <!--end::Primary button-->
                                    </div>
                                    <!--end::Actions-->

<!--begin::Basic info-->
<div class="card mb-5 mb-xl-10">
<!--begin::Card header-->
<div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
    <!--begin::Card title-->
    <div class="card-title m-0">
        <h3 class="fw-bold m-0">Student Details</h3>
    </div>
    <!--end::Card title-->
</div>

<!--begin::Card header-->
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
<!--begin::Content-->
<div id="kt_account_settings_profile_details" class="collapse show">
    <!--begin::Form-->

    {!! Form::model($schoolclassid, ['route' => ['studentresults.update',$schoolclassid], 'method'=>'PATCH','class'=>'form-horizontal form-stripe','onSubmit'=>'return validateForm();']) !!}
    @csrf



            <!--begin::Card body-->
            <div class="card-body border-top p-9">
                <!--begin::Input group-->
                <div class="row mb-6">
                    <!--begin::Label-->
                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">Term</label>
                    <!--end::Label-->

                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">

                        <select  class="form-control form-control-lg form-control-solid" name ="termid" id="termid">
                            <option value="" selected>Select Term </option>
                            @foreach ($schoolterms as $term => $name )
                             <option value="{{$name->id}}">{{ $name->term}}</option>
                            @endforeach
                        </select>


                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Input group-->
            </div>


                  <!--begin::Card body-->
        <div class="card-body border-top p-9">
            <!--begin::Input group-->
            <div class="row mb-6">
                <!--begin::Label-->
                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Sessionn</label>
                <!--end::Label-->

                <!--begin::Col-->
                <div class="col-lg-8 fv-row">

                    <select  class="form-control form-control-lg form-control-solid"  name ="sessionid" id="sessiionid">
                        <option value="" selected>Select Session </option>
                        @foreach ($schoolsessions as $schoolsession => $name )
                         <option value="{{$name->id}}">{{ $name->session}}</option>
                        @endforeach
                    </select>

                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->
        </div>





                    <div class="d-flex">
                        {{-- <button type="reset" class="btn btn-light btn-active-light-primary me-2">Discard</button> --}}
                        <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">Proceed</button>
                    </div>
                    {!! Form::close() !!}
                <!--end::Form-->
            </div>
            <!--end::Edit-->


        </div>
<!--end::Wrapper-->
</div>
<!--end::Notice-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Content-->
</div>
<!--end::Sign-in Method-->




</div>
    <!--end::Content container-->
</div>
<!--end::Content-->
            </div>
            <!--end::Content wrapper-->

@endsection

